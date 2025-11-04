<?php
/**
 * Survey Approval Admin System - Unified & Optimized
 * 
 * Features:
 * - Pending approvals management
 * - Approval workflow with balance updates
 * - Rejection workflow with notifications
 * - Admin notices for pending approvals
 * - Email notifications
 * - Balance tracking integration
 * 
 * @package RM_Panel_Extensions
 * @version 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Survey_Approval_Admin {
    
    private $tracker;
    
    public function __construct() {
        $this->tracker = new RM_Panel_Survey_Tracking();
        
        add_action('admin_menu', [$this, 'add_admin_menu'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_notices', [$this, 'pending_approval_notice']);
        
        // AJAX handlers
        add_action('wp_ajax_rm_approve_survey_v2', [$this, 'ajax_approve']);
        add_action('wp_ajax_rm_reject_survey_v2', [$this, 'ajax_reject']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        $pending_count = $this->get_pending_count();
        
        add_submenu_page(
            'edit.php?post_type=rm_survey',
            __('Survey Approvals', 'rm-panel-extensions'),
            sprintf(
                __('Pending Approvals %s', 'rm-panel-extensions'),
                '<span class="awaiting-mod count-' . $pending_count . '"><span class="pending-count">' . $pending_count . '</span></span>'
            ),
            'manage_options',
            'rm-survey-approvals',
            [$this, 'render_approval_page']
        );
    }
    
    /**
     * Show pending approval notice
     */
    public function pending_approval_notice() {
        $pending_count = $this->get_pending_count();
        
        if ($pending_count > 0 && current_user_can('manage_options')) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php _e('Survey Approvals:', 'rm-panel-extensions'); ?></strong>
                    <?php 
                    printf(
                        __('You have %d survey response(s) waiting for approval. <a href="%s">Review now</a>', 'rm-panel-extensions'),
                        $pending_count,
                        admin_url('edit.php?post_type=rm_survey&page=rm-survey-approvals')
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Render approval page
     */
    public function render_approval_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        
        // Get filter
        $status_filter = isset($_GET['approval_status']) ? sanitize_text_field($_GET['approval_status']) : 'pending';
        
        // Get responses with enhanced query
        $query = "SELECT 
                    r.*,
                    u.display_name,
                    u.user_email,
                    p.post_title as survey_title,
                    pm.meta_value as survey_amount,
                    approver.display_name as approved_by_name,
                    TIMESTAMPDIFF(MINUTE, r.start_time, r.completion_time) as duration_minutes
                  FROM $table_name r
                  LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                  LEFT JOIN {$wpdb->posts} p ON r.survey_id = p.ID
                  LEFT JOIN {$wpdb->postmeta} pm ON (pm.post_id = r.survey_id AND pm.meta_key = '_rm_survey_amount')
                  LEFT JOIN {$wpdb->users} approver ON r.approved_by = approver.ID
                  WHERE r.approval_status = %s
                  AND r.completion_status = 'success'
                  ORDER BY r.completion_time DESC";
        
        $responses = $wpdb->get_results($wpdb->prepare($query, $status_filter));
        
        // Get counts
        $pending_count = $this->get_count_by_status('pending');
        $approved_count = $this->get_count_by_status('approved');
        $rejected_count = $this->get_count_by_status('rejected');
        
        ?>
        <div class="wrap">
            <h1><?php _e('Survey Response Approvals', 'rm-panel-extensions'); ?></h1>
            
            <ul class="subsubsub">
                <li>
                    <a href="?post_type=rm_survey&page=rm-survey-approvals&approval_status=pending" 
                       class="<?php echo $status_filter === 'pending' ? 'current' : ''; ?>">
                        <?php printf(__('Pending Approval (%d)', 'rm-panel-extensions'), $pending_count); ?>
                    </a> |
                </li>
                <li>
                    <a href="?post_type=rm_survey&page=rm-survey-approvals&approval_status=approved" 
                       class="<?php echo $status_filter === 'approved' ? 'current' : ''; ?>">
                        <?php printf(__('Approved (%d)', 'rm-panel-extensions'), $approved_count); ?>
                    </a> |
                </li>
                <li>
                    <a href="?post_type=rm_survey&page=rm-survey-approvals&approval_status=rejected" 
                       class="<?php echo $status_filter === 'rejected' ? 'current' : ''; ?>">
                        <?php printf(__('Rejected (%d)', 'rm-panel-extensions'), $rejected_count); ?>
                    </a>
                </li>
            </ul>
            
            <div class="clear"></div>
            
            <?php if (empty($responses)) : ?>
                <div class="notice notice-info inline">
                    <p><?php _e('No responses found with this status.', 'rm-panel-extensions'); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php _e('ID', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('User', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Survey', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Amount', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Completed', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Duration', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Country', 'rm-panel-extensions'); ?></th>
                            <?php if ($status_filter !== 'pending') : ?>
                                <th><?php _e('Approved By', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Approval Date', 'rm-panel-extensions'); ?></th>
                            <?php endif; ?>
                            <th><?php _e('Actions', 'rm-panel-extensions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($responses as $response) : ?>
                            <tr data-response-id="<?php echo $response->id; ?>">
                                <td><?php echo $response->id; ?></td>
                                <td>
                                    <strong><?php echo esc_html($response->display_name); ?></strong><br>
                                    <small><?php echo esc_html($response->user_email); ?></small>
                                </td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($response->survey_id); ?>" target="_blank">
                                        <?php echo esc_html($response->survey_title); ?>
                                    </a>
                                </td>
                                <td>
                                    <strong style="color: #2ea44f; font-size: 14px;">
                                        $<?php echo number_format($response->survey_amount ?: 0, 2); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php echo date_i18n('M j, Y', strtotime($response->completion_time)); ?><br>
                                    <small><?php echo date_i18n('H:i', strtotime($response->completion_time)); ?></small>
                                </td>
                                <td><?php echo $response->duration_minutes ? $response->duration_minutes . ' min' : '—'; ?></td>
                                <td><?php echo esc_html($response->country ?: '—'); ?></td>
                                
                                <?php if ($status_filter !== 'pending') : ?>
                                    <td><?php echo esc_html($response->approved_by_name ?: '—'); ?></td>
                                    <td>
                                        <?php if ($response->approval_date) : ?>
                                            <?php echo date_i18n('M j, Y H:i', strtotime($response->approval_date)); ?>
                                        <?php else : ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                
                                <td>
                                    <?php if ($status_filter === 'pending') : ?>
                                        <button type="button" class="button button-primary approve-btn" 
                                                data-response-id="<?php echo $response->id; ?>"
                                                data-user-id="<?php echo $response->user_id; ?>"
                                                data-amount="<?php echo $response->survey_amount; ?>">
                                            <span class="dashicons dashicons-yes"></span>
                                            <?php _e('Approve', 'rm-panel-extensions'); ?>
                                        </button>
                                        <button type="button" class="button button-secondary reject-btn" 
                                                data-response-id="<?php echo $response->id; ?>">
                                            <span class="dashicons dashicons-no"></span>
                                            <?php _e('Reject', 'rm-panel-extensions'); ?>
                                        </button>
                                    <?php else : ?>
                                        <span class="approval-status-badge status-<?php echo esc_attr($response->approval_status); ?>">
                                            <?php echo ucfirst($response->approval_status); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($response->admin_notes) : ?>
                                <tr class="admin-notes-row">
                                    <td colspan="<?php echo $status_filter === 'pending' ? '9' : '11'; ?>">
                                        <strong><?php _e('Admin Notes:', 'rm-panel-extensions'); ?></strong>
                                        <?php echo esc_html($response->admin_notes); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Approval Modal -->
        <div id="approval-modal" class="rm-modal" style="display:none;">
            <div class="rm-modal-content">
                <span class="close-modal">&times;</span>
                <h2><?php _e('Approve Survey Response', 'rm-panel-extensions'); ?></h2>
                <form id="approval-form">
                    <input type="hidden" id="approve-response-id" value="">
                    <input type="hidden" id="approve-user-id" value="">
                    <input type="hidden" id="approve-amount" value="">
                    
                    <div class="approval-summary">
                        <p><strong><?php _e('This will:', 'rm-panel-extensions'); ?></strong></p>
                        <ul>
                            <li>✅ Mark the survey response as approved</li>
                            <li>💰 Add <strong>$<span class="approval-amount">0.00</span></strong> to user's withdrawable balance</li>
                            <li>📧 Send approval notification to user</li>
                        </ul>
                    </div>
                    
                    <p>
                        <label for="approval-notes"><?php _e('Admin Notes (optional):', 'rm-panel-extensions'); ?></label>
                        <textarea id="approval-notes" rows="3" style="width: 100%;" 
                                  placeholder="<?php _e('Add any notes about this approval...', 'rm-panel-extensions'); ?>"></textarea>
                    </p>
                    
                    <p class="submit-actions">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-yes"></span>
                            <?php _e('Confirm Approval', 'rm-panel-extensions'); ?>
                        </button>
                        <button type="button" class="button button-secondary cancel-modal">
                            <?php _e('Cancel', 'rm-panel-extensions'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Rejection Modal -->
        <div id="rejection-modal" class="rm-modal" style="display:none;">
            <div class="rm-modal-content">
                <span class="close-modal">&times;</span>
                <h2><?php _e('Reject Survey Response', 'rm-panel-extensions'); ?></h2>
                <form id="rejection-form">
                    <input type="hidden" id="reject-response-id" value="">
                    
                    <p>
                        <label for="rejection-notes"><?php _e('Reason for Rejection (required):', 'rm-panel-extensions'); ?></label>
                        <textarea id="rejection-notes" rows="4" style="width: 100%;" required
                                  placeholder="<?php _e('Please provide a reason for rejecting this response...', 'rm-panel-extensions'); ?>"></textarea>
                    </p>
                    
                    <p class="submit-actions">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-no"></span>
                            <?php _e('Confirm Rejection', 'rm-panel-extensions'); ?>
                        </button>
                        <button type="button" class="button button-secondary cancel-modal">
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
            }
            .rm-modal-content {
                background-color: #fefefe;
                margin: 5% auto;
                padding: 30px;
                border: 1px solid #888;
                width: 600px;
                max-width: 90%;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .rm-modal h2 {
                margin-top: 0;
                color: #1d2327;
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
            .approval-summary {
                background: #f0f8ff;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .approval-summary ul {
                margin: 10px 0 0 0;
                padding-left: 20px;
            }
            .approval-summary li {
                margin: 8px 0;
                font-size: 14px;
            }
            .submit-actions {
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
            }
            .button-large {
                padding: 8px 16px !important;
                height: auto !important;
                line-height: 1.5 !important;
            }
            .approval-status-badge {
                display: inline-block;
                padding: 5px 12px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
            }
            .status-approved {
                background: #d4edda;
                color: #155724;
            }
            .status-rejected {
                background: #f8d7da;
                color: #721c24;
            }
            .admin-notes-row {
                background: #f9f9f9;
            }
            .admin-notes-row td {
                padding: 10px 15px !important;
                font-size: 13px;
                color: #666;
            }
        </style>
        <?php
    }
    
    /**
     * Get count by status
     */
    private function get_count_by_status($status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE approval_status = %s 
             AND completion_status = 'success'",
            $status
        ));
    }
    
    /**
     * Get pending count
     */
    private function get_pending_count() {
        return $this->get_count_by_status('pending');
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'rm_survey_page_rm-survey-approvals') {
            return;
        }
        
        wp_enqueue_script(
            'rm-survey-approval-v2',
            RM_PANEL_EXT_PLUGIN_URL . 'assets/js/survey-approval.js',
            ['jquery'],
            RM_PANEL_EXT_VERSION,
            true
        );
        
        wp_localize_script('rm-survey-approval', 'rmApprovalAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rm_approval_v2_nonce'),
            'current_user' => wp_get_current_user()->display_name,
            'strings' => [
                'approving' => __('Processing approval...', 'rm-panel-extensions'),
                'rejecting' => __('Processing rejection...', 'rm-panel-extensions'),
                'success' => __('Success!', 'rm-panel-extensions'),
                'error' => __('Error occurred', 'rm-panel-extensions')
            ]
        ]);
    }
    
    /**
     * AJAX approve handler
     */
    public function ajax_approve() {
        check_ajax_referer('rm_approval_v2_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'rm-panel-extensions')]);
        }
        
        $response_id = isset($_POST['response_id']) ? intval($_POST['response_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        
        // Update response
        $result = $wpdb->update(
            $table_name,
            [
                'approval_status' => 'approved',
                'approved_by' => get_current_user_id(),
                'approval_date' => current_time('mysql'),
                'admin_notes' => $notes
            ],
            ['id' => $response_id]
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Database error occurred', 'rm-panel-extensions')]);
        }
        
        // Add to user's withdrawable balance
        $current_balance = floatval(get_user_meta($user_id, 'rm_withdrawable_balance', true));
        update_user_meta($user_id, 'rm_withdrawable_balance', $current_balance + $amount);
        
        // Track total earnings
        $total_earned = floatval(get_user_meta($user_id, 'rm_total_earnings', true));
        update_user_meta($user_id, 'rm_total_earnings', $total_earned + $amount);
        
        // Send email notification
        $this->send_approval_email($response_id, $user_id, $amount);
        
        do_action('rm_survey_approved_v2', $user_id, $response_id, $amount);
        
        wp_send_json_success([
            'message' => __('Survey response approved successfully! User can now withdraw this amount.', 'rm-panel-extensions')
        ]);
    }
    
    /**
     * AJAX reject handler
     */
    public function ajax_reject() {
        check_ajax_referer('rm_approval_v2_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'rm-panel-extensions')]);
        }
        
        $response_id = isset($_POST['response_id']) ? intval($_POST['response_id']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        if (empty($notes)) {
            wp_send_json_error(['message' => __('Rejection reason is required', 'rm-panel-extensions')]);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        
        $result = $wpdb->update(
            $table_name,
            [
                'approval_status' => 'rejected',
                'approved_by' => get_current_user_id(),
                'approval_date' => current_time('mysql'),
                'admin_notes' => $notes
            ],
            ['id' => $response_id]
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Database error occurred', 'rm-panel-extensions')]);
        }
        
        // Get response details for email
        $response = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE id = %d",
            $response_id
        ));
        
        if ($response) {
            $this->send_rejection_email($response_id, $response->user_id, $notes);
        }
        
        do_action('rm_survey_rejected_v2', $response_id);
        
        wp_send_json_success([
            'message' => __('Survey response rejected.', 'rm-panel-extensions')
        ]);
    }
    
    /**
     * Send approval email
     */
    private function send_approval_email($response_id, $user_id, $amount) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        $response = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, p.post_title as survey_title 
             FROM $table_name r
             LEFT JOIN {$wpdb->posts} p ON r.survey_id = p.ID
             WHERE r.id = %d",
            $response_id
        ));
        
        if (!$response) return;
        
        $subject = sprintf(__('Survey Approved: %s', 'rm-panel-extensions'), $response->survey_title);
        
        $message = sprintf(
            __('Hello %s,

Great news! Your survey response has been approved.

Survey: %s
Amount Approved: $%s
Approval Date: %s

The amount has been added to your withdrawable balance. You can now request a withdrawal from your dashboard.

Current Withdrawable Balance: $%s

Thank you for your participation!

Best regards,
%s', 'rm-panel-extensions'),
            $user->display_name,
            $response->survey_title,
            number_format($amount, 2),
            date_i18n(get_option('date_format'), strtotime($response->approval_date)),
            number_format(get_user_meta($user_id, 'rm_withdrawable_balance', true), 2),
            get_bloginfo('name')
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Send rejection email
     */
    private function send_rejection_email($response_id, $user_id, $notes) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        $response = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, p.post_title as survey_title 
             FROM $table_name r
             LEFT JOIN {$wpdb->posts} p ON r.survey_id = p.ID
             WHERE r.id = %d",
            $response_id
        ));
        
        if (!$response) return;
        
        $subject = sprintf(__('Survey Response Update: %s', 'rm-panel-extensions'), $response->survey_title);
        
        $message = sprintf(
            __('Hello %s,

Your survey response has been reviewed.

Survey: %s
Status: Not Approved

Reason: %s

If you have questions about this decision, please contact our support team.

Best regards,
%s', 'rm-panel-extensions'),
            $user->display_name,
            $response->survey_title,
            $notes,
            get_bloginfo('name')
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
}

// Initialize
new RM_Survey_Approval_Admin();