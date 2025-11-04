<?php
/**
 * Payment History Sidebar Widget
 * 
 * Displays user's payment/withdrawal history in WordPress sidebar
 * 
 * @package RM_Panel_Extensions
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Payment_History_Sidebar_Widget extends WP_Widget {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'rm_payment_history_widget',
            __('RM Payment History', 'rm-panel-extensions'),
            [
                'description' => __('Displays user payment/withdrawal history', 'rm-panel-extensions'),
                'classname' => 'rm-payment-history-widget'
            ]
        );
    }

    /**
     * Front-end display of widget
     */
    public function widget($args, $instance) {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        
        // Get settings
        $title = !empty($instance['title']) ? $instance['title'] : __('Payment History', 'rm-panel-extensions');
        $items_to_show = !empty($instance['items_to_show']) ? absint($instance['items_to_show']) : 5;
        $show_balance = isset($instance['show_balance']) ? (bool) $instance['show_balance'] : true;
        $show_stats = isset($instance['show_stats']) ? (bool) $instance['show_stats'] : true;

        echo $args['before_widget'];

        if (!empty($title)) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }

        // Get user balance data
        if ($show_balance || $show_stats) {
            $balance_data = $this->get_user_balance_data($user_id);
        }

        ?>
        <div class="rm-payment-history-widget-content">
            
            <?php if ($show_balance) : ?>
                <!-- Available Balance -->
                <div class="rm-widget-balance-card">
                    <div class="rm-widget-balance-icon">💰</div>
                    <div class="rm-widget-balance-info">
                        <div class="rm-widget-balance-label">
                            <?php _e('Available Balance', 'rm-panel-extensions'); ?>
                        </div>
                        <div class="rm-widget-balance-amount">
                            $<?php echo number_format($balance_data['available_balance'], 2); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($show_stats) : ?>
                <!-- Stats Grid -->
                <div class="rm-widget-stats-grid">
                    <div class="rm-widget-stat-item">
                        <div class="rm-widget-stat-label"><?php _e('Total Earned', 'rm-panel-extensions'); ?></div>
                        <div class="rm-widget-stat-value">$<?php echo number_format($balance_data['total_earned'], 2); ?></div>
                    </div>
                    <div class="rm-widget-stat-item">
                        <div class="rm-widget-stat-label"><?php _e('Withdrawn', 'rm-panel-extensions'); ?></div>
                        <div class="rm-widget-stat-value">$<?php echo number_format($balance_data['total_withdrawn'], 2); ?></div>
                    </div>
                    <?php if ($balance_data['pending_amount'] > 0) : ?>
                        <div class="rm-widget-stat-item rm-pending">
                            <div class="rm-widget-stat-label"><?php _e('Pending', 'rm-panel-extensions'); ?></div>
                            <div class="rm-widget-stat-value">$<?php echo number_format($balance_data['pending_amount'], 2); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Recent Transactions -->
            <div class="rm-widget-transactions">
                <h4 class="rm-widget-section-title"><?php _e('Recent Transactions', 'rm-panel-extensions'); ?></h4>
                <?php $this->render_transactions($user_id, $items_to_show); ?>
            </div>

            <?php if (!empty($instance['show_view_all']) && $instance['show_view_all']) : ?>
                <?php if (!empty($instance['view_all_url'])) : ?>
                    <div class="rm-widget-view-all">
                        <a href="<?php echo esc_url($instance['view_all_url']); ?>" class="rm-widget-view-all-link">
                            <?php _e('View All Transactions', 'rm-panel-extensions'); ?> →
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        </div>
        <?php

        echo $args['after_widget'];
    }

    /**
     * Get user balance data with corrected calculations
     */
    private function get_user_balance_data($user_id) {
        global $wpdb;
        
        $available_balance = get_user_meta($user_id, 'rm_withdrawable_balance', true) ?: 0;
        $total_earned = get_user_meta($user_id, 'rm_total_earnings', true) ?: 0;
        
        // FIXED: Calculate total withdrawn correctly from completed withdrawals only
        $withdrawals_table = $wpdb->prefix . 'rm_withdrawal_requests';
        $total_withdrawn = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(net_amount) FROM $withdrawals_table WHERE user_id = %d AND status = 'completed'",
            $user_id
        )) ?: 0;
        
        // Get pending withdrawal amount
        $pending_amount = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $withdrawals_table WHERE user_id = %d AND status IN ('pending', 'approved', 'processing')",
            $user_id
        )) ?: 0;

        // Update the cached total_paid meta (for consistency)
        update_user_meta($user_id, 'rm_total_paid', $total_withdrawn);

        return [
            'available_balance' => floatval($available_balance),
            'total_earned' => floatval($total_earned),
            'total_withdrawn' => floatval($total_withdrawn),
            'pending_amount' => floatval($pending_amount),
        ];
    }

    /**
     * Render recent transactions
     */
    private function render_transactions($user_id, $limit = 5) {
        global $wpdb;

        if (!class_exists('RM_Withdrawal_Requests')) {
            echo '<p class="rm-widget-no-data">' . __('No transaction history available.', 'rm-panel-extensions') . '</p>';
            return;
        }

        $table_name = $wpdb->prefix . 'rm_withdrawal_requests';
        
        $transactions = $wpdb->get_results($wpdb->prepare("
            SELECT w.*, pm.method_name
            FROM $table_name w
            LEFT JOIN {$wpdb->prefix}rm_payment_methods pm ON w.payment_method_id = pm.id
            WHERE w.user_id = %d
            ORDER BY w.created_at DESC
            LIMIT %d
        ", $user_id, $limit));

        if (empty($transactions)) {
            echo '<p class="rm-widget-no-data">' . __('No transactions yet.', 'rm-panel-extensions') . '</p>';
            return;
        }

        ?>
        <div class="rm-widget-transaction-list">
            <?php foreach ($transactions as $transaction) : ?>
                <div class="rm-widget-transaction-item">
                    <div class="rm-widget-transaction-header">
                        <div class="rm-widget-transaction-date">
                            <span class="rm-widget-date-icon">📅</span>
                            <?php echo date_i18n('M j, Y', strtotime($transaction->created_at)); ?>
                        </div>
                        <span class="rm-widget-status rm-widget-status-<?php echo esc_attr($transaction->status); ?>">
                            <?php echo $this->get_status_label($transaction->status); ?>
                        </span>
                    </div>
                    <div class="rm-widget-transaction-details">
                        <div class="rm-widget-transaction-method">
                            <span class="rm-widget-method-icon">💳</span>
                            <?php echo esc_html($transaction->method_name); ?>
                        </div>
                        <div class="rm-widget-transaction-amount">
                            <strong>$<?php echo number_format($transaction->net_amount, 2); ?></strong>
                            <?php if ($transaction->processing_fee > 0) : ?>
                                <small class="rm-widget-fee">
                                    ($<?php echo number_format($transaction->amount, 2); ?> - $<?php echo number_format($transaction->processing_fee, 2); ?> fee)
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($transaction->status === 'completed' && $transaction->transaction_reference) : ?>
                        <div class="rm-widget-transaction-ref">
                            <small>
                                <?php _e('Ref:', 'rm-panel-extensions'); ?> 
                                <?php echo esc_html($transaction->transaction_reference); ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Get status label with emoji
     */
    private function get_status_label($status) {
        $labels = [
            'pending' => '⏳ ' . __('Pending', 'rm-panel-extensions'),
            'approved' => '✅ ' . __('Approved', 'rm-panel-extensions'),
            'processing' => '🔄 ' . __('Processing', 'rm-panel-extensions'),
            'completed' => '✅ ' . __('Paid', 'rm-panel-extensions'),
            'rejected' => '❌ ' . __('Rejected', 'rm-panel-extensions'),
            'cancelled' => '⚫ ' . __('Cancelled', 'rm-panel-extensions')
        ];
        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Back-end widget form
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Payment History', 'rm-panel-extensions');
        $items_to_show = !empty($instance['items_to_show']) ? absint($instance['items_to_show']) : 5;
        $show_balance = isset($instance['show_balance']) ? (bool) $instance['show_balance'] : true;
        $show_stats = isset($instance['show_stats']) ? (bool) $instance['show_stats'] : true;
        $show_view_all = isset($instance['show_view_all']) ? (bool) $instance['show_view_all'] : false;
        $view_all_url = !empty($instance['view_all_url']) ? $instance['view_all_url'] : '';
        ?>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php _e('Title:', 'rm-panel-extensions'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('items_to_show')); ?>">
                <?php _e('Number of Transactions:', 'rm-panel-extensions'); ?>
            </label>
            <input class="tiny-text" 
                   id="<?php echo esc_attr($this->get_field_id('items_to_show')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('items_to_show')); ?>" 
                   type="number" 
                   step="1" 
                   min="1" 
                   max="20"
                   value="<?php echo esc_attr($items_to_show); ?>" 
                   size="3">
        </p>

        <p>
            <input class="checkbox" 
                   type="checkbox" 
                   <?php checked($show_balance); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_balance')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_balance')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_balance')); ?>">
                <?php _e('Show Available Balance', 'rm-panel-extensions'); ?>
            </label>
        </p>

        <p>
            <input class="checkbox" 
                   type="checkbox" 
                   <?php checked($show_stats); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_stats')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_stats')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_stats')); ?>">
                <?php _e('Show Statistics', 'rm-panel-extensions'); ?>
            </label>
        </p>

        <p>
            <input class="checkbox" 
                   type="checkbox" 
                   <?php checked($show_view_all); ?> 
                   id="<?php echo esc_attr($this->get_field_id('show_view_all')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('show_view_all')); ?>">
            <label for="<?php echo esc_attr($this->get_field_id('show_view_all')); ?>">
                <?php _e('Show "View All" Link', 'rm-panel-extensions'); ?>
            </label>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('view_all_url')); ?>">
                <?php _e('View All Transactions URL:', 'rm-panel-extensions'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('view_all_url')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('view_all_url')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($view_all_url); ?>"
                   placeholder="https://yoursite.com/payment-history">
        </p>

        <p class="description">
            <?php _e('This widget only displays for logged-in users.', 'rm-panel-extensions'); ?>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        
        $instance['title'] = (!empty($new_instance['title'])) 
            ? sanitize_text_field($new_instance['title']) 
            : '';
            
        $instance['items_to_show'] = (!empty($new_instance['items_to_show'])) 
            ? absint($new_instance['items_to_show']) 
            : 5;
            
        $instance['show_balance'] = !empty($new_instance['show_balance']) ? 1 : 0;
        $instance['show_stats'] = !empty($new_instance['show_stats']) ? 1 : 0;
        $instance['show_view_all'] = !empty($new_instance['show_view_all']) ? 1 : 0;
        
        $instance['view_all_url'] = (!empty($new_instance['view_all_url'])) 
            ? esc_url_raw($new_instance['view_all_url']) 
            : '';

        return $instance;
    }
}

/**
 * Register the widget
 */
function rm_register_payment_history_widget() {
    register_widget('RM_Payment_History_Sidebar_Widget');
}
add_action('widgets_init', 'rm_register_payment_history_widget');