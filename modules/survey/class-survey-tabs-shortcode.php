<?php
/**
 * Survey Tabs Shortcode - Available & Completed Surveys
 * File: modules/survey/class-survey-tabs-shortcode.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Survey_Tabs_Shortcode {
    
    private $tracker;
    
    public function __construct() {
        $this->tracker = new RM_Panel_Survey_Tracking();
        add_shortcode('rm_survey_tabs', [$this, 'render_survey_tabs']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    public function enqueue_scripts() {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'rm_survey_tabs')) {
            wp_enqueue_style(
                'rm-survey-tabs',
                RM_PANEL_EXT_PLUGIN_URL . 'assets/css/survey-tabs.css',
                [],
                RM_PANEL_EXT_VERSION
            );
            
            wp_enqueue_script(
                'rm-survey-tabs',
                RM_PANEL_EXT_PLUGIN_URL . 'assets/js/survey-tabs.js',
                ['jquery'],
                RM_PANEL_EXT_VERSION,
                true
            );
        }
    }
    
    public function render_survey_tabs($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view surveys.', 'rm-panel-extensions') . '</p>';
        }
        
        $atts = shortcode_atts([
            'columns' => 3,
            'show_amount' => 'yes',
            'show_category' => 'yes'
        ], $atts);
        
        $user_id = get_current_user_id();
        
        // Get available surveys
        $available_surveys = $this->tracker->get_available_surveys($user_id);
        
        // Get completed surveys
        $completed_surveys = $this->tracker->get_user_survey_history($user_id, [
            'status' => 'completed',
            'limit' => 50
        ]);
        
        ob_start();
        ?>
        <div class="rm-survey-tabs-container">
            <div class="rm-survey-tabs-nav">
                <button class="rm-tab-btn active" data-tab="available">
                    <?php _e('Available Surveys', 'rm-panel-extensions'); ?>
                    <span class="tab-count"><?php echo count($available_surveys); ?></span>
                </button>
                <button class="rm-tab-btn" data-tab="completed">
                    <?php _e('Completed Surveys', 'rm-panel-extensions'); ?>
                    <span class="tab-count"><?php echo count($completed_surveys); ?></span>
                </button>
            </div>
            
            <!-- Available Surveys Tab -->
            <div class="rm-tab-content active" id="tab-available">
                <?php if (empty($available_surveys)) : ?>
                    <div class="no-surveys">
                        <p><?php _e('No available surveys at the moment. Check back later!', 'rm-panel-extensions'); ?></p>
                    </div>
                <?php else : ?>
                    <div class="rm-survey-grid" style="grid-template-columns: repeat(<?php echo $atts['columns']; ?>, 1fr);">
                        <?php foreach ($available_surveys as $survey) : ?>
                            <?php $this->render_survey_card($survey, $atts, 'available'); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Completed Surveys Tab -->
            <div class="rm-tab-content" id="tab-completed">
                <?php if (empty($completed_surveys)) : ?>
                    <div class="no-surveys">
                        <p><?php _e('You haven\'t completed any surveys yet.', 'rm-panel-extensions'); ?></p>
                    </div>
                <?php else : ?>
                    <div class="rm-survey-grid" style="grid-template-columns: repeat(<?php echo $atts['columns']; ?>, 1fr);">
                        <?php foreach ($completed_surveys as $response) : ?>
                            <?php $this->render_completed_survey_card($response, $atts); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_survey_card($survey, $atts, $type) {
        $survey_type = get_post_meta($survey->ID, '_rm_survey_type', true);
        $survey_amount = get_post_meta($survey->ID, '_rm_survey_amount', true);
        $estimated_time = get_post_meta($survey->ID, '_rm_survey_estimated_time', true);
        $questions_count = get_post_meta($survey->ID, '_rm_survey_questions_count', true);
        $survey_url = get_post_meta($survey->ID, '_rm_survey_url', true);
        $parameters = get_post_meta($survey->ID, '_rm_survey_parameters', true);
        
        // Build survey URL
        $final_url = $this->build_survey_url($survey->ID, $survey_url, $parameters);
        ?>
        <div class="rm-survey-card">
            <?php if (has_post_thumbnail($survey->ID)) : ?>
                <div class="survey-card-image">
                    <?php echo get_the_post_thumbnail($survey->ID, 'medium'); ?>
                    <?php if ($survey_type === 'paid' && $atts['show_amount'] === 'yes') : ?>
                        <div class="survey-amount-badge">
                            $<?php echo number_format($survey_amount, 2); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="survey-card-content">
                <?php if ($atts['show_category'] === 'yes') : ?>
                    <?php $categories = get_the_terms($survey->ID, 'survey_category'); ?>
                    <?php if ($categories && !is_wp_error($categories)) : ?>
                        <div class="survey-categories">
                            <?php foreach ($categories as $cat) : ?>
                                <span class="survey-category-tag"><?php echo esc_html($cat->name); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <h3 class="survey-card-title">
                    <?php echo esc_html($survey->post_title); ?>
                </h3>
                
                <div class="survey-card-excerpt">
                    <?php echo wp_trim_words($survey->post_excerpt, 15); ?>
                </div>
                
                <div class="survey-card-meta">
                    <?php if ($questions_count) : ?>
                        <span class="meta-item">
                            <i class="dashicons dashicons-editor-ul"></i>
                            <?php echo $questions_count; ?> <?php _e('Questions', 'rm-panel-extensions'); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($estimated_time) : ?>
                        <span class="meta-item">
                            <i class="dashicons dashicons-clock"></i>
                            <?php echo $estimated_time; ?> <?php _e('min', 'rm-panel-extensions'); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <a href="<?php echo esc_url($final_url); ?>" 
                   class="survey-card-button" 
                   target="_blank"
                   data-survey-id="<?php echo $survey->ID; ?>">
                    <?php _e('Start Survey', 'rm-panel-extensions'); ?>
                    <i class="dashicons dashicons-arrow-right-alt"></i>
                </a>
            </div>
        </div>
        <?php
    }
    
    private function render_completed_survey_card($response, $atts) {
        $survey = get_post($response->survey_id);
        if (!$survey) return;
        
        $survey_type = get_post_meta($response->survey_id, '_rm_survey_type', true);
        $survey_amount = get_post_meta($response->survey_id, '_rm_survey_amount', true);
        ?>
        <div class="rm-survey-card completed-survey">
            <?php if (has_post_thumbnail($response->survey_id)) : ?>
                <div class="survey-card-image">
                    <?php echo get_the_post_thumbnail($response->survey_id, 'medium'); ?>
                    <div class="completion-overlay">
                        <i class="dashicons dashicons-yes-alt"></i>
                        <?php _e('Completed', 'rm-panel-extensions'); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="survey-card-content">
                <h3 class="survey-card-title">
                    <?php echo esc_html($response->survey_title); ?>
                </h3>
                
                <div class="completion-info">
                    <div class="info-row">
                        <span class="label"><?php _e('Completed:', 'rm-panel-extensions'); ?></span>
                        <span class="value"><?php echo date_i18n(get_option('date_format'), strtotime($response->completion_time)); ?></span>
                    </div>
                    
                    <?php if ($response->completion_status) : ?>
                        <div class="info-row">
                            <span class="label"><?php _e('Status:', 'rm-panel-extensions'); ?></span>
                            <span class="completion-status status-<?php echo esc_attr($response->completion_status); ?>">
                                <?php echo $this->get_status_label($response->completion_status); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($survey_type === 'paid' && $response->completion_status === 'success') : ?>
                        <div class="info-row">
                            <span class="label"><?php _e('Amount:', 'rm-panel-extensions'); ?></span>
                            <span class="value amount">$<?php echo number_format($survey_amount, 2); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <span class="label"><?php _e('Payment:', 'rm-panel-extensions'); ?></span>
                            <span class="approval-status status-<?php echo esc_attr($response->approval_status); ?>">
                                <?php 
                                $approval_labels = [
                                    'pending' => __('Pending Approval', 'rm-panel-extensions'),
                                    'approved' => __('Approved', 'rm-panel-extensions'),
                                    'rejected' => __('Not Approved', 'rm-panel-extensions')
                                ];
                                echo $approval_labels[$response->approval_status] ?? $response->approval_status;
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
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
    
    private function get_status_label($status) {
        $labels = [
            'success' => __('Successful', 'rm-panel-extensions'),
            'quota_complete' => __('Quota Full', 'rm-panel-extensions'),
            'disqualified' => __('Disqualified', 'rm-panel-extensions')
        ];
        return $labels[$status] ?? $status;
    }
}

new RM_Survey_Tabs_Shortcode();