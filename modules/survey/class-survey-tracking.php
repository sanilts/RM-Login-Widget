<?php
/**
 * Survey Tracking System - Unified & Optimized
 * 
 * Features:
 * - Survey response tracking (started, waiting, completed, not_complete)
 * - Multiple completion statuses (success, quota_complete, disqualified)
 * - Approval workflow for paid surveys
 * - Auto-pause on quota full with manager notifications
 * - Reward/payment processing
 * - Country tracking with IP detection
 * 
 * @package RM_Panel_Extensions
 * @version 2.1.0
 */
if (!defined('ABSPATH')) {
    exit;
}

class RM_Panel_Survey_Tracking {

    /**
     * Database table name
     */
    private $table_name;

    /**
     * Status constants
     */
    const STATUS_STARTED = 'started';
    const STATUS_WAITING = 'waiting_to_complete';
    const STATUS_NOT_COMPLETE = 'not_complete';
    const STATUS_COMPLETED = 'completed';

    /**
     * Completion status constants
     */
    const STATUS_SUCCESS = 'success';
    const STATUS_QUOTA_COMPLETE = 'quota_complete';
    const STATUS_DISQUALIFIED = 'disqualified';
    const STATUS_ABANDONED = 'abandoned';

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'rm_survey_responses';
        $this->init();
    }

    /**
     * Initialize hooks
     */
    private function init() {
        // AJAX handlers
        add_action('wp_ajax_rm_start_survey', [$this, 'ajax_start_survey']);
        add_action('wp_ajax_nopriv_rm_start_survey', [$this, 'ajax_start_survey']);
        add_action('wp_ajax_rm_complete_survey', [$this, 'ajax_complete_survey']);
        add_action('wp_ajax_nopriv_rm_complete_survey', [$this, 'ajax_complete_survey']);

        // Callback endpoints
        add_action('init', [$this, 'add_survey_callback_endpoint']);
        add_action('template_redirect', [$this, 'handle_survey_callback']);

        // Shortcodes
        add_shortcode('rm_survey_history', [$this, 'render_survey_history']);

        // Admin columns
        add_filter('manage_rm_survey_posts_columns', [$this, 'add_response_column']);
        add_action('manage_rm_survey_posts_custom_column', [$this, 'render_response_column'], 10, 2);

        // Cron for checking waiting responses
        add_action('rm_check_waiting_responses', [$this, 'check_waiting_responses_cron']);

        if (!wp_next_scheduled('rm_check_waiting_responses')) {
            wp_schedule_event(time(), 'hourly', 'rm_check_waiting_responses');
        }

        // Callback processing hook for auto-pause
        add_action('rm_survey_callback_processed', [$this, 'handle_quotafull_autopause'], 10, 3);
    }

    // ============================================
    // CORE TRACKING METHODS
    // ============================================

    /**
     * Start survey tracking
     * 
     * @param int $user_id User ID
     * @param int $survey_id Survey ID
     * @return int|WP_Error Response ID or error
     */
    public function start_survey($user_id, $survey_id) {
        global $wpdb;

        // Check for existing response
        $existing = $this->get_user_survey_response($user_id, $survey_id);

        // Prevent duplicate completions unless allowed
        if ($existing && $existing->status === self::STATUS_COMPLETED) {
            $allow_multiple = get_post_meta($survey_id, '_rm_survey_allow_multiple', true);
            if (!$allow_multiple) {
                return new WP_Error('already_completed', __('You have already completed this survey.', 'rm-panel-extensions'));
            }
        }

        $data = [
            'user_id' => $user_id,
            'survey_id' => $survey_id,
            'status' => self::STATUS_WAITING,
            'start_time' => current_time('mysql'),
            'waiting_since' => current_time('mysql'),
            'country' => $this->get_user_country(),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer_url' => $_SERVER['HTTP_REFERER'] ?? ''
        ];

        if ($existing) {
            $wpdb->update($this->table_name, $data, ['id' => $existing->id]);
            $response_id = $existing->id;
        } else {
            $wpdb->insert($this->table_name, $data);
            $response_id = $wpdb->insert_id;
        }

        do_action('rm_survey_started_waiting', $user_id, $survey_id, $response_id);

        return $response_id;
    }

    /**
     * Complete survey with validation and approval workflow
     * 
     * @param int $user_id User ID
     * @param int $survey_id Survey ID
     * @param string $completion_status Completion status
     * @param mixed $response_data Optional response data
     * @return int|WP_Error Result or error
     */
    public function complete_survey($user_id, $survey_id, $completion_status, $response_data = null) {
        global $wpdb;

        // Validate completion status
        $valid_statuses = [
            self::STATUS_SUCCESS,
            self::STATUS_QUOTA_COMPLETE,
            self::STATUS_DISQUALIFIED
        ];

        if (!in_array($completion_status, $valid_statuses)) {
            return new WP_Error('invalid_status', __('Invalid completion status.', 'rm-panel-extensions'));
        }

        // Check if response exists
        $existing = $this->get_user_survey_response($user_id, $survey_id);

        if (!$existing) {
            // If no response exists, create one first
            $response_id = $this->start_survey($user_id, $survey_id);
            if (is_wp_error($response_id)) {
                return $response_id;
            }
        }

        // Determine approval status
        $survey_type = get_post_meta($survey_id, '_rm_survey_type', true);
        $approval_status = ($survey_type === 'paid' && $completion_status === self::STATUS_SUCCESS) ? 'pending' : 'auto_approved';

        $data = [
            'status' => self::STATUS_COMPLETED,
            'completion_status' => $completion_status,
            'completion_time' => current_time('mysql'),
            'return_time' => current_time('mysql'),
            'approval_status' => $approval_status,
            'response_data' => $response_data ? json_encode($response_data) : null,
            'waiting_since' => NULL // Clear waiting status
        ];

        $result = $wpdb->update(
                $this->table_name,
                $data,
                [
                    'user_id' => $user_id,
                    'survey_id' => $survey_id
                ]
        );

        if ($result !== false) {
            do_action('rm_survey_completed', $user_id, $survey_id, $completion_status, $response_data);

            // Handle quota full scenario
            if ($completion_status === self::STATUS_QUOTA_COMPLETE) {
                $this->handle_quota_full($survey_id);
            }

            // Process rewards if auto-approved AND successful
            if ($approval_status === 'auto_approved' && $completion_status === self::STATUS_SUCCESS) {
                $this->process_survey_rewards($user_id, $survey_id, $completion_status);
            }

            // Log success for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                                'Survey completed: User %d, Survey %d, Status: %s, Approval: %s',
                                $user_id, $survey_id, $completion_status, $approval_status
                ));
            }
        } else {
            error_log(sprintf(
                            'Survey completion failed: User %d, Survey %d, DB Error: %s',
                            $user_id, $survey_id, $wpdb->last_error
            ));
        }

        // TEMPORARY DEBUG - REMOVE AFTER TESTING
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $check = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$this->table_name} WHERE user_id = %d AND survey_id = %d",
                            $user_id, $survey_id
            ));
            error_log('Survey completion check: ' . print_r($check, true));
        }

        return $result;
    }

    // ============================================
    // APPROVAL WORKFLOW
    // ============================================

    /**
     * Approve survey response and process payment
     * 
     * @param int $response_id Response ID
     * @param string $admin_notes Admin notes
     * @return int|WP_Error Result or error
     */
    public function approve_survey_response($response_id, $admin_notes = '') {
        global $wpdb;

        $response = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$this->table_name} WHERE id = %d",
                        $response_id
        ));

        if (!$response) {
            return new WP_Error('not_found', __('Response not found.', 'rm-panel-extensions'));
        }

        if ($response->approval_status === 'approved') {
            return new WP_Error('already_approved', __('This response is already approved.', 'rm-panel-extensions'));
        }

        $data = [
            'approval_status' => 'approved',
            'approved_by' => get_current_user_id(),
            'approval_date' => current_time('mysql'),
            'admin_notes' => $admin_notes
        ];

        $result = $wpdb->update($this->table_name, $data, ['id' => $response_id]);

        if ($result !== false) {
            // Process payment
            $this->process_survey_rewards(
                    $response->user_id,
                    $response->survey_id,
                    $response->completion_status
            );

            do_action('rm_survey_approved', $response->user_id, $response->survey_id, $response_id);
            $this->send_approval_email($response);
        }

        return $result;
    }

    /**
     * Reject survey response
     * 
     * @param int $response_id Response ID
     * @param string $admin_notes Admin notes (required)
     * @return int|WP_Error Result or error
     */
    public function reject_survey_response($response_id, $admin_notes = '') {
        global $wpdb;

        if (empty($admin_notes)) {
            return new WP_Error('notes_required', __('Rejection reason is required.', 'rm-panel-extensions'));
        }

        $data = [
            'approval_status' => 'rejected',
            'approved_by' => get_current_user_id(),
            'approval_date' => current_time('mysql'),
            'admin_notes' => $admin_notes
        ];

        $result = $wpdb->update($this->table_name, $data, ['id' => $response_id]);

        if ($result !== false) {
            $response = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$this->table_name} WHERE id = %d",
                            $response_id
            ));

            do_action('rm_survey_rejected', $response->user_id, $response->survey_id, $response_id);
            $this->send_rejection_email($response);
        }

        return $result;
    }

    // ============================================
    // QUOTA MANAGEMENT
    // ============================================

    /**
     * Handle quota full scenario
     * 
     * @param int $survey_id Survey ID
     */
    private function handle_quota_full($survey_id) {
        $notify_enabled = get_post_meta($survey_id, '_rm_survey_notify_quotafull', true);

        if ($notify_enabled === '1') {
            $this->auto_pause_survey($survey_id, 'quota_full');
            $this->notify_survey_manager($survey_id, 'quota_full');
        }
    }

    /**
     * Auto-pause survey
     * 
     * @param int $survey_id Survey ID
     * @param string $reason Pause reason
     */
    private function auto_pause_survey($survey_id, $reason = '') {
        global $wpdb;

        update_post_meta($survey_id, '_rm_survey_status', 'paused');
        update_post_meta($survey_id, '_rm_survey_paused_reason', $reason);
        update_post_meta($survey_id, '_rm_survey_paused_at', current_time('mysql'));

        // Update response record
        $wpdb->query($wpdb->prepare(
                        "UPDATE {$this->table_name} 
             SET survey_paused_at = %s 
             WHERE survey_id = %d 
             AND completion_status = 'quota_complete' 
             ORDER BY completion_time DESC 
             LIMIT 1",
                        current_time('mysql'),
                        $survey_id
        ));

        do_action('rm_survey_auto_paused', $survey_id, $reason);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Survey #{$survey_id} auto-paused due to: {$reason}");
        }
    }

    /**
     * Notify survey manager
     * 
     * @param int $survey_id Survey ID
     * @param string $reason Notification reason
     */
    private function notify_survey_manager($survey_id, $reason) {
        $manager_id = get_post_meta($survey_id, '_rm_survey_manager_id', true);

        if (!$manager_id) {
            return;
        }

        $manager = get_userdata($manager_id);
        if (!$manager) {
            return;
        }

        $survey = get_post($survey_id);
        $survey_title = $survey ? $survey->post_title : "Survey #{$survey_id}";
        $stats = $this->get_survey_stats($survey_id);

        $subject = sprintf(
                __('[Action Required] Survey Quota Full: %s', 'rm-panel-extensions'),
                $survey_title
        );

        $message = sprintf(
                __('Hello %s,

The survey "%s" has reached its quota and has been automatically paused.

Survey Statistics:
- Total Participants: %d
- Completed: %d
- Successful: %d
- Quota Full: %d
- Disqualified: %d

The survey status has been changed to "Paused" to prevent new participants from starting.

Actions you can take:
1. Review the survey responses in the admin panel
2. Export the completed responses
3. If needed, increase the quota and reactivate the survey

View Survey: %s

---
This is an automated notification from RM Panel Extensions.', 'rm-panel-extensions'),
                $manager->display_name,
                $survey_title,
                $stats->total_participants,
                $stats->total_completed,
                $stats->successful,
                $stats->quota_complete,
                $stats->disqualified,
                admin_url('post.php?post=' . $survey_id . '&action=edit')
        );

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];

        $sent = wp_mail($manager->user_email, $subject, $message, $headers);

        if ($sent) {
            add_post_meta($survey_id, '_rm_survey_notification_sent', [
                'type' => 'quota_full',
                'recipient' => $manager->user_email,
                'sent_at' => current_time('mysql')
            ]);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Quota full notification sent to {$manager->user_email} for survey #{$survey_id}");
            }
        } else {
            error_log("Failed to send quota full notification for survey #{$survey_id}");
        }

        do_action('rm_survey_manager_notified', $survey_id, $manager_id, $reason);
    }

    /**
     * Hook for callback-based auto-pause
     */
    public function handle_quotafull_autopause($survey_id, $user_id, $status) {
        if ($status === 'quota_complete') {
            $this->handle_quota_full($survey_id);
        }
    }

    // ============================================
    // REWARD PROCESSING
    // ============================================

    /**
     * Process survey rewards based on completion status
     * 
     * @param int $user_id User ID
     * @param int $survey_id Survey ID
     * @param string $completion_status Completion status
     */
    private function process_survey_rewards($user_id, $survey_id, $completion_status) {
        $survey_type = get_post_meta($survey_id, '_rm_survey_type', true);

        if ($survey_type === 'paid' && $completion_status === self::STATUS_SUCCESS) {
            $amount = floatval(get_post_meta($survey_id, '_rm_survey_amount', true));

            if ($amount > 0) {
                // Add to withdrawable balance
                $current_balance = floatval(get_user_meta($user_id, 'rm_withdrawable_balance', true));
                update_user_meta($user_id, 'rm_withdrawable_balance', $current_balance + $amount);

                // Track total earnings
                $total_earned = floatval(get_user_meta($user_id, 'rm_total_earnings', true));
                update_user_meta($user_id, 'rm_total_earnings', $total_earned + $amount);

                // Store individual earning record
                add_user_meta($user_id, 'rm_survey_earning', [
                    'survey_id' => $survey_id,
                    'amount' => $amount,
                    'date' => current_time('mysql'),
                    'status' => 'approved'
                ]);

                do_action('rm_survey_reward_earned', $user_id, $survey_id, $amount);
            }
        }
    }

    // ============================================
    // CRON JOBS
    // ============================================

    /**
     * Cron job to check waiting responses
     */
    public function check_waiting_responses_cron() {
        global $wpdb;

        $timeout_hours = apply_filters('rm_survey_waiting_timeout_hours', 48);

        $waiting_responses = $wpdb->get_results($wpdb->prepare(
                        "SELECT id, user_id, survey_id, start_time, waiting_since
             FROM {$this->table_name}
             WHERE status = %s
             AND waiting_since < DATE_SUB(NOW(), INTERVAL %d HOUR)",
                        self::STATUS_WAITING,
                        $timeout_hours
        ));

        foreach ($waiting_responses as $response) {
            $wpdb->update(
                    $this->table_name,
                    [
                        'status' => self::STATUS_NOT_COMPLETE,
                        'completion_status' => 'abandoned',
                        'completion_time' => current_time('mysql'),
                        'waiting_since' => NULL
                    ],
                    ['id' => $response->id]
            );

            do_action('rm_survey_marked_not_complete', $response->user_id, $response->survey_id, $response->id);
        }

        if (count($waiting_responses) > 0 && defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                            'Marked %d surveys as not_complete after %d hour timeout',
                            count($waiting_responses),
                            $timeout_hours
            ));
        }
    }

    // ============================================
    // DATA RETRIEVAL METHODS
    // ============================================

    /**
     * Get user's survey response
     */
    public function get_user_survey_response($user_id, $survey_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
                                "SELECT * FROM {$this->table_name} WHERE user_id = %d AND survey_id = %d",
                                $user_id,
                                $survey_id
        ));
    }

    /**
     * Get all survey responses for a user
     */
    public function get_user_survey_history($user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'start_time',
            'order' => 'DESC',
            'status' => null
        ];

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT r.*, p.post_title as survey_title 
                  FROM {$this->table_name} r
                  LEFT JOIN {$wpdb->posts} p ON r.survey_id = p.ID
                  WHERE r.user_id = %d";

        $query_args = [$user_id];

        if ($args['status']) {
            $query .= " AND r.status = %s";
            $query_args[] = $args['status'];
        }

        $query .= " ORDER BY {$args['orderby']} {$args['order']}";
        $query .= " LIMIT %d OFFSET %d";
        $query_args[] = $args['limit'];
        $query_args[] = $args['offset'];

        return $wpdb->get_results($wpdb->prepare($query, ...$query_args));
    }

    /**
     * Get survey statistics
     */
    public function get_survey_stats($survey_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
                                "SELECT 
                COUNT(DISTINCT user_id) as total_participants,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as total_completed,
                COUNT(CASE WHEN completion_status = 'success' THEN 1 END) as successful,
                COUNT(CASE WHEN completion_status = 'quota_complete' THEN 1 END) as quota_complete,
                COUNT(CASE WHEN completion_status = 'disqualified' THEN 1 END) as disqualified,
                COUNT(CASE WHEN status = 'waiting_to_complete' THEN 1 END) as waiting,
                COUNT(CASE WHEN status = 'started' THEN 1 END) as in_progress
            FROM {$this->table_name}
            WHERE survey_id = %d",
                                $survey_id
        ));
    }

    /**
     * Get pending approvals count
     */
    public function get_pending_count() {
        global $wpdb;
        return $wpdb->get_var(
                        "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE approval_status = 'pending'"
        );
    }

    /**
     * Get available surveys for a user
     */
    public function get_available_surveys($user_id) {
        $current_date = current_time('Y-m-d');

        $meta_query = ['relation' => 'AND'];

        // Active surveys only
        $meta_query[] = [
            'key' => '_rm_survey_status',
            'value' => 'active',
            'compare' => '=',
        ];

        // Date range filter
        $meta_query[] = [
            'relation' => 'AND',
            [
                'relation' => 'OR',
                [
                    'key' => '_rm_survey_start_date',
                    'value' => $current_date,
                    'compare' => '<=',
                    'type' => 'DATE',
                ],
                [
                    'key' => '_rm_survey_start_date',
                    'compare' => 'NOT EXISTS',
                ],
            ],
            [
                'relation' => 'OR',
                [
                    'key' => '_rm_survey_end_date',
                    'value' => $current_date,
                    'compare' => '>=',
                    'type' => 'DATE',
                ],
                [
                    'key' => '_rm_survey_end_date',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ];

        $args = [
            'post_type' => 'rm_survey',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => $meta_query,
            'meta_key' => '_rm_survey_start_date',
            'orderby' => 'meta_value',
            'order' => 'DESC',
        ];

        $surveys = get_posts($args);
        $completed_ids = $this->get_user_completed_survey_ids($user_id);

        return array_filter($surveys, function ($survey) use ($completed_ids) {
            return !in_array($survey->ID, $completed_ids);
        });
    }

    /**
     * Get IDs of surveys user has completed
     */
    private function get_user_completed_survey_ids($user_id) {
        global $wpdb;

        $ids = $wpdb->get_col($wpdb->prepare(
                        "SELECT survey_id FROM {$this->table_name} 
             WHERE user_id = %d 
             AND status = 'completed'",
                        $user_id
        ));

        return array_map('intval', $ids);
    }

    // ============================================
    // AJAX HANDLERS
    // ============================================

    /**
     * AJAX handler to start survey
     */
    public function ajax_start_survey() {
        check_ajax_referer('rm_survey_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to take surveys.', 'rm-panel-extensions')]);
        }

        $survey_id = intval($_POST['survey_id']);
        $user_id = get_current_user_id();

        $result = $this->start_survey($user_id, $survey_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Survey started successfully.', 'rm-panel-extensions'),
            'response_id' => $result
        ]);
    }

    /**
     * AJAX handler to complete survey
     */
    public function ajax_complete_survey() {
        check_ajax_referer('rm_survey_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'rm-panel-extensions')]);
        }

        $survey_id = intval($_POST['survey_id']);
        $user_id = get_current_user_id();
        $completion_status = sanitize_text_field($_POST['completion_status']);
        $response_data = isset($_POST['response_data']) ? $_POST['response_data'] : null;

        $result = $this->complete_survey($user_id, $survey_id, $completion_status, $response_data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Survey completed successfully.', 'rm-panel-extensions')
        ]);
    }

    // ============================================
    // CALLBACK HANDLING
    // ============================================

    /**
     * Add survey callback endpoint
     */
    public function add_survey_callback_endpoint() {
        add_rewrite_rule(
                '^survey-callback/?$',
                'index.php?rm_survey_callback=1',
                'top'
        );

        add_rewrite_tag('%rm_survey_callback%', '([^&]+)');
    }

    /**
     * Handle survey callback from external platform
     */
    public function handle_survey_callback() {
        if (!get_query_var('rm_survey_callback')) {
            return;
        }

        $user_id = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
        $survey_id = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

        // Verify token
        $expected_token = wp_hash($user_id . '-' . $survey_id . '-' . wp_salt('auth'));

        if ($token !== $expected_token) {
            wp_die(__('Invalid callback token.', 'rm-panel-extensions'));
        }

        // Map status
        $status_map = [
            'complete' => self::STATUS_SUCCESS,
            'quotafull' => self::STATUS_QUOTA_COMPLETE,
            'screenout' => self::STATUS_DISQUALIFIED,
            'success' => self::STATUS_SUCCESS
        ];

        $completion_status = $status_map[$status] ?? self::STATUS_DISQUALIFIED;

        // Complete survey
        $this->complete_survey($user_id, $survey_id, $completion_status);

        // Redirect to thank you page
        $redirect_url = add_query_arg([
            'survey_id' => $survey_id,
            'status' => $completion_status
                ], home_url('/survey-thank-you/'));

        wp_redirect($redirect_url);
        exit;
    }

    // ============================================
    // EMAIL NOTIFICATIONS
    // ============================================

    /**
     * Send approval email
     */
    private function send_approval_email($response) {
        $user = get_userdata($response->user_id);
        $survey = get_post($response->survey_id);
        $amount = get_post_meta($response->survey_id, '_rm_survey_amount', true);

        $subject = sprintf(__('Survey Approved: %s', 'rm-panel-extensions'), $survey->post_title);

        $message = sprintf(
                __('Congratulations! Your survey response has been approved.

Survey: %s
Amount: $%s
Approval Date: %s

The amount has been added to your withdrawable balance.

Thank you for your participation!', 'rm-panel-extensions'),
                $survey->post_title,
                number_format($amount, 2),
                date_i18n(get_option('date_format'), strtotime($response->approval_date))
        );

        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Send rejection email
     */
    private function send_rejection_email($response) {
        $user = get_userdata($response->user_id);
        $survey = get_post($response->survey_id);

        $subject = sprintf(__('Survey Response Update: %s', 'rm-panel-extensions'), $survey->post_title);

        $message = sprintf(
                __('Your survey response has been reviewed.

Survey: %s
Status: Not Approved

%s

If you have questions, please contact support.', 'rm-panel-extensions'),
                $survey->post_title,
                $response->admin_notes ? "Admin Notes: " . $response->admin_notes : ''
        );

        wp_mail($user->user_email, $subject, $message);
    }

    // ============================================
    // UTILITY METHODS
    // ============================================

    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                $ips = explode(',', $_SERVER[$key]);
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get user's country from IP
     */
    private function get_user_country() {
        $ip = $this->get_user_ip();

        // Try ipstack API
        $api_key = get_option('rm_panel_ipstack_api_key', '');
        if ($api_key) {
            $response = wp_remote_get("http://api.ipstack.com/{$ip}?access_key={$api_key}");
            if (!is_wp_error($response)) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($data['country_name'])) {
                    return $data['country_name'];
                }
            }
        }

        // Fallback to CloudFlare header
        if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return $_SERVER['HTTP_CF_IPCOUNTRY'];
        }

        return 'Unknown';
    }

    /**
     * Get status label
     */
    public static function get_status_label($status) {
        $labels = [
            self::STATUS_STARTED => __('Started', 'rm-panel-extensions'),
            self::STATUS_WAITING => __('Waiting to Complete', 'rm-panel-extensions'),
            self::STATUS_NOT_COMPLETE => __('Not Complete', 'rm-panel-extensions'),
            self::STATUS_COMPLETED => __('Completed', 'rm-panel-extensions'),
            self::STATUS_SUCCESS => __('Successful', 'rm-panel-extensions'),
            self::STATUS_QUOTA_COMPLETE => __('Quota Full', 'rm-panel-extensions'),
            self::STATUS_DISQUALIFIED => __('Disqualified', 'rm-panel-extensions'),
            self::STATUS_ABANDONED => __('Abandoned', 'rm-panel-extensions')
        ];

        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    // ============================================
    // FRONTEND DISPLAY
    // ============================================

    /**
     * Render survey history shortcode
     */
    public function render_survey_history($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your survey history.', 'rm-panel-extensions') . '</p>';
        }

        $atts = shortcode_atts([
            'limit' => 10,
            'show_earnings' => 'yes'
                ], $atts);

        $user_id = get_current_user_id();
        $history = $this->get_user_survey_history($user_id, ['limit' => $atts['limit']]);

        ob_start();
        ?>
        <div class="rm-survey-history">
            <h3><?php _e('Your Survey History', 'rm-panel-extensions'); ?></h3>

        <?php if ($atts['show_earnings'] === 'yes') : ?>
            <?php $total_earned = get_user_meta($user_id, 'rm_total_earnings', true) ?: 0; ?>
                <div class="survey-earnings-summary">
                    <p><strong><?php _e('Total Earned:', 'rm-panel-extensions'); ?></strong> $<?php echo number_format($total_earned, 2); ?></p>
                </div>
        <?php endif; ?>

            <?php if (empty($history)) : ?>
                <p><?php _e('You have not completed any surveys yet.', 'rm-panel-extensions'); ?></p>
            <?php else : ?>
                <table class="survey-history-table">
                    <thead>
                        <tr>
                            <th><?php _e('Survey', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Date', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Status', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Result', 'rm-panel-extensions'); ?></th>
            <?php if ($atts['show_earnings'] === 'yes') : ?>
                                <th><?php _e('Earned', 'rm-panel-extensions'); ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
            <?php foreach ($history as $response) : ?>
                <?php
                $survey_type = get_post_meta($response->survey_id, '_rm_survey_type', true);
                $survey_amount = get_post_meta($response->survey_id, '_rm_survey_amount', true);
                ?>
                            <tr>
                                <td><?php echo esc_html($response->survey_title); ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($response->start_time)); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($response->status); ?>">
                <?php echo esc_html(self::get_status_label($response->status)); ?>
                                    </span>
                                </td>
                                <td>
                <?php if ($response->completion_status) : ?>
                                        <span class="completion-status <?php echo esc_attr($response->completion_status); ?>">
                                        <?php echo self::get_status_label($response->completion_status); ?>
                                        </span>
                                        <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                    <?php if ($atts['show_earnings'] === 'yes') : ?>
                                    <td>
                                    <?php if ($survey_type === 'paid' && $response->completion_status === self::STATUS_SUCCESS) : ?>
                                            $<?php echo number_format($survey_amount, 2); ?>
                                        <?php else : ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                    </tbody>
                </table>
                    <?php endif; ?>
        </div>
            <?php
            return ob_get_clean();
        }

        /**
         * Add response count column
         */
        public function add_response_column($columns) {
            $columns['responses'] = __('Responses', 'rm-panel-extensions');
            return $columns;
        }

        /**
         * Render response count column
         */
        public function render_response_column($column, $post_id) {
            if ($column === 'responses') {
                $stats = $this->get_survey_stats($post_id);
                echo sprintf(
                        '<span title="%s">%d / %d</span>',
                        sprintf(
                                __('Success: %d, Quota: %d, Disqualified: %d, Waiting: %d', 'rm-panel-extensions'),
                                $stats->successful,
                                $stats->quota_complete,
                                $stats->disqualified,
                                $stats->waiting
                        ),
                        $stats->total_completed,
                        $stats->total_participants
                );
            }
        }
    }

// Initialize
    new RM_Panel_Survey_Tracking();
    