<?php
/**
 * Enhanced Survey Tracking System
 * File: modules/survey/class-survey-tracking-enhanced.php
 * 
 * This extends the base tracking with:
 * - Waiting to complete status
 * - Not complete status  
 * - Auto-pause on quota full
 * - Manager notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Panel_Survey_Tracking_Enhanced extends RM_Panel_Survey_Tracking {
    
    /**
     * Additional status constants
     */
    const STATUS_WAITING = 'waiting_to_complete';
    const STATUS_NOT_COMPLETE = 'not_complete';
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->init_enhanced_features();
    }
    
    /**
     * Initialize enhanced features
     */
    private function init_enhanced_features() {
        // Schedule cron for checking waiting responses
        add_action('rm_check_waiting_responses', [$this, 'check_waiting_responses_cron']);
        
        if (!wp_next_scheduled('rm_check_waiting_responses')) {
            wp_schedule_event(time(), 'hourly', 'rm_check_waiting_responses');
        }
        
        // Hook into callback processing for auto-pause
        add_action('rm_survey_callback_processed', [$this, 'handle_quotafull_autopause'], 10, 3);
    }
    
    /**
     * Enhanced start survey with waiting status
     */
    public function start_survey($user_id, $survey_id) {
        global $wpdb;
        
        $existing = $this->get_user_survey_response($user_id, $survey_id);
        
        if ($existing && $existing->status === 'completed') {
            $allow_multiple = get_post_meta($survey_id, '_rm_survey_allow_multiple', true);
            if (!$allow_multiple) {
                return new WP_Error('already_completed', __('You have already completed this survey.', 'rm-panel-extensions'));
            }
        }
        
        $data = [
            'user_id' => $user_id,
            'survey_id' => $survey_id,
            'status' => self::STATUS_WAITING, // Changed from 'started' to 'waiting_to_complete'
            'start_time' => current_time('mysql'),
            'waiting_since' => current_time('mysql'),
            'country' => $this->get_user_country(),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer_url' => $_SERVER['HTTP_REFERER'] ?? ''
        ];
        
        if ($existing) {
            $wpdb->update($this->table_name, $data, ['id' => $existing->id]);
            $response_id = $existing->id;
        } else {
            $wpdb->insert($this->table_name, $data);
            $response_id = $wpdb->insert_id;
        }
        
        // Log the start
        do_action('rm_survey_started_waiting', $user_id, $survey_id, $response_id);
        
        return $response_id;
    }
    
    /**
     * Enhanced complete survey with status validation
     */
    public function complete_survey($user_id, $survey_id, $completion_status, $response_data = null) {
        global $wpdb;
        
        $valid_statuses = [
            self::STATUS_SUCCESS,
            self::STATUS_QUOTA_COMPLETE,
            self::STATUS_DISQUALIFIED
        ];
        
        if (!in_array($completion_status, $valid_statuses)) {
            return new WP_Error('invalid_status', __('Invalid completion status.', 'rm-panel-extensions'));
        }
        
        // Check if it's a paid survey
        $survey_type = get_post_meta($survey_id, '_rm_survey_type', true);
        $approval_status = ($survey_type === 'paid' && $completion_status === self::STATUS_SUCCESS) 
            ? 'pending' 
            : 'auto_approved';
        
        $data = [
            'status' => self::STATUS_COMPLETED,
            'completion_status' => $completion_status,
            'completion_time' => current_time('mysql'),
            'return_time' => current_time('mysql'),
            'approval_status' => $approval_status,
            'response_data' => $response_data ? json_encode($response_data) : null,
            'waiting_since' => NULL // Clear waiting status
        ];
        
        $result = $wpdb->update(
            $this->table_name,
            $data,
            [
                'user_id' => $user_id,
                'survey_id' => $survey_id
            ]
        );
        
        if ($result !== false) {
            // Trigger actions based on completion status
            do_action('rm_survey_completed', $user_id, $survey_id, $completion_status, $response_data);
            
            // Handle quota full scenario
            if ($completion_status === self::STATUS_QUOTA_COMPLETE) {
                $this->handle_quota_full($survey_id);
            }
            
            // Only process rewards if auto-approved
            if ($approval_status === 'auto_approved') {
                $this->process_survey_rewards($user_id, $survey_id, $completion_status);
            }
        }
        
        return $result;
    }
    
    /**
     * Handle quota full - auto-pause and notify
     */
    private function handle_quota_full($survey_id) {
        // Check if notifications are enabled
        $notify_enabled = get_post_meta($survey_id, '_rm_survey_notify_quotafull', true);
        
        if ($notify_enabled === '1') {
            // Auto-pause the survey
            $this->auto_pause_survey($survey_id, 'quota_full');
            
            // Send notification to survey manager
            $this->notify_survey_manager($survey_id, 'quota_full');
        }
    }
    
    /**
     * Auto-pause survey
     */
    private function auto_pause_survey($survey_id, $reason = '') {
        global $wpdb;
        
        // Update survey status to paused
        update_post_meta($survey_id, '_rm_survey_status', 'paused');
        update_post_meta($survey_id, '_rm_survey_paused_reason', $reason);
        update_post_meta($survey_id, '_rm_survey_paused_at', current_time('mysql'));
        
        // Log to responses table
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} 
             SET survey_paused_at = %s 
             WHERE survey_id = %d 
             AND completion_status = 'quota_complete' 
             ORDER BY completion_time DESC 
             LIMIT 1",
            current_time('mysql'),
            $survey_id
        ));
        
        do_action('rm_survey_auto_paused', $survey_id, $reason);
        
        error_log("Survey #{$survey_id} auto-paused due to: {$reason}");
    }
    
    /**
     * Notify survey manager
     */
    private function notify_survey_manager($survey_id, $reason) {
        $manager_id = get_post_meta($survey_id, '_rm_survey_manager_id', true);
        
        if (!$manager_id) {
            return;
        }
        
        $manager = get_userdata($manager_id);
        if (!$manager) {
            return;
        }
        
        $survey = get_post($survey_id);
        $survey_title = $survey ? $survey->post_title : "Survey #{$survey_id}";
        
        // Get survey statistics
        $stats = $this->get_survey_stats($survey_id);
        
        // Prepare email
        $to = $manager->user_email;
        $subject = sprintf(
            __('[Action Required] Survey Quota Full: %s', 'rm-panel-extensions'),
            $survey_title
        );
        
        $message = sprintf(
            __('Hello %s,

The survey "%s" has reached its quota and has been automatically paused.

Survey Statistics:
- Total Participants: %d
- Completed: %d
- Successful: %d
- Quota Full: %d
- Disqualified: %d

The survey status has been changed to "Paused" to prevent new participants from starting.

Actions you can take:
1. Review the survey responses in the admin panel
2. Export the completed responses
3. If needed, increase the quota and reactivate the survey

View Survey: %s

---
This is an automated notification from RM Panel Extensions.', 'rm-panel-extensions'),
            $manager->display_name,
            $survey_title,
            $stats->total_participants,
            $stats->total_completed,
            $stats->successful,
            $stats->quota_complete,
            $stats->disqualified,
            admin_url('post.php?post=' . $survey_id . '&action=edit')
        );
        
        // Set headers
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
        
        // Send email
        $sent = wp_mail($to, $subject, $message, $headers);
        
        if ($sent) {
            // Log notification
            add_post_meta($survey_id, '_rm_survey_notification_sent', [
                'type' => 'quota_full',
                'recipient' => $manager->user_email,
                'sent_at' => current_time('mysql')
            ]);
            
            error_log("Quota full notification sent to {$manager->user_email} for survey #{$survey_id}");
        } else {
            error_log("Failed to send quota full notification for survey #{$survey_id}");
        }
        
        do_action('rm_survey_manager_notified', $survey_id, $manager_id, $reason);
    }
    
    /**
     * Cron job to check waiting responses
     * Mark as "not_complete" if waiting too long
     */
    public function check_waiting_responses_cron() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        
        // Get responses waiting more than 48 hours
        $timeout_hours = apply_filters('rm_survey_waiting_timeout_hours', 48);
        
        $waiting_responses = $wpdb->get_results($wpdb->prepare(
            "SELECT id, user_id, survey_id, start_time, waiting_since
             FROM {$table_name}
             WHERE status = %s
             AND waiting_since < DATE_SUB(NOW(), INTERVAL %d HOUR)",
            self::STATUS_WAITING,
            $timeout_hours
        ));
        
        foreach ($waiting_responses as $response) {
            // Update to not_complete
            $wpdb->update(
                $table_name,
                [
                    'status' => self::STATUS_NOT_COMPLETE,
                    'completion_status' => 'abandoned',
                    'completion_time' => current_time('mysql'),
                    'waiting_since' => NULL
                ],
                ['id' => $response->id]
            );
            
            do_action('rm_survey_marked_not_complete', $response->user_id, $response->survey_id, $response->id);
        }
        
        if (count($waiting_responses) > 0) {
            error_log(sprintf(
                'Marked %d surveys as not_complete after %d hour timeout',
                count($waiting_responses),
                $timeout_hours
            ));
        }
    }
    
    /**
     * Get status label - enhanced
     */
    public static function get_status_label($status) {
        $labels = [
            self::STATUS_STARTED => __('Started', 'rm-panel-extensions'),
            self::STATUS_WAITING => __('Waiting to Complete', 'rm-panel-extensions'),
            self::STATUS_NOT_COMPLETE => __('Not Complete', 'rm-panel-extensions'),
            self::STATUS_COMPLETED => __('Completed', 'rm-panel-extensions'),
            self::STATUS_SUCCESS => __('Successful', 'rm-panel-extensions'),
            self::STATUS_QUOTA_COMPLETE => __('Quota Full', 'rm-panel-extensions'),
            self::STATUS_DISQUALIFIED => __('Disqualified', 'rm-panel-extensions'),
            self::STATUS_ABANDONED => __('Abandoned', 'rm-panel-extensions')
        ];
        
        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
    
    /**
     * Hook for callback-based auto-pause
     */
    public function handle_quotafull_autopause($survey_id, $user_id, $status) {
        if ($status === 'quota_complete') {
            $this->handle_quota_full($survey_id);
        }
    }
}

// Replace the original tracking class
if (class_exists('RM_Panel_Survey_Tracking')) {
    // Use the enhanced version
    global $rm_survey_tracker;
    $rm_survey_tracker = new RM_Panel_Survey_Tracking_Enhanced();
}
