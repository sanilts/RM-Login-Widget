// Add this shortcode to your Survey Tracking class or create new file
// File: modules/survey/class-survey-thank-you.php

<?php
class RM_Survey_Thank_You {
    
    public function __construct() {
        add_shortcode('survey_thank_you', [$this, 'render_thank_you_page']);
    }
    
    public function render_thank_you_page($atts) {
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $survey_id = isset($_GET['survey_id']) ? intval($_GET['survey_id']) : 0;
        
        ob_start();
        ?>
        <div class="survey-thank-you-container">
            <?php if ($status === 'success'): ?>
                <div class="thank-you-message success">
                    <h2><?php _e('Survey Completed Successfully!', 'rm-panel-extensions'); ?></h2>
                    <p><?php _e('Thank you for completing the survey. Your response has been recorded.', 'rm-panel-extensions'); ?></p>
                    <?php
                    // Check if paid survey
                    $survey_type = get_post_meta($survey_id, '_rm_survey_type', true);
                    $survey_amount = get_post_meta($survey_id, '_rm_survey_amount', true);
                    if ($survey_type === 'paid' && $survey_amount): ?>
                        <div class="earning-info">
                            <p class="amount">
                                <?php printf(__('You have earned: $%s', 'rm-panel-extensions'), number_format($survey_amount, 2)); ?>
                            </p>
                            <p class="note">
                                <?php _e('This amount will be credited to your account within 24 hours.', 'rm-panel-extensions'); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($status === 'quota_complete'): ?>
                <div class="thank-you-message quota-full">
                    <h2><?php _e('Survey Quota Reached', 'rm-panel-extensions'); ?></h2>
                    <p><?php _e('Unfortunately, this survey has already received the required number of responses.', 'rm-panel-extensions'); ?></p>
                    <p><?php _e('Please check out our other available surveys.', 'rm-panel-extensions'); ?></p>
                </div>
                
            <?php elseif ($status === 'disqualified'): ?>
                <div class="thank-you-message disqualified">
                    <h2><?php _e('Survey Not Matched', 'rm-panel-extensions'); ?></h2>
                    <p><?php _e('Based on your profile, this survey was not a match.', 'rm-panel-extensions'); ?></p>
                    <p><?php _e('Don\'t worry! There are other surveys that may be a better fit for you.', 'rm-panel-extensions'); ?></p>
                </div>
                
            <?php else: ?>
                <div class="thank-you-message default">
                    <h2><?php _e('Thank You', 'rm-panel-extensions'); ?></h2>
                    <p><?php _e('Thank you for your participation.', 'rm-panel-extensions'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="thank-you-actions">
                <a href="<?php echo get_post_type_archive_link('rm_survey'); ?>" class="button button-primary">
                    <?php _e('Browse More Surveys', 'rm-panel-extensions'); ?>
                </a>
                <a href="<?php echo home_url('/my-dashboard/'); ?>" class="button">
                    <?php _e('View Dashboard', 'rm-panel-extensions'); ?>
                </a>
            </div>
        </div>
        
        <style>
            .survey-thank-you-container {
                max-width: 600px;
                margin: 40px auto;
                padding: 30px;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .thank-you-message h2 {
                margin-bottom: 20px;
                color: #333;
            }
            .thank-you-message p {
                font-size: 16px;
                line-height: 1.6;
                color: #666;
            }
            .thank-you-message.success h2 {
                color: #46b450;
            }
            .thank-you-message.quota-full h2 {
                color: #f0ad4e;
            }
            .thank-you-message.disqualified h2 {
                color: #dc3545;
            }
            .earning-info {
                background: #f0f8ff;
                padding: 20px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .earning-info .amount {
                font-size: 24px;
                font-weight: bold;
                color: #46b450;
            }
            .thank-you-actions {
                margin-top: 30px;
                display: flex;
                gap: 15px;
                justify-content: center;
            }
            .thank-you-actions .button {
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 5px;
            }
            .thank-you-actions .button-primary {
                background: #007cba;
                color: white;
            }
        </style>
        <?php
        return ob_get_clean();
    }
}

// Initialize
new RM_Survey_Thank_You();