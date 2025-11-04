<?php
/**
 * Balance Sidebar Widget for Elementor
 * 
 * Compact widget designed for sidebars showing balance and withdrawal button
 * 
 * @package RM_Panel_Extensions
 * @version 1.0.0
 */

namespace RMPanelExtensions\Modules\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if (!defined('ABSPATH')) {
    exit;
}

class Balance_Sidebar_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'rm-balance-sidebar';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('Balance Sidebar', 'rm-panel-extensions');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-sidebar';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['rm-panel-widgets'];
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return ['balance', 'sidebar', 'withdrawal', 'earnings', 'wallet', 'money', 'rm panel'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {

        // ============================================
        // CONTENT TAB
        // ============================================

        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_icon',
            [
                'label' => __('Show Icon', 'rm-panel-extensions'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'rm-panel-extensions'),
                'label_off' => __('Hide', 'rm-panel-extensions'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'icon_type',
            [
                'label' => __('Icon Type', 'rm-panel-extensions'),
                'type' => Controls_Manager::SELECT,
                'default' => 'emoji',
                'options' => [
                    'emoji' => __('Emoji', 'rm-panel-extensions'),
                    'dashicon' => __('Dashicon', 'rm-panel-extensions'),
                ],
                'condition' => [
                    'show_icon' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'emoji_icon',
            [
                'label' => __('Emoji Icon', 'rm-panel-extensions'),
                'type' => Controls_Manager::TEXT,
                'default' => '💰',
                'condition' => [
                    'show_icon' => 'yes',
                    'icon_type' => 'emoji',
                ],
            ]
        );

        $this->add_control(
            'dashicon_class',
            [
                'label' => __('Dashicon Class', 'rm-panel-extensions'),
                'type' => Controls_Manager::TEXT,
                'default' => 'dashicons-money-alt',
                'description' => __('e.g., dashicons-money-alt, dashicons-bank', 'rm-panel-extensions'),
                'condition' => [
                    'show_icon' => 'yes',
                    'icon_type' => 'dashicon',
                ],
            ]
        );

        $this->end_controls_section();

        // Text Customization Section
        $this->start_controls_section(
            'text_section',
            [
                'label' => __('Text Labels', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'available_balance_label',
            [
                'label' => __('Available Balance Label', 'rm-panel-extensions'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Available Balance', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'total_earned_label',
            [
                'label' => __('Total Earned Label', 'rm-panel-extensions'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Total Earned', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'total_withdrawn_label',
            [
                'label' => __('Total Withdrawn Label', 'rm-panel-extensions'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Total Withdrawn', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => __('Button Text', 'rm-panel-extensions'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Request Withdrawal', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'no_balance_message',
            [
                'label' => __('No Balance Message', 'rm-panel-extensions'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Complete surveys to start earning!', 'rm-panel-extensions'),
            ]
        );

        $this->end_controls_section();

        // ============================================
        // STYLE TAB
        // ============================================

        $this->register_style_controls();
    }

    /**
     * Register style controls
     */
    private function register_style_controls() {

        // Container Style
        $this->start_controls_section(
            'container_style',
            [
                'label' => __('Container', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'container_bg',
            [
                'label' => __('Background Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rm-balance-sidebar-widget' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'container_border',
                'selector' => '{{WRAPPER}} .rm-balance-sidebar-widget',
            ]
        );

        $this->add_control(
            'container_border_radius',
            [
                'label' => __('Border Radius', 'rm-panel-extensions'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'unit' => 'px',
                    'top' => 12,
                    'right' => 12,
                    'bottom' => 12,
                    'left' => 12,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-balance-sidebar-widget' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'container_shadow',
                'selector' => '{{WRAPPER}} .rm-balance-sidebar-widget',
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label' => __('Padding', 'rm-panel-extensions'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'unit' => 'px',
                    'top' => 25,
                    'right' => 20,
                    'bottom' => 25,
                    'left' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-balance-sidebar-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Balance Header Style
        $this->start_controls_section(
            'balance_header_style',
            [
                'label' => __('Balance Header', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'header_bg',
            [
                'label' => __('Background Type', 'rm-panel-extensions'),
                'type' => Controls_Manager::SELECT,
                'default' => 'gradient',
                'options' => [
                    'solid' => __('Solid Color', 'rm-panel-extensions'),
                    'gradient' => __('Gradient', 'rm-panel-extensions'),
                ],
            ]
        );

        $this->add_control(
            'header_solid_bg',
            [
                'label' => __('Background Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#667eea',
                'condition' => [
                    'header_bg' => 'solid',
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-balance-header' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'header_gradient_color_1',
            [
                'label' => __('Gradient Color 1', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#667eea',
                'condition' => [
                    'header_bg' => 'gradient',
                ],
            ]
        );

        $this->add_control(
            'header_gradient_color_2',
            [
                'label' => __('Gradient Color 2', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#764ba2',
                'condition' => [
                    'header_bg' => 'gradient',
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-balance-header' => 'background: linear-gradient(135deg, {{header_gradient_color_1.VALUE}} 0%, {{VALUE}} 100%);',
                ],
            ]
        );

        $this->add_control(
            'header_text_color',
            [
                'label' => __('Text Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-balance-header' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .rm-sidebar-balance-label' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .rm-sidebar-balance-amount' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'header_padding',
            [
                'label' => __('Padding', 'rm-panel-extensions'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'unit' => 'px',
                    'top' => 20,
                    'right' => 20,
                    'bottom' => 20,
                    'left' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-balance-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'header_icon_size',
            [
                'label' => __('Icon Size', 'rm-panel-extensions'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 20,
                        'max' => 80,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 36,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-balance-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .rm-sidebar-balance-icon .dashicons' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Balance Amount Style
        $this->start_controls_section(
            'balance_amount_style',
            [
                'label' => __('Balance Amount', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'balance_amount_typography',
                'selector' => '{{WRAPPER}} .rm-sidebar-balance-amount',
            ]
        );

        $this->end_controls_section();

        // Stats Style
        $this->start_controls_section(
            'stats_style',
            [
                'label' => __('Statistics', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'stats_bg',
            [
                'label' => __('Background Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#f9fafb',
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-stat-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'stats_label_typography',
                'label' => __('Label Typography', 'rm-panel-extensions'),
                'selector' => '{{WRAPPER}} .rm-sidebar-stat-label',
            ]
        );

        $this->add_control(
            'stats_label_color',
            [
                'label' => __('Label Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#6b7280',
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-stat-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'stats_value_typography',
                'label' => __('Value Typography', 'rm-panel-extensions'),
                'selector' => '{{WRAPPER}} .rm-sidebar-stat-value',
            ]
        );

        $this->add_control(
            'stats_value_color',
            [
                'label' => __('Value Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#1f2937',
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-stat-value' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'stats_padding',
            [
                'label' => __('Padding', 'rm-panel-extensions'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'unit' => 'px',
                    'top' => 12,
                    'right' => 15,
                    'bottom' => 12,
                    'left' => 15,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-stat-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'stats_spacing',
            [
                'label' => __('Spacing Between Items', 'rm-panel-extensions'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 10,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-stats' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Button Style
        $this->start_controls_section(
            'button_style',
            [
                'label' => __('Withdrawal Button', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .rm-sidebar-withdrawal-btn',
            ]
        );

        $this->start_controls_tabs('button_tabs');

        $this->start_controls_tab(
            'button_normal_tab',
            [
                'label' => __('Normal', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'button_bg_color',
            [
                'label' => __('Background Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#3b82f6',
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-withdrawal-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __('Text Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-withdrawal-btn' => 'color: {{VALUE}};',
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
            'button_hover_bg',
            [
                'label' => __('Background Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2563eb',
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-withdrawal-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_text',
            [
                'label' => __('Text Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-withdrawal-btn:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'button_border_radius',
            [
                'label' => __('Border Radius', 'rm-panel-extensions'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'separator' => 'before',
                'default' => [
                    'unit' => 'px',
                    'top' => 8,
                    'right' => 8,
                    'bottom' => 8,
                    'left' => 8,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-withdrawal-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label' => __('Padding', 'rm-panel-extensions'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'unit' => 'px',
                    'top' => 12,
                    'right' => 20,
                    'bottom' => 12,
                    'left' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-sidebar-withdrawal-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend
     */
    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="rm-sidebar-notice">' . 
                 __('Please log in to view your balance.', 'rm-panel-extensions') . 
                 '</div>';
            return;
        }

        $settings = $this->get_settings_for_display();
        $user_id = get_current_user_id();

        // Get user balance data
        $balance_data = $this->get_user_balance_data($user_id);

        // Check if payment methods exist
        $has_payment_methods = false;
        if (class_exists('RM_Payment_Methods')) {
            $payment_methods = \RM_Payment_Methods::get_instance()->get_active_methods();
            $has_payment_methods = !empty($payment_methods);
        }

        ?>
        <div class="rm-balance-sidebar-widget">
            
            <!-- Balance Header -->
            <div class="rm-sidebar-balance-header">
                <?php if ($settings['show_icon'] === 'yes') : ?>
                    <div class="rm-sidebar-balance-icon">
                        <?php if ($settings['icon_type'] === 'emoji') : ?>
                            <?php echo esc_html($settings['emoji_icon']); ?>
                        <?php else : ?>
                            <span class="dashicons <?php echo esc_attr($settings['dashicon_class']); ?>"></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="rm-sidebar-balance-info">
                    <div class="rm-sidebar-balance-label">
                        <?php echo esc_html($settings['available_balance_label']); ?>
                    </div>
                    <div class="rm-sidebar-balance-amount">
                        $<?php echo number_format($balance_data['available_balance'], 2); ?>
                    </div>
                </div>
            </div>

            <!-- Withdrawal Button -->
            <?php if ($balance_data['available_balance'] > 0 && $has_payment_methods) : ?>
                <div class="rm-sidebar-button-wrapper">
                    <button type="button" class="rm-sidebar-withdrawal-btn" id="rm-sidebar-open-withdrawal">
                        <span class="dashicons dashicons-download"></span>
                        <?php echo esc_html($settings['button_text']); ?>
                    </button>
                </div>
            <?php elseif (!$has_payment_methods) : ?>
                <div class="rm-sidebar-no-methods">
                    <small><?php _e('Payment methods not available', 'rm-panel-extensions'); ?></small>
                </div>
            <?php else : ?>
                <div class="rm-sidebar-no-balance">
                    <small><?php echo esc_html($settings['no_balance_message']); ?></small>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="rm-sidebar-stats">
                <div class="rm-sidebar-stat-item">
                    <div class="rm-sidebar-stat-label">
                        <?php echo esc_html($settings['total_earned_label']); ?>
                    </div>
                    <div class="rm-sidebar-stat-value">
                        $<?php echo number_format($balance_data['total_earned'], 2); ?>
                    </div>
                </div>

                <div class="rm-sidebar-stat-item">
                    <div class="rm-sidebar-stat-label">
                        <?php echo esc_html($settings['total_withdrawn_label']); ?>
                    </div>
                    <div class="rm-sidebar-stat-value">
                        $<?php echo number_format($balance_data['total_withdrawn'], 2); ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- Withdrawal Modal -->
        <?php if ($has_payment_methods) : ?>
            <div id="rm-sidebar-withdrawal-modal" class="rm-sidebar-modal" style="display: none;">
                <div class="rm-sidebar-modal-content">
                    <span class="rm-sidebar-modal-close">&times;</span>
                    <h3><?php _e('Request Withdrawal', 'rm-panel-extensions'); ?></h3>
                    <div class="rm-sidebar-modal-body">
                        <?php echo do_shortcode('[rm_withdrawal_form]'); ?>
                    </div>
                </div>
            </div>

            <script>
            jQuery(document).ready(function($) {
                $('#rm-sidebar-open-withdrawal').on('click', function() {
                    $('#rm-sidebar-withdrawal-modal').fadeIn();
                });

                $('.rm-sidebar-modal-close, .rm-sidebar-modal').on('click', function(e) {
                    if (e.target === this) {
                        $('#rm-sidebar-withdrawal-modal').fadeOut();
                    }
                });
            });
            </script>
        <?php endif; ?>
        <?php
    }

    /**
     * Get user balance data with corrected calculations
     */
    private function get_user_balance_data($user_id) {
        global $wpdb;
        
        $available_balance = get_user_meta($user_id, 'rm_withdrawable_balance', true) ?: 0;
        $total_earned = get_user_meta($user_id, 'rm_total_earnings', true) ?: 0;
        
        // Calculate total withdrawn correctly from completed withdrawals only
        $withdrawals_table = $wpdb->prefix . 'rm_withdrawal_requests';
        $total_withdrawn = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(net_amount) FROM $withdrawals_table WHERE user_id = %d AND status = 'completed'",
            $user_id
        )) ?: 0;

        return [
            'available_balance' => floatval($available_balance),
            'total_earned' => floatval($total_earned),
            'total_withdrawn' => floatval($total_withdrawn),
        ];
    }

    /**
     * Render widget output in the editor
     */
    protected function content_template() {
        ?>
        <#
        var showIcon = settings.show_icon === 'yes';
        var iconType = settings.icon_type;
        #>
        
        <div class="rm-balance-sidebar-widget">
            
            <!-- Balance Header -->
            <div class="rm-sidebar-balance-header">
                <# if (showIcon) { #>
                    <div class="rm-sidebar-balance-icon">
                        <# if (iconType === 'emoji') { #>
                            {{{ settings.emoji_icon }}}
                        <# } else { #>
                            <span class="dashicons {{{ settings.dashicon_class }}}"></span>
                        <# } #>
                    </div>
                <# } #>
                
                <div class="rm-sidebar-balance-info">
                    <div class="rm-sidebar-balance-label">
                        {{{ settings.available_balance_label }}}
                    </div>
                    <div class="rm-sidebar-balance-amount">
                        $1,234.56
                    </div>
                </div>
            </div>

            <!-- Withdrawal Button -->
            <div class="rm-sidebar-button-wrapper">
                <button type="button" class="rm-sidebar-withdrawal-btn">
                    <span class="dashicons dashicons-download"></span>
                    {{{ settings.button_text }}}
                </button>
            </div>

            <!-- Statistics -->
            <div class="rm-sidebar-stats">
                <div class="rm-sidebar-stat-item">
                    <div class="rm-sidebar-stat-label">
                        {{{ settings.total_earned_label }}}
                    </div>
                    <div class="rm-sidebar-stat-value">
                        $2,500.00
                    </div>
                </div>

                <div class="rm-sidebar-stat-item">
                    <div class="rm-sidebar-stat-label">
                        {{{ settings.total_withdrawn_label }}}
                    </div>
                    <div class="rm-sidebar-stat-value">
                        $1,200.00
                    </div>
                </div>
            </div>

        </div>
        <?php
    }
}