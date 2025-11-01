<?php
/**
 * Survey Database Upgrade to v1.2.0
 * File: modules/survey/class-survey-database-upgrade-v1.2.0.php
 * 
 * Adds survey manager tracking and enhanced status management
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Survey_Database_Upgrade_v120 {
    
    public function __construct() {
        add_action('plugins_loaded', [$this, 'check_and_upgrade']);
    }
    
    public function check_and_upgrade() {
        $current_version = get_option('rm_panel_survey_db_version', '1.0.0');
        
        if (version_compare($current_version, '1.2.0', '<')) {
            $this->upgrade_to_1_2_0();
        }
    }
    
    private function upgrade_to_1_2_0() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        
        // Check if columns exist before adding
        $columns = $wpdb->get_col("DESCRIBE {$table_name}");
        
        // Add waiting_since column if it doesn't exist
        if (!in_array('waiting_since', $columns)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN waiting_since DATETIME DEFAULT NULL AFTER start_time");
        }
        
        // Add last_reminder_sent column if it doesn't exist
        if (!in_array('last_reminder_sent', $columns)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN last_reminder_sent DATETIME DEFAULT NULL AFTER waiting_since");
        }
        
        // Add survey_paused_at column if it doesn't exist
        if (!in_array('survey_paused_at', $columns)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN survey_paused_at DATETIME DEFAULT NULL AFTER admin_notes");
        }
        
        // Update existing 'started' records to have waiting_since
        $wpdb->query("
            UPDATE {$table_name} 
            SET waiting_since = start_time 
            WHERE status = 'started' 
            AND waiting_since IS NULL
        ");
        
        // Update version
        update_option('rm_panel_survey_db_version', '1.2.0');
        
        // Log upgrade
        error_log('RM Survey Module: Database upgraded to version 1.2.0');
    }
}

// Initialize
new RM_Survey_Database_Upgrade_v120();
