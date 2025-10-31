<?php
/**
 * Live Survey Monitoring
 * 
 * Tracks users currently taking surveys in real-time
 * 
 * @package RM_Panel_Extensions
 * @version 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Survey_Live_Monitor {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // AJAX handlers for live monitoring
        add_action('wp_ajax_rm_get_live_survey_stats', [$this, 'ajax_get_live_stats']);
        add_action('wp_ajax_rm_get_active_users', [$this, 'ajax_get_active_users']);
        
        // Track survey page visits
        add_action('wp', [$this, 'track_survey_visit']);
        
        // Heartbeat API for real-time updates
        add_action('wp_ajax_heartbeat', [$this, 'heartbeat_update']);
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    /**
     * Add admin menu under Surveys
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=rm_survey',
            __('Live Monitoring', 'rm-panel-extensions'),
            __('📊 Live Monitor', 'rm-panel-extensions'),
            'manage_options',
            'rm-survey-live-monitor',
            [$this, 'render_live_monitor_page']
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'rm_survey_page_rm-survey-live-monitor') {
            return;
        }
        
        wp_enqueue_style(
            'rm-live-monitor',
            RM_PANEL_EXT_PLUGIN_URL . 'assets/css/live-monitor.css',
            [],
            RM_PANEL_EXT_VERSION
        );
        
        wp_enqueue_script(
            'rm-live-monitor',
            RM_PANEL_EXT_PLUGIN_URL . 'assets/js/live-monitor.js',
            ['jquery'],
            RM_PANEL_EXT_VERSION,
            true
        );
        
        wp_localize_script('rm-live-monitor', 'rmLiveMonitor', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rm_live_monitor'),
            'refresh_interval' => 5000 // 5 seconds
        ]);
    }
    
    /**
     * Track survey page visits
     */
    public function track_survey_visit() {
        if (!is_user_logged_in() || !is_singular('rm_survey')) {
            return;
        }
        
        global $post;
        $user_id = get_current_user_id();
        $survey_id = $post->ID;
        
        // Store active session in transient (expires in 2 minutes)
        $session_key = 'rm_active_survey_' . $user_id . '_' . $survey_id;
        set_transient($session_key, [
            'user_id' => $user_id,
            'survey_id' => $survey_id,
            'started' => current_time('mysql'),
            'last_active' => current_time('mysql')
        ], 2 * MINUTE_IN_SECONDS);
    }
    
    /**
     * Get live statistics
     */
    public function ajax_get_live_stats() {
        check_ajax_referer('rm_live_monitor', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        
        // Get currently active surveys (started in last 2 minutes, not completed)
        $active_surveys = $wpdb->get_results("
            SELECT 
                r.*,
                u.display_name,
                p.post_title as survey_title
            FROM {$table_name} r
            LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
            LEFT JOIN {$wpdb->posts} p ON r.survey_id = p.ID
            WHERE r.status = 'started'
            AND r.start_time >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
            ORDER BY r.start_time DESC
        ");
        
        // Get waiting to complete (started but not completed in last 24 hours)
        $waiting_complete = $wpdb->get_results("
            SELECT 
                r.*,
                u.display_name,
                p.post_title as survey_title,
                TIMESTAMPDIFF(MINUTE, r.start_time, NOW()) as minutes_waiting
            FROM {$table_name} r
            LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
            LEFT JOIN {$wpdb->posts} p ON r.survey_id = p.ID
            WHERE r.status = 'started'
            AND r.start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND r.start_time < DATE_SUB(NOW(), INTERVAL 2 MINUTE)
            ORDER BY r.start_time DESC
        ");
        
        // Get today's completions
        $today_completed = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$table_name}
            WHERE status = 'completed'
            AND DATE(completion_time) = CURDATE()
        ");
        
        // Get today's starts
        $today_started = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$table_name}
            WHERE DATE(start_time) = CURDATE()
        ");
        
        // Calculate conversion rate
        $conversion_rate = $today_started > 0 ? round(($today_completed / $today_started) * 100, 2) : 0;
        
        wp_send_json_success([
            'active_now' => count($active_surveys),
            'waiting_complete' => count($waiting_complete),
            'today_completed' => intval($today_completed),
            'today_started' => intval($today_started),
            'conversion_rate' => $conversion_rate,
            'active_surveys' => $active_surveys,
            'waiting_surveys' => $waiting_complete,
            'timestamp' => current_time('mysql')
        ]);
    }
    
    /**
     * Get active users on site
     */
    public function ajax_get_active_users() {
        check_ajax_referer('rm_live_monitor', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        
        // Get users active in last 5 minutes (based on user meta)
        $active_users = $wpdb->get_results("
            SELECT 
                u.ID,
                u.display_name,
                u.user_email,
                um.meta_value as last_activity
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = 'rm_last_activity'
            AND um.meta_value >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY um.meta_value DESC
        ");
        
        wp_send_json_success([
            'active_users' => $active_users,
            'total_active' => count($active_users)
        ]);
    }
    
    /**
     * Heartbeat API update
     */
    public function heartbeat_update($response, $data) {
        if (!empty($data['rm_monitor_active'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'rm_survey_responses';
            
            // Get quick stats
            $active_count = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$table_name}
                WHERE status = 'started'
                AND start_time >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
            ");
            
            $response['rm_active_surveys'] = intval($active_count);
        }
        
        return $response;
    }
    
    /**
     * Render live monitor page
     */
    public function render_live_monitor_page() {
        ?>
        <div class="wrap rm-live-monitor-page">
            <h1>
                <?php _e('Live Survey Monitoring', 'rm-panel-extensions'); ?>
                <span class="rm-live-indicator">
                    <span class="rm-live-dot"></span>
                    <?php _e('LIVE', 'rm-panel-extensions'); ?>
                </span>
            </h1>
            
            <!-- Stats Cards -->
            <div class="rm-stats-cards">
                <div class="rm-stat-card rm-stat-active">
                    <div class="rm-stat-icon">🔴</div>
                    <div class="rm-stat-content">
                        <div class="rm-stat-label"><?php _e('Active Now', 'rm-panel-extensions'); ?></div>
                        <div class="rm-stat-value" id="rm-active-now">0</div>
                    </div>
                </div>
                
                <div class="rm-stat-card rm-stat-waiting">
                    <div class="rm-stat-icon">⏳</div>
                    <div class="rm-stat-content">
                        <div class="rm-stat-label"><?php _e('Waiting to Complete', 'rm-panel-extensions'); ?></div>
                        <div class="rm-stat-value" id="rm-waiting-complete">0</div>
                    </div>
                </div>
                
                <div class="rm-stat-card rm-stat-completed">
                    <div class="rm-stat-icon">✅</div>
                    <div class="rm-stat-content">
                        <div class="rm-stat-label"><?php _e('Completed Today', 'rm-panel-extensions'); ?></div>
                        <div class="rm-stat-value" id="rm-today-completed">0</div>
                    </div>
                </div>
                
                <div class="rm-stat-card rm-stat-conversion">
                    <div class="rm-stat-icon">📈</div>
                    <div class="rm-stat-content">
                        <div class="rm-stat-label"><?php _e('Conversion Rate', 'rm-panel-extensions'); ?></div>
                        <div class="rm-stat-value" id="rm-conversion-rate">0%</div>
                    </div>
                </div>
            </div>
            
            <!-- Active Surveys Table -->
            <div class="rm-monitor-section">
                <h2><?php _e('🔴 Users Taking Surveys Right Now', 'rm-panel-extensions'); ?></h2>
                <div id="rm-active-surveys-table">
                    <p class="rm-loading"><?php _e('Loading...', 'rm-panel-extensions'); ?></p>
                </div>
            </div>
            
            <!-- Waiting to Complete Table -->
            <div class="rm-monitor-section">
                <h2><?php _e('⏳ Waiting to Complete', 'rm-panel-extensions'); ?></h2>
                <div id="rm-waiting-surveys-table">
                    <p class="rm-loading"><?php _e('Loading...', 'rm-panel-extensions'); ?></p>
                </div>
            </div>
            
            <!-- Active Users -->
            <div class="rm-monitor-section">
                <h2><?php _e('👥 Active Users on Site', 'rm-panel-extensions'); ?></h2>
                <div id="rm-active-users-list">
                    <p class="rm-loading"><?php _e('Loading...', 'rm-panel-extensions'); ?></p>
                </div>
            </div>
            
            <p class="rm-last-update">
                <?php _e('Last updated:', 'rm-panel-extensions'); ?>
                <span id="rm-last-update-time">--</span>
            </p>
        </div>
        <?php
    }
}

// Initialize
RM_Survey_Live_Monitor::get_instance();