<?php

/**
 * Profile Picture Upload Handler
 * 
 * Handles AJAX upload requests, file validation, and profile picture management
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class RM_Profile_Picture_Handler {

    /**
     * Instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // AJAX handlers for logged-in users
        add_action('wp_ajax_rm_upload_profile_picture', [$this, 'upload_profile_picture']);

        // AJAX handlers for getting current profile picture
        add_action('wp_ajax_rm_get_profile_picture', [$this, 'get_profile_picture']);

        // AJAX handler for deleting profile picture
        add_action('wp_ajax_rm_delete_profile_picture', [$this, 'delete_profile_picture']);
    }
    
    /**
     * Get profile picture
     */
    public function get_profile_picture() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rm_profile_picture_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'rm-panel-extensions')]);
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in', 'rm-panel-extensions')]);
        }

        $user_id = get_current_user_id();
        $attachment_id = get_user_meta($user_id, 'rm_profile_picture', true);

        if ($attachment_id) {
            $image_url = wp_get_attachment_image_url($attachment_id, 'medium');
            wp_send_json_success([
                'url' => $image_url,
                'has_picture' => true
            ]);
        } else {
            wp_send_json_success([
                'url' => get_avatar_url($user_id, ['size' => 150]),
                'has_picture' => false
            ]);
        }
    }

    /**
     * Maybe delete old profile picture
     * Only delete if it's not used elsewhere
     */
    private function maybe_delete_old_picture($attachment_id) {
        // Check if this attachment is used by other users
        global $wpdb;

        $usage_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->usermeta} 
            WHERE meta_key = 'rm_profile_picture' 
            AND meta_value = %d",
                        $attachment_id
        ));

        // Only delete if not used by any other user
        if ($usage_count == 0) {
            wp_delete_attachment($attachment_id, true);
        }
    }

    /**
     * Log profile picture update
     */
    private function log_profile_picture_update($user_id, $attachment_id) {
        // Optional: Add to activity log or user meta
        $log_entry = [
            'user_id' => $user_id,
            'attachment_id' => $attachment_id,
            'timestamp' => current_time('mysql'),
            'ip_address' => $this->get_user_ip()
        ];

        // Store in user meta as history (optional)
        $history = get_user_meta($user_id, 'rm_profile_picture_history', true);
        if (!is_array($history)) {
            $history = [];
        }

        // Keep only last 5 entries
        $history = array_slice($history, -4);
        $history[] = $log_entry;

        update_user_meta($user_id, 'rm_profile_picture_history', $history);
    }

    /**
     * Get user IP address
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return sanitize_text_field($ip);
    }

    /**
     * Get profile picture URL for a user
     * 
     * @param int $user_id User ID
     * @param string $size Image size
     * @return string Image URL
     */
    public static function get_user_profile_picture($user_id, $size = 'medium') {
        $attachment_id = get_user_meta($user_id, 'rm_profile_picture', true);

        if ($attachment_id) {
            $image_url = wp_get_attachment_image_url($attachment_id, $size);
            if ($image_url) {
                return $image_url;
            }
        }

        // Fallback to WordPress avatar
        return get_avatar_url($user_id, ['size' => 150]);
    }

    /**
     * Upload profile picture
     */
    public function upload_profile_picture() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rm_profile_picture_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'rm-panel-extensions')]);
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to upload a profile picture', 'rm-panel-extensions')]);
        }

        $user_id = get_current_user_id();

        // Verify user ID matches
        if (isset($_POST['user_id']) && intval($_POST['user_id']) !== $user_id) {
            wp_send_json_error(['message' => __('Invalid user ID', 'rm-panel-extensions')]);
        }

        // Check if file was uploaded
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => __('No file uploaded or upload error occurred', 'rm-panel-extensions')]);
        }

        $file = $_FILES['profile_picture'];

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = wp_check_filetype($file['name']);
        $mime_type = $file['type'];

        if (!in_array($mime_type, $allowed_types)) {
            wp_send_json_error(['message' => __('Invalid file type. Only JPG, PNG, and GIF are allowed', 'rm-panel-extensions')]);
        }

        // Validate file size (5MB max)
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $max_size) {
            wp_send_json_error(['message' => __('File size must be less than 5MB', 'rm-panel-extensions')]);
        }

        // Include required WordPress files
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Upload file to WordPress media library
        $attachment_id = media_handle_upload('profile_picture', 0, [], [
            'test_form' => false,
            'test_type' => true,
        ]);

        // Check for upload errors
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => $attachment_id->get_error_message()]);
        }

        // Set attachment as profile picture
        $old_attachment_id = get_user_meta($user_id, 'rm_profile_picture', true);
        update_user_meta($user_id, 'rm_profile_picture', $attachment_id);

        // ✨ NEW: Sync to FluentCRM if available and enabled
        $fluentcrm_synced = false;
        $settings = get_option('rm_panel_extensions_settings', []);
        $sync_enabled = isset($settings['sync_profile_to_fluentcrm']) ? $settings['sync_profile_to_fluentcrm'] : 1;

        if ($sync_enabled && class_exists('RM_Panel_FluentCRM_Helper')) {
            $fluentcrm_synced = RM_Panel_FluentCRM_Helper::update_contact_avatar($user_id, $attachment_id);
        }

        // Optionally delete old profile picture to save space
        if ($old_attachment_id && $old_attachment_id !== $attachment_id) {
            $this->maybe_delete_old_picture($old_attachment_id);
        }

        // Get the uploaded image URL
        $image_url = wp_get_attachment_image_url($attachment_id, 'medium');
        $full_image_url = wp_get_attachment_image_url($attachment_id, 'full');

        // Log the action
        $this->log_profile_picture_update($user_id, $attachment_id);

        // Return success response
        wp_send_json_success([
            'message' => __('Profile picture updated successfully!', 'rm-panel-extensions'),
            'url' => $image_url,
            'full_url' => $full_image_url,
            'attachment_id' => $attachment_id,
            'fluentcrm_synced' => $fluentcrm_synced // ✨ NEW: Indicate if synced to FluentCRM
        ]);
    }

    /**
     * Delete profile picture
     */
    public function delete_profile_picture() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rm_profile_picture_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'rm-panel-extensions')]);
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in', 'rm-panel-extensions')]);
        }

        $user_id = get_current_user_id();
        $attachment_id = get_user_meta($user_id, 'rm_profile_picture', true);

        if ($attachment_id) {
            // Delete user meta
            delete_user_meta($user_id, 'rm_profile_picture');

            // ✨ NEW: Remove from FluentCRM if available
            if (class_exists('RM_Panel_FluentCRM_Helper')) {
                RM_Panel_FluentCRM_Helper::remove_contact_avatar($user_id);
            }

            // Optionally delete the attachment
            $this->maybe_delete_old_picture($attachment_id);

            wp_send_json_success([
                'message' => __('Profile picture deleted successfully', 'rm-panel-extensions'),
                'default_url' => get_avatar_url($user_id, ['size' => 150])
            ]);
        } else {
            wp_send_json_error(['message' => __('No profile picture to delete', 'rm-panel-extensions')]);
        }
    }
}

// Initialize the handler
RM_Profile_Picture_Handler::get_instance();
