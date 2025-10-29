<?php
/**
 * Survey Tracking Class
 * Handles user survey response tracking
 */
if (!defined('ABSPATH')) {
    exit;
}

class RM_Panel_Survey_Tracking {

    /**
     * Table name
     */
    private $table_name;

    /**
     * Completion status constants
     */
    const STATUS_STARTED = 'started';
    const STATUS_COMPLETED = 'completed';
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
        // Add AJAX handlers for survey tracking
        add_action('wp_ajax_rm_start_survey', [$this, 'ajax_start_survey']);
        add_action('wp_ajax_nopriv_rm_start_survey', [$this, 'ajax_start_survey']);

        add_action('wp_ajax_rm_complete_survey', [$this, 'ajax_complete_survey']);
        add_action('wp_ajax_nopriv_rm_complete_survey', [$this, 'ajax_complete_survey']);

        // Add endpoint for survey callback
        add_action('init', [$this, 'add_survey_callback_endpoint']);
        add_action('template_redirect', [$this, 'handle_survey_callback']);

        // Add shortcode for displaying user survey history
        add_shortcode('rm_survey_history', [$this, 'render_survey_history']);

        // Admin columns for user survey stats
        add_filter('manage_rm_survey_posts_columns', [$this, 'add_response_column']);
        add_action('manage_rm_survey_posts_custom_column', [$this, 'render_response_column'], 10, 2);
    }

    /**
     * Process rewards based on completion status
     */
    private function process_survey_rewards($user_id, $survey_id, $completion_status) {
        $survey_type = get_post_meta($survey_id, '_rm_survey_type', true);

        if ($survey_type === 'paid' && $completion_status === self::STATUS_SUCCESS) {
            $amount = get_post_meta($survey_id, '_rm_survey_amount', true);

            if ($amount) {
                // Store earned amount as user meta
                $total_earned = get_user_meta($user_id, 'rm_survey_total_earned', true) ?: 0;
                $total_earned += $amount;
                update_user_meta($user_id, 'rm_survey_total_earned', $total_earned);

                // Store individual earning record
                add_user_meta($user_id, 'rm_survey_earning', [
                    'survey_id' => $survey_id,
                    'amount' => $amount,
                    'date' => current_time('mysql'),
                    'status' => 'pending' // Can be: pending, approved, paid
                ]);

                // Trigger action for payment processing
                do_action('rm_survey_reward_earned', $user_id, $survey_id, $amount);
            }
        }
    }

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

        $stats = $wpdb->get_row($wpdb->prepare(
                        "SELECT 
                COUNT(DISTINCT user_id) as total_participants,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as total_completed,
                COUNT(CASE WHEN completion_status = 'success' THEN 1 END) as successful,
                COUNT(CASE WHEN completion_status = 'quota_complete' THEN 1 END) as quota_complete,
                COUNT(CASE WHEN completion_status = 'disqualified' THEN 1 END) as disqualified,
                COUNT(CASE WHEN status = 'started' THEN 1 END) as in_progress
            FROM {$this->table_name}
            WHERE survey_id = %d",
                        $survey_id
        ));

        return $stats;
    }

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
     * Handle survey callback from external survey platform
     * Update this in class-survey-tracking.php to match the new token format
     */
    public function handle_survey_callback() {
        if (!get_query_var('rm_survey_callback')) {
            return;
        }

        // Get parameters from callback URL
        $user_id = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
        $survey_id = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

        // Verify token for security (using the same stable format)
        $expected_token = wp_hash($user_id . '-' . $survey_id . '-' . wp_salt('auth'));

        if ($token !== $expected_token) {
            wp_die(__('Invalid callback token.', 'rm-panel-extensions'));
        }

        // Map external status to our status
        $status_map = [
            'complete' => self::STATUS_SUCCESS,
            'quotafull' => self::STATUS_QUOTA_COMPLETE,
            'screenout' => self::STATUS_DISQUALIFIED,
            'success' => self::STATUS_SUCCESS
        ];

        $completion_status = isset($status_map[$status]) ? $status_map[$status] : self::STATUS_DISQUALIFIED;

        // Complete the survey
        $this->complete_survey($user_id, $survey_id, $completion_status);

        // Redirect to thank you page
        $redirect_url = home_url('/survey-thank-you/');
        $redirect_url = add_query_arg([
            'survey_id' => $survey_id,
            'status' => $completion_status
                ], $redirect_url);

        wp_redirect($redirect_url);
        exit;
    }

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
                <?php $total_earned = get_user_meta($user_id, 'rm_survey_total_earned', true) ?: 0; ?>
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
                                        <?php echo esc_html(ucfirst($response->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($response->completion_status) : ?>
                                        <span class="completion-status <?php echo esc_attr($response->completion_status); ?>">
                                            <?php echo $this->get_status_label($response->completion_status); ?>
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
     * Get readable status label
     */
    private function get_status_label($status) {
        $labels = [
            self::STATUS_SUCCESS => __('Successful', 'rm-panel-extensions'),
            self::STATUS_QUOTA_COMPLETE => __('Quota Full', 'rm-panel-extensions'),
            self::STATUS_DISQUALIFIED => __('Disqualified', 'rm-panel-extensions'),
            self::STATUS_STARTED => __('In Progress', 'rm-panel-extensions'),
            self::STATUS_ABANDONED => __('Abandoned', 'rm-panel-extensions')
        ];

        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    /**
     * Add response count column to survey list
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
                            __('Success: %d, Quota: %d, Disqualified: %d', 'rm-panel-extensions'),
                            $stats->successful,
                            $stats->quota_complete,
                            $stats->disqualified
                    ),
                    $stats->total_completed,
                    $stats->total_participants
            );
        }
    }

    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ips = explode(',', $_SERVER[$key]);
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get user's country from IP using ipstack (free tier)
     */
    private function get_user_country() {
        $ip = $this->get_user_ip();

        // First try to get from ipstack (requires API key)
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

        // Fallback: Try to get from CloudFlare header
        if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return $_SERVER['HTTP_CF_IPCOUNTRY'];
        }

        // Default
        return 'Unknown';
    }

    /**
     * Enhanced start_survey method with country tracking
     */
    public function start_survey($user_id, $survey_id) {
        global $wpdb;

        $existing = $this->get_user_survey_response($user_id, $survey_id);

        if ($existing && $existing->status === 'completed') {
            $allow_multiple = get_post_meta($survey_id, '_rm_survey_allow_multiple', true);
            if (!$allow_multiple) {
                return new WP_Error('already_completed', __('You have already completed this survey.', 'rm-panel-extensions'));
            }
        }

        $data = [
            'user_id' => $user_id,
            'survey_id' => $survey_id,
            'status' => self::STATUS_STARTED,
            'start_time' => current_time('mysql'),
            'country' => $this->get_user_country(),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer_url' => $_SERVER['HTTP_REFERER'] ?? ''
        ];

        if ($existing) {
            $wpdb->update($this->table_name, $data, ['id' => $existing->id]);
            return $existing->id;
        } else {
            $wpdb->insert($this->table_name, $data);
            return $wpdb->insert_id;
        }
    }

    /**
     * Enhanced complete_survey with return time tracking
     */
    public function complete_survey($user_id, $survey_id, $completion_status, $response_data = null) {
        global $wpdb;

        $valid_statuses = [
            self::STATUS_SUCCESS,
            self::STATUS_QUOTA_COMPLETE,
            self::STATUS_DISQUALIFIED
        ];

        if (!in_array($completion_status, $valid_statuses)) {
            return new WP_Error('invalid_status', __('Invalid completion status.', 'rm-panel-extensions'));
        }

        // Check if it's a paid survey
        $survey_type = get_post_meta($survey_id, '_rm_survey_type', true);
        $approval_status = ($survey_type === 'paid' && $completion_status === self::STATUS_SUCCESS) ? 'pending' : 'auto_approved';

        $data = [
            'status' => self::STATUS_COMPLETED,
            'completion_status' => $completion_status,
            'completion_time' => current_time('mysql'),
            'return_time' => current_time('mysql'),
            'approval_status' => $approval_status,
            'response_data' => $response_data ? json_encode($response_data) : null
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

            // Only process rewards if auto-approved
            if ($approval_status === 'auto_approved') {
                $this->process_survey_rewards($user_id, $survey_id, $completion_status);
            }
        }

        return $result;
    }

    /**
     * Approve survey response and process payment
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

            // Send notification to user
            do_action('rm_survey_approved', $response->user_id, $response->survey_id, $response_id);

            // Send email notification
            $this->send_approval_email($response);
        }

        return $result;
    }

    /**
     * Reject survey response
     */
    public function reject_survey_response($response_id, $admin_notes = '') {
        global $wpdb;

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

The amount has been added to your account.

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

        // Initialize meta_query
        $meta_query = ['relation' => 'AND'];

        // Status filter - only active surveys
        $meta_query[] = [
            'key' => '_rm_survey_status',
            'value' => 'active',
            'compare' => '=',
        ];

        // Date range filter
        $meta_query[] = [
            'relation' => 'AND',
            // Start date condition
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
                [
                    'key' => '_rm_survey_start_date',
                    'value' => '',
                    'compare' => '=',
                ],
            ],
            // End date condition
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
                [
                    'key' => '_rm_survey_end_date',
                    'value' => '',
                    'compare' => '=',
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

        // Get all surveys
        $surveys = get_posts($args);

        // Filter out surveys user has already completed
        $completed_survey_ids = $this->get_user_completed_survey_ids($user_id);

        return array_filter($surveys, function ($survey) use ($completed_survey_ids) {
            return !in_array($survey->ID, $completed_survey_ids);
        });
    }

    /**
     * Get IDs of surveys user has completed
     */
    private function get_user_completed_survey_ids($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';

        $completed_ids = $wpdb->get_col($wpdb->prepare(
                        "SELECT survey_id FROM $table_name 
        WHERE user_id = %d 
        AND status = 'completed'",
                        $user_id
        ));

        return array_map('intval', $completed_ids);
    }
}
