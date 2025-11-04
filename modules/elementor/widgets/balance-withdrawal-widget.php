<?php
/**
 * Balance & Withdrawal Widget for Elementor
 * 
 * Displays user's available balance, withdrawal form, and payment history
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

class Balance_Withdrawal_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'rm-balance-withdrawal';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('Balance & Withdrawal', 'rm-panel-extensions');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-price-table';
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
        return ['balance', 'withdrawal', 'payment', 'earnings', 'wallet', 'money', 'rm panel'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {

        // ============================================
        // CONTENT TAB
        // ============================================

        // Balance Display Section
        $this->start_controls_section(
            'balance_section',
            [
                'label' => __('Balance Display', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_total_earned',
            [
                'label' => __('Show Total Earned', 'rm-panel-extensions'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'rm-panel-extensions'),
                'label_off' => __('Hide', 'rm-panel-extensions'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_total_withdrawn',
            [
                'label' => __('Show Total Withdrawn', 'rm-panel-extensions'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'rm-panel-extensions'),
                'label_off' => __('Hide', 'rm-panel-extensions'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_pending_withdrawals',
            [
                'label' => __('Show Pending Withdrawals', 'rm-panel-extensions'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'rm-panel-extensions'),
                'label_off' => __('Hide', 'rm-panel-extensions'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Withdrawal Form Section
        $this->start_controls_section(
            'withdrawal_form_section',
            [
                'label' => __('Withdrawal Form', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'enable_withdrawal_form',
            [
                'label' => __('Enable Withdrawal Form', 'rm-panel-extensions'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'rm-panel-extensions'),
                'label_off' => __('No', 'rm-panel-extensions'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'withdrawal_button_text',
            [
                'label' => __('Withdrawal Button Text', 'rm-panel-extensions'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Request Withdrawal', 'rm-panel-extensions'),
                'condition' => [
                    'enable_withdrawal_form' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Payment History Section
        $this->start_controls_section(
            'payment_history_section',
            [
                'label' => __('Payment History', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_payment_history',
            [
                'label' => __('Show Payment History', 'rm-panel-extensions'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'rm-panel-extensions'),
                'label_off' => __('Hide', 'rm-panel-extensions'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'history_items_per_page',
            [
                'label' => __('Items Per Page', 'rm-panel-extensions'),
                'type' => Controls_Manager::NUMBER,
                'min' => 5,
                'max' => 50,
                'step' => 5,
                'default' => 10,
                'condition' => [
                    'show_payment_history' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Text Customization Section
        $this->start_controls_section(
            'text_section',
            [
                'label' => __('Text Customization', 'rm-panel-extensions'),
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
            'pending_label',
            [
                'label' => __('Pending Label', 'rm-panel-extensions'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Pending', 'rm-panel-extensions'),
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
        
         // Withdrawal Link Section
        $this->start_controls_section(
            'withdrawal_link_section',
            [
                'label' => __('Withdrawal Link', 'rm-panel-extensions'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'withdrawal_action_type',
            [
                'label' => __('Withdrawal Action', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'modal',
                'options' => [
                    'modal' => __('Open Modal (Default)', 'rm-panel-extensions'),
                    'custom_link' => __('Custom Link', 'rm-panel-extensions'),
                ],
                'description' => __('Choose how the withdrawal button should work', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'custom_withdrawal_url',
            [
                'label' => __('Custom Withdrawal Page URL', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __('https://your-site.com/withdrawal', 'rm-panel-extensions'),
                'description' => __('Link to your custom withdrawal page', 'rm-panel-extensions'),
                'condition' => [
                    'withdrawal_action_type' => 'custom_link',
                ],
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'open_in_new_tab',
            [
                'label' => __('Open in New Tab', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'rm-panel-extensions'),
                'label_off' => __('No', 'rm-panel-extensions'),
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => [
                    'withdrawal_action_type' => 'custom_link',
                ],
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

        // Balance Card Style
        $this->start_controls_section(
            'balance_card_style',
            [
                'label' => __('Balance Card', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'balance_card_bg',
            [
                'label' => __('Background Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rm-balance-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'balance_card_border',
                'selector' => '{{WRAPPER}} .rm-balance-card',
            ]
        );

        $this->add_control(
            'balance_card_border_radius',
            [
                'label' => __('Border Radius', 'rm-panel-extensions'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .rm-balance-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'balance_card_shadow',
                'selector' => '{{WRAPPER}} .rm-balance-card',
            ]
        );

        $this->add_responsive_control(
            'balance_card_padding',
            [
                'label' => __('Padding', 'rm-panel-extensions'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .rm-balance-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                'selector' => '{{WRAPPER}} .rm-balance-amount',
            ]
        );

        $this->add_control(
            'balance_amount_color',
            [
                'label' => __('Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2ea44f',
                'selectors' => [
                    '{{WRAPPER}} .rm-balance-amount' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Withdrawal Button Style
        $this->start_controls_section(
            'withdrawal_button_style',
            [
                'label' => __('Withdrawal Button', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .rm-withdrawal-btn',
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
                    '{{WRAPPER}} .rm-withdrawal-btn' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .rm-withdrawal-btn' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .rm-withdrawal-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_text',
            [
                'label' => __('Text Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rm-withdrawal-btn:hover' => 'color: {{VALUE}};',
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
                'selectors' => [
                    '{{WRAPPER}} .rm-withdrawal-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label' => __('Padding', 'rm-panel-extensions'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .rm-withdrawal-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // History Table Style
        $this->start_controls_section(
            'history_table_style',
            [
                'label' => __('Payment History Table', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'table_header_bg',
            [
                'label' => __('Header Background', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#f9fafb',
                'selectors' => [
                    '{{WRAPPER}} .rm-payment-history-table thead' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'table_row_hover',
            [
                'label' => __('Row Hover Background', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#f9fafb',
                'selectors' => [
                    '{{WRAPPER}} .rm-payment-history-table tbody tr:hover' => 'background-color: {{VALUE}};',
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
            echo '<div class="rm-notice rm-notice-warning">' . 
                 __('Please log in to view your balance and withdrawal options.', 'rm-panel-extensions') . 
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
        <div class="rm-balance-withdrawal-container">
            
            <!-- Balance Overview Cards -->
            <div class="rm-balance-cards-grid">
                
                <!-- Available Balance Card -->
                <div class="rm-balance-card rm-balance-main">
                    <div class="rm-balance-icon">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="rm-balance-content">
                        <div class="rm-balance-label">
                            <?php echo esc_html($settings['available_balance_label']); ?>
                        </div>
                        <div class="rm-balance-amount">
                            $<?php echo number_format($balance_data['available_balance'], 2); ?>
                        </div>
                        <?php if ($settings['enable_withdrawal_form'] === 'yes' && $balance_data['available_balance'] > 0 && $has_payment_methods) : ?>
                            <button type="button" class="rm-withdrawal-btn" id="open-withdrawal-modal">
                                <span class="dashicons dashicons-download"></span>
                                <?php echo esc_html($settings['withdrawal_button_text']); ?>
                            </button>
                        <?php elseif (!$has_payment_methods) : ?>
                            <p class="rm-no-methods-notice">
                                <small><?php _e('No payment methods available. Please contact support.', 'rm-panel-extensions'); ?></small>
                            </p>
                        <?php elseif ($balance_data['available_balance'] <= 0) : ?>
                            <p class="rm-no-balance-message">
                                <small><?php echo esc_html($settings['no_balance_message']); ?></small>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Total Earned Card -->
                <?php if ($settings['show_total_earned'] === 'yes') : ?>
                    <div class="rm-balance-card rm-balance-stat">
                        <div class="rm-stat-icon">💰</div>
                        <div class="rm-stat-content">
                            <div class="rm-stat-label">
                                <?php echo esc_html($settings['total_earned_label']); ?>
                            </div>
                            <div class="rm-stat-value">
                                $<?php echo number_format($balance_data['total_earned'], 2); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Total Withdrawn Card -->
                <?php if ($settings['show_total_withdrawn'] === 'yes') : ?>
                    <div class="rm-balance-card rm-balance-stat">
                        <div class="rm-stat-icon">📤</div>
                        <div class="rm-stat-content">
                            <div class="rm-stat-label">
                                <?php echo esc_html($settings['total_withdrawn_label']); ?>
                            </div>
                            <div class="rm-stat-value">
                                $<?php echo number_format($balance_data['total_withdrawn'], 2); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Pending Withdrawals Card -->
                <?php if ($settings['show_pending_withdrawals'] === 'yes' && $balance_data['pending_amount'] > 0) : ?>
                    <div class="rm-balance-card rm-balance-stat rm-pending-stat">
                        <div class="rm-stat-icon">⏳</div>
                        <div class="rm-stat-content">
                            <div class="rm-stat-label">
                                <?php echo esc_html($settings['pending_label']); ?>
                            </div>
                            <div class="rm-stat-value">
                                $<?php echo number_format($balance_data['pending_amount'], 2); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Payment History Section -->
            <?php if ($settings['show_payment_history'] === 'yes') : ?>
                <?php $this->render_payment_history($user_id, $settings); ?>
            <?php endif; ?>

        </div>

        <!-- Withdrawal Modal -->
        <?php if ($settings['enable_withdrawal_form'] === 'yes' && $has_payment_methods) : ?>
            <?php $this->render_withdrawal_modal($balance_data['available_balance']); ?>
        <?php endif; ?>

        <?php
    }

    /**
     * Get user balance data
     */
    private function get_user_balance_data($user_id) {
        global $wpdb;
        
        $available_balance = get_user_meta($user_id, 'rm_withdrawable_balance', true) ?: 0;
        $total_earned = get_user_meta($user_id, 'rm_total_earnings', true) ?: 0;
        
        // FIXED: Calculate total withdrawn correctly from completed withdrawals only
        // Use net_amount (what user actually received) instead of amount
        $withdrawals_table = $wpdb->prefix . 'rm_withdrawal_requests';
        $total_withdrawn = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(net_amount) FROM $withdrawals_table WHERE user_id = %d AND status = 'completed'",
            $user_id
        )) ?: 0;
        
        // Get pending withdrawal amount (includes pending, approved, and processing)
        $pending_amount = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $withdrawals_table WHERE user_id = %d AND status IN ('pending', 'approved', 'processing')",
            $user_id
        )) ?: 0;

        // Update the cached total_paid meta for consistency
        update_user_meta($user_id, 'rm_total_paid', $total_withdrawn);

        return [
            'available_balance' => floatval($available_balance),
            'total_earned' => floatval($total_earned),
            'total_withdrawn' => floatval($total_withdrawn),
            'pending_amount' => floatval($pending_amount),
        ];
    }

    /**
     * Render payment history table
     */
    private function render_payment_history($user_id, $settings) {
        global $wpdb;

        if (!class_exists('RM_Withdrawal_Requests')) {
            return;
        }

        $per_page = intval($settings['history_items_per_page']);
        $paged = isset($_GET['payment_page']) ? absint($_GET['payment_page']) : 1;
        $offset = ($paged - 1) * $per_page;

        $table_name = $wpdb->prefix . 'rm_withdrawal_requests';
        
        // Get withdrawals
        $withdrawals = $wpdb->get_results($wpdb->prepare("
            SELECT w.*, pm.method_name
            FROM $table_name w
            LEFT JOIN {$wpdb->prefix}rm_payment_methods pm ON w.payment_method_id = pm.id
            WHERE w.user_id = %d
            ORDER BY w.created_at DESC
            LIMIT %d OFFSET %d
        ", $user_id, $per_page, $offset));

        // Get total count for pagination
        $total_items = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            $user_id
        ));

        $total_pages = ceil($total_items / $per_page);

        ?>
        <div class="rm-payment-history-section">
            <h3 class="rm-section-heading">
                <span class="dashicons dashicons-archive"></span>
                <?php _e('Payment History', 'rm-panel-extensions'); ?>
            </h3>

            <?php if (empty($withdrawals)) : ?>
                <div class="rm-no-history">
                    <p><?php _e('You haven\'t made any withdrawal requests yet.', 'rm-panel-extensions'); ?></p>
                </div>
            <?php else : ?>
                <div class="rm-table-responsive">
                    <table class="rm-payment-history-table">
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Method', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Amount', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Fee', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Net Amount', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Status', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Reference', 'rm-panel-extensions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withdrawals as $withdrawal) : ?>
                                <tr>
                                    <td>
                                        <?php echo date_i18n('M j, Y', strtotime($withdrawal->created_at)); ?>
                                        <br>
                                        <small><?php echo date_i18n('H:i', strtotime($withdrawal->created_at)); ?></small>
                                    </td>
                                    <td><?php echo esc_html($withdrawal->method_name); ?></td>
                                    <td>$<?php echo number_format($withdrawal->amount, 2); ?></td>
                                    <td>
                                        <?php if ($withdrawal->processing_fee > 0) : ?>
                                            <span style="color: #dc3545;">-$<?php echo number_format($withdrawal->processing_fee, 2); ?></span>
                                        <?php else : ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td><strong>$<?php echo number_format($withdrawal->net_amount, 2); ?></strong></td>
                                    <td>
                                        <span class="rm-status-badge rm-status-<?php echo esc_attr($withdrawal->status); ?>">
                                            <?php echo $this->get_status_label($withdrawal->status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($withdrawal->status === 'completed' && $withdrawal->transaction_reference) : ?>
                                            <small><?php echo esc_html($withdrawal->transaction_reference); ?></small>
                                        <?php else : ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1) : ?>
                    <div class="rm-pagination">
                        <?php
                        $base_url = remove_query_arg('payment_page');
                        for ($i = 1; $i <= $total_pages; $i++) :
                            $class = ($i === $paged) ? 'active' : '';
                            $url = add_query_arg('payment_page', $i, $base_url);
                        ?>
                            <a href="<?php echo esc_url($url); ?>" class="rm-page-link <?php echo $class; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render withdrawal modal
     */
    private function render_withdrawal_modal($available_balance) {
        ?>
        <div id="rm-withdrawal-modal" class="rm-modal" style="display: none;">
            <div class="rm-modal-content">
                <span class="rm-modal-close">&times;</span>
                <h2><?php _e('Request Withdrawal', 'rm-panel-extensions'); ?></h2>
                
                <div class="rm-withdrawal-form-wrapper">
                    <?php echo do_shortcode('[rm_withdrawal_form]'); ?>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#open-withdrawal-modal').on('click', function() {
                $('#rm-withdrawal-modal').fadeIn();
            });

            $('.rm-modal-close, .rm-modal').on('click', function(e) {
                if (e.target === this) {
                    $('#rm-withdrawal-modal').fadeOut();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Get status label
     */
    private function get_status_label($status) {
        $labels = [
            'pending' => __('⏳ Pending', 'rm-panel-extensions'),
            'approved' => __('✅ Approved', 'rm-panel-extensions'),
            'processing' => __('🔄 Processing', 'rm-panel-extensions'),
            'completed' => __('✅ Completed', 'rm-panel-extensions'),
            'rejected' => __('❌ Rejected', 'rm-panel-extensions'),
            'cancelled' => __('⚫ Cancelled', 'rm-panel-extensions')
        ];
        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Render widget output in the editor
     */
    protected function content_template() {
        ?>
        <#
        var showTotalEarned = settings.show_total_earned === 'yes';
        var showTotalWithdrawn = settings.show_total_withdrawn === 'yes';
        var showPending = settings.show_pending_withdrawals === 'yes';
        var enableWithdrawal = settings.enable_withdrawal_form === 'yes';
        var showHistory = settings.show_payment_history === 'yes';
        #>
        
        <div class="rm-balance-withdrawal-container">
            <div class="rm-balance-cards-grid">
                
                <!-- Available Balance Card -->
                <div class="rm-balance-card rm-balance-main">
                    <div class="rm-balance-icon">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="rm-balance-content">
                        <div class="rm-balance-label">
                            {{{ settings.available_balance_label }}}
                        </div>
                        <div class="rm-balance-amount">
                            $1,234.56
                        </div>
                        <# if (enableWithdrawal) { #>
                            <button type="button" class="rm-withdrawal-btn">
                                <span class="dashicons dashicons-download"></span>
                                {{{ settings.withdrawal_button_text }}}
                            </button>
                        <# } #>
                    </div>
                </div>

                <!-- Total Earned Card -->
                <# if (showTotalEarned) { #>
                    <div class="rm-balance-card rm-balance-stat">
                        <div class="rm-stat-icon">💰</div>
                        <div class="rm-stat-content">
                            <div class="rm-stat-label">
                                {{{ settings.total_earned_label }}}
                            </div>
                            <div class="rm-stat-value">$2,500.00</div>
                        </div>
                    </div>
                <# } #>

                <!-- Total Withdrawn Card -->
                <# if (showTotalWithdrawn) { #>
                    <div class="rm-balance-card rm-balance-stat">
                        <div class="rm-stat-icon">📤</div>
                        <div class="rm-stat-content">
                            <div class="rm-stat-label">
                                {{{ settings.total_withdrawn_label }}}
                            </div>
                            <div class="rm-stat-value">$1,200.00</div>
                        </div>
                    </div>
                <# } #>

                <!-- Pending Card -->
                <# if (showPending) { #>
                    <div class="rm-balance-card rm-balance-stat rm-pending-stat">
                        <div class="rm-stat-icon">⏳</div>
                        <div class="rm-stat-content">
                            <div class="rm-stat-label">
                                {{{ settings.pending_label }}}
                            </div>
                            <div class="rm-stat-value">$65.44</div>
                        </div>
                    </div>
                <# } #>

            </div>

            <!-- Payment History Preview -->
            <# if (showHistory) { #>
                <div class="rm-payment-history-section">
                    <h3 class="rm-section-heading">
                        <span class="dashicons dashicons-archive"></span>
                        Payment History
                    </h3>
                    <div class="rm-table-responsive">
                        <table class="rm-payment-history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th>Fee</th>
                                    <th>Net Amount</th>
                                    <th>Status</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        Oct 25, 2024<br>
                                        <small>14:30</small>
                                    </td>
                                    <td>PayPal</td>
                                    <td>$100.00</td>
                                    <td><span style="color: #dc3545;">-$2.00</span></td>
                                    <td><strong>$98.00</strong></td>
                                    <td>
                                        <span class="rm-status-badge rm-status-completed">
                                            ✅ Completed
                                        </span>
                                    </td>
                                    <td><small>TXN123456</small></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            <# } #>
        </div>
        <?php
    }
}