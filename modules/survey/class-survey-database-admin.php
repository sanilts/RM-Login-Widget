<?php
/**
 * Survey Database Admin Page
 * File: modules/survey/class-survey-database-admin.php
 * 
 * Provides admin interface for:
 * - Database status monitoring
 * - Schema verification
 * - Manual upgrades
 * - Table maintenance
 * - Statistics
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Survey_Database_Admin {
    
    private $db_manager;
    
    public function __construct() {
        // Get database manager instance
        if (class_exists('RM_Survey_Database_Manager')) {
            $this->db_manager = new RM_Survey_Database_Manager();
        }
        
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu'], 20);
        
        // Handle admin actions
        add_action('admin_post_rm_survey_db_action', [$this, 'handle_admin_actions']);
    }
    
    /**
     * Add database admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'rm-panel-extensions',
            __('Survey Database', 'rm-panel-extensions'),
            __('Survey Database', 'rm-panel-extensions'),
            'manage_options',
            'rm-survey-database',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Get database information
        $current_version = RM_Survey_Database_Manager::get_current_version();
        $needs_upgrade = RM_Survey_Database_Manager::needs_upgrade();
        $schema_report = $this->db_manager->verify_schema();
        $statistics = $this->db_manager->get_statistics();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Survey Database Management', 'rm-panel-extensions'); ?></h1>
            
            <!-- Status Overview -->
            <div class="rm-db-status-cards">
                <div class="rm-db-card">
                    <div class="rm-db-card-header">
                        <span class="dashicons dashicons-database"></span>
                        <h3><?php _e('Database Version', 'rm-panel-extensions'); ?></h3>
                    </div>
                    <div class="rm-db-card-content">
                        <p class="rm-db-version">
                            <strong><?php _e('Current:', 'rm-panel-extensions'); ?></strong> 
                            <?php echo esc_html($current_version); ?>
                        </p>
                        <p class="rm-db-version">
                            <strong><?php _e('Required:', 'rm-panel-extensions'); ?></strong> 
                            <?php echo esc_html(RM_Survey_Database_Manager::DB_VERSION); ?>
                        </p>
                        <?php if ($needs_upgrade) : ?>
                            <p class="rm-db-status-warning">
                                <span class="dashicons dashicons-warning"></span>
                                <?php _e('Database upgrade needed!', 'rm-panel-extensions'); ?>
                            </p>
                        <?php else : ?>
                            <p class="rm-db-status-success">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php _e('Database is up to date', 'rm-panel-extensions'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="rm-db-card">
                    <div class="rm-db-card-header">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <h3><?php _e('Table Status', 'rm-panel-extensions'); ?></h3>
                    </div>
                    <div class="rm-db-card-content">
                        <?php if ($schema_report['table_exists']) : ?>
                            <p class="rm-db-status-success">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php _e('Table exists', 'rm-panel-extensions'); ?>
                            </p>
                        <?php else : ?>
                            <p class="rm-db-status-error">
                                <span class="dashicons dashicons-dismiss"></span>
                                <?php _e('Table missing!', 'rm-panel-extensions'); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($schema_report['status'] === 'healthy') : ?>
                            <p class="rm-db-status-success">
                                <span class="dashicons dashicons-heart"></span>
                                <?php _e('Schema is healthy', 'rm-panel-extensions'); ?>
                            </p>
                        <?php elseif ($schema_report['status'] === 'incomplete') : ?>
                            <p class="rm-db-status-warning">
                                <span class="dashicons dashicons-warning"></span>
                                <?php printf(
                                    __('%d columns missing', 'rm-panel-extensions'),
                                    count($schema_report['missing_columns'])
                                ); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="rm-db-card">
                    <div class="rm-db-card-header">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <h3><?php _e('Statistics', 'rm-panel-extensions'); ?></h3>
                    </div>
                    <div class="rm-db-card-content">
                        <p>
                            <strong><?php _e('Total Responses:', 'rm-panel-extensions'); ?></strong> 
                            <?php echo number_format($statistics['total_responses']); ?>
                        </p>
                        <p>
                            <strong><?php _e('Table Size:', 'rm-panel-extensions'); ?></strong> 
                            <?php echo esc_html($statistics['table_size']); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Schema Details -->
            <?php if (!empty($schema_report['missing_columns'])) : ?>
                <div class="notice notice-warning">
                    <h3><?php _e('Missing Columns', 'rm-panel-extensions'); ?></h3>
                    <p><?php _e('The following columns are missing from your database:', 'rm-panel-extensions'); ?></p>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <?php foreach ($schema_report['missing_columns'] as $column) : ?>
                            <li><code><?php echo esc_html($column); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                    <p>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=rm_survey_db_action&db_action=upgrade'), 'rm_db_action'); ?>" 
                           class="button button-primary">
                            <?php _e('Run Database Upgrade', 'rm-panel-extensions'); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Detailed Statistics -->
            <div class="rm-db-section">
                <h2><?php _e('Response Statistics', 'rm-panel-extensions'); ?></h2>
                
                <div class="rm-db-stats-grid">
                    <!-- Status Breakdown -->
                    <div class="rm-db-stats-box">
                        <h3><?php _e('By Status', 'rm-panel-extensions'); ?></h3>
                        <?php if (!empty($statistics['by_status'])) : ?>
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Status', 'rm-panel-extensions'); ?></th>
                                        <th><?php _e('Count', 'rm-panel-extensions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($statistics['by_status'] as $status => $count) : ?>
                                        <tr>
                                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $status))); ?></td>
                                            <td><strong><?php echo number_format($count); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <p><?php _e('No data available', 'rm-panel-extensions'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Completion Status Breakdown -->
                    <div class="rm-db-stats-box">
                        <h3><?php _e('By Completion Status', 'rm-panel-extensions'); ?></h3>
                        <?php if (!empty($statistics['by_completion_status'])) : ?>
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Completion Status', 'rm-panel-extensions'); ?></th>
                                        <th><?php _e('Count', 'rm-panel-extensions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($statistics['by_completion_status'] as $status => $count) : ?>
                                        <tr>
                                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $status))); ?></td>
                                            <td><strong><?php echo number_format($count); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <p><?php _e('No data available', 'rm-panel-extensions'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Approval Status Breakdown -->
                    <div class="rm-db-stats-box">
                        <h3><?php _e('By Approval Status', 'rm-panel-extensions'); ?></h3>
                        <?php if (!empty($statistics['by_approval_status'])) : ?>
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Approval Status', 'rm-panel-extensions'); ?></th>
                                        <th><?php _e('Count', 'rm-panel-extensions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($statistics['by_approval_status'] as $status => $count) : ?>
                                        <tr>
                                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $status))); ?></td>
                                            <td><strong><?php echo number_format($count); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <p><?php _e('No data available', 'rm-panel-extensions'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Maintenance Actions -->
            <div class="rm-db-section">
                <h2><?php _e('Maintenance Actions', 'rm-panel-extensions'); ?></h2>
                
                <div class="rm-db-actions">
                    <div class="rm-db-action-box">
                        <h3><?php _e('Force Database Upgrade', 'rm-panel-extensions'); ?></h3>
                        <p><?php _e('Force a complete database upgrade. This will re-run all upgrade scripts.', 'rm-panel-extensions'); ?></p>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=rm_survey_db_action&db_action=force_upgrade'), 'rm_db_action'); ?>" 
                           class="button button-primary"
                           onclick="return confirm('<?php esc_attr_e('Are you sure? This will re-run all database upgrades.', 'rm-panel-extensions'); ?>');">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Force Upgrade', 'rm-panel-extensions'); ?>
                        </a>
                    </div>
                    
                    <div class="rm-db-action-box">
                        <h3><?php _e('Repair Table', 'rm-panel-extensions'); ?></h3>
                        <p><?php _e('Repair the database table if it becomes corrupted.', 'rm-panel-extensions'); ?></p>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=rm_survey_db_action&db_action=repair'), 'rm_db_action'); ?>" 
                           class="button"
                           onclick="return confirm('<?php esc_attr_e('Are you sure you want to repair the table?', 'rm-panel-extensions'); ?>');">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php _e('Repair Table', 'rm-panel-extensions'); ?>
                        </a>
                    </div>
                    
                    <div class="rm-db-action-box">
                        <h3><?php _e('Optimize Table', 'rm-panel-extensions'); ?></h3>
                        <p><?php _e('Optimize the database table to improve performance.', 'rm-panel-extensions'); ?></p>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=rm_survey_db_action&db_action=optimize'), 'rm_db_action'); ?>" 
                           class="button">
                            <span class="dashicons dashicons-performance"></span>
                            <?php _e('Optimize Table', 'rm-panel-extensions'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .rm-db-status-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            
            .rm-db-card {
                background: white;
                border: 1px solid #ccc;
                border-radius: 4px;
                padding: 0;
            }
            
            .rm-db-card-header {
                background: #f0f0f0;
                padding: 15px;
                border-bottom: 1px solid #ccc;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .rm-db-card-header .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
            }
            
            .rm-db-card-header h3 {
                margin: 0;
                font-size: 16px;
            }
            
            .rm-db-card-content {
                padding: 15px;
            }
            
            .rm-db-version {
                margin: 10px 0;
                font-size: 14px;
            }
            
            .rm-db-status-success {
                color: #46b450;
                display: flex;
                align-items: center;
                gap: 5px;
                margin: 10px 0;
            }
            
            .rm-db-status-warning {
                color: #f0ad4e;
                display: flex;
                align-items: center;
                gap: 5px;
                margin: 10px 0;
            }
            
            .rm-db-status-error {
                color: #dc3545;
                display: flex;
                align-items: center;
                gap: 5px;
                margin: 10px 0;
            }
            
            .rm-db-section {
                background: white;
                padding: 20px;
                margin: 20px 0;
                border: 1px solid #ccc;
                border-radius: 4px;
            }
            
            .rm-db-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            
            .rm-db-stats-box {
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 4px;
            }
            
            .rm-db-stats-box h3 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #ddd;
            }
            
            .rm-db-actions {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            
            .rm-db-action-box {
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 4px;
            }
            
            .rm-db-action-box h3 {
                margin-top: 0;
                font-size: 16px;
            }
            
            .rm-db-action-box p {
                margin: 10px 0;
                color: #666;
            }
            
            .rm-db-action-box .button {
                margin-top: 10px;
            }
            
            .rm-db-action-box .button .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                vertical-align: text-bottom;
            }
        </style>
        <?php
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.'));
        }
        
        check_admin_referer('rm_db_action');
        
        $action = isset($_GET['db_action']) ? sanitize_text_field($_GET['db_action']) : '';
        $redirect_url = admin_url('admin.php?page=rm-survey-database');
        
        switch ($action) {
            case 'upgrade':
            case 'force_upgrade':
                $result = $this->db_manager->force_upgrade();
                if ($result) {
                    $redirect_url = add_query_arg('message', 'upgrade_success', $redirect_url);
                } else {
                    $redirect_url = add_query_arg('message', 'upgrade_failed', $redirect_url);
                }
                break;
                
            case 'repair':
                $result = $this->db_manager->repair_table();
                if ($result) {
                    $redirect_url = add_query_arg('message', 'repair_success', $redirect_url);
                } else {
                    $redirect_url = add_query_arg('message', 'repair_failed', $redirect_url);
                }
                break;
                
            case 'optimize':
                $result = $this->db_manager->optimize_table();
                if ($result) {
                    $redirect_url = add_query_arg('message', 'optimize_success', $redirect_url);
                } else {
                    $redirect_url = add_query_arg('message', 'optimize_failed', $redirect_url);
                }
                break;
        }
        
        wp_redirect($redirect_url);
        exit;
    }
}

// Initialize
new RM_Survey_Database_Admin();