<?php
/**
 * Payment Methods Management
 * Admin can configure multiple withdrawal methods
 * 
 * @package RM_Panel_Extensions
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Payment_Methods {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->create_tables();
    }
    
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // AJAX handlers
        add_action('wp_ajax_rm_add_payment_method', [$this, 'ajax_add_payment_method']);
        add_action('wp_ajax_rm_update_payment_method', [$this, 'ajax_update_payment_method']);
        add_action('wp_ajax_rm_delete_payment_method', [$this, 'ajax_delete_payment_method']);
        add_action('wp_ajax_rm_toggle_payment_method', [$this, 'ajax_toggle_payment_method']);
    }
    
    private function create_tables() {
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
        
        // Add default payment methods if table is empty
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count == 0) {
            $this->add_default_methods();
        }
    }
    
    private function add_default_methods() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_payment_methods';
        
        $default_methods = [
            [
                'method_name' => 'PayPal',
                'method_type' => 'paypal',
                'icon' => 'dashicons-money-alt',
                'description' => 'Receive payments via PayPal',
                'required_fields' => json_encode([
                    ['name' => 'paypal_email', 'label' => 'PayPal Email Address', 'type' => 'email', 'required' => true]
                ]),
                'min_withdrawal' => 10.00,
                'max_withdrawal' => 10000.00,
                'processing_fee_type' => 'percentage',
                'processing_fee_value' => 2.00,
                'processing_days' => 3,
                'instructions' => 'Enter your PayPal email address. Payments are processed within 3 business days.',
                'is_active' => 1,
                'sort_order' => 1
            ],
            [
                'method_name' => 'Bank Transfer',
                'method_type' => 'bank_transfer',
                'icon' => 'dashicons-bank',
                'description' => 'Direct bank transfer',
                'required_fields' => json_encode([
                    ['name' => 'account_holder', 'label' => 'Account Holder Name', 'type' => 'text', 'required' => true],
                    ['name' => 'bank_name', 'label' => 'Bank Name', 'type' => 'text', 'required' => true],
                    ['name' => 'account_number', 'label' => 'Account Number', 'type' => 'text', 'required' => true],
                    ['name' => 'routing_number', 'label' => 'Routing Number', 'type' => 'text', 'required' => true],
                    ['name' => 'swift_code', 'label' => 'SWIFT/BIC Code', 'type' => 'text', 'required' => false]
                ]),
                'min_withdrawal' => 50.00,
                'max_withdrawal' => 50000.00,
                'processing_fee_type' => 'fixed',
                'processing_fee_value' => 5.00,
                'processing_days' => 5,
                'instructions' => 'Provide your bank account details. Transfers take 5-7 business days.',
                'is_active' => 1,
                'sort_order' => 2
            ],
            [
                'method_name' => 'Wise (TransferWise)',
                'method_type' => 'wise',
                'icon' => 'dashicons-money',
                'description' => 'Fast international transfers via Wise',
                'required_fields' => json_encode([
                    ['name' => 'wise_email', 'label' => 'Wise Email Address', 'type' => 'email', 'required' => true]
                ]),
                'min_withdrawal' => 20.00,
                'max_withdrawal' => 20000.00,
                'processing_fee_type' => 'percentage',
                'processing_fee_value' => 1.50,
                'processing_days' => 2,
                'instructions' => 'Enter your Wise account email. Transfers typically complete within 1-2 business days.',
                'is_active' => 1,
                'sort_order' => 3
            ],
            [
                'method_name' => 'Cryptocurrency (Bitcoin)',
                'method_type' => 'crypto_btc',
                'icon' => 'dashicons-shield',
                'description' => 'Receive payment in Bitcoin',
                'required_fields' => json_encode([
                    ['name' => 'btc_address', 'label' => 'Bitcoin Wallet Address', 'type' => 'text', 'required' => true]
                ]),
                'min_withdrawal' => 100.00,
                'max_withdrawal' => NULL,
                'processing_fee_type' => 'fixed',
                'processing_fee_value' => 2.00,
                'processing_days' => 1,
                'instructions' => 'Provide your Bitcoin wallet address. Transfers are processed within 24 hours.',
                'is_active' => 0,
                'sort_order' => 4
            ],
            [
                'method_name' => 'Payoneer',
                'method_type' => 'payoneer',
                'icon' => 'dashicons-bank',
                'description' => 'Receive payments via Payoneer',
                'required_fields' => json_encode([
                    ['name' => 'payoneer_email', 'label' => 'Payoneer Email Address', 'type' => 'email', 'required' => true]
                ]),
                'min_withdrawal' => 50.00,
                'max_withdrawal' => 10000.00,
                'processing_fee_type' => 'percentage',
                'processing_fee_value' => 3.00,
                'processing_days' => 3,
                'instructions' => 'Enter your Payoneer account email. Payments processed within 3 business days.',
                'is_active' => 0,
                'sort_order' => 5
            ]
        ];
        
        foreach ($default_methods as $method) {
            $wpdb->insert($table_name, $method);
        }
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=rm_survey',
            __('Payment Methods', 'rm-panel-extensions'),
            __('💳 Payment Methods', 'rm-panel-extensions'),
            'manage_options',
            'rm-payment-methods',
            [$this, 'render_admin_page']
        );
    }
    
    public function render_admin_page() {
        $methods = $this->get_all_methods();
        ?>
        <div class="wrap">
            <h1>
                <?php _e('Payment Methods Configuration', 'rm-panel-extensions'); ?>
                <button type="button" class="page-title-action" id="add-payment-method-btn">
                    <?php _e('Add New Method', 'rm-panel-extensions'); ?>
                </button>
            </h1>
            
            <p class="description">
                <?php _e('Configure payment methods that users can choose for withdrawing their earnings.', 'rm-panel-extensions'); ?>
            </p>
            
            <div id="payment-methods-container">
                <?php if (empty($methods)) : ?>
                    <div class="notice notice-info inline">
                        <p><?php _e('No payment methods configured yet. Click "Add New Method" to get started.', 'rm-panel-extensions'); ?></p>
                    </div>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;"><?php _e('Active', 'rm-panel-extensions'); ?></th>
                                <th style="width: 60px;"><?php _e('Icon', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Method Name', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Type', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Min/Max', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Fees', 'rm-panel-extensions'); ?></th>
                                <th><?php _e('Processing', 'rm-panel-extensions'); ?></th>
                                <th style="width: 150px;"><?php _e('Actions', 'rm-panel-extensions'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="payment-methods-list">
                            <?php foreach ($methods as $method) : ?>
                                <tr data-method-id="<?php echo $method->id; ?>" class="<?php echo $method->is_active ? '' : 'inactive-method'; ?>">
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" 
                                                   class="toggle-method" 
                                                   data-method-id="<?php echo $method->id; ?>"
                                                   <?php checked($method->is_active, 1); ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <span class="dashicons <?php echo esc_attr($method->icon); ?>" style="font-size: 28px; width: 28px; height: 28px;"></span>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($method->method_name); ?></strong>
                                        <br>
                                        <small class="description"><?php echo esc_html($method->description); ?></small>
                                    </td>
                                    <td>
                                        <code><?php echo esc_html($method->method_type); ?></code>
                                    </td>
                                    <td>
                                        <strong>Min:</strong> $<?php echo number_format($method->min_withdrawal, 2); ?>
                                        <br>
                                        <strong>Max:</strong> <?php echo $method->max_withdrawal ? '$' . number_format($method->max_withdrawal, 2) : __('Unlimited', 'rm-panel-extensions'); ?>
                                    </td>
                                    <td>
                                        <?php if ($method->processing_fee_type === 'percentage') : ?>
                                            <?php echo $method->processing_fee_value; ?>%
                                        <?php elseif ($method->processing_fee_type === 'fixed') : ?>
                                            $<?php echo number_format($method->processing_fee_value, 2); ?>
                                        <?php else : ?>
                                            <?php _e('None', 'rm-panel-extensions'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $method->processing_days; ?> <?php _e('days', 'rm-panel-extensions'); ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small edit-method-btn" data-method-id="<?php echo $method->id; ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                            <?php _e('Edit', 'rm-panel-extensions'); ?>
                                        </button>
                                        <button type="button" class="button button-small button-link-delete delete-method-btn" data-method-id="<?php echo $method->id; ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Add/Edit Payment Method Modal -->
        <div id="payment-method-modal" class="rm-modal" style="display:none;">
            <div class="rm-modal-content" style="max-width: 700px;">
                <span class="close-modal">&times;</span>
                <h2 id="modal-title"><?php _e('Add Payment Method', 'rm-panel-extensions'); ?></h2>
                
                <form id="payment-method-form">
                    <input type="hidden" id="method-id" value="">
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="method-name"><?php _e('Method Name', 'rm-panel-extensions'); ?> *</label></th>
                            <td>
                                <input type="text" id="method-name" class="regular-text" required>
                                <p class="description"><?php _e('e.g., PayPal, Bank Transfer, Wise', 'rm-panel-extensions'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="method-type"><?php _e('Method Type', 'rm-panel-extensions'); ?> *</label></th>
                            <td>
                                <input type="text" id="method-type" class="regular-text" required>
                                <p class="description"><?php _e('Unique identifier (lowercase, no spaces). e.g., paypal, bank_transfer', 'rm-panel-extensions'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="method-icon"><?php _e('Icon', 'rm-panel-extensions'); ?></label></th>
                            <td>
                                <input type="text" id="method-icon" class="regular-text" value="dashicons-money-alt">
                                <p class="description">
                                    <?php _e('Dashicons class name. Examples:', 'rm-panel-extensions'); ?>
                                    <br>
                                    <span class="dashicons dashicons-money-alt"></span> dashicons-money-alt
                                    <span class="dashicons dashicons-bank"></span> dashicons-bank
                                    <span class="dashicons dashicons-shield"></span> dashicons-shield
                                    <br>
                                    <a href="https://developer.wordpress.org/resource/dashicons/" target="_blank"><?php _e('View all Dashicons', 'rm-panel-extensions'); ?></a>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="method-description"><?php _e('Description', 'rm-panel-extensions'); ?></label></th>
                            <td>
                                <textarea id="method-description" class="large-text" rows="2"></textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label><?php _e('Required Fields', 'rm-panel-extensions'); ?></label></th>
                            <td>
                                <div id="required-fields-container">
                                    <button type="button" class="button" id="add-required-field">
                                        <?php _e('Add Field', 'rm-panel-extensions'); ?>
                                    </button>
                                </div>
                                <p class="description"><?php _e('Define what information users need to provide (e.g., email, account number)', 'rm-panel-extensions'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="min-withdrawal"><?php _e('Minimum Withdrawal', 'rm-panel-extensions'); ?></label></th>
                            <td>
                                $<input type="number" id="min-withdrawal" step="0.01" min="0" value="10.00" style="width: 150px;">
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="max-withdrawal"><?php _e('Maximum Withdrawal', 'rm-panel-extensions'); ?></label></th>
                            <td>
                                $<input type="number" id="max-withdrawal" step="0.01" min="0" style="width: 150px;">
                                <p class="description"><?php _e('Leave empty for unlimited', 'rm-panel-extensions'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="processing-fee-type"><?php _e('Processing Fee', 'rm-panel-extensions'); ?></label></th>
                            <td>
                                <select id="processing-fee-type" style="width: 150px;">
                                    <option value="none"><?php _e('No Fee', 'rm-panel-extensions'); ?></option>
                                    <option value="fixed"><?php _e('Fixed Amount', 'rm-panel-extensions'); ?></option>
                                    <option value="percentage"><?php _e('Percentage', 'rm-panel-extensions'); ?></option>
                                </select>
                                <input type="number" id="processing-fee-value" step="0.01" min="0" value="0" style="width: 150px; margin-left: 10px;" placeholder="0.00">
                                <span id="fee-suffix"></span>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="processing-days"><?php _e('Processing Time', 'rm-panel-extensions'); ?></label></th>
                            <td>
                                <input type="number" id="processing-days" min="0" value="3" style="width: 100px;"> <?php _e('business days', 'rm-panel-extensions'); ?>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="method-instructions"><?php _e('Instructions for Users', 'rm-panel-extensions'); ?></label></th>
                            <td>
                                <textarea id="method-instructions" class="large-text" rows="3"></textarea>
                                <p class="description"><?php _e('Shown to users when they select this method', 'rm-panel-extensions'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            <?php _e('Save Payment Method', 'rm-panel-extensions'); ?>
                        </button>
                        <button type="button" class="button button-secondary cancel-modal">
                            <?php _e('Cancel', 'rm-panel-extensions'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <style>
            .inactive-method {
                opacity: 0.5;
            }
            .switch {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 24px;
            }
            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 24px;
            }
            .slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }
            input:checked + .slider {
                background-color: #2271b1;
            }
            input:checked + .slider:before {
                transform: translateX(26px);
            }
            .rm-modal {
                position: fixed;
                z-index: 100000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.7);
                overflow-y: auto;
            }
            .rm-modal-content {
                background-color: #fefefe;
                margin: 50px auto;
                padding: 30px;
                border: 1px solid #888;
                width: 90%;
                max-width: 800px;
                border-radius: 8px;
            }
            .close-modal {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
                line-height: 20px;
            }
            .close-modal:hover {
                color: #000;
            }
            .required-field-row {
                display: flex;
                gap: 10px;
                margin-bottom: 10px;
                align-items: center;
            }
            .required-field-row input[type="text"] {
                flex: 1;
            }
            .required-field-row select {
                width: 120px;
            }
            .required-field-row .button-link-delete {
                color: #b32d2e;
                padding: 0;
                border: none;
                background: none;
                cursor: pointer;
            }
        </style>
        <?php
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'rm_survey_page_rm-payment-methods') {
            return;
        }
        
        wp_enqueue_script(
            'rm-payment-methods',
            RM_PANEL_EXT_PLUGIN_URL . 'assets/js/payment-methods.js',
            ['jquery'],
            RM_PANEL_EXT_VERSION,
            true
        );
        
        wp_localize_script('rm-payment-methods', 'rmPaymentMethods', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rm_payment_methods_nonce'),
            'methods' => $this->get_all_methods()
        ]);
    }
    
    public function get_all_methods() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_payment_methods';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY sort_order ASC, method_name ASC");
    }
    
    public function get_active_methods() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_payment_methods';
        return $wpdb->get_results("SELECT * FROM $table_name WHERE is_active = 1 ORDER BY sort_order ASC, method_name ASC");
    }
    
    public function get_method_by_id($method_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_payment_methods';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $method_id));
    }
    
    public function ajax_add_payment_method() {
        check_ajax_referer('rm_payment_methods_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $data = $_POST['method_data'];
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_payment_methods';
        
        $result = $wpdb->insert($table_name, [
            'method_name' => sanitize_text_field($data['method_name']),
            'method_type' => sanitize_key($data['method_type']),
            'icon' => sanitize_text_field($data['icon']),
            'description' => sanitize_textarea_field($data['description']),
            'required_fields' => wp_json_encode($data['required_fields']),
            'min_withdrawal' => floatval($data['min_withdrawal']),
            'max_withdrawal' => !empty($data['max_withdrawal']) ? floatval($data['max_withdrawal']) : NULL,
            'processing_fee_type' => sanitize_text_field($data['processing_fee_type']),
            'processing_fee_value' => floatval($data['processing_fee_value']),
            'processing_days' => intval($data['processing_days']),
            'instructions' => sanitize_textarea_field($data['instructions']),
            'is_active' => 1,
            'sort_order' => 999
        ]);
        
        if ($result) {
            wp_send_json_success(['message' => 'Payment method added successfully']);
        } else {
            wp_send_json_error(['message' => 'Database error']);
        }
    }
    
    public function ajax_update_payment_method() {
        check_ajax_referer('rm_payment_methods_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $method_id = intval($_POST['method_id']);
        $data = $_POST['method_data'];
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_payment_methods';
        
        $result = $wpdb->update(
            $table_name,
            [
                'method_name' => sanitize_text_field($data['method_name']),
                'method_type' => sanitize_key($data['method_type']),
                'icon' => sanitize_text_field($data['icon']),
                'description' => sanitize_textarea_field($data['description']),
                'required_fields' => wp_json_encode($data['required_fields']),
                'min_withdrawal' => floatval($data['min_withdrawal']),
                'max_withdrawal' => !empty($data['max_withdrawal']) ? floatval($data['max_withdrawal']) : NULL,
                'processing_fee_type' => sanitize_text_field($data['processing_fee_type']),
                'processing_fee_value' => floatval($data['processing_fee_value']),
                'processing_days' => intval($data['processing_days']),
                'instructions' => sanitize_textarea_field($data['instructions'])
            ],
            ['id' => $method_id]
        );
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Payment method updated successfully']);
        } else {
            wp_send_json_error(['message' => 'No changes made or database error']);
        }
    }
    
    public function ajax_toggle_payment_method() {
        check_ajax_referer('rm_payment_methods_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $method_id = intval($_POST['method_id']);
        $is_active = intval($_POST['is_active']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_payment_methods';
        
        $result = $wpdb->update(
            $table_name,
            ['is_active' => $is_active],
            ['id' => $method_id]
        );
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Status updated']);
        } else {
            wp_send_json_error(['message' => 'Database error']);
        }
    }
    
    public function ajax_delete_payment_method() {
        check_ajax_referer('rm_payment_methods_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $method_id = intval($_POST['method_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_payment_methods';
        
        // Check if any pending withdrawals use this method
        $withdrawals_table = $wpdb->prefix . 'rm_withdrawal_requests';
        $pending_withdrawals = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $withdrawals_table WHERE payment_method_id = %d AND status = 'pending'",
            $method_id
        ));
        
        if ($pending_withdrawals > 0) {
            wp_send_json_error([
                'message' => sprintf(
                    __('Cannot delete. %d pending withdrawal(s) use this method.', 'rm-panel-extensions'),
                    $pending_withdrawals
                )
            ]);
        }
        
        $result = $wpdb->delete($table_name, ['id' => $method_id]);
        
        if ($result) {
            wp_send_json_success(['message' => 'Payment method deleted']);
        } else {
            wp_send_json_error(['message' => 'Database error']);
        }
    }
}

// Initialize
RM_Payment_Methods::get_instance();