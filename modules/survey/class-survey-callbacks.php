<?php
/**
 * Survey Callback URLs Handler - DEBUG VERSION
 * Add this temporarily to see what's happening
 */

// Add this to your wp-config.php temporarily:
// define('WP_DEBUG', true);
// define('WP_DEBUG_LOG', true);
// define('WP_DEBUG_DISPLAY', false);

class RM_Survey_Callbacks {

    public function __construct() {
        $this->init();
    }

    private function init() {
        add_action('init', [$this, 'register_callback_endpoints']);
        add_action('template_redirect', [$this, 'handle_callback_request']);
        add_action('add_meta_boxes', [$this, 'add_callback_urls_metabox']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('init', [$this, 'check_and_fix_rewrite_rules'], 999);
        add_action('template_redirect', [$this, 'fallback_callback_handler'], 1);
    }

    public function register_callback_endpoints() {
        add_rewrite_rule('^survey-callback/success/?$', 'index.php?rm_callback=success', 'top');
        add_rewrite_rule('^survey-callback/terminate/?$', 'index.php?rm_callback=terminate', 'top');
        add_rewrite_rule('^survey-callback/quotafull/?$', 'index.php?rm_callback=quotafull', 'top');
        add_rewrite_tag('%rm_callback%', '([^&]+)');
    }

    /**
     * Generate survey-level token (works for all users)
     */
    private function generate_survey_token($survey_id) {
        $data = 'survey_' . $survey_id . '_callback_' . wp_salt('auth');
        return hash('sha256', $data);
    }

    /**
     * Verify survey token
     */
    private function verify_survey_token($survey_id, $provided_token) {
        $expected_token = $this->generate_survey_token($survey_id);
        return hash_equals($expected_token, $provided_token);
    }

    /**
     * Handle callback request - WITH DEBUGGING
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

        // DEBUG OUTPUT - REMOVE IN PRODUCTION
        $debug_info = [
            'callback_type' => $callback_type,
            'survey_id' => $survey_id,
            'user_id' => $user_id,
            'provided_token' => $token,
            'expected_token' => $this->generate_survey_token($survey_id),
            'tokens_match' => hash_equals($this->generate_survey_token($survey_id), $token),
            'request_uri' => $_SERVER['REQUEST_URI'],
            'get_params' => $_GET
        ];

        // Show debug page if in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Survey Callback Debug: ' . print_r($debug_info, true));
        }

        // If test mode, show debug info
        if (isset($_GET['debug']) || isset($_GET['test_mode'])) {
            $this->show_debug_page($debug_info);
            exit;
        }

        // Verify token
        if (!$this->verify_survey_token($survey_id, $token)) {
            // Show detailed error in debug mode
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $this->show_debug_page($debug_info);
                exit;
            }
            
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
     * Show debug page
     */
    private function show_debug_page($debug_info) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Survey Callback Debug</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
                .container { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
                h1 { color: #333; border-bottom: 2px solid #007cba; padding-bottom: 10px; }
                .info-grid { display: grid; gap: 10px; margin: 20px 0; }
                .info-row { display: grid; grid-template-columns: 200px 1fr; gap: 10px; padding: 10px; background: #f9f9f9; }
                .info-label { font-weight: bold; color: #666; }
                .info-value { font-family: monospace; word-break: break-all; }
                .success { color: green; font-weight: bold; }
                .error { color: red; font-weight: bold; }
                .token { background: #e0e0e0; padding: 5px; border-radius: 3px; }
                .match { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 20px 0; }
                .no-match { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 20px 0; }
                code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🔍 Survey Callback Debug Information</h1>
                
                <div class="<?php echo $debug_info['tokens_match'] ? 'match' : 'no-match'; ?>">
                    <strong>Token Verification: <?php echo $debug_info['tokens_match'] ? '✅ PASS' : '❌ FAIL'; ?></strong>
                </div>

                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Callback Type:</div>
                        <div class="info-value"><?php echo esc_html($debug_info['callback_type']); ?></div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Survey ID:</div>
                        <div class="info-value"><?php echo esc_html($debug_info['survey_id']); ?></div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">User ID:</div>
                        <div class="info-value"><?php echo esc_html($debug_info['user_id']); ?></div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Provided Token:</div>
                        <div class="info-value token"><?php echo esc_html($debug_info['provided_token']); ?></div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Expected Token:</div>
                        <div class="info-value token"><?php echo esc_html($debug_info['expected_token']); ?></div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">Request URI:</div>
                        <div class="info-value"><?php echo esc_html($debug_info['request_uri']); ?></div>
                    </div>
                </div>

                <h2>How to Fix</h2>
                <?php if (!$debug_info['tokens_match']) : ?>
                    <ol>
                        <li>Go to your survey edit page in WordPress admin</li>
                        <li>Find the "Survey Callback URLs" meta box</li>
                        <li>Copy the NEW token from there (it should be: <code><?php echo substr($debug_info['expected_token'], 0, 20); ?>...</code>)</li>
                        <li>Replace the old token in your URL</li>
                    </ol>
                    
                    <h3>Correct URL for User ID <?php echo $debug_info['user_id']; ?>:</h3>
                    <div style="background: #e8f4f8; padding: 15px; border-radius: 5px; margin: 10px 0;">
                        <code style="word-break: break-all;">
                            <?php 
                            $correct_url = home_url('/survey-callback/' . $debug_info['callback_type'] . '/');
                            $correct_url = add_query_arg([
                                'sid' => $debug_info['survey_id'],
                                'uid' => $debug_info['user_id'],
                                'token' => $debug_info['expected_token']
                            ], $correct_url);
                            echo esc_html($correct_url);
                            ?>
                        </code>
                    </div>
                    
                    <p><a href="<?php echo $correct_url; ?>" class="button">Test Correct URL</a></p>
                <?php else : ?>
                    <p class="success">✅ Token is correct! The callback should work.</p>
                <?php endif; ?>
                
                <p style="margin-top: 30px;">
                    <a href="<?php echo admin_url('edit.php?post_type=rm_survey'); ?>">← Back to Surveys</a>
                </p>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Fallback handler
     */
    public function fallback_callback_handler() {
        if (get_query_var('rm_callback')) {
            return;
        }
        
        $request_uri = $_SERVER['REQUEST_URI'];
        
        if (strpos($request_uri, '/survey-callback/') !== false) {
            if (preg_match('/survey-callback\/(success|terminate|quotafull)/', $request_uri, $matches)) {
                $_GET['rm_callback'] = $matches[1];
                set_query_var('rm_callback', $matches[1]);
                $this->handle_callback_request();
            }
        }
    }

    public function check_and_fix_rewrite_rules() {
        if (!is_admin()) {
            return;
        }
        
        $rules = get_option('rewrite_rules');
        
        if (!$rules || !isset($rules['^survey-callback/success/?$'])) {
            $this->register_callback_endpoints();
            set_transient('rm_survey_flush_rules', true, 60);
        }
        
        if (get_transient('rm_survey_flush_rules')) {
            flush_rewrite_rules();
            delete_transient('rm_survey_flush_rules');
        }
    }

    public function generate_callback_urls($survey_id, $user_id = null) {
        if (!$user_id) {
            $user_id = '{USER_ID}';
        }

        $base_token = $this->generate_survey_token($survey_id);

        $urls = [
            'success' => $this->build_callback_url('success', $survey_id, $user_id, $base_token),
            'terminate' => $this->build_callback_url('terminate', $survey_id, $user_id, $base_token),
            'quotafull' => $this->build_callback_url('quotafull', $survey_id, $user_id, $base_token),
        ];

        return $urls;
    }

    private function build_callback_url($status, $survey_id, $user_id, $token) {
        $base_url = home_url('/survey-callback/' . $status . '/');

        $params = [
            'sid' => $survey_id,
            'uid' => $user_id,
            'token' => $token,
        ];

        return add_query_arg($params, $base_url);
    }

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

    public function render_callback_urls_metabox($post) {
        $survey_id = $post->ID;
        $urls = $this->generate_callback_urls($survey_id);
        $current_user_id = get_current_user_id();
        
        // Generate test URLs with current user
        $test_urls = [
            'success' => str_replace('{USER_ID}', $current_user_id, $urls['success']),
            'terminate' => str_replace('{USER_ID}', $current_user_id, $urls['terminate']),
            'quotafull' => str_replace('{USER_ID}', $current_user_id, $urls['quotafull']),
        ];
        ?>
        <div class="rm-callback-urls-box">
            <div class="notice notice-info inline" style="margin: 15px 0;">
                <p><strong>📋 Token Information:</strong></p>
                <p>Current survey token: <code><?php echo substr($this->generate_survey_token($survey_id), 0, 30); ?>...</code></p>
                <p>This token works for ALL users taking survey #<?php echo $survey_id; ?></p>
            </div>

            <?php foreach (['success', 'terminate', 'quotafull'] as $type) : ?>
            <div class="callback-url-group" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px;">
                <label style="font-weight: bold; display: block; margin-bottom: 10px;">
                    <?php echo ucfirst($type); ?> URL:
                </label>
                
                <div style="margin-bottom: 10px;">
                    <strong>With Placeholder:</strong>
                    <input type="text" 
                           id="callback_url_<?php echo $type; ?>" 
                           value="<?php echo esc_url($urls[$type]); ?>" 
                           readonly 
                           style="width: 100%; padding: 8px; font-family: monospace; font-size: 12px;" />
                    <button type="button" 
                            class="button copy-url-btn" 
                            data-clipboard-target="#callback_url_<?php echo $type; ?>"
                            style="margin-top: 5px;">
                        📋 Copy URL with {USER_ID}
                    </button>
                </div>
                
                <div style="margin-top: 10px;">
                    <strong>Test with Your User ID (<?php echo $current_user_id; ?>):</strong>
                    <div style="margin-top: 5px;">
                        <a href="<?php echo esc_url($test_urls[$type] . '&debug=1'); ?>" 
                           class="button button-primary" 
                           target="_blank">
                            🧪 Test <?php echo ucfirst($type); ?> URL
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <style>
            .callback-url-group { border: 1px solid #ddd; }
            .copy-url-btn { margin-top: 5px; }
        </style>
        <?php
    }

    public function enqueue_admin_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        global $post_type;
        if ('rm_survey' !== $post_type) {
            return;
        }

        wp_enqueue_script(
            'clipboard-js',
            'https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.11/clipboard.min.js',
            [],
            '2.0.11',
            true
        );

        wp_add_inline_script('clipboard-js', '
            new ClipboardJS(".copy-url-btn").on("success", function(e) {
                var btn = e.trigger;
                var originalText = btn.textContent;
                btn.textContent = "✅ Copied!";
                btn.style.background = "#46b450";
                btn.style.color = "white";
                setTimeout(function() {
                    btn.textContent = originalText;
                    btn.style.background = "";
                    btn.style.color = "";
                }, 2000);
                e.clearSelection();
            });
        ');
    }

    private function process_callback($survey_id, $user_id, $status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';

        if (class_exists('RM_Panel_Survey_Tracking')) {
            $tracker = new RM_Panel_Survey_Tracking();
            $tracker->complete_survey($user_id, $survey_id, $status);
        }

        do_action('rm_survey_callback_processed', $survey_id, $user_id, $status);
    }

    private function redirect_to_thank_you($survey_id, $status) {
        $thank_you_url = home_url('/survey-thank-you/');
        $thank_you_url = add_query_arg([
            'survey_id' => $survey_id,
            'status' => $status
        ], $thank_you_url);

        wp_redirect($thank_you_url);
        exit;
    }
}

new RM_Survey_Callbacks();