<?php
/**
 * Survey Approval Admin Page
 * File: modules/survey/class-survey-approval-admin.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Survey_Approval_Admin {
    
    private $tracker;
    
    public function __construct() {
        $this->tracker = new RM_Panel_Survey_Tracking();
        
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_rm_approve_survey', [$this, 'ajax_approve']);
        add_action('wp_ajax_rm_reject_survey', [$this, 'ajax_reject']);
        add_action('admin_notices', [$this, 'pending_approval_notice']);
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'rm-panel-extensions',
            __('Pending Approvals', 'rm-panel-extensions'),
            sprintf(__('Pending Approvals %s', 'rm-panel-extensions'), '<span class="update-plugins count-' . $this->tracker->get_pending_count() . '"><span class="plugin-count">' . $this->tracker->get_pending_count() . '</span></span>'),
            'manage_options',
            'rm-survey-approvals',
            [$this, 'render_approval_page']
        );
    }
    
    public function pending_approval_notice() {
        $pending_count = $this->tracker->get_pending_count();
        
        if ($pending_count > 0 && current_user_can('manage_options')) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php 
                    printf(
                        __('You have %d survey response(s) pending approval. <a href="%s">Review now</a>', 'rm-panel-extensions'),
                        $pending_count,
                        admin_url('admin.php?page=rm-survey-approvals')
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }
    
    public function render_approval_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        
        // Get filter
        $status_filter = isset($_GET['approval_status']) ? sanitize_text_field($_GET['approval_status']) : 'pending';
        
        // Get responses
        $query = "SELECT r.*, u.display_name, u.user_email, p.post_title as survey_title,
                         pm.meta_value as survey_amount
                  FROM $table_name r
                  LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                  LEFT JOIN {$wpdb->posts} p ON r.survey_id = p.ID
                  LEFT JOIN {$wpdb->postmeta} pm ON (pm.post_id = r.survey_id AND pm.meta_key = '_rm_survey_amount')
                  WHERE r.approval_status = %s
                  ORDER BY r.completion_time DESC";
        
        $responses = $wpdb->get_results($wpdb->prepare($query, $status_filter));
        
        ?>
        <div class="wrap">
            <h1><?php _e('Survey Approval Management', 'rm-panel-extensions'); ?></h1>
            
            <ul class="subsubsub">
                <li>
                    <a href="?page=rm-survey-approvals&approval_status=pending" class="<?php echo $status_filter === 'pending' ? 'current' : ''; ?>">
                        <?php printf(__('Pending (%d)', 'rm-panel-extensions'), $this->get_count_by_status('pending')); ?>
                    </a> |
                </li>
                <li>
                    <a href="?page=rm-survey-approvals&approval_status=approved" class="<?php echo $status_filter === 'approved' ? 'current' : ''; ?>">
                        <?php printf(__('Approved (%d)', 'rm-panel-extensions'), $this->get_count_by_status('approved')); ?>
                    </a> |
                </li>
                <li>
                    <a href="?page=rm-survey-approvals&approval_status=rejected" class="<?php echo $status_filter === 'rejected' ? 'current' : ''; ?>">
                        <?php printf(__('Rejected (%d)', 'rm-panel-extensions'), $this->get_count_by_status('rejected')); ?>
                    </a>
                </li>
            </ul>
            
            <div class="clear"></div>
            
            <?php if (empty($responses)) : ?>
                <p><?php _e('No responses found with this status.', 'rm-panel-extensions'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php _e('ID', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('User', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Survey', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Country', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Start Time', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Completion Time', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Duration', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Amount', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Status', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Actions', 'rm-panel-extensions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($responses as $response) : ?>
                            <?php
                            $duration = '';
                            if ($response->start_time && $response->completion_time) {
                                $start = strtotime($response->start_time);
                                $end = strtotime($response->completion_time);
                                $diff = $end - $start;
                                $duration = gmdate('H:i:s', $diff);
                            }
                            ?>
                            <tr data-response-id="<?php echo $response->id; ?>">
                                <td><?php echo $response->id; ?></td>
                                <td>
                                    <strong><?php echo esc_html($response->display_name); ?></strong><br>
                                    <small><?php echo esc_html($response->user_email); ?></small>
                                </td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($response->survey_id); ?>">
                                        <?php echo esc_html($response->survey_title); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($response->country ?: 'N/A'); ?></td>
                                <td><?php echo $response->start_time ? date_i18n('Y-m-d H:i', strtotime($response->start_time)) : 'N/A'; ?></td>
                                <td><?php echo $response->completion_time ? date_i18n('Y-m-d H:i', strtotime($response->completion_time)) : 'N/A'; ?></td>
                                <td><?php echo $duration ?: 'N/A'; ?></td>
                                <td><strong>$<?php echo number_format($response->survey_amount ?: 0, 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($response->approval_status); ?>">
                                        <?php echo esc_html(ucfirst($response->approval_status)); ?>
                                    </span>
                                    <?php if ($response->approval_status === 'approved' || $response->approval_status === 'rejected') : ?>
                                        <br><small><?php echo date_i18n('Y-m-d', strtotime($response->approval_date)); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($status_filter === 'pending') : ?>
                                        <button type="button" class="button button-primary approve-btn" 
                                                data-response-id="<?php echo $response->id; ?>">
                                            <?php _e('Approve', 'rm-panel-extensions'); ?>
                                        </button>
                                        <button type="button" class="button button-secondary reject-btn" 
                                                data-response-id="<?php echo $response->id; ?>">
                                            <?php _e('Reject', 'rm-panel-extensions'); ?>
                                        </button>
                                    <?php else : ?>
                                        <a href="#" class="view-details" data-response-id="<?php echo $response->id; ?>">
                                            <?php _e('View Details', 'rm-panel-extensions'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($response->admin_notes) : ?>
                                <tr class="admin-notes-row">
                                    <td colspan="10">
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
        <div id="approval-modal" class="approval-modal" style="display:none;">
            <div class="approval-modal-content">
                <span class="close-modal">&times;</span>
                <h2><?php _e('Approve Survey Response', 'rm-panel-extensions'); ?></h2>
                <form id="approval-form">
                    <input type="hidden" id="approve-response-id" value="">
                    <p>
                        <label for="approval-notes"><?php _e('Admin Notes (optional):', 'rm-panel-extensions'); ?></label>
                        <textarea id="approval-notes" rows="4" style="width: 100%;"></textarea>
                    </p>
                    <p>
                        <button type="submit" class="button button-primary"><?php _e('Confirm Approval', 'rm-panel-extensions'); ?></button>
                        <button type="button" class="button cancel-modal"><?php _e('Cancel', 'rm-panel-extensions'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Rejection Modal -->
        <div id="rejection-modal" class="approval-modal" style="display:none;">
            <div class="approval-modal-content">
                <span class="close-modal">&times;</span>
                <h2><?php _e('Reject Survey Response', 'rm-panel-extensions'); ?></h2>
                <form id="rejection-form">
                    <input type="hidden" id="reject-response-id" value="">
                    <p>
                        <label for="rejection-notes"><?php _e('Reason for Rejection (required):', 'rm-panel-extensions'); ?></label>
                        <textarea id="rejection-notes" rows="4" style="width: 100%;" required></textarea>
                    </p>
                    <p>
                        <button type="submit" class="button button-primary"><?php _e('Confirm Rejection', 'rm-panel-extensions'); ?></button>
                        <button type="button" class="button cancel-modal"><?php _e('Cancel', 'rm-panel-extensions'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
        <style>
            .status-badge {
                padding: 5px 10px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: bold;
            }
            .status-pending {
                background: #fff3cd;
                color: #856404;
            }
            .status-approved {
                background: #d4edda;
                color: #155724;
            }
            .status-rejected {
                background: #f8d7da;
                color: #721c24;
            }
            .approval-modal {
                position: fixed;
                z-index: 100000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.7);
            }
            .approval-modal-content {
                background-color: #fefefe;
                margin: 10% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 500px;
                max-width: 90%;
                border-radius: 5px;
            }
            .close-modal {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }
            .close-modal:hover {
                color: #000;
            }
            .admin-notes-row {
                background: #f9f9f9;
            }
        </style>
        <?php
    }
    
    private function get_count_by_status($status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE approval_status = %s",
            $status
        ));
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'rm-panel-ext_page_rm-survey-approvals') {
            return;
        }
        
        wp_enqueue_script(
            'rm-survey-approval',
            RM_PANEL_EXT_PLUGIN_URL . 'assets/js/survey-approval.js',
            ['jquery'],
            RM_PANEL_EXT_VERSION,
            true
        );
        
        wp_localize_script('rm-survey-approval', 'rmApprovalAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rm_approval_nonce'),
            'strings' => [
                'approving' => __('Approving...', 'rm-panel-extensions'),
                'rejecting' => __('Rejecting...', 'rm-panel-extensions'),
                'success' => __('Success!', 'rm-panel-extensions'),
                'error' => __('Error occurred', 'rm-panel-extensions')
            ]
        ]);
    }
    
    public function ajax_approve() {
        check_ajax_referer('rm_approval_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'rm-panel-extensions')]);
        }
        
        $response_id = isset($_POST['response_id']) ? intval($_POST['response_id']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        $result = $this->tracker->approve_survey_response($response_id, $notes);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success(['message' => __('Survey response approved successfully!', 'rm-panel-extensions')]);
    }
    
    public function ajax_reject() {
        check_ajax_referer('rm_approval_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'rm-panel-extensions')]);
        }
        
        $response_id = isset($_POST['response_id']) ? intval($_POST['response_id']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        if (empty($notes)) {
            wp_send_json_error(['message' => __('Rejection reason is required', 'rm-panel-extensions')]);
        }
        
        $result = $this->tracker->reject_survey_response($response_id, $notes);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success(['message' => __('Survey response rejected.', 'rm-panel-extensions')]);
    }
}

new RM_Survey_Approval_Admin();