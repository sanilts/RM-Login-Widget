<?php
/**
 * Withdrawal Requests Management - FIXED VERSION
 * Users can request withdrawals, admins can process them
 * 
 * @package RM_Panel_Extensions
 * @version 2.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Withdrawal_Requests {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->create_tables();
    }
    
    private function init_hooks() {
        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // Frontend
        add_shortcode('rm_withdrawal_form', [$this, 'render_withdrawal_form']);
        add_shortcode('rm_withdrawal_history', [$this, 'render_withdrawal_history']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        
        // AJAX handlers - User side
        add_action('wp_ajax_rm_submit_withdrawal', [$this, 'ajax_submit_withdrawal']);
        add_action('wp_ajax_rm_cancel_withdrawal', [$this, 'ajax_cancel_withdrawal']);
        
        // AJAX handlers - Admin side
        add_action('wp_ajax_rm_get_withdrawal_details', [$this, 'ajax_get_withdrawal_details']);
        add_action('wp_ajax_rm_approve_withdrawal', [$this, 'ajax_approve_withdrawal']);
        add_action('wp_ajax_rm_reject_withdrawal', [$this, 'ajax_reject_withdrawal']);
        add_action('wp_ajax_rm_complete_withdrawal', [$this, 'ajax_complete_withdrawal']);
        
        // Admin notices
        add_action('admin_notices', [$this, 'pending_withdrawals_notice']);
    }
    
    private function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_withdrawal_requests';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            payment_method_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            processing_fee decimal(10,2) DEFAULT 0.00,
            net_amount decimal(10,2) NOT NULL,
            payment_details longtext DEFAULT NULL,
            status varchar(50) DEFAULT 'pending',
            admin_notes text DEFAULT NULL,
            processed_by bigint(20) DEFAULT NULL,
            processed_at datetime DEFAULT NULL,
            transaction_reference varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY payment_method_id (payment_method_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function add_admin_menu() {
        $pending_count = $this->get_pending_count();
        
        add_submenu_page(
            'edit.php?post_type=rm_survey',
            __('Withdrawal Requests', 'rm-panel-extensions'),
            sprintf(__('Withdrawal Requests %s', 'rm-panel-extensions'), 
                $pending_count > 0 ? '<span class="awaiting-mod count-' . $pending_count . '"><span class="pending-count">' . $pending_count . '</span></span>' : ''),
            'manage_options',
            'rm-withdrawal-requests',
            [$this, 'render_admin_page']
        );
    }
    
    public function pending_withdrawals_notice() {
        $pending_count = $this->get_pending_count();
        
        if ($pending_count > 0 && current_user_can('manage_options')) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong><?php _e('Withdrawal Requests:', 'rm-panel-extensions'); ?></strong>
                    <?php 
                    printf(
                        __('You have %d pending withdrawal request(s). <a href="%s">Process now</a>', 'rm-panel-extensions'),
                        $pending_count,
                        admin_url('edit.php?post_type=rm_survey&page=rm-withdrawal-requests')
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }
    
    public function render_admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_withdrawal_requests';
        
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'pending';
        
        // Get withdrawal requests
        $query = "SELECT 
                    w.*,
                    u.display_name,
                    u.user_email,
                    pm.method_name,
                    pm.method_type,
                    processor.display_name as processed_by_name
                  FROM $table_name w
                  LEFT JOIN {$wpdb->users} u ON w.user_id = u.ID
                  LEFT JOIN {$wpdb->prefix}rm_payment_methods pm ON w.payment_method_id = pm.id
                  LEFT JOIN {$wpdb->users} processor ON w.processed_by = processor.ID
                  WHERE w.status = %s
                  ORDER BY w.created_at DESC";
        
        $requests = $wpdb->get_results($wpdb->prepare($query, $status_filter));
        
        // Get counts
        $pending_count = $this->get_count_by_status('pending');
        $approved_count = $this->get_count_by_status('approved');
        $processing_count = $this->get_count_by_status('processing');
        $completed_count = $this->get_count_by_status('completed');
        $rejected_count = $this->get_count_by_status('rejected');
        
        // Get statistics
        $stats = $this->get_withdrawal_statistics();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Withdrawal Requests Management', 'rm-panel-extensions'); ?></h1>
            
            <!-- Statistics Cards -->
            <div class="rm-stats-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                <div class="rm-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="font-size: 14px; color: #666; margin-bottom: 10px;"><?php _e('Total Pending', 'rm-panel-extensions'); ?></div>
                    <div style="font-size: 32px; font-weight: 600; color: #f0ad4e;">$<?php echo number_format($stats->pending_amount, 2); ?></div>
                    <div style="font-size: 12px; color: #999; margin-top: 5px;"><?php echo $pending_count; ?> requests</div>
                </div>
                
                <div class="rm-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="font-size: 14px; color: #666; margin-bottom: 10px;"><?php _e('Total Paid', 'rm-panel-extensions'); ?></div>
                    <div style="font-size: 32px; font-weight: 600; color: #46b450;">$<?php echo number_format($stats->completed_amount, 2); ?></div>
                    <div style="font-size: 12px; color: #999; margin-top: 5px;"><?php echo $completed_count; ?> completed</div>
                </div>
                
                <div class="rm-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="font-size: 14px; color: #666; margin-bottom: 10px;"><?php _e('This Month', 'rm-panel-extensions'); ?></div>
                    <div style="font-size: 32px; font-weight: 600; color: #2271b1;">$<?php echo number_format($stats->month_amount, 2); ?></div>
                    <div style="font-size: 12px; color: #999; margin-top: 5px;"><?php echo $stats->month_count; ?> requests</div>
                </div>
                
                <div class="rm-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="font-size: 14px; color: #666; margin-bottom: 10px;"><?php _e('Processing Fees', 'rm-panel-extensions'); ?></div>
                    <div style="font-size: 32px; font-weight: 600; color: #666;">$<?php echo number_format($stats->total_fees, 2); ?></div>
                    <div style="font-size: 12px; color: #999; margin-top: 5px;"><?php _e('Total collected', 'rm-panel-extensions'); ?></div>
                </div>
            </div>
            
            <!-- Status Filters -->
            <ul class="subsubsub">
                <li>
                    <a href="?post_type=rm_survey&page=rm-withdrawal-requests&status_filter=pending" 
                       class="<?php echo $status_filter === 'pending' ? 'current' : ''; ?>">
                        <?php printf(__('⏳ Pending (%d)', 'rm-panel-extensions'), $pending_count); ?>
                    </a> |
                </li>
                <li>
                    <a href="?post_type=rm_survey&page=rm-withdrawal-requests&status_filter=approved" 
                       class="<?php echo $status_filter === 'approved' ? 'current' : ''; ?>">
                        <?php printf(__('✅ Approved (%d)', 'rm-panel-extensions'), $approved_count); ?>
                    </a> |
                </li>
                <li>
                    <a href="?post_type=rm_survey&page=rm-withdrawal-requests&status_filter=processing" 
                       class="<?php echo $status_filter === 'processing' ? 'current' : ''; ?>">
                        <?php printf(__('🔄 Processing (%d)', 'rm-panel-extensions'), $processing_count); ?>
                    </a> |
                </li>
                <li>
                    <a href="?post_type=rm_survey&page=rm-withdrawal-requests&status_filter=completed" 
                       class="<?php echo $status_filter === 'completed' ? 'current' : ''; ?>">
                        <?php printf(__('💚 Completed (%d)', 'rm-panel-extensions'), $completed_count); ?>
                    </a> |
                </li>
                <li>
                    <a href="?post_type=rm_survey&page=rm-withdrawal-requests&status_filter=rejected" 
                       class="<?php echo $status_filter === 'rejected' ? 'current' : ''; ?>">
                        <?php printf(__('❌ Rejected (%d)', 'rm-panel-extensions'), $rejected_count); ?>
                    </a>
                </li>
            </ul>
            
            <div class="clear"></div>
            
            <!-- Export Button -->
            <?php if (!empty($requests)) : ?>
                <div style="margin: 20px 0;">
                    <button type="button" class="button" onclick="window.print()">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Print/Export', 'rm-panel-extensions'); ?>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Requests Table -->
            <?php if (empty($requests)) : ?>
                <div class="notice notice-info inline">
                    <p><?php _e('No withdrawal requests found with this status.', 'rm-panel-extensions'); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php _e('ID', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('User', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Method', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Amount', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Fee', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Net Amount', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Requested', 'rm-panel-extensions'); ?></th>
                            <?php if ($status_filter !== 'pending') : ?>
                                <th><?php _e('Processed By', 'rm-panel-extensions'); ?></th>
                            <?php endif; ?>
                            <th><?php _e('Actions', 'rm-panel-extensions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request) : ?>
                            <tr data-request-id="<?php echo $request->id; ?>">
                                <td><strong>#<?php echo $request->id; ?></strong></td>
                                <td>
                                    <strong><?php echo esc_html($request->display_name); ?></strong><br>
                                    <small><?php echo esc_html($request->user_email); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($request->method_name); ?></strong><br>
                                    <button type="button" class="button button-small view-details-btn" 
                                            data-request-id="<?php echo $request->id; ?>"
                                            data-payment-details='<?php echo esc_attr($request->payment_details); ?>'>
                                        <?php _e('View Details', 'rm-panel-extensions'); ?>
                                    </button>
                                </td>
                                <td>
                                    <strong style="font-size: 16px;">$<?php echo number_format($request->amount, 2); ?></strong>
                                </td>
                                <td>
                                    <?php if ($request->processing_fee > 0) : ?>
                                        <span style="color: #dc3545;">-$<?php echo number_format($request->processing_fee, 2); ?></span>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="color: #2ea44f; font-size: 16px;">
                                        $<?php echo number_format($request->net_amount, 2); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php echo date_i18n('M j, Y', strtotime($request->created_at)); ?><br>
                                    <small><?php echo date_i18n('H:i', strtotime($request->created_at)); ?></small>
                                </td>
                                <?php if ($status_filter !== 'pending') : ?>
                                    <td>
                                        <?php if ($request->processed_by_name) : ?>
                                            <?php echo esc_html($request->processed_by_name); ?><br>
                                            <small><?php echo date_i18n('M j, Y', strtotime($request->processed_at)); ?></small>
                                        <?php else : ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <?php if ($status_filter === 'pending') : ?>
                                        <button type="button" class="button button-primary button-small approve-withdrawal-btn" 
                                                data-request-id="<?php echo $request->id; ?>">
                                            <span class="dashicons dashicons-yes"></span>
                                            <?php _e('Approve', 'rm-panel-extensions'); ?>
                                        </button>
                                        <button type="button" class="button button-secondary button-small reject-withdrawal-btn" 
                                                data-request-id="<?php echo $request->id; ?>">
                                            <span class="dashicons dashicons-no"></span>
                                            <?php _e('Reject', 'rm-panel-extensions'); ?>
                                        </button>
                                    <?php elseif ($status_filter === 'approved' || $status_filter === 'processing') : ?>
                                        <button type="button" class="button button-primary button-small complete-withdrawal-btn" 
                                                data-request-id="<?php echo $request->id; ?>">
                                            <span class="dashicons dashicons-yes-alt"></span>
                                            <?php _e('Mark as Paid', 'rm-panel-extensions'); ?>
                                        </button>
                                    <?php else : ?>
                                        <span class="withdrawal-status-badge status-<?php echo esc_attr($request->status); ?>">
                                            <?php echo ucfirst($request->status); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Payment Details Modal -->
        <div id="payment-details-modal" class="rm-modal" style="display:none;">
            <div class="rm-modal-content">
                <span class="close-modal">&times;</span>
                <h2><?php _e('Payment Details', 'rm-panel-extensions'); ?></h2>
                <div id="payment-details-content"></div>
            </div>
        </div>
        
        <!-- Approve Withdrawal Modal -->
        <div id="approve-withdrawal-modal" class="rm-modal" style="display:none;">
            <div class="rm-modal-content">
                <span class="close-modal">&times;</span>
                <h2><?php _e('Approve Withdrawal Request', 'rm-panel-extensions'); ?></h2>
                <form id="approve-withdrawal-form">
                    <input type="hidden" id="approve-request-id" value="">
                    
                    <div class="approval-info" style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
                        <p><?php _e('This will move the request to "Approved" status. You can then process the payment and mark it as completed.', 'rm-panel-extensions'); ?></p>
                    </div>
                    
                    <p>
                        <label for="approve-notes"><?php _e('Admin Notes (optional):', 'rm-panel-extensions'); ?></label>
                        <textarea id="approve-notes" rows="3" style="width: 100%;"></textarea>
                    </p>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-yes"></span>
                            <?php _e('Approve Withdrawal', 'rm-panel-extensions'); ?>
                        </button>
                        <button type="button" class="button cancel-modal">
                            <?php _e('Cancel', 'rm-panel-extensions'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Reject Withdrawal Modal -->
        <div id="reject-withdrawal-modal" class="rm-modal" style="display:none;">
            <div class="rm-modal-content">
                <span class="close-modal">&times;</span>
                <h2><?php _e('Reject Withdrawal Request', 'rm-panel-extensions'); ?></h2>
                <form id="reject-withdrawal-form">
                    <input type="hidden" id="reject-request-id" value="">
                    
                    <div class="rejection-warning" style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #f0ad4e;">
                        <p><strong><?php _e('Warning:', 'rm-panel-extensions'); ?></strong> <?php _e('Rejecting will return the amount to user\'s balance and notify them.', 'rm-panel-extensions'); ?></p>
                    </div>
                    
                    <p>
                        <label for="reject-reason"><?php _e('Reason for Rejection (required):', 'rm-panel-extensions'); ?></label>
                        <textarea id="reject-reason" rows="4" style="width: 100%;" required></textarea>
                    </p>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-no"></span>
                            <?php _e('Reject Withdrawal', 'rm-panel-extensions'); ?>
                        </button>
                        <button type="button" class="button cancel-modal">
                            <?php _e('Cancel', 'rm-panel-extensions'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Complete Withdrawal Modal -->
        <div id="complete-withdrawal-modal" class="rm-modal" style="display:none;">
            <div class="rm-modal-content">
                <span class="close-modal">&times;</span>
                <h2><?php _e('Mark Withdrawal as Completed', 'rm-panel-extensions'); ?></h2>
                <form id="complete-withdrawal-form">
                    <input type="hidden" id="complete-request-id" value="">
                    
                    <div class="completion-info" style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;">
                        <p><?php _e('Confirm that you have successfully sent the payment to the user.', 'rm-panel-extensions'); ?></p>
                    </div>
                    
                    <p>
                        <label for="transaction-reference"><?php _e('Transaction Reference/ID:', 'rm-panel-extensions'); ?></label>
                        <input type="text" id="transaction-reference" class="regular-text" 
                               placeholder="<?php _e('e.g., PayPal Transaction ID, Bank Reference', 'rm-panel-extensions'); ?>">
                    </p>
                    
                    <p>
                        <label for="completion-notes"><?php _e('Completion Notes (optional):', 'rm-panel-extensions'); ?></label>
                        <textarea id="completion-notes" rows="3" style="width: 100%;"></textarea>
                    </p>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Mark as Paid', 'rm-panel-extensions'); ?>
                        </button>
                        <button type="button" class="button cancel-modal">
                            <?php _e('Cancel', 'rm-panel-extensions'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <style>
            .rm-modal {
                position: fixed;
                z-index: 100000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.7);
                overflow-y: auto;
            }
            .rm-modal-content {
                background-color: #fefefe;
                margin: 50px auto;
                padding: 30px;
                border: 1px solid #888;
                width: 600px;
                max-width: 90%;
                border-radius: 8px;
            }
            .close-modal {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
                line-height: 20px;
            }
            .close-modal:hover {
                color: #000;
            }
            .withdrawal-status-badge {
                display: inline-block;
                padding: 5px 12px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
            }
            .status-completed {
                background: #d4edda;
                color: #155724;
            }
            .status-rejected {
                background: #f8d7da;
                color: #721c24;
            }
            .status-processing {
                background: #d1ecf1;
                color: #0c5460;
            }
            .payment-detail-row {
                padding: 10px;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
            }
            .payment-detail-label {
                font-weight: 600;
                color: #666;
            }
            .payment-detail-value {
                color: #333;
            }
        </style>
        <?php
    }
    
    private function get_count_by_status($status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_withdrawal_requests';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE status = %s",
            $status
        ));
    }
    
    private function get_pending_count() {
        return $this->get_count_by_status('pending');
    }
    
    private function get_withdrawal_statistics() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_withdrawal_requests';
        
        return $wpdb->get_row("
            SELECT 
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN net_amount ELSE 0 END), 0) as completed_amount,
                COALESCE(SUM(CASE WHEN status = 'completed' AND MONTH(processed_at) = MONTH(NOW()) THEN net_amount ELSE 0 END), 0) as month_amount,
                COALESCE(COUNT(CASE WHEN MONTH(created_at) = MONTH(NOW()) THEN 1 END), 0) as month_count,
                COALESCE(SUM(processing_fee), 0) as total_fees
            FROM $table_name
        ");
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'rm_survey_page_rm-withdrawal-requests') {
            return;
        }
        
        wp_enqueue_script(
            'rm-withdrawal-admin',
            RM_PANEL_EXT_PLUGIN_URL . 'assets/js/withdrawal-admin.js',
            ['jquery'],
            RM_PANEL_EXT_VERSION,
            true
        );
        
        wp_localize_script('rm-withdrawal-admin', 'rmWithdrawal', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rm_withdrawal_nonce')
        ]);
    }
    
    public function enqueue_frontend_scripts() {
        if (!is_user_logged_in()) {
            return;
        }
        
        wp_enqueue_style(
            'rm-withdrawal-frontend',
            RM_PANEL_EXT_PLUGIN_URL . 'assets/css/withdrawal-frontend.css',
            [],
            RM_PANEL_EXT_VERSION
        );
        
        wp_enqueue_script(
            'rm-withdrawal-frontend',
            RM_PANEL_EXT_PLUGIN_URL . 'assets/js/withdrawal-frontend.js',
            ['jquery'],
            RM_PANEL_EXT_VERSION,
            true
        );
        
        wp_localize_script('rm-withdrawal-frontend', 'rmWithdrawal', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rm_withdrawal_nonce')
        ]);
    }
    
    /**
     * Render withdrawal form shortcode
     */
    public function render_withdrawal_form() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to request a withdrawal.', 'rm-panel-extensions') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $balance = get_user_meta($user_id, 'rm_withdrawable_balance', true) ?: 0;
        
        // Get active payment methods
        $payment_methods = RM_Payment_Methods::get_instance()->get_active_methods();
        
        if (empty($payment_methods)) {
            return '<div class="rm-notice rm-notice-warning">' . __('No payment methods are currently available. Please contact support.', 'rm-panel-extensions') . '</div>';
        }
        
        ob_start();
        ?>
        <div class="rm-withdrawal-form-container">
            <div class="rm-balance-card">
                <div class="balance-label"><?php _e('Available Balance', 'rm-panel-extensions'); ?></div>
                <div class="balance-amount">$<?php echo number_format($balance, 2); ?></div>
            </div>
            
            <?php if ($balance <= 0) : ?>
                <div class="rm-notice rm-notice-info">
                    <p><?php _e('You don\'t have any available balance to withdraw. Complete surveys to earn money!', 'rm-panel-extensions'); ?></p>
                </div>
            <?php else : ?>
                <form id="rm-withdrawal-form" class="rm-withdrawal-form">
                    <div class="form-step active" data-step="1">
                        <h3><?php _e('Step 1: Choose Payment Method', 'rm-panel-extensions'); ?></h3>
                        
                        <div class="payment-methods-grid">
                            <?php foreach ($payment_methods as $method) : ?>
                                <label class="payment-method-option">
                                    <input type="radio" name="payment_method" value="<?php echo $method->id; ?>" 
                                           data-min="<?php echo $method->min_withdrawal; ?>"
                                           data-max="<?php echo $method->max_withdrawal ?: 999999; ?>"
                                           data-fee-type="<?php echo $method->processing_fee_type; ?>"
                                           data-fee-value="<?php echo $method->processing_fee_value; ?>"
                                           data-fields='<?php echo esc_attr($method->required_fields); ?>'
                                           required>
                                    <div class="method-card">
                                        <span class="dashicons <?php echo esc_attr($method->icon); ?>"></span>
                                        <div class="method-name"><?php echo esc_html($method->method_name); ?></div>
                                        <div class="method-description"><?php echo esc_html($method->description); ?></div>
                                        <div class="method-limits">
                                            <small>
                                                <?php _e('Min:', 'rm-panel-extensions'); ?> $<?php echo number_format($method->min_withdrawal, 2); ?>
                                                <?php if ($method->max_withdrawal) : ?>
                                                    | <?php _e('Max:', 'rm-panel-extensions'); ?> $<?php echo number_format($method->max_withdrawal, 2); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <?php if ($method->processing_fee_type !== 'none') : ?>
                                            <div class="method-fee">
                                                <small>
                                                    <?php _e('Fee:', 'rm-panel-extensions'); ?>
                                                    <?php if ($method->processing_fee_type === 'percentage') : ?>
                                                        <?php echo $method->processing_fee_value; ?>%
                                                    <?php else : ?>
                                                        $<?php echo number_format($method->processing_fee_value, 2); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" class="rm-btn rm-btn-primary next-step">
                            <?php _e('Continue', 'rm-panel-extensions'); ?> →
                        </button>
                    </div>
                    
                    <div class="form-step" data-step="2">
                        <h3><?php _e('Step 2: Enter Amount', 'rm-panel-extensions'); ?></h3>
                        
                        <div class="form-group">
                            <label for="withdrawal-amount"><?php _e('Withdrawal Amount', 'rm-panel-extensions'); ?></label>
                            <div class="amount-input-wrapper">
                                <span class="currency-symbol">$</span>
                                <input type="number" id="withdrawal-amount" step="0.01" min="0" 
                                       max="<?php echo $balance; ?>" required>
                            </div>
                            <small class="amount-limits"></small>
                        </div>
                        
                        <div class="withdrawal-summary">
                            <div class="summary-row">
                                <span><?php _e('Requested Amount:', 'rm-panel-extensions'); ?></span>
                                <span class="summary-amount">$0.00</span>
                            </div>
                            <div class="summary-row">
                                <span><?php _e('Processing Fee:', 'rm-panel-extensions'); ?></span>
                                <span class="summary-fee">$0.00</span>
                            </div>
                            <div class="summary-row summary-total">
                                <span><?php _e('You will receive:', 'rm-panel-extensions'); ?></span>
                                <span class="summary-net">$0.00</span>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="rm-btn rm-btn-secondary prev-step">
                                ← <?php _e('Back', 'rm-panel-extensions'); ?>
                            </button>
                            <button type="button" class="rm-btn rm-btn-primary next-step">
                                <?php _e('Continue', 'rm-panel-extensions'); ?> →
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-step" data-step="3">
                        <h3><?php _e('Step 3: Payment Details', 'rm-panel-extensions'); ?></h3>
                        
                        <div id="payment-details-fields"></div>
                        
                        <div class="form-actions">
                            <button type="button" class="rm-btn rm-btn-secondary prev-step">
                                ← <?php _e('Back', 'rm-panel-extensions'); ?>
                            </button>
                            <button type="submit" class="rm-btn rm-btn-primary">
                                <?php _e('Submit Withdrawal Request', 'rm-panel-extensions'); ?>
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render withdrawal history shortcode
     */
    public function render_withdrawal_history() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your withdrawal history.', 'rm-panel-extensions') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $withdrawals = $this->get_user_withdrawals($user_id);
        
        ob_start();
        ?>
        <div class="rm-withdrawal-history">
            <h3><?php _e('Withdrawal History', 'rm-panel-extensions'); ?></h3>
            
            <?php if (empty($withdrawals)) : ?>
                <p><?php _e('You haven\'t made any withdrawal requests yet.', 'rm-panel-extensions'); ?></p>
            <?php else : ?>
                <table class="rm-withdrawal-table">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Method', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Amount', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Fee', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Net', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Status', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Actions', 'rm-panel-extensions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawals as $withdrawal) : ?>
                            <tr>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($withdrawal->created_at)); ?></td>
                                <td><?php echo esc_html($withdrawal->method_name); ?></td>
                                <td>$<?php echo number_format($withdrawal->amount, 2); ?></td>
                                <td>$<?php echo number_format($withdrawal->processing_fee, 2); ?></td>
                                <td><strong>$<?php echo number_format($withdrawal->net_amount, 2); ?></strong></td>
                                <td>
                                    <span class="withdrawal-status status-<?php echo esc_attr($withdrawal->status); ?>">
                                        <?php echo $this->get_status_label($withdrawal->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($withdrawal->status === 'pending') : ?>
                                        <button type="button" class="rm-btn rm-btn-small cancel-withdrawal-btn" 
                                                data-request-id="<?php echo $withdrawal->id; ?>">
                                            <?php _e('Cancel', 'rm-panel-extensions'); ?>
                                        </button>
                                    <?php elseif ($withdrawal->status === 'completed' && $withdrawal->transaction_reference) : ?>
                                        <small>
                                            <?php _e('Ref:', 'rm-panel-extensions'); ?> 
                                            <?php echo esc_html($withdrawal->transaction_reference); ?>
                                        </small>
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
        <?php
        return ob_get_clean();
    }
    
    private function get_user_withdrawals($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_withdrawal_requests';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT w.*, pm.method_name
            FROM $table_name w
            LEFT JOIN {$wpdb->prefix}rm_payment_methods pm ON w.payment_method_id = pm.id
            WHERE w.user_id = %d
            ORDER BY w.created_at DESC
        ", $user_id));
    }
    
    private function get_status_label($status) {
        $labels = [
            'pending' => __('⏳ Pending', 'rm-panel-extensions'),
            'approved' => __('✅ Approved', 'rm-panel-extensions'),
            'processing' => __('🔄 Processing', 'rm-panel-extensions'),
            'completed' => __('💚 Completed', 'rm-panel-extensions'),
            'rejected' => __('❌ Rejected', 'rm-panel-extensions'),
            'cancelled' => __('⚫ Cancelled', 'rm-panel-extensions')
        ];
        return $labels[$status] ?? ucfirst($status);
    }
    
    // ============================================
    // AJAX HANDLERS
    // ============================================
    
    /**
     * AJAX: Get withdrawal details
     */
    public function ajax_get_withdrawal_details() {
        check_ajax_referer('rm_withdrawal_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $request_id = intval($_POST['request_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_withdrawal_requests';
        
        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $request_id
        ));
        
        if (!$request) {
            wp_send_json_error(['message' => 'Request not found']);
        }
        
        wp_send_json_success([
            'payment_details' => $request->payment_details,
            'amount' => $request->amount,
            'fee' => $request->processing_fee,
            'net_amount' => $request->net_amount
        ]);
    }
    
    /**
     * AJAX: Submit withdrawal request (user side)
     */
    public function ajax_submit_withdrawal() {
        check_ajax_referer('rm_withdrawal_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in', 'rm-panel-extensions')]);
        }
        
        $user_id = get_current_user_id();
        $payment_method_id = intval($_POST['payment_method_id']);
        $amount = floatval($_POST['amount']);
        $payment_details = isset($_POST['payment_details']) ? $_POST['payment_details'] : [];
        
        // Validate amount
        $balance = get_user_meta($user_id, 'rm_withdrawable_balance', true) ?: 0;
        
        if ($amount > $balance) {
            wp_send_json_error(['message' => __('Insufficient balance', 'rm-panel-extensions')]);
        }
        
        // Get payment method
        $method = RM_Payment_Methods::get_instance()->get_method_by_id($payment_method_id);
        
        if (!$method || !$method->is_active) {
            wp_send_json_error(['message' => __('Invalid payment method', 'rm-panel-extensions')]);
        }
        
        // Check limits
        if ($amount < $method->min_withdrawal) {
            wp_send_json_error([
                'message' => sprintf(__('Minimum withdrawal is $%s', 'rm-panel-extensions'), number_format($method->min_withdrawal, 2))
            ]);
        }
        
        if ($method->max_withdrawal && $amount > $method->max_withdrawal) {
            wp_send_json_error([
                'message' => sprintf(__('Maximum withdrawal is $%s', 'rm-panel-extensions'), number_format($method->max_withdrawal, 2))
            ]);
        }
        
        // Calculate fee
        $processing_fee = 0;
        if ($method->processing_fee_type === 'percentage') {
            $processing_fee = ($amount * $method->processing_fee_value) / 100;
        } elseif ($method->processing_fee_type === 'fixed') {
            $processing_fee = $method->processing_fee_value;
        }
        
        $net_amount = $amount - $processing_fee;
        
        // Deduct from balance
        $new_balance = $balance - $amount;
        update_user_meta($user_id, 'rm_withdrawable_balance', $new_balance);
        
        // Create withdrawal request
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_withdrawal_requests';
        
        $result = $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'payment_method_id' => $payment_method_id,
            'amount' => $amount,
            'processing_fee' => $processing_fee,
            'net_amount' => $net_amount,
            'payment_details' => wp_json_encode($payment_details),
            'status' => 'pending'
        ]);
        
        if ($result) {
            // Send notification to admin
            $this->notify_admin_new_withdrawal($wpdb->insert_id);
            
            // Send confirmation to user
            $this->send_user_withdrawal_confirmation($user_id, $wpdb->insert_id);
            
            wp_send_json_success([
                'message' => __('Withdrawal request submitted successfully!', 'rm-panel-extensions'),
                'new_balance' => $new_balance
            ]);
        } else {
            // Rollback balance
            update_user_meta($user_id, 'rm_withdrawable_balance', $balance);
            wp_send_json_error(['message' => __('Database error occurred', 'rm-panel-extensions')]);
        }
    }
    
    /**
     * AJAX: Cancel withdrawal request (user side)
     */
    public function ajax_cancel_withdrawal() {
        check_ajax_referer('rm_withdrawal_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        
        $user_id = get_current_user_id();
        $request_id = intval($_POST['request_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_withdrawal_requests';
        
        // Get request
        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND user_id = %d AND status = 'pending'",
            $request_id,
            $user_id
        ));
        
        if (!$request) {
            wp_send_json_error(['message' => __('Request not found or cannot be cancelled', 'rm-panel-extensions')]);
        }
        
        // Update status
        $wpdb->update($table_name, ['status' => 'cancelled'], ['id' => $request_id]);
        
        // Return amount to balance
        $balance = get_user_meta($user_id, 'rm_withdrawable_balance', true) ?: 0;
        update_user_meta($user_id, 'rm_withdrawable_balance', $balance + $request->amount);
        
        wp_send_json_success(['message' => __('Withdrawal request cancelled', 'rm-panel-extensions')]);
    }
    
    /**
     * AJAX: Approve withdrawal (admin side)
     */
    public function ajax_approve_withdrawal() {
        check_ajax_referer('rm_withdrawal_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $request_id = intval($_POST['request_id']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_withdrawal_requests';
        
        $result = $wpdb->update(
            $table_name,
            [
                'status' => 'approved',
                'processed_by' => get_current_user_id(),
                'processed_at' => current_time('mysql'),
                'admin_notes' => $notes
            ],
            ['id' => $request_id]
        );
        
        if ($result !== false) {
            $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $request_id));
            $this->send_user_withdrawal_approved($request);
            
            wp_send_json_success(['message' => 'Withdrawal approved successfully!']);
        } else {
            wp_send_json_error(['message' => 'Database error']);
        }
    }
    
    /**
     * AJAX: Reject withdrawal (admin side)
     */
    public function ajax_reject_withdrawal() {
        check_ajax_referer('rm_withdrawal_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $request_id = intval($_POST['request_id']);
        $reason = sanitize_textarea_field($_POST['reason']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_withdrawal_requests';
        
        $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $request_id));
        
        if (!$request) {
            wp_send_json_error(['message' => 'Request not found']);
        }
        
        // Update status
        $result = $wpdb->update(
            $table_name,
            [
                'status' => 'rejected',
                'processed_by' => get_current_user_id(),
                'processed_at' => current_time('mysql'),
                'admin_notes' => $reason
            ],
            ['id' => $request_id]
        );
        
        if ($result !== false) {
            // Return amount to user's balance
            $balance = get_user_meta($request->user_id, 'rm_withdrawable_balance', true) ?: 0;
            update_user_meta($request->user_id, 'rm_withdrawable_balance', $balance + $request->amount);
            
            $this->send_user_withdrawal_rejected($request, $reason);
            
            wp_send_json_success(['message' => 'Withdrawal rejected and amount returned to user']);
        } else {
            wp_send_json_error(['message' => 'Database error']);
        }
    }
    
    /**
     * AJAX: Complete withdrawal (admin side)
     */
    public function ajax_complete_withdrawal() {
        check_ajax_referer('rm_withdrawal_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $request_id = intval($_POST['request_id']);
        $transaction_ref = sanitize_text_field($_POST['transaction_reference']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_withdrawal_requests';
        
        $result = $wpdb->update(
            $table_name,
            [
                'status' => 'completed',
                'processed_by' => get_current_user_id(),
                'processed_at' => current_time('mysql'),
                'transaction_reference' => $transaction_ref,
                'admin_notes' => $notes
            ],
            ['id' => $request_id]
        );
        
        if ($result !== false) {
            $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $request_id));
            
            // Update user's total paid amount
            $total_paid = get_user_meta($request->user_id, 'rm_total_paid', true) ?: 0;
            update_user_meta($request->user_id, 'rm_total_paid', $total_paid + $request->net_amount);
            
            $this->send_user_withdrawal_completed($request);
            
            wp_send_json_success(['message' => 'Withdrawal marked as completed successfully!']);
        } else {
            wp_send_json_error(['message' => 'Database error']);
        }
    }
    
    // ============================================
    // EMAIL NOTIFICATION METHODS (Stubs)
    // ============================================
    
    private function notify_admin_new_withdrawal($request_id) {
        // TODO: Implementation for admin notification
        // Send email to admin about new withdrawal request
    }
    
    private function send_user_withdrawal_confirmation($user_id, $request_id) {
        // TODO: Implementation for user confirmation
        // Send email to user confirming withdrawal request received
    }
    
    private function send_user_withdrawal_approved($request) {
        // TODO: Implementation for approval notification
        // Send email to user that withdrawal was approved
    }
    
    private function send_user_withdrawal_rejected($request, $reason) {
        // TODO: Implementation for rejection notification
        // Send email to user that withdrawal was rejected with reason
    }
    
    private function send_user_withdrawal_completed($request) {
        // TODO: Implementation for completion notification
        // Send email to user that payment has been sent
    }
}

// Initialize
RM_Withdrawal_Requests::get_instance();