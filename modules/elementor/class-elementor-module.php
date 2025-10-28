<?php
/**
 * RM Panel Extensions - Elementor Module
 * 
 * @package RM_Panel_Extensions
 * @subpackage Modules/Elementor
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Elementor Module Class
 */
class RM_Panel_Elementor_Module {

    /**
     * Settings
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('rm_panel_extensions_settings', []);
        $this->init();
    }

    /**
     * Initialize the module
     */
    private function init() {
        // Check if Elementor is loaded
        if (!did_action('elementor/loaded')) {
            return;
        }

        // Register widgets
        add_action('elementor/widgets/register', [$this, 'register_widgets']);

        // Register widget categories
        add_action('elementor/elements/categories_registered', [$this, 'add_widget_categories']);

        // Register AJAX handlers for login widget
        add_action('wp_ajax_nopriv_rm_panel_login_handler', [$this, 'handle_login']);
        add_action('wp_ajax_rm_panel_login_handler', [$this, 'handle_login']);

        // Register AJAX handlers for survey listing
        add_action('wp_ajax_nopriv_rm_load_more_surveys', [$this, 'load_more_surveys']);
        add_action('wp_ajax_rm_load_more_surveys', [$this, 'load_more_surveys']);

        // Register WPML strings if enabled
        if (!empty($this->settings['enable_wpml_support'])) {
            $this->register_wpml_strings();
        }

        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        // Add custom Elementor controls if needed
        add_action('elementor/controls/register', [$this, 'register_controls']);
    }

    /**
     * Add custom widget categories
     */
    public function add_widget_categories($elements_manager) {
        $category_name = !empty($this->settings['custom_widget_category']) ? $this->settings['custom_widget_category'] : __('RM Panel Widgets', 'rm-panel-extensions');

        $elements_manager->add_category(
                'rm-panel-widgets',
                [
                    'title' => $category_name,
                    'icon' => 'fa fa-plug',
                ]
        );
    }

    /**
     * Register widgets
     */
    public function register_widgets($widgets_manager) {
        // Check if login widget is enabled
        if (empty($this->settings['enable_login_widget']) && isset($this->settings['enable_login_widget'])) {
            // Don't return here, continue to register other widgets
        } else {
            // Include login widget file
            $login_widget_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/elementor/widgets/login-widget.php';
            if (file_exists($login_widget_file)) {
                require_once $login_widget_file;
                $widgets_manager->register(new \RM_Panel_Login_Widget());
            }
        }

        // Check if survey widget is enabled
        if (empty($this->settings['enable_survey_widget']) && isset($this->settings['enable_survey_widget'])) {
            // Skip if explicitly disabled
        } else {
            // Include survey listing widget file
            $survey_widget_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/elementor/widgets/survey-listing-widget.php';
            if (file_exists($survey_widget_file)) {
                require_once $survey_widget_file;
                $widgets_manager->register(new \RM_Panel_Survey_Listing_Widget());
            }

            // Include survey accordion widget file
            $survey_accordion_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/elementor/widgets/survey-accordion-widget.php';
            if (file_exists($survey_accordion_file)) {
                require_once $survey_accordion_file;
                $widgets_manager->register(new \RM_Panel_Survey_Accordion_Widget());
            }
        }
        
        // Register Profile Picture Widget
        require_once RM_PANEL_EXT_PLUGIN_DIR . 'modules/elementor/widgets/profile-picture-widget.php';
        $widgets_manager->register(new \RMPanelExtensions\Modules\Elementor\Widgets\Profile_Picture_Widget());


        // Include survey accordion tabs widget
        $survey_accordion_tabs_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/elementor/widgets/survey-accordion-tabs-widget.php';
        if (file_exists($survey_accordion_tabs_file)) {
            require_once $survey_accordion_tabs_file;
            $widgets_manager->register(new \RM_Panel_Survey_Accordion_Tabs_Widget());
        }

        // Hook for additional widgets
        do_action('rm_panel_register_elementor_widgets', $widgets_manager);
    }

    /**
     * Register custom controls
     */
    public function register_controls($controls_manager) {
        // Add custom controls if needed in the future
        do_action('rm_panel_register_elementor_controls', $controls_manager);
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        // Enqueue jQuery
        wp_enqueue_script('jquery');

        // Enqueue custom styles
        wp_enqueue_style(
                'rm-panel-elementor-widgets',
                RM_PANEL_EXT_PLUGIN_URL . 'assets/css/elementor-widgets.css',
                [],
                RM_PANEL_EXT_VERSION
        );

        // Enqueue survey styles if survey post type exists
        if (post_type_exists('rm_survey')) {
            wp_enqueue_style(
                    'rm-panel-survey-styles',
                    RM_PANEL_EXT_PLUGIN_URL . 'assets/css/survey-styles.css',
                    [],
                    RM_PANEL_EXT_VERSION
            );

            // Enqueue survey accordion styles
            wp_enqueue_style(
                    'rm-panel-survey-accordion',
                    RM_PANEL_EXT_PLUGIN_URL . 'assets/css/survey-accordion.css',
                    [],
                    RM_PANEL_EXT_VERSION
            );
        }

        // Enqueue custom scripts
        wp_enqueue_script(
                'rm-panel-elementor-widgets',
                RM_PANEL_EXT_PLUGIN_URL . 'assets/js/elementor-widgets.js',
                ['jquery'],
                RM_PANEL_EXT_VERSION,
                true
        );

        // Enqueue survey scripts if survey post type exists
        if (post_type_exists('rm_survey')) {
            wp_enqueue_script(
                    'rm-panel-survey-scripts',
                    RM_PANEL_EXT_PLUGIN_URL . 'assets/js/survey-scripts.js',
                    ['jquery'],
                    RM_PANEL_EXT_VERSION,
                    true
            );
        }

        // Localize script
        wp_localize_script('rm-panel-elementor-widgets', 'rm_panel_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rm_panel_ajax_nonce'),
            'strings' => [
                'loading' => __('Loading...', 'rm-panel-extensions'),
                'error' => __('An error occurred. Please try again.', 'rm-panel-extensions'),
                'empty_username' => __('Please enter your username or email.', 'rm-panel-extensions'),
                'empty_password' => __('Please enter your password.', 'rm-panel-extensions'),
                'invalid_email' => __('Please enter a valid email address.', 'rm-panel-extensions'),
                'load_more' => __('Load More', 'rm-panel-extensions'),
                'no_more_surveys' => __('No more surveys to load', 'rm-panel-extensions'),
            ]
        ]);
    }

    /**
     * Handle AJAX Load More Surveys
     */
    public function load_more_surveys() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rm_panel_ajax_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'rm-panel-extensions')]);
        }

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 6;
        $settings = isset($_POST['settings']) ? json_decode(stripslashes($_POST['settings']), true) : [];

        // Build query
        $args = [
            'post_type' => 'rm_survey',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
        ];

        // Apply filters from settings
        if (!empty($settings['orderby'])) {
            $args['orderby'] = $settings['orderby'];
        }

        if (!empty($settings['order'])) {
            $args['order'] = $settings['order'];
        }

        if (!empty($settings['categories'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'survey_category',
                    'field' => 'term_id',
                    'terms' => $settings['categories'],
                ],
            ];
        }

        if (!empty($settings['survey_status_filter'])) {
            $args['meta_query'][] = [
                'key' => '_rm_survey_status',
                'value' => $settings['survey_status_filter'],
                'compare' => 'IN',
            ];
        }

        $query = new WP_Query($args);

        ob_start();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $this->render_survey_item_ajax($settings);
            }
        }

        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'has_more' => $page < $query->max_num_pages,
            'max_pages' => $query->max_num_pages,
        ]);

        wp_reset_postdata();
    }

    /**
     * Render survey item for AJAX
     */
    private function render_survey_item_ajax($settings) {
        ?>
        <article class="survey-item">
            <h3 class="survey-title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>
            <?php if (!empty($settings['show_excerpt'])) : ?>
                <div class="survey-excerpt">
                    <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                </div>
            <?php endif; ?>
            <a href="<?php the_permalink(); ?>" class="survey-button">
                <?php _e('Take Survey', 'rm-panel-extensions'); ?>
            </a>
        </article>
        <?php
    }

    /**
     * Register WPML strings
     */
    private function register_wpml_strings() {
        if (!function_exists('icl_register_string')) {
            return;
        }

        // Register default strings for WPML
        $strings = [
            // Login widget strings
            'username_label' => __('Username or Email', 'rm-panel-extensions'),
            'username_placeholder' => __('Enter your username or email', 'rm-panel-extensions'),
            'password_label' => __('Password', 'rm-panel-extensions'),
            'password_placeholder' => __('Enter your password', 'rm-panel-extensions'),
            'remember_text' => __('Remember Me', 'rm-panel-extensions'),
            'login_button_text' => __('Login', 'rm-panel-extensions'),
            'register_link_text' => __('Register', 'rm-panel-extensions'),
            'lost_password_text' => __('Lost your password?', 'rm-panel-extensions'),
            'logged_in_message' => __('Welcome, {user}! You are already logged in.', 'rm-panel-extensions'),
            'logout_link_text' => __('Logout', 'rm-panel-extensions'),
            'loading_text' => __('Logging in...', 'rm-panel-extensions'),
            'error_message' => __('An error occurred. Please try again.', 'rm-panel-extensions'),
            'success_message' => __('Login successful! Redirecting...', 'rm-panel-extensions'),
            'empty_fields' => __('Please enter username and password.', 'rm-panel-extensions'),
            'security_error' => __('Security check failed. Please refresh and try again.', 'rm-panel-extensions'),
            // Survey widget strings
            'take_survey' => __('Take Survey', 'rm-panel-extensions'),
            'no_surveys_found' => __('No surveys found.', 'rm-panel-extensions'),
            'load_more' => __('Load More', 'rm-panel-extensions'),
            'survey_status_active' => __('Active', 'rm-panel-extensions'),
            'survey_status_draft' => __('Draft', 'rm-panel-extensions'),
            'survey_status_paused' => __('Paused', 'rm-panel-extensions'),
            'survey_status_closed' => __('Closed', 'rm-panel-extensions'),
        ];

        foreach ($strings as $key => $value) {
            icl_register_string('rm-panel-extensions', $key, $value);
        }
    }

    /**
     * Handle AJAX login
     */
    public function handle_login() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rm_panel_login_nonce')) {
            wp_send_json_error([
                'message' => $this->get_translated_string('security_error', __('Security check failed. Please refresh and try again.', 'rm-panel-extensions'))
            ]);
        }

        // Sanitize input
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) && $_POST['remember'] == '1' ? true : false;

        // Get redirect URLs
        $redirect_urls = json_decode(stripslashes($_POST['redirect_urls']), true);
        $default_redirect = esc_url_raw($_POST['default_redirect']);

        // Validate input
        if (empty($username) || empty($password)) {
            wp_send_json_error([
                'message' => $this->get_translated_string('empty_fields', __('Please enter username and password.', 'rm-panel-extensions'))
            ]);
        }

        // Check if username is email and validate
        if (is_email($username)) {
            $user = get_user_by('email', $username);
            if ($user) {
                $username = $user->user_login;
            }
        }

        // Try to login
        $credentials = [
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember
        ];

        // Allow plugins to modify credentials
        $credentials = apply_filters('rm_panel_login_credentials', $credentials);

        // Perform login
        $user = wp_signon($credentials, is_ssl());

        if (is_wp_error($user)) {
            // Login failed
            do_action('rm_panel_login_failed', $username, $user);

            wp_send_json_error([
                'message' => $this->get_user_friendly_error($user->get_error_code(), $user->get_error_message())
            ]);
        } else {
            // Login successful
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, $remember);

            do_action('rm_panel_login_success', $user);

            // Determine redirect URL based on user role
            $redirect_url = $this->get_redirect_url($user, $redirect_urls, $default_redirect);

            // Apply WPML filter to redirect URL
            if (function_exists('icl_object_id')) {
                $redirect_url = apply_filters('wpml_permalink', $redirect_url);
            }

            // Allow plugins to modify redirect URL
            $redirect_url = apply_filters('rm_panel_login_redirect', $redirect_url, $user);

            wp_send_json_success([
                'message' => $this->get_translated_string('success_message', __('Login successful! Redirecting...', 'rm-panel-extensions')),
                'redirect' => $redirect_url,
                'user' => [
                    'ID' => $user->ID,
                    'display_name' => $user->display_name,
                    'email' => $user->user_email,
                    'roles' => $user->roles
                ]
            ]);
        }
    }

    /**
     * Get user-friendly error message
     */
    private function get_user_friendly_error($error_code, $default_message) {
        $messages = [
            'empty_username' => __('Please enter your username or email address.', 'rm-panel-extensions'),
            'empty_password' => __('Please enter your password.', 'rm-panel-extensions'),
            'invalid_username' => __('Invalid username or email address.', 'rm-panel-extensions'),
            'invalid_email' => __('Invalid email address.', 'rm-panel-extensions'),
            'incorrect_password' => __('Incorrect password. Please try again.', 'rm-panel-extensions'),
            'authentication_failed' => __('Authentication failed. Please check your credentials.', 'rm-panel-extensions'),
        ];

        if (isset($messages[$error_code])) {
            return $messages[$error_code];
        }

        return $default_message;
    }

    /**
     * Get redirect URL based on user role
     */
    private function get_redirect_url($user, $redirect_urls, $default_redirect) {
        $redirect_url = $default_redirect;

        if (!empty($redirect_urls) && is_array($redirect_urls)) {
            $user_roles = $user->roles;

            // Check each user role for a matching redirect
            foreach ($user_roles as $role) {
                if (isset($redirect_urls[$role]) && !empty($redirect_urls[$role])) {
                    $redirect_url = $redirect_urls[$role];
                    break;
                }
            }
        }

        // If no redirect URL is set, use admin URL
        if (empty($redirect_url)) {
            $redirect_url = admin_url();
        }

        return esc_url_raw($redirect_url);
    }

    /**
     * Get translated string
     */
    private function get_translated_string($key, $default) {
        if (function_exists('icl_t')) {
            return icl_t('rm-panel-extensions', $key, $default);
        }
        return $default;
    }

    /**
     * Get module info
     */
    public static function get_info() {
        return [
            'name' => __('Elementor Module', 'rm-panel-extensions'),
            'description' => __('Adds custom Elementor widgets including login forms, survey listings, and survey accordion.', 'rm-panel-extensions'),
            'version' => '1.1.0',
            'author' => 'RM Panel',
            'requires' => [
                'elementor' => '3.0.0'
            ]
        ];
    }
}
