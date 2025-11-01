<?php
/**
 * Enhanced Survey Admin Columns
 * File: modules/survey/class-survey-admin-columns-enhanced.php
 * 
 * Add status indicators and manager information to admin list
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Survey_Admin_Columns_Enhanced {
    
    public function __construct() {
        add_filter('manage_rm_survey_posts_columns', [$this, 'add_custom_columns']);
        add_action('manage_rm_survey_posts_custom_column', [$this, 'render_custom_columns'], 10, 2);
        add_filter('manage_edit-rm_survey_sortable_columns', [$this, 'make_columns_sortable']);
    }
    
    /**
     * Add custom columns
     */
    public function add_custom_columns($columns) {
        $date = $columns['date'];
        unset($columns['date']);
        
        $columns['survey_manager'] = __('Manager', 'rm-panel-extensions');
        $columns['responses_breakdown'] = __('Response Status', 'rm-panel-extensions');
        $columns['auto_pause_status'] = __('Auto-Pause', 'rm-panel-extensions');
        
        $columns['date'] = $date;
        
        return $columns;
    }
    
    /**
     * Render custom columns
     */
    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'survey_manager':
                $this->render_manager_column($post_id);
                break;
                
            case 'responses_breakdown':
                $this->render_responses_breakdown($post_id);
                break;
                
            case 'auto_pause_status':
                $this->render_autopause_status($post_id);
                break;
        }
    }
    
    /**
     * Render manager column
     */
    private function render_manager_column($post_id) {
        $manager_id = get_post_meta($post_id, '_rm_survey_manager_id', true);
        
        if ($manager_id) {
            $manager = get_userdata($manager_id);
            if ($manager) {
                echo '<div class="survey-manager-badge">';
                echo '<span class="dashicons dashicons-admin-users"></span> ';
                echo esc_html($manager->display_name);
                echo '</div>';
                return;
            }
        }
        
        echo '<span style="color: #999;">—</span>';
    }
    
    /**
     * Render responses breakdown
     */
    private function render_responses_breakdown($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rm_survey_responses';
        
        // Get counts for each status
        $counts = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                status,
                completion_status,
                COUNT(*) as count
             FROM {$table_name}
             WHERE survey_id = %d
             GROUP BY status, completion_status",
            $post_id
        ));
        
        $stats = [
            'waiting' => 0,
            'not_complete' => 0,
            'success' => 0,
            'quota_complete' => 0,
            'disqualified' => 0
        ];
        
        foreach ($counts as $row) {
            if ($row->status === 'waiting_to_complete') {
                $stats['waiting'] += $row->count;
            } elseif ($row->status === 'not_complete') {
                $stats['not_complete'] += $row->count;
            } elseif ($row->status === 'completed') {
                switch ($row->completion_status) {
                    case 'success':
                        $stats['success'] += $row->count;
                        break;
                    case 'quota_complete':
                        $stats['quota_complete'] += $row->count;
                        break;
                    case 'disqualified':
                        $stats['disqualified'] += $row->count;
                        break;
                }
            }
        }
        
        ?>
        <div class="response-breakdown">
            <div class="breakdown-item" title="<?php _e('Waiting to Complete', 'rm-panel-extensions'); ?>">
                <span class="dashicons dashicons-clock" style="color: #f0ad4e;"></span>
                <strong><?php echo $stats['waiting']; ?></strong>
            </div>
            <div class="breakdown-item" title="<?php _e('Not Complete', 'rm-panel-extensions'); ?>">
                <span class="dashicons dashicons-dismiss" style="color: #999;"></span>
                <strong><?php echo $stats['not_complete']; ?></strong>
            </div>
            <div class="breakdown-item" title="<?php _e('Success', 'rm-panel-extensions'); ?>">
                <span class="dashicons dashicons-yes" style="color: #46b450;"></span>
                <strong><?php echo $stats['success']; ?></strong>
            </div>
            <div class="breakdown-item" title="<?php _e('Quota Full', 'rm-panel-extensions'); ?>">
                <span class="dashicons dashicons-warning" style="color: #dc3545;"></span>
                <strong><?php echo $stats['quota_complete']; ?></strong>
            </div>
            <div class="breakdown-item" title="<?php _e('Disqualified', 'rm-panel-extensions'); ?>">
                <span class="dashicons dashicons-no" style="color: #6c757d;"></span>
                <strong><?php echo $stats['disqualified']; ?></strong>
            </div>
        </div>
        
        <style>
            .response-breakdown {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }
            .breakdown-item {
                display: flex;
                align-items: center;
                gap: 3px;
                padding: 2px 6px;
                background: #f5f5f5;
                border-radius: 3px;
                font-size: 12px;
            }
            .breakdown-item .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }
            .survey-manager-badge {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 3px 8px;
                background: #e7f3ff;
                border-radius: 3px;
                font-size: 12px;
            }
            .survey-manager-badge .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }
        </style>
        <?php
    }
    
    /**
     * Render auto-pause status
     */
    private function render_autopause_status($post_id) {
        $notify_enabled = get_post_meta($post_id, '_rm_survey_notify_quotafull', true);
        $paused_at = get_post_meta($post_id, '_rm_survey_paused_at', true);
        $paused_reason = get_post_meta($post_id, '_rm_survey_paused_reason', true);
        
        if ($notify_enabled === '1') {
            echo '<span class="autopause-enabled" title="' . esc_attr__('Auto-pause enabled', 'rm-panel-extensions') . '">';
            echo '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>';
            echo '</span>';
            
            if ($paused_at) {
                echo '<div style="font-size: 11px; color: #dc3545; margin-top: 3px;">';
                echo '<strong>' . __('Paused:', 'rm-panel-extensions') . '</strong> ';
                echo date_i18n('M j, H:i', strtotime($paused_at));
                if ($paused_reason) {
                    echo '<br><em>(' . esc_html(ucfirst(str_replace('_', ' ', $paused_reason))) . ')</em>';
                }
                echo '</div>';
            }
        } else {
            echo '<span style="color: #999;">—</span>';
        }
    }
    
    /**
     * Make columns sortable
     */
    public function make_columns_sortable($columns) {
        $columns['survey_manager'] = 'survey_manager';
        return $columns;
    }
}

// Initialize
new RM_Survey_Admin_Columns_Enhanced();
