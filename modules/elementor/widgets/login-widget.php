<?php
/**
 * RM Panel Login Widget for Elementor
 * 
 * @package RM_Panel_Extensions
 * @subpackage Modules/Elementor/Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RM_Panel_Login_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'rm_panel_login_widget';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __( 'RM Login Form', 'rm-panel-extensions' );
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-lock-user';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return [ 'rm-panel-widgets', 'general' ];
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return [ 'login', 'form', 'authentication', 'signin', 'rm panel', 'user', 'member', 'account', 'access' ];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        
        // Content Section - Form Settings
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Form Settings', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_labels',
            [
                'label' => __( 'Show Labels', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_placeholders',
            [
                'label' => __( 'Show Placeholders', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_remember',
            [
                'label' => __( 'Show Remember Me', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_register',
            [
                'label' => __( 'Show Register Link', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_lost_password',
            [
                'label' => __( 'Show Lost Password', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'custom_register_url',
            [
                'label' => __( 'Custom Registration URL', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __( 'https://your-site.com/register', 'rm-panel-extensions' ),
                'condition' => [
                    'show_register' => 'yes',
                ],
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'custom_lost_password_url',
            [
                'label' => __( 'Custom Lost Password URL', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __( 'https://your-site.com/lost-password', 'rm-panel-extensions' ),
                'condition' => [
                    'show_lost_password' => 'yes',
                ],
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->end_controls_section();

        // Text Customization Section
        $this->start_controls_section(
            'text_section',
            [
                'label' => __( 'Text Customization', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'username_label',
            [
                'label' => __( 'Username Label', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Username or Email', 'rm-panel-extensions' ),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'username_placeholder',
            [
                'label' => __( 'Username Placeholder', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Enter your username or email', 'rm-panel-extensions' ),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'password_label',
            [
                'label' => __( 'Password Label', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Password', 'rm-panel-extensions' ),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'password_placeholder',
            [
                'label' => __( 'Password Placeholder', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Enter your password', 'rm-panel-extensions' ),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'remember_text',
            [
                'label' => __( 'Remember Me Text', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Remember Me', 'rm-panel-extensions' ),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'login_button_text',
            [
                'label' => __( 'Login Button Text', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Login', 'rm-panel-extensions' ),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'register_link_text',
            [
                'label' => __( 'Register Link Text', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Register', 'rm-panel-extensions' ),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'lost_password_text',
            [
                'label' => __( 'Lost Password Text', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Lost your password?', 'rm-panel-extensions' ),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'logged_in_message',
            [
                'label' => __( 'Logged In Message', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __( 'Welcome, {user}! You are already logged in.', 'rm-panel-extensions' ),
                'description' => __( 'Use {user} to display the username', 'rm-panel-extensions' ),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'logout_link_text',
            [
                'label' => __( 'Logout Link Text', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Logout', 'rm-panel-extensions' ),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'loading_text',
            [
                'label' => __( 'Loading Text', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Logging in...', 'rm-panel-extensions' ),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'error_message',
            [
                'label' => __( 'Generic Error Message', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'An error occurred. Please try again.', 'rm-panel-extensions' ),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'success_message',
            [
                'label' => __( 'Success Message', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Login successful! Redirecting...', 'rm-panel-extensions' ),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->end_controls_section();

        // Redirect Section
        $this->start_controls_section(
            'redirect_section',
            [
                'label' => __( 'Redirection Settings', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'redirect_after_login',
            [
                'label' => __( 'Redirect After Login', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        // Get all WordPress roles
        $wp_roles = wp_roles()->get_names();
        
        foreach ( $wp_roles as $role_key => $role_name ) {
            $this->add_control(
                'redirect_' . $role_key,
                [
                    'label' => sprintf( __( '%s Redirect URL', 'rm-panel-extensions' ), $role_name ),
                    'type' => \Elementor\Controls_Manager::URL,
                    'placeholder' => __( 'https://your-site.com/dashboard', 'rm-panel-extensions' ),
                    'description' => sprintf( __( 'Redirect URL for %s role', 'rm-panel-extensions' ), $role_name ),
                    'condition' => [
                        'redirect_after_login' => 'yes',
                    ],
                    'dynamic' => [
                        'active' => true,
                    ],
                ]
            );
        }

        $this->add_control(
            'default_redirect',
            [
                'label' => __( 'Default Redirect URL', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __( 'https://your-site.com/dashboard', 'rm-panel-extensions' ),
                'description' => __( 'Fallback redirect if role-specific redirect is not set', 'rm-panel-extensions' ),
                'condition' => [
                    'redirect_after_login' => 'yes',
                ],
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->end_controls_section();

        // Register style controls
        $this->register_style_controls();
    }

    /**
     * Register style controls
     */
    private function register_style_controls() {
        // Form Style
        $this->start_controls_section(
            'form_style',
            [
                'label' => __( 'Form Style', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'form_bg_color',
            [
                'label' => __( 'Background Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'form_padding',
            [
                'label' => __( 'Padding', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'default' => [
                    'top' => '20',
                    'right' => '20',
                    'bottom' => '20',
                    'left' => '20',
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'form_margin',
            [
                'label' => __( 'Margin', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'form_border',
                'selector' => '{{WRAPPER}} .rm-login-form',
            ]
        );

        $this->add_control(
            'form_border_radius',
            [
                'label' => __( 'Border Radius', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'form_box_shadow',
                'selector' => '{{WRAPPER}} .rm-login-form',
            ]
        );

        $this->end_controls_section();

        // Additional style sections
        $this->register_input_styles();
        $this->register_button_styles();
        $this->register_label_styles();
        $this->register_link_styles();
        $this->register_message_styles();
    }

    /**
     * Register input style controls
     */
    private function register_input_styles() {
        $this->start_controls_section(
            'input_style',
            [
                'label' => __( 'Input Fields', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'input_typography',
                'selector' => '{{WRAPPER}} .rm-login-form input[type="text"], {{WRAPPER}} .rm-login-form input[type="password"], {{WRAPPER}} .rm-login-form input[type="email"]',
            ]
        );

        $this->start_controls_tabs( 'input_style_tabs' );

        $this->start_controls_tab(
            'input_normal_tab',
            [
                'label' => __( 'Normal', 'rm-panel-extensions' ),
            ]
        );

        $this->add_control(
            'input_text_color',
            [
                'label' => __( 'Text Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form input[type="text"], {{WRAPPER}} .rm-login-form input[type="password"], {{WRAPPER}} .rm-login-form input[type="email"]' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_bg_color',
            [
                'label' => __( 'Background Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form input[type="text"], {{WRAPPER}} .rm-login-form input[type="password"], {{WRAPPER}} .rm-login-form input[type="email"]' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_border_color',
            [
                'label' => __( 'Border Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form input[type="text"], {{WRAPPER}} .rm-login-form input[type="password"], {{WRAPPER}} .rm-login-form input[type="email"]' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'input_focus_tab',
            [
                'label' => __( 'Focus', 'rm-panel-extensions' ),
            ]
        );

        $this->add_control(
            'input_focus_text_color',
            [
                'label' => __( 'Text Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form input[type="text"]:focus, {{WRAPPER}} .rm-login-form input[type="password"]:focus, {{WRAPPER}} .rm-login-form input[type="email"]:focus' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_focus_bg_color',
            [
                'label' => __( 'Background Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form input[type="text"]:focus, {{WRAPPER}} .rm-login-form input[type="password"]:focus, {{WRAPPER}} .rm-login-form input[type="email"]:focus' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_focus_border_color',
            [
                'label' => __( 'Border Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form input[type="text"]:focus, {{WRAPPER}} .rm-login-form input[type="password"]:focus, {{WRAPPER}} .rm-login-form input[type="email"]:focus' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'input_border',
                'selector' => '{{WRAPPER}} .rm-login-form input[type="text"], {{WRAPPER}} .rm-login-form input[type="password"], {{WRAPPER}} .rm-login-form input[type="email"]',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'input_border_radius',
            [
                'label' => __( 'Border Radius', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form input[type="text"], {{WRAPPER}} .rm-login-form input[type="password"], {{WRAPPER}} .rm-login-form input[type="email"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'input_padding',
            [
                'label' => __( 'Padding', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form input[type="text"], {{WRAPPER}} .rm-login-form input[type="password"], {{WRAPPER}} .rm-login-form input[type="email"]' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'input_height',
            [
                'label' => __( 'Height', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [
                        'min' => 30,
                        'max' => 80,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form input[type="text"], {{WRAPPER}} .rm-login-form input[type="password"], {{WRAPPER}} .rm-login-form input[type="email"]' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register button style controls
     */
    private function register_button_styles() {
        $this->start_controls_section(
            'button_style',
            [
                'label' => __( 'Submit Button', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .rm-login-form button[type="submit"]',
            ]
        );

        $this->start_controls_tabs( 'button_style_tabs' );

        $this->start_controls_tab(
            'button_normal_tab',
            [
                'label' => __( 'Normal', 'rm-panel-extensions' ),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __( 'Text Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form button[type="submit"]' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_bg_color',
            [
                'label' => __( 'Background Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form button[type="submit"]' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'selector' => '{{WRAPPER}} .rm-login-form button[type="submit"]',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_box_shadow',
                'selector' => '{{WRAPPER}} .rm-login-form button[type="submit"]',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'button_hover_tab',
            [
                'label' => __( 'Hover', 'rm-panel-extensions' ),
            ]
        );

        $this->add_control(
            'button_hover_color',
            [
                'label' => __( 'Text Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form button[type="submit"]:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_bg_color',
            [
                'label' => __( 'Background Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#005a87',
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form button[type="submit"]:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_border_color',
            [
                'label' => __( 'Border Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form button[type="submit"]:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_hover_box_shadow',
                'selector' => '{{WRAPPER}} .rm-login-form button[type="submit"]:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'button_border_radius',
            [
                'label' => __( 'Border Radius', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'separator' => 'before',
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form button[type="submit"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label' => __( 'Padding', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form button[type="submit"]' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_width',
            [
                'label' => __( 'Width', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    '' => __( 'Default', 'rm-panel-extensions' ),
                    'auto' => __( 'Auto', 'rm-panel-extensions' ),
                    '100' => __( 'Full Width', 'rm-panel-extensions' ),
                    '50' => __( '50%', 'rm-panel-extensions' ),
                ],
                'default' => '100',
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form button[type="submit"]' => 'width: {{VALUE}}%;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register label style controls
     */
    private function register_label_styles() {
        $this->start_controls_section(
            'label_style',
            [
                'label' => __( 'Labels', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_labels' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'selector' => '{{WRAPPER}} .rm-login-form label',
            ]
        );

        $this->add_control(
            'label_color',
            [
                'label' => __( 'Label Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'label_margin',
            [
                'label' => __( 'Margin', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'label_spacing',
            [
                'label' => __( 'Spacing', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form .form-group' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register link style controls
     */
    private function register_link_styles() {
        $this->start_controls_section(
            'links_style',
            [
                'label' => __( 'Links', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'links_typography',
                'selector' => '{{WRAPPER}} .rm-login-form .form-links a',
            ]
        );

        $this->start_controls_tabs( 'links_style_tabs' );

        $this->start_controls_tab(
            'links_normal_tab',
            [
                'label' => __( 'Normal', 'rm-panel-extensions' ),
            ]
        );

        $this->add_control(
            'links_color',
            [
                'label' => __( 'Link Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form .form-links a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'links_text_decoration',
            [
                'label' => __( 'Text Decoration', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'none' => __( 'None', 'rm-panel-extensions' ),
                    'underline' => __( 'Underline', 'rm-panel-extensions' ),
                ],
                'default' => 'none',
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form .form-links a' => 'text-decoration: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'links_hover_tab',
            [
                'label' => __( 'Hover', 'rm-panel-extensions' ),
            ]
        );

        $this->add_control(
            'links_hover_color',
            [
                'label' => __( 'Link Hover Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#005a87',
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form .form-links a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'links_hover_text_decoration',
            [
                'label' => __( 'Text Decoration', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'none' => __( 'None', 'rm-panel-extensions' ),
                    'underline' => __( 'Underline', 'rm-panel-extensions' ),
                ],
                'default' => 'underline',
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form .form-links a:hover' => 'text-decoration: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'links_alignment',
            [
                'label' => __( 'Alignment', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'separator' => 'before',
                'options' => [
                    'left' => [
                        'title' => __( 'Left', 'rm-panel-extensions' ),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __( 'Center', 'rm-panel-extensions' ),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __( 'Right', 'rm-panel-extensions' ),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .rm-login-form .form-links' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'links_spacing',
            [
                'label' => __( 'Spacing Between Links', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
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
                    '{{WRAPPER}} .rm-login-form .form-links a' => 'margin: 0 {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Register message style controls
     */
    private function register_message_styles() {
        $this->start_controls_section(
            'message_style',
            [
                'label' => __( 'Messages', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'message_typography',
                'selector' => '{{WRAPPER}} .login-messages .error, {{WRAPPER}} .login-messages .success',
            ]
        );

        // Error Message Style
        $this->add_control(
            'error_message_heading',
            [
                'label' => __( 'Error Message', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'error_text_color',
            [
                'label' => __( 'Text Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#721c24',
                'selectors' => [
                    '{{WRAPPER}} .login-messages .error' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'error_bg_color',
            [
                'label' => __( 'Background Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f8d7da',
                'selectors' => [
                    '{{WRAPPER}} .login-messages .error' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'error_border_color',
            [
                'label' => __( 'Border Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f5c6cb',
                'selectors' => [
                    '{{WRAPPER}} .login-messages .error' => 'border: 1px solid {{VALUE}};',
                ],
            ]
        );

        // Success Message Style
        $this->add_control(
            'success_message_heading',
            [
                'label' => __( 'Success Message', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'success_text_color',
            [
                'label' => __( 'Text Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#155724',
                'selectors' => [
                    '{{WRAPPER}} .login-messages .success' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'success_bg_color',
            [
                'label' => __( 'Background Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#d4edda',
                'selectors' => [
                    '{{WRAPPER}} .login-messages .success' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'success_border_color',
            [
                'label' => __( 'Border Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#c3e6cb',
                'selectors' => [
                    '{{WRAPPER}} .login-messages .success' => 'border: 1px solid {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'message_border_radius',
            [
                'label' => __( 'Border Radius', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'separator' => 'before',
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .login-messages .error, {{WRAPPER}} .login-messages .success' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'message_padding',
            [
                'label' => __( 'Padding', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .login-messages .error, {{WRAPPER}} .login-messages .success' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
        
        // Get custom text values with WPML support
        $texts = $this->get_translated_texts( $settings );
        
        if ( is_user_logged_in() && ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            $this->render_logged_in_content( $texts );
            return;
        }
        
        $this->render_login_form( $settings, $texts );
    }

    /**
     * Get translated texts
     */
    private function get_translated_texts( $settings ) {
        $texts = [
            'username_label' => ! empty( $settings['username_label'] ) ? $settings['username_label'] : __( 'Username or Email', 'rm-panel-extensions' ),
            'username_placeholder' => ! empty( $settings['username_placeholder'] ) ? $settings['username_placeholder'] : __( 'Enter your username or email', 'rm-panel-extensions' ),
            'password_label' => ! empty( $settings['password_label'] ) ? $settings['password_label'] : __( 'Password', 'rm-panel-extensions' ),
            'password_placeholder' => ! empty( $settings['password_placeholder'] ) ? $settings['password_placeholder'] : __( 'Enter your password', 'rm-panel-extensions' ),
            'remember_text' => ! empty( $settings['remember_text'] ) ? $settings['remember_text'] : __( 'Remember Me', 'rm-panel-extensions' ),
            'login_button_text' => ! empty( $settings['login_button_text'] ) ? $settings['login_button_text'] : __( 'Login', 'rm-panel-extensions' ),
            'register_link_text' => ! empty( $settings['register_link_text'] ) ? $settings['register_link_text'] : __( 'Register', 'rm-panel-extensions' ),
            'lost_password_text' => ! empty( $settings['lost_password_text'] ) ? $settings['lost_password_text'] : __( 'Lost your password?', 'rm-panel-extensions' ),
            'logged_in_message' => ! empty( $settings['logged_in_message'] ) ? $settings['logged_in_message'] : __( 'Welcome, {user}! You are already logged in.', 'rm-panel-extensions' ),
            'logout_link_text' => ! empty( $settings['logout_link_text'] ) ? $settings['logout_link_text'] : __( 'Logout', 'rm-panel-extensions' ),
            'loading_text' => ! empty( $settings['loading_text'] ) ? $settings['loading_text'] : __( 'Logging in...', 'rm-panel-extensions' ),
            'error_message' => ! empty( $settings['error_message'] ) ? $settings['error_message'] : __( 'An error occurred. Please try again.', 'rm-panel-extensions' ),
            'success_message' => ! empty( $settings['success_message'] ) ? $settings['success_message'] : __( 'Login successful! Redirecting...', 'rm-panel-extensions' ),
        ];
        
        // Apply WPML filters if WPML is active
        if ( function_exists( 'icl_t' ) ) {
            foreach ( $texts as $key => $value ) {
                $texts[$key] = icl_t( 'rm-panel-extensions', $key, $value );
            }
        }
        
        return $texts;
    }

    /**
     * Render logged in content
     */
    private function render_logged_in_content( $texts ) {
        $current_user = wp_get_current_user();
        $display_message = str_replace( '{user}', $current_user->display_name, $texts['logged_in_message'] );
        $logout_url = wp_logout_url( apply_filters( 'wpml_home_url', home_url() ) );
        ?>
        <div class="rm-logged-in-message">
            <div class="rm-logged-in-avatar">
                <?php echo get_avatar( $current_user->ID, 80 ); ?>
            </div>
            <div class="rm-logged-in-content">
                <p><?php echo esc_html( $display_message ); ?></p>
                <a href="<?php echo esc_url( $logout_url ); ?>" class="rm-logout-link">
                    <?php echo esc_html( $texts['logout_link_text'] ); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render login form
     */
    private function render_login_form( $settings, $texts ) {
        // Prepare redirect URLs
        $redirect_urls = $this->get_redirect_urls( $settings );
        $default_redirect = ! empty( $settings['default_redirect']['url'] ) ? $settings['default_redirect']['url'] : admin_url();
        
        // Get URLs with WPML support
        $register_url = $this->get_register_url( $settings );
        $lost_password_url = $this->get_lost_password_url( $settings );
        
        // Include the form template
        $template_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/elementor/templates/login-form.php';
        if ( file_exists( $template_file ) ) {
            include $template_file;
        } else {
            // Fallback if template doesn't exist
            echo '<p>' . __( 'Login form template not found. Please ensure all plugin files are properly installed.', 'rm-panel-extensions' ) . '</p>';
        }
    }

    /**
     * Get redirect URLs
     */
    private function get_redirect_urls( $settings ) {
        $redirect_urls = [];
        $wp_roles = wp_roles()->get_names();
        
        foreach ( $wp_roles as $role_key => $role_name ) {
            if ( ! empty( $settings['redirect_' . $role_key]['url'] ) ) {
                $redirect_urls[$role_key] = $settings['redirect_' . $role_key]['url'];
            }
        }
        
        return $redirect_urls;
    }

    /**
     * Get register URL
     */
    private function get_register_url( $settings ) {
        $url = ! empty( $settings['custom_register_url']['url'] ) 
            ? $settings['custom_register_url']['url'] 
            : wp_registration_url();
        
        // Apply WPML URL filter
        if ( function_exists( 'icl_object_id' ) ) {
            $url = apply_filters( 'wpml_permalink', $url );
        }
        
        return esc_url( $url );
    }

    /**
     * Get lost password URL
     */
    private function get_lost_password_url( $settings ) {
        $url = ! empty( $settings['custom_lost_password_url']['url'] ) 
            ? $settings['custom_lost_password_url']['url'] 
            : wp_lostpassword_url();
        
        // Apply WPML URL filter
        if ( function_exists( 'icl_object_id' ) ) {
            $url = apply_filters( 'wpml_permalink', $url );
        }
        
        return esc_url( $url );
    }

    /**
     * Render widget output in the editor
     */
    protected function content_template() {
        ?>
        <#
        var showLabels = settings.show_labels === 'yes';
        var showPlaceholders = settings.show_placeholders === 'yes';
        var showRemember = settings.show_remember === 'yes';
        var showRegister = settings.show_register === 'yes';
        var showLostPassword = settings.show_lost_password === 'yes';
        #>
        <div class="rm-login-form-wrapper">
            <form class="rm-login-form">
                <div class="form-group">
                    <# if ( showLabels ) { #>
                        <label>{{{ settings.username_label || 'Username or Email' }}}</label>
                    <# } #>
                    <input type="text" class="form-control" 
                        placeholder="{{ showPlaceholders ? (settings.username_placeholder || 'Enter your username or email') : '' }}" readonly>
                </div>
                
                <div class="form-group">
                    <# if ( showLabels ) { #>
                        <label>{{{ settings.password_label || 'Password' }}}</label>
                    <# } #>
                    <input type="password" class="form-control" 
                        placeholder="{{ showPlaceholders ? (settings.password_placeholder || 'Enter your password') : '' }}" readonly>
                </div>
                
                <# if ( showRemember ) { #>
                    <div class="form-group remember-me">
                        <label>
                            <input type="checkbox" value="1">
                            <span>{{{ settings.remember_text || 'Remember Me' }}}</span>
                        </label>
                    </div>
                <# } #>
                
                <div class="form-group">
                    <button type="submit" class="btn-login">
                        <span class="button-text">{{{ settings.login_button_text || 'Login' }}}</span>
                    </button>
                </div>
                
                <div class="form-links">
                    <# if ( showRegister ) { #>
                        <a href="#" class="register-link">{{{ settings.register_link_text || 'Register' }}}</a>
                    <# } #>
                    
                    <# if ( showRegister && showLostPassword ) { #>
                        <span class="link-separator">|</span>
                    <# } #>
                    
                    <# if ( showLostPassword ) { #>
                        <a href="#" class="lost-password-link">{{{ settings.lost_password_text || 'Lost your password?' }}}</a>
                    <# } #>
                </div>
            </form>
        </div>
        <?php
    }
}