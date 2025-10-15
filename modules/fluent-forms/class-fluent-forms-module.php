<?php
/**
 * Fluent Forms Integration Module - Enhanced with Real-time Validation & Country Detection
 * 
 * File: modules/fluent-forms/class-fluent-forms-module.php
 */
if (!defined('ABSPATH')) {
    exit;
}

class RM_Panel_Fluent_Forms_Module {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Check if Fluent Forms is active
        if (!$this->is_fluent_forms_active()) {
            add_action('admin_notices', [$this, 'fluent_forms_missing_notice']);
            return;
        }
        
        // Core validation hooks
        add_filter('fluentform/validation_errors', [$this, 'validate_password_confirmation'], 10, 4);
        add_action('fluentform/before_insert_submission', [$this, 'before_submission'], 10, 3);

        // AJAX validation endpoints
        add_action('wp_ajax_check_username_availability', [$this, 'check_username_availability']);
        add_action('wp_ajax_nopriv_check_username_availability', [$this, 'check_username_availability']);
        add_action('wp_ajax_check_email_availability', [$this, 'check_email_availability']);
        add_action('wp_ajax_nopriv_check_email_availability', [$this, 'check_email_availability']);
        add_action('wp_ajax_check_password_strength', [$this, 'check_password_strength']);
        add_action('wp_ajax_nopriv_check_password_strength', [$this, 'check_password_strength']);
        
        // Country detection AJAX endpoints
        add_action('wp_ajax_get_country_from_ip', [$this, 'ajax_get_country_from_ip']);
        add_action('wp_ajax_nopriv_get_country_from_ip', [$this, 'ajax_get_country_from_ip']);
        
        // Frontend scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_validation_scripts']);
        
        // Admin settings
        add_action('admin_menu', [$this, 'add_settings_submenu'], 100);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        $this->init_hooks();
    }

    private function is_fluent_forms_active() {
        return defined('FLUENTFORM') || function_exists('wpFluentForm');
    }

    private function init_hooks() {
        add_filter('fluentform/validation_errors', [$this, 'validate_password_confirmation'], 10, 4);
        add_action('fluentform/before_insert_submission', [$this, 'before_submission'], 10, 3);
        add_filter('fluentform/validation_message_password', [$this, 'custom_password_messages'], 10, 3);
    }

    public function validate_password_confirmation($errors, $formData, $form, $fields) {
        $password_field = 'password';
        $confirm_password_field = 'confirm_password';
        
        $password = isset($formData[$password_field]) ? $formData[$password_field] : '';
        $confirm_password = isset($formData[$confirm_password_field]) ? $formData[$confirm_password_field] : '';

        if (!empty($password) || !empty($confirm_password)) {
            if (!empty($password) && strlen($password) < 8) {
                $errors[$password_field] = [
                    __('Password must be at least 8 characters long.', 'rm-panel-extensions')
                ];
            }

            if ($password !== $confirm_password) {
                $errors[$confirm_password_field] = [
                    __('Passwords do not match. Please ensure both password fields are identical.', 'rm-panel-extensions')
                ];
            }

            if (!empty($password) && !$this->validate_password_strength($password)) {
                $errors[$password_field] = [
                    __('Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.', 'rm-panel-extensions')
                ];
            }
        }
        
        // Username validation
        $username = isset($formData['username']) ? sanitize_user($formData['username']) : '';
        if (!empty($username)) {
            if (strlen($username) < 5) {
                $errors['username'] = [
                    __('Username must be at least 5 characters long.', 'rm-panel-extensions')
                ];
            }

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $errors['username'] = [
                    __('Username can only contain letters, numbers, and underscores.', 'rm-panel-extensions')
                ];
            }

            if (username_exists($username)) {
                $errors['username'] = [
                    __('This username is already taken. Please choose another.', 'rm-panel-extensions')
                ];
            }
        }

        // Email validation
        $email = isset($formData['email']) ? sanitize_email($formData['email']) : '';
        if (!empty($email)) {
            if (!is_email($email)) {
                $errors['email'] = [
                    __('Please enter a valid email address.', 'rm-panel-extensions')
                ];
            }

            if (email_exists($email)) {
                $errors['email'] = [
                    __('This email is already registered. Please use another email or login.', 'rm-panel-extensions')
                ];
            }
        }

        return $errors;
    }

    private function validate_password_strength($password) {
        if (strlen($password) < 8) {
            return false;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        return true;
    }

    public function check_username_availability() {
        check_ajax_referer('rm_username_check_nonce', 'nonce');

        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';

        if (strlen($username) < 5) {
            wp_send_json_error([
                'message' => __('Username must be at least 5 characters long.', 'rm-panel-extensions')
            ]);
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            wp_send_json_error([
                'message' => __('Username can only contain letters, numbers, and underscores.', 'rm-panel-extensions')
            ]);
        }

        if (username_exists($username)) {
            wp_send_json_error([
                'message' => __('This username is already taken. Please choose another.', 'rm-panel-extensions')
            ]);
        }

        wp_send_json_success([
            'message' => __('Username is available!', 'rm-panel-extensions')
        ]);
    }

    public function check_email_availability() {
        check_ajax_referer('rm_email_check_nonce', 'nonce');

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (empty($email)) {
            wp_send_json_error([
                'message' => __('Please enter an email address.', 'rm-panel-extensions')
            ]);
        }

        if (!is_email($email)) {
            wp_send_json_error([
                'message' => __('Please enter a valid email address.', 'rm-panel-extensions')
            ]);
        }

        if (email_exists($email)) {
            wp_send_json_error([
                'message' => __('This email is already registered. Please use another email or login.', 'rm-panel-extensions')
            ]);
        }

        wp_send_json_success([
            'message' => __('Email is available!', 'rm-panel-extensions')
        ]);
    }

    public function check_password_strength() {
        check_ajax_referer('rm_password_check_nonce', 'nonce');

        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        $errors = [];
        $strength = 'weak';

        // Check length
        if (strlen($password) < 8) {
            $errors[] = __('At least 8 characters', 'rm-panel-extensions');
        }

        // Check uppercase
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = __('One uppercase letter', 'rm-panel-extensions');
        }

        // Check lowercase
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = __('One lowercase letter', 'rm-panel-extensions');
        }

        // Check number
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = __('One number', 'rm-panel-extensions');
        }

        // Calculate strength
        if (empty($errors)) {
            $strength = 'strong';
        } elseif (count($errors) <= 2) {
            $strength = 'medium';
        }

        // Check password match
        $passwords_match = false;
        if (!empty($confirm_password) && $password === $confirm_password) {
            $passwords_match = true;
        }

        if (empty($errors)) {
            wp_send_json_success([
                'strength' => $strength,
                'message' => __('Strong password!', 'rm-panel-extensions'),
                'passwords_match' => $passwords_match
            ]);
        } else {
            wp_send_json_error([
                'strength' => $strength,
                'message' => __('Password needs: ', 'rm-panel-extensions') . implode(', ', $errors),
                'requirements' => $errors,
                'passwords_match' => $passwords_match
            ]);
        }
    }

    /**
     * AJAX handler to get country from IP
     */
    public function ajax_get_country_from_ip() {
        check_ajax_referer('rm_country_check_nonce', 'nonce');
        
        error_log('RM Panel: Country detection AJAX called');
        
        $country = $this->get_user_country_from_ip();
        
        if ($country) {
            error_log('RM Panel: Country detected: ' . $country);
            wp_send_json_success([
                'country' => $country,
                'message' => __('Country detected', 'rm-panel-extensions')
            ]);
        } else {
            error_log('RM Panel: Failed to detect country');
            wp_send_json_error([
                'message' => __('Could not detect country', 'rm-panel-extensions')
            ]);
        }
    }

    /**
     * Get country from IP using IPStack
     */
    private function get_user_country_from_ip() {
        $ip = $this->get_user_ip();
        $api_key = get_option('rm_panel_ipstack_api_key', '');
        
        error_log('RM Panel: Detecting country for IP: ' . $ip);
        error_log('RM Panel: API Key present: ' . (!empty($api_key) ? 'Yes' : 'No'));
        
        if (empty($api_key)) {
            error_log('RM Panel: IPStack API key is not set');
            return '';
        }
        
        // Try to get from cache first (5 minutes)
        $cache_key = 'rm_country_' . md5($ip);
        $cached_country = get_transient($cache_key);
        
        if ($cached_country !== false) {
            error_log('RM Panel: Using cached country: ' . $cached_country);
            return $cached_country;
        }
        
        // Call IPStack API
        $url = "http://api.ipstack.com/{$ip}?access_key={$api_key}";
        error_log('RM Panel: Calling IPStack API: ' . $url);
        
        $response = wp_remote_get($url, [
            'timeout' => 10,
            'sslverify' => false
        ]);
        
        if (is_wp_error($response)) {
            error_log('RM Panel: IPStack API error - ' . $response->get_error_message());
            return '';
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Log the full response for debugging
        error_log('RM Panel: IPStack API response: ' . print_r($data, true));
        
        if (isset($data['country_name']) && !empty($data['country_name'])) {
            $country = $data['country_name'];
            error_log('RM Panel: Country detected successfully: ' . $country);
            
            // Cache for 5 minutes
            set_transient($cache_key, $country, 5 * MINUTE_IN_SECONDS);
            return $country;
        }
        
        if (isset($data['error'])) {
            error_log('RM Panel: IPStack API error - ' . print_r($data['error'], true));
        }
        
        return '';
    }

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
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function enqueue_validation_scripts() {
    if (!function_exists('wpFluentForm')) {
        return;
    }

    global $post;
    if (!is_a($post, 'WP_Post')) {
        return;
    }

    preg_match_all('/\[fluentform id="(\d+)"\]/', $post->post_content, $matches);
    
    $load_scripts = false;
    if (!empty($matches[1])) {
        foreach ($matches[1] as $form_id) {
            $settings = get_option('rm_fluent_form_validation_' . $form_id, []);
            if (!empty($settings['enable_realtime_validation'])) {
                $load_scripts = true;
                break;
            }
        }
    }

    if (!$load_scripts) {
        return;
    }

    wp_enqueue_script(
        'rm-fluent-forms-validation',
        RM_PANEL_EXT_PLUGIN_URL . 'assets/js/fluent-forms-validation.js',
        ['jquery'],
        RM_PANEL_EXT_VERSION,
        true
    );

    wp_localize_script('rm-fluent-forms-validation', 'rmFluentFormsValidation', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'username_nonce' => wp_create_nonce('rm_username_check_nonce'),
        'email_nonce' => wp_create_nonce('rm_email_check_nonce'),
        'password_nonce' => wp_create_nonce('rm_password_check_nonce'),
        'country_nonce' => wp_create_nonce('rm_country_check_nonce'),
        'messages' => [
            'username_checking' => __('Checking username...', 'rm-panel-extensions'),
            'username_available' => __('Username is available!', 'rm-panel-extensions'),
            'email_checking' => __('Checking email...', 'rm-panel-extensions'),
            'email_available' => __('Email is available!', 'rm-panel-extensions'),
            'password_checking' => __('Checking password strength...', 'rm-panel-extensions'),
            'password_strong' => __('Strong password!', 'rm-panel-extensions'),
            'passwords_match' => __('Passwords match!', 'rm-panel-extensions'),
            'passwords_no_match' => __('Passwords do not match', 'rm-panel-extensions'),
            'country_detecting' => __('Detecting country...', 'rm-panel-extensions'),
            'country_detected' => __('Country detected!', 'rm-panel-extensions'),
            // NEW MESSAGE
            'country_mismatch' => __('Please select your actual country. Changing your country is not allowed.', 'rm-panel-extensions')
        ]
    ]);

    wp_enqueue_style(
        'rm-fluent-forms-validation',
        RM_PANEL_EXT_PLUGIN_URL . 'assets/css/fluent-forms-validation.css',
        [],
        RM_PANEL_EXT_VERSION
    );
}

    public function add_settings_submenu() {
        if (!defined('FLUENTFORM')) {
            return;
        }

        add_submenu_page(
            'fluent_forms',
            __('RM Validation Settings', 'rm-panel-extensions'),
            __('RM Validation', 'rm-panel-extensions'),
            'manage_options',
            'rm-fluent-forms-validation',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        // Settings are stored per form, no need to register
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['rm_fluent_validation_nonce']) && 
            wp_verify_nonce($_POST['rm_fluent_validation_nonce'], 'rm_fluent_validation_settings')) {
            $this->save_form_settings();
        }

        global $wpdb;
        $forms = $wpdb->get_results(
            "SELECT id, title FROM {$wpdb->prefix}fluentform_forms ORDER BY title ASC"
        );

        ?>
        <div class="wrap">
            <h1><?php _e('RM Real-time Validation Settings', 'rm-panel-extensions'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Enable real-time validation for specific forms:', 'rm-panel-extensions'); ?></strong><br>
                    <?php _e('This will add instant feedback for username, email, password fields, and auto-detect country.', 'rm-panel-extensions'); ?>
                </p>
            </div>

            <?php if (isset($_GET['settings-updated'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully!', 'rm-panel-extensions'); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field('rm_fluent_validation_settings', 'rm_fluent_validation_nonce'); ?>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php _e('Enable', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Form Name', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Form ID', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Validation Features', 'rm-panel-extensions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($forms)) : ?>
                            <tr>
                                <td colspan="4">
                                    <?php _e('No Fluent Forms found. Please create a form first.', 'rm-panel-extensions'); ?>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($forms as $form) : 
                                $settings = get_option('rm_fluent_form_validation_' . $form->id, []);
                                $enabled = isset($settings['enable_realtime_validation']) ? $settings['enable_realtime_validation'] : false;
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox" 
                                           name="forms[<?php echo $form->id; ?>][enable_realtime_validation]" 
                                           value="1" 
                                           <?php checked($enabled, 1); ?>>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($form->title); ?></strong>
                                </td>
                                <td>
                                    <code><?php echo $form->id; ?></code>
                                </td>
                                <td>
                                    <small>
                                        <?php _e('✓ Real-time username validation', 'rm-panel-extensions'); ?><br>
                                        <?php _e('✓ Real-time email validation', 'rm-panel-extensions'); ?><br>
                                        <?php _e('✓ Password strength indicator', 'rm-panel-extensions'); ?><br>
                                        <?php _e('✓ Password match validation', 'rm-panel-extensions'); ?><br>
                                        <?php _e('✓ Auto-detect country from IP', 'rm-panel-extensions'); ?>
                                    </small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php _e('Save Settings', 'rm-panel-extensions'); ?>
                    </button>
                </p>
            </form>

            <hr>

            <h2><?php _e('Field Name Requirements', 'rm-panel-extensions'); ?></h2>
            <p><?php _e('For validation to work, use these exact field names in your Fluent Forms:', 'rm-panel-extensions'); ?></p>
            
            <table class="widefat" style="max-width: 600px;">
                <thead>
                    <tr>
                        <th><?php _e('Field Purpose', 'rm-panel-extensions'); ?></th>
                        <th><?php _e('Required Field Name', 'rm-panel-extensions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php _e('Username', 'rm-panel-extensions'); ?></td>
                        <td><code>username</code></td>
                    </tr>
                    <tr>
                        <td><?php _e('Email', 'rm-panel-extensions'); ?></td>
                        <td><code>email</code></td>
                    </tr>
                    <tr>
                        <td><?php _e('Password', 'rm-panel-extensions'); ?></td>
                        <td><code>password</code></td>
                    </tr>
                    <tr>
                        <td><?php _e('Confirm Password', 'rm-panel-extensions'); ?></td>
                        <td><code>confirm_password</code></td>
                    </tr>
                    <tr>
                        <td><?php _e('Country (Auto-detect)', 'rm-panel-extensions'); ?></td>
                        <td><code>country</code></td>
                    </tr>
                </tbody>
            </table>
            
            <hr>
            
            <h2><?php _e('Country Detection Setup', 'rm-panel-extensions'); ?></h2>
            <p><?php _e('To enable country auto-detection:', 'rm-panel-extensions'); ?></p>
            <ol>
                <li><?php _e('Get a free API key from', 'rm-panel-extensions'); ?> <a href="https://ipstack.com" target="_blank">ipstack.com</a></li>
                <li><?php _e('Go to RM Panel Ext → Settings', 'rm-panel-extensions'); ?></li>
                <li><?php _e('Enter your IPStack API key', 'rm-panel-extensions'); ?></li>
                <li><?php _e('Add a field named "country" to your form', 'rm-panel-extensions'); ?></li>
                <li><?php _e('Enable validation for the form', 'rm-panel-extensions'); ?></li>
            </ol>
            
            <p>
                <strong><?php _e('Current API Key Status:', 'rm-panel-extensions'); ?></strong>
                <?php
                $api_key = get_option('rm_panel_ipstack_api_key', '');
                if (!empty($api_key)) {
                    echo '<span style="color: green;">✓ ' . __('API Key is set', 'rm-panel-extensions') . '</span>';
                    echo ' <a href="' . admin_url('admin.php?page=rm-panel-extensions-settings') . '">' . __('Update', 'rm-panel-extensions') . '</a>';
                } else {
                    echo '<span style="color: red;">✗ ' . __('API Key not set', 'rm-panel-extensions') . '</span>';
                    echo ' <a href="' . admin_url('admin.php?page=rm-panel-extensions-settings') . '">' . __('Set API Key', 'rm-panel-extensions') . '</a>';
                }
                ?>
            </p>
        </div>

        <style>
            .wrap h1 { margin-bottom: 20px; }
            .wrap .notice { margin: 20px 0; }
            .wrap table { margin-top: 20px; }
            .wrap table td { vertical-align: middle; }
            .wrap table small { color: #666; line-height: 1.6; }
        </style>
        <?php
    }

    private function save_form_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $forms = isset($_POST['forms']) ? $_POST['forms'] : [];

        foreach ($forms as $form_id => $settings) {
            $form_id = intval($form_id);
            $enable = isset($settings['enable_realtime_validation']) ? 1 : 0;
            
            update_option('rm_fluent_form_validation_' . $form_id, [
                'enable_realtime_validation' => $enable
            ]);
        }

        wp_redirect(add_query_arg('settings-updated', 'true', $_SERVER['REQUEST_URI']));
        exit;
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'fluent_forms_page_rm-fluent-forms-validation') {
            return;
        }
    }

    public function before_submission($insertData, $formData, $form) {
        // Add any additional pre-submission logic here
    }

    public function custom_password_messages($message, $formData, $form) {
        return __('Please enter a valid password.', 'rm-panel-extensions');
    }

    private function create_wordpress_user($formData) {
        $username = isset($formData['username']) ? sanitize_user($formData['username']) : '';
        $email = isset($formData['email']) ? sanitize_email($formData['email']) : '';
        $password = isset($formData['password']) ? $formData['password'] : '';
        $first_name = isset($formData['first_name']) ? sanitize_text_field($formData['first_name']) : '';
        $last_name = isset($formData['last_name']) ? sanitize_text_field($formData['last_name']) : '';

        if (username_exists($username) || email_exists($email)) {
            return new WP_Error('user_exists', __('Username or email already exists.', 'rm-panel-extensions'));
        }

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ]);

        if (isset($formData['gender'])) {
            update_user_meta($user_id, 'gender', sanitize_text_field($formData['gender']));
        }

        if (isset($formData['country'])) {
            update_user_meta($user_id, 'country', sanitize_text_field($formData['country']));
        }

        wp_new_user_notification($user_id, null, 'both');

        return $user_id;
    }

    public function fluent_forms_missing_notice() {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php _e('RM Panel Extensions:', 'rm-panel-extensions'); ?></strong>
                <?php _e('Fluent Forms Integrations module requires Fluent Forms to be installed and activated.', 'rm-panel-extensions'); ?>
                <a href="<?php echo admin_url('plugin-install.php?s=fluent+forms&tab=search&type=term'); ?>">
                    <?php _e('Install Fluent Forms', 'rm-panel-extensions'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}