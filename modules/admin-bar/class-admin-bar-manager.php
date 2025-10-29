<?php
/**
 * Admin Bar Manager
 * 
 * Manages WordPress admin bar visibility based on user roles
 * 
 * @package RM_Panel_Extensions
 * @since 1.0.4
 * @version 1.0.4.1 (FIXED - Corrected inverted logic)
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class RM_Panel_Admin_Bar_Manager
 */
class RM_Panel_Admin_Bar_Manager {

    /**
     * Instance
     */
    private static $instance = null;

    /**
     * Get instance (Singleton)
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
        // Hide admin bar based on role
        add_action('after_setup_theme', [$this, 'manage_admin_bar']);
        
        // Add CSS to hide admin bar for certain roles
        add_action('wp_head', [$this, 'hide_admin_bar_css'], 999);
        add_action('admin_head', [$this, 'hide_admin_bar_css'], 999);
    }

    /**
     * Manage admin bar visibility based on role
     * 
     * FIXED: Now explicitly enables OR disables admin bar
     */
    public function manage_admin_bar() {
        // Get settings
        $settings = $this->get_admin_bar_settings();
        
        // If no settings exist, use defaults (admins only)
        if (empty($settings)) {
            $settings = self::get_default_settings();
        }

        // Check if current user should see admin bar
        if ($this->should_show_admin_bar($settings)) {
            // EXPLICITLY ENABLE admin bar
            show_admin_bar(true);
            add_filter('show_admin_bar', '__return_true');
        } else {
            // EXPLICITLY DISABLE admin bar
            show_admin_bar(false);
            add_filter('show_admin_bar', '__return_false');
        }
    }

    /**
     * Check if current user should see admin bar based on their role
     * 
     * @param array $settings Admin bar settings
     * @return bool True if should show, false if should hide
     */
    private function should_show_admin_bar($settings) {
        // Get current user
        $current_user = wp_get_current_user();
        
        // If not logged in, don't show admin bar
        if (!is_user_logged_in()) {
            return false;
        }

        // Get user roles
        $user_roles = $current_user->roles;
        
        // If user has no roles, don't show admin bar
        if (empty($user_roles)) {
            return false;
        }

        // Check if any of the user's roles are allowed to see admin bar
        foreach ($user_roles as $role) {
            // If this role is allowed to see admin bar
            if (isset($settings[$role]) && $settings[$role] === '1') {
                return true;
            }
        }

        // Default: hide admin bar
        return false;
    }

    /**
     * Get admin bar settings from database
     * 
     * @return array Settings array with role => enabled pairs
     */
    private function get_admin_bar_settings() {
        $settings = get_option('rm_panel_admin_bar_settings', []);
        
        // If empty, return defaults
        if (empty($settings)) {
            return self::get_default_settings();
        }
        
        return $settings;
    }

    /**
     * Add CSS to hide admin bar completely
     * This ensures no visual artifacts remain
     */
    public function hide_admin_bar_css() {
        // Only add CSS if admin bar should be hidden
        $settings = $this->get_admin_bar_settings();
        
        if (!$this->should_show_admin_bar($settings)) {
            ?>
            <style type="text/css">
                /* Hide admin bar completely */
                #wpadminbar {
                    display: none !important;
                }
                
                /* Remove top margin added by admin bar */
                html {
                    margin-top: 0 !important;
                }
                
                body.admin-bar {
                    margin-top: 0 !important;
                }
                
                /* Fix for Elementor editor */
                body.elementor-editor-active {
                    margin-top: 0 !important;
                }
            </style>
            <?php
        }
    }

    /**
     * Get all available WordPress roles
     * 
     * @return array Array of role objects with name and display_name
     */
    public static function get_all_roles() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        $roles = [];
        
        foreach ($wp_roles->roles as $role_key => $role_data) {
            $roles[$role_key] = [
                'name' => $role_key,
                'display_name' => $role_data['name']
            ];
        }
        
        return $roles;
    }

    /**
     * Save admin bar settings
     * 
     * @param array $settings Settings to save
     * @return bool True on success, false on failure
     */
    public static function save_settings($settings) {
        // Validate settings
        $validated = [];
        
        // Get all available roles
        $all_roles = self::get_all_roles();
        
        foreach ($all_roles as $role_key => $role_data) {
            // Check if this role is enabled
            $validated[$role_key] = isset($settings[$role_key]) ? '1' : '0';
        }
        
        // Save to database
        return update_option('rm_panel_admin_bar_settings', $validated);
    }

    /**
     * Get default settings
     * By default, only administrators can see the admin bar
     * 
     * @return array Default settings
     */
    public static function get_default_settings() {
        return [
            'administrator' => '1', // Admins can see
            'editor' => '0',        // Editors cannot see
            'author' => '0',        // Authors cannot see
            'contributor' => '0',   // Contributors cannot see
            'subscriber' => '0'     // Subscribers cannot see
        ];
    }

    /**
     * Reset settings to default
     * 
     * @return bool True on success
     */
    public static function reset_to_defaults() {
        return update_option('rm_panel_admin_bar_settings', self::get_default_settings());
    }
}

// Initialize the module
RM_Panel_Admin_Bar_Manager::get_instance();