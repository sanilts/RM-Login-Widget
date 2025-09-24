<?php
/**
 * RM Panel Extensions - Enhanced Survey Module
 * 
 * @package RM_Panel_Extensions
 * @subpackage Modules/Survey
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Survey Module Class
 */
class RM_Panel_Survey_Module {

    /**
     * Post type name
     */
    const POST_TYPE = 'rm_survey';

    /**
     * Taxonomy name
     */
    const TAXONOMY = 'survey_category';
    
    /**
     * User Category Taxonomy
     */
    const USER_CATEGORY_TAXONOMY = 'survey_user_category';

    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize the module
     */
    private function init() {
        // Register post type
        add_action( 'init', [ $this, 'register_post_type' ] );
        
        // Register taxonomies
        add_action( 'init', [ $this, 'register_taxonomies' ] );
        
        // Add meta boxes
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        
        // Save meta data
        add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_meta_data' ], 10, 3 );
        
        // Admin columns
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'add_admin_columns' ] );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'render_admin_columns' ], 10, 2 );
        add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', [ $this, 'make_columns_sortable' ] );
        
        // Add custom fields support for Elementor
        add_action( 'elementor/dynamic_tags/register', [ $this, 'register_dynamic_tags' ] );
        
        // Handle survey URL redirect with parameters
        add_filter( 'post_type_link', [ $this, 'modify_survey_permalink' ], 10, 2 );
        add_action( 'template_redirect', [ $this, 'handle_survey_redirect' ] );
        
        // Enqueue admin scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        
        // Flush rewrite rules on activation
        register_activation_hook( RM_PANEL_EXT_FILE, [ $this, 'flush_rewrite_rules' ] );
    }

    /**
     * Register Survey Post Type
     */
    public function register_post_type() {
        $labels = [
            'name'                  => _x( 'Surveys', 'Post type general name', 'rm-panel-extensions' ),
            'singular_name'         => _x( 'Survey', 'Post type singular name', 'rm-panel-extensions' ),
            'menu_name'             => _x( 'Surveys', 'Admin Menu text', 'rm-panel-extensions' ),
            'name_admin_bar'        => _x( 'Survey', 'Add New on Toolbar', 'rm-panel-extensions' ),
            'add_new'               => __( 'Add New', 'rm-panel-extensions' ),
            'add_new_item'          => __( 'Add New Survey', 'rm-panel-extensions' ),
            'new_item'              => __( 'New Survey', 'rm-panel-extensions' ),
            'edit_item'             => __( 'Edit Survey', 'rm-panel-extensions' ),
            'view_item'             => __( 'View Survey', 'rm-panel-extensions' ),
            'all_items'             => __( 'All Surveys', 'rm-panel-extensions' ),
            'search_items'          => __( 'Search Surveys', 'rm-panel-extensions' ),
            'parent_item_colon'     => __( 'Parent Surveys:', 'rm-panel-extensions' ),
            'not_found'             => __( 'No surveys found.', 'rm-panel-extensions' ),
            'not_found_in_trash'    => __( 'No surveys found in Trash.', 'rm-panel-extensions' ),
            'featured_image'        => _x( 'Survey Cover Image', 'Overrides the "Featured Image" phrase', 'rm-panel-extensions' ),
            'set_featured_image'    => _x( 'Set cover image', 'Overrides the "Set featured image" phrase', 'rm-panel-extensions' ),
            'remove_featured_image' => _x( 'Remove cover image', 'Overrides the "Remove featured image" phrase', 'rm-panel-extensions' ),
            'use_featured_image'    => _x( 'Use as cover image', 'Overrides the "Use as featured image" phrase', 'rm-panel-extensions' ),
            'archives'              => _x( 'Survey archives', 'The post type archive label', 'rm-panel-extensions' ),
            'insert_into_item'      => _x( 'Insert into survey', 'Overrides the "Insert into post" phrase', 'rm-panel-extensions' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this survey', 'Overrides the "Uploaded to this post" phrase', 'rm-panel-extensions' ),
            'filter_items_list'     => _x( 'Filter surveys list', 'Screen reader text', 'rm-panel-extensions' ),
            'items_list_navigation' => _x( 'Surveys list navigation', 'Screen reader text', 'rm-panel-extensions' ),
            'items_list'            => _x( 'Surveys list', 'Screen reader text', 'rm-panel-extensions' ),
        ];

        $args = [
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'query_var'             => true,
            'rewrite'               => [ 'slug' => 'survey', 'with_front' => false ],
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => false,
            'menu_position'         => 25,
            'menu_icon'             => 'dashicons-clipboard',
            'supports'              => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ],
            'show_in_rest'          => true,
            'rest_base'             => 'surveys',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        ];

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Register Taxonomies
     */
    public function register_taxonomies() {
        // Register Survey Category Taxonomy
        $labels = [
            'name'                       => _x( 'Survey Categories', 'taxonomy general name', 'rm-panel-extensions' ),
            'singular_name'              => _x( 'Survey Category', 'taxonomy singular name', 'rm-panel-extensions' ),
            'search_items'               => __( 'Search Categories', 'rm-panel-extensions' ),
            'popular_items'              => __( 'Popular Categories', 'rm-panel-extensions' ),
            'all_items'                  => __( 'All Categories', 'rm-panel-extensions' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Category', 'rm-panel-extensions' ),
            'update_item'                => __( 'Update Category', 'rm-panel-extensions' ),
            'add_new_item'               => __( 'Add New Category', 'rm-panel-extensions' ),
            'new_item_name'              => __( 'New Category Name', 'rm-panel-extensions' ),
            'separate_items_with_commas' => __( 'Separate categories with commas', 'rm-panel-extensions' ),
            'add_or_remove_items'        => __( 'Add or remove categories', 'rm-panel-extensions' ),
            'choose_from_most_used'      => __( 'Choose from the most used categories', 'rm-panel-extensions' ),
            'not_found'                  => __( 'No categories found.', 'rm-panel-extensions' ),
            'menu_name'                  => __( 'Categories', 'rm-panel-extensions' ),
        ];

        $args = [
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'show_in_rest'          => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => [ 'slug' => 'survey-category' ],
        ];

        register_taxonomy( self::TAXONOMY, [ self::POST_TYPE ], $args );
        
        // Register User Category Taxonomy
        $user_cat_labels = [
            'name'                       => _x( 'User Categories', 'taxonomy general name', 'rm-panel-extensions' ),
            'singular_name'              => _x( 'User Category', 'taxonomy singular name', 'rm-panel-extensions' ),
            'search_items'               => __( 'Search User Categories', 'rm-panel-extensions' ),
            'all_items'                  => __( 'All User Categories', 'rm-panel-extensions' ),
            'edit_item'                  => __( 'Edit User Category', 'rm-panel-extensions' ),
            'update_item'                => __( 'Update User Category', 'rm-panel-extensions' ),
            'add_new_item'               => __( 'Add New User Category', 'rm-panel-extensions' ),
            'new_item_name'              => __( 'New User Category Name', 'rm-panel-extensions' ),
            'menu_name'                  => __( 'User Categories', 'rm-panel-extensions' ),
        ];

        $user_cat_args = [
            'hierarchical'          => true,
            'labels'                => $user_cat_labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'show_in_rest'          => true,
            'query_var'             => true,
            'rewrite'               => [ 'slug' => 'survey-user-category' ],
        ];

        register_taxonomy( self::USER_CATEGORY_TAXONOMY, [ self::POST_TYPE ], $user_cat_args );
        
        // Add default user categories if they don't exist
        $this->create_default_user_categories();
    }

    /**
     * Create default user categories
     */
    private function create_default_user_categories() {
        $default_categories = [
            'General Population' => __( 'General Population', 'rm-panel-extensions' ),
            'B2B Professionals' => __( 'B2B Professionals', 'rm-panel-extensions' ),
            'Engineers' => __( 'Engineers', 'rm-panel-extensions' ),
            'Doctors' => __( 'Doctors', 'rm-panel-extensions' ),
            'Teachers' => __( 'Teachers', 'rm-panel-extensions' ),
            'Students' => __( 'Students', 'rm-panel-extensions' ),
            'IT Professionals' => __( 'IT Professionals', 'rm-panel-extensions' ),
            'Healthcare Workers' => __( 'Healthcare Workers', 'rm-panel-extensions' ),
            'Business Owners' => __( 'Business Owners', 'rm-panel-extensions' ),
            'Marketing Professionals' => __( 'Marketing Professionals', 'rm-panel-extensions' ),
        ];
        
        foreach ( $default_categories as $slug => $name ) {
            if ( ! term_exists( $slug, self::USER_CATEGORY_TAXONOMY ) ) {
                wp_insert_term( $name, self::USER_CATEGORY_TAXONOMY, [
                    'slug' => sanitize_title( $slug )
                ] );
            }
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts( $hook ) {
        global $post_type;
        
        if ( ( $hook == 'post.php' || $hook == 'post-new.php' ) && $post_type == self::POST_TYPE ) {
            // Enqueue jQuery
            wp_enqueue_script( 'jquery' );
            
            // Check if the JavaScript file exists, if not use inline script
            $js_file_path = RM_PANEL_EXT_PLUGIN_DIR . 'assets/js/survey-admin.js';
            
            if ( file_exists( $js_file_path ) ) {
                // Enqueue the external JavaScript file
                wp_enqueue_script(
                    'rm-survey-admin',
                    RM_PANEL_EXT_PLUGIN_URL . 'assets/js/survey-admin.js',
                    [ 'jquery' ],
                    RM_PANEL_EXT_VERSION,
                    true
                );
            } else {
                // Use inline script as fallback
                add_action( 'admin_footer', [ $this, 'output_inline_admin_script' ] );
            }
            
            // Add custom styles
            add_action( 'admin_head', [ $this, 'output_admin_styles' ] );
        }
    }
    
    /**
     * Output inline admin script (fallback)
     */
    public function output_inline_admin_script() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Toggle payment amount field
            function togglePaymentAmount() {
                var surveyType = $('#rm_survey_type').val();
                if (surveyType === 'paid') {
                    $('#survey_amount_field').slideDown();
                } else {
                    $('#survey_amount_field').slideUp();
                }
            }
            
            // Toggle duration fields
            function toggleDurationFields() {
                var durationType = $('#rm_survey_duration_type').val();
                if (durationType === 'date_range') {
                    $('.survey-date-fields').slideDown();
                } else {
                    $('.survey-date-fields').slideUp();
                }
            }
            
            // Initialize on page load
            togglePaymentAmount();
            toggleDurationFields();
            
            // Bind change events
            $('#rm_survey_type').on('change', togglePaymentAmount);
            $('#rm_survey_duration_type').on('change', toggleDurationFields);
            
            // Calculate next parameter index
            function getNextParameterIndex() {
                var maxIndex = -1;
                $('#survey-parameters-table tbody tr').each(function() {
                    var nameAttr = $(this).find('select').attr('name');
                    if (nameAttr) {
                        var matches = nameAttr.match(/\[(\d+)\]/);
                        if (matches) {
                            var index = parseInt(matches[1]);
                            if (index > maxIndex) {
                                maxIndex = index;
                            }
                        }
                    }
                });
                return maxIndex + 1;
            }
            
            // Add parameter row
            $('#add_survey_parameter').on('click', function(e) {
                e.preventDefault();
                
                var parameterIndex = getNextParameterIndex();
                
                var html = '<tr class="survey-parameter-row">' +
                    '<td>' +
                        '<select name="rm_survey_parameters[' + parameterIndex + '][field]">' +
                            '<option value="user_id">User ID</option>' +
                            '<option value="username">Username</option>' +
                            '<option value="email">Email</option>' +
                            '<option value="first_name">First Name</option>' +
                            '<option value="last_name">Last Name</option>' +
                            '<option value="display_name">Display Name</option>' +
                            '<option value="user_role">User Role</option>' +
                            '<option value="custom">Custom Field</option>' +
                        '</select>' +
                    '</td>' +
                    '<td>' +
                        '<input type="text" name="rm_survey_parameters[' + parameterIndex + '][variable]" placeholder="e.g., uid" />' +
                    '</td>' +
                    '<td>' +
                        '<input type="text" name="rm_survey_parameters[' + parameterIndex + '][custom_value]" placeholder="For custom field only" />' +
                    '</td>' +
                    '<td>' +
                        '<button type="button" class="button remove-parameter">Remove</button>' +
                    '</td>' +
                '</tr>';
                
                $('#survey-parameters-table tbody').append(html);
            });
            
            // Remove parameter row
            $(document).on('click', '.remove-parameter', function(e) {
                e.preventDefault();
                $(this).closest('tr').remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Output admin styles
     */
    public function output_admin_styles() {
        ?>
        <style>
            .survey-meta-field {
                margin-bottom: 20px;
            }
            .survey-meta-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
            }
            .survey-meta-field input[type="text"],
            .survey-meta-field input[type="number"],
            .survey-meta-field input[type="url"],
            .survey-meta-field input[type="date"],
            .survey-meta-field select,
            .survey-meta-field textarea {
                width: 100%;
                max-width: 500px;
            }
            .survey-meta-field .description {
                color: #666;
                font-size: 13px;
                margin-top: 5px;
            }
            #survey_amount_field {
                display: none;
            }
            .survey-date-fields {
                display: none;
            }
            #survey-parameters-table {
                margin-top: 10px;
            }
            #survey-parameters-table td {
                padding: 8px;
            }
            #survey-parameters-table input[type="text"] {
                width: 100%;
            }
        </style>
        <?php
    }

    /**
     * Add Meta Boxes
     */
    public function add_meta_boxes() {
        // Survey Type and Payment
        add_meta_box(
            'rm_survey_type_payment',
            __( 'Survey Type & Payment', 'rm-panel-extensions' ),
            [ $this, 'render_survey_type_payment_metabox' ],
            self::POST_TYPE,
            'normal',
            'high'
        );
        
        // Survey URL and Parameters
        add_meta_box(
            'rm_survey_url_parameters',
            __( 'Survey URL & Parameters', 'rm-panel-extensions' ),
            [ $this, 'render_survey_url_parameters_metabox' ],
            self::POST_TYPE,
            'normal',
            'high'
        );
        
        // Survey Duration
        add_meta_box(
            'rm_survey_duration',
            __( 'Survey Duration', 'rm-panel-extensions' ),
            [ $this, 'render_survey_duration_metabox' ],
            self::POST_TYPE,
            'normal',
            'high'
        );

        // Original Survey Details
        add_meta_box(
            'rm_survey_details',
            __( 'Survey Details', 'rm-panel-extensions' ),
            [ $this, 'render_survey_details_metabox' ],
            self::POST_TYPE,
            'normal',
            'high'
        );

        // Survey Settings
        add_meta_box(
            'rm_survey_settings',
            __( 'Survey Settings', 'rm-panel-extensions' ),
            [ $this, 'render_survey_settings_metabox' ],
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    /**
     * Render Survey Type & Payment Metabox
     */
    public function render_survey_type_payment_metabox( $post ) {
        wp_nonce_field( 'rm_survey_meta_box', 'rm_survey_meta_box_nonce' );
        
        $survey_type = get_post_meta( $post->ID, '_rm_survey_type', true );
        $survey_amount = get_post_meta( $post->ID, '_rm_survey_amount', true );
        ?>
        <style>
            .survey-meta-field {
                margin-bottom: 20px;
            }
            .survey-meta-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
            }
            .survey-meta-field input[type="text"],
            .survey-meta-field input[type="number"],
            .survey-meta-field input[type="url"],
            .survey-meta-field input[type="date"],
            .survey-meta-field select,
            .survey-meta-field textarea {
                width: 100%;
                max-width: 500px;
            }
            .survey-meta-field .description {
                color: #666;
                font-size: 13px;
                margin-top: 5px;
            }
            #survey_amount_field {
                display: none;
            }
        </style>
        
        <div class="survey-meta-field">
            <label for="rm_survey_type"><?php _e( 'Survey Type', 'rm-panel-extensions' ); ?></label>
            <select id="rm_survey_type" name="rm_survey_type">
                <option value="not_paid" <?php selected( $survey_type, 'not_paid' ); ?>><?php _e( 'Not Paid', 'rm-panel-extensions' ); ?></option>
                <option value="paid" <?php selected( $survey_type, 'paid' ); ?>><?php _e( 'Paid', 'rm-panel-extensions' ); ?></option>
            </select>
            <p class="description"><?php _e( 'Choose whether this survey offers compensation', 'rm-panel-extensions' ); ?></p>
        </div>
        
        <div class="survey-meta-field" id="survey_amount_field">
            <label for="rm_survey_amount"><?php _e( 'Survey Amount', 'rm-panel-extensions' ); ?></label>
            <input type="number" id="rm_survey_amount" name="rm_survey_amount" value="<?php echo esc_attr( $survey_amount ); ?>" min="0" step="0.01">
            <p class="description"><?php _e( 'Enter the payment amount for completing this survey', 'rm-panel-extensions' ); ?></p>
        </div>
        <?php
    }

    /**
     * Render Survey URL & Parameters Metabox
     */
    public function render_survey_url_parameters_metabox( $post ) {
        $survey_url = get_post_meta( $post->ID, '_rm_survey_url', true );
        $survey_parameters = get_post_meta( $post->ID, '_rm_survey_parameters', true );
        if ( ! is_array( $survey_parameters ) ) {
            $survey_parameters = [];
        }
        ?>
        <div class="survey-meta-field">
            <label for="rm_survey_url"><?php _e( 'Survey URL', 'rm-panel-extensions' ); ?></label>
            <input type="url" id="rm_survey_url" name="rm_survey_url" value="<?php echo esc_url( $survey_url ); ?>" placeholder="https://example.com/survey">
            <p class="description"><?php _e( 'Enter the external survey URL', 'rm-panel-extensions' ); ?></p>
        </div>
        
        <div class="survey-meta-field">
            <label><?php _e( 'URL Parameters', 'rm-panel-extensions' ); ?></label>
            <p class="description"><?php _e( 'Configure which user data to pass as URL parameters', 'rm-panel-extensions' ); ?></p>
            
            <table id="survey-parameters-table" class="widefat" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th><?php _e( 'User Field', 'rm-panel-extensions' ); ?></th>
                        <th><?php _e( 'URL Variable Name', 'rm-panel-extensions' ); ?></th>
                        <th><?php _e( 'Custom Value', 'rm-panel-extensions' ); ?></th>
                        <th><?php _e( 'Action', 'rm-panel-extensions' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ( ! empty( $survey_parameters ) ) {
                        foreach ( $survey_parameters as $index => $param ) {
                            ?>
                            <tr class="survey-parameter-row">
                                <td>
                                    <select name="rm_survey_parameters[<?php echo $index; ?>][field]">
                                        <option value="user_id" <?php selected( $param['field'], 'user_id' ); ?>><?php _e( 'User ID', 'rm-panel-extensions' ); ?></option>
                                        <option value="username" <?php selected( $param['field'], 'username' ); ?>><?php _e( 'Username', 'rm-panel-extensions' ); ?></option>
                                        <option value="email" <?php selected( $param['field'], 'email' ); ?>><?php _e( 'Email', 'rm-panel-extensions' ); ?></option>
                                        <option value="first_name" <?php selected( $param['field'], 'first_name' ); ?>><?php _e( 'First Name', 'rm-panel-extensions' ); ?></option>
                                        <option value="last_name" <?php selected( $param['field'], 'last_name' ); ?>><?php _e( 'Last Name', 'rm-panel-extensions' ); ?></option>
                                        <option value="display_name" <?php selected( $param['field'], 'display_name' ); ?>><?php _e( 'Display Name', 'rm-panel-extensions' ); ?></option>
                                        <option value="user_role" <?php selected( $param['field'], 'user_role' ); ?>><?php _e( 'User Role', 'rm-panel-extensions' ); ?></option>
                                        <option value="custom" <?php selected( $param['field'], 'custom' ); ?>><?php _e( 'Custom Field', 'rm-panel-extensions' ); ?></option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="rm_survey_parameters[<?php echo $index; ?>][variable]" value="<?php echo esc_attr( $param['variable'] ); ?>" placeholder="e.g., uid">
                                </td>
                                <td>
                                    <input type="text" name="rm_survey_parameters[<?php echo $index; ?>][custom_value]" value="<?php echo esc_attr( isset($param['custom_value']) ? $param['custom_value'] : '' ); ?>" placeholder="For custom field only">
                                </td>
                                <td>
                                    <button type="button" class="button remove-parameter"><?php _e( 'Remove', 'rm-panel-extensions' ); ?></button>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
            
            <p style="margin-top: 10px;">
                <button type="button" id="add_survey_parameter" class="button"><?php _e( 'Add Parameter', 'rm-panel-extensions' ); ?></button>
            </p>
            
            <p class="description" style="margin-top: 10px;">
                <?php _e( 'Example: If you set "User ID" field with variable "uid", the URL will become: https://example.com/survey?uid=123', 'rm-panel-extensions' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render Survey Duration Metabox
     */
    public function render_survey_duration_metabox( $post ) {
        $duration_type = get_post_meta( $post->ID, '_rm_survey_duration_type', true );
        $start_date = get_post_meta( $post->ID, '_rm_survey_start_date', true );
        $end_date = get_post_meta( $post->ID, '_rm_survey_end_date', true );
        ?>
        <div class="survey-meta-field">
            <label for="rm_survey_duration_type"><?php _e( 'Duration Type', 'rm-panel-extensions' ); ?></label>
            <select id="rm_survey_duration_type" name="rm_survey_duration_type">
                <option value="never_ending" <?php selected( $duration_type, 'never_ending' ); ?>><?php _e( 'Never Ending', 'rm-panel-extensions' ); ?></option>
                <option value="date_range" <?php selected( $duration_type, 'date_range' ); ?>><?php _e( 'Date Range', 'rm-panel-extensions' ); ?></option>
            </select>
            <p class="description"><?php _e( 'Choose whether this survey has a time limit', 'rm-panel-extensions' ); ?></p>
        </div>
        
        <div class="survey-date-fields" style="display: none;">
            <div class="survey-meta-field">
                <label for="rm_survey_start_date"><?php _e( 'Start Date', 'rm-panel-extensions' ); ?></label>
                <input type="date" id="rm_survey_start_date" name="rm_survey_start_date" value="<?php echo esc_attr( $start_date ); ?>">
                <p class="description"><?php _e( 'When should this survey become available?', 'rm-panel-extensions' ); ?></p>
            </div>

            <div class="survey-meta-field">
                <label for="rm_survey_end_date"><?php _e( 'End Date', 'rm-panel-extensions' ); ?></label>
                <input type="date" id="rm_survey_end_date" name="rm_survey_end_date" value="<?php echo esc_attr( $end_date ); ?>">
                <p class="description"><?php _e( 'When should this survey close?', 'rm-panel-extensions' ); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render Survey Details Metabox
     */
    public function render_survey_details_metabox( $post ) {
        $questions_count = get_post_meta( $post->ID, '_rm_survey_questions_count', true );
        $estimated_time = get_post_meta( $post->ID, '_rm_survey_estimated_time', true );
        $target_audience = get_post_meta( $post->ID, '_rm_survey_target_audience', true );
        ?>
        <div class="survey-meta-field">
            <label for="rm_survey_questions_count"><?php _e( 'Number of Questions', 'rm-panel-extensions' ); ?></label>
            <input type="number" id="rm_survey_questions_count" name="rm_survey_questions_count" value="<?php echo esc_attr( $questions_count ); ?>" min="1">
            <p class="description"><?php _e( 'Total number of questions in this survey', 'rm-panel-extensions' ); ?></p>
        </div>

        <div class="survey-meta-field">
            <label for="rm_survey_estimated_time"><?php _e( 'Estimated Completion Time (minutes)', 'rm-panel-extensions' ); ?></label>
            <input type="number" id="rm_survey_estimated_time" name="rm_survey_estimated_time" value="<?php echo esc_attr( $estimated_time ); ?>" min="1">
            <p class="description"><?php _e( 'How long should it take to complete this survey?', 'rm-panel-extensions' ); ?></p>
        </div>

        <div class="survey-meta-field">
            <label for="rm_survey_target_audience"><?php _e( 'Target Audience Description', 'rm-panel-extensions' ); ?></label>
            <textarea id="rm_survey_target_audience" name="rm_survey_target_audience" rows="3"><?php echo esc_textarea( $target_audience ); ?></textarea>
            <p class="description"><?php _e( 'Additional description about who this survey is intended for', 'rm-panel-extensions' ); ?></p>
        </div>
        <?php
    }

    /**
     * Render Survey Settings Metabox
     */
    public function render_survey_settings_metabox( $post ) {
        $status = get_post_meta( $post->ID, '_rm_survey_status', true );
        $requires_login = get_post_meta( $post->ID, '_rm_survey_requires_login', true );
        $allow_multiple = get_post_meta( $post->ID, '_rm_survey_allow_multiple', true );
        $anonymous = get_post_meta( $post->ID, '_rm_survey_anonymous', true );
        ?>
        <div class="survey-meta-field">
            <label for="rm_survey_status"><?php _e( 'Survey Status', 'rm-panel-extensions' ); ?></label>
            <select id="rm_survey_status" name="rm_survey_status">
                <option value="draft" <?php selected( $status, 'draft' ); ?>><?php _e( 'Draft', 'rm-panel-extensions' ); ?></option>
                <option value="active" <?php selected( $status, 'active' ); ?>><?php _e( 'Active', 'rm-panel-extensions' ); ?></option>
                <option value="paused" <?php selected( $status, 'paused' ); ?>><?php _e( 'Paused', 'rm-panel-extensions' ); ?></option>
                <option value="closed" <?php selected( $status, 'closed' ); ?>><?php _e( 'Closed', 'rm-panel-extensions' ); ?></option>
            </select>
        </div>

        <div class="survey-meta-field">
            <label>
                <input type="checkbox" name="rm_survey_requires_login" value="1" <?php checked( $requires_login, '1' ); ?>>
                <?php _e( 'Require Login', 'rm-panel-extensions' ); ?>
            </label>
            <p class="description"><?php _e( 'Users must be logged in to participate', 'rm-panel-extensions' ); ?></p>
        </div>

        <div class="survey-meta-field">
            <label>
                <input type="checkbox" name="rm_survey_allow_multiple" value="1" <?php checked( $allow_multiple, '1' ); ?>>
                <?php _e( 'Allow Multiple Submissions', 'rm-panel-extensions' ); ?>
            </label>
            <p class="description"><?php _e( 'Allow users to submit multiple responses', 'rm-panel-extensions' ); ?></p>
        </div>

        <div class="survey-meta-field">
            <label>
                <input type="checkbox" name="rm_survey_anonymous" value="1" <?php checked( $anonymous, '1' ); ?>>
                <?php _e( 'Anonymous Responses', 'rm-panel-extensions' ); ?>
            </label>
            <p class="description"><?php _e( 'Do not track user information', 'rm-panel-extensions' ); ?></p>
        </div>
        <?php
    }

    /**
     * Save Meta Data
     */
    public function save_meta_data( $post_id, $post, $update ) {
        // Check if our nonce is set
        if ( ! isset( $_POST['rm_survey_meta_box_nonce'] ) ) {
            return;
        }

        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['rm_survey_meta_box_nonce'], 'rm_survey_meta_box' ) ) {
            return;
        }

        // Check if user has permission
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Check if not an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Save all fields
        $fields = [
            'rm_survey_type' => '_rm_survey_type',
            'rm_survey_amount' => '_rm_survey_amount',
            'rm_survey_url' => '_rm_survey_url',
            'rm_survey_duration_type' => '_rm_survey_duration_type',
            'rm_survey_start_date' => '_rm_survey_start_date',
            'rm_survey_end_date' => '_rm_survey_end_date',
            'rm_survey_questions_count' => '_rm_survey_questions_count',
            'rm_survey_estimated_time' => '_rm_survey_estimated_time',
            'rm_survey_target_audience' => '_rm_survey_target_audience',
            'rm_survey_status' => '_rm_survey_status',
        ];

        foreach ( $fields as $field_name => $meta_key ) {
            if ( isset( $_POST[$field_name] ) ) {
                if ( $field_name === 'rm_survey_url' ) {
                    update_post_meta( $post_id, $meta_key, esc_url_raw( $_POST[$field_name] ) );
                } else {
                    update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[$field_name] ) );
                }
            }
        }

        // Save parameters array
        if ( isset( $_POST['rm_survey_parameters'] ) && is_array( $_POST['rm_survey_parameters'] ) ) {
            $parameters = [];
            foreach ( $_POST['rm_survey_parameters'] as $param ) {
                if ( ! empty( $param['field'] ) && ! empty( $param['variable'] ) ) {
                    $parameters[] = [
                        'field' => sanitize_text_field( $param['field'] ),
                        'variable' => sanitize_text_field( $param['variable'] ),
                        'custom_value' => sanitize_text_field( isset($param['custom_value']) ? $param['custom_value'] : '' ),
                    ];
                }
            }
            update_post_meta( $post_id, '_rm_survey_parameters', $parameters );
        }

        // Save checkboxes
        $checkboxes = [
            'rm_survey_requires_login' => '_rm_survey_requires_login',
            'rm_survey_allow_multiple' => '_rm_survey_allow_multiple',
            'rm_survey_anonymous' => '_rm_survey_anonymous',
        ];

        foreach ( $checkboxes as $field_name => $meta_key ) {
            $value = isset( $_POST[$field_name] ) ? '1' : '0';
            update_post_meta( $post_id, $meta_key, $value );
        }
    }

    /**
     * Add Admin Columns
     */
    public function add_admin_columns( $columns ) {
        $new_columns = [];
        
        foreach ( $columns as $key => $value ) {
            $new_columns[$key] = $value;
            
            if ( $key === 'title' ) {
                $new_columns['survey_type'] = __( 'Type', 'rm-panel-extensions' );
                $new_columns['survey_status'] = __( 'Status', 'rm-panel-extensions' );
                $new_columns['duration'] = __( 'Duration', 'rm-panel-extensions' );
                $new_columns['amount'] = __( 'Amount', 'rm-panel-extensions' );
            }
        }
        
        return $new_columns;
    }

    /**
     * Render Admin Columns
     */
    public function render_admin_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'survey_type':
                $type = get_post_meta( $post_id, '_rm_survey_type', true );
                $type_label = ( $type === 'paid' ) ? __( 'Paid', 'rm-panel-extensions' ) : __( 'Not Paid', 'rm-panel-extensions' );
                $type_class = ( $type === 'paid' ) ? 'paid' : 'not-paid';
                echo '<span class="survey-type-' . esc_attr( $type_class ) . '">' . esc_html( $type_label ) . '</span>';
                break;
                
            case 'survey_status':
                $status = get_post_meta( $post_id, '_rm_survey_status', true );
                $status_label = $status ? ucfirst( $status ) : 'Draft';
                $status_class = 'status-' . ( $status ?: 'draft' );
                echo '<span class="' . esc_attr( $status_class ) . '" style="padding: 3px 8px; border-radius: 3px; background: #f0f0f0;">' . esc_html( $status_label ) . '</span>';
                break;
                
            case 'duration':
                $duration_type = get_post_meta( $post_id, '_rm_survey_duration_type', true );
                if ( $duration_type === 'never_ending' ) {
                    echo __( 'Never Ending', 'rm-panel-extensions' );
                } else {
                    $start = get_post_meta( $post_id, '_rm_survey_start_date', true );
                    $end = get_post_meta( $post_id, '_rm_survey_end_date', true );
                    if ( $start && $end ) {
                        echo date( 'M j', strtotime( $start ) ) . ' - ' . date( 'M j, Y', strtotime( $end ) );
                    } else {
                        echo '—';
                    }
                }
                break;
                
            case 'amount':
                $type = get_post_meta( $post_id, '_rm_survey_type', true );
                if ( $type === 'paid' ) {
                    $amount = get_post_meta( $post_id, '_rm_survey_amount', true );
                    echo $amount ? '$' . number_format( $amount, 2 ) : '—';
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Make Columns Sortable
     */
    public function make_columns_sortable( $columns ) {
        $columns['survey_type'] = 'survey_type';
        $columns['survey_status'] = 'survey_status';
        $columns['amount'] = 'amount';
        
        return $columns;
    }

    /**
     * Handle Survey Redirect
     */
    public function handle_survey_redirect() {
        if ( is_singular( self::POST_TYPE ) ) {
            global $post;
            
            $survey_url = get_post_meta( $post->ID, '_rm_survey_url', true );
            $parameters = get_post_meta( $post->ID, '_rm_survey_parameters', true );
            
            if ( ! empty( $survey_url ) ) {
                $redirect_url = $survey_url;
                $query_params = [];
                
                // Check if user is logged in
                if ( is_user_logged_in() && ! empty( $parameters ) ) {
                    $current_user = wp_get_current_user();
                    
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
                }
                
                // Add parameters to URL
                if ( ! empty( $query_params ) ) {
                    $redirect_url = add_query_arg( $query_params, $redirect_url );
                }
                
                // Redirect to survey URL
                wp_redirect( $redirect_url );
                exit;
            }
        }
    }

    /**
     * Modify Survey Permalink
     */
    public function modify_survey_permalink( $permalink, $post ) {
        if ( $post->post_type === self::POST_TYPE ) {
            // Keep the default permalink for now
            // This can be modified if needed
        }
        return $permalink;
    }

    /**
     * Register Dynamic Tags for Elementor
     */
    public function register_dynamic_tags( $dynamic_tags_manager ) {
        // This will be implemented if needed for dynamic content
    }

    /**
     * Flush Rewrite Rules
     */
    public function flush_rewrite_rules() {
        $this->register_post_type();
        $this->register_taxonomies();
        flush_rewrite_rules();
    }

    /**
     * Get Survey Status Options
     */
    public static function get_status_options() {
        return [
            'draft' => __( 'Draft', 'rm-panel-extensions' ),
            'active' => __( 'Active', 'rm-panel-extensions' ),
            'paused' => __( 'Paused', 'rm-panel-extensions' ),
            'closed' => __( 'Closed', 'rm-panel-extensions' ),
        ];
    }

    /**
     * Check if survey is active
     */
    public static function is_survey_active( $post_id ) {
        $status = get_post_meta( $post_id, '_rm_survey_status', true );
        $duration_type = get_post_meta( $post_id, '_rm_survey_duration_type', true );
        
        if ( $status !== 'active' ) {
            return false;
        }
        
        if ( $duration_type === 'date_range' ) {
            $start_date = get_post_meta( $post_id, '_rm_survey_start_date', true );
            $end_date = get_post_meta( $post_id, '_rm_survey_end_date', true );
            $current_date = current_time( 'Y-m-d' );
            
            if ( $start_date && $current_date < $start_date ) {
                return false;
            }
            
            if ( $end_date && $current_date > $end_date ) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if user can access survey
     */
    public static function can_user_access_survey( $post_id, $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        
        // Check if login is required
        $requires_login = get_post_meta( $post_id, '_rm_survey_requires_login', true );
        if ( $requires_login && ! is_user_logged_in() ) {
            return false;
        }
        
        // Check user categories
        $survey_user_categories = wp_get_post_terms( $post_id, self::USER_CATEGORY_TAXONOMY, [ 'fields' => 'slugs' ] );
        
        // If no user categories are set, allow all users
        if ( empty( $survey_user_categories ) ) {
            return true;
        }
        
        // Check if "general-population" is selected
        if ( in_array( 'general-population', $survey_user_categories ) ) {
            return true;
        }
        
        // Check if user belongs to any of the specified categories
        // This would need to be extended based on how you store user categories
        // For now, we'll return true if user is logged in
        if ( is_user_logged_in() ) {
            // You can add custom user meta checks here
            // Example: $user_category = get_user_meta( $user_id, 'user_category', true );
            return true;
        }
        
        return false;
    }
}