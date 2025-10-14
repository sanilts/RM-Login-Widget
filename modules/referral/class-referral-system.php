<?php
/**
 * Referral System
 * File: modules/referral/class-referral-system.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Referral_System {
    
    public function __construct() {
        add_action('init', [$this, 'handle_referral_registration']);
        add_action('user_register', [$this, 'process_referral'], 10, 1);
        add_action('wp_ajax_rm_get_referral_stats', [$this, 'ajax_get_referral_stats']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // Create referrals table
        register_activation_hook(RM_PANEL_EXT_FILE, [$this, 'create_referrals_table']);
    }
    
    public function create_referrals_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rm_referrals';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            referrer_id bigint(20) NOT NULL,
            referred_id bigint(20) NOT NULL,
            survey_id bigint(20) DEFAULT NULL,
            referral_code varchar(50) DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            reward_amount decimal(10,2) DEFAULT 0.00,
            reward_paid tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY referrer_id (referrer_id),
            KEY referred_id (referred_id),
            KEY survey_id (survey_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function handle_referral_registration() {
        if (isset($_GET['ref'])) {
            $referrer_id = intval($_GET['ref']);
            $survey_id = isset($_GET['survey']) ? intval($_GET['survey']) : null;
            
            // Store in cookie for 30 days
            setcookie('rm_referrer', $referrer_id, time() + (30 * 24 * 60 * 60), '/');
            if ($survey_id) {
                setcookie('rm_referral_survey', $survey_id, time() + (30 * 24 * 60 * 60), '/');
            }
        }
    }
    
    public function process_referral($user_id) {
        if (isset($_COOKIE['rm_referrer'])) {
            $referrer_id = intval($_COOKIE['rm_referrer']);
            $survey_id = isset($_COOKIE['rm_referral_survey']) ? intval($_COOKIE['rm_referral_survey']) : null;
            
            if ($referrer_id && $referrer_id !== $user_id) {
                $this->create_referral($referrer_id, $user_id, $survey_id);
                
                // Clear cookies
                setcookie('rm_referrer', '', time() - 3600, '/');
                setcookie('rm_referral_survey', '', time() - 3600, '/');
            }
        }
    }
    
    private function create_referral($referrer_id, $referred_id, $survey_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rm_referrals';
        $reward_amount = get_option('rm_referral_reward_amount', 5.00);
        
        $data = [
            'referrer_id' => $referrer_id,
            'referred_id' => $referred_id,
            'survey_id' => $survey_id,
            'status' => 'completed',
            'reward_amount' => $reward_amount,
            'reward_paid' => 0
        ];
        
        $wpdb->insert($table_name, $data);
        
        // Send notification email
        $this->send_referral_notification($referrer_id, $referred_id);
        
        // Award points/credit if configured
        $auto_approve = get_option('rm_referral_auto_approve', 'no');
        if ($auto_approve === 'yes') {
            $this->approve_referral($wpdb->insert_id);
        }
    }
    
    public function approve_referral($referral_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rm_referrals';
        
        $referral = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $referral_id
        ));
        
        if (!$referral || $referral->reward_paid) {
            return false;
        }
        
        // Update referral
        $wpdb->update(
            $table_name,
            ['reward_paid' => 1],
            ['id' => $referral_id]
        );
        
        // Add to user's earnings
        $current_earnings = get_user_meta($referral->referrer_id, 'rm_referral_earnings', true) ?: 0;
        $new_earnings = $current_earnings + $referral->reward_amount;
        update_user_meta($referral->referrer_id, 'rm_referral_earnings', $new_earnings);
        
        return true;
    }
    
    private function send_referral_notification($referrer_id, $referred_id) {
        $referrer = get_userdata($referrer_id);
        $referred = get_userdata($referred_id);
        
        if (!$referrer || !$referred) return;
        
        $subject = __('New Referral Registered!', 'rm-panel-extensions');
        $message = sprintf(
            __('Great news! %s has registered using your referral link.

You will receive your referral reward once their account is verified.

Keep sharing your link to earn more!', 'rm-panel-extensions'),
            $referred->display_name
        );
        
        wp_mail($referrer->user_email, $subject, $message);
    }
    
    public function ajax_get_referral_stats() {
        check_ajax_referer('rm_referral_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Not logged in']);
        }
        
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_referrals';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE referrer_id = %d",
            $user_id
        ));
        
        $earnings = get_user_meta($user_id, 'rm_referral_earnings', true) ?: 0;
        
        wp_send_json_success([
            'count' => intval($count),
            'earnings' => number_format($earnings, 2)
        ]);
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'rm-panel-extensions',
            __('Referral Settings', 'rm-panel-extensions'),
            __('Referral Settings', 'rm-panel-extensions'),
            'manage_options',
            'rm-referral-settings',
            [$this, 'render_settings_page']
        );
        
        add_submenu_page(
            'rm-panel-extensions',
            __('Referrals', 'rm-panel-extensions'),
            __('Referrals', 'rm-panel-extensions'),
            'manage_options',
            'rm-referrals',
            [$this, 'render_referrals_page']
        );
    }
    
    public function register_settings() {
        register_setting('rm_referral_settings', 'rm_referral_reward_amount');
        register_setting('rm_referral_settings', 'rm_referral_auto_approve');
        register_setting('rm_referral_settings', 'rm_referral_registration_url');
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Referral System Settings', 'rm-panel-extensions'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('rm_referral_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="rm_referral_registration_url">
                                <?php _e('Registration Page URL', 'rm-panel-extensions'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="rm_referral_registration_url" 
                                   name="rm_referral_registration_url" 
                                   value="<?php echo esc_url(get_option('rm_referral_registration_url', wp_registration_url())); ?>" 
                                   class="regular-text">
                            <p class="description">
                                <?php _e('The page where users will register. Referral parameter will be automatically added.', 'rm-panel-extensions'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="rm_referral_reward_amount">
                                <?php _e('Referral Reward Amount', 'rm-panel-extensions'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number" 
                                   step="0.01" 
                                   id="rm_referral_reward_amount" 
                                   name="rm_referral_reward_amount" 
                                   value="<?php echo esc_attr(get_option('rm_referral_reward_amount', '5.00')); ?>">
                            <p class="description">
                                <?php _e('Amount to reward for each successful referral', 'rm-panel-extensions'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="rm_referral_auto_approve">
                                <?php _e('Auto-Approve Referrals', 'rm-panel-extensions'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   id="rm_referral_auto_approve" 
                                   name="rm_referral_auto_approve" 
                                   value="yes" 
                                   <?php checked(get_option('rm_referral_auto_approve'), 'yes'); ?>>
                            <label for="rm_referral_auto_approve">
                                <?php _e('Automatically approve and pay referral rewards', 'rm-panel-extensions'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function render_referrals_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_referrals';
        
        $referrals = $wpdb->get_results(
            "SELECT r.*, 
                    referrer.display_name as referrer_name,
                    referred.display_name as referred_name,
                    p.post_title as survey_title
             FROM $table_name r
             LEFT JOIN {$wpdb->users} referrer ON r.referrer_id = referrer.ID
             LEFT JOIN {$wpdb->users} referred ON r.referred_id = referred.ID
             LEFT JOIN {$wpdb->posts} p ON r.survey_id = p.ID
             ORDER BY r.created_at DESC
             LIMIT 50"
        );
        ?>
        <div class="wrap">
            <h1><?php _e('Referrals', 'rm-panel-extensions'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'rm-panel-extensions'); ?></th>
                        <th><?php _e('Referrer', 'rm-panel-extensions'); ?></th>
                        <th><?php _e('Referred User', 'rm-panel-extensions'); ?></th>
                        <th><?php _e('Survey', 'rm-panel-extensions'); ?></th>
                        <th><?php _e('Reward', 'rm-panel-extensions'); ?></th>
                        <th><?php _e('Status', 'rm-panel-extensions'); ?></th>
                        <th><?php _e('Date', 'rm-panel-extensions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($referrals)) : ?>
                        <tr>
                            <td colspan="7"><?php _e('No referrals found.', 'rm-panel-extensions'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($referrals as $referral) : ?>
                            <tr>
                                <td><?php echo $referral->id; ?></td>
                                <td><?php echo esc_html($referral->referrer_name); ?></td>
                                <td><?php echo esc_html($referral->referred_name); ?></td>
                                <td><?php echo $referral->survey_title ? esc_html($referral->survey_title) : '—'; ?></td>
                                <td>$<?php echo number_format($referral->reward_amount, 2); ?></td>
                                <td>
                                    <?php if ($referral->reward_paid) : ?>
                                        <span class="status-paid">✓ <?php _e('Paid', 'rm-panel-extensions'); ?></span>
                                    <?php else : ?>
                                        <span class="status-pending">⏳ <?php _e('Pending', 'rm-panel-extensions'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($referral->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

new RM_Referral_System();