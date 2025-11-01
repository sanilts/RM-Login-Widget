<?php
/**
 * Survey Thank You Page Handler
 * File: modules/survey/class-survey-thank-you.php
 * 
 * INSTALLATION:
 * 1. Copy this file to: wp-content/plugins/rm-panel-extensions/modules/survey/
 * 2. Add to main plugin file (after other survey includes):
 *    require_once RM_PANEL_EXT_PLUGIN_DIR . 'modules/survey/class-survey-thank-you.php';
 * 3. Create WordPress page with shortcode [survey_thank_you]
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Survey_Thank_You {
    
    public function __construct() {
        // Register shortcode
        add_shortcode('survey_thank_you', [$this, 'render_thank_you_page']);
        
        // Enqueue styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }
    
    /**
     * Enqueue styles for thank you page
     */
    public function enqueue_styles() {
        // Only load on thank you page
        if (is_page('survey-thank-you') || has_shortcode(get_post()->post_content ?? '', 'survey_thank_you')) {
            wp_enqueue_style(
                'rm-survey-thank-you',
                RM_PANEL_EXT_PLUGIN_URL . 'assets/css/survey-thank-you.css',
                [],
                RM_PANEL_EXT_VERSION
            );
        }
    }
    
    /**
     * Render thank you page
     */
    public function render_thank_you_page($atts) {
        // Get parameters from URL
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $survey_id = isset($_GET['survey_id']) ? intval($_GET['survey_id']) : 0;
        
        // Get survey details if ID provided
        $survey = null;
        $survey_title = '';
        $survey_type = '';
        $survey_amount = 0;
        
        if ($survey_id > 0) {
            $survey = get_post($survey_id);
            if ($survey) {
                $survey_title = $survey->post_title;
                $survey_type = get_post_meta($survey_id, '_rm_survey_type', true);
                $survey_amount = get_post_meta($survey_id, '_rm_survey_amount', true);
            }
        }
        
        ob_start();
        ?>
        <div class="survey-thank-you-container">
            <?php if ($status === 'success'): ?>
                <!-- SUCCESS -->
                <div class="thank-you-message success">
                    <div class="thank-you-icon success-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <h2><?php _e('Survey Completed Successfully!', 'rm-panel-extensions'); ?></h2>
                    <p class="thank-you-description">
                        <?php _e('Thank you for completing the survey. Your response has been recorded.', 'rm-panel-extensions'); ?>
                    </p>
                    
                    <?php if ($survey_title) : ?>
                        <div class="survey-info-box">
                            <strong><?php _e('Survey:', 'rm-panel-extensions'); ?></strong> 
                            <?php echo esc_html($survey_title); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    // Check if paid survey
                    if ($survey_type === 'paid' && $survey_amount > 0) : ?>
                        <div class="earning-info">
                            <div class="earning-icon">💰</div>
                            <div class="earning-details">
                                <p class="amount">
                                    <?php printf(
                                        __('You have earned: %s', 'rm-panel-extensions'), 
                                        '<strong>$' . number_format($survey_amount, 2) . '</strong>'
                                    ); ?>
                                </p>
                                <p class="note">
                                    <?php _e('This amount will be credited to your account after admin approval.', 'rm-panel-extensions'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="approval-notice">
                            <span class="dashicons dashicons-info"></span>
                            <div>
                                <strong><?php _e('What happens next?', 'rm-panel-extensions'); ?></strong>
                                <p><?php _e('Your response will be reviewed by our team. Once approved, the payment will be processed and added to your account. You\'ll receive an email notification.', 'rm-panel-extensions'); ?></p>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="thank-you-note">
                            <span class="dashicons dashicons-heart"></span>
                            <p><?php _e('Your feedback is valuable to us. Thank you for your time!', 'rm-panel-extensions'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($status === 'quota_complete' || $status === 'quotafull'): ?>
                <!-- QUOTA FULL -->
                <div class="thank-you-message quota-full">
                    <div class="thank-you-icon warning-icon">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <h2><?php _e('Survey Quota Reached', 'rm-panel-extensions'); ?></h2>
                    <p class="thank-you-description">
                        <?php _e('Unfortunately, this survey has already received the required number of responses.', 'rm-panel-extensions'); ?>
                    </p>
                    
                    <?php if ($survey_title) : ?>
                        <div class="survey-info-box">
                            <strong><?php _e('Survey:', 'rm-panel-extensions'); ?></strong> 
                            <?php echo esc_html($survey_title); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-message">
                        <span class="dashicons dashicons-info"></span>
                        <p><?php _e('Don\'t worry! We have many other surveys available. Check out our survey list to find one that matches your profile.', 'rm-panel-extensions'); ?></p>
                    </div>
                </div>
                
            <?php elseif ($status === 'disqualified' || $status === 'terminate'): ?>
                <!-- DISQUALIFIED -->
                <div class="thank-you-message disqualified">
                    <div class="thank-you-icon info-icon">
                        <span class="dashicons dashicons-info"></span>
                    </div>
                    <h2><?php _e('Survey Not Matched', 'rm-panel-extensions'); ?></h2>
                    <p class="thank-you-description">
                        <?php _e('Based on your responses, this particular survey was not a match for your profile.', 'rm-panel-extensions'); ?>
                    </p>
                    
                    <?php if ($survey_title) : ?>
                        <div class="survey-info-box">
                            <strong><?php _e('Survey:', 'rm-panel-extensions'); ?></strong> 
                            <?php echo esc_html($survey_title); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-message">
                        <span class="dashicons dashicons-lightbulb"></span>
                        <p><?php _e('This is completely normal! Not all surveys will be a perfect match. We have many other surveys that may be a better fit for you.', 'rm-panel-extensions'); ?></p>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- DEFAULT / UNKNOWN STATUS -->
                <div class="thank-you-message default">
                    <div class="thank-you-icon default-icon">
                        <span class="dashicons dashicons-thumbs-up"></span>
                    </div>
                    <h2><?php _e('Thank You', 'rm-panel-extensions'); ?></h2>
                    <p class="thank-you-description">
                        <?php _e('Thank you for your participation.', 'rm-panel-extensions'); ?>
                    </p>
                    
                    <?php if ($survey_title) : ?>
                        <div class="survey-info-box">
                            <strong><?php _e('Survey:', 'rm-panel-extensions'); ?></strong> 
                            <?php echo esc_html($survey_title); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="thank-you-actions">
                <?php
                // Get archive URL
                $archive_url = get_post_type_archive_link('rm_survey');
                if (!$archive_url) {
                    // Fallback to surveys page
                    $surveys_page = get_page_by_path('surveys');
                    $archive_url = $surveys_page ? get_permalink($surveys_page) : home_url('/surveys/');
                }
                
                // Get dashboard URL
                $dashboard_url = home_url('/my-dashboard/');
                $dashboard_page = get_page_by_path('my-dashboard');
                if ($dashboard_page) {
                    $dashboard_url = get_permalink($dashboard_page);
                }
                ?>
                
                <a href="<?php echo esc_url($archive_url); ?>" class="button button-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Browse More Surveys', 'rm-panel-extensions'); ?>
                </a>
                
                <?php if (is_user_logged_in()) : ?>
                    <a href="<?php echo esc_url($dashboard_url); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-dashboard"></span>
                        <?php _e('Go to Dashboard', 'rm-panel-extensions'); ?>
                    </a>
                <?php endif; ?>
                
                <a href="<?php echo esc_url(home_url('/')); ?>" class="button button-tertiary">
                    <span class="dashicons dashicons-admin-home"></span>
                    <?php _e('Back to Home', 'rm-panel-extensions'); ?>
                </a>
            </div>
            
            <!-- Additional Help -->
            <?php if (is_user_logged_in()) : ?>
                <div class="thank-you-footer">
                    <p><?php _e('Need help? Contact our support team.', 'rm-panel-extensions'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php
        return ob_get_clean();
    }
}

// Initialize
new RM_Survey_Thank_You();