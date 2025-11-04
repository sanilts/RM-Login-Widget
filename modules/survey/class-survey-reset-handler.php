<?php
/**
 * Survey Reset Handler
 * Allows admins to reset completed surveys back to incomplete state
 * 
 * File: modules/survey/class-survey-reset-handler.php
 * Version: 1.0.0
 * 
 * @package RM_Panel_Extensions
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Survey_Reset_Handler {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'rm_survey_responses';
        
        // Admin page hooks
        add_action('admin_menu', [$this, 'add_reset_admin_menu'], 25);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // AJAX handlers
        add_action('wp_ajax_rm_reset_survey_response', [$this, 'ajax_reset_single_response']);
        add_action('wp_ajax_rm_bulk_reset_responses', [$this, 'ajax_bulk_reset_responses']);
        add_action('wp_ajax_rm_reset_user_all_surveys', [$this, 'ajax_reset_user_all_surveys']);
        
        // Add reset button to approval page
        add_action('admin_footer', [$this, 'add_reset_buttons_to_approval_page']);
        
        // Add bulk action to responses list
        add_filter('bulk_actions-edit-rm_survey_response', [$this, 'add_bulk_reset_action']);
        add_filter('handle_bulk_actions-edit-rm_survey_response', [$this, 'handle_bulk_reset_action'], 10, 3);
    }
    
    /**
     * Add admin menu
     */
    public function add_reset_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=rm_survey',
            __('Reset Surveys', 'rm-panel-extensions'),
            __('Reset Surveys', 'rm-panel-extensions'),
            'manage_options',
            'rm-survey-reset',
            [$this, 'render_reset_page']
        );
    }
    
    /**
     * Render reset page
     */
    public function render_reset_page() {
        global $wpdb;
        
        // Get filter parameters
        $user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $survey_filter = isset($_GET['survey_id']) ? intval($_GET['survey_id']) : 0;
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'completed';
        
        // Build query
        $query = "SELECT 
                    r.*,
                    u.display_name,
                    u.user_email,
                    p.post_title as survey_title,
                    pm.meta_value as survey_amount
                  FROM {$this->table_name} r
                  LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                  LEFT JOIN {$wpdb->posts} p ON r.survey_id = p.ID
                  LEFT JOIN {$wpdb->postmeta} pm ON (pm.post_id = r.survey_id AND pm.meta_key = '_rm_survey_amount')
                  WHERE 1=1";
        
        $query_args = [];
        
        if ($user_filter) {
            $query .= " AND r.user_id = %d";
            $query_args[] = $user_filter;
        }
        
        if ($survey_filter) {
            $query .= " AND r.survey_id = %d";
            $query_args[] = $survey_filter;
        }
        
        if ($status_filter) {
            $query .= " AND r.status = %s";
            $query_args[] = $status_filter;
        }
        
        $query .= " ORDER BY r.completion_time DESC LIMIT 100";
        
        if (!empty($query_args)) {
            $responses = $wpdb->get_results($wpdb->prepare($query, ...$query_args));
        } else {
            $responses = $wpdb->get_results($query);
        }
        
        // Get users who have completed surveys
        $users_with_responses = $wpdb->get_results(
            "SELECT DISTINCT u.ID, u.display_name, u.user_email,
                    COUNT(r.id) as total_responses
             FROM {$wpdb->users} u
             INNER JOIN {$this->table_name} r ON u.ID = r.user_id
             WHERE r.status = 'completed'
             GROUP BY u.ID
             ORDER BY u.display_name"
        );
        
        // Get surveys with responses
        $surveys_with_responses = $wpdb->get_results(
            "SELECT DISTINCT p.ID, p.post_title,
                    COUNT(r.id) as total_responses
             FROM {$wpdb->posts} p
             INNER JOIN {$this->table_name} r ON p.ID = r.survey_id
             WHERE p.post_type = 'rm_survey' AND r.status = 'completed'
             GROUP BY p.ID
             ORDER BY p.post_title"
        );
        
        ?>
        <div class="wrap">
            <h1><?php _e('Reset Survey Responses', 'rm-panel-extensions'); ?></h1>
            
            <div class="notice notice-warning">
                <p><strong><?php _e('⚠️ Warning:', 'rm-panel-extensions'); ?></strong> 
                <?php _e('Resetting survey responses will:', 'rm-panel-extensions'); ?></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><?php _e('Change status from "completed" to "not_complete"', 'rm-panel-extensions'); ?></li>
                    <li><?php _e('Clear completion timestamps and data', 'rm-panel-extensions'); ?></li>
                    <li><?php _e('Allow users to retake the survey', 'rm-panel-extensions'); ?></li>
                    <li><?php _e('NOT automatically refund any approved payments (you must handle this separately)', 'rm-panel-extensions'); ?></li>
                </ul>
            </div>
            
            <!-- Filters -->
            <div class="rm-reset-filters" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; margin: 20px 0;">
                <h2><?php _e('Filter Responses', 'rm-panel-extensions'); ?></h2>
                <form method="get" action="">
                    <input type="hidden" name="post_type" value="rm_survey">
                    <input type="hidden" name="page" value="rm-survey-reset">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label for="user_id"><strong><?php _e('User:', 'rm-panel-extensions'); ?></strong></label>
                            <select name="user_id" id="user_id" style="width: 100%;">
                                <option value=""><?php _e('All Users', 'rm-panel-extensions'); ?></option>
                                <?php foreach ($users_with_responses as $user) : ?>
                                    <option value="<?php echo $user->ID; ?>" <?php selected($user_filter, $user->ID); ?>>
                                        <?php echo esc_html($user->display_name); ?> (<?php echo $user->total_responses; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="survey_id"><strong><?php _e('Survey:', 'rm-panel-extensions'); ?></strong></label>
                            <select name="survey_id" id="survey_id" style="width: 100%;">
                                <option value=""><?php _e('All Surveys', 'rm-panel-extensions'); ?></option>
                                <?php foreach ($surveys_with_responses as $survey) : ?>
                                    <option value="<?php echo $survey->ID; ?>" <?php selected($survey_filter, $survey->ID); ?>>
                                        <?php echo esc_html($survey->post_title); ?> (<?php echo $survey->total_responses; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="status"><strong><?php _e('Status:', 'rm-panel-extensions'); ?></strong></label>
                            <select name="status" id="status" style="width: 100%;">
                                <option value="completed" <?php selected($status_filter, 'completed'); ?>><?php _e('Completed', 'rm-panel-extensions'); ?></option>
                                <option value="waiting_to_complete" <?php selected($status_filter, 'waiting_to_complete'); ?>><?php _e('Waiting', 'rm-panel-extensions'); ?></option>
                                <option value="not_complete" <?php selected($status_filter, 'not_complete'); ?>><?php _e('Not Complete', 'rm-panel-extensions'); ?></option>
                            </select>
                        </div>
                        
                        <div style="display: flex; align-items: flex-end;">
                            <button type="submit" class="button button-primary"><?php _e('Filter', 'rm-panel-extensions'); ?></button>
                            <a href="?post_type=rm_survey&page=rm-survey-reset" class="button" style="margin-left: 10px;"><?php _e('Clear', 'rm-panel-extensions'); ?></a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Bulk Actions -->
            <?php if (!empty($responses)) : ?>
                <div class="rm-bulk-actions" style="background: #fff; padding: 15px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 20px;">
                    <strong><?php _e('Bulk Actions:', 'rm-panel-extensions'); ?></strong>
                    <button type="button" class="button" id="select-all-responses"><?php _e('Select All', 'rm-panel-extensions'); ?></button>
                    <button type="button" class="button" id="deselect-all-responses"><?php _e('Deselect All', 'rm-panel-extensions'); ?></button>
                    <button type="button" class="button button-primary" id="bulk-reset-selected" disabled>
                        <?php _e('Reset Selected', 'rm-panel-extensions'); ?>
                    </button>
                    <span id="selected-count" style="margin-left: 10px; color: #666;"></span>
                </div>
            <?php endif; ?>
            
            <!-- Results Table -->
            <?php if (empty($responses)) : ?>
                <div class="notice notice-info">
                    <p><?php _e('No responses found matching your criteria.', 'rm-panel-extensions'); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped" style="background: #fff;">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="select-all-checkbox"></th>
                            <th style="width: 50px;"><?php _e('ID', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('User', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Survey', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Status', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Completion', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Completed', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Approval', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Amount', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Actions', 'rm-panel-extensions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($responses as $response) : ?>
                            <tr data-response-id="<?php echo $response->id; ?>">
                                <td>
                                    <input type="checkbox" class="response-checkbox" value="<?php echo $response->id; ?>">
                                </td>
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
                                    <span class="status-badge status-<?php echo esc_attr($response->status); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $response->status))); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($response->completion_status) : ?>
                                        <span class="completion-badge completion-<?php echo esc_attr($response->completion_status); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $response->completion_status))); ?>
                                        </span>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($response->completion_time) : ?>
                                        <?php echo date_i18n('M j, Y H:i', strtotime($response->completion_time)); ?>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($response->approval_status) : ?>
                                        <span class="approval-badge approval-<?php echo esc_attr($response->approval_status); ?>">
                                            <?php echo esc_html(ucfirst($response->approval_status)); ?>
                                        </span>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($response->survey_amount) : ?>
                                        <strong style="color: #2ea44f;">$<?php echo number_format($response->survey_amount, 2); ?></strong>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" 
                                            class="button button-small reset-single-btn" 
                                            data-response-id="<?php echo $response->id; ?>"
                                            data-user-name="<?php echo esc_attr($response->display_name); ?>"
                                            data-survey-title="<?php echo esc_attr($response->survey_title); ?>">
                                        <span class="dashicons dashicons-update-alt"></span>
                                        <?php _e('Reset', 'rm-panel-extensions'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p style="margin-top: 10px; color: #666;">
                    <?php printf(__('Showing %d responses (limited to 100 results)', 'rm-panel-extensions'), count($responses)); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <style>
            .status-badge, .completion-badge, .approval-badge {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }
            
            .status-completed { background: #d4edda; color: #155724; }
            .status-waiting_to_complete { background: #fff3cd; color: #856404; }
            .status-not_complete { background: #f8d7da; color: #721c24; }
            
            .completion-success { background: #d4edda; color: #155724; }
            .completion-quota_complete { background: #fff3cd; color: #856404; }
            .completion-disqualified { background: #f8d7da; color: #721c24; }
            
            .approval-pending { background: #fff3cd; color: #856404; }
            .approval-approved { background: #d4edda; color: #155724; }
            .approval-rejected { background: #f8d7da; color: #721c24; }
            
            .reset-single-btn .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
                vertical-align: middle;
                margin-right: 3px;
            }
        </style>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'rm_survey_page_rm-survey-reset') {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                // Select/Deselect all
                $("#select-all-checkbox").on("change", function() {
                    $(".response-checkbox").prop("checked", $(this).is(":checked"));
                    updateSelectedCount();
                });
                
                $("#select-all-responses").on("click", function() {
                    $(".response-checkbox").prop("checked", true);
                    $("#select-all-checkbox").prop("checked", true);
                    updateSelectedCount();
                });
                
                $("#deselect-all-responses").on("click", function() {
                    $(".response-checkbox").prop("checked", false);
                    $("#select-all-checkbox").prop("checked", false);
                    updateSelectedCount();
                });
                
                $(".response-checkbox").on("change", updateSelectedCount);
                
                function updateSelectedCount() {
                    var count = $(".response-checkbox:checked").length;
                    $("#selected-count").text(count + " selected");
                    $("#bulk-reset-selected").prop("disabled", count === 0);
                }
                
                // Reset single response
                $(".reset-single-btn").on("click", function() {
                    var $btn = $(this);
                    var responseId = $btn.data("response-id");
                    var userName = $btn.data("user-name");
                    var surveyTitle = $btn.data("survey-title");
                    
                    if (!confirm("Are you sure you want to reset this survey response?\\n\\nUser: " + userName + "\\nSurvey: " + surveyTitle + "\\n\\nThis will allow the user to retake the survey.")) {
                        return;
                    }
                    
                    var originalText = $btn.html();
                    $btn.prop("disabled", true).html("<span class=\"dashicons dashicons-update-alt spinning\"></span> Resetting...");
                    
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "rm_reset_survey_response",
                            response_id: responseId,
                            nonce: "' . wp_create_nonce('rm_reset_survey_nonce') . '"
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.data.message);
                                location.reload();
                            } else {
                                alert("Error: " + response.data.message);
                                $btn.prop("disabled", false).html(originalText);
                            }
                        },
                        error: function() {
                            alert("An error occurred. Please try again.");
                            $btn.prop("disabled", false).html(originalText);
                        }
                    });
                });
                
                // Bulk reset
                $("#bulk-reset-selected").on("click", function() {
                    var selectedIds = [];
                    $(".response-checkbox:checked").each(function() {
                        selectedIds.push($(this).val());
                    });
                    
                    if (selectedIds.length === 0) {
                        return;
                    }
                    
                    if (!confirm("Are you sure you want to reset " + selectedIds.length + " survey responses?\\n\\nThis will allow users to retake these surveys.")) {
                        return;
                    }
                    
                    var $btn = $(this);
                    var originalText = $btn.html();
                    $btn.prop("disabled", true).html("<span class=\"dashicons dashicons-update-alt spinning\"></span> Resetting...");
                    
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "rm_bulk_reset_responses",
                            response_ids: selectedIds,
                            nonce: "' . wp_create_nonce('rm_reset_survey_nonce') . '"
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.data.message);
                                location.reload();
                            } else {
                                alert("Error: " + response.data.message);
                                $btn.prop("disabled", false).html(originalText);
                            }
                        },
                        error: function() {
                            alert("An error occurred. Please try again.");
                            $btn.prop("disabled", false).html(originalText);
                        }
                    });
                });
            });
            
            // Add spinning animation
            $("<style>.spinning { animation: spin 1s linear infinite; } @keyframes spin { 100% { transform: rotate(360deg); } }</style>").appendTo("head");
        ');
    }
    
    /**
     * Reset single survey response
     */
    public function ajax_reset_single_response() {
        check_ajax_referer('rm_reset_survey_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'rm-panel-extensions')]);
        }
        
        $response_id = isset($_POST['response_id']) ? intval($_POST['response_id']) : 0;
        
        $result = $this->reset_survey_response($response_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => __('Survey response has been reset successfully.', 'rm-panel-extensions')
        ]);
    }
    
    /**
     * Bulk reset survey responses
     */
    public function ajax_bulk_reset_responses() {
        check_ajax_referer('rm_reset_survey_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'rm-panel-extensions')]);
        }
        
        $response_ids = isset($_POST['response_ids']) ? array_map('intval', $_POST['response_ids']) : [];
        
        if (empty($response_ids)) {
            wp_send_json_error(['message' => __('No responses selected', 'rm-panel-extensions')]);
        }
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($response_ids as $response_id) {
            $result = $this->reset_survey_response($response_id);
            if (is_wp_error($result)) {
                $error_count++;
            } else {
                $success_count++;
            }
        }
        
        $message = sprintf(
            __('Reset complete: %d succeeded, %d failed', 'rm-panel-extensions'),
            $success_count,
            $error_count
        );
        
        wp_send_json_success(['message' => $message]);
    }
    
    /**
     * Reset all surveys for a user
     */
    public function ajax_reset_user_all_surveys() {
        check_ajax_referer('rm_reset_survey_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'rm-panel-extensions')]);
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(['message' => __('Invalid user ID', 'rm-panel-extensions')]);
        }
        
        global $wpdb;
        
        $response_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE user_id = %d AND status = 'completed'",
            $user_id
        ));
        
        if (empty($response_ids)) {
            wp_send_json_error(['message' => __('No completed surveys found for this user', 'rm-panel-extensions')]);
        }
        
        $success_count = 0;
        foreach ($response_ids as $response_id) {
            $result = $this->reset_survey_response($response_id);
            if (!is_wp_error($result)) {
                $success_count++;
            }
        }
        
        wp_send_json_success([
            'message' => sprintf(__('%d survey responses have been reset', 'rm-panel-extensions'), $success_count)
        ]);
    }
    
    /**
     * Core reset function
     */
    private function reset_survey_response($response_id) {
        global $wpdb;
        
        // Get response details
        $response = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $response_id
        ));
        
        if (!$response) {
            return new WP_Error('not_found', __('Response not found', 'rm-panel-extensions'));
        }
        
        // Update response to not_complete
        $result = $wpdb->update(
            $this->table_name,
            [
                'status' => 'not_complete',
                'completion_status' => 'reset',
                'completion_time' => NULL,
                'return_time' => NULL,
                'approval_status' => NULL,
                'approved_by' => NULL,
                'approval_date' => NULL,
                'response_data' => NULL,
                'waiting_since' => NULL,
                'admin_notes' => '[RESET] ' . current_time('mysql') . ' - Previous: ' . ($response->admin_notes ?? '')
            ],
            ['id' => $response_id]
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Database error occurred', 'rm-panel-extensions'));
        }
        
        // Log the action
        do_action('rm_survey_response_reset', $response_id, $response->user_id, $response->survey_id);
        
        // If was approved and paid, log a warning (admin should handle refund manually)
        if ($response->approval_status === 'approved') {
            error_log(sprintf(
                'WARNING: Survey response #%d was reset after approval. Manual refund may be needed for user #%d, survey #%d',
                $response_id,
                $response->user_id,
                $response->survey_id
            ));
        }
        
        return true;
    }
    
    /**
     * Add reset buttons to approval page
     */
    public function add_reset_buttons_to_approval_page() {
        $screen = get_current_screen();
        
        if ($screen && $screen->id === 'rm_survey_page_rm-survey-approvals') {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Add reset button to each row
                    $('table tbody tr[data-response-id]').each(function() {
                        var $row = $(this);
                        var responseId = $row.data('response-id');
                        var $actionsCell = $row.find('td:last');
                        
                        var $resetBtn = $('<button type="button" class="button button-small reset-from-approval" style="margin-left: 5px;">' +
                            '<span class="dashicons dashicons-update-alt" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span> ' +
                            'Reset</button>');
                        
                        $resetBtn.data('response-id', responseId);
                        $actionsCell.append($resetBtn);
                    });
                    
                    // Handle reset from approval page
                    $(document).on('click', '.reset-from-approval', function() {
                        var $btn = $(this);
                        var responseId = $btn.data('response-id');
                        
                        if (!confirm('Reset this survey response to not completed?\\n\\nThis will allow the user to retake the survey.')) {
                            return;
                        }
                        
                        var originalText = $btn.html();
                        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Resetting...');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'rm_reset_survey_response',
                                response_id: responseId,
                                nonce: '<?php echo wp_create_nonce('rm_reset_survey_nonce'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert('Error: ' + response.data.message);
                                    $btn.prop('disabled', false).html(originalText);
                                }
                            },
                            error: function() {
                                alert('An error occurred');
                                $btn.prop('disabled', false).html(originalText);
                            }
                        });
                    });
                });
            </script>
            <?php
        }
    }
    
    /**
     * Add bulk reset action
     */
    public function add_bulk_reset_action($actions) {
        $actions['reset_surveys'] = __('Reset to Not Completed', 'rm-panel-extensions');
        return $actions;
    }
    
    /**
     * Handle bulk reset action
     */
    public function handle_bulk_reset_action($redirect_to, $action, $post_ids) {
        if ($action !== 'reset_surveys') {
            return $redirect_to;
        }
        
        $count = 0;
        foreach ($post_ids as $post_id) {
            $result = $this->reset_survey_response($post_id);
            if (!is_wp_error($result)) {
                $count++;
            }
        }
        
        $redirect_to = add_query_arg('bulk_reset_count', $count, $redirect_to);
        return $redirect_to;
    }
}

// Initialize
new RM_Survey_Reset_Handler();