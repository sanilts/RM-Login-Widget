<?php
/**
 * Survey Manager Meta Box Addition
 * File: modules/survey/class-survey-manager-metabox.php
 * 
 * Add this to class-survey-module.php or include as separate file
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Survey_Manager_Metabox {
    
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_manager_metabox']);
        add_action('save_post_rm_survey', [$this, 'save_manager_meta'], 10, 3);
    }
    
    /**
     * Add Survey Manager meta box
     */
    public function add_manager_metabox() {
        add_meta_box(
            'rm_survey_manager',
            __('Survey Manager', 'rm-panel-extensions'),
            [$this, 'render_manager_metabox'],
            'rm_survey',
            'side',
            'high'
        );
    }
    
    /**
     * Render Survey Manager meta box
     */
    public function render_manager_metabox($post) {
        wp_nonce_field('rm_survey_manager_nonce', 'rm_survey_manager_nonce_field');
        
        $survey_manager_id = get_post_meta($post->ID, '_rm_survey_manager_id', true);
        $notify_on_quotafull = get_post_meta($post->ID, '_rm_survey_notify_quotafull', true);
        
        // Get all users with administrator or survey_manager capability
        $managers = get_users([
            'role__in' => ['administrator', 'editor'],
            'orderby' => 'display_name',
            'order' => 'ASC'
        ]);
        
        ?>
        <div class="survey-manager-field">
            <p>
                <label for="rm_survey_manager_id">
                    <strong><?php _e('Assign Survey Manager:', 'rm-panel-extensions'); ?></strong>
                </label>
            </p>
            <select id="rm_survey_manager_id" name="rm_survey_manager_id" style="width: 100%;">
                <option value=""><?php _e('— No Manager —', 'rm-panel-extensions'); ?></option>
                <?php foreach ($managers as $manager) : ?>
                    <option value="<?php echo esc_attr($manager->ID); ?>" <?php selected($survey_manager_id, $manager->ID); ?>>
                        <?php echo esc_html($manager->display_name); ?> (<?php echo esc_html($manager->user_email); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">
                <?php _e('Select the person responsible for managing this survey.', 'rm-panel-extensions'); ?>
            </p>
        </div>
        
        <div class="survey-manager-notifications" style="margin-top: 15px;">
            <p>
                <label>
                    <input type="checkbox" 
                           name="rm_survey_notify_quotafull" 
                           value="1" 
                           <?php checked($notify_on_quotafull, '1'); ?>>
                    <?php _e('Notify manager when quota is full', 'rm-panel-extensions'); ?>
                </label>
            </p>
            <p class="description">
                <?php _e('Survey will be automatically paused and manager will receive an email notification.', 'rm-panel-extensions'); ?>
            </p>
        </div>
        
        <?php if ($survey_manager_id) : ?>
            <div class="survey-manager-info" style="margin-top: 15px; padding: 10px; background: #f0f8ff; border-radius: 4px;">
                <?php
                $manager = get_userdata($survey_manager_id);
                if ($manager) :
                ?>
                    <p style="margin: 0;">
                        <strong><?php _e('Current Manager:', 'rm-panel-extensions'); ?></strong><br>
                        <?php echo esc_html($manager->display_name); ?><br>
                        <a href="mailto:<?php echo esc_attr($manager->user_email); ?>">
                            <?php echo esc_html($manager->user_email); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <style>
            .survey-manager-field select {
                margin-top: 5px;
            }
            .survey-manager-notifications label {
                display: flex;
                align-items: center;
                gap: 8px;
            }
        </style>
        <?php
    }
    
    /**
     * Save Survey Manager meta
     */
    public function save_manager_meta($post_id, $post, $update) {
        // Check nonce
        if (!isset($_POST['rm_survey_manager_nonce_field']) || 
            !wp_verify_nonce($_POST['rm_survey_manager_nonce_field'], 'rm_survey_manager_nonce')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save manager ID
        if (isset($_POST['rm_survey_manager_id'])) {
            $manager_id = intval($_POST['rm_survey_manager_id']);
            if ($manager_id > 0) {
                update_post_meta($post_id, '_rm_survey_manager_id', $manager_id);
            } else {
                delete_post_meta($post_id, '_rm_survey_manager_id');
            }
        }
        
        // Save notification preference
        $notify = isset($_POST['rm_survey_notify_quotafull']) ? '1' : '0';
        update_post_meta($post_id, '_rm_survey_notify_quotafull', $notify);
    }
}

// Initialize
new RM_Survey_Manager_Metabox();
