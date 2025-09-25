<?php
/**
 * RM Panel Survey Accordion Widget for Elementor
 * 
 * File: modules/elementor/widgets/survey-accordion-widget.php
 * 
 * @package RM_Panel_Extensions
 * @subpackage Modules/Elementor/Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RM_Panel_Survey_Accordion_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'rm_panel_survey_accordion';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __( 'Survey Accordion', 'rm-panel-extensions' );
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-accordion';
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
        return [ 'survey', 'accordion', 'collapse', 'expand', 'rm panel', 'questionnaire', 'forms' ];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        
        // Query Section
        $this->start_controls_section(
            'query_section',
            [
                'label' => __( 'Query', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label' => __( 'Number of Surveys', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => -1,
                'min' => -1,
                'description' => __( 'Use -1 to show all surveys', 'rm-panel-extensions' ),
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label' => __( 'Order By', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date' => __( 'Date', 'rm-panel-extensions' ),
                    'title' => __( 'Title', 'rm-panel-extensions' ),
                    'menu_order' => __( 'Menu Order', 'rm-panel-extensions' ),
                    'start_date' => __( 'Start Date', 'rm-panel-extensions' ),
                    'end_date' => __( 'End Date', 'rm-panel-extensions' ),
                ],
            ]
        );

        $this->add_control(
            'order',
            [
                'label' => __( 'Order', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'ASC' => __( 'Ascending', 'rm-panel-extensions' ),
                    'DESC' => __( 'Descending', 'rm-panel-extensions' ),
                ],
            ]
        );

        $this->add_control(
            'survey_status_filter',
            [
                'label' => __( 'Filter by Status', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => [
                    'draft' => __( 'Draft', 'rm-panel-extensions' ),
                    'active' => __( 'Active', 'rm-panel-extensions' ),
                    'paused' => __( 'Paused', 'rm-panel-extensions' ),
                    'closed' => __( 'Closed', 'rm-panel-extensions' ),
                ],
                'default' => [ 'active' ],
            ]
        );

        // Get survey categories
        $categories = get_terms( [
            'taxonomy' => 'survey_category',
            'hide_empty' => false,
        ] );

        $category_options = [];
        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
            foreach ( $categories as $category ) {
                $category_options[ $category->term_id ] = $category->name;
            }
        }

        $this->add_control(
            'categories',
            [
                'label' => __( 'Filter by Categories', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $category_options,
                'default' => [],
            ]
        );

        $this->end_controls_section();

        // Display Settings
        $this->start_controls_section(
            'display_section',
            [
                'label' => __( 'Display Settings', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'first_item_expanded',
            [
                'label' => __( 'First Item Expanded', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __( 'Expand the first survey by default', 'rm-panel-extensions' ),
            ]
        );

        $this->add_control(
            'toggle_icon',
            [
                'label' => __( 'Toggle Icon', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-chevron-down',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'toggle_icon_active',
            [
                'label' => __( 'Toggle Icon Active', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-chevron-up',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'allow_multiple_expanded',
            [
                'label' => __( 'Allow Multiple Expanded', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'no',
                'description' => __( 'Allow multiple surveys to be expanded at once', 'rm-panel-extensions' ),
            ]
        );

        $this->end_controls_section();

        // Content Display
        $this->start_controls_section(
            'content_display_section',
            [
                'label' => __( 'Content Display', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_status_badge',
            [
                'label' => __( 'Show Status Badge', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_category',
            [
                'label' => __( 'Show Category', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_thumbnail',
            [
                'label' => __( 'Show Thumbnail', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_dates',
            [
                'label' => __( 'Show Dates', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_questions_count',
            [
                'label' => __( 'Show Questions Count', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_estimated_time',
            [
                'label' => __( 'Show Estimated Time', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_payment_info',
            [
                'label' => __( 'Show Payment Info', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_target_audience',
            [
                'label' => __( 'Show Target Audience', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_take_survey_button',
            [
                'label' => __( 'Show Take Survey Button', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => __( 'Button Text', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Take Survey', 'rm-panel-extensions' ),
                'condition' => [
                    'show_take_survey_button' => 'yes',
                ],
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
        // Accordion Item Style
        $this->start_controls_section(
            'accordion_style',
            [
                'label' => __( 'Accordion Item', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'item_background',
            [
                'label' => __( 'Background Color', 'rm-panel-extensions' ),
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
                'label' => __( 'Border Radius', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-accordion-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'item_spacing',
            [
                'label' => __( 'Item Spacing', 'rm-panel-extensions' ),
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
                    '{{WRAPPER}} .rm-survey-accordion-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
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

        // Header Style
        $this->start_controls_section(
            'header_style',
            [
                'label' => __( 'Accordion Header', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'header_background',
            [
                'label' => __( 'Background Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f8f9fa',
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-accordion-header' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'header_hover_background',
            [
                'label' => __( 'Hover Background Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#e9ecef',
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-accordion-header:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'header_active_background',
            [
                'label' => __( 'Active Background Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-accordion-item.active .rm-survey-accordion-header' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'header_padding',
            [
                'label' => __( 'Padding', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'default' => [
                    'top' => '15',
                    'right' => '20',
                    'bottom' => '15',
                    'left' => '20',
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-accordion-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Title Style
        $this->start_controls_section(
            'title_style',
            [
                'label' => __( 'Title', 'rm-panel-extensions' ),
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
                'label' => __( 'Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-accordion-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'title_active_color',
            [
                'label' => __( 'Active Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-accordion-item.active .rm-survey-accordion-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Content Style
        $this->start_controls_section(
            'content_style',
            [
                'label' => __( 'Content', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'content_background',
            [
                'label' => __( 'Background Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-accordion-content' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'content_padding',
            [
                'label' => __( 'Padding', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'default' => [
                    'top' => '20',
                    'right' => '20',
                    'bottom' => '20',
                    'left' => '20',
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-accordion-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'selector' => '{{WRAPPER}} .rm-survey-accordion-content',
            ]
        );

        $this->add_control(
            'content_color',
            [
                'label' => __( 'Text Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-accordion-content' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Button Style
        $this->start_controls_section(
            'button_style',
            [
                'label' => __( 'Button', 'rm-panel-extensions' ),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_take_survey_button' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .rm-survey-button',
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
                    '{{WRAPPER}} .rm-survey-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background',
            [
                'label' => __( 'Background Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-button' => 'background-color: {{VALUE}};',
                ],
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
            'button_hover_text_color',
            [
                'label' => __( 'Text Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_background',
            [
                'label' => __( 'Background Color', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#005a87',
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'button_border_radius',
            [
                'label' => __( 'Border Radius', 'rm-panel-extensions' ),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'separator' => 'before',
                'selectors' => [
                    '{{WRAPPER}} .rm-survey-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .rm-survey-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
        
        // Build query arguments
        $query_args = $this->build_query_args( $settings );
        
        // Get surveys
        $surveys = new WP_Query( $query_args );
        
        if ( ! $surveys->have_posts() ) {
            echo '<p>' . __( 'No surveys found.', 'rm-panel-extensions' ) . '</p>';
            return;
        }
        
        $widget_id = $this->get_id();
        ?>
        <div class="rm-survey-accordion" id="rm-accordion-<?php echo esc_attr( $widget_id ); ?>" data-allow-multiple="<?php echo esc_attr( $settings['allow_multiple_expanded'] ); ?>">
            <?php 
            $index = 0;
            while ( $surveys->have_posts() ) : 
                $surveys->the_post(); 
                $this->render_survey_accordion_item( $settings, $index, $widget_id );
                $index++;
            endwhile; 
            ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var accordionId = '#rm-accordion-<?php echo esc_js( $widget_id ); ?>';
            var allowMultiple = $(accordionId).data('allow-multiple') === 'yes';
            
            // Toggle accordion items
            $(accordionId + ' .rm-survey-accordion-header').on('click', function(e) {
                e.preventDefault();
                var $item = $(this).closest('.rm-survey-accordion-item');
                var $content = $item.find('.rm-survey-accordion-content');
                var $accordion = $(this).closest('.rm-survey-accordion');
                
                if (!allowMultiple) {
                    // Close other items if multiple not allowed
                    $accordion.find('.rm-survey-accordion-item').not($item).removeClass('active');
                    $accordion.find('.rm-survey-accordion-content').not($content).slideUp(300);
                }
                
                // Toggle current item
                $item.toggleClass('active');
                $content.slideToggle(300);
                
                // Update icon
                var $icon = $(this).find('.rm-accordion-toggle-icon');
                if ($item.hasClass('active')) {
                    $icon.removeClass('<?php echo esc_js( $settings['toggle_icon']['value'] ); ?>');
                    $icon.addClass('<?php echo esc_js( $settings['toggle_icon_active']['value'] ); ?>');
                } else {
                    $icon.removeClass('<?php echo esc_js( $settings['toggle_icon_active']['value'] ); ?>');
                    $icon.addClass('<?php echo esc_js( $settings['toggle_icon']['value'] ); ?>');
                }
            });
            
            // Prevent button clicks from toggling accordion
            $(accordionId + ' .rm-survey-button').on('click', function(e) {
                e.stopPropagation();
            });
        });
        </script>
        <?php
        
        wp_reset_postdata();
    }

    /**
     * Build query arguments
     */
    private function build_query_args( $settings ) {
        $args = [
            'post_type' => 'rm_survey',
            'posts_per_page' => $settings['posts_per_page'],
            'orderby' => $settings['orderby'],
            'order' => $settings['order'],
            'post_status' => 'publish',
        ];
        
        // Handle custom orderby
        if ( in_array( $settings['orderby'], [ 'start_date', 'end_date' ] ) ) {
            $args['meta_key'] = '_rm_survey_' . $settings['orderby'];
            $args['orderby'] = 'meta_value';
        }
        
        // Category filter
        if ( ! empty( $settings['categories'] ) ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'survey_category',
                    'field' => 'term_id',
                    'terms' => $settings['categories'],
                ],
            ];
        }
        
        // Status filter
        if ( ! empty( $settings['survey_status_filter'] ) ) {
            $args['meta_query'][] = [
                'key' => '_rm_survey_status',
                'value' => $settings['survey_status_filter'],
                'compare' => 'IN',
            ];
        }
        
        return $args;
    }

    /**
     * Render single accordion item
     */
    private function render_survey_accordion_item( $settings, $index, $widget_id ) {
        $post_id = get_the_ID();
        
        // Get meta data
        $status = get_post_meta( $post_id, '_rm_survey_status', true );
        $survey_type = get_post_meta( $post_id, '_rm_survey_type', true );
        $survey_amount = get_post_meta( $post_id, '_rm_survey_amount', true );
        $survey_url = get_post_meta( $post_id, '_rm_survey_url', true );
        $parameters = get_post_meta( $post_id, '_rm_survey_parameters', true );
        $duration_type = get_post_meta( $post_id, '_rm_survey_duration_type', true );
        $start_date = get_post_meta( $post_id, '_rm_survey_start_date', true );
        $end_date = get_post_meta( $post_id, '_rm_survey_end_date', true );
        $questions_count = get_post_meta( $post_id, '_rm_survey_questions_count', true );
        $estimated_time = get_post_meta( $post_id, '_rm_survey_estimated_time', true );
        $target_audience = get_post_meta( $post_id, '_rm_survey_target_audience', true );
        
        $is_active = ( $settings['first_item_expanded'] === 'yes' && $index === 0 ) ? 'active' : '';
        $display = ( $settings['first_item_expanded'] === 'yes' && $index === 0 ) ? 'block' : 'none';
        
        // Build survey URL with parameters
        $final_survey_url = $survey_url;
        if ( is_user_logged_in() && ! empty( $parameters ) && ! empty( $survey_url ) ) {
            $current_user = wp_get_current_user();
            $query_params = [];
            
            foreach ( $parameters as $param ) {
                $value = '';
                
                switch ( $param['field'] ) {
                    case 'user_id':
                        $value = $current_user->ID;
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
                    case 'user_role':
                        $value = implode( ',', $current_user->roles );
                        break;
                    case 'custom':
                        $value = $param['custom_value'];
                        break;
                }
                
                if ( ! empty( $value ) && ! empty( $param['variable'] ) ) {
                    $query_params[ $param['variable'] ] = $value;
                }
            }
            
            if ( ! empty( $query_params ) ) {
                $final_survey_url = add_query_arg( $query_params, $survey_url );
            }
        }
        ?>
        <div class="rm-survey-accordion-item <?php echo esc_attr( $is_active ); ?>">
            <div class="rm-survey-accordion-header">
                <div class="rm-survey-accordion-header-content">
                    <h3 class="rm-survey-accordion-title"><?php the_title(); ?></h3>
                    
                    <div class="rm-survey-accordion-meta">
                        <?php if ( $settings['show_status_badge'] === 'yes' && $status ) : ?>
                            <span class="rm-survey-status-badge status-<?php echo esc_attr( $status ); ?>">
                                <?php echo esc_html( ucfirst( $status ) ); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ( $survey_type === 'paid' && $survey_amount ) : ?>
                            <span class="rm-survey-amount-badge">
                                $<?php echo number_format( $survey_amount, 2 ); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ( $settings['show_questions_count'] === 'yes' && $questions_count ) : ?>
                            <span class="rm-survey-meta-item">
                                <i class="eicon-editor-list-ul"></i>
                                <?php echo sprintf( _n( '%s Question', '%s Questions', $questions_count, 'rm-panel-extensions' ), $questions_count ); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ( $settings['show_estimated_time'] === 'yes' && $estimated_time ) : ?>
                            <span class="rm-survey-meta-item">
                                <i class="eicon-clock"></i>
                                <?php echo sprintf( _n( '%s Min', '%s Mins', $estimated_time, 'rm-panel-extensions' ), $estimated_time ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <span class="rm-accordion-toggle-icon <?php echo esc_attr( $is_active ? $settings['toggle_icon_active']['value'] : $settings['toggle_icon']['value'] ); ?>"></span>
            </div>
            
            <div class="rm-survey-accordion-content" style="display: <?php echo $display; ?>;">
                <div class="rm-survey-content-wrapper">
                    <?php if ( $settings['show_thumbnail'] === 'yes' && has_post_thumbnail() ) : ?>
                        <div class="rm-survey-thumbnail">
                            <?php the_post_thumbnail( 'medium' ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="rm-survey-details">
                        <?php if ( $settings['show_category'] === 'yes' ) : ?>
                            <?php $categories = get_the_terms( $post_id, 'survey_category' ); ?>
                            <?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
                                <div class="rm-survey-categories">
                                    <?php foreach ( $categories as $category ) : ?>
                                        <span class="rm-survey-category">
                                            <?php echo esc_html( $category->name ); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="rm-survey-description">
                            <?php the_excerpt(); ?>
                        </div>
                        
                        <div class="rm-survey-info-grid">
                            <?php if ( $settings['show_dates'] === 'yes' && $duration_type === 'date_range' ) : ?>
                                <?php if ( $start_date || $end_date ) : ?>
                                    <div class="rm-survey-info-item">
                                        <strong><?php _e( 'Duration:', 'rm-panel-extensions' ); ?></strong>
                                        <?php if ( $start_date && $end_date ) : ?>
                                            <?php echo date( 'M j, Y', strtotime( $start_date ) ); ?> - <?php echo date( 'M j, Y', strtotime( $end_date ) ); ?>
                                        <?php elseif ( $start_date ) : ?>
                                            <?php _e( 'Starts:', 'rm-panel-extensions' ); ?> <?php echo date( 'M j, Y', strtotime( $start_date ) ); ?>
                                        <?php elseif ( $end_date ) : ?>
                                            <?php _e( 'Ends:', 'rm-panel-extensions' ); ?> <?php echo date( 'M j, Y', strtotime( $end_date ) ); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ( $settings['show_payment_info'] === 'yes' && $survey_type === 'paid' && $survey_amount ) : ?>
                                <div class="rm-survey-info-item">
                                    <strong><?php _e( 'Compensation:', 'rm-panel-extensions' ); ?></strong>
                                    $<?php echo number_format( $survey_amount, 2 ); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ( $settings['show_target_audience'] === 'yes' && $target_audience ) : ?>
                                <div class="rm-survey-info-item">
                                    <strong><?php _e( 'Target Audience:', 'rm-panel-extensions' ); ?></strong>
                                    <?php echo esc_html( $target_audience ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ( $settings['show_take_survey_button'] === 'yes' && $final_survey_url ) : ?>
                            <div class="rm-survey-action">
                                <a href="<?php echo esc_url( $final_survey_url ); ?>" class="rm-survey-button" target="_blank">
                                    <?php echo esc_html( $settings['button_text'] ); ?>
                                    <i class="fas fa-external-link-alt" style="margin-left: 5px;"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}