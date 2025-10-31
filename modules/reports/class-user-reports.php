<?php
/**
 * User Reports Dashboard
 * 
 * Comprehensive user activity, earnings, and payment tracking
 * 
 * @package RM_Panel_Extensions
 * @version 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_User_Reports {
    
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
        
        // Track user activity
        add_action('wp_login', [$this, 'track_user_login'], 10, 2);
        add_action('init', [$this, 'track_user_activity']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=rm_survey',
            __('User Reports', 'rm-panel-extensions'),
            __('👥 User Reports', 'rm-panel-extensions'),
            'manage_options',
            'rm-user-reports',
            [$this, 'render_user_reports_page']
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'rm_survey_page_rm-user-reports') {
            return;
        }
        
        wp_enqueue_style(
            'rm-user-reports',
            RM_PANEL_EXT_PLUGIN_URL . 'assets/css/user-reports.css',
            [],
            RM_PANEL_EXT_VERSION
        );
        
        wp_enqueue_script(
            'rm-user-reports',
            RM_PANEL_EXT_PLUGIN_URL . 'assets/js/user-reports.js',
            ['jquery', 'jquery-ui-datepicker'],
            RM_PANEL_EXT_VERSION,
            true
        );
        
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    }
    
    /**
     * Track user login
     */
    public function track_user_login($user_login, $user) {
        update_user_meta($user->ID, 'rm_last_login', current_time('mysql'));
        update_user_meta($user->ID, 'rm_login_count', intval(get_user_meta($user->ID, 'rm_login_count', true)) + 1);
    }
    
    /**
     * Track user activity
     */
    public function track_user_activity() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $last_activity = get_user_meta($user_id, 'rm_last_activity', true);
            
            // Update if more than 5 minutes since last update
            if (empty($last_activity) || strtotime($last_activity) < strtotime('-5 minutes')) {
                update_user_meta($user_id, 'rm_last_activity', current_time('mysql'));
            }
        }
    }
    
    /**
     * Get user comprehensive data
     */
    private function get_user_comprehensive_data($filters = []) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        
        // Base user query
        $user_args = [
            'orderby' => 'registered',
            'order' => 'DESC'
        ];
        
        // Role filter
        if (!empty($filters['role'])) {
            $user_args['role'] = $filters['role'];
        }
        
        // Date filter
        if (!empty($filters['date_from'])) {
            $user_args['date_query'] = [
                [
                    'after' => $filters['date_from'],
                    'inclusive' => true
                ]
            ];
        }
        
        $users = get_users($user_args);
        
        $user_data = [];
        
        foreach ($users as $user) {
            // Get survey statistics
            $survey_stats = $wpdb->get_row($wpdb->prepare("
                SELECT 
                    COUNT(*) as total_surveys,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_surveys,
                    SUM(CASE WHEN completion_status = 'success' THEN 1 ELSE 0 END) as successful_surveys,
                    MAX(completion_time) as last_survey_completed
                FROM {$table_name}
                WHERE user_id = %d
            ", $user->ID));
            
            // Get earnings
            $total_earned = floatval(get_user_meta($user->ID, 'rm_total_earnings', true));
            $paid_amount = floatval(get_user_meta($user->ID, 'rm_paid_amount', true));
            $pending_payment = $total_earned - $paid_amount;
            
            // Get activity data
            $last_login = get_user_meta($user->ID, 'rm_last_login', true);
            $last_activity = get_user_meta($user->ID, 'rm_last_activity', true);
            $login_count = intval(get_user_meta($user->ID, 'rm_login_count', true));
            
            // Get country from FluentCRM if available
            $country = '';
            if (class_exists('RM_Panel_FluentCRM_Helper')) {
                $country = RM_Panel_FluentCRM_Helper::get_contact_country($user->ID);
            }
            if (empty($country)) {
                $country = get_user_meta($user->ID, 'country', true);
            }
            
            // Apply search filter
            if (!empty($filters['search'])) {
                $search_term = strtolower($filters['search']);
                $searchable = strtolower($user->display_name . ' ' . $user->user_email . ' ' . $user->user_login);
                if (strpos($searchable, $search_term) === false) {
                    continue;
                }
            }
            
            $user_data[] = [
                'ID' => $user->ID,
                'username' => $user->user_login,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'registered' => $user->user_registered,
                'role' => implode(', ', $user->roles),
                'country' => $country,
                'last_login' => $last_login,
                'last_activity' => $last_activity,
                'login_count' => $login_count,
                'total_surveys' => intval($survey_stats->total_surveys),
                'completed_surveys' => intval($survey_stats->completed_surveys),
                'successful_surveys' => intval($survey_stats->successful_surveys),
                'last_survey_completed' => $survey_stats->last_survey_completed,
                'total_earned' => $total_earned,
                'paid_amount' => $paid_amount,
                'pending_payment' => $pending_payment
            ];
        }
        
        return $user_data;
    }
    
    /**
     * Handle Excel export
     */
    public function handle_excel_export() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'rm_export_user_reports') {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('rm_export_user_reports');
        
        // Get filters
        $filters = [
            'role' => isset($_GET['filter_role']) ? sanitize_text_field($_GET['filter_role']) : '',
            'date_from' => isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '',
            'search' => isset($_GET['filter_search']) ? sanitize_text_field($_GET['filter_search']) : ''
        ];
        
        $users = $this->get_user_comprehensive_data($filters);
        
        // Generate CSV
        $filename = 'user-reports-' . date('Y-m-d-His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'User ID',
            'Username',
            'Display Name',
            'Email',
            'Role',
            'Country',
            'Registered',
            'Last Login',
            'Last Activity',
            'Login Count',
            'Total Surveys',
            'Completed Surveys',
            'Successful Surveys',
            'Last Survey Completed',
            'Total Earned',
            'Paid Amount',
            'Pending Payment'
        ]);
        
        // Currency symbol (customize as needed)
        $currency = get_option('rm_panel_currency_symbol', '$');
        
        // Data rows
        foreach ($users as $user) {
            fputcsv($output, [
                $user['ID'],
                $user['username'],
                $user['display_name'],
                $user['email'],
                $user['role'],
                $user['country'],
                $user['registered'],
                $user['last_login'] ?: 'Never',
                $user['last_activity'] ?: 'Never',
                $user['login_count'],
                $user['total_surveys'],
                $user['completed_surveys'],
                $user['successful_surveys'],
                $user['last_survey_completed'] ?: 'Never',
                $currency . number_format($user['total_earned'], 2),
                $currency . number_format($user['paid_amount'], 2),
                $currency . number_format($user['pending_payment'], 2)
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Render user reports page
     */
    public function render_user_reports_page() {
        // Get filters
        $filters = [
            'role' => isset($_GET['filter_role']) ? sanitize_text_field($_GET['filter_role']) : '',
            'date_from' => isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '',
            'search' => isset($_GET['filter_search']) ? sanitize_text_field($_GET['filter_search']) : ''
        ];
        
        $users = $this->get_user_comprehensive_data($filters);
        
        // Calculate totals
        $total_users = count($users);
        $total_earned = array_sum(array_column($users, 'total_earned'));
        $total_paid = array_sum(array_column($users, 'paid_amount'));
        $total_pending = array_sum(array_column($users, 'pending_payment'));
        
        // Get all roles for filter
        global $wp_roles;
        $all_roles = $wp_roles->roles;
        
        // Currency symbol
        $currency = get_option('rm_panel_currency_symbol', '$');
        
        ?>
        <div class="wrap rm-user-reports-page">
            <h1><?php _e('User Reports & Activity', 'rm-panel-extensions'); ?></h1>
            
            <!-- Summary Stats -->
            <div class="rm-summary-stats">
                <div class="rm-summary-card">
                    <div class="rm-summary-icon">👥</div>
                    <div class="rm-summary-content">
                        <div class="rm-summary-value"><?php echo number_format($total_users); ?></div>
                        <div class="rm-summary-label"><?php _e('Total Users', 'rm-panel-extensions'); ?></div>
                    </div>
                </div>
                
                <div class="rm-summary-card">
                    <div class="rm-summary-icon">💰</div>
                    <div class="rm-summary-content">
                        <div class="rm-summary-value"><?php echo $currency . number_format($total_earned, 2); ?></div>
                        <div class="rm-summary-label"><?php _e('Total Earned', 'rm-panel-extensions'); ?></div>
                    </div>
                </div>
                
                <div class="rm-summary-card rm-summary-success">
                    <div class="rm-summary-icon">✅</div>
                    <div class="rm-summary-content">
                        <div class="rm-summary-value"><?php echo $currency . number_format($total_paid, 2); ?></div>
                        <div class="rm-summary-label"><?php _e('Total Paid', 'rm-panel-extensions'); ?></div>
                    </div>
                </div>
                
                <div class="rm-summary-card rm-summary-warning">
                    <div class="rm-summary-icon">⏳</div>
                    <div class="rm-summary-content">
                        <div class="rm-summary-value"><?php echo $currency . number_format($total_pending, 2); ?></div>
                        <div class="rm-summary-label"><?php _e('Pending Payment', 'rm-panel-extensions'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="rm-user-reports-filters">
                <form method="get" action="">
                    <input type="hidden" name="post_type" value="rm_survey">
                    <input type="hidden" name="page" value="rm-user-reports">
                    
                    <div class="rm-filter-row">
                        <!-- Search -->
                        <div class="rm-filter-field">
                            <label><?php _e('Search:', 'rm-panel-extensions'); ?></label>
                            <input type="text" name="filter_search" 
                                   value="<?php echo esc_attr($filters['search']); ?>" 
                                   placeholder="<?php _e('Name, email, username...', 'rm-panel-extensions'); ?>">
                        </div>
                        
                        <!-- Role Filter -->
                        <div class="rm-filter-field">
                            <label><?php _e('Role:', 'rm-panel-extensions'); ?></label>
                            <select name="filter_role">
                                <option value=""><?php _e('All Roles', 'rm-panel-extensions'); ?></option>
                                <?php foreach ($all_roles as $role_key => $role_data) : ?>
                                    <option value="<?php echo esc_attr($role_key); ?>" 
                                            <?php selected($filters['role'], $role_key); ?>>
                                        <?php echo esc_html($role_data['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Registered After -->
                        <div class="rm-filter-field">
                            <label><?php _e('Registered After:', 'rm-panel-extensions'); ?></label>
                            <input type="text" name="filter_date_from" class="rm-datepicker" 
                                   value="<?php echo esc_attr($filters['date_from']); ?>" 
                                   placeholder="YYYY-MM-DD">
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="rm-filter-field rm-filter-actions">
                            <button type="submit" class="button button-primary">
                                <?php _e('Apply Filters', 'rm-panel-extensions'); ?>
                            </button>
                            <a href="<?php echo admin_url('edit.php?post_type=rm_survey&page=rm-user-reports'); ?>" 
                               class="button">
                                <?php _e('Clear', 'rm-panel-extensions'); ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Export Button -->
            <div class="rm-export-section">
                <a href="<?php echo wp_nonce_url(add_query_arg(array_merge([
                    'action' => 'rm_export_user_reports',
                    'post_type' => 'rm_survey',
                    'page' => 'rm-user-reports'
                ], $filters)), 'rm_export_user_reports'); ?>" class="button button-primary">
                    📥 <?php _e('Export to Excel', 'rm-panel-extensions'); ?>
                </a>
                <span class="rm-export-count">
                    <?php printf(__('(%d users)', 'rm-panel-extensions'), $total_users); ?>
                </span>
            </div>
            
            <!-- Users Table -->
            <div class="rm-users-table-wrapper">
                <?php if (empty($users)) : ?>
                    <p><?php _e('No users found matching your filters.', 'rm-panel-extensions'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped rm-user-reports-table">
                        <thead>
                            <tr>
                                <th><?php _e('User', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Country', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Registered', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Last Login', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Last Active', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Surveys', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Last Survey', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Earned', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Paid', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Pending', 'rm-panel-extensions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user) : ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($user['display_name']); ?></strong><br>
                                        <small><?php echo esc_html($user['email']); ?></small><br>
                                        <span class="rm-user-role"><?php echo esc_html($user['role']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($user['country']) : ?>
                                            🌍 <?php echo esc_html($user['country']); ?>
                                        <?php else : ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date_i18n('Y-m-d', strtotime($user['registered'])); ?></td>
                                    <td>
                                        <?php 
                                        if ($user['last_login']) {
                                            echo date_i18n('Y-m-d H:i', strtotime($user['last_login']));
                                            echo '<br><small>' . human_time_diff(strtotime($user['last_login'])) . ' ago</small>';
                                        } else {
                                            echo '<span class="rm-never">Never</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($user['last_activity']) {
                                            $minutes_ago = round((time() - strtotime($user['last_activity'])) / 60);
                                            if ($minutes_ago < 5) {
                                                echo '<span class="rm-active-now">🟢 Active Now</span>';
                                            } else {
                                                echo human_time_diff(strtotime($user['last_activity'])) . ' ago';
                                            }
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <strong><?php echo $user['completed_surveys']; ?></strong> / <?php echo $user['total_surveys']; ?>
                                        <?php if ($user['successful_surveys'] > 0) : ?>
                                            <br><small class="rm-success-count">✅ <?php echo $user['successful_surveys']; ?> successful</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($user['last_survey_completed']) {
                                            echo human_time_diff(strtotime($user['last_survey_completed'])) . ' ago';
                                        } else {
                                            echo '<span class="rm-never">Never</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="rm-amount"><?php echo $currency . number_format($user['total_earned'], 2); ?></td>
                                    <td class="rm-amount rm-amount-paid"><?php echo $currency . number_format($user['paid_amount'], 2); ?></td>
                                    <td class="rm-amount rm-amount-pending">
                                        <?php if ($user['pending_payment'] > 0) : ?>
                                            <strong><?php echo $currency . number_format($user['pending_payment'], 2); ?></strong>
                                        <?php else : ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="7"><strong><?php _e('TOTALS:', 'rm-panel-extensions'); ?></strong></th>
                                <th class="rm-amount"><strong><?php echo $currency . number_format($total_earned, 2); ?></strong></th>
                                <th class="rm-amount"><strong><?php echo $currency . number_format($total_paid, 2); ?></strong></th>
                                <th class="rm-amount"><strong><?php echo $currency . number_format($total_pending, 2); ?></strong></th>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

// Initialize
RM_User_Reports::get_instance();