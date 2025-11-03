<?php
/**
 * Plugin Name: RM Panel Extensions
 * Description: A comprehensive suite of extensions for WordPress including custom Elementor widgets, role management, and more
 * Version: 1.2.0
 * Author: Research and Metric
 * Author URI: https://researchandmetric.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rm-panel-extensions
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 *
 * @package RM_Panel_Extensions
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================
// PLUGIN CONSTANTS
// ============================================

define( 'RM_PANEL_EXT_VERSION', '1.2.0' );
define( 'RM_PANEL_EXT_FILE', __FILE__ );
define( 'RM_PANEL_EXT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RM_PANEL_EXT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RM_PANEL_EXT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'RM_PANEL_EXT_DB_VERSION', '1.2.0' );

// ============================================
// MAIN PLUGIN CLASS
// ============================================

/**
 * Main Plugin Class
 *
 * @since 1.0.0
 */
class RM_Panel_Extensions {

	/**
	 * Plugin instance
	 *
	 * @var RM_Panel_Extensions
	 */
	private static $instance = null;

	/**
	 * Loaded modules
	 *
	 * @var array
	 */
	private $modules = array();

	/**
	 * Get singleton instance
	 *
	 * @return RM_Panel_Extensions
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->check_requirements();
		$this->init_hooks();
	}

	/**
	 * Check plugin requirements
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function check_requirements() {
		// Check PHP version
		if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
			return false;
		}

		// Check WordPress version
		if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'wp_version_notice' ) );
			return false;
		}

		return true;
	}

	/**
	 * PHP version notice
	 *
	 * @since 1.0.0
	 */
	public function php_version_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'RM Panel Extensions requires PHP 7.0 or higher.', 'rm-panel-extensions' ); ?></p>
		</div>
		<?php
	}

	/**
	 * WordPress version notice
	 *
	 * @since 1.0.0
	 */
	public function wp_version_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'RM Panel Extensions requires WordPress 5.0 or higher.', 'rm-panel-extensions' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Core hooks
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init_modules' ), 5 );
		
		// Admin hooks
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		
		// Frontend hooks
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		
		// Plugin links
		add_filter( 'plugin_action_links_' . RM_PANEL_EXT_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
		
		// Database update check
		add_action( 'plugins_loaded', array( $this, 'check_db_update' ) );
	}

	/**
	 * Load plugin textdomain
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'rm-panel-extensions',
			false,
			dirname( RM_PANEL_EXT_PLUGIN_BASENAME ) . '/languages'
		);
	}

	// ============================================
	// MODULE MANAGEMENT
	// ============================================

	/**
	 * Initialize all modules
	 *
	 * @since 1.0.0
	 */
	public function init_modules() {
		$this->load_modules();
		$this->instantiate_modules();
		
		do_action( 'rm_panel_extensions_modules_loaded' );
	}

	/**
	 * Load module files
	 *
	 * @since 1.0.0
	 */
	private function load_modules() {
		$core_modules = array();

		// Survey Module (Core - always load)
		$this->load_module_file(
			'survey',
			'modules/survey/class-survey-module.php',
			'RM_Panel_Survey_Module',
			$core_modules
		);

		// Survey Tracking Module
		$this->load_module_file(
			'survey-tracking',
			'modules/survey/class-survey-tracking.php',
			'RM_Panel_Survey_Tracking',
			$core_modules
		);

		// Survey-related modules (always load if files exist)
		$this->load_survey_submodules();

		// Elementor Module (conditional)
		if ( did_action( 'elementor/loaded' ) ) {
			$this->load_module_file(
				'elementor-widgets',
				'modules/elementor/class-elementor-module.php',
				'RM_Panel_Elementor_Module',
				$core_modules
			);
		}

		// FluentCRM Helper (conditional)
		if ( defined( 'FLUENTCRM' ) || function_exists( 'FluentCrmApi' ) ) {
			$this->load_file( 'modules/fluent-crm/class-fluent-crm-helper.php' );
		}

		// Fluent Forms Module (conditional)
		if ( defined( 'FLUENTFORM' ) || function_exists( 'wpFluentForm' ) ) {
			$this->load_module_file(
				'fluent-forms',
				'modules/fluent-forms/class-fluent-forms-module.php',
				'RM_Panel_Fluent_Forms_Module',
				$core_modules
			);
		}

		// Profile Picture Handler
		$this->load_file( 'modules/profile-picture/class-profile-picture-handler.php' );

		// Reports Modules (v1.1.0)
		$this->load_reports_modules();

		// Referral System
		$this->load_file( 'modules/referral/class-referral-system.php' );

		// Admin Bar Manager
		$this->load_file( 'modules/admin-bar/class-admin-bar-manager.php' );

		// Allow filtering of modules
		$this->modules = apply_filters( 'rm_panel_extensions_modules', $core_modules );

		// Check for missing critical files
		$this->check_missing_files();
	}

	/**
	 * Load survey submodules
	 *
	 * @since 1.2.0
	 */
	private function load_survey_submodules() {
		$survey_modules = array(
			'modules/survey/class-survey-callbacks.php',
			'modules/survey/class-survey-database-upgrade.php',
			'modules/survey/class-survey-database-upgrade-v1.2.0.php',
			'modules/survey/class-survey-approval-admin.php',
			'modules/survey/class-survey-tabs-shortcode.php',
			'modules/survey/class-survey-manager-metabox.php',
			'modules/survey/class-survey-tracking-enhanced.php',
			'modules/survey/class-survey-admin-columns-enhanced.php',
			'modules/survey/class-survey-thank-you.php',
		);

		foreach ( $survey_modules as $module ) {
			$this->load_file( $module );
		}

		// Initialize Survey Callbacks immediately (doesn't need to be in modules array)
		if ( class_exists( 'RM_Survey_Callbacks' ) ) {
			new RM_Survey_Callbacks();
		}
	}

	/**
	 * Load reports modules
	 *
	 * @since 1.1.0
	 */
	private function load_reports_modules() {
		$reports_dir = RM_PANEL_EXT_PLUGIN_DIR . 'modules/reports/';
		
		$report_files = array(
			'class-survey-live-monitor.php',
			'class-survey-reports.php',
			'class-user-reports.php',
		);

		foreach ( $report_files as $file ) {
			$this->load_file( $reports_dir . $file );
		}
	}

	/**
	 * Load a single module file
	 *
	 * @since 1.2.0
	 * @param string $key         Module key
	 * @param string $file        File path relative to plugin directory
	 * @param string $class_name  Class name
	 * @param array  &$modules    Modules array (passed by reference)
	 */
	private function load_module_file( $key, $file, $class_name, &$modules ) {
		$full_path = RM_PANEL_EXT_PLUGIN_DIR . $file;
		
		if ( file_exists( $full_path ) ) {
			require_once $full_path;
			$modules[ $key ] = $class_name;
		}
	}

	/**
	 * Load a file if it exists
	 *
	 * @since 1.2.0
	 * @param string $file File path (can be relative or absolute)
	 * @return bool
	 */
	private function load_file( $file ) {
		// Convert to absolute path if relative
		if ( strpos( $file, RM_PANEL_EXT_PLUGIN_DIR ) !== 0 ) {
			$file = RM_PANEL_EXT_PLUGIN_DIR . $file;
		}

		if ( file_exists( $file ) ) {
			require_once $file;
			return true;
		}
		
		return false;
	}

	/**
	 * Instantiate loaded modules
	 *
	 * @since 1.2.0
	 */
	private function instantiate_modules() {
		// Initialize Survey module first (doesn't depend on anything)
		if ( isset( $this->modules['survey'] ) && class_exists( $this->modules['survey'] ) ) {
			new $this->modules['survey']();
		}

		// Initialize Survey Tracking module
		if ( isset( $this->modules['survey-tracking'] ) && class_exists( $this->modules['survey-tracking'] ) ) {
			new $this->modules['survey-tracking']();
		}

		// Initialize Elementor module if Elementor is active
		if ( did_action( 'elementor/loaded' ) ) {
			if ( isset( $this->modules['elementor-widgets'] ) && class_exists( $this->modules['elementor-widgets'] ) ) {
				new $this->modules['elementor-widgets']();
			}
		}

		// Initialize Fluent Forms module using singleton pattern
		if ( defined( 'FLUENTFORM' ) || function_exists( 'wpFluentForm' ) ) {
			if ( class_exists( 'RM_Panel_Fluent_Forms_Module' ) ) {
				RM_Panel_Fluent_Forms_Module::get_instance();
			}
		}
	}

	/**
	 * Check for missing critical files
	 *
	 * @since 1.2.0
	 */
	private function check_missing_files() {
		$missing_files = array();

		// Check critical files
		$critical_files = array(
			'modules/survey/class-survey-module.php',
		);

		if ( did_action( 'elementor/loaded' ) ) {
			$critical_files[] = 'modules/elementor/class-elementor-module.php';
		}

		foreach ( $critical_files as $file ) {
			if ( ! file_exists( RM_PANEL_EXT_PLUGIN_DIR . $file ) ) {
				$missing_files[] = $file;
			}
		}

		if ( ! empty( $missing_files ) ) {
			add_action( 'admin_notices', function() use ( $missing_files ) {
				$this->show_missing_files_notice( $missing_files );
			} );
		}
	}

	/**
	 * Show missing files notice
	 *
	 * @since 1.0.0
	 * @param array $missing_files Array of missing file paths
	 */
	private function show_missing_files_notice( $missing_files ) {
		?>
		<div class="notice notice-warning">
			<p><strong><?php esc_html_e( 'RM Panel Extensions: Some module files are missing:', 'rm-panel-extensions' ); ?></strong></p>
			<ul style="list-style: disc; margin-left: 20px;">
				<?php foreach ( $missing_files as $file ) : ?>
					<li><code><?php echo esc_html( $file ); ?></code></li>
				<?php endforeach; ?>
			</ul>
			<p><?php esc_html_e( 'Please ensure all plugin files are properly uploaded.', 'rm-panel-extensions' ); ?></p>
		</div>
		<?php
	}

	// ============================================
	// ASSET ENQUEUEING
	// ============================================

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'rm-panel-extensions' ) === false ) {
			return;
		}

		$css_file = RM_PANEL_EXT_PLUGIN_DIR . 'assets/css/admin.css';
		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'rm-panel-admin',
				RM_PANEL_EXT_PLUGIN_URL . 'assets/css/admin.css',
				array(),
				RM_PANEL_EXT_VERSION
			);
		}
	}

	/**
	 * Enqueue frontend scripts and styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_frontend_scripts() {
		// Survey tracking assets
		$this->enqueue_survey_tracking_assets();

		// Profile picture assets (logged-in users only)
		if ( is_user_logged_in() ) {
			$this->enqueue_profile_picture_assets();
		}

		// Survey accordion tabs CSS
		$this->enqueue_survey_accordion_assets();

		// Font Awesome for social icons
		wp_enqueue_style(
			'font-awesome',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
			array(),
			'6.0.0'
		);
	}

	/**
	 * Enqueue survey tracking assets
	 *
	 * @since 1.0.0
	 */
	private function enqueue_survey_tracking_assets() {
		$should_load = $this->should_load_survey_tracking();

		if ( ! $should_load ) {
			return;
		}

		// Enqueue JavaScript
		$tracking_js_file = RM_PANEL_EXT_PLUGIN_DIR . 'assets/js/survey-tracking.js';
		if ( file_exists( $tracking_js_file ) ) {
			wp_enqueue_script(
				'rm-survey-tracking',
				RM_PANEL_EXT_PLUGIN_URL . 'assets/js/survey-tracking.js',
				array( 'jquery' ),
				RM_PANEL_EXT_VERSION,
				true
			);

			wp_localize_script(
				'rm-survey-tracking',
				'rm_survey_ajax',
				array(
					'ajax_url'      => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'rm_survey_nonce' ),
					'dashboard_url' => home_url( '/my-dashboard/' ),
					'is_logged_in'  => is_user_logged_in(),
					'user_id'       => get_current_user_id(),
				)
			);
		}

		// Enqueue CSS
		$tracking_css_file = RM_PANEL_EXT_PLUGIN_DIR . 'assets/css/survey-tracking.css';
		if ( file_exists( $tracking_css_file ) ) {
			wp_enqueue_style(
				'rm-survey-tracking',
				RM_PANEL_EXT_PLUGIN_URL . 'assets/css/survey-tracking.css',
				array(),
				RM_PANEL_EXT_VERSION
			);
		}
	}

	/**
	 * Check if survey tracking assets should be loaded
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	private function should_load_survey_tracking() {
		// Check if on survey single page or archive
		if ( is_singular( 'rm_survey' ) || is_post_type_archive( 'rm_survey' ) ) {
			return true;
		}

		// Check if current page has the survey history shortcode
		if ( is_singular() ) {
			global $post;
			if ( $post && has_shortcode( $post->post_content, 'rm_survey_history' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enqueue profile picture assets
	 *
	 * @since 1.0.0
	 */
	private function enqueue_profile_picture_assets() {
		// Enqueue CSS
		$profile_css_file = RM_PANEL_EXT_PLUGIN_DIR . 'assets/css/profile-picture-widget.css';
		if ( file_exists( $profile_css_file ) ) {
			wp_enqueue_style(
				'rm-profile-picture-widget',
				RM_PANEL_EXT_PLUGIN_URL . 'assets/css/profile-picture-widget.css',
				array(),
				RM_PANEL_EXT_VERSION
			);
		}

		// Enqueue JavaScript
		$profile_js_file = RM_PANEL_EXT_PLUGIN_DIR . 'assets/js/profile-picture-widget.js';
		if ( file_exists( $profile_js_file ) ) {
			wp_enqueue_script(
				'rm-profile-picture-widget',
				RM_PANEL_EXT_PLUGIN_URL . 'assets/js/profile-picture-widget.js',
				array( 'jquery' ),
				RM_PANEL_EXT_VERSION,
				true
			);

			wp_localize_script(
				'rm-profile-picture-widget',
				'rmProfilePicture',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'rm_profile_picture_nonce' ),
				)
			);

			// Debug mode
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				wp_add_inline_script(
					'rm-profile-picture-widget',
					'console.log("RM Profile Picture: Script enqueued successfully");',
					'before'
				);
			}
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'RM Panel: profile-picture-widget.js not found at: ' . $profile_js_file );
		}
	}

	/**
	 * Enqueue survey accordion assets
	 *
	 * @since 1.0.0
	 */
	private function enqueue_survey_accordion_assets() {
		$accordion_css = RM_PANEL_EXT_PLUGIN_DIR . 'assets/css/survey-accordion-tabs.css';
		if ( file_exists( $accordion_css ) ) {
			wp_enqueue_style(
				'rm-survey-accordion-tabs',
				RM_PANEL_EXT_PLUGIN_URL . 'assets/css/survey-accordion-tabs.css',
				array(),
				RM_PANEL_EXT_VERSION
			);
		}
	}

	// ============================================
	// ADMIN MENU & PAGES
	// ============================================

	/**
	 * Add admin menu pages
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		// Main menu page
		add_menu_page(
			__( 'RM Panel Extensions', 'rm-panel-extensions' ),
			__( 'RM Panel Ext', 'rm-panel-extensions' ),
			'manage_options',
			'rm-panel-extensions',
			array( $this, 'render_admin_page' ),
			'dashicons-admin-generic',
			100
		);

		// Settings submenu
		add_submenu_page(
			'rm-panel-extensions',
			__( 'Settings', 'rm-panel-extensions' ),
			__( 'Settings', 'rm-panel-extensions' ),
			'manage_options',
			'rm-panel-extensions-settings',
			array( $this, 'render_settings_page' )
		);

		// Modules submenu
		add_submenu_page(
			'rm-panel-extensions',
			__( 'Modules', 'rm-panel-extensions' ),
			__( 'Modules', 'rm-panel-extensions' ),
			'manage_options',
			'rm-panel-extensions-modules',
			array( $this, 'render_modules_page' )
		);

		// Survey responses submenu
		add_submenu_page(
			'rm-panel-extensions',
			__( 'Survey Responses', 'rm-panel-extensions' ),
			__( 'Survey Responses', 'rm-panel-extensions' ),
			'manage_options',
			'rm-panel-survey-responses',
			array( $this, 'render_survey_responses_page' )
		);
	}

	/**
	 * Add plugin action links
	 *
	 * @since 1.0.0
	 * @param array $links Existing plugin action links
	 * @return array Modified plugin action links
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=rm-panel-extensions-settings' ),
			__( 'Settings', 'rm-panel-extensions' )
		);
		
		$modules_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=rm-panel-extensions-modules' ),
			__( 'Modules', 'rm-panel-extensions' )
		);

		array_unshift( $links, $settings_link, $modules_link );
		
		return $links;
	}

	/**
	 * Render main admin page
	 *
	 * @since 1.0.0
	 */
	public function render_admin_page() {
		require_once RM_PANEL_EXT_PLUGIN_DIR . 'includes/admin/views/main-dashboard.php';
	}

	/**
	 * Render settings page
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page() {
		// Save settings if form is submitted
		if ( isset( $_POST['rm_panel_settings_nonce'] ) && 
		     wp_verify_nonce( $_POST['rm_panel_settings_nonce'], 'rm_panel_settings' ) ) {
			$this->save_settings();
		}

		$settings = get_option( 'rm_panel_extensions_settings', $this->get_default_settings() );
		
		require_once RM_PANEL_EXT_PLUGIN_DIR . 'includes/admin/views/settings-page.php';
	}

	/**
	 * Render modules page
	 *
	 * @since 1.0.0
	 */
	public function render_modules_page() {
		require_once RM_PANEL_EXT_PLUGIN_DIR . 'includes/admin/views/modules-page.php';
	}

	/**
	 * Render survey responses page
	 *
	 * @since 1.0.0
	 */
	public function render_survey_responses_page() {
		require_once RM_PANEL_EXT_PLUGIN_DIR . 'includes/admin/views/survey-responses-page.php';
	}

	// ============================================
	// SETTINGS MANAGEMENT
	// ============================================

	/**
	 * Get default settings
	 *
	 * @since 1.0.0
	 * @return array Default settings
	 */
	private function get_default_settings() {
		return array(
			'enable_survey_module'           => 1,
			'enable_login_widget'            => 1,
			'enable_survey_widget'           => 1,
			'enable_wpml_support'            => 1,
			'enable_profile_picture_widget'  => 1,
			'sync_profile_to_fluentcrm'      => 1,
			'custom_widget_category'         => 'RM Panel Widgets',
		);
	}

	/**
	 * Save plugin settings
	 *
	 * @since 1.0.0
	 */
	private function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings   = isset( $_POST['rm_panel_settings'] ) ? $_POST['rm_panel_settings'] : array();
		$sanitized  = array();

		// Sanitize checkbox settings
		$checkbox_settings = array(
			'enable_survey_module',
			'enable_login_widget',
			'enable_survey_widget',
			'enable_wpml_support',
			'enable_profile_picture_widget',
			'sync_profile_to_fluentcrm',
		);

		foreach ( $checkbox_settings as $setting ) {
			$sanitized[ $setting ] = isset( $settings[ $setting ] ) ? 1 : 0;
		}

		// Sanitize text settings
		$sanitized['custom_widget_category'] = isset( $settings['custom_widget_category'] ) 
			? sanitize_text_field( $settings['custom_widget_category'] ) 
			: 'RM Panel Widgets';

		// Update main settings
		update_option( 'rm_panel_extensions_settings', $sanitized );

		// Save IPStack API key separately
		if ( isset( $settings['ipstack_api_key'] ) ) {
			$api_key = sanitize_text_field( $settings['ipstack_api_key'] );
			update_option( 'rm_panel_ipstack_api_key', $api_key );
		}

		// Save Admin Bar settings
		if ( class_exists( 'RM_Panel_Admin_Bar_Manager' ) ) {
			if ( isset( $_POST['rm_panel_admin_bar'] ) ) {
				RM_Panel_Admin_Bar_Manager::save_settings( $_POST['rm_panel_admin_bar'] );
			} else {
				RM_Panel_Admin_Bar_Manager::save_settings( array() );
			}
		}

		add_settings_error(
			'rm_panel_settings',
			'settings_saved',
			__( 'Settings saved successfully!', 'rm-panel-extensions' ),
			'success'
		);
	}

	// ============================================
	// DATABASE MANAGEMENT
	// ============================================

	/**
	 * Check and update database if needed
	 *
	 * @since 1.0.0
	 */
	public function check_db_update() {
		$installed_version = get_option( 'rm_panel_survey_db_version' );

		if ( version_compare( $installed_version, RM_PANEL_EXT_DB_VERSION, '<' ) ) {
			$this->create_survey_tracking_table();
			update_option( 'rm_panel_survey_db_version', RM_PANEL_EXT_DB_VERSION );
		}
	}

	/**
	 * Create survey tracking database table
	 *
	 * @since 1.0.0
	 */
	private function create_survey_tracking_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'rm_survey_responses';
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

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

// ============================================
// PLUGIN INITIALIZATION
// ============================================

/**
 * Initialize the plugin
 *
 * @since 1.0.0
 * @return RM_Panel_Extensions
 */
function rm_panel_extensions() {
	return RM_Panel_Extensions::get_instance();
}

// Initialize plugin
rm_panel_extensions();

// ============================================
// ACTIVATION & DEACTIVATION HOOKS
// ============================================

/**
 * Plugin activation callback
 *
 * @since 1.0.0
 */
function rm_panel_extensions_activate() {
	// Set default options
	$default_settings = array(
		'enable_survey_module'  => 1,
		'enable_login_widget'   => 1,
		'enable_survey_widget'  => 1,
		'enable_wpml_support'   => 1,
		'custom_widget_category' => 'RM Panel Widgets',
	);

	if ( ! get_option( 'rm_panel_extensions_settings' ) ) {
		update_option( 'rm_panel_extensions_settings', $default_settings );
	}

	// Load and initialize the Survey module to register post type
	$survey_module_file = plugin_dir_path( __FILE__ ) . 'modules/survey/class-survey-module.php';
	if ( file_exists( $survey_module_file ) ) {
		require_once $survey_module_file;
		if ( class_exists( 'RM_Panel_Survey_Module' ) ) {
			new RM_Panel_Survey_Module();
		}
	}

	// Create database tables
	rm_panel_extensions()->check_db_update();

	// Flush rewrite rules
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'rm_panel_extensions_activate' );

/**
 * Plugin deactivation callback
 *
 * @since 1.0.0
 */
function rm_panel_extensions_deactivate() {
	// Flush rewrite rules
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'rm_panel_extensions_deactivate' );

// ============================================
// DEBUGGING UTILITIES (Development Only)
// ============================================

/**
 * Debug survey targeting
 * 
 * Access via: yoursite.com/?debug_surveys=1
 *
 * @since 1.0.0
 */
function rm_debug_survey_targeting() {
	if ( ! is_user_logged_in() ) {
		echo '<p>Please log in first</p>';
		return;
	}

	$user_id = get_current_user_id();

	echo '<h2>Survey Targeting Debug</h2>';

	// Check if FluentCRM is active
	echo '<h3>1. FluentCRM Status</h3>';
	if ( defined( 'FLUENTCRM' ) ) {
		echo '✅ FluentCRM is active<br>';
	} else {
		echo '❌ FluentCRM is NOT active<br>';
		return;
	}

	// Check if helper class exists
	echo '<h3>2. Helper Class Status</h3>';
	if ( class_exists( 'RM_Panel_FluentCRM_Helper' ) ) {
		echo '✅ RM_Panel_FluentCRM_Helper class exists<br>';
	} else {
		echo '❌ RM_Panel_FluentCRM_Helper class NOT found<br>';
		return;
	}

	// Get user's country from FluentCRM
	echo '<h3>3. User\'s Country from FluentCRM</h3>';
	$user_country = RM_Panel_FluentCRM_Helper::get_contact_country( $user_id );
	echo 'User ID: ' . $user_id . '<br>';
	echo 'Country: ' . ( $user_country ?: '<strong>NOT SET</strong>' ) . '<br>';

	// Get contact data
	$contact_data = RM_Panel_FluentCRM_Helper::get_contact_data( $user_id );
	if ( $contact_data ) {
		echo '<pre>';
		print_r( $contact_data );
		echo '</pre>';
	} else {
		echo '❌ No contact data found for this user<br>';
	}

	// Get all surveys
	echo '<h3>4. Survey Targeting Settings</h3>';
	$surveys = get_posts(
		array(
			'post_type'      => 'rm_survey',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		)
	);

	echo '<table border="1" cellpadding="10">';
	echo '<tr><th>Survey ID</th><th>Title</th><th>Location Type</th><th>Target Countries</th><th>Should Show?</th></tr>';

	foreach ( $surveys as $survey ) {
		$location_type    = get_post_meta( $survey->ID, '_rm_survey_location_type', true );
		$target_countries = get_post_meta( $survey->ID, '_rm_survey_countries', true );
		$matches          = RM_Panel_FluentCRM_Helper::matches_survey_location( $user_id, $survey->ID );

		echo '<tr>';
		echo '<td>' . $survey->ID . '</td>';
		echo '<td>' . esc_html( $survey->post_title ) . '</td>';
		echo '<td>' . ( $location_type ?: 'all' ) . '</td>';
		echo '<td>';
		if ( is_array( $target_countries ) ) {
			echo esc_html( implode( ', ', $target_countries ) );
		} else {
			echo 'None';
		}
		echo '</td>';
		echo '<td style="background: ' . ( $matches ? '#d4edda' : '#f8d7da' ) . ';">';
		echo $matches ? '✅ YES' : '❌ NO';
		echo '</td>';
		echo '</tr>';
	}
	echo '</table>';

	// Test matching logic
	echo '<h3>5. Detailed Matching Logic</h3>';
	foreach ( $surveys as $survey ) {
		echo '<strong>Survey: ' . esc_html( $survey->post_title ) . '</strong><br>';

		$location_type = get_post_meta( $survey->ID, '_rm_survey_location_type', true );
		echo 'Location Type: ' . ( $location_type ?: 'all' ) . '<br>';

		if ( $location_type === 'specific' ) {
			$target_countries = get_post_meta( $survey->ID, '_rm_survey_countries', true );
			echo 'Target Countries (raw): ';
			var_dump( $target_countries );
			echo '<br>';

			echo 'User Country: ' . ( $user_country ?: 'NOT SET' ) . '<br>';

			if ( ! empty( $user_country ) && is_array( $target_countries ) ) {
				if ( in_array( $user_country, $target_countries, true ) ) {
					echo '✅ Match: User country IS in target countries<br>';
				} else {
					echo '❌ No Match: User country NOT in target countries<br>';
				}
			}
		} else {
			echo '✅ Showing to all countries<br>';
		}
		echo '<hr>';
	}
}

// Only enable debugging in development environment
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	add_action(
		'wp',
		function() {
			if ( isset( $_GET['debug_surveys'] ) ) {
				rm_debug_survey_targeting();
				exit;
			}
		}
	);
}