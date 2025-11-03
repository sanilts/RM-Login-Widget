<?php
/**
 * Plugin Name: RM Panel Extensions
 * Description: A comprehensive suite of extensions for WordPress including custom Elementor widgets, role management, survey system with payments and withdrawals
 * Version: 2.1.0
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

define( 'RM_PANEL_EXT_VERSION', '2.1.0' );
define( 'RM_PANEL_EXT_FILE', __FILE__ );
define( 'RM_PANEL_EXT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RM_PANEL_EXT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RM_PANEL_EXT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'RM_PANEL_EXT_DB_VERSION', '1.3.0' );

// ============================================
// MAIN PLUGIN CLASS
// ============================================

/**
 * Main Plugin Class
 *
 * @since 2.0.0
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
	 * @since 2.0.0
	 */
	private function __construct() {
		$this->check_requirements();
		$this->init_hooks();
	}

	/**
	 * Check plugin requirements
	 *
	 * @since 2.0.0
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
	 */
	public function init_modules() {
		$this->load_modules();
		$this->instantiate_modules();
		
		do_action( 'rm_panel_extensions_modules_loaded' );
	}

	/**
	 * Load module files
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

		// Payment and Withdrawal Modules
		$this->load_payment_modules();

		// Elementor Module (conditional - REQUIRED)
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

		// Reports Modules
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
	 */
	private function load_survey_submodules() {
		$survey_modules = array(
			'modules/survey/class-survey-callbacks.php',
			'modules/survey/class-survey-database-upgrade.php',
			'modules/survey/class-survey-database-upgrade-v1.2.0.php',
			'modules/survey/class-survey-approval-admin.php',
			'modules/survey/class-survey-approval-enhanced.php',
			'modules/survey/class-survey-manager-metabox.php',
			'modules/survey/class-survey-tracking-enhanced.php',
			'modules/survey/class-survey-admin-columns-enhanced.php',
			'modules/survey/class-survey-thank-you.php',
		);

		foreach ( $survey_modules as $module ) {
			$this->load_file( $module );
		}

		// Initialize Survey Callbacks immediately
		if ( class_exists( 'RM_Survey_Callbacks' ) ) {
			new RM_Survey_Callbacks();
		}
	}

	/**
	 * Load payment and withdrawal modules
	 */
	private function load_payment_modules() {
		// Create payments directory if it doesn't exist
		$payments_dir = RM_PANEL_EXT_PLUGIN_DIR . 'modules/payments/';
		
		$payment_modules = array(
			'class-payment-methods.php',
			'class-withdrawal-requests.php',
		);

		foreach ( $payment_modules as $module ) {
			$this->load_file( $payments_dir . $module );
		}
	}

	/**
	 * Load reports modules
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
	 */
	private function instantiate_modules() {
		// Initialize Survey module first
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

		// Initialize Fluent Forms module
		if ( defined( 'FLUENTFORM' ) || function_exists( 'wpFluentForm' ) ) {
			if ( class_exists( 'RM_Panel_Fluent_Forms_Module' ) ) {
				RM_Panel_Fluent_Forms_Module::get_instance();
			}
		}
	}

	/**
	 * Check for missing critical files
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
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'rm-panel-extensions' ) === false && 
		     strpos( $hook, 'rm_survey' ) === false ) {
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
	 * @return bool
	 */
	private function should_load_survey_tracking() {
		// Check if on survey single page or archive
		if ( is_singular( 'rm_survey' ) || is_post_type_archive( 'rm_survey' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Enqueue profile picture assets
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
		}
	}

	/**
	 * Enqueue survey accordion assets
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
	 */
	public function render_admin_page() {
		$views_file = RM_PANEL_EXT_PLUGIN_DIR . 'includes/admin/views/main-dashboard.php';
		if ( file_exists( $views_file ) ) {
			require_once $views_file;
		} else {
			$this->render_default_dashboard();
		}
	}

	/**
	 * Render default dashboard if view file doesn't exist
	 */
	private function render_default_dashboard() {
		?>
		<div class="wrap">
			<h1><?php _e( 'RM Panel Extensions', 'rm-panel-extensions' ); ?></h1>
			<div class="card">
				<h2><?php _e( 'Welcome to RM Panel Extensions', 'rm-panel-extensions' ); ?></h2>
				<p><?php _e( 'Version', 'rm-panel-extensions' ); ?>: <?php echo RM_PANEL_EXT_VERSION; ?></p>
				<p>
					<a href="<?php echo admin_url( 'admin.php?page=rm-panel-extensions-settings' ); ?>" class="button button-primary">
						<?php _e( 'Settings', 'rm-panel-extensions' ); ?>
					</a>
					<a href="<?php echo admin_url( 'edit.php?post_type=rm_survey' ); ?>" class="button">
						<?php _e( 'Manage Surveys', 'rm-panel-extensions' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		// Save settings if form is submitted
		if ( isset( $_POST['rm_panel_settings_nonce'] ) && 
		     wp_verify_nonce( $_POST['rm_panel_settings_nonce'], 'rm_panel_settings' ) ) {
			$this->save_settings();
		}

		$settings = get_option( 'rm_panel_extensions_settings', $this->get_default_settings() );
		
		$views_file = RM_PANEL_EXT_PLUGIN_DIR . 'includes/admin/views/settings-page.php';
		if ( file_exists( $views_file ) ) {
			require_once $views_file;
		} else {
			$this->render_default_settings( $settings );
		}
	}

	/**
	 * Render default settings page
	 */
	private function render_default_settings( $settings ) {
		?>
		<div class="wrap">
			<h1><?php _e( 'RM Panel Extensions Settings', 'rm-panel-extensions' ); ?></h1>
			
			<?php settings_errors( 'rm_panel_settings' ); ?>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'rm_panel_settings', 'rm_panel_settings_nonce' ); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row"><?php _e( 'Survey Module', 'rm-panel-extensions' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="rm_panel_settings[enable_survey_module]" value="1" 
									<?php checked( $settings['enable_survey_module'], 1 ); ?>>
								<?php _e( 'Enable Survey Module', 'rm-panel-extensions' ); ?>
							</label>
						</td>
					</tr>
				</table>
				
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render modules page
	 */
	public function render_modules_page() {
		$views_file = RM_PANEL_EXT_PLUGIN_DIR . 'includes/admin/views/modules-page.php';
		if ( file_exists( $views_file ) ) {
			require_once $views_file;
		} else {
			echo '<div class="wrap"><h1>' . __( 'Modules', 'rm-panel-extensions' ) . '</h1></div>';
		}
	}

	/**
	 * Render survey responses page
	 */
	public function render_survey_responses_page() {
		$views_file = RM_PANEL_EXT_PLUGIN_DIR . 'includes/admin/views/survey-responses-page.php';
		if ( file_exists( $views_file ) ) {
			require_once $views_file;
		} else {
			echo '<div class="wrap"><h1>' . __( 'Survey Responses', 'rm-panel-extensions' ) . '</h1></div>';
		}
	}

	// ============================================
	// SETTINGS MANAGEMENT
	// ============================================

	/**
	 * Get default settings
	 *
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
	 */
	public function check_db_update() {
		$installed_version = get_option( 'rm_panel_survey_db_version' );

		if ( version_compare( $installed_version, RM_PANEL_EXT_DB_VERSION, '<' ) ) {
			$this->create_all_tables();
			update_option( 'rm_panel_survey_db_version', RM_PANEL_EXT_DB_VERSION );
		}
	}

	/**
	 * Create all database tables
	 */
	private function create_all_tables() {
		$this->create_survey_tracking_table();
		$this->create_payment_methods_table();
		$this->create_withdrawal_requests_table();
	}

	/**
	 * Create survey tracking database table
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
			approval_status varchar(20) DEFAULT 'pending',
			approved_by bigint(20) DEFAULT NULL,
			approval_date datetime DEFAULT NULL,
			country varchar(100) DEFAULT NULL,
			return_time datetime DEFAULT NULL,
			admin_notes text DEFAULT NULL,
			waiting_since DATETIME DEFAULT NULL,
			last_reminder_sent DATETIME DEFAULT NULL,
			survey_paused_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY survey_id (survey_id),
			KEY status (status),
			KEY completion_status (completion_status),
			KEY approval_status (approval_status),
			KEY start_time (start_time),
			UNIQUE KEY user_survey (user_id, survey_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create payment methods table
	 */
	private function create_payment_methods_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rm_payment_methods';
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			method_name varchar(100) NOT NULL,
			method_type varchar(50) NOT NULL,
			icon varchar(255) DEFAULT NULL,
			description text DEFAULT NULL,
			required_fields longtext DEFAULT NULL,
			min_withdrawal decimal(10,2) DEFAULT 0.00,
			max_withdrawal decimal(10,2) DEFAULT NULL,
			processing_fee_type varchar(20) DEFAULT 'none',
			processing_fee_value decimal(10,2) DEFAULT 0.00,
			processing_days int(11) DEFAULT 3,
			instructions text DEFAULT NULL,
			is_active tinyint(1) DEFAULT 1,
			sort_order int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY method_type (method_type),
			KEY is_active (is_active)
		) $charset_collate;";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	/**
	 * Create withdrawal requests table
	 */
	private function create_withdrawal_requests_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rm_withdrawal_requests';
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			payment_method_id bigint(20) NOT NULL,
			amount decimal(10,2) NOT NULL,
			processing_fee decimal(10,2) DEFAULT 0.00,
			net_amount decimal(10,2) NOT NULL,
			payment_details longtext DEFAULT NULL,
			status varchar(50) DEFAULT 'pending',
			admin_notes text DEFAULT NULL,
			processed_by bigint(20) DEFAULT NULL,
			processed_at datetime DEFAULT NULL,
			transaction_reference varchar(255) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY payment_method_id (payment_method_id),
			KEY status (status),
			KEY created_at (created_at)
		) $charset_collate;";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
}

// ============================================
// PLUGIN INITIALIZATION
// ============================================

/**
 * Initialize the plugin
 *
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
 */
function rm_panel_extensions_deactivate() {
	// Flush rewrite rules
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'rm_panel_extensions_deactivate' );