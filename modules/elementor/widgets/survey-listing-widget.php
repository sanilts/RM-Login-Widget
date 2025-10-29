<?php
/**
 * RM Panel Survey Listing Widget for Elementor
 * 
 * @package RM_Panel_Extensions
 * @subpackage Modules/Elementor/Widgets
 */
if (!defined('ABSPATH')) {
    exit;
}

class RM_Panel_Survey_Listing_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'rm_panel_survey_listing';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('Survey Listing', 'rm-panel-extensions');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-posts-grid';
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
        return ['survey', 'listing', 'grid', 'posts', 'rm panel', 'questionnaire', 'forms'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {

        // Query Section
        $this->start_controls_section(
                'query_section',
                [
                    'label' => __('Query', 'rm-panel-extensions'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
        );

        $this->add_control(
                'posts_per_page',
                [
                    'label' => __('Surveys Per Page', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'default' => 6,
                    'min' => 1,
                    'max' => 50,
                ]
        );

        $this->add_control(
                'orderby',
                [
                    'label' => __('Order By', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'date',
                    'options' => [
                        'date' => __('Date', 'rm-panel-extensions'),
                        'title' => __('Title', 'rm-panel-extensions'),
                        'menu_order' => __('Menu Order', 'rm-panel-extensions'),
                        'rand' => __('Random', 'rm-panel-extensions'),
                        'start_date' => __('Start Date', 'rm-panel-extensions'),
                        'end_date' => __('End Date', 'rm-panel-extensions'),
                    ],
                ]
        );

        $this->add_control(
                'order',
                [
                    'label' => __('Order', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'DESC',
                    'options' => [
                        'ASC' => __('Ascending', 'rm-panel-extensions'),
                        'DESC' => __('Descending', 'rm-panel-extensions'),
                    ],
                ]
        );

        $this->add_control(
                'survey_status_filter',
                [
                    'label' => __('Filter by Status', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SELECT2,
                    'multiple' => true,
                    'options' => [
                        'draft' => __('Draft', 'rm-panel-extensions'),
                        'active' => __('Active', 'rm-panel-extensions'),
                        'paused' => __('Paused', 'rm-panel-extensions'),
                        'closed' => __('Closed', 'rm-panel-extensions'),
                    ],
                    'default' => ['active'],
                ]
        );

        // Get survey categories
        $categories = get_terms([
            'taxonomy' => 'survey_category',
            'hide_empty' => false,
        ]);

        $category_options = [];
        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                $category_options[$category->term_id] = $category->name;
            }
        }

        $this->add_control(
                'categories',
                [
                    'label' => __('Filter by Categories', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SELECT2,
                    'multiple' => true,
                    'options' => $category_options,
                    'default' => [],
                ]
        );

        $this->add_control(
                'show_only_available',
                [
                    'label' => __('Show Only Available Surveys', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'no',
                    'description' => __('Show only surveys within their date range', 'rm-panel-extensions'),
                ]
        );

        $this->end_controls_section();

        // Layout Section
        $this->start_controls_section(
                'layout_section',
                [
                    'label' => __('Layout', 'rm-panel-extensions'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
        );

        $this->add_control(
                'layout_type',
                [
                    'label' => __('Layout Type', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'grid',
                    'options' => [
                        'grid' => __('Grid', 'rm-panel-extensions'),
                        'list' => __('List', 'rm-panel-extensions'),
                        'cards' => __('Cards', 'rm-panel-extensions'),
                    ],
                ]
        );

        $this->add_responsive_control(
                'columns',
                [
                    'label' => __('Columns', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => '3',
                    'tablet_default' => '2',
                    'mobile_default' => '1',
                    'options' => [
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                        '5' => '5',
                        '6' => '6',
                    ],
                    'condition' => [
                        'layout_type' => ['grid', 'cards'],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                    ],
                ]
        );

        $this->add_responsive_control(
                'gap',
                [
                    'label' => __('Gap', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'em', 'rem'],
                    'default' => [
                        'size' => 20,
                        'unit' => 'px',
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .rm-survey-grid' => 'gap: {{SIZE}}{{UNIT}};',
                        '{{WRAPPER}} .rm-survey-list .survey-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                    ],
                ]
        );

        $this->end_controls_section();

        // Content Section
        $this->start_controls_section(
                'content_section',
                [
                    'label' => __('Content', 'rm-panel-extensions'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
        );

        $this->add_control(
                'show_thumbnail',
                [
                    'label' => __('Show Thumbnail', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'show_title',
                [
                    'label' => __('Show Title', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'show_excerpt',
                [
                    'label' => __('Show Excerpt', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'excerpt_length',
                [
                    'label' => __('Excerpt Length', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::NUMBER,
                    'default' => 20,
                    'min' => 5,
                    'max' => 100,
                    'condition' => [
                        'show_excerpt' => 'yes',
                    ],
                ]
        );

        $this->add_control(
                'show_category',
                [
                    'label' => __('Show Category', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'show_status',
                [
                    'label' => __('Show Status', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'show_dates',
                [
                    'label' => __('Show Dates', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'show_questions_count',
                [
                    'label' => __('Show Questions Count', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'show_estimated_time',
                [
                    'label' => __('Show Estimated Time', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'show_button',
                [
                    'label' => __('Show Button', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'button_text',
                [
                    'label' => __('Button Text', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => __('Take Survey', 'rm-panel-extensions'),
                    'condition' => [
                        'show_button' => 'yes',
                    ],
                ]
        );

        $this->end_controls_section();

        // Pagination Section
        $this->start_controls_section(
                'pagination_section',
                [
                    'label' => __('Pagination', 'rm-panel-extensions'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
        );

        $this->add_control(
                'show_pagination',
                [
                    'label' => __('Show Pagination', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                ]
        );

        $this->add_control(
                'pagination_type',
                [
                    'label' => __('Pagination Type', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'numbers',
                    'options' => [
                        'numbers' => __('Numbers', 'rm-panel-extensions'),
                        'prev_next' => __('Previous/Next', 'rm-panel-extensions'),
                        'load_more' => __('Load More', 'rm-panel-extensions'),
                    ],
                    'condition' => [
                        'show_pagination' => 'yes',
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
        // Item Style
        $this->start_controls_section(
                'item_style',
                [
                    'label' => __('Item', 'rm-panel-extensions'),
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
                        '{{WRAPPER}} .survey-item' => 'background-color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name' => 'item_border',
                    'selector' => '{{WRAPPER}} .survey-item',
                ]
        );

        $this->add_control(
                'item_border_radius',
                [
                    'label' => __('Border Radius', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .survey-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        '{{WRAPPER}} .survey-item .survey-thumbnail img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} 0 0;',
                    ],
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Box_Shadow::get_type(),
                [
                    'name' => 'item_box_shadow',
                    'selector' => '{{WRAPPER}} .survey-item',
                ]
        );

        $this->add_responsive_control(
                'item_padding',
                [
                    'label' => __('Padding', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .survey-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
        );

        $this->end_controls_section();

        // Title Style
        $this->start_controls_section(
                'title_style',
                [
                    'label' => __('Title', 'rm-panel-extensions'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'show_title' => 'yes',
                    ],
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'title_typography',
                    'selector' => '{{WRAPPER}} .survey-title',
                ]
        );

        $this->add_control(
                'title_color',
                [
                    'label' => __('Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#333333',
                    'selectors' => [
                        '{{WRAPPER}} .survey-title a' => 'color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_control(
                'title_hover_color',
                [
                    'label' => __('Hover Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#007cba',
                    'selectors' => [
                        '{{WRAPPER}} .survey-title a:hover' => 'color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_responsive_control(
                'title_margin',
                [
                    'label' => __('Margin', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .survey-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
        );

        $this->end_controls_section();

        // Content Style
        $this->start_controls_section(
                'content_style',
                [
                    'label' => __('Content', 'rm-panel-extensions'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'content_typography',
                    'selector' => '{{WRAPPER}} .survey-excerpt',
                ]
        );

        $this->add_control(
                'content_color',
                [
                    'label' => __('Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#666666',
                    'selectors' => [
                        '{{WRAPPER}} .survey-excerpt' => 'color: {{VALUE}};',
                    ],
                ]
        );

        $this->end_controls_section();

        // Button Style
        $this->start_controls_section(
                'button_style',
                [
                    'label' => __('Button', 'rm-panel-extensions'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'show_button' => 'yes',
                    ],
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'button_typography',
                    'selector' => '{{WRAPPER}} .survey-button',
                ]
        );

        $this->start_controls_tabs('button_style_tabs');

        $this->start_controls_tab(
                'button_normal_tab',
                [
                    'label' => __('Normal', 'rm-panel-extensions'),
                ]
        );

        $this->add_control(
                'button_text_color',
                [
                    'label' => __('Text Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#ffffff',
                    'selectors' => [
                        '{{WRAPPER}} .survey-button' => 'color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_control(
                'button_background',
                [
                    'label' => __('Background Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#007cba',
                    'selectors' => [
                        '{{WRAPPER}} .survey-button' => 'background-color: {{VALUE}};',
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
                'button_hover_text_color',
                [
                    'label' => __('Text Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .survey-button:hover' => 'color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_control(
                'button_hover_background',
                [
                    'label' => __('Background Color', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#005a87',
                    'selectors' => [
                        '{{WRAPPER}} .survey-button:hover' => 'background-color: {{VALUE}};',
                    ],
                ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
                'button_border_radius',
                [
                    'label' => __('Border Radius', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'separator' => 'before',
                    'selectors' => [
                        '{{WRAPPER}} .survey-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
        );

        $this->add_responsive_control(
                'button_padding',
                [
                    'label' => __('Padding', 'rm-panel-extensions'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .survey-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
        $query_args = $this->build_query_args($settings);

        // Get surveys
        $surveys = new WP_Query($query_args);

        if (!$surveys->have_posts()) {
            echo '<p>' . __('No surveys found.', 'rm-panel-extensions') . '</p>';
            return;
        }

        // Render based on layout type
        $this->render_surveys($surveys, $settings);

        // Render pagination
        if ($settings['show_pagination'] === 'yes') {
            $this->render_pagination($surveys, $settings);
        }

        wp_reset_postdata();
    }

    /**
     * Build query arguments
     */
    private function build_query_args($settings) {
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;

        $args = [
            'post_type' => 'rm_survey',
            'posts_per_page' => $settings['posts_per_page'],
            'orderby' => $settings['orderby'],
            'order' => $settings['order'],
            'paged' => $paged,
            'post_status' => 'publish',
        ];

        // Handle custom orderby
        if (in_array($settings['orderby'], ['start_date', 'end_date'])) {
            $args['meta_key'] = '_rm_survey_' . $settings['orderby'];
            $args['orderby'] = 'meta_value';
        }

        // Initialize meta_query array with AND relation
        $meta_query = ['relation' => 'AND'];

        // Category filter
        if (!empty($settings['categories'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'survey_category',
                    'field' => 'term_id',
                    'terms' => $settings['categories'],
                ],
            ];
        }

        // Status filter
        if (!empty($settings['survey_status_filter'])) {
            $meta_query[] = [
                'key' => '_rm_survey_status',
                'value' => $settings['survey_status_filter'],
                'compare' => 'IN',
            ];
        }

        // Show only available surveys (within date range)
        if ($settings['show_only_available'] === 'yes') {
            $current_date = current_time('Y-m-d');

            // Add date range filter
            $meta_query[] = [
                'relation' => 'AND',
                // Start date condition: survey has started OR no start date set
                [
                    'relation' => 'OR',
                    [
                        'key' => '_rm_survey_start_date',
                        'value' => $current_date,
                        'compare' => '<=',
                        'type' => 'DATE',
                    ],
                    [
                        'key' => '_rm_survey_start_date',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key' => '_rm_survey_start_date',
                        'value' => '',
                        'compare' => '=',
                    ],
                ],
                // End date condition: survey hasn't ended OR no end date set
                [
                    'relation' => 'OR',
                    [
                        'key' => '_rm_survey_end_date',
                        'value' => $current_date,
                        'compare' => '>=',
                        'type' => 'DATE',
                    ],
                    [
                        'key' => '_rm_survey_end_date',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key' => '_rm_survey_end_date',
                        'value' => '',
                        'compare' => '=',
                    ],
                ],
            ];
        }

        // Only add meta_query to args if we have conditions
        if (count($meta_query) > 1) { // More than just 'relation'
            $args['meta_query'] = $meta_query;
        }

        return $args;
    }

    /**
     * Render surveys
     */
    private function render_surveys($surveys, $settings) {
        $layout_class = 'rm-survey-' . $settings['layout_type'];
        ?>
        <div class="rm-survey-listing <?php echo esc_attr($layout_class); ?>">
            <?php if ($settings['layout_type'] === 'grid' || $settings['layout_type'] === 'cards') : ?>
                <div class="rm-survey-grid">
                <?php else : ?>
                    <div class="rm-survey-list">
                    <?php endif; ?>

                    <?php while ($surveys->have_posts()) : $surveys->the_post(); ?>
                        <?php $this->render_survey_item($settings); ?>
                    <?php endwhile; ?>

                </div>
            </div>
            <?php
        }

        /**
         * Render single survey item
         */
        private function render_survey_item($settings) {
            $post_id = get_the_ID();
            $status = get_post_meta($post_id, '_rm_survey_status', true);
            $start_date = get_post_meta($post_id, '_rm_survey_start_date', true);
            $end_date = get_post_meta($post_id, '_rm_survey_end_date', true);
            $questions_count = get_post_meta($post_id, '_rm_survey_questions_count', true);
            $estimated_time = get_post_meta($post_id, '_rm_survey_estimated_time', true);
            $survey_url = get_post_meta($post_id, '_rm_survey_url', true);
            $parameters = get_post_meta($post_id, '_rm_survey_parameters', true);

            // Build survey URL with parameters
            $final_survey_url = $survey_url;

            if (!empty($survey_url)) {
                // Ensure parameters is an array
                if (!is_array($parameters)) {
                    $parameters = [];
                }

                // If parameters array is empty or doesn't have required defaults, add them
                $has_survey_id = false;
                $has_user_id = false;

                foreach ($parameters as $param) {
                    if (isset($param['field'])) {
                        if ($param['field'] === 'survey_id') {
                            $has_survey_id = true;
                        }
                        if ($param['field'] === 'user_id') {
                            $has_user_id = true;
                        }
                    }
                }

                // Add defaults if missing
                if (!$has_survey_id) {
                    array_unshift($parameters, [
                        'field' => 'survey_id',
                        'variable' => 'sid',
                        'custom_value' => ''
                    ]);
                }

                if (!$has_user_id) {
                    // Insert after survey_id
                    array_splice($parameters, 1, 0, [[
                    'field' => 'user_id',
                    'variable' => 'uid',
                    'custom_value' => ''
                    ]]);
                }

                // Now process all parameters
                $query_params = [];

                foreach ($parameters as $param) {
                    if (empty($param['variable'])) {
                        continue;
                    }

                    $value = '';

                    switch ($param['field']) {
                        case 'survey_id':
                            // IMPORTANT: Always use the current post ID for survey_id
                            $value = $post_id;
                            break;
                        case 'timestamp':
                            $value = time();
                            break;
                        case 'custom':
                            $value = isset($param['custom_value']) ? $param['custom_value'] : '';
                            break;
                        // User-specific parameters
                        case 'user_id':
                            if (is_user_logged_in()) {
                                $value = get_current_user_id();
                            }
                            break;
                        case 'username':
                            if (is_user_logged_in()) {
                                $current_user = wp_get_current_user();
                                $value = $current_user->user_login;
                            }
                            break;
                        case 'email':
                            if (is_user_logged_in()) {
                                $current_user = wp_get_current_user();
                                $value = $current_user->user_email;
                            }
                            break;
                        case 'first_name':
                            if (is_user_logged_in()) {
                                $current_user = wp_get_current_user();
                                $value = $current_user->first_name;
                            }
                            break;
                        case 'last_name':
                            if (is_user_logged_in()) {
                                $current_user = wp_get_current_user();
                                $value = $current_user->last_name;
                            }
                            break;
                        case 'display_name':
                            if (is_user_logged_in()) {
                                $current_user = wp_get_current_user();
                                $value = $current_user->display_name;
                            }
                            break;
                        case 'user_role':
                            if (is_user_logged_in()) {
                                $current_user = wp_get_current_user();
                                $value = implode(',', $current_user->roles);
                            }
                            break;
                    }

                    // Only add parameter if we have both variable name and value
                    if (!empty($value) && !empty($param['variable'])) {
                        $query_params[$param['variable']] = $value;
                    }
                }

                // Add parameters to URL
                if (!empty($query_params)) {
                    $final_survey_url = add_query_arg($query_params, $survey_url);
                }
            }

            $item_classes = [
                'survey-item',
                'survey-status-' . ( $status ?: 'draft' ),
            ];
            ?>
            <article class="<?php echo esc_attr(implode(' ', $item_classes)); ?>" data-survey-id="<?php echo esc_attr($post_id); ?>">
                <!-- ... rest of the HTML ... -->

                <?php if ($settings['show_button'] === 'yes') : ?>
                    <?php if (!empty($final_survey_url)) : ?>
                        <a href="<?php echo esc_url($final_survey_url); ?>" 
                           class="survey-button" 
                           target="_blank"
                           data-survey-id="<?php echo esc_attr($post_id); ?>">
                               <?php echo esc_html($settings['button_text']); ?>
                            <i class="fas fa-external-link-alt" style="margin-left: 5px; font-size: 12px;"></i>
                        </a>
                    <?php elseif (!empty($survey_url)) : ?>
                        <!-- Fallback to survey URL without parameters if something went wrong -->
                        <a href="<?php echo esc_url($survey_url); ?>" 
                           class="survey-button" 
                           target="_blank"
                           data-survey-id="<?php echo esc_attr($post_id); ?>">
                               <?php echo esc_html($settings['button_text']); ?>
                            <i class="fas fa-external-link-alt" style="margin-left: 5px; font-size: 12px;"></i>
                        </a>
                    <?php else : ?>
                        <!-- No survey URL defined, use WordPress post URL -->
                        <a href="<?php the_permalink(); ?>" 
                           class="survey-button"
                           data-survey-id="<?php echo esc_attr($post_id); ?>">
                               <?php echo esc_html($settings['button_text']); ?>
                        </a>
                        <?php if (current_user_can('edit_posts')) : ?>
                            <small style="display: block; color: #d63638; margin-top: 5px;">
                                <?php _e('No survey URL configured', 'rm-panel-extensions'); ?>
                            </small>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <?php
                // Debug output for admins (remove in production)
                if (current_user_can('manage_options') && defined('WP_DEBUG') && WP_DEBUG) :
                    ?>
                    <div style="margin-top: 10px; padding: 10px; background: #f0f0f0; border: 1px solid #ccc; font-size: 11px;">
                        <strong>Debug Info (Listing):</strong><br>
                        Survey ID: <?php echo $post_id; ?><br>
                        Base URL: <?php echo esc_html($survey_url); ?><br>
                        Parameters Count: <?php echo count($parameters); ?><br>
                        Parameters: <pre><?php print_r($parameters); ?></pre>
                        Query Params: <pre><?php print_r($query_params ?? []); ?></pre>
                        Final URL: <?php echo esc_html($final_survey_url); ?>
                    </div>
                <?php endif; ?>
            </article>
            <?php
        }

        /**
         * Render pagination
         */
        private function render_pagination($surveys, $settings) {
            if ($settings['pagination_type'] === 'numbers') {
                $big = 999999999;
                echo paginate_links([
                    'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                    'format' => '?paged=%#%',
                    'current' => max(1, get_query_var('paged')),
                    'total' => $surveys->max_num_pages,
                    'prev_text' => __('&laquo; Previous', 'rm-panel-extensions'),
                    'next_text' => __('Next &raquo;', 'rm-panel-extensions'),
                ]);
            } elseif ($settings['pagination_type'] === 'prev_next') {
                ?>
                <div class="survey-pagination prev-next">
                    <?php previous_posts_link(__('&laquo; Previous', 'rm-panel-extensions')); ?>
                    <?php next_posts_link(__('Next &raquo;', 'rm-panel-extensions'), $surveys->max_num_pages); ?>
                </div>
                <?php
            } elseif ($settings['pagination_type'] === 'load_more') {
                ?>
                <div class="survey-load-more">
                    <button class="load-more-button" data-page="1" data-max="<?php echo $surveys->max_num_pages; ?>">
                        <?php _e('Load More', 'rm-panel-extensions'); ?>
                    </button>
                </div>
                <?php
            }
        }
    }
    