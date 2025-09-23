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

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define plugin constants - These will work regardless of folder name
define( 'RM_PANEL_EXT_VERSION', '1.0.0' );
define( 'RM_PANEL_EXT_FILE', __FILE__ );
define( 'RM_PANEL_EXT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RM_PANEL_EXT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RM_PANEL_EXT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

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
        if ( null === self::$instance ) {
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
        if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
            add_action( 'admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p><?php _e( 'RM Panel Extensions requires PHP 7.0 or higher.', 'rm-panel-extensions' ); ?></p>
                </div>
                <?php
            });
            return false;
        }
        
        // Check WordPress version
        if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
            add_action( 'admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p><?php _e( 'RM Panel Extensions requires WordPress 5.0 or higher.', 'rm-panel-extensions' ); ?></p>
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
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        
        // Initialize modules
        add_action( 'init', [ $this, 'init_modules' ] );
        
        // Admin menu
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        
        // Plugin action links
        add_filter( 'plugin_action_links_' . RM_PANEL_EXT_PLUGIN_BASENAME, [ $this, 'add_action_links' ] );
        
        // Admin scripts and styles
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'rm-panel-extensions', false, dirname( RM_PANEL_EXT_PLUGIN_BASENAME ) . '/languages' );
    }

    /**
     * Initialize modules
     */
    public function init_modules() {
        // Load module files
        $this->load_modules();
        
        // Initialize each module
        foreach ( $this->modules as $module_id => $module_class ) {
            if ( class_exists( $module_class ) ) {
                new $module_class();
            }
        }
        
        // Fire action for external modules
        do_action( 'rm_panel_extensions_modules_loaded' );
    }

    /**
     * Load modules
     */
    private function load_modules() {
        // Core modules to load
        $core_modules = [
            'elementor-widgets' => 'RM_Panel_Elementor_Module',
        ];
        
        // Check if module file exists before requiring
        $elementor_module_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/elementor/class-elementor-module.php';
        
        // Load Elementor module if Elementor is active and module file exists
        if ( did_action( 'elementor/loaded' ) ) {
            if ( file_exists( $elementor_module_file ) ) {
                require_once $elementor_module_file;
            } else {
                add_action( 'admin_notices', [ $this, 'module_missing_notice' ] );
                return;
            }
        }
        
        // Allow filtering of modules
        $this->modules = apply_filters( 'rm_panel_extensions_modules', $core_modules );
    }

    /**
     * Module missing notice
     */
    public function module_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e( 'RM Panel Extensions: Module files are missing. Please ensure all files are properly uploaded.', 'rm-panel-extensions' ); ?></p>
            <p><strong><?php _e( 'Required file structure:', 'rm-panel-extensions' ); ?></strong></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li>/modules/elementor/class-elementor-module.php</li>
                <li>/modules/elementor/widgets/login-widget.php</li>
                <li>/modules/elementor/templates/login-form.php</li>
                <li>/assets/css/admin.css</li>
                <li>/assets/css/elementor-widgets.css</li>
                <li>/assets/js/elementor-widgets.js</li>
            </ul>
        </div>
        <?php
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'RM Panel Extensions', 'rm-panel-extensions' ),
            __( 'RM Panel Ext', 'rm-panel-extensions' ),
            'manage_options',
            'rm-panel-extensions',
            [ $this, 'render_admin_page' ],
            'dashicons-admin-generic',
            100
        );
        
        // Add submenu for settings
        add_submenu_page(
            'rm-panel-extensions',
            __( 'Settings', 'rm-panel-extensions' ),
            __( 'Settings', 'rm-panel-extensions' ),
            'manage_options',
            'rm-panel-extensions-settings',
            [ $this, 'render_settings_page' ]
        );
        
        // Add submenu for modules
        add_submenu_page(
            'rm-panel-extensions',
            __( 'Modules', 'rm-panel-extensions' ),
            __( 'Modules', 'rm-panel-extensions' ),
            'manage_options',
            'rm-panel-extensions-modules',
            [ $this, 'render_modules_page' ]
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'rm-panel-extensions' ) !== false ) {
            // Check if CSS file exists before enqueuing
            $css_file = RM_PANEL_EXT_PLUGIN_DIR . 'assets/css/admin.css';
            if ( file_exists( $css_file ) ) {
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
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <div class="rm-panel-dashboard">
                <div class="rm-panel-welcome">
                    <h2><?php _e( 'Welcome to RM Panel Extensions', 'rm-panel-extensions' ); ?></h2>
                    <p><?php _e( 'A comprehensive suite of extensions for WordPress to enhance your website functionality.', 'rm-panel-extensions' ); ?></p>
                    <div class="rm-panel-version">
                        <span><?php _e( 'Version:', 'rm-panel-extensions' ); ?></span>
                        <strong><?php echo RM_PANEL_EXT_VERSION; ?></strong>
                    </div>
                </div>
                
                <?php
                // Check if all required files exist
                $required_files = [
                    'modules/elementor/class-elementor-module.php' => __( 'Elementor Module', 'rm-panel-extensions' ),
                    'modules/elementor/widgets/login-widget.php' => __( 'Login Widget', 'rm-panel-extensions' ),
                    'modules/elementor/templates/login-form.php' => __( 'Login Form Template', 'rm-panel-extensions' ),
                    'assets/css/elementor-widgets.css' => __( 'Widget Styles', 'rm-panel-extensions' ),
                    'assets/js/elementor-widgets.js' => __( 'Widget Scripts', 'rm-panel-extensions' ),
                ];
                
                $missing_files = [];
                foreach ( $required_files as $file => $name ) {
                    if ( ! file_exists( RM_PANEL_EXT_PLUGIN_DIR . $file ) ) {
                        $missing_files[$file] = $name;
                    }
                }
                
                if ( ! empty( $missing_files ) ) : ?>
                    <div class="notice notice-warning" style="margin: 20px 0;">
                        <p><strong><?php _e( 'Missing Files Detected:', 'rm-panel-extensions' ); ?></strong></p>
                        <ul style="list-style: disc; margin-left: 20px;">
                            <?php foreach ( $missing_files as $file => $name ) : ?>
                                <li><?php echo esc_html( $name ); ?> - <code><?php echo esc_html( $file ); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                        <p><?php _e( 'Please ensure all plugin files are properly uploaded.', 'rm-panel-extensions' ); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="rm-panel-stats">
                    <div class="rm-panel-stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-admin-plugins"></span>
                        </div>
                        <div class="stat-content">
                            <h3><?php _e( 'Active Modules', 'rm-panel-extensions' ); ?></h3>
                            <p class="stat-number"><?php echo count( $this->modules ); ?></p>
                        </div>
                    </div>
                    
                    <div class="rm-panel-stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-admin-customizer"></span>
                        </div>
                        <div class="stat-content">
                            <h3><?php _e( 'Elementor Status', 'rm-panel-extensions' ); ?></h3>
                            <p class="stat-status <?php echo did_action( 'elementor/loaded' ) ? 'active' : 'inactive'; ?>">
                                <?php echo did_action( 'elementor/loaded' ) ? __( 'Active', 'rm-panel-extensions' ) : __( 'Inactive', 'rm-panel-extensions' ); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="rm-panel-stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-translation"></span>
                        </div>
                        <div class="stat-content">
                            <h3><?php _e( 'WPML Status', 'rm-panel-extensions' ); ?></h3>
                            <p class="stat-status <?php echo function_exists( 'icl_object_id' ) ? 'active' : 'inactive'; ?>">
                                <?php echo function_exists( 'icl_object_id' ) ? __( 'Active', 'rm-panel-extensions' ) : __( 'Not Installed', 'rm-panel-extensions' ); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="rm-panel-quick-links">
                    <h3><?php _e( 'Quick Links', 'rm-panel-extensions' ); ?></h3>
                    <div class="quick-links-grid">
                        <a href="<?php echo admin_url( 'admin.php?page=rm-panel-extensions-settings' ); ?>" class="quick-link">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <span><?php _e( 'Settings', 'rm-panel-extensions' ); ?></span>
                        </a>
                        <a href="<?php echo admin_url( 'admin.php?page=rm-panel-extensions-modules' ); ?>" class="quick-link">
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <span><?php _e( 'Modules', 'rm-panel-extensions' ); ?></span>
                        </a>
                        <?php if ( did_action( 'elementor/loaded' ) ) : ?>
                            <a href="<?php echo admin_url( 'edit.php?post_type=elementor_library' ); ?>" class="quick-link">
                                <span class="dashicons dashicons-admin-page"></span>
                                <span><?php _e( 'Elementor Templates', 'rm-panel-extensions' ); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="rm-panel-info">
                    <h3><?php _e( 'Plugin Information', 'rm-panel-extensions' ); ?></h3>
                    <table class="widefat">
                        <tbody>
                            <tr>
                                <td><strong><?php _e( 'Plugin Directory', 'rm-panel-extensions' ); ?></strong></td>
                                <td><code><?php echo RM_PANEL_EXT_PLUGIN_DIR; ?></code></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e( 'Plugin URL', 'rm-panel-extensions' ); ?></strong></td>
                                <td><code><?php echo RM_PANEL_EXT_PLUGIN_URL; ?></code></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e( 'PHP Version', 'rm-panel-extensions' ); ?></strong></td>
                                <td><?php echo PHP_VERSION; ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e( 'WordPress Version', 'rm-panel-extensions' ); ?></strong></td>
                                <td><?php echo get_bloginfo( 'version' ); ?></td>
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
        if ( isset( $_POST['rm_panel_settings_nonce'] ) && wp_verify_nonce( $_POST['rm_panel_settings_nonce'], 'rm_panel_settings' ) ) {
            $this->save_settings();
        }
        
        $settings = get_option( 'rm_panel_extensions_settings', $this->get_default_settings() );
        ?>
        <div class="wrap">
            <h1><?php _e( 'RM Panel Extensions Settings', 'rm-panel-extensions' ); ?></h1>
            
            <?php settings_errors( 'rm_panel_settings' ); ?>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'rm_panel_settings', 'rm_panel_settings_nonce' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_login_widget">
                                <?php _e( 'Enable Login Widget', 'rm-panel-extensions' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" name="rm_panel_settings[enable_login_widget]" id="enable_login_widget" value="1" 
                                <?php checked( isset( $settings['enable_login_widget'] ) ? $settings['enable_login_widget'] : 1 ); ?>>
                            <p class="description"><?php _e( 'Enable the custom login widget for Elementor', 'rm-panel-extensions' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_wpml_support">
                                <?php _e( 'Enable WPML Support', 'rm-panel-extensions' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" name="rm_panel_settings[enable_wpml_support]" id="enable_wpml_support" value="1" 
                                <?php checked( isset( $settings['enable_wpml_support'] ) ? $settings['enable_wpml_support'] : 1 ); ?>>
                            <p class="description"><?php _e( 'Enable WPML translation support for widgets', 'rm-panel-extensions' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="custom_widget_category">
                                <?php _e( 'Custom Widget Category', 'rm-panel-extensions' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" name="rm_panel_settings[custom_widget_category]" id="custom_widget_category" 
                                value="<?php echo esc_attr( isset( $settings['custom_widget_category'] ) ? $settings['custom_widget_category'] : 'RM Panel Widgets' ); ?>" 
                                class="regular-text">
                            <p class="description"><?php _e( 'Category name for custom widgets in Elementor', 'rm-panel-extensions' ); ?></p>
                        </td>
                    </tr>
                </table>
                
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
            'enable_login_widget' => 1,
            'enable_wpml_support' => 1,
            'custom_widget_category' => 'RM Panel Widgets',
        ];
    }

    /**
     * Save settings
     */
    private function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        $settings = isset( $_POST['rm_panel_settings'] ) ? $_POST['rm_panel_settings'] : [];
        $sanitized = [];
        
        $sanitized['enable_login_widget'] = isset( $settings['enable_login_widget'] ) ? 1 : 0;
        $sanitized['enable_wpml_support'] = isset( $settings['enable_wpml_support'] ) ? 1 : 0;
        $sanitized['custom_widget_category'] = sanitize_text_field( $settings['custom_widget_category'] );
        
        update_option( 'rm_panel_extensions_settings', $sanitized );
        
        add_settings_error(
            'rm_panel_settings',
            'settings_saved',
            __( 'Settings saved successfully!', 'rm-panel-extensions' ),
            'success'
        );
    }

    /**
     * Render modules page
     */
    public function render_modules_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'RM Panel Modules', 'rm-panel-extensions' ); ?></h1>
            
            <div class="rm-panel-modules-grid">
                <?php if ( did_action( 'elementor/loaded' ) ) : ?>
                    <div class="module-card active">
                        <div class="module-header">
                            <h3><?php _e( 'Elementor Widgets', 'rm-panel-extensions' ); ?></h3>
                            <span class="module-status active"><?php _e( 'Active', 'rm-panel-extensions' ); ?></span>
                        </div>
                        <div class="module-content">
                            <p><?php _e( 'Custom Elementor widgets including login forms with role-based redirection.', 'rm-panel-extensions' ); ?></p>
                            <ul>
                                <li>✓ <?php _e( 'Login Widget', 'rm-panel-extensions' ); ?></li>
                                <li>✓ <?php _e( 'Role-based Redirection', 'rm-panel-extensions' ); ?></li>
                                <li>✓ <?php _e( 'WPML Support', 'rm-panel-extensions' ); ?></li>
                                <li>✓ <?php _e( 'Customizable Text', 'rm-panel-extensions' ); ?></li>
                            </ul>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="module-card inactive">
                        <div class="module-header">
                            <h3><?php _e( 'Elementor Widgets', 'rm-panel-extensions' ); ?></h3>
                            <span class="module-status inactive"><?php _e( 'Requires Elementor', 'rm-panel-extensions' ); ?></span>
                        </div>
                        <div class="module-content">
                            <p><?php _e( 'Install and activate Elementor to use this module.', 'rm-panel-extensions' ); ?></p>
                            <a href="<?php echo admin_url( 'plugin-install.php?s=elementor&tab=search&type=term' ); ?>" class="button">
                                <?php _e( 'Install Elementor', 'rm-panel-extensions' ); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Add plugin action links
     */
    public function add_action_links( $links ) {
        $settings_link = '<a href="' . admin_url( 'admin.php?page=rm-panel-extensions-settings' ) . '">' . __( 'Settings', 'rm-panel-extensions' ) . '</a>';
        $modules_link = '<a href="' . admin_url( 'admin.php?page=rm-panel-extensions-modules' ) . '">' . __( 'Modules', 'rm-panel-extensions' ) . '</a>';
        
        array_unshift( $links, $settings_link, $modules_link );
        return $links;
    }
}

// Initialize the plugin
RM_Panel_Extensions::get_instance();

// Register activation hook
register_activation_hook( __FILE__, 'rm_panel_extensions_activate' );
function rm_panel_extensions_activate() {
    // Set default options
    $default_settings = [
        'enable_login_widget' => 1,
        'enable_wpml_support' => 1,
        'custom_widget_category' => 'RM Panel Widgets'
    ];
    
    if ( ! get_option( 'rm_panel_extensions_settings' ) ) {
        update_option( 'rm_panel_extensions_settings', $default_settings );
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Register deactivation hook
register_deactivation_hook( __FILE__, 'rm_panel_extensions_deactivate' );
function rm_panel_extensions_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}