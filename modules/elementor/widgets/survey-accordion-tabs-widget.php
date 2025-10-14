<?php
/**
 * RM Panel Survey Accordion Tabs Widget for Elementor
 * 
 * File: modules/elementor/widgets/survey-accordion-tabs-widget.php
 * 
 * @package RM_Panel_Extensions
 * @subpackage Modules/Elementor/Widgets
 */
if (!defined('ABSPATH')) {
    exit;
}

class RM_Panel_Survey_Accordion_Tabs_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'rm_panel_survey_accordion_tabs';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('Survey Accordion Tabs', 'rm-panel-extensions');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-tabs';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['rm-panel-widgets', 'general'];
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return ['survey', 'accordion', 'tabs', 'completed', 'available', 'rm panel'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'rm-panel-extensions'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => __('Columns', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
            ]
        );

        $this->add_control(
            'show_referral_button',
            [
                'label' => __('Show Invite Button', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Style Controls
        $this->register_style_controls();
    }

    /**
     * Register style controls
     */
    private function register_style_controls() {
        // Tab Navigation Style
        $this->start_controls_section(
            'tab_nav_style',
            [
                'label' => __('Tab Navigation', 'rm-panel-extensions'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'tab_bg_color',
            [
                'label' => __('Background Color', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f8f9fa',
                'selectors' => [
                    '{{WRAPPER}} .rm-accordion-tab-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tab_active_bg_color',
            [
                'label' => __('Active Background Color', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .rm-accordion-tab-btn.active' => 'background-color: {{VALUE}};',
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
            echo '<p>' . __('Please log in to view surveys.', 'rm-panel-extensions') . '</p>';
            return;
        }

        $settings = $this->get_settings_for_display();
        $user_id = get_current_user_id();

        // Get tracker instance
        if (!class_exists('RM_Panel_Survey_Tracking')) {
            echo '<p>' . __('Survey tracking module not available.', 'rm-panel-extensions') . '</p>';
            return;
        }

        $tracker = new RM_Panel_Survey_Tracking();
        
        // Get available surveys
        $available_surveys = $tracker->get_available_surveys($user_id);
        
        // Get completed surveys
        $completed_surveys = $tracker->get_user_survey_history($user_id, [
            'status' => 'completed',
            'limit' => 50
        ]);

        ?>
        <div class="rm-accordion-tabs-wrapper">
            <!-- Tab Navigation -->
            <div class="rm-accordion-tabs-nav">
                <button class="rm-accordion-tab-btn active" data-tab="available">
                    <span class="tab-icon">📋</span>
                    <span><?php _e('Available Surveys', 'rm-panel-extensions'); ?></span>
                    <span class="tab-count"><?php echo count($available_surveys); ?></span>
                </button>
                <button class="rm-accordion-tab-btn" data-tab="completed">
                    <span class="tab-icon">✅</span>
                    <span><?php _e('Completed Surveys', 'rm-panel-extensions'); ?></span>
                    <span class="tab-count"><?php echo count($completed_surveys); ?></span>
                </button>
            </div>

            <!-- Available Surveys Tab -->
            <div class="rm-accordion-tab-content active" id="available-tab">
                <?php if (empty($available_surveys)) : ?>
                    <div class="no-surveys-message">
                        <p><?php _e('No available surveys at the moment. Check back later!', 'rm-panel-extensions'); ?></p>
                    </div>
                <?php else : ?>
                    <div class="rm-survey-accordion">
                        <?php foreach ($available_surveys as $survey) : ?>
                            <?php $this->render_survey_accordion_item($survey, $settings); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Completed Surveys Tab -->
            <div class="rm-accordion-tab-content" id="completed-tab">
                <?php if (empty($completed_surveys)) : ?>
                    <div class="no-surveys-message">
                        <p><?php _e('You haven\'t completed any surveys yet.', 'rm-panel-extensions'); ?></p>
                    </div>
                <?php else : ?>
                    <div class="rm-survey-accordion">
                        <?php foreach ($completed_surveys as $response) : ?>
                            <?php $this->render_completed_accordion_item($response, $settings); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Invite Modal (if enabled) -->
        <?php if ($settings['show_referral_button'] === 'yes') : ?>
            <?php $this->render_invite_modal(); ?>
        <?php endif; ?>

        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.rm-accordion-tab-btn').on('click', function() {
                var tab = $(this).data('tab');
                
                $('.rm-accordion-tab-btn').removeClass('active');
                $(this).addClass('active');
                
                $('.rm-accordion-tab-content').removeClass('active');
                $('#' + tab + '-tab').addClass('active');
            });

            // Accordion functionality
            $('.rm-survey-accordion-header').on('click', function() {
                var $item = $(this).closest('.rm-survey-accordion-item');
                var $content = $item.find('.rm-survey-accordion-content');
                
                $item.toggleClass('active');
                $content.slideToggle(300);
            });

            // Invite button
            $('.rm-invite-button').on('click', function() {
                $('#invite-modal').fadeIn();
            });

            // Close modal
            $('.rm-invite-close, .rm-invite-modal').on('click', function(e) {
                if (e.target === this) {
                    $('#invite-modal').fadeOut();
                }
            });

            // Copy referral link
            $('.copy-link-btn').on('click', function() {
                var $input = $('.referral-link-input');
                $input.select();
                document.execCommand('copy');
                
                var $btn = $(this);
                var originalText = $btn.text();
                $btn.text('<?php _e('Copied!', 'rm-panel-extensions'); ?>');
                
                setTimeout(function() {
                    $btn.text(originalText);
                }, 2000);
            });
        });
        </script>
        <?php
    }

    /**
     * Render survey accordion item
     */
    private function render_survey_accordion_item($survey, $settings) {
        $survey_id = $survey->ID;
        $survey_type = get_post_meta($survey_id, '_rm_survey_type', true);
        $survey_amount = get_post_meta($survey_id, '_rm_survey_amount', true);
        $estimated_time = get_post_meta($survey_id, '_rm_survey_estimated_time', true);
        $questions_count = get_post_meta($survey_id, '_rm_survey_questions_count', true);
        $survey_url = get_post_meta($survey_id, '_rm_survey_url', true);
        $parameters = get_post_meta($survey_id, '_rm_survey_parameters', true);

        // Build survey URL
        $final_url = $this->build_survey_url($survey_id, $survey_url, $parameters);
        ?>
        <div class="rm-survey-accordion-item">
            <div class="rm-survey-accordion-header">
                <div class="rm-survey-accordion-header-content">
                    <h3 class="rm-survey-accordion-title"><?php echo esc_html($survey->post_title); ?></h3>
                    <div class="rm-survey-accordion-meta">
                        <?php if ($survey_type === 'paid' && $survey_amount) : ?>
                            <span class="rm-survey-meta-item">
                                💰 $<?php echo number_format($survey_amount, 2); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($questions_count) : ?>
                            <span class="rm-survey-meta-item">
                                📝 <?php echo $questions_count; ?> <?php _e('Questions', 'rm-panel-extensions'); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($estimated_time) : ?>
                            <span class="rm-survey-meta-item">
                                ⏱️ <?php echo $estimated_time; ?> <?php _e('min', 'rm-panel-extensions'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="rm-accordion-toggle-icon">▼</span>
            </div>

            <div class="rm-survey-accordion-content">
                <div class="rm-survey-content-wrapper">
                    <?php if ($survey->post_excerpt) : ?>
                        <p><?php echo esc_html($survey->post_excerpt); ?></p>
                    <?php endif; ?>

                    <div class="rm-survey-action">
                        <a href="<?php echo esc_url($final_url); ?>" 
                           class="rm-survey-button" 
                           target="_blank">
                            <?php _e('Start Survey', 'rm-panel-extensions'); ?>
                        </a>
                        
                        <?php if ($settings['show_referral_button'] === 'yes') : ?>
                            <button class="rm-invite-button" data-survey-id="<?php echo $survey_id; ?>">
                                <span class="dashicons dashicons-share"></span>
                                <?php _e('Invite Friends', 'rm-panel-extensions'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render completed survey accordion item
     */
    private function render_completed_accordion_item($response, $settings) {
        $survey = get_post($response->survey_id);
        if (!$survey) return;

        $survey_type = get_post_meta($response->survey_id, '_rm_survey_type', true);
        $survey_amount = get_post_meta($response->survey_id, '_rm_survey_amount', true);
        ?>
        <div class="rm-survey-accordion-item">
            <div class="rm-survey-accordion-header">
                <div class="rm-survey-accordion-header-content">
                    <h3 class="rm-survey-accordion-title"><?php echo esc_html($response->survey_title); ?></h3>
                    <div class="rm-survey-accordion-meta">
                        <span class="completion-badge">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Completed', 'rm-panel-extensions'); ?>
                        </span>
                        <?php if ($response->completion_time) : ?>
                            <span class="rm-survey-meta-item">
                                📅 <?php echo date_i18n(get_option('date_format'), strtotime($response->completion_time)); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="rm-accordion-toggle-icon">▼</span>
            </div>

            <div class="rm-survey-accordion-content">
                <div class="rm-completion-details">
                    <h4><?php _e('Completion Details', 'rm-panel-extensions'); ?></h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label"><?php _e('Status', 'rm-panel-extensions'); ?></span>
                            <span class="detail-value">
                                <span class="approval-status status-<?php echo esc_attr($response->completion_status); ?>">
                                    <?php echo $this->get_status_label($response->completion_status); ?>
                                </span>
                            </span>
                        </div>

                        <?php if ($survey_type === 'paid' && $response->completion_status === 'success') : ?>
                            <div class="detail-item">
                                <span class="detail-label"><?php _e('Amount Earned', 'rm-panel-extensions'); ?></span>
                                <span class="detail-value">$<?php echo number_format($survey_amount, 2); ?></span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label"><?php _e('Payment Status', 'rm-panel-extensions'); ?></span>
                                <span class="detail-value">
                                    <span class="approval-status status-<?php echo esc_attr($response->approval_status ?? 'pending'); ?>">
                                        <?php 
                                        $approval_status = $response->approval_status ?? 'pending';
                                        $labels = [
                                            'pending' => __('Pending Review', 'rm-panel-extensions'),
                                            'approved' => __('Approved', 'rm-panel-extensions'),
                                            'rejected' => __('Not Approved', 'rm-panel-extensions')
                                        ];
                                        echo $labels[$approval_status] ?? $approval_status;
                                        ?>
                                    </span>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render invite modal
     */
    private function render_invite_modal() {
        $user_id = get_current_user_id();
        $registration_url = get_option('rm_referral_registration_url', wp_registration_url());
        $referral_link = add_query_arg('ref', $user_id, $registration_url);
        ?>
        <div id="invite-modal" class="rm-invite-modal">
            <div class="rm-invite-modal-content">
                <span class="rm-invite-close">&times;</span>
                <h3><?php _e('Invite Friends to Earn More!', 'rm-panel-extensions'); ?></h3>
                <p><?php _e('Share your referral link and earn rewards when friends sign up.', 'rm-panel-extensions'); ?></p>

                <div class="referral-link-container">
                    <input type="text" 
                           class="referral-link-input" 
                           value="<?php echo esc_url($referral_link); ?>" 
                           readonly>
                    <button class="copy-link-btn"><?php _e('Copy Link', 'rm-panel-extensions'); ?></button>
                </div>

                <div class="social-share-buttons">
                    <button class="share-btn whatsapp-share" 
                            onclick="window.open('https://wa.me/?text=<?php echo urlencode('Join me on this survey platform: ' . $referral_link); ?>', '_blank')">
                        <i class="fab fa-whatsapp"></i>
                        <?php _e('WhatsApp', 'rm-panel-extensions'); ?>
                    </button>
                    <button class="share-btn facebook-share" 
                            onclick="window.open('https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($referral_link); ?>', '_blank')">
                        <i class="fab fa-facebook"></i>
                        <?php _e('Facebook', 'rm-panel-extensions'); ?>
                    </button>
                    <button class="share-btn twitter-share" 
                            onclick="window.open('https://twitter.com/intent/tweet?url=<?php echo urlencode($referral_link); ?>&text=<?php echo urlencode('Check out this survey platform!'); ?>', '_blank')">
                        <i class="fab fa-twitter"></i>
                        <?php _e('Twitter', 'rm-panel-extensions'); ?>
                    </button>
                    <button class="share-btn email-share" 
                            onclick="window.location.href='mailto:?subject=<?php echo urlencode('Join this survey platform'); ?>&body=<?php echo urlencode('Join me: ' . $referral_link); ?>'">
                        <i class="fas fa-envelope"></i>
                        <?php _e('Email', 'rm-panel-extensions'); ?>
                    </button>
                </div>

                <?php
                // Get referral stats if referral system exists
                if (class_exists('RM_Referral_System')) {
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'rm_referrals';
                    $referral_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_name WHERE referrer_id = %d",
                        $user_id
                    ));
                    $earnings = get_user_meta($user_id, 'rm_referral_earnings', true) ?: 0;
                    ?>
                    <div class="invite-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo intval($referral_count); ?></span>
                            <span class="stat-label"><?php _e('Referrals', 'rm-panel-extensions'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">$<?php echo number_format($earnings, 2); ?></span>
                            <span class="stat-label"><?php _e('Earned', 'rm-panel-extensions'); ?></span>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        <?php
    }

    /**
     * Build survey URL with parameters
     */
    private function build_survey_url($survey_id, $base_url, $parameters) {
        if (empty($base_url)) {
            return get_permalink($survey_id);
        }

        if (empty($parameters) || !is_array($parameters)) {
            return $base_url;
        }

        $user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        $query_params = [];

        foreach ($parameters as $param) {
            if (empty($param['variable'])) continue;

            $value = '';
            switch ($param['field']) {
                case 'survey_id':
                    $value = $survey_id;
                    break;
                case 'user_id':
                    $value = $user_id;
                    break;
                case 'username':
                    $value = $current_user->user_login;
                    break;
                case 'email':
                    $value = $current_user->user_email;
                    break;
                case 'first_name':
                    $value = $current_user->first_name;
                    break;
                case 'last_name':
                    $value = $current_user->last_name;
                    break;
                case 'display_name':
                    $value = $current_user->display_name;
                    break;
                case 'timestamp':
                    $value = time();
                    break;
                case 'custom':
                    $value = $param['custom_value'] ?? '';
                    break;
            }

            if (!empty($value)) {
                $query_params[$param['variable']] = $value;
            }
        }

        return add_query_arg($query_params, $base_url);
    }

    /**
     * Get status label
     */
    private function get_status_label($status) {
        $labels = [
            'success' => __('Successful', 'rm-panel-extensions'),
            'quota_complete' => __('Quota Full', 'rm-panel-extensions'),
            'disqualified' => __('Disqualified', 'rm-panel-extensions')
        ];
        return $labels[$status] ?? $status;
    }
}