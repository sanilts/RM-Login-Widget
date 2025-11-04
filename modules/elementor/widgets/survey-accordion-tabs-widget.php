<?php
/**
 * RM Panel Survey Accordion Tabs Widget for Elementor (COMPLETION DETAILS FIXED)
 * 
 * File: modules/elementor/widgets/survey-accordion-tabs-widget.php
 * Version: 1.1.2 (FIXED - Proper HTML structure for completion details)
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
        
        // ============================================
        // CONTENT TAB
        // ============================================
        
        // Layout Section
        $this->start_controls_section(
            'layout_section',
            [
                'label' => __('Layout', 'rm-panel-extensions'),
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

        $this->end_controls_section();

        // Content Display Section
        $this->start_controls_section(
            'content_display_section',
            [
                'label' => __('Content Display', 'rm-panel-extensions'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_description',
            [
                'label' => __('Show Description', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Show survey description/excerpt', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'show_survey_type',
            [
                'label' => __('Show Survey Type', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Show if survey is paid or standard', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'show_amount',
            [
                'label' => __('Show Amount', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Show earning amount for paid surveys', 'rm-panel-extensions'),
                'condition' => [
                    'show_survey_type' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_duration',
            [
                'label' => __('Show Duration', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Show survey duration (date range or never ending)', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'show_days_remaining',
            [
                'label' => __('Show Days Remaining', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Show countdown for surveys with end dates', 'rm-panel-extensions'),
                'condition' => [
                    'show_duration' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_questions_count',
            [
                'label' => __('Show Questions Count', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Show number of questions in survey', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'show_estimated_time',
            [
                'label' => __('Show Estimated Time', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Show estimated completion time', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'show_target_audience',
            [
                'label' => __('Show Target Audience', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Show target audience description', 'rm-panel-extensions'),
            ]
        );

        $this->end_controls_section();

        // Header Meta Section
        $this->start_controls_section(
            'header_meta_section',
            [
                'label' => __('Header Meta', 'rm-panel-extensions'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'header_show_amount',
            [
                'label' => __('Show Amount in Header', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Show earning amount in accordion header', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'header_show_questions',
            [
                'label' => __('Show Questions in Header', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Show questions count in accordion header', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'header_show_time',
            [
                'label' => __('Show Time in Header', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Show estimated time in accordion header', 'rm-panel-extensions'),
            ]
        );

        $this->end_controls_section();

        // Button Section
        $this->start_controls_section(
            'button_section',
            [
                'label' => __('Buttons', 'rm-panel-extensions'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_start_button',
            [
                'label' => __('Show Start Survey Button', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'start_button_text',
            [
                'label' => __('Start Button Text', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Start Survey', 'rm-panel-extensions'),
                'condition' => [
                    'show_start_button' => 'yes',
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

        $this->add_control(
            'invite_button_text',
            [
                'label' => __('Invite Button Text', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Invite Friends', 'rm-panel-extensions'),
                'condition' => [
                    'show_referral_button' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Messages Section
        $this->start_controls_section(
            'messages_section',
            [
                'label' => __('Messages', 'rm-panel-extensions'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'no_available_message',
            [
                'label' => __('No Available Surveys Message', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('No available surveys at the moment. Check back later!', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'no_completed_message',
            [
                'label' => __('No Completed Surveys Message', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('You haven\'t completed any surveys yet.', 'rm-panel-extensions'),
            ]
        );

        $this->end_controls_section();

        // ============================================
        // STYLE TAB
        // ============================================
        
        // Register style controls
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
                'default' => '#f9fafb',
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
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rm-accordion-tab-btn.active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tab_text_color',
            [
                'label' => __('Text Color', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#6b7280',
                'selectors' => [
                    '{{WRAPPER}} .rm-accordion-tab-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tab_active_text_color',
            [
                'label' => __('Active Text Color', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#1f2937',
                'selectors' => [
                    '{{WRAPPER}} .rm-accordion-tab-btn.active' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Accordion Item Style
        $this->start_controls_section(
            'accordion_item_style',
            [
                'label' => __('Accordion Item', 'rm-panel-extensions'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'item_background',
            [
                'label' => __('Background Color', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-accordion-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'item_border',
                'selector' => '{{WRAPPER}} .rm-survey-accordion-item',
            ]
        );

        $this->add_control(
            'item_border_radius',
            [
                'label' => __('Border Radius', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-accordion-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'item_box_shadow',
                'selector' => '{{WRAPPER}} .rm-survey-accordion-item',
            ]
        );

        $this->end_controls_section();

        // Title Style
        $this->start_controls_section(
            'title_style',
            [
                'label' => __('Title', 'rm-panel-extensions'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .rm-survey-accordion-title',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Color', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#1f2937',
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-accordion-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Button Style
        $this->start_controls_section(
            'button_style',
            [
                'label' => __('Buttons', 'rm-panel-extensions'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs('button_tabs');

        // Start Survey Button
        $this->start_controls_tab(
            'start_button_tab',
            [
                'label' => __('Start Button', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'start_button_bg',
            [
                'label' => __('Background Color', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#3b82f6',
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'start_button_color',
            [
                'label' => __('Text Color', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        // Invite Button
        $this->start_controls_tab(
            'invite_button_tab',
            [
                'label' => __('Invite Button', 'rm-panel-extensions'),
            ]
        );

        $this->add_control(
            'invite_button_bg',
            [
                'label' => __('Background Color', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#10b981',
                'selectors' => [
                    '{{WRAPPER}} .rm-invite-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'invite_button_color',
            [
                'label' => __('Text Color', 'rm-panel-extensions'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rm-invite-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

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
        
        // Filter by date range
        $current_date = current_time('Y-m-d');
        $available_surveys = array_filter($available_surveys, function($survey) use ($current_date) {
            $start_date = get_post_meta($survey->ID, '_rm_survey_start_date', true);
            $end_date = get_post_meta($survey->ID, '_rm_survey_end_date', true);
            
            if (!empty($start_date) && $start_date > $current_date) {
                return false;
            }
            
            if (!empty($end_date) && $end_date < $current_date) {
                return false;
            }
            
            return true;
        });
        
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
                        <p><?php echo esc_html($settings['no_available_message']); ?></p>
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
                        <p><?php echo esc_html($settings['no_completed_message']); ?></p>
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
            <?php $this->render_invite_modal($settings); ?>
        <?php endif; ?>

        <script>
        jQuery(document).ready(function($) {
            'use strict';
            
            // Tab switching
            $('.rm-accordion-tab-btn').on('click', function() {
                var tab = $(this).data('tab');
                
                $('.rm-accordion-tab-btn').removeClass('active');
                $(this).addClass('active');
                
                $('.rm-accordion-tab-content').removeClass('active');
                $('#' + tab + '-tab').addClass('active');
            });

            // Accordion functionality - FIXED v1.1.1
            // Let CSS handle the animation via max-height transition
            $('.rm-survey-accordion-header').on('click', function() {
                var $item = $(this).closest('.rm-survey-accordion-item');
                
                // Just toggle the active class - CSS handles the animation
                $item.toggleClass('active');
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
        $duration_type = get_post_meta($survey_id, '_rm_survey_duration_type', true);
        $start_date = get_post_meta($survey_id, '_rm_survey_start_date', true);
        $end_date = get_post_meta($survey_id, '_rm_survey_end_date', true);
        $target_audience = get_post_meta($survey_id, '_rm_survey_target_audience', true);

        // Build survey URL
        $final_url = $this->build_survey_url($survey_id, $survey_url, $parameters);
        
        // Calculate if survey is expired
        $is_expired = false;
        $days_remaining = null;
        if ($duration_type === 'date_range' && !empty($end_date)) {
            $current_date = current_time('timestamp');
            $end_timestamp = strtotime($end_date);
            $is_expired = $end_timestamp < $current_date;
            
            if (!$is_expired) {
                $days_remaining = ceil(($end_timestamp - $current_date) / DAY_IN_SECONDS);
            }
        }
        ?>
        <div class="rm-survey-accordion-item">
            <div class="rm-survey-accordion-header">
                <div class="rm-survey-accordion-header-content">
                    <h3 class="rm-survey-accordion-title"><?php echo esc_html($survey->post_title); ?></h3>
                    <div class="rm-survey-accordion-meta">
                        <?php if ($settings['header_show_amount'] === 'yes' && $survey_type === 'paid' && $survey_amount) : ?>
                            <span class="rm-survey-meta-item">
                                💰 $<?php echo number_format($survey_amount, 2); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($settings['header_show_questions'] === 'yes' && $questions_count) : ?>
                            <span class="rm-survey-meta-item">
                                📝 <?php echo $questions_count; ?> <?php _e('Questions', 'rm-panel-extensions'); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($settings['header_show_time'] === 'yes' && $estimated_time) : ?>
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
                    
                    <!-- About This Survey Section -->
                    <div class="rm-about-survey-section">
                        <h4 class="rm-section-heading">📋 About This Survey</h4>
                        
                        <!-- Description -->
                        <?php if ($settings['show_description'] === 'yes' && ($survey->post_excerpt || $survey->post_content)) : ?>
                            <div class="rm-survey-description-text">
                                <?php 
                                if (!empty($survey->post_excerpt)) {
                                    echo '<p>' . esc_html($survey->post_excerpt) . '</p>';
                                } elseif (!empty($survey->post_content)) {
                                    echo wpautop(wp_trim_words($survey->post_content, 50));
                                }
                                ?>
                            </div>
                        <?php endif; ?>

                        <!-- Info Cards Grid -->
                        <div class="rm-info-cards-grid">
                            
                            <!-- Survey Type Card -->
                            <?php if ($settings['show_survey_type'] === 'yes') : ?>
                                <div class="rm-info-card rm-type-card">
                                    <div class="rm-card-icon">💳</div>
                                    <div class="rm-card-content">
                                        <div class="rm-card-label">SURVEY TYPE</div>
                                        <div class="rm-card-value">
                                            <?php if ($survey_type === 'paid') : ?>
                                                Paid Survey
                                            <?php else : ?>
                                                Standard Survey
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($survey_type === 'paid' && $survey_amount && $settings['show_amount'] === 'yes') : ?>
                                            <div class="rm-earn-amount">Earn: $<?php echo number_format($survey_amount, 2); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Duration Card -->
                            <?php if ($settings['show_duration'] === 'yes') : ?>
                                <div class="rm-info-card rm-duration-card">
                                    <div class="rm-card-icon">🔄</div>
                                    <div class="rm-card-content">
                                        <div class="rm-card-label">DURATION</div>
                                        <div class="rm-card-value">
                                            <?php if ($duration_type === 'never_ending') : ?>
                                                Never Ending
                                            <?php elseif ($duration_type === 'date_range') : ?>
                                                <?php if ($is_expired) : ?>
                                                    <span class="rm-expired-text">Expired</span>
                                                <?php else : ?>
                                                    <?php if ($start_date && $end_date) : ?>
                                                        <?php echo date_i18n('M j', strtotime($start_date)); ?> - <?php echo date_i18n('M j, Y', strtotime($end_date)); ?>
                                                    <?php elseif ($end_date) : ?>
                                                        Until <?php echo date_i18n('M j, Y', strtotime($end_date)); ?>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php else : ?>
                                                Not specified
                                            <?php endif; ?>
                                        </div>
                                        <div class="rm-card-subtitle">
                                            <?php if ($duration_type === 'never_ending') : ?>
                                                Available anytime
                                            <?php elseif ($is_expired) : ?>
                                                Survey has ended
                                            <?php elseif ($days_remaining !== null && $settings['show_days_remaining'] === 'yes') : ?>
                                                <?php echo sprintf(_n('%s day remaining', '%s days remaining', $days_remaining, 'rm-panel-extensions'), $days_remaining); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- Survey Length - Yellow Highlight Box -->
                        <?php if (($settings['show_questions_count'] === 'yes' && $questions_count) || ($settings['show_estimated_time'] === 'yes' && $estimated_time)) : ?>
                            <div class="rm-survey-length-box">
                                <div class="rm-length-icon">📊</div>
                                <div class="rm-length-content">
                                    <div class="rm-length-label">Survey Length</div>
                                    <div class="rm-length-details">
                                        <?php if ($settings['show_questions_count'] === 'yes' && $questions_count) : ?>
                                            <span><?php echo $questions_count; ?> Questions</span>
                                        <?php endif; ?>
                                        <?php if ($settings['show_questions_count'] === 'yes' && $questions_count && $settings['show_estimated_time'] === 'yes' && $estimated_time) : ?>
                                            <span class="rm-separator">•</span>
                                        <?php endif; ?>
                                        <?php if ($settings['show_estimated_time'] === 'yes' && $estimated_time) : ?>
                                            <span>~<?php echo $estimated_time; ?> minutes to complete</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Target Audience -->
                        <?php if ($settings['show_target_audience'] === 'yes' && !empty($target_audience)) : ?>
                            <div class="rm-target-audience-box">
                                <div class="rm-audience-icon">🎯</div>
                                <div class="rm-audience-content">
                                    <div class="rm-audience-label">Target Audience</div>
                                    <div class="rm-audience-text">
                                        <?php echo wp_kses_post($target_audience); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>

                    <!-- Action Buttons -->
                    <?php if ($settings['show_start_button'] === 'yes' || $settings['show_referral_button'] === 'yes') : ?>
                        <div class="rm-survey-action">
                            <?php if ($settings['show_start_button'] === 'yes') : ?>
                                <?php if (!$is_expired) : ?>
                                    <a href="<?php echo esc_url($final_url); ?>" 
                                       class="rm-survey-button" 
                                       target="_blank"
                                       data-survey-id="<?php echo esc_attr($survey_id); ?>">
                                        <?php echo esc_html($settings['start_button_text']); ?>
                                    </a>
                                <?php else : ?>
                                    <button class="rm-survey-button rm-button-disabled" disabled>
                                        Survey Expired
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($settings['show_referral_button'] === 'yes' && !$is_expired) : ?>
                                <button class="rm-invite-button" data-survey-id="<?php echo esc_attr($survey_id); ?>">
                                    <span class="dashicons dashicons-share"></span>
                                    <?php echo esc_html($settings['invite_button_text']); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render completed survey accordion item - FIXED VERSION
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
                <div class="rm-survey-content-wrapper">
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
        </div>
        <?php
    }

    /**
     * Render invite modal
     */
    private function render_invite_modal($settings) {
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