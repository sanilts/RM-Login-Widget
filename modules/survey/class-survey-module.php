<?php
/**
 * RM Panel Extensions - Survey Module
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
        
        // Register taxonomy
        add_action( 'init', [ $this, 'register_taxonomy' ] );
        
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
            'show_in_rest'          => true, // Enable Gutenberg
            'rest_base'             => 'surveys',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        ];

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Register Survey Category Taxonomy
     */
    public function register_taxonomy() {
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
    }

    /**
     * Add Meta Boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'rm_survey_details',
            __( 'Survey Details', 'rm-panel-extensions' ),
            [ $this, 'render_survey_details_metabox' ],
            self::POST_TYPE,
            'normal',
            'high'
        );

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
     * Render Survey Details Metabox
     */
    public function render_survey_details_metabox( $post ) {
        wp_nonce_field( 'rm_survey_meta_box', 'rm_survey_meta_box_nonce' );

        $start_date = get_post_meta( $post->ID, '_rm_survey_start_date', true );
        $end_date = get_post_meta( $post->ID, '_rm_survey_end_date', true );
        $questions_count = get_post_meta( $post->ID, '_rm_survey_questions_count', true );
        $estimated_time = get_post_meta( $post->ID, '_rm_survey_estimated_time', true );
        $target_audience = get_post_meta( $post->ID, '_rm_survey_target_audience', true );
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
            .survey-meta-field input[type="date"],
            .survey-meta-field textarea {
                width: 100%;
                max-width: 500px;
            }
            .survey-meta-field .description {
                color: #666;
                font-size: 13px;
                margin-top: 5px;
            }
        </style>
        
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
            <label for="rm_survey_target_audience"><?php _e( 'Target Audience', 'rm-panel-extensions' ); ?></label>
            <textarea id="rm_survey_target_audience" name="rm_survey_target_audience" rows="3"><?php echo esc_textarea( $target_audience ); ?></textarea>
            <p class="description"><?php _e( 'Who is this survey intended for?', 'rm-panel-extensions' ); ?></p>
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

        // Save survey details
        $fields = [
            'rm_survey_start_date' => '_rm_survey_start_date',
            'rm_survey_end_date' => '_rm_survey_end_date',
            'rm_survey_questions_count' => '_rm_survey_questions_count',
            'rm_survey_estimated_time' => '_rm_survey_estimated_time',
            'rm_survey_target_audience' => '_rm_survey_target_audience',
            'rm_survey_status' => '_rm_survey_status',
        ];

        foreach ( $fields as $field_name => $meta_key ) {
            if ( isset( $_POST[$field_name] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[$field_name] ) );
            }
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
                $new_columns['survey_status'] = __( 'Status', 'rm-panel-extensions' );
                $new_columns['questions'] = __( 'Questions', 'rm-panel-extensions' );
                $new_columns['start_date'] = __( 'Start Date', 'rm-panel-extensions' );
                $new_columns['end_date'] = __( 'End Date', 'rm-panel-extensions' );
            }
        }
        
        return $new_columns;
    }

    /**
     * Render Admin Columns
     */
    public function render_admin_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'survey_status':
                $status = get_post_meta( $post_id, '_rm_survey_status', true );
                $status_label = $status ? ucfirst( $status ) : 'Draft';
                $status_class = 'status-' . ( $status ?: 'draft' );
                echo '<span class="' . esc_attr( $status_class ) . '" style="padding: 3px 8px; border-radius: 3px; background: #f0f0f0;">' . esc_html( $status_label ) . '</span>';
                break;
                
            case 'questions':
                $count = get_post_meta( $post_id, '_rm_survey_questions_count', true );
                echo $count ? esc_html( $count ) : '—';
                break;
                
            case 'start_date':
                $date = get_post_meta( $post_id, '_rm_survey_start_date', true );
                echo $date ? esc_html( date( 'M j, Y', strtotime( $date ) ) ) : '—';
                break;
                
            case 'end_date':
                $date = get_post_meta( $post_id, '_rm_survey_end_date', true );
                echo $date ? esc_html( date( 'M j, Y', strtotime( $date ) ) ) : '—';
                break;
        }
    }

    /**
     * Make Columns Sortable
     */
    public function make_columns_sortable( $columns ) {
        $columns['survey_status'] = 'survey_status';
        $columns['questions'] = 'questions';
        $columns['start_date'] = 'start_date';
        $columns['end_date'] = 'end_date';
        
        return $columns;
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
        $this->register_taxonomy();
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
        $start_date = get_post_meta( $post_id, '_rm_survey_start_date', true );
        $end_date = get_post_meta( $post_id, '_rm_survey_end_date', true );
        
        if ( $status !== 'active' ) {
            return false;
        }
        
        $current_date = current_time( 'Y-m-d' );
        
        if ( $start_date && $current_date < $start_date ) {
            return false;
        }
        
        if ( $end_date && $current_date > $end_date ) {
            return false;
        }
        
        return true;
    }
}