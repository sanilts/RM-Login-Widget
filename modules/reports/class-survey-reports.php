<?php
/**
 * Survey Reports with Excel Export
 * 
 * Detailed survey completion reports with filtering and Excel export
 * 
 * @package RM_Panel_Extensions
 * @version 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Survey_Reports {
    
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
        
        // Handle Excel export
        add_action('admin_init', [$this, 'handle_excel_export']);
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=rm_survey',
            __('Survey Reports', 'rm-panel-extensions'),
            __('📊 Survey Reports', 'rm-panel-extensions'),
            'manage_options',
            'rm-survey-reports',
            [$this, 'render_reports_page']
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'rm_survey_page_rm-survey-reports') {
            return;
        }
        
        wp_enqueue_style(
            'rm-survey-reports',
            RM_PANEL_EXT_PLUGIN_URL . 'assets/css/survey-reports.css',
            [],
            RM_PANEL_EXT_VERSION
        );
        
        wp_enqueue_script(
            'rm-survey-reports',
            RM_PANEL_EXT_PLUGIN_URL . 'assets/js/survey-reports.js',
            ['jquery', 'jquery-ui-datepicker'],
            RM_PANEL_EXT_VERSION,
            true
        );
        
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    }
    
    /**
     * Get filtered survey responses
     */
    private function get_filtered_responses($filters = []) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        
        $where_clauses = ['1=1'];
        
        // Survey filter
        if (!empty($filters['survey_id'])) {
            $where_clauses[] = $wpdb->prepare('r.survey_id = %d', $filters['survey_id']);
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $where_clauses[] = $wpdb->prepare('r.status = %s', $filters['status']);
        }
        
        // Completion status filter
        if (!empty($filters['completion_status'])) {
            $where_clauses[] = $wpdb->prepare('r.completion_status = %s', $filters['completion_status']);
        }
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $where_clauses[] = $wpdb->prepare('DATE(r.start_time) >= %s', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = $wpdb->prepare('DATE(r.start_time) <= %s', $filters['date_to']);
        }
        
        // User filter
        if (!empty($filters['user_id'])) {
            $where_clauses[] = $wpdb->prepare('r.user_id = %d', $filters['user_id']);
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $sql = "
            SELECT 
                r.*,
                u.display_name,
                u.user_email,
                p.post_title as survey_title,
                TIMESTAMPDIFF(MINUTE, r.start_time, r.completion_time) as duration_minutes
            FROM {$table_name} r
            LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
            LEFT JOIN {$wpdb->posts} p ON r.survey_id = p.ID
            WHERE {$where_sql}
            ORDER BY r.start_time DESC
        ";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Handle Excel export
     */
    public function handle_excel_export() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'rm_export_survey_reports') {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('rm_export_reports');
        
        // Get filters
        $filters = [
            'survey_id' => isset($_GET['survey_id']) ? intval($_GET['survey_id']) : '',
            'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '',
            'completion_status' => isset($_GET['completion_status']) ? sanitize_text_field($_GET['completion_status']) : '',
            'date_from' => isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '',
            'date_to' => isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '',
            'user_id' => isset($_GET['user_id']) ? intval($_GET['user_id']) : ''
        ];
        
        $responses = $this->get_filtered_responses($filters);
        
        // Generate CSV (Excel compatible)
        $filename = 'survey-reports-' . date('Y-m-d-His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'ID',
            'User Name',
            'User Email',
            'Survey Title',
            'Status',
            'Completion Status',
            'Started',
            'Completed',
            'Duration (minutes)',
            'IP Address',
            'User Agent'
        ]);
        
        // Data rows
        foreach ($responses as $response) {
            fputcsv($output, [
                $response->id,
                $response->display_name,
                $response->user_email,
                $response->survey_title,
                $response->status,
                $response->completion_status,
                $response->start_time,
                $response->completion_time,
                $response->duration_minutes,
                $response->ip_address,
                $response->user_agent
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Render reports page
     */
    public function render_reports_page() {
        // Get filters from URL
        $filters = [
            'survey_id' => isset($_GET['filter_survey']) ? intval($_GET['filter_survey']) : '',
            'status' => isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '',
            'completion_status' => isset($_GET['filter_completion']) ? sanitize_text_field($_GET['filter_completion']) : '',
            'date_from' => isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '',
            'date_to' => isset($_GET['filter_date_to']) ? sanitize_text_field($_GET['filter_date_to']) : '',
            'user_id' => isset($_GET['filter_user']) ? intval($_GET['filter_user']) : ''
        ];
        
        $responses = $this->get_filtered_responses($filters);
        
        // Get all surveys for filter dropdown
        $surveys = get_posts([
            'post_type' => 'rm_survey',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        ?>
        <div class="wrap rm-survey-reports-page">
            <h1><?php _e('Survey Completion Reports', 'rm-panel-extensions'); ?></h1>
            
            <!-- Filters -->
            <div class="rm-reports-filters">
                <form method="get" action="">
                    <input type="hidden" name="post_type" value="rm_survey">
                    <input type="hidden" name="page" value="rm-survey-reports">
                    
                    <div class="rm-filter-row">
                        <!-- Survey Filter -->
                        <div class="rm-filter-field">
                            <label><?php _e('Survey:', 'rm-panel-extensions'); ?></label>
                            <select name="filter_survey">
                                <option value=""><?php _e('All Surveys', 'rm-panel-extensions'); ?></option>
                                <?php foreach ($surveys as $survey) : ?>
                                    <option value="<?php echo $survey->ID; ?>" <?php selected($filters['survey_id'], $survey->ID); ?>>
                                        <?php echo esc_html($survey->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="rm-filter-field">
                            <label><?php _e('Status:', 'rm-panel-extensions'); ?></label>
                            <select name="filter_status">
                                <option value=""><?php _e('All Statuses', 'rm-panel-extensions'); ?></option>
                                <option value="started" <?php selected($filters['status'], 'started'); ?>><?php _e('Started', 'rm-panel-extensions'); ?></option>
                                <option value="completed" <?php selected($filters['status'], 'completed'); ?>><?php _e('Completed', 'rm-panel-extensions'); ?></option>
                            </select>
                        </div>
                        
                        <!-- Completion Status Filter -->
                        <div class="rm-filter-field">
                            <label><?php _e('Result:', 'rm-panel-extensions'); ?></label>
                            <select name="filter_completion">
                                <option value=""><?php _e('All Results', 'rm-panel-extensions'); ?></option>
                                <option value="success" <?php selected($filters['completion_status'], 'success'); ?>><?php _e('Success', 'rm-panel-extensions'); ?></option>
                                <option value="quota_complete" <?php selected($filters['completion_status'], 'quota_complete'); ?>><?php _e('Quota Complete', 'rm-panel-extensions'); ?></option>
                                <option value="disqualified" <?php selected($filters['completion_status'], 'disqualified'); ?>><?php _e('Disqualified', 'rm-panel-extensions'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="rm-filter-row">
                        <!-- Date From -->
                        <div class="rm-filter-field">
                            <label><?php _e('Date From:', 'rm-panel-extensions'); ?></label>
                            <input type="text" name="filter_date_from" class="rm-datepicker" 
                                   value="<?php echo esc_attr($filters['date_from']); ?>" 
                                   placeholder="YYYY-MM-DD">
                        </div>
                        
                        <!-- Date To -->
                        <div class="rm-filter-field">
                            <label><?php _e('Date To:', 'rm-panel-extensions'); ?></label>
                            <input type="text" name="filter_date_to" class="rm-datepicker" 
                                   value="<?php echo esc_attr($filters['date_to']); ?>" 
                                   placeholder="YYYY-MM-DD">
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="rm-filter-field rm-filter-actions">
                            <button type="submit" class="button button-primary">
                                <?php _e('Apply Filters', 'rm-panel-extensions'); ?>
                            </button>
                            <a href="<?php echo admin_url('edit.php?post_type=rm_survey&page=rm-survey-reports'); ?>" 
                               class="button">
                                <?php _e('Clear Filters', 'rm-panel-extensions'); ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Export Button -->
            <div class="rm-export-section">
                <a href="<?php echo wp_nonce_url(add_query_arg(array_merge([
                    'action' => 'rm_export_survey_reports',
                    'post_type' => 'rm_survey',
                    'page' => 'rm-survey-reports'
                ], $filters)), 'rm_export_reports'); ?>" class="button button-primary">
                    📥 <?php _e('Export to Excel', 'rm-panel-extensions'); ?>
                </a>
                <span class="rm-export-count">
                    <?php printf(__('(%d records)', 'rm-panel-extensions'), count($responses)); ?>
                </span>
            </div>
            
            <!-- Results Table -->
            <div class="rm-reports-table-wrapper">
                <?php if (empty($responses)) : ?>
                    <p><?php _e('No survey responses found matching your filters.', 'rm-panel-extensions'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('User', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Survey', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Started', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Completed', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Duration', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Status', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Result', 'rm-panel-extensions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($responses as $response) : ?>
                                <tr>
                                    <td><?php echo $response->id; ?></td>
                                    <td>
                                        <strong><?php echo esc_html($response->display_name); ?></strong><br>
                                        <small><?php echo esc_html($response->user_email); ?></small>
                                    </td>
                                    <td><?php echo esc_html($response->survey_title); ?></td>
                                    <td><?php echo date_i18n('Y-m-d H:i', strtotime($response->start_time)); ?></td>
                                    <td>
                                        <?php 
                                        if ($response->completion_time) {
                                            echo date_i18n('Y-m-d H:i', strtotime($response->completion_time));
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($response->duration_minutes) {
                                            printf(__('%d min', 'rm-panel-extensions'), $response->duration_minutes);
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="rm-status-badge rm-status-<?php echo esc_attr($response->status); ?>">
                                            <?php echo esc_html(ucfirst($response->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($response->completion_status) : ?>
                                            <span class="rm-completion-badge rm-completion-<?php echo esc_attr($response->completion_status); ?>">
                                                <?php echo esc_html(str_replace('_', ' ', ucfirst($response->completion_status))); ?>
                                            </span>
                                        <?php else : ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

// Initialize
RM_Survey_Reports::get_instance();