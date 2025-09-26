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
        
        // Add test callback handler
        add_action('wp_ajax_test_callback_url', [$this, 'ajax_test_callback_url']);
        
        // REMOVED: Admin menu hooks - No longer needed
        // add_action('admin_menu', [$this, 'add_test_page']);
        
        // Check and fix rewrite rules if needed
        add_action('init', [$this, 'check_and_fix_rewrite_rules'], 999);
        
        // Add fallback handler for callback URLs
        add_action('template_redirect', [$this, 'fallback_callback_handler'], 1);
        
        // Add admin notices for debugging (only when needed)
        // add_action('admin_notices', [$this, 'show_rewrite_debug']);
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
     * NO TIMESTAMP - Clean URLs
     */
    public function generate_callback_urls($survey_id, $user_id = null) {
        // If no user ID provided, use placeholder
        if (!$user_id) {
            $user_id = '{USER_ID}';
        }

        // Generate secure token
        $base_token = $this->generate_token($survey_id, $user_id);

        // Generate three callback URLs WITHOUT timestamp
        $urls = [
            'success' => $this->build_callback_url('success', $survey_id, $user_id, $base_token),
            'terminate' => $this->build_callback_url('terminate', $survey_id, $user_id, $base_token),
            'quotafull' => $this->build_callback_url('quotafull', $survey_id, $user_id, $base_token),
        ];

        return $urls;
    }

    /**
     * Build a callback URL WITHOUT timestamp
     */
    private function build_callback_url($status, $survey_id, $user_id, $token) {
        $base_url = home_url('/survey-callback/' . $status . '/');

        $params = [
            'sid' => $survey_id,
            'uid' => $user_id,
            'token' => $token,
            // NO timestamp parameter
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
     * Check and fix rewrite rules if needed
     */
    public function check_and_fix_rewrite_rules() {
        // Only check on admin pages to avoid performance impact
        if (!is_admin()) {
            return;
        }
        
        $rules = get_option('rewrite_rules');
        
        // Check if our rules exist
        if (!$rules || !isset($rules['^survey-callback/success/?$'])) {
            // Rules are missing, register them
            $this->register_callback_endpoints();
            
            // Set a transient to flush rules on next page load
            set_transient('rm_survey_flush_rules', true, 60);
        }
        
        // Check if we need to flush
        if (get_transient('rm_survey_flush_rules')) {
            flush_rewrite_rules();
            delete_transient('rm_survey_flush_rules');
        }
    }
    
    /**
     * Fallback handler for callback URLs (works even if rewrite rules fail)
     */
    public function fallback_callback_handler() {
        // Only process if main handler didn't catch it
        if (get_query_var('rm_callback')) {
            return; // Main handler will process this
        }
        
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Check if this is a survey callback URL
        if (strpos($request_uri, '/survey-callback/') !== false) {
            if (preg_match('/survey-callback\/(success|terminate|quotafull)/', $request_uri, $matches)) {
                $callback_type = $matches[1];
                
                // Get parameters
                $survey_id = isset($_GET['sid']) ? intval($_GET['sid']) : 0;
                $user_id = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
                $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
                $test_mode = isset($_GET['test_mode']) ? true : false;
                
                if (!$survey_id || !$user_id || !$token) {
                    wp_die('Missing required parameters', 'Error', ['response' => 400]);
                }
                
                // Verify token
                if (!$this->verify_token($survey_id, $user_id, $token)) {
                    wp_die('Invalid callback token', 'Security Error', ['response' => 403]);
                }
                
                // If test mode, just show success
                if ($test_mode) {
                    wp_die(
                        '<h1>Test Successful!</h1>
                        <p>Callback URL is working correctly.</p>
                        <ul>
                            <li>Type: ' . esc_html($callback_type) . '</li>
                            <li>Survey ID: ' . esc_html($survey_id) . '</li>
                            <li>User ID: ' . esc_html($user_id) . '</li>
                            <li>Token: Valid ✓</li>
                        </ul>
                        <p><a href="' . admin_url('edit.php?post_type=rm_survey') . '">Back to Surveys</a></p>',
                        'Test Successful'
                    );
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
        }
    }

    /**
     * Add callback URLs meta box
     */
    public function add_callback_urls_metabox() {
        add_meta_box(
            'rm_survey_callback_urls',
            __('Survey Callback URLs', 'rm-panel-extensions'),
            [$this, 'render_callback_urls_metabox'],
            'rm_survey',
            'normal',
            'high'
        );
    }

    /**
     * Render callback URLs meta box
     */
    public function render_callback_urls_metabox($post) {
        $survey_id = $post->ID;

        // Generate URLs WITHOUT timestamp
        $urls = $this->generate_callback_urls($survey_id);
        ?>
        <div class="rm-callback-urls-box">
            <p class="description">
                <?php _e('Share these URLs with your survey platform. Use {USER_ID} as placeholder for dynamic user ID.', 'rm-panel-extensions'); ?>
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
                    <button type="button"
                            class="button test-url-btn"
                            data-url-type="success"
                            data-survey-id="<?php echo $survey_id; ?>">
                        <?php _e('Test', 'rm-panel-extensions'); ?>
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
                            class="button copy-url-btn"
                            data-clipboard-target="#callback_url_terminate">
                        <?php _e('Copy', 'rm-panel-extensions'); ?>
                    </button>
                    <button type="button"
                            class="button test-url-btn"
                            data-url-type="terminate"
                            data-survey-id="<?php echo $survey_id; ?>">
                        <?php _e('Test', 'rm-panel-extensions'); ?>
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
                            class="button copy-url-btn"
                            data-clipboard-target="#callback_url_quotafull">
                        <?php _e('Copy', 'rm-panel-extensions'); ?>
                    </button>
                    <button type="button"
                            class="button test-url-btn"
                            data-url-type="quotafull"
                            data-survey-id="<?php echo $survey_id; ?>">
                        <?php _e('Test', 'rm-panel-extensions'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Test Results Area -->
            <div id="callback-test-results" style="margin-top: 20px; display: none;">
                <h4><?php _e('Test Results:', 'rm-panel-extensions'); ?></h4>
                <div class="test-results-content" style="background: #f0f0f0; padding: 15px; border-radius: 5px;">
                    <!-- Results will be displayed here -->
                </div>
            </div>
        </div>
        
        <style>
            .callback-url-group {
                margin-bottom: 15px;
            }
            .url-field-wrapper {
                display: flex;
                gap: 10px;
                align-items: center;
            }
            .callback-url-field {
                flex: 1;
            }
            .test-url-btn {
                background: #0073aa;
                color: white;
            }
            .test-url-btn:hover {
                background: #005177;
            }
        </style>
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

        // Add parameters (without timestamp)
        $thank_you_url = add_query_arg([
            'survey_id' => $survey_id,
            'status' => $status
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
                'error' => __('Error generating URLs', 'rm-panel-extensions'),
                'testing' => __('Testing...', 'rm-panel-extensions'),
                'test_success' => __('Test successful! Data received.', 'rm-panel-extensions'),
                'test_failed' => __('Test failed. Check the console for details.', 'rm-panel-extensions')
            ]
        ]);
        
        // Add inline script for testing functionality
        wp_add_inline_script('rm-survey-callback-admin', '
            jQuery(document).ready(function($) {
                // Test URL button handler
                $(".test-url-btn").on("click", function() {
                    var btn = $(this);
                    var urlType = btn.data("url-type");
                    var surveyId = btn.data("survey-id");
                    var originalText = btn.text();
                    
                    btn.text(rm_callback_ajax.strings.testing).prop("disabled", true);
                    
                    $.ajax({
                        url: rm_callback_ajax.ajax_url,
                        type: "POST",
                        data: {
                            action: "test_callback_url",
                            url_type: urlType,
                            survey_id: surveyId,
                            nonce: rm_callback_ajax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                $("#callback-test-results").show();
                                $(".test-results-content").html(response.data.message);
                            } else {
                                alert(response.data.message || rm_callback_ajax.strings.test_failed);
                            }
                        },
                        error: function() {
                            alert(rm_callback_ajax.strings.test_failed);
                        },
                        complete: function() {
                            btn.text(originalText).prop("disabled", false);
                        }
                    });
                });
            });
        ');
    }

    /**
     * AJAX handler to test callback URLs
     */
    public function ajax_test_callback_url() {
        check_ajax_referer('rm_callback_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $url_type = sanitize_text_field($_POST['url_type']);
        $survey_id = intval($_POST['survey_id']);
        $test_user_id = get_current_user_id();
        
        // Generate test token
        $token = $this->generate_token($survey_id, $test_user_id);
        
        // Build test URL
        $test_url = home_url("/survey-callback/{$url_type}/");
        $test_url = add_query_arg([
            'sid' => $survey_id,
            'uid' => $test_user_id,
            'token' => $token,
            'test_mode' => 1
        ], $test_url);
        
        // Log test attempt
        $log_entry = sprintf(
            '[TEST] Callback URL tested - Type: %s, Survey: %d, User: %d, URL: %s',
            $url_type,
            $survey_id,
            $test_user_id,
            $test_url
        );
        
        error_log($log_entry);
        
        // Check database for recent entry
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE user_id = %d AND survey_id = %d",
            $test_user_id,
            $survey_id
        ));
        
        $message = '<strong>Test Results:</strong><br>';
        $message .= 'URL Type: ' . ucfirst($url_type) . '<br>';
        $message .= 'Survey ID: ' . $survey_id . '<br>';
        $message .= 'Test User ID: ' . $test_user_id . '<br>';
        $message .= 'Token Generated: ' . substr($token, 0, 20) . '...<br>';
        $message .= 'Full Test URL: <code style="word-break: break-all;">' . esc_html($test_url) . '</code><br>';
        
        if ($existing) {
            $message .= '<br><strong style="color: green;">✓ Database record exists</strong><br>';
            $message .= 'Status: ' . $existing->status . '<br>';
            if ($existing->completion_status) {
                $message .= 'Completion Status: ' . $existing->completion_status . '<br>';
            }
        } else {
            $message .= '<br><strong style="color: orange;">⚠ No database record found (will be created when URL is accessed)</strong>';
        }
        
        wp_send_json_success(['message' => $message]);
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

        // Generate actual URLs with real user ID (NO timestamp)
        $urls = $this->generate_user_specific_urls($survey_id, $user_id);

        wp_send_json_success($urls);
    }

    /**
     * Generate user-specific URLs WITHOUT timestamp
     */
    private function generate_user_specific_urls($survey_id, $user_id) {
        $token = $this->generate_token($survey_id, $user_id);
        
        // Generate URLs WITHOUT timestamp
        $urls = [
            'success' => home_url("/survey-callback/success/?sid=$survey_id&uid=$user_id&token=$token"),
            'terminate' => home_url("/survey-callback/terminate/?sid=$survey_id&uid=$user_id&token=$token"),
            'quotafull' => home_url("/survey-callback/quotafull/?sid=$survey_id&uid=$user_id&token=$token"),
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