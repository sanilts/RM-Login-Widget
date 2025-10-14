<?php
/**
 * Login Form Template
 * 
 * @package RM_Panel_Extensions
 * @subpackage Modules/Elementor/Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="rm-login-form-wrapper">
    <form class="rm-login-form" method="post" action="" data-widget-id="<?php echo esc_attr( $this->get_id() ); ?>">
        <?php wp_nonce_field( 'rm_panel_login_nonce', 'rm_panel_login_nonce_field' ); ?>
        <input type="hidden" name="redirect_urls" value="<?php echo esc_attr( json_encode( $redirect_urls ) ); ?>">
        <input type="hidden" name="default_redirect" value="<?php echo esc_attr( $default_redirect ); ?>">
        <input type="hidden" name="success_message" value="<?php echo esc_attr( $texts['success_message'] ); ?>">
        <input type="hidden" name="error_message" value="<?php echo esc_attr( $texts['error_message'] ); ?>">
        <input type="hidden" name="loading_text" value="<?php echo esc_attr( $texts['loading_text'] ); ?>">
        <input type="hidden" name="login_button_text" value="<?php echo esc_attr( $texts['login_button_text'] ); ?>">
        
        <div class="form-group">
            <?php if ( $settings['show_labels'] == 'yes' ) : ?>
                <label for="rm-username-<?php echo esc_attr( $this->get_id() ); ?>">
                    <?php echo esc_html( $texts['username_label'] ); ?>
                </label>
            <?php endif; ?>
            <input type="text" 
                   name="username" 
                   id="rm-username-<?php echo esc_attr( $this->get_id() ); ?>" 
                   class="form-control" 
                   <?php echo $settings['show_placeholders'] == 'yes' ? 'placeholder="' . esc_attr( $texts['username_placeholder'] ) . '"' : ''; ?>
                   required>
        </div>
        
        <div class="form-group">
            <?php if ( $settings['show_labels'] == 'yes' ) : ?>
                <label for="rm-password-<?php echo esc_attr( $this->get_id() ); ?>">
                    <?php echo esc_html( $texts['password_label'] ); ?>
                </label>
            <?php endif; ?>
            <input type="password" 
                   name="password" 
                   id="rm-password-<?php echo esc_attr( $this->get_id() ); ?>" 
                   class="form-control" 
                   <?php echo $settings['show_placeholders'] == 'yes' ? 'placeholder="' . esc_attr( $texts['password_placeholder'] ) . '"' : ''; ?>
                   required>
        </div>
        
        <?php if ( $settings['show_remember'] == 'yes' ) : ?>
            <div class="form-group remember-me">
                <label for="rm-remember-<?php echo esc_attr( $this->get_id() ); ?>">
                    <input type="checkbox" 
                           name="remember" 
                           id="rm-remember-<?php echo esc_attr( $this->get_id() ); ?>" 
                           value="1">
                    <span><?php echo esc_html( $texts['remember_text'] ); ?></span>
                </label>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <button type="submit" name="rm_login_submit" class="btn-login">
                <span class="button-text"><?php echo esc_html( $texts['login_button_text'] ); ?></span>
                <span class="button-loading" style="display:none;">
                    <span class="rm-spinner"></span>
                    <?php echo esc_html( $texts['loading_text'] ); ?>
                </span>
            </button>
        </div>
        
        <div class="form-links">
            <?php if ( $settings['show_register'] == 'yes' && get_option( 'users_can_register' ) ) : ?>
                <a href="<?php echo esc_url( $register_url ); ?>" class="register-link">
                    <?php echo esc_html( $texts['register_link_text'] ); ?>
                </a>
            <?php endif; ?>
            
            <?php if ( $settings['show_register'] == 'yes' && $settings['show_lost_password'] == 'yes' && get_option( 'users_can_register' ) ) : ?>
                <span class="link-separator">|</span>
            <?php endif; ?>
            
            <?php if ( $settings['show_lost_password'] == 'yes' ) : ?>
                <a href="<?php echo esc_url( $lost_password_url ); ?>" class="lost-password-link">
                    <?php echo esc_html( $texts['lost_password_text'] ); ?>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="login-messages"></div>
    </form>
</div>