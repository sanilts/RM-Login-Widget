<?php
/**
 * Main Dashboard View
 *
 * @package RM_Panel_Extensions
 * @since 1.2.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap rm-panel-admin">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="rm-panel-dashboard">
		<!-- Welcome Section -->
		<div class="rm-panel-welcome">
			<h2><?php esc_html_e( 'Welcome to RM Panel Extensions', 'rm-panel-extensions' ); ?></h2>
			<p><?php esc_html_e( 'A comprehensive suite of extensions for WordPress to enhance your website functionality.', 'rm-panel-extensions' ); ?></p>
			<div class="rm-panel-version">
				<span><?php esc_html_e( 'Version:', 'rm-panel-extensions' ); ?></span>
				<strong><?php echo esc_html( RM_PANEL_EXT_VERSION ); ?></strong>
			</div>
		</div>

		<?php
		// Check if all required files exist
		$required_files = array(
			'modules/survey/class-survey-module.php'        => __( 'Survey Module', 'rm-panel-extensions' ),
			'modules/elementor/class-elementor-module.php'  => __( 'Elementor Module', 'rm-panel-extensions' ),
			'modules/elementor/widgets/login-widget.php'    => __( 'Login Widget', 'rm-panel-extensions' ),
			'modules/elementor/widgets/survey-listing-widget.php' => __( 'Survey Listing Widget', 'rm-panel-extensions' ),
			'modules/elementor/templates/login-form.php'    => __( 'Login Form Template', 'rm-panel-extensions' ),
			'assets/css/elementor-widgets.css'              => __( 'Widget Styles', 'rm-panel-extensions' ),
			'assets/css/survey-styles.css'                  => __( 'Survey Styles', 'rm-panel-extensions' ),
			'assets/js/elementor-widgets.js'                => __( 'Widget Scripts', 'rm-panel-extensions' ),
			'assets/js/survey-scripts.js'                   => __( 'Survey Scripts', 'rm-panel-extensions' ),
		);

		$missing_files = array();
		foreach ( $required_files as $file => $name ) {
			if ( ! file_exists( RM_PANEL_EXT_PLUGIN_DIR . $file ) ) {
				$missing_files[ $file ] = $name;
			}
		}
		?>

		<?php if ( ! empty( $missing_files ) ) : ?>
			<div class="notice notice-warning" style="margin: 20px 0;">
				<p><strong><?php esc_html_e( 'Missing Files Detected:', 'rm-panel-extensions' ); ?></strong></p>
				<ul style="list-style: disc; margin-left: 20px;">
					<?php foreach ( $missing_files as $file => $name ) : ?>
						<li><?php echo esc_html( $name ); ?> - <code><?php echo esc_html( $file ); ?></code></li>
					<?php endforeach; ?>
				</ul>
				<p><?php esc_html_e( 'Please ensure all plugin files are properly uploaded.', 'rm-panel-extensions' ); ?></p>
			</div>
		<?php else : ?>
			<div class="notice notice-success" style="margin: 20px 0;">
				<p><?php esc_html_e( 'All required files are present and ready!', 'rm-panel-extensions' ); ?></p>
			</div>
		<?php endif; ?>

		<!-- Statistics Cards -->
		<div class="rm-panel-stats">
			<div class="rm-panel-stat-card">
				<div class="stat-icon">
					<span class="dashicons dashicons-admin-plugins"></span>
				</div>
				<div class="stat-content">
					<h3><?php esc_html_e( 'Active Modules', 'rm-panel-extensions' ); ?></h3>
					<p class="stat-number"><?php echo count( $this->modules ); ?></p>
				</div>
			</div>

			<div class="rm-panel-stat-card">
				<div class="stat-icon">
					<span class="dashicons dashicons-clipboard"></span>
				</div>
				<div class="stat-content">
					<h3><?php esc_html_e( 'Survey Module', 'rm-panel-extensions' ); ?></h3>
					<p class="stat-status <?php echo class_exists( 'RM_Panel_Survey_Module' ) ? 'active' : 'inactive'; ?>">
						<?php echo class_exists( 'RM_Panel_Survey_Module' ) ? esc_html__( 'Active', 'rm-panel-extensions' ) : esc_html__( 'Inactive', 'rm-panel-extensions' ); ?>
					</p>
				</div>
			</div>

			<div class="rm-panel-stat-card">
				<div class="stat-icon">
					<span class="dashicons dashicons-admin-customizer"></span>
				</div>
				<div class="stat-content">
					<h3><?php esc_html_e( 'Elementor Status', 'rm-panel-extensions' ); ?></h3>
					<p class="stat-status <?php echo did_action( 'elementor/loaded' ) ? 'active' : 'inactive'; ?>">
						<?php echo did_action( 'elementor/loaded' ) ? esc_html__( 'Active', 'rm-panel-extensions' ) : esc_html__( 'Inactive', 'rm-panel-extensions' ); ?>
					</p>
				</div>
			</div>

			<div class="rm-panel-stat-card">
				<div class="stat-icon">
					<span class="dashicons dashicons-translation"></span>
				</div>
				<div class="stat-content">
					<h3><?php esc_html_e( 'WPML Status', 'rm-panel-extensions' ); ?></h3>
					<p class="stat-status <?php echo function_exists( 'icl_object_id' ) ? 'active' : 'inactive'; ?>">
						<?php echo function_exists( 'icl_object_id' ) ? esc_html__( 'Active', 'rm-panel-extensions' ) : esc_html__( 'Not Installed', 'rm-panel-extensions' ); ?>
					</p>
				</div>
			</div>
		</div>

		<!-- Quick Links -->
		<div class="rm-panel-quick-links">
			<h3><?php esc_html_e( 'Quick Links', 'rm-panel-extensions' ); ?></h3>
			<div class="quick-links-grid">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rm-panel-extensions-settings' ) ); ?>" class="quick-link">
					<span class="dashicons dashicons-admin-settings"></span>
					<span><?php esc_html_e( 'Settings', 'rm-panel-extensions' ); ?></span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rm-panel-extensions-modules' ) ); ?>" class="quick-link">
					<span class="dashicons dashicons-admin-plugins"></span>
					<span><?php esc_html_e( 'Modules', 'rm-panel-extensions' ); ?></span>
				</a>
				<?php if ( post_type_exists( 'rm_survey' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=rm_survey' ) ); ?>" class="quick-link">
						<span class="dashicons dashicons-clipboard"></span>
						<span><?php esc_html_e( 'Surveys', 'rm-panel-extensions' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=survey_category&post_type=rm_survey' ) ); ?>" class="quick-link">
						<span class="dashicons dashicons-category"></span>
						<span><?php esc_html_e( 'Survey Categories', 'rm-panel-extensions' ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( did_action( 'elementor/loaded' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=elementor_library' ) ); ?>" class="quick-link">
						<span class="dashicons dashicons-admin-page"></span>
						<span><?php esc_html_e( 'Elementor Templates', 'rm-panel-extensions' ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<!-- Plugin Information -->
		<div class="rm-panel-info">
			<h3><?php esc_html_e( 'Plugin Information', 'rm-panel-extensions' ); ?></h3>
			<table class="widefat">
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Plugin Directory', 'rm-panel-extensions' ); ?></strong></td>
						<td><code><?php echo esc_html( RM_PANEL_EXT_PLUGIN_DIR ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Plugin URL', 'rm-panel-extensions' ); ?></strong></td>
						<td><code><?php echo esc_html( RM_PANEL_EXT_PLUGIN_URL ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'PHP Version', 'rm-panel-extensions' ); ?></strong></td>
						<td><?php echo esc_html( PHP_VERSION ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'WordPress Version', 'rm-panel-extensions' ); ?></strong></td>
						<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Active Modules', 'rm-panel-extensions' ); ?></strong></td>
						<td><?php echo esc_html( implode( ', ', array_keys( $this->modules ) ) ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>