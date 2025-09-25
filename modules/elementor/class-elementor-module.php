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
     * Load modules
     */
    private function load_modules() {
// Core modules to load
        $core_modules = [
            'elementor-widgets' => 'RM_Panel_Elementor_Module',
            'survey' => 'RM_Panel_Survey_Module', // Add this line
        ];

// Load Survey module first (it's independent)
        $survey_module_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/survey/class-survey-module.php';
        if (file_exists($survey_module_file)) {
            require_once $survey_module_file;
        }

// Check if module file exists before requiring
        $elementor_module_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/elementor/class-elementor-module.php';

// Load Elementor module if Elementor is active and module file exists
        if (did_action('elementor/loaded')) {
            if (file_exists($elementor_module_file)) {
                require_once $elementor_module_file;
            } else {
                add_action('admin_notices', [$this, 'module_missing_notice']);
                return;
            }
        }

// Allow filtering of modules
        $this->modules = apply_filters('rm_panel_extensions_modules', $core_modules);
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
// This is a simplified version - you would include the full template
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
            'description' => __('Adds custom Elementor widgets including login forms and survey listings.', 'rm-panel-extensions'),
            'version' => '1.1.0',
            'author' => 'RM Panel',
            'requires' => [
                'elementor' => '3.0.0'
            ]
        ];
    }
}

/**
 * RM Panel Survey Accordion Widget for Elementor
 * 
 * @package RM_Panel_Extensions
 * @subpackage Modules/Elementor/Widgets
 */
if (!defined('ABSPATH')) {
    exit;
}

class RM_Panel_Survey_Accordion_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'rm_panel_survey_accordion';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('Survey Accordion', 'rm-panel-extensions');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-accordion';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['rm-panel-widgets', 'general'];
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return ['survey', 'accordion', 'collapse', 'expand', 'rm panel', 'questionnaire', 'forms'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {

// Query Section
        $this->start_controls_section(
                'query_section',
                [
                    'label' => __('Query', 'rm-panel-extensions'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
        );

        $this->add_control(
                'posts_per_page',
                [
                    'label' => __('Number of Surveys', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'default' => -1,
                    'min' => -1,
                    'description' => __('Use -1 to show all surveys', 'rm-panel-extensions'),
                ]
        );

        $this->add_control(
                'orderby',
                [
                    'label' => __('Order By', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'date',
                    'options' => [
                        'date' => __('Date', 'rm-panel-extensions'),
                        'title' => __('Title', 'rm-panel-extensions'),
                        'menu_order' => __('Menu Order', 'rm-panel-extensions'),
                        'start_date' => __('Start Date', 'rm-panel-extensions'),
                        'end_date' => __('End Date', 'rm-panel-extensions'),
                    ],
                ]
        );

        $this->add_control(
                'order',
                [
                    'label' => __('Order', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'DESC',
                    'options' => [
                        'ASC' => __('Ascending', 'rm-panel-extensions'),
                        'DESC' => __('Descending', 'rm-panel-extensions'),
                    ],
                ]
        );

        $this->add_control(
                'survey_status_filter',
                [
                    'label' => __('Filter by Status', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SELECT2,
                    'multiple' => true,
                    'options' => [
                        'draft' => __('Draft', 'rm-panel-extensions'),
                        'active' => __('Active', 'rm-panel-extensions'),
                        'paused' => __('Paused', 'rm-panel-extensions'),
                        'closed' => __('Closed', 'rm-panel-extensions'),
                    ],
                    'default' => ['active'],
                ]
        );

// Get survey categories
        $categories = get_terms([
            'taxonomy' => 'survey_category',
            'hide_empty' => false,
                ]);

        $category_options = [];
        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                $category_options[$category->term_id] = $category->name;
            }
        }

        $this->add_control(
                'categories',
                [
                    'label' => __('Filter by Categories', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SELECT2,
                    'multiple' => true,
                    'options' => $category_options,
                    'default' => [],
                ]
        );

        $this->end_controls_section();

// Display Settings
        $this->start_controls_section(
                'display_section',
                [
                    'label' => __('Display Settings', 'rm-panel-extensions'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
        );

        $this->add_control(
                'first_item_expanded',
                [
                    'label' => __('First Item Expanded', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                    'description' => __('Expand the first survey by default', 'rm-panel-extensions'),
                ]
        );

        $this->add_control(
                'toggle_icon',
                [
                    'label' => __('Toggle Icon', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::ICONS,
                    'default' => [
                        'value' => 'fas fa-chevron-down',
                        'library' => 'fa-solid',
                    ],
                ]
        );

        $this->add_control(
                'toggle_icon_active',
                [
                    'label' => __('Toggle Icon Active', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::ICONS,
                    'default' => [
                        'value' => 'fas fa-chevron-up',
                        'library' => 'fa-solid',
                    ],
                ]
        );

        $this->add_control(
                'allow_multiple_expanded',
                [
                    'label' => __('Allow Multiple Expanded', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'no',
                    'description' => __('Allow multiple surveys to be expanded at once', 'rm-panel-extensions'),
                ]
        );

        $this->end_controls_section();

// Content Display
        $this->start_controls_section(
                'content_display_section',
                [
                    'label' => __('Content Display', 'rm-panel-extensions'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
        );

        $this->add_control(
                'show_status_badge',
                [
                    'label' => __('Show Status Badge', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'show_category',
                [
                    'label' => __('Show Category', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'show_thumbnail',
                [
                    'label' => __('Show Thumbnail', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'show_dates',
                [
                    'label' => __('Show Dates', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'show_questions_count',
                [
                    'label' => __('Show Questions Count', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'show_estimated_time',
                [
                    'label' => __('Show Estimated Time', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'show_payment_info',
                [
                    'label' => __('Show Payment Info', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'show_target_audience',
                [
                    'label' => __('Show Target Audience', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'show_take_survey_button',
                [
                    'label' => __('Show Take Survey Button', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'button_text',
                [
                    'label' => __('Button Text', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => __('Take Survey', 'rm-panel-extensions'),
                    'condition' => [
                        'show_take_survey_button' => 'yes',
                    ],
                ]
        );

        $this->end_controls_section();

// Style Controls
        $this->register_style_controls();
    }

    /**
     * Register style controls
     */
    private function register_style_controls() {
// Accordion Item Style
        $this->start_controls_section(
                'accordion_style',
                [
                    'label' => __('Accordion Item', 'rm-panel-extensions'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_control(
                'item_background',
                [
                    'label' => __('Background Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#ffffff',
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-accordion-item' => 'background-color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name' => 'item_border',
                    'selector' => '{{WRAPPER}} .rm-survey-accordion-item',
                ]
        );

        $this->add_control(
                'item_border_radius',
                [
                    'label' => __('Border Radius', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-accordion-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
        );

        $this->add_responsive_control(
                'item_spacing',
                [
                    'label' => __('Item Spacing', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px'],
                    'range' => [
                        'px' => [
                            'min' => 0,
                            'max' => 50,
                        ],
                    ],
                    'default' => [
                        'size' => 10,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-accordion-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                    ],
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Box_Shadow::get_type(),
                [
                    'name' => 'item_box_shadow',
                    'selector' => '{{WRAPPER}} .rm-survey-accordion-item',
                ]
        );

        $this->end_controls_section();

// Header Style
        $this->start_controls_section(
                'header_style',
                [
                    'label' => __('Accordion Header', 'rm-panel-extensions'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_control(
                'header_background',
                [
                    'label' => __('Background Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#f8f9fa',
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-accordion-header' => 'background-color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_control(
                'header_hover_background',
                [
                    'label' => __('Hover Background Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#e9ecef',
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-accordion-header:hover' => 'background-color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_control(
                'header_active_background',
                [
                    'label' => __('Active Background Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#007cba',
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-accordion-item.active .rm-survey-accordion-header' => 'background-color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_responsive_control(
                'header_padding',
                [
                    'label' => __('Padding', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'default' => [
                        'top' => '15',
                        'right' => '20',
                        'bottom' => '15',
                        'left' => '20',
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-accordion-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
        );

        $this->end_controls_section();

// Title Style
        $this->start_controls_section(
                'title_style',
                [
                    'label' => __('Title', 'rm-panel-extensions'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'title_typography',
                    'selector' => '{{WRAPPER}} .rm-survey-accordion-title',
                ]
        );

        $this->add_control(
                'title_color',
                [
                    'label' => __('Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#333333',
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-accordion-title' => 'color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_control(
                'title_active_color',
                [
                    'label' => __('Active Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#ffffff',
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-accordion-item.active .rm-survey-accordion-title' => 'color: {{VALUE}};',
                    ],
                ]
        );

        $this->end_controls_section();

// Content Style
        $this->start_controls_section(
                'content_style',
                [
                    'label' => __('Content', 'rm-panel-extensions'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_control(
                'content_background',
                [
                    'label' => __('Background Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#ffffff',
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-accordion-content' => 'background-color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_responsive_control(
                'content_padding',
                [
                    'label' => __('Padding', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'default' => [
                        'top' => '20',
                        'right' => '20',
                        'bottom' => '20',
                        'left' => '20',
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-accordion-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'content_typography',
                    'selector' => '{{WRAPPER}} .rm-survey-accordion-content',
                ]
        );

        $this->add_control(
                'content_color',
                [
                    'label' => __('Text Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#666666',
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-accordion-content' => 'color: {{VALUE}};',
                    ],
                ]
        );

        $this->end_controls_section();

// Button Style
        $this->start_controls_section(
                'button_style',
                [
                    'label' => __('Button', 'rm-panel-extensions'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'show_take_survey_button' => 'yes',
                    ],
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'button_typography',
                    'selector' => '{{WRAPPER}} .rm-survey-button',
                ]
        );

        $this->start_controls_tabs('button_style_tabs');

        $this->start_controls_tab(
                'button_normal_tab',
                [
                    'label' => __('Normal', 'rm-panel-extensions'),
                ]
        );

        $this->add_control(
                'button_text_color',
                [
                    'label' => __('Text Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#ffffff',
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-button' => 'color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_control(
                'button_background',
                [
                    'label' => __('Background Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#007cba',
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-button' => 'background-color: {{VALUE}};',
                    ],
                ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
                'button_hover_tab',
                [
                    'label' => __('Hover', 'rm-panel-extensions'),
                ]
        );

        $this->add_control(
                'button_hover_text_color',
                [
                    'label' => __('Text Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-button:hover' => 'color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_control(
                'button_hover_background',
                [
                    'label' => __('Background Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#005a87',
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-button:hover' => 'background-color: {{VALUE}};',
                    ],
                ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
                'button_border_radius',
                [
                    'label' => __('Border Radius', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'separator' => 'before',
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
        );

        $this->add_responsive_control(
                'button_padding',
                [
                    'label' => __('Padding', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

// Build query arguments
        $query_args = $this->build_query_args($settings);

// Get surveys
        $surveys = new WP_Query($query_args);

        if (!$surveys->have_posts()) {
            echo '<p>' . __('No surveys found.', 'rm-panel-extensions') . '</p>';
            return;
        }

        $widget_id = $this->get_id();
        ?>
        <div class="rm-survey-accordion" id="rm-accordion-<?php echo esc_attr($widget_id); ?>" data-allow-multiple="<?php echo esc_attr($settings['allow_multiple_expanded']); ?>">
        <?php
        $index = 0;
        while ($surveys->have_posts()) :
            $surveys->the_post();
            $this->render_survey_accordion_item($settings, $index, $widget_id);
            $index++;
        endwhile;
        ?>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                var accordionId = '#rm-accordion-<?php echo esc_js($widget_id); ?>';
                var allowMultiple = $(accordionId).data('allow-multiple') === 'yes';

                // Toggle accordion items
                $(accordionId + ' .rm-survey-accordion-header').on('click', function (e) {
                    e.preventDefault();
                    var $item = $(this).closest('.rm-survey-accordion-item');
                    var $content = $item.find('.rm-survey-accordion-content');
                    var $accordion = $(this).closest('.rm-survey-accordion');

                    if (!allowMultiple) {
                        // Close other items if multiple not allowed
                        $accordion.find('.rm-survey-accordion-item').not($item).removeClass('active');
                        $accordion.find('.rm-survey-accordion-content').not($content).slideUp(300);
                    }

                    // Toggle current item
                    $item.toggleClass('active');
                    $content.slideToggle(300);

                    // Update icon
                    var $icon = $(this).find('.rm-accordion-toggle-icon');
                    if ($item.hasClass('active')) {
                        $icon.removeClass('<?php echo esc_js($settings['toggle_icon']['value']); ?>');
                        $icon.addClass('<?php echo esc_js($settings['toggle_icon_active']['value']); ?>');
                    } else {
                        $icon.removeClass('<?php echo esc_js($settings['toggle_icon_active']['value']); ?>');
                        $icon.addClass('<?php echo esc_js($settings['toggle_icon']['value']); ?>');
                    }
                });

                // Prevent button clicks from toggling accordion
                $(accordionId + ' .rm-survey-button').on('click', function (e) {
                    e.stopPropagation();
                });
            });
        </script>
        <?php
        wp_reset_postdata();
    }

    /**
     * Build query arguments
     */
    private function build_query_args($settings) {
        $args = [
            'post_type' => 'rm_survey',
            'posts_per_page' => $settings['posts_per_page'],
            'orderby' => $settings['orderby'],
            'order' => $settings['order'],
            'post_status' => 'publish',
        ];

// Handle custom orderby
        if (in_array($settings['orderby'], ['start_date', 'end_date'])) {
            $args['meta_key'] = '_rm_survey_' . $settings['orderby'];
            $args['orderby'] = 'meta_value';
        }

// Category filter
        if (!empty($settings['categories'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'survey_category',
                    'field' => 'term_id',
                    'terms' => $settings['categories'],
                ],
            ];
        }

// Status filter
        if (!empty($settings['survey_status_filter'])) {
            $args['meta_query'][] = [
                'key' => '_rm_survey_status',
                'value' => $settings['survey_status_filter'],
                'compare' => 'IN',
            ];
        }

        return $args;
    }

    /**
     * Render single accordion item
     */
    private function render_survey_accordion_item($settings, $index, $widget_id) {
        $post_id = get_the_ID();

// Get meta data
        $status = get_post_meta($post_id, '_rm_survey_status', true);
        $survey_type = get_post_meta($post_id, '_rm_survey_type', true);
        $survey_amount = get_post_meta($post_id, '_rm_survey_amount', true);
        $survey_url = get_post_meta($post_id, '_rm_survey_url', true);
        $parameters = get_post_meta($post_id, '_rm_survey_parameters', true);
        $duration_type = get_post_meta($post_id, '_rm_survey_duration_type', true);
        $start_date = get_post_meta($post_id, '_rm_survey_start_date', true);
        $end_date = get_post_meta($post_id, '_rm_survey_end_date', true);
        $questions_count = get_post_meta($post_id, '_rm_survey_questions_count', true);
        $estimated_time = get_post_meta($post_id, '_rm_survey_estimated_time', true);
        $target_audience = get_post_meta($post_id, '_rm_survey_target_audience', true);

        $is_active = ( $settings['first_item_expanded'] === 'yes' && $index === 0 ) ? 'active' : '';
        $display = ( $settings['first_item_expanded'] === 'yes' && $index === 0 ) ? 'block' : 'none';

// Build survey URL with parameters
        $final_survey_url = $survey_url;
        if (is_user_logged_in() && !empty($parameters) && !empty($survey_url)) {
            $current_user = wp_get_current_user();
            $query_params = [];

            foreach ($parameters as $param) {
                $value = '';

                switch ($param['field']) {
                    case 'user_id':
                        $value = $current_user->ID;
                        break;
                    case 'username':
                        $value = $current_user->user_login;
                        break;
                    case 'email':
                        $value = $current_user->user_email;
                        break;
                    case 'first_name':
                        $value = $current_user->first_name;
                        break;
                    case 'last_name':
                        $value = $current_user->last_name;
                        break;
                    case 'display_name':
                        $value = $current_user->display_name;
                        break;
                    case 'user_role':
                        $value = implode(',', $current_user->roles);
                        break;
                    case 'custom':
                        $value = $param['custom_value'];
                        break;
                }

                if (!empty($value) && !empty($param['variable'])) {
                    $query_params[$param['variable']] = $value;
                }
            }

            if (!empty($query_params)) {
                $final_survey_url = add_query_arg($query_params, $survey_url);
            }
        }
        ?>
        <div class="rm-survey-accordion-item <?php echo esc_attr($is_active); ?>">
            <div class="rm-survey-accordion-header">
                <div class="rm-survey-accordion-header-content">
                    <h3 class="rm-survey-accordion-title"><?php the_title(); ?></h3>

                    <div class="rm-survey-accordion-meta">
        <?php if ($settings['show_status_badge'] === 'yes' && $status) : ?>
                            <span class="rm-survey-status-badge status-<?php echo esc_attr($status); ?>">
                            <?php echo esc_html(ucfirst($status)); ?>
                            </span>
                            <?php endif; ?>

                        <?php if ($survey_type === 'paid' && $survey_amount) : ?>
                            <span class="rm-survey-amount-badge">
                                $<?php echo number_format($survey_amount, 2); ?>
                            </span>
        <?php endif; ?>

                        <?php if ($settings['show_questions_count'] === 'yes' && $questions_count) : ?>
                            <span class="rm-survey-meta-item">
                                <i class="eicon-editor-list-ul"></i>
                            <?php echo sprintf(_n('%s Question', '%s Questions', $questions_count, 'rm-panel-extensions'), $questions_count); ?>
                            </span>
                            <?php endif; ?>

                        <?php if ($settings['show_estimated_time'] === 'yes' && $estimated_time) : ?>
                            <span class="rm-survey-meta-item">
                                <i class="eicon-clock"></i>
                            <?php echo sprintf(_n('%s Min', '%s Mins', $estimated_time, 'rm-panel-extensions'), $estimated_time); ?>
                            </span>
                            <?php endif; ?>
                    </div>
                </div>

                <span class="rm-accordion-toggle-icon <?php echo esc_attr($is_active ? $settings['toggle_icon_active']['value'] : $settings['toggle_icon']['value']); ?>"></span>
            </div>

            <div class="rm-survey-accordion-content" style="display: <?php echo $display; ?>;">
                <div class="rm-survey-content-wrapper">
        <?php if ($settings['show_thumbnail'] === 'yes' && has_post_thumbnail()) : ?>
                        <div class="rm-survey-thumbnail">
                        <?php the_post_thumbnail('medium'); ?>
                        </div>
                        <?php endif; ?>

                    <div class="rm-survey-details">
                    <?php if ($settings['show_category'] === 'yes') : ?>
            <?php $categories = get_the_terms($post_id, 'survey_category'); ?>
                            <?php if ($categories && !is_wp_error($categories)) : ?>
                                <div class="rm-survey-categories">
                                <?php foreach ($categories as $category) : ?>
                                        <span class="rm-survey-category">
                                        <?php echo esc_html($category->name); ?>
                                        </span>
                                        <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>

                        <div class="rm-survey-description">
                        <?php the_excerpt(); ?>
                        </div>

                        <div class="rm-survey-info-grid">
        <?php if ($settings['show_dates'] === 'yes' && $duration_type === 'date_range') : ?>
            <?php if ($start_date || $end_date) : ?>
                                    <div class="rm-survey-info-item">
                                        <strong><?php _e('Duration:', 'rm-panel-extensions'); ?></strong>
                                    <?php if ($start_date && $end_date) : ?>
                    <?php echo date('M j, Y', strtotime($start_date)); ?> - <?php echo date('M j, Y', strtotime($end_date)); ?>
                                        <?php elseif ($start_date) : ?>
                                            <?php _e('Starts:', 'rm-panel-extensions'); ?> <?php echo date('M j, Y', strtotime($start_date)); ?>
                                        <?php elseif ($end_date) : ?>
                                            <?php _e('Ends:', 'rm-panel-extensions'); ?> <?php echo date('M j, Y', strtotime($end_date)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                            <?php if ($settings['show_payment_info'] === 'yes' && $survey_type === 'paid' && $survey_amount) : ?>
                                <div class="rm-survey-info-item">
                                    <strong><?php _e('Compensation:', 'rm-panel-extensions'); ?></strong>
                                    $<?php echo number_format($survey_amount, 2); ?>
                                </div>
        <?php endif; ?>

                            <?php if ($settings['show_target_audience'] === 'yes' && $target_audience) : ?>
                                <div class="rm-survey-info-item">
                                    <strong><?php _e('Target Audience:', 'rm-panel-extensions'); ?></strong>
                                <?php echo esc_html($target_audience); ?>
                                </div>
                                <?php endif; ?>
                        </div>

                            <?php if ($settings['show_take_survey_button'] === 'yes' && $final_survey_url) : ?>
                            <div class="rm-survey-action">
                                <a href="<?php echo esc_url($final_survey_url); ?>" class="rm-survey-button" target="_blank">
                            <?php echo esc_html($settings['button_text']); ?>
                                    <i class="fas fa-external-link-alt" style="margin-left: 5px;"></i>
                                </a>
                            </div>
        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
