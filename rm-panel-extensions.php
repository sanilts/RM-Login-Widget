<?php
/**
 * Plugin Name: RM Panel Extensions
 * Description: A comprehensive suite of extensions for WordPress including custom Elementor widgets, role management, and more
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: rm-panel-extensions
 * Domain Path: /languages
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants - These will work regardless of folder name
define('RM_PANEL_EXT_VERSION', '1.0.0');
define('RM_PANEL_EXT_FILE', __FILE__);
define('RM_PANEL_EXT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RM_PANEL_EXT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RM_PANEL_EXT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class RM_Panel_Extensions {

    /**
     * Instance
     */
    private static $instance = null;

    /**
     * Modules
     */
    private $modules = [];

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->check_requirements();
        $this->init_hooks();
    }

    /**
     * Check plugin requirements
     */
    private function check_requirements() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.0', '<')) {
            add_action('admin_notices', function () {
                ?>
                <div class="notice notice-error">
                    <p><?php _e('RM Panel Extensions requires PHP 7.0 or higher.', 'rm-panel-extensions'); ?></p>
                </div>
                <?php
            });
            return false;
        }

        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            add_action('admin_notices', function () {
                ?>
                <div class="notice notice-error">
                    <p><?php _e('RM Panel Extensions requires WordPress 5.0 or higher.', 'rm-panel-extensions'); ?></p>
                </div>
                <?php
            });
            return false;
        }

        return true;
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Load plugin textdomain
        add_action('plugins_loaded', [$this, 'load_textdomain']);

        // Initialize modules
        add_action('init', [$this, 'init_modules'], 5); // Priority 5 to ensure it runs early
        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // Plugin action links
        add_filter('plugin_action_links_' . RM_PANEL_EXT_PLUGIN_BASENAME, [$this, 'add_action_links']);

        // Admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);

        // Frontend scripts for survey tracking
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('rm-panel-extensions', false, dirname(RM_PANEL_EXT_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Initialize modules
     */
    public function init_modules() {
        // Load module files
        $this->load_modules();

        // Initialize Survey module first (doesn't depend on anything)
        if (isset($this->modules['survey']) && class_exists($this->modules['survey'])) {
            new $this->modules['survey']();
        }

        // Initialize Survey Tracking module
        if (isset($this->modules['survey-tracking']) && class_exists($this->modules['survey-tracking'])) {
            new $this->modules['survey-tracking']();
        }

        // Initialize Elementor module if Elementor is active
        if (did_action('elementor/loaded')) {
            if (isset($this->modules['elementor-widgets']) && class_exists($this->modules['elementor-widgets'])) {
                new $this->modules['elementor-widgets']();
            }
        }

        // Initialize Fluent Forms module if Fluent Forms is active
        // FIXED: Use singleton pattern instead of direct instantiation
        if (defined('FLUENTFORM') || function_exists('wpFluentForm')) {
            if (class_exists('RM_Panel_Fluent_Forms_Module')) {
                RM_Panel_Fluent_Forms_Module::get_instance();
            }
        }

        // Fire action for external modules
        do_action('rm_panel_extensions_modules_loaded');
    }

    /**
     * Load modules
     */
    private function load_modules() {
        // Core modules to load
        $core_modules = [];

        // Load Survey module (independent of other modules)
        $survey_module_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/survey/class-survey-module.php';
        if (file_exists($survey_module_file)) {
            require_once $survey_module_file;
            $core_modules['survey'] = 'RM_Panel_Survey_Module';
        }

        // Load Fluent Forms module if Fluent Forms is active
        if (defined('FLUENTFORM') || function_exists('wpFluentForm')) {
            $fluent_forms_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/fluent-forms/class-fluent-forms-module.php';
            if (file_exists($fluent_forms_file)) {
                require_once $fluent_forms_file;
                $core_modules['fluent-forms'] = 'RM_Panel_Fluent_Forms_Module';
            }
        }

        // Load Survey Tracking module
        $survey_tracking_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/survey/class-survey-tracking.php';
        if (file_exists($survey_tracking_file)) {
            require_once $survey_tracking_file;
            $core_modules['survey-tracking'] = 'RM_Panel_Survey_Tracking';
        }

        // Load Elementor module if Elementor is active
        if (did_action('elementor/loaded')) {
            $elementor_module_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/elementor/class-elementor-module.php';
            if (file_exists($elementor_module_file)) {
                require_once $elementor_module_file;
                $core_modules['elementor-widgets'] = 'RM_Panel_Elementor_Module';
            }
        }

        // Load FluentCRM helper if FluentCRM is active
        if (defined('FLUENTCRM') || function_exists('FluentCrmApi')) {
            $fluent_crm_helper_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/fluent-crm/class-fluent-crm-helper.php';
            if (file_exists($fluent_crm_helper_file)) {
                require_once $fluent_crm_helper_file;
            }
        }

        // Load Survey Callbacks module
        $survey_callbacks_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/survey/class-survey-callbacks.php';
        if (file_exists($survey_callbacks_file)) {
            require_once $survey_callbacks_file;
            // Initialize immediately (doesn't need to be in modules array)
            new RM_Survey_Callbacks();
        }

        // Load Profile Picture Handler
        $profile_picture_handler_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/profile-picture/class-profile-picture-handler.php';
        if (file_exists($profile_picture_handler_file)) {
            require_once $profile_picture_handler_file;
        }
        
        // Load Reports Modules - v1.1.0
        $reports_dir = RM_PANEL_EXT_PLUGIN_DIR . 'modules/reports/';

        if (file_exists($reports_dir . 'class-survey-live-monitor.php')) {
            require_once $reports_dir . 'class-survey-live-monitor.php';
        }
        if (file_exists($reports_dir . 'class-survey-reports.php')) {
            require_once $reports_dir . 'class-survey-reports.php';
        }
        if (file_exists($reports_dir . 'class-user-reports.php')) {
            require_once $reports_dir . 'class-user-reports.php';
        }

        // Allow filtering of modules
        $this->modules = apply_filters('rm_panel_extensions_modules', $core_modules);

        // Check if any critical files are missing
        $missing_files = [];

        if (!file_exists($survey_module_file)) {
            $missing_files[] = 'modules/survey/class-survey-module.php';
        }

        if (did_action('elementor/loaded') && !file_exists(RM_PANEL_EXT_PLUGIN_DIR . 'modules/elementor/class-elementor-module.php')) {
            $missing_files[] = 'modules/elementor/class-elementor-module.php';
        }

        if (!empty($missing_files)) {
            add_action('admin_notices', function () use ($missing_files) {
                $this->show_missing_files_notice($missing_files);
            });
        }

        // Load Survey Database Upgrade
        $survey_db_upgrade_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/survey/class-survey-database-upgrade.php';
        if (file_exists($survey_db_upgrade_file)) {
            require_once $survey_db_upgrade_file;
        }

        // Load Survey Approval Admin
        $survey_approval_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/survey/class-survey-approval-admin.php';
        if (file_exists($survey_approval_file)) {
            require_once $survey_approval_file;
        }

        // Load Survey Tabs Shortcode
        $survey_tabs_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/survey/class-survey-tabs-shortcode.php';
        if (file_exists($survey_tabs_file)) {
            require_once $survey_tabs_file;
        }

        $referral_system_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/referral/class-referral-system.php';
        if (file_exists($referral_system_file)) {
            require_once $referral_system_file;
        }

        // Load Admin Bar Manager
        $admin_bar_manager_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/admin-bar/class-admin-bar-manager.php';
        if (file_exists($admin_bar_manager_file)) {
            require_once $admin_bar_manager_file;
        }
    }

    /**
     * Enqueue frontend scripts
     */
    /**
     * CORRECT ENQUEUE CODE FOR PROFILE PICTURE WIDGET
     * 
     * Copy this code into your rm-panel-extensions.php file
     * Replace or update your existing enqueue_frontend_scripts() method
     */

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        // Check if we need to load survey tracking scripts
        $should_load = false;

        // Check if on survey single page or archive
        if (is_singular('rm_survey') || is_post_type_archive('rm_survey')) {
            $should_load = true;
        }

        // Check if current page has the survey history shortcode
        if (!$should_load && is_singular()) {
            global $post;
            if ($post && has_shortcode($post->post_content, 'rm_survey_history')) {
                $should_load = true;
            }
        }

        // Only load scripts if needed
        if ($should_load) {
            // Check if the JavaScript file exists before enqueuing
            $tracking_js_file = RM_PANEL_EXT_PLUGIN_DIR . 'assets/js/survey-tracking.js';
            if (file_exists($tracking_js_file)) {
                // Enqueue survey tracking script
                wp_enqueue_script(
                        'rm-survey-tracking',
                        RM_PANEL_EXT_PLUGIN_URL . 'assets/js/survey-tracking.js',
                        ['jquery'],
                        RM_PANEL_EXT_VERSION,
                        true
                );

                // Localize script with necessary data
                wp_localize_script('rm-survey-tracking', 'rm_survey_ajax', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('rm_survey_nonce'),
                    'dashboard_url' => home_url('/my-dashboard/'),
                    'is_logged_in' => is_user_logged_in(),
                    'user_id' => get_current_user_id()
                ]);
            }

            // Check if CSS file exists before enqueuing
            $tracking_css_file = RM_PANEL_EXT_PLUGIN_DIR . 'assets/css/survey-tracking.css';
            if (file_exists($tracking_css_file)) {
                // Add CSS for survey tracking elements
                wp_enqueue_style(
                        'rm-survey-tracking',
                        RM_PANEL_EXT_PLUGIN_URL . 'assets/css/survey-tracking.css',
                        [],
                        RM_PANEL_EXT_VERSION
                );
            }
        }

        // ================================================================
        // PROFILE PICTURE WIDGET - ADD THIS CODE
        // ================================================================
        // Only load profile picture scripts for logged-in users
        if (is_user_logged_in()) {

            // Enqueue Profile Picture CSS
            $profile_css_file = RM_PANEL_EXT_PLUGIN_DIR . 'assets/css/profile-picture-widget.css';
            if (file_exists($profile_css_file)) {
                wp_enqueue_style(
                        'rm-profile-picture-widget',
                        RM_PANEL_EXT_PLUGIN_URL . 'assets/css/profile-picture-widget.css',
                        [],
                        RM_PANEL_EXT_VERSION
                );
            }

            // Enqueue Profile Picture JavaScript
            $profile_js_file = RM_PANEL_EXT_PLUGIN_DIR . 'assets/js/profile-picture-widget.js';
            if (file_exists($profile_js_file)) {

                // CRITICAL: Enqueue script with jQuery dependency
                wp_enqueue_script(
                        'rm-profile-picture-widget',
                        RM_PANEL_EXT_PLUGIN_URL . 'assets/js/profile-picture-widget.js',
                        ['jquery'], // ← jQuery is REQUIRED
                        RM_PANEL_EXT_VERSION,
                        true // Load in footer
                );

                // CRITICAL: Localize script with AJAX configuration
                // This MUST come AFTER wp_enqueue_script
                wp_localize_script('rm-profile-picture-widget', 'rmProfilePicture', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('rm_profile_picture_nonce')
                ]);

                // Optional: Add inline script for debugging (remove in production)
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    wp_add_inline_script('rm-profile-picture-widget',
                            'console.log("RM Profile Picture: Script enqueued successfully");',
                            'before'
                    );
                }
            } else {
                // Log error if file not found
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('RM Panel: profile-picture-widget.js not found at: ' . $profile_js_file);
                }
            }
        }

        // ================================================================
        // END PROFILE PICTURE WIDGET CODE
        // ================================================================
        // Enqueue survey accordion tabs CSS
        wp_enqueue_style(
                'rm-survey-accordion-tabs',
                RM_PANEL_EXT_PLUGIN_URL . 'assets/css/survey-accordion-tabs.css',
                [],
                RM_PANEL_EXT_VERSION
        );

        // Load Font Awesome for social icons
        wp_enqueue_style(
                'font-awesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
                [],
                '6.0.0'
        );
    }

    /**
     * Helper function to get post content
     */
    private function get_post_content() {
        global $post;
        return isset($post->post_content) ? $post->post_content : '';
    }

    /**
     * Show missing files notice
     */
    private function show_missing_files_notice($missing_files) {
        ?>
        <div class="notice notice-warning">
            <p><strong><?php _e('RM Panel Extensions: Some module files are missing:', 'rm-panel-extensions'); ?></strong></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <?php foreach ($missing_files as $file) : ?>
                    <li><code><?php echo esc_html($file); ?></code></li>
                <?php endforeach; ?>
            </ul>
            <p><?php _e('Please ensure all plugin files are properly uploaded.', 'rm-panel-extensions'); ?></p>
        </div>
        <?php
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
                __('RM Panel Extensions', 'rm-panel-extensions'),
                __('RM Panel Ext', 'rm-panel-extensions'),
                'manage_options',
                'rm-panel-extensions',
                [$this, 'render_admin_page'],
                'dashicons-admin-generic',
                100
        );

        // Add submenu for settings
        add_submenu_page(
                'rm-panel-extensions',
                __('Settings', 'rm-panel-extensions'),
                __('Settings', 'rm-panel-extensions'),
                'manage_options',
                'rm-panel-extensions-settings',
                [$this, 'render_settings_page']
        );

        // Add submenu for modules
        add_submenu_page(
                'rm-panel-extensions',
                __('Modules', 'rm-panel-extensions'),
                __('Modules', 'rm-panel-extensions'),
                'manage_options',
                'rm-panel-extensions-modules',
                [$this, 'render_modules_page']
        );

        // Add submenu for survey responses
        add_submenu_page(
                'rm-panel-extensions',
                __('Survey Responses', 'rm-panel-extensions'),
                __('Survey Responses', 'rm-panel-extensions'),
                'manage_options',
                'rm-panel-survey-responses',
                [$this, 'render_survey_responses_page']
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'rm-panel-extensions') !== false) {
            // Check if CSS file exists before enqueuing
            $css_file = RM_PANEL_EXT_PLUGIN_DIR . 'assets/css/admin.css';
            if (file_exists($css_file)) {
                wp_enqueue_style(
                        'rm-panel-admin',
                        RM_PANEL_EXT_PLUGIN_URL . 'assets/css/admin.css',
                        [],
                        RM_PANEL_EXT_VERSION
                );
            }
        }
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap rm-panel-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="rm-panel-dashboard">
                <div class="rm-panel-welcome">
                    <h2><?php _e('Welcome to RM Panel Extensions', 'rm-panel-extensions'); ?></h2>
                    <p><?php _e('A comprehensive suite of extensions for WordPress to enhance your website functionality.', 'rm-panel-extensions'); ?></p>
                    <div class="rm-panel-version">
                        <span><?php _e('Version:', 'rm-panel-extensions'); ?></span>
                        <strong><?php echo RM_PANEL_EXT_VERSION; ?></strong>
                    </div>
                </div>

                <?php
                // Check if all required files exist
                $required_files = [
                    'modules/survey/class-survey-module.php' => __('Survey Module', 'rm-panel-extensions'),
                    'modules/elementor/class-elementor-module.php' => __('Elementor Module', 'rm-panel-extensions'),
                    'modules/elementor/widgets/login-widget.php' => __('Login Widget', 'rm-panel-extensions'),
                    'modules/elementor/widgets/survey-listing-widget.php' => __('Survey Listing Widget', 'rm-panel-extensions'),
                    'modules/elementor/templates/login-form.php' => __('Login Form Template', 'rm-panel-extensions'),
                    'assets/css/elementor-widgets.css' => __('Widget Styles', 'rm-panel-extensions'),
                    'assets/css/survey-styles.css' => __('Survey Styles', 'rm-panel-extensions'),
                    'assets/js/elementor-widgets.js' => __('Widget Scripts', 'rm-panel-extensions'),
                    'assets/js/survey-scripts.js' => __('Survey Scripts', 'rm-panel-extensions'),
                ];

                $missing_files = [];
                foreach ($required_files as $file => $name) {
                    if (!file_exists(RM_PANEL_EXT_PLUGIN_DIR . $file)) {
                        $missing_files[$file] = $name;
                    }
                }

                if (!empty($missing_files)) :
                    ?>
                    <div class="notice notice-warning" style="margin: 20px 0;">
                        <p><strong><?php _e('Missing Files Detected:', 'rm-panel-extensions'); ?></strong></p>
                        <ul style="list-style: disc; margin-left: 20px;">
                            <?php foreach ($missing_files as $file => $name) : ?>
                                <li><?php echo esc_html($name); ?> - <code><?php echo esc_html($file); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                        <p><?php _e('Please ensure all plugin files are properly uploaded.', 'rm-panel-extensions'); ?></p>
                    </div>
                <?php else : ?>
                    <div class="notice notice-success" style="margin: 20px 0;">
                        <p><?php _e('All required files are present and ready!', 'rm-panel-extensions'); ?></p>
                    </div>
                <?php endif; ?>

                <div class="rm-panel-stats">
                    <div class="rm-panel-stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-admin-plugins"></span>
                        </div>
                        <div class="stat-content">
                            <h3><?php _e('Active Modules', 'rm-panel-extensions'); ?></h3>
                            <p class="stat-number"><?php echo count($this->modules); ?></p>
                        </div>
                    </div>

                    <div class="rm-panel-stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-clipboard"></span>
                        </div>
                        <div class="stat-content">
                            <h3><?php _e('Survey Module', 'rm-panel-extensions'); ?></h3>
                            <p class="stat-status <?php echo class_exists('RM_Panel_Survey_Module') ? 'active' : 'inactive'; ?>">
                                <?php echo class_exists('RM_Panel_Survey_Module') ? __('Active', 'rm-panel-extensions') : __('Inactive', 'rm-panel-extensions'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="rm-panel-stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-admin-customizer"></span>
                        </div>
                        <div class="stat-content">
                            <h3><?php _e('Elementor Status', 'rm-panel-extensions'); ?></h3>
                            <p class="stat-status <?php echo did_action('elementor/loaded') ? 'active' : 'inactive'; ?>">
                                <?php echo did_action('elementor/loaded') ? __('Active', 'rm-panel-extensions') : __('Inactive', 'rm-panel-extensions'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="rm-panel-stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-translation"></span>
                        </div>
                        <div class="stat-content">
                            <h3><?php _e('WPML Status', 'rm-panel-extensions'); ?></h3>
                            <p class="stat-status <?php echo function_exists('icl_object_id') ? 'active' : 'inactive'; ?>">
                                <?php echo function_exists('icl_object_id') ? __('Active', 'rm-panel-extensions') : __('Not Installed', 'rm-panel-extensions'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rm-panel-quick-links">
                    <h3><?php _e('Quick Links', 'rm-panel-extensions'); ?></h3>
                    <div class="quick-links-grid">
                        <a href="<?php echo admin_url('admin.php?page=rm-panel-extensions-settings'); ?>" class="quick-link">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <span><?php _e('Settings', 'rm-panel-extensions'); ?></span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=rm-panel-extensions-modules'); ?>" class="quick-link">
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <span><?php _e('Modules', 'rm-panel-extensions'); ?></span>
                        </a>
                        <?php if (post_type_exists('rm_survey')) : ?>
                            <a href="<?php echo admin_url('edit.php?post_type=rm_survey'); ?>" class="quick-link">
                                <span class="dashicons dashicons-clipboard"></span>
                                <span><?php _e('Surveys', 'rm-panel-extensions'); ?></span>
                            </a>
                            <a href="<?php echo admin_url('edit-tags.php?taxonomy=survey_category&post_type=rm_survey'); ?>" class="quick-link">
                                <span class="dashicons dashicons-category"></span>
                                <span><?php _e('Survey Categories', 'rm-panel-extensions'); ?></span>
                            </a>
                        <?php endif; ?>
                        <?php if (did_action('elementor/loaded')) : ?>
                            <a href="<?php echo admin_url('edit.php?post_type=elementor_library'); ?>" class="quick-link">
                                <span class="dashicons dashicons-admin-page"></span>
                                <span><?php _e('Elementor Templates', 'rm-panel-extensions'); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rm-panel-info">
                    <h3><?php _e('Plugin Information', 'rm-panel-extensions'); ?></h3>
                    <table class="widefat">
                        <tbody>
                            <tr>
                                <td><strong><?php _e('Plugin Directory', 'rm-panel-extensions'); ?></strong></td>
                                <td><code><?php echo RM_PANEL_EXT_PLUGIN_DIR; ?></code></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Plugin URL', 'rm-panel-extensions'); ?></strong></td>
                                <td><code><?php echo RM_PANEL_EXT_PLUGIN_URL; ?></code></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('PHP Version', 'rm-panel-extensions'); ?></strong></td>
                                <td><?php echo PHP_VERSION; ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('WordPress Version', 'rm-panel-extensions'); ?></strong></td>
                                <td><?php echo get_bloginfo('version'); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Active Modules', 'rm-panel-extensions'); ?></strong></td>
                                <td><?php echo implode(', ', array_keys($this->modules)); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Save settings if form is submitted
        if (isset($_POST['rm_panel_settings_nonce']) && wp_verify_nonce($_POST['rm_panel_settings_nonce'], 'rm_panel_settings')) {
            $this->save_settings();
        }

        $settings = get_option('rm_panel_extensions_settings', $this->get_default_settings());
        ?>
        <div class="wrap">
            <h1><?php _e('RM Panel Extensions Settings', 'rm-panel-extensions'); ?></h1>

            <?php settings_errors('rm_panel_settings'); ?>

            <form method="post" action="">
                <?php wp_nonce_field('rm_panel_settings', 'rm_panel_settings_nonce'); ?>

                <h2><?php _e('Module Settings', 'rm-panel-extensions'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row" colspan="2">
                            <h3><?php _e('Survey Module', 'rm-panel-extensions'); ?></h3>
                        </th>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="enable_survey_module">
                                <?php _e('Enable Survey Module', 'rm-panel-extensions'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" name="rm_panel_settings[enable_survey_module]" id="enable_survey_module" value="1" 
                                   <?php checked(isset($settings['enable_survey_module']) ? $settings['enable_survey_module'] : 1); ?>>
                            <p class="description"><?php _e('Enable the Survey custom post type and functionality', 'rm-panel-extensions'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row" colspan="2">
                            <h3><?php _e('Elementor Widgets', 'rm-panel-extensions'); ?></h3>
                        </th>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="enable_login_widget">
                                <?php _e('Enable Login Widget', 'rm-panel-extensions'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" name="rm_panel_settings[enable_login_widget]" id="enable_login_widget" value="1" 
                                   <?php checked(isset($settings['enable_login_widget']) ? $settings['enable_login_widget'] : 1); ?>>
                            <p class="description"><?php _e('Enable the custom login widget for Elementor', 'rm-panel-extensions'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="enable_survey_widget">
                                <?php _e('Enable Survey Listing Widget', 'rm-panel-extensions'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" name="rm_panel_settings[enable_survey_widget]" id="enable_survey_widget" value="1" 
                                   <?php checked(isset($settings['enable_survey_widget']) ? $settings['enable_survey_widget'] : 1); ?>>
                            <p class="description"><?php _e('Enable the survey listing widget for Elementor', 'rm-panel-extensions'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="enable_wpml_support">
                                <?php _e('Enable WPML Support', 'rm-panel-extensions'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" name="rm_panel_settings[enable_wpml_support]" id="enable_wpml_support" value="1" 
                                   <?php checked(isset($settings['enable_wpml_support']) ? $settings['enable_wpml_support'] : 1); ?>>
                            <p class="description"><?php _e('Enable WPML translation support for widgets', 'rm-panel-extensions'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="custom_widget_category">
                                <?php _e('Custom Widget Category', 'rm-panel-extensions'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" name="rm_panel_settings[custom_widget_category]" id="custom_widget_category" 
                                   value="<?php echo esc_attr(isset($settings['custom_widget_category']) ? $settings['custom_widget_category'] : 'RM Panel Widgets'); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('Category name for custom widgets in Elementor', 'rm-panel-extensions'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ipstack_api_key">
                                <?php _e('IPStack API Key', 'rm-panel-extensions'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" name="rm_panel_settings[ipstack_api_key]" id="ipstack_api_key" 
                                   value="<?php echo esc_attr(get_option('rm_panel_ipstack_api_key', '')); ?>" 
                                   class="regular-text">
                            <p class="description">
                                <?php _e('Get your free API key from <a href="https://ipstack.com" target="_blank">ipstack.com</a> for country detection', 'rm-panel-extensions'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="enable_profile_picture_widget">
                                <?php _e('Enable Profile Picture Widget', 'rm-panel-extensions'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   name="rm_panel_settings[enable_profile_picture_widget]" 
                                   id="enable_profile_picture_widget" 
                                   value="1" 
                                   <?php checked(isset($settings['enable_profile_picture_widget']) ? $settings['enable_profile_picture_widget'] : 1); ?>>
                            <p class="description">
                                <?php _e('Enable the profile picture upload widget for Elementor', 'rm-panel-extensions'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- ADMIN BAR MANAGEMENT SECTION - NEW -->
                <h2><?php _e('Admin Bar Visibility', 'rm-panel-extensions'); ?></h2>
                <p class="description">
                    <?php _e('Control which user roles can see the WordPress admin bar on the frontend and backend.', 'rm-panel-extensions'); ?>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row" colspan="2">
                            <h3><?php _e('Show Admin Bar for These Roles', 'rm-panel-extensions'); ?></h3>
                            <p class="description">
                                <?php _e('Check the roles that should see the WordPress admin bar. Unchecked roles will not see the admin bar.', 'rm-panel-extensions'); ?>
                            </p>
                        </th>
                    </tr>

                    <?php
                    // Get admin bar settings
                    $admin_bar_settings = get_option('rm_panel_admin_bar_settings', RM_Panel_Admin_Bar_Manager::get_default_settings());

                    // Get all WordPress roles
                    $all_roles = RM_Panel_Admin_Bar_Manager::get_all_roles();

                    foreach ($all_roles as $role_key => $role_data) :
                        $is_checked = isset($admin_bar_settings[$role_key]) && $admin_bar_settings[$role_key] === '1';
                        ?>
                        <tr>
                            <th scope="row">
                                <label for="admin_bar_<?php echo esc_attr($role_key); ?>">
                                    <?php echo esc_html($role_data['display_name']); ?>
                                </label>
                            </th>
                            <td>
                                <input type="checkbox" 
                                       name="rm_panel_admin_bar[<?php echo esc_attr($role_key); ?>]" 
                                       id="admin_bar_<?php echo esc_attr($role_key); ?>" 
                                       value="1" 
                                       <?php checked($is_checked); ?>>
                                <p class="description">
                                    <?php
                                    if ($role_key === 'administrator') {
                                        _e('Recommended: Keep enabled for administrators', 'rm-panel-extensions');
                                    } else {
                                        printf(__('Allow %s to see the admin bar', 'rm-panel-extensions'), esc_html($role_data['display_name']));
                                    }
                                    ?>
                                </p>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <tr>
                        <th scope="row" colspan="2">
                            <button type="button" id="rm-admin-bar-reset" class="button">
                                <?php _e('Reset to Defaults', 'rm-panel-extensions'); ?>
                            </button>
                            <p class="description">
                                <?php _e('Reset admin bar visibility to default settings (only administrators can see admin bar)', 'rm-panel-extensions'); ?>
                            </p>
                        </th>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="sync_profile_to_fluentcrm">
                                <?php _e('Sync Profile Pictures to FluentCRM', 'rm-panel-extensions'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   name="rm_panel_settings[sync_profile_to_fluentcrm]" 
                                   id="sync_profile_to_fluentcrm" 
                                   value="1" 
                                   <?php checked(isset($settings['sync_profile_to_fluentcrm']) ? $settings['sync_profile_to_fluentcrm'] : 1); ?>>
                            <p class="description">
                                <?php _e('Automatically sync uploaded profile pictures to FluentCRM contact avatars', 'rm-panel-extensions'); ?>
                            </p>
                            <?php if (!defined('FLUENTCRM')) : ?>
                                <p class="description" style="color: #d63638;">
                                    <?php _e('⚠️ FluentCRM is not active. This feature requires FluentCRM.', 'rm-panel-extensions'); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <script type="text/javascript">
                    jQuery(document).ready(function ($) {
                        // Reset to defaults button
                        $('#rm-admin-bar-reset').on('click', function () {
                            if (confirm('<?php _e('Are you sure you want to reset admin bar settings to defaults?', 'rm-panel-extensions'); ?>')) {
                                // Uncheck all checkboxes
                                $('input[name^="rm_panel_admin_bar"]').prop('checked', false);

                                // Check only administrator
                                $('#admin_bar_administrator').prop('checked', true);

                                alert('<?php _e('Settings reset to defaults. Click "Save Changes" to apply.', 'rm-panel-extensions'); ?>');
                            }
                        });
                    });
                </script>
                <!-- END ADMIN BAR MANAGEMENT SECTION -->

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get default settings
     */
    private function get_default_settings() {
        return [
            'enable_survey_module' => 1,
            'enable_login_widget' => 1,
            'enable_survey_widget' => 1,
            'enable_wpml_support' => 1,
            'custom_widget_category' => 'RM Panel Widgets',
        ];
    }

    /**
     * Save settings
     */

    /**
     * Save settings
     */
    private function save_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = isset($_POST['rm_panel_settings']) ? $_POST['rm_panel_settings'] : [];
        $sanitized = [];

        $sanitized['enable_survey_module'] = isset($settings['enable_survey_module']) ? 1 : 0;
        $sanitized['enable_login_widget'] = isset($settings['enable_login_widget']) ? 1 : 0;
        $sanitized['enable_survey_widget'] = isset($settings['enable_survey_widget']) ? 1 : 0;
        $sanitized['enable_wpml_support'] = isset($settings['enable_wpml_support']) ? 1 : 0;
        $sanitized['custom_widget_category'] = sanitize_text_field($settings['custom_widget_category']);
        $sanitized['enable_profile_picture_widget'] = isset($settings['enable_profile_picture_widget']) ? 1 : 0;

        update_option('rm_panel_extensions_settings', $sanitized);

        // Save IPStack API key separately
        if (isset($settings['ipstack_api_key'])) {
            $api_key = sanitize_text_field($settings['ipstack_api_key']);
            update_option('rm_panel_ipstack_api_key', $api_key);
        }

        // Save Admin Bar settings (NEW)
        if (isset($_POST['rm_panel_admin_bar'])) {
            RM_Panel_Admin_Bar_Manager::save_settings($_POST['rm_panel_admin_bar']);
        } else {
            // If no admin bar settings submitted, save empty (hide for all roles)
            RM_Panel_Admin_Bar_Manager::save_settings([]);
        }

        add_settings_error(
                'rm_panel_settings',
                'settings_saved',
                __('Settings saved successfully!', 'rm-panel-extensions'),
                'success'
        );
    }

    /**
     * Render modules page
     */
    public function render_modules_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('RM Panel Modules', 'rm-panel-extensions'); ?></h1>

            <div class="rm-panel-modules-grid">
                <?php if (class_exists('RM_Panel_Survey_Module')) : ?>
                    <div class="module-card active">
                        <div class="module-header">
                            <h3><?php _e('Survey Module', 'rm-panel-extensions'); ?></h3>
                            <span class="module-status active"><?php _e('Active', 'rm-panel-extensions'); ?></span>
                        </div>
                        <div class="module-content">
                            <p><?php _e('Create and manage surveys with custom fields and settings.', 'rm-panel-extensions'); ?></p>
                            <ul>
                                <li>✓ <?php _e('Custom Post Type', 'rm-panel-extensions'); ?></li>
                                <li>✓ <?php _e('Survey Categories', 'rm-panel-extensions'); ?></li>
                                <li>✓ <?php _e('Start/End Dates', 'rm-panel-extensions'); ?></li>
                                <li>✓ <?php _e('Question Count', 'rm-panel-extensions'); ?></li>
                                <li>✓ <?php _e('Status Management', 'rm-panel-extensions'); ?></li>
                                <li>✓ <?php _e('Access Control', 'rm-panel-extensions'); ?></li>
                            </ul>
                            <p>
                                <a href="<?php echo admin_url('edit.php?post_type=rm_survey'); ?>" class="button">
                                    <?php _e('Manage Surveys', 'rm-panel-extensions'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="module-card inactive">
                        <div class="module-header">
                            <h3><?php _e('Survey Module', 'rm-panel-extensions'); ?></h3>
                            <span class="module-status inactive"><?php _e('Module File Missing', 'rm-panel-extensions'); ?></span>
                        </div>
                        <div class="module-content">
                            <p><?php _e('The Survey module file is missing. Please ensure the file exists at:', 'rm-panel-extensions'); ?></p>
                            <code>modules/survey/class-survey-module.php</code>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (class_exists('RM_Panel_Survey_Tracking')) : ?>
                    <div class="module-card active">
                        <div class="module-header">
                            <h3><?php _e('Survey Tracking', 'rm-panel-extensions'); ?></h3>
                            <span class="module-status active"><?php _e('Active', 'rm-panel-extensions'); ?></span>
                        </div>
                        <div class="module-content">
                            <p><?php _e('Track user survey completions and responses.', 'rm-panel-extensions'); ?></p>
                            <ul>
                                <li>✓ <?php _e('User Response Tracking', 'rm-panel-extensions'); ?></li>
                                <li>✓ <?php _e('Completion Status', 'rm-panel-extensions'); ?></li>
                                <li>✓ <?php _e('Earnings Calculation', 'rm-panel-extensions'); ?></li>
                                <li>✓ <?php _e('Survey History', 'rm-panel-extensions'); ?></li>
                                <li>✓ <?php _e('Response Analytics', 'rm-panel-extensions'); ?></li>
                            </ul>
                            <p>
                                <a href="<?php echo admin_url('admin.php?page=rm-panel-survey-responses'); ?>" class="button">
                                    <?php _e('View Responses', 'rm-panel-extensions'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (did_action('elementor/loaded')) : ?>
                    <div class="module-card active">
                        <div class="module-header">
                            <h3><?php _e('Elementor Widgets', 'rm-panel-extensions'); ?></h3>
                            <span class="module-status active"><?php _e('Active', 'rm-panel-extensions'); ?></span>
                        </div>
                        <div class="module-content">
                            <p><?php _e('Custom Elementor widgets including login forms and survey listings.', 'rm-panel-extensions'); ?></p>
                            <ul>
                                <li>✓ <?php _e('Login Widget', 'rm-panel-extensions'); ?></li>
                                <li>✓ <?php _e('Survey Listing Widget', 'rm-panel-extensions'); ?></li>
                                <li>✓ <?php _e('Survey Accordion Widget', 'rm-panel-extensions'); ?></li>
                                <li>✓ <?php _e('Role-based Redirection', 'rm-panel-extensions'); ?></li>
                                <li>✓ <?php _e('WPML Support', 'rm-panel-extensions'); ?></li>
                                <li>✓ <?php _e('Multiple Layouts', 'rm-panel-extensions'); ?></li>
                            </ul>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="module-card inactive">
                        <div class="module-header">
                            <h3><?php _e('Elementor Widgets', 'rm-panel-extensions'); ?></h3>
                            <span class="module-status inactive"><?php _e('Requires Elementor', 'rm-panel-extensions'); ?></span>
                        </div>
                        <div class="module-content">
                            <p><?php _e('Install and activate Elementor to use this module.', 'rm-panel-extensions'); ?></p>
                            <a href="<?php echo admin_url('plugin-install.php?s=elementor&tab=search&type=term'); ?>" class="button">
                                <?php _e('Install Elementor', 'rm-panel-extensions'); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if (defined('FLUENTFORM')) : ?>
            <div class="module-card active">
                <div class="module-header">
                    <h3><?php _e('Fluent Forms Integration', 'rm-panel-extensions'); ?></h3>
                    <span class="module-status active"><?php _e('Active', 'rm-panel-extensions'); ?></span>
                </div>
                <div class="module-content">
                    <p><?php _e('Custom validation for Fluent Forms including password confirmation.', 'rm-panel-extensions'); ?></p>
                    <ul>
                        <li>✓ <?php _e('Password Confirmation Validation', 'rm-panel-extensions'); ?></li>
                        <li>✓ <?php _e('Password Strength Validation', 'rm-panel-extensions'); ?></li>
                        <li>✓ <?php _e('Custom Error Messages', 'rm-panel-extensions'); ?></li>
                        <li>✓ <?php _e('User Registration Support', 'rm-panel-extensions'); ?></li>
                    </ul>
                </div>
            </div>
        <?php else : ?>
            <div class="module-card inactive">
                <div class="module-header">
                    <h3><?php _e('Fluent Forms Integration', 'rm-panel-extensions'); ?></h3>
                    <span class="module-status inactive"><?php _e('Requires Fluent Forms', 'rm-panel-extensions'); ?></span>
                </div>
                <div class="module-content">
                    <p><?php _e('Install and activate Fluent Forms to use this module.', 'rm-panel-extensions'); ?></p>
                    <a href="<?php echo admin_url('plugin-install.php?s=fluent+forms&tab=search&type=term'); ?>" class="button">
                        <?php _e('Install Fluent Forms', 'rm-panel-extensions'); ?>
                    </a>
                </div>
            </div>
        <?php
        endif;
    }

    /**
     * Render survey responses page
     */
    public function render_survey_responses_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

        if (!$table_exists) {
            ?>
            <div class="wrap">
                <h1><?php _e('Survey Responses', 'rm-panel-extensions'); ?></h1>
                <div class="notice notice-error">
                    <p><?php _e('Survey tracking table does not exist. Please deactivate and reactivate the plugin to create it.', 'rm-panel-extensions'); ?></p>
                </div>
            </div>
            <?php
            return;
        }

        // Get summary statistics
        $stats = $wpdb->get_row(
                "SELECT 
                COUNT(DISTINCT user_id) as total_users,
                COUNT(*) as total_responses,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN completion_status = 'success' THEN 1 END) as successful,
                COUNT(CASE WHEN completion_status = 'quota_complete' THEN 1 END) as quota_complete,
                COUNT(CASE WHEN completion_status = 'disqualified' THEN 1 END) as disqualified
            FROM $table_name"
        );

        // Get recent responses
        $recent_responses = $wpdb->get_results(
                "SELECT r.*, u.display_name, p.post_title as survey_title
            FROM $table_name r
            LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
            LEFT JOIN {$wpdb->posts} p ON r.survey_id = p.ID
            ORDER BY r.start_time DESC
            LIMIT 20"
        );
        ?>
        <div class="wrap">
            <h1><?php _e('Survey Responses', 'rm-panel-extensions'); ?></h1>

            <div class="survey-response-stats">
                <h2><?php _e('Overview', 'rm-panel-extensions'); ?></h2>
                <div class="stats-grid">
                    <div class="stat-box">
                        <span class="stat-number"><?php echo intval($stats->total_users); ?></span>
                        <span class="stat-label"><?php _e('Total Users', 'rm-panel-extensions'); ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-number"><?php echo intval($stats->total_responses); ?></span>
                        <span class="stat-label"><?php _e('Total Responses', 'rm-panel-extensions'); ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-number"><?php echo intval($stats->successful); ?></span>
                        <span class="stat-label"><?php _e('Successful', 'rm-panel-extensions'); ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-number"><?php echo intval($stats->quota_complete); ?></span>
                        <span class="stat-label"><?php _e('Quota Complete', 'rm-panel-extensions'); ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-number"><?php echo intval($stats->disqualified); ?></span>
                        <span class="stat-label"><?php _e('Disqualified', 'rm-panel-extensions'); ?></span>
                    </div>
                </div>
            </div>

            <h2><?php _e('Recent Responses', 'rm-panel-extensions'); ?></h2>
            <?php if (empty($recent_responses)) : ?>
                <p><?php _e('No survey responses found.', 'rm-panel-extensions'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('User', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Survey', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Start Time', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Status', 'rm-panel-extensions'); ?></th>
                            <th><?php _e('Result', 'rm-panel-extensions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_responses as $response) : ?>
                            <tr>
                                <td><?php echo esc_html($response->display_name); ?></td>
                                <td><?php echo esc_html($response->survey_title); ?></td>
                                <td><?php echo $response->start_time ? date_i18n('Y-m-d H:i', strtotime($response->start_time)) : '—'; ?></td>
                                <td><?php echo esc_html(ucfirst($response->status)); ?></td>
                                <td>
                                    <?php if ($response->completion_status) : ?>
                                        <span class="status-badge status-<?php echo esc_attr($response->completion_status); ?>">
                                            <?php echo esc_html(str_replace('_', ' ', ucfirst($response->completion_status))); ?>
                                        </span>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <style>
            .survey-response-stats {
                margin: 20px 0;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            .stat-box {
                background: #fff;
                padding: 20px;
                border: 1px solid #ccc;
                text-align: center;
            }
            .stat-number {
                display: block;
                font-size: 32px;
                font-weight: bold;
                color: #333;
            }
            .stat-label {
                display: block;
                margin-top: 10px;
                color: #666;
            }
            .status-badge {
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 12px;
            }
            .status-success {
                background: #d4edda;
                color: #155724;
            }
            .status-quota_complete {
                background: #fff3cd;
                color: #856404;
            }
            .status-disqualified {
                background: #f8d7da;
                color: #721c24;
            }
        </style>
        <?php
    }

    /**
     * Add plugin action links
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=rm-panel-extensions-settings') . '">' . __('Settings', 'rm-panel-extensions') . '</a>';
        $modules_link = '<a href="' . admin_url('admin.php?page=rm-panel-extensions-modules') . '">' . __('Modules', 'rm-panel-extensions') . '</a>';

        array_unshift($links, $settings_link, $modules_link);
        return $links;
    }
}

// Initialize the plugin
RM_Panel_Extensions::get_instance();

// ============================================
// ACTIVATION & DEACTIVATION HOOKS
// ============================================

/**
 * Create survey tracking database table
 */
function rm_panel_create_survey_tracking_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'rm_survey_responses';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        survey_id bigint(20) NOT NULL,
        status varchar(50) NOT NULL DEFAULT 'started',
        completion_status varchar(50) DEFAULT NULL,
        start_time datetime DEFAULT CURRENT_TIMESTAMP,
        completion_time datetime DEFAULT NULL,
        response_data longtext DEFAULT NULL,
        ip_address varchar(100) DEFAULT NULL,
        user_agent text DEFAULT NULL,
        referrer_url text DEFAULT NULL,
        notes text DEFAULT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY survey_id (survey_id),
        KEY status (status),
        KEY completion_status (completion_status),
        KEY start_time (start_time),
        UNIQUE KEY user_survey (user_id, survey_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Add database version for future upgrades
    add_option('rm_panel_survey_db_version', '1.0.0');
}

/**
 * Plugin activation hook
 */
function rm_panel_extensions_activate() {
    // Set default options
    $default_settings = [
        'enable_survey_module' => 1,
        'enable_login_widget' => 1,
        'enable_survey_widget' => 1,
        'enable_wpml_support' => 1,
        'custom_widget_category' => 'RM Panel Widgets'
    ];

    if (!get_option('rm_panel_extensions_settings')) {
        update_option('rm_panel_extensions_settings', $default_settings);
    }

    // Load and initialize the Survey module to register post type
    $survey_module_file = plugin_dir_path(__FILE__) . 'modules/survey/class-survey-module.php';
    if (file_exists($survey_module_file)) {
        require_once $survey_module_file;
        if (class_exists('RM_Panel_Survey_Module')) {
            $survey_module = new RM_Panel_Survey_Module();
        }
    }

    // Create survey tracking table
    rm_panel_create_survey_tracking_table();

    // Flush rewrite rules
    flush_rewrite_rules();
}

// Register activation hook
register_activation_hook(__FILE__, 'rm_panel_extensions_activate');

/**
 * Plugin deactivation hook
 */
function rm_panel_extensions_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Register deactivation hook
register_deactivation_hook(__FILE__, 'rm_panel_extensions_deactivate');

/**
 * Check and update database on plugin updates
 */
function rm_panel_check_db_update() {
    $installed_version = get_option('rm_panel_survey_db_version');

    if ($installed_version != '1.0.0') {
        rm_panel_create_survey_tracking_table();
        update_option('rm_panel_survey_db_version', '1.0.0');
    }
}

add_action('plugins_loaded', 'rm_panel_check_db_update');

// Add to functions.php temporarily for debugging
function rm_debug_survey_targeting() {
    if (!is_user_logged_in()) {
        echo "Please log in first";
        return;
    }

    $user_id = get_current_user_id();

    echo "<h2>Survey Targeting Debug</h2>";

    // Check if FluentCRM is active
    echo "<h3>1. FluentCRM Status</h3>";
    if (defined('FLUENTCRM')) {
        echo "✅ FluentCRM is active<br>";
    } else {
        echo "❌ FluentCRM is NOT active<br>";
        return;
    }

    // Check if helper class exists
    echo "<h3>2. Helper Class Status</h3>";
    if (class_exists('RM_Panel_FluentCRM_Helper')) {
        echo "✅ RM_Panel_FluentCRM_Helper class exists<br>";
    } else {
        echo "❌ RM_Panel_FluentCRM_Helper class NOT found<br>";
        return;
    }

    // Get user's country from FluentCRM
    echo "<h3>3. User's Country from FluentCRM</h3>";
    $user_country = RM_Panel_FluentCRM_Helper::get_contact_country($user_id);
    echo "User ID: $user_id<br>";
    echo "Country: " . ($user_country ?: '<strong>NOT SET</strong>') . "<br>";

    // Get contact data
    $contact_data = RM_Panel_FluentCRM_Helper::get_contact_data($user_id);
    if ($contact_data) {
        echo "<pre>";
        print_r($contact_data);
        echo "</pre>";
    } else {
        echo "❌ No contact data found for this user<br>";
    }

    // Get all surveys
    echo "<h3>4. Survey Targeting Settings</h3>";
    $surveys = get_posts([
        'post_type' => 'rm_survey',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ]);

    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Survey ID</th><th>Title</th><th>Location Type</th><th>Target Countries</th><th>Should Show?</th></tr>";

    foreach ($surveys as $survey) {
        $location_type = get_post_meta($survey->ID, '_rm_survey_location_type', true);
        $target_countries = get_post_meta($survey->ID, '_rm_survey_countries', true);
        $matches = RM_Panel_FluentCRM_Helper::matches_survey_location($user_id, $survey->ID);

        echo "<tr>";
        echo "<td>{$survey->ID}</td>";
        echo "<td>{$survey->post_title}</td>";
        echo "<td>" . ($location_type ?: 'all') . "</td>";
        echo "<td>";
        if (is_array($target_countries)) {
            echo implode(', ', $target_countries);
        } else {
            echo "None";
        }
        echo "</td>";
        echo "<td style='background: " . ($matches ? '#d4edda' : '#f8d7da') . ";'>";
        echo $matches ? '✅ YES' : '❌ NO';
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Test matching logic
    echo "<h3>5. Detailed Matching Logic</h3>";
    foreach ($surveys as $survey) {
        echo "<strong>Survey: {$survey->post_title}</strong><br>";

        $location_type = get_post_meta($survey->ID, '_rm_survey_location_type', true);
        echo "Location Type: " . ($location_type ?: 'all') . "<br>";

        if ($location_type === 'specific') {
            $target_countries = get_post_meta($survey->ID, '_rm_survey_countries', true);
            echo "Target Countries (raw): ";
            var_dump($target_countries);
            echo "<br>";

            echo "User Country: " . ($user_country ?: 'NOT SET') . "<br>";

            if (!empty($user_country) && is_array($target_countries)) {
                if (in_array($user_country, $target_countries)) {
                    echo "✅ Match: User country IS in target countries<br>";
                } else {
                    echo "❌ No Match: User country NOT in target countries<br>";
                }
            }
        } else {
            echo "✅ Showing to all countries<br>";
        }
        echo "<hr>";
    }
}

// Access at: yoursite.com/?debug_surveys=1
add_action('wp', function () {
    if (isset($_GET['debug_surveys'])) {
        rm_debug_survey_targeting();
        exit;
    }
});
