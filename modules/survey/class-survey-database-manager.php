<?php
/**
 * Survey Database Manager - Unified & Optimized
 * 
 * This class consolidates ALL database operations for the survey module:
 * - Initial table creation (v1.0.0 schema)
 * - Schema upgrades (v1.0.0 → v1.1.0 → v1.2.0)
 * - Future migrations
 * - Database maintenance
 * - Schema verification
 * 
 * @package RM_Panel_Extensions
 * @subpackage Survey
 * @version 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Survey_Database_Manager {
    
    /**
     * Current database version
     */
    const DB_VERSION = '1.2.0';
    
    /**
     * Database version option name
     */
    const VERSION_OPTION = 'rm_panel_survey_db_version';
    
    /**
     * Table name (without prefix)
     */
    const TABLE_NAME = 'rm_survey_responses';
    
    /**
     * Full table name (with prefix)
     */
    private $table_name;
    
    /**
     * WordPress database object
     */
    private $wpdb;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . self::TABLE_NAME;
        
        // Hook into plugins_loaded to check for updates
        add_action('plugins_loaded', [$this, 'check_and_upgrade'], 5);
        
        // Admin notice for upgrade status
        add_action('admin_notices', [$this, 'show_upgrade_notices']);
    }
    
    /**
     * Check database version and upgrade if needed
     */
    public function check_and_upgrade() {
        $installed_version = get_option(self::VERSION_OPTION, '0.0.0');
        
        // If database doesn't exist or is outdated
        if (version_compare($installed_version, self::DB_VERSION, '<')) {
            $this->run_upgrades($installed_version);
        }
    }
    
    /**
     * Run database upgrades
     * 
     * @param string $from_version Starting version
     */
    private function run_upgrades($from_version) {
        $success = true;
        $errors = [];
        
        try {
            // Fresh install (no existing database)
            if (version_compare($from_version, '1.0.0', '<')) {
                if (!$this->create_initial_table()) {
                    $errors[] = 'Failed to create initial table';
                    $success = false;
                } else {
                    $from_version = '1.0.0';
                }
            }
            
            // Upgrade to v1.1.0 (Approval system)
            if (version_compare($from_version, '1.1.0', '<') && $success) {
                if (!$this->upgrade_to_1_1_0()) {
                    $errors[] = 'Failed to upgrade to v1.1.0';
                    $success = false;
                } else {
                    $from_version = '1.1.0';
                }
            }
            
            // Upgrade to v1.2.0 (Enhanced tracking)
            if (version_compare($from_version, '1.2.0', '<') && $success) {
                if (!$this->upgrade_to_1_2_0()) {
                    $errors[] = 'Failed to upgrade to v1.2.0';
                    $success = false;
                } else {
                    $from_version = '1.2.0';
                }
            }
            
            // Update version option if all upgrades succeeded
            if ($success) {
                update_option(self::VERSION_OPTION, self::DB_VERSION);
                $this->log_success("Database upgraded from {$from_version} to " . self::DB_VERSION);
                
                // Store success message for admin notice
                set_transient('rm_survey_db_upgrade_success', [
                    'from' => $from_version,
                    'to' => self::DB_VERSION
                ], 60);
            } else {
                $this->log_error('Database upgrade failed: ' . implode(', ', $errors));
                
                // Store error message for admin notice
                set_transient('rm_survey_db_upgrade_error', implode(', ', $errors), 300);
            }
            
        } catch (Exception $e) {
            $this->log_error('Database upgrade exception: ' . $e->getMessage());
            set_transient('rm_survey_db_upgrade_error', $e->getMessage(), 300);
        }
    }
    
    /**
     * Create initial database table (v1.0.0)
     * 
     * Base schema with essential tracking fields
     * 
     * @return bool Success status
     */
    private function create_initial_table() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            survey_id bigint(20) NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'started',
            completion_status varchar(50) DEFAULT NULL,
            start_time datetime DEFAULT CURRENT_TIMESTAMP,
            completion_time datetime DEFAULT NULL,
            response_data longtext DEFAULT NULL,
            ip_address varchar(100) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            referrer_url text DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY survey_id (survey_id),
            KEY status (status),
            KEY completion_status (completion_status),
            KEY start_time (start_time),
            KEY created_at (created_at),
            UNIQUE KEY user_survey (user_id, survey_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        if (!empty($result)) {
            $this->log_success('Initial database table created (v1.0.0)');
            return true;
        }
        
        // Verify table was created
        $table_exists = $this->wpdb->get_var(
            $this->wpdb->prepare("SHOW TABLES LIKE %s", $this->table_name)
        );
        
        if ($table_exists === $this->table_name) {
            $this->log_success('Initial database table verified (v1.0.0)');
            return true;
        }
        
        $this->log_error('Failed to create initial database table');
        return false;
    }
    
    /**
     * Upgrade to v1.1.0 - Add approval system columns
     * 
     * New features:
     * - Approval status tracking
     * - Admin approval workflow
     * - Country tracking
     * - Return time tracking
     * - Admin notes
     * 
     * @return bool Success status
     */
    private function upgrade_to_1_1_0() {
        $columns = $this->get_table_columns();
        $success = true;
        
        // Define new columns for v1.1.0
        $new_columns = [
            'approval_status' => "ADD COLUMN approval_status varchar(20) DEFAULT 'pending' AFTER completion_status",
            'approved_by' => "ADD COLUMN approved_by bigint(20) DEFAULT NULL AFTER approval_status",
            'approval_date' => "ADD COLUMN approval_date datetime DEFAULT NULL AFTER approved_by",
            'country' => "ADD COLUMN country varchar(100) DEFAULT NULL AFTER approval_date",
            'return_time' => "ADD COLUMN return_time datetime DEFAULT NULL AFTER country",
            'admin_notes' => "ADD COLUMN admin_notes text DEFAULT NULL AFTER return_time"
        ];
        
        // Add each column if it doesn't exist
        foreach ($new_columns as $column_name => $sql) {
            if (!in_array($column_name, $columns)) {
                $result = $this->wpdb->query("ALTER TABLE {$this->table_name} $sql");
                if ($result === false) {
                    $this->log_error("Failed to add column: $column_name");
                    $success = false;
                } else {
                    $this->log_success("Added column: $column_name");
                }
            }
        }
        
        // Create indexes for new columns
        if ($success) {
            $this->create_index('approval_status', 'approval_status');
            $this->create_index('country', 'country');
        }
        
        if ($success) {
            $this->log_success('Upgraded to v1.1.0 (Approval System)');
        }
        
        return $success;
    }
    
    /**
     * Upgrade to v1.2.0 - Add enhanced tracking columns
     * 
     * New features:
     * - Waiting to complete status tracking
     * - Reminder system support
     * - Survey pause tracking
     * 
     * @return bool Success status
     */
    private function upgrade_to_1_2_0() {
        $columns = $this->get_table_columns();
        $success = true;
        
        // Define new columns for v1.2.0
        $new_columns = [
            'waiting_since' => "ADD COLUMN waiting_since datetime DEFAULT NULL AFTER start_time",
            'last_reminder_sent' => "ADD COLUMN last_reminder_sent datetime DEFAULT NULL AFTER waiting_since",
            'survey_paused_at' => "ADD COLUMN survey_paused_at datetime DEFAULT NULL AFTER admin_notes"
        ];
        
        // Add each column if it doesn't exist
        foreach ($new_columns as $column_name => $sql) {
            if (!in_array($column_name, $columns)) {
                $result = $this->wpdb->query("ALTER TABLE {$this->table_name} $sql");
                if ($result === false) {
                    $this->log_error("Failed to add column: $column_name");
                    $success = false;
                } else {
                    $this->log_success("Added column: $column_name");
                }
            }
        }
        
        // Migrate existing 'started' records to have waiting_since
        if ($success) {
            $migrated = $this->wpdb->query("
                UPDATE {$this->table_name} 
                SET waiting_since = start_time 
                WHERE status = 'started' 
                AND waiting_since IS NULL
            ");
            
            if ($migrated !== false) {
                $this->log_success("Migrated $migrated existing records to waiting status");
            }
        }
        
        // Create indexes for new columns
        if ($success) {
            $this->create_index('waiting_since', 'waiting_since');
        }
        
        if ($success) {
            $this->log_success('Upgraded to v1.2.0 (Enhanced Tracking)');
        }
        
        return $success;
    }
    
    /**
     * Get list of columns in the table
     * 
     * @return array Column names
     */
    private function get_table_columns() {
        $columns = $this->wpdb->get_col("DESCRIBE {$this->table_name}");
        return $columns ?: [];
    }
    
    /**
     * Create an index on a column (if it doesn't exist)
     * 
     * @param string $index_name Index name
     * @param string $column_name Column name
     * @return bool Success status
     */
    private function create_index($index_name, $column_name) {
        // Check if index already exists
        $indexes = $this->wpdb->get_results("SHOW INDEX FROM {$this->table_name}");
        $index_exists = false;
        
        foreach ($indexes as $index) {
            if ($index->Key_name === $index_name) {
                $index_exists = true;
                break;
            }
        }
        
        if (!$index_exists) {
            $result = $this->wpdb->query(
                "ALTER TABLE {$this->table_name} ADD INDEX {$index_name} ({$column_name})"
            );
            
            if ($result !== false) {
                $this->log_success("Created index: $index_name");
                return true;
            } else {
                $this->log_error("Failed to create index: $index_name");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Verify table schema integrity
     * 
     * @return array Status report
     */
    public function verify_schema() {
        $report = [
            'table_exists' => false,
            'required_columns' => [],
            'missing_columns' => [],
            'indexes' => [],
            'status' => 'unknown'
        ];
        
        // Check if table exists
        $table_exists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $this->table_name
            )
        );
        
        $report['table_exists'] = ($table_exists === $this->table_name);
        
        if (!$report['table_exists']) {
            $report['status'] = 'missing';
            return $report;
        }
        
        // Get current columns
        $current_columns = $this->get_table_columns();
        
        // Define required columns for current version
        $required_columns = [
            // v1.0.0 columns
            'id', 'user_id', 'survey_id', 'status', 'completion_status',
            'start_time', 'completion_time', 'response_data',
            'ip_address', 'user_agent', 'referrer_url', 'notes',
            'created_at', 'updated_at',
            // v1.1.0 columns
            'approval_status', 'approved_by', 'approval_date',
            'country', 'return_time', 'admin_notes',
            // v1.2.0 columns
            'waiting_since', 'last_reminder_sent', 'survey_paused_at'
        ];
        
        $report['required_columns'] = $required_columns;
        $report['missing_columns'] = array_diff($required_columns, $current_columns);
        
        // Get indexes
        $indexes = $this->wpdb->get_results("SHOW INDEX FROM {$this->table_name}");
        foreach ($indexes as $index) {
            $report['indexes'][] = $index->Key_name;
        }
        
        // Determine overall status
        if (empty($report['missing_columns'])) {
            $report['status'] = 'healthy';
        } else {
            $report['status'] = 'incomplete';
        }
        
        return $report;
    }
    
    /**
     * Get database statistics
     * 
     * @return array Statistics
     */
    public function get_statistics() {
        $stats = [
            'total_responses' => 0,
            'by_status' => [],
            'by_completion_status' => [],
            'by_approval_status' => [],
            'table_size' => '0 KB'
        ];
        
        // Total responses
        $stats['total_responses'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name}"
        );
        
        // By status
        $status_counts = $this->wpdb->get_results(
            "SELECT status, COUNT(*) as count 
             FROM {$this->table_name} 
             GROUP BY status"
        );
        
        foreach ($status_counts as $row) {
            $stats['by_status'][$row->status] = $row->count;
        }
        
        // By completion status
        $completion_counts = $this->wpdb->get_results(
            "SELECT completion_status, COUNT(*) as count 
             FROM {$this->table_name} 
             WHERE completion_status IS NOT NULL
             GROUP BY completion_status"
        );
        
        foreach ($completion_counts as $row) {
            $stats['by_completion_status'][$row->completion_status] = $row->count;
        }
        
        // By approval status
        $approval_counts = $this->wpdb->get_results(
            "SELECT approval_status, COUNT(*) as count 
             FROM {$this->table_name} 
             WHERE approval_status IS NOT NULL
             GROUP BY approval_status"
        );
        
        foreach ($approval_counts as $row) {
            $stats['by_approval_status'][$row->approval_status] = $row->count;
        }
        
        // Table size
        $table_size = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT 
                    ROUND((data_length + index_length) / 1024, 2) AS size_kb
                 FROM information_schema.TABLES 
                 WHERE table_schema = %s 
                 AND table_name = %s",
                DB_NAME,
                $this->table_name
            )
        );
        
        if ($table_size) {
            $stats['table_size'] = $table_size->size_kb . ' KB';
        }
        
        return $stats;
    }
    
    /**
     * Repair table if corrupted
     * 
     * @return bool Success status
     */
    public function repair_table() {
        $result = $this->wpdb->query("REPAIR TABLE {$this->table_name}");
        
        if ($result !== false) {
            $this->log_success('Table repaired successfully');
            return true;
        } else {
            $this->log_error('Failed to repair table');
            return false;
        }
    }
    
    /**
     * Optimize table
     * 
     * @return bool Success status
     */
    public function optimize_table() {
        $result = $this->wpdb->query("OPTIMIZE TABLE {$this->table_name}");
        
        if ($result !== false) {
            $this->log_success('Table optimized successfully');
            return true;
        } else {
            $this->log_error('Failed to optimize table');
            return false;
        }
    }
    
    /**
     * Show admin notices for upgrade status
     */
    public function show_upgrade_notices() {
        // Success notice
        $success = get_transient('rm_survey_db_upgrade_success');
        if ($success) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e('RM Panel Survey Database:', 'rm-panel-extensions'); ?></strong>
                    <?php printf(
                        __('Successfully upgraded from version %s to %s', 'rm-panel-extensions'),
                        esc_html($success['from']),
                        esc_html($success['to'])
                    ); ?>
                </p>
            </div>
            <?php
            delete_transient('rm_survey_db_upgrade_success');
        }
        
        // Error notice
        $error = get_transient('rm_survey_db_upgrade_error');
        if ($error) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php _e('RM Panel Survey Database Error:', 'rm-panel-extensions'); ?></strong>
                    <?php echo esc_html($error); ?>
                </p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=rm-survey-database'); ?>" class="button">
                        <?php _e('View Database Status', 'rm-panel-extensions'); ?>
                    </a>
                </p>
            </div>
            <?php
            delete_transient('rm_survey_db_upgrade_error');
        }
    }
    
    /**
     * Log success message
     * 
     * @param string $message Message to log
     */
    private function log_success($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('RM Survey DB [SUCCESS]: ' . $message);
        }
    }
    
    /**
     * Log error message
     * 
     * @param string $message Message to log
     */
    private function log_error($message) {
        error_log('RM Survey DB [ERROR]: ' . $message);
    }
    
    /**
     * Get current database version
     * 
     * @return string Version number
     */
    public static function get_current_version() {
        return get_option(self::VERSION_OPTION, '0.0.0');
    }
    
    /**
     * Check if database needs upgrade
     * 
     * @return bool True if upgrade needed
     */
    public static function needs_upgrade() {
        $current = self::get_current_version();
        return version_compare($current, self::DB_VERSION, '<');
    }
    
    /**
     * Force a database upgrade (admin use only)
     * 
     * @return bool Success status
     */
    public function force_upgrade() {
        delete_option(self::VERSION_OPTION);
        $this->check_and_upgrade();
        return (self::get_current_version() === self::DB_VERSION);
    }
}

// Initialize the database manager
new RM_Survey_Database_Manager();