<?php
/**
 * Survey Database Upgrade Handler
 * File: modules/survey/class-survey-database-upgrade.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Survey_Database_Upgrade {
    
    public function __construct() {
        add_action('plugins_loaded', [$this, 'check_and_upgrade']);
    }
    
    public function check_and_upgrade() {
        $current_version = get_option('rm_panel_survey_db_version', '1.0.0');
        
        if (version_compare($current_version, '1.1.0', '<')) {
            $this->upgrade_to_1_1_0();
        }
    }
    
    private function upgrade_to_1_1_0() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        
        // Add new columns
        $columns_to_add = [
            "ADD COLUMN approval_status varchar(20) DEFAULT 'pending' AFTER completion_status",
            "ADD COLUMN approved_by bigint(20) DEFAULT NULL AFTER approval_status",
            "ADD COLUMN approval_date datetime DEFAULT NULL AFTER approved_by",
            "ADD COLUMN country varchar(100) DEFAULT NULL AFTER approval_date",
            "ADD COLUMN return_time datetime DEFAULT NULL AFTER country",
            "ADD COLUMN admin_notes text DEFAULT NULL AFTER return_time"
        ];
        
        foreach ($columns_to_add as $column) {
            $wpdb->query("ALTER TABLE $table_name $column");
        }
        
        // Update version
        update_option('rm_panel_survey_db_version', '1.1.0');
    }
}

new RM_Survey_Database_Upgrade();