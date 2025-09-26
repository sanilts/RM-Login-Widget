<?php
/**
 * Survey Callback URLs Handler
 * Generates and processes callback URLs for external survey platforms
 * 
 * File: modules/survey/class-survey-callbacks.php
 */
if (!defined('ABSPATH')) {
    exit;
}

class RM_Survey_Callbacks {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize hooks
     */
    private function init() {
        // Register callback endpoints
        add_action('init', [$this, 'register_callback_endpoints']);

        // Handle callback requests
        add_action('template_redirect', [$this, 'handle_callback_request']);

        // Add meta box for callback URLs
        add_action('add_meta_boxes', [$this, 'add_callback_urls_metabox']);

        // Add AJAX handler for copying URLs
        add_action('wp_ajax_copy_callback_urls', [$this, 'ajax_get_callback_urls']);

        // Admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Register callback endpoints
     */
    public function register_callback_endpoints() {
        // Success callback
        add_rewrite_rule(
                '^survey-callback/success/?$',
                'index.php?rm_callback=success',
                'top'
        );

        // Terminate callback
        add_rewrite_rule(
                '^survey-callback/terminate/?$',
                'index.php?rm_callback=terminate',
                'top'
        );

        // Quota full callback
        add_rewrite_rule(
                '^survey-callback/quotafull/?$',
                'index.php?rm_callback=quotafull',
                'top'
        );

        // Add query vars
        add_rewrite_tag('%rm_callback%', '([^&]+)');
    }

    /**
     * Generate callback URLs for a survey
     */
    public function generate_callback_urls($survey_id, $user_id = null) {
        // If no user ID provided, use current user
        if (!$user_id) {
            $user_id = '{USER_ID}'; // Placeholder for dynamic replacement
        }

        // Generate secure token
        $base_token = $this->generate_token($survey_id, $user_id);

        // Base parameters
        $base_params = [
            'sid' => $survey_id,
            'uid' => $user_id,
        ];

        // Generate three callback URLs
        $urls = [
            'success' => $this->build_callback_url('success', $survey_id, $user_id, $base_token),
            'terminate' => $this->build_callback_url('terminate', $survey_id, $user_id, $base_token),
            'quotafull' => $this->build_callback_url('quotafull', $survey_id, $user_id, $base_token),
        ];

        return $urls;
    }

    /**
     * Build a callback URL
     */
    private function build_callback_url($status, $survey_id, $user_id, $token) {
        $base_url = home_url('/survey-callback/' . $status . '/');

        $params = [
            'sid' => $survey_id,
            'uid' => $user_id,
            'token' => $token,
            'timestamp' => '{TIMESTAMP}', // Placeholder for dynamic timestamp
        ];

        return add_query_arg($params, $base_url);
    }

    /**
     * Generate secure token
     */
    private function generate_token($survey_id, $user_id) {
        // Create a secure token using survey ID, user ID, and WordPress salt
        $data = $survey_id . '|' . $user_id . '|' . wp_salt('auth');
        return hash('sha256', $data);
    }

    /**
     * Verify token
     */
    private function verify_token($survey_id, $user_id, $provided_token) {
        $expected_token = $this->generate_token($survey_id, $user_id);
        return hash_equals($expected_token, $provided_token);
    }

    /**
     * Handle callback request
     */
    public function handle_callback_request() {
        $callback_type = get_query_var('rm_callback');

        if (!$callback_type) {
            return;
        }

        // Get parameters
        $survey_id = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
        $user_id = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        $timestamp = isset($_GET['timestamp']) ? sanitize_text_field($_GET['timestamp']) : current_time('timestamp');

        // Log the callback for debugging
        $this->log_callback($callback_type, $survey_id, $user_id, $_GET);

        // Verify token
        if (!$this->verify_token($survey_id, $user_id, $token)) {
            wp_die('Invalid callback token. This request cannot be processed.', 'Security Error', ['response' => 403]);
        }

        // Map callback type to internal status
        $status_map = [
            'success' => 'success',
            'terminate' => 'disqualified',
            'quotafull' => 'quota_complete'
        ];

        $internal_status = $status_map[$callback_type];

        // Process the callback
        $this->process_callback($survey_id, $user_id, $internal_status);

        // Redirect to thank you page
        $this->redirect_to_thank_you($survey_id, $internal_status);
    }

    /**
     * Add callback URLs meta box
     */
    public function add_callback_urls_metabox() {
        add_meta_box(
                'rm_survey_callback_urls', // ID
                __('Survey Callback URLs', 'rm-panel-extensions'), // Title
                [$this, 'render_callback_urls_metabox'], // Callback
                'rm_survey', // Post type (Survey)
                'normal', // Position (sidebar)
                'high'                                // Priority (top of sidebar)
        );
    }

    /**
     * Render callback URLs meta box
     */
    public function render_callback_urls_metabox($post) {
        $survey_id = $post->ID;

        // URLS ARE GENERATED HERE!
        $urls = $this->generate_callback_urls($survey_id);
        ?>
        <div class="rm-callback-urls-box">
            <p class="description">
        <?php _e('Share these URLs with your survey platform.', 'rm-panel-extensions'); ?>
            </p>

            <!-- SUCCESS URL -->
            <div class="callback-url-group">
                <label><?php _e('Success URL:', 'rm-panel-extensions'); ?></label>
                <div class="url-field-wrapper">
                    <input type="text" 
                           id="callback_url_success" 
                           value="<?php echo esc_url($urls['success']); ?>" 
                           readonly 
                           class="widefat callback-url-field" />
                    <button type="button" 
                            class="button copy-url-btn" 
                            data-clipboard-target="#callback_url_success">
        <?php _e('Copy', 'rm-panel-extensions'); ?>
                    </button>
                </div>
            </div>

            <!-- TERMINATE URL -->
            <div class="callback-url-group">
                <label><?php _e('Terminate URL:', 'rm-panel-extensions'); ?></label>
                <div class="url-field-wrapper">
                    <input type="text" 
                           id="callback_url_terminate" 
                           value="<?php echo esc_url($urls['terminate']); ?>" 
                           readonly 
                           class="widefat callback-url-field" />
                    <button type="button" 
                            class="button copy-url-btn">
        <?php _e('Copy', 'rm-panel-extensions'); ?>
                    </button>
                </div>
            </div>

            <!-- QUOTA FULL URL -->
            <div class="callback-url-group">
                <label><?php _e('Quota Full URL:', 'rm-panel-extensions'); ?></label>
                <div class="url-field-wrapper">
                    <input type="text" 
                           id="callback_url_quotafull" 
                           value="<?php echo esc_url($urls['quotafull']); ?>" 
                           readonly 
                           class="widefat callback-url-field" />
                    <button type="button" 
                            class="button copy-url-btn">
        <?php _e('Copy', 'rm-panel-extensions'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Process callback and update database
     */
    private function process_callback($survey_id, $user_id, $status) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'rm_survey_responses';

        // Check if tracking class exists
        if (class_exists('RM_Panel_Survey_Tracking')) {
            $tracker = new RM_Panel_Survey_Tracking();
            $tracker->complete_survey($user_id, $survey_id, $status);
        } else {
            // Fallback: Direct database update
            $data = [
                'status' => 'completed',
                'completion_status' => $status,
                'completion_time' => current_time('mysql')
            ];

            $where = [
                'user_id' => $user_id,
                'survey_id' => $survey_id
            ];

            $wpdb->update($table_name, $data, $where);
        }

        // Trigger action for other plugins
        do_action('rm_survey_callback_processed', $survey_id, $user_id, $status);
    }

    /**
     * Redirect to thank you page
     */
    private function redirect_to_thank_you($survey_id, $status) {
        // Build thank you page URL
        $thank_you_url = home_url('/survey-thank-you/');

        // Add parameters
        $thank_you_url = add_query_arg([
            'survey_id' => $survey_id,
            'status' => $status,
            'completed' => current_time('timestamp')
                ], $thank_you_url);

        // Allow filtering
        $thank_you_url = apply_filters('rm_survey_thank_you_url', $thank_you_url, $survey_id, $status);

        // Redirect
        wp_redirect($thank_you_url);
        exit;
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        global $post_type;
        if ('rm_survey' !== $post_type) {
            return;
        }

        // Enqueue clipboard.js from CDN
        wp_enqueue_script(
                'clipboard-js',
                'https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.11/clipboard.min.js',
                [],
                '2.0.11',
                true
        );

        // Enqueue custom script
        wp_enqueue_script(
                'rm-survey-callback-admin',
                RM_PANEL_EXT_PLUGIN_URL . 'assets/js/survey-callback-admin.js',
                ['jquery', 'clipboard-js'],
                RM_PANEL_EXT_VERSION,
                true
        );

        // Localize script
        wp_localize_script('rm-survey-callback-admin', 'rm_callback_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rm_callback_nonce'),
            'current_user_id' => get_current_user_id(),
            'strings' => [
                'copied' => __('Copied!', 'rm-panel-extensions'),
                'copy' => __('Copy', 'rm-panel-extensions'),
                'error' => __('Error generating URLs', 'rm-panel-extensions')
            ]
        ]);
    }

    /**
     * AJAX handler to get callback URLs
     */
    public function ajax_get_callback_urls() {
        check_ajax_referer('rm_callback_nonce', 'nonce');

        $survey_id = intval($_POST['survey_id']);
        $user_id = intval($_POST['user_id']);

        if (!$survey_id || !$user_id) {
            wp_send_json_error('Invalid parameters');
        }

        // Generate actual URLs with real user ID
        $urls = $this->generate_user_specific_urls($survey_id, $user_id);

        wp_send_json_success($urls);
    }

    /**
     * Generate user-specific URLs
     */
    private function generate_user_specific_urls($survey_id, $user_id) {
        $token = $this->generate_token($survey_id, $user_id);
        $timestamp = current_time('timestamp');

        $urls = [
            'success' => home_url("/survey-callback/success/?sid=$survey_id&uid=$user_id&token=$token&timestamp=$timestamp"),
            'terminate' => home_url("/survey-callback/terminate/?sid=$survey_id&uid=$user_id&token=$token&timestamp=$timestamp"),
            'quotafull' => home_url("/survey-callback/quotafull/?sid=$survey_id&uid=$user_id&token=$token&timestamp=$timestamp"),
        ];

        return $urls;
    }

    /**
     * Log callback for debugging
     */
    private function log_callback($type, $survey_id, $user_id, $params) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_entry = sprintf(
                    '[%s] Survey Callback - Type: %s, Survey: %d, User: %d, Params: %s',
                    current_time('Y-m-d H:i:s'),
                    $type,
                    $survey_id,
                    $user_id,
                    json_encode($params)
            );

            error_log($log_entry);
        }
    }

    /**
     * Get callback statistics
     */
    public function get_callback_stats($survey_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';

        $query = "SELECT 
                    completion_status, 
                    COUNT(*) as count 
                  FROM $table_name 
                  WHERE status = 'completed'";

        if ($survey_id) {
            $query .= $wpdb->prepare(" AND survey_id = %d", $survey_id);
        }

        $query .= " GROUP BY completion_status";

        return $wpdb->get_results($query, OBJECT_K);
    }
}

// Initialize the callback handler
new RM_Survey_Callbacks();
