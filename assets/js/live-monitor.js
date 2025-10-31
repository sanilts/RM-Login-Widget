/**
 * RM Panel Extensions - Live Monitor JavaScript
 * Version: 1.1.0
 * Auto-refreshing live survey monitoring
 */

(function($) {
    'use strict';
    
    var RMLiveMonitor = {
        
        refreshInterval: null,
        
        /**
         * Initialize
         */
        init: function() {
            console.log('RM Live Monitor: Initializing...');
            
            // Initial load
            this.loadStats();
            this.loadActiveUsers();
            
            // Auto-refresh every 5 seconds
            this.refreshInterval = setInterval(function() {
                RMLiveMonitor.loadStats();
                RMLiveMonitor.loadActiveUsers();
            }, rmLiveMonitor.refresh_interval);
            
            // Cleanup on page unload
            $(window).on('beforeunload', function() {
                if (RMLiveMonitor.refreshInterval) {
                    clearInterval(RMLiveMonitor.refreshInterval);
                }
            });
        },
        
        /**
         * Load statistics
         */
        loadStats: function() {
            $.ajax({
                url: rmLiveMonitor.ajax_url,
                type: 'POST',
                data: {
                    action: 'rm_get_live_survey_stats',
                    nonce: rmLiveMonitor.nonce
                },
                success: function(response) {
                    if (response.success) {
                        RMLiveMonitor.updateStats(response.data);
                        RMLiveMonitor.updateActiveSurveys(response.data.active_surveys);
                        RMLiveMonitor.updateWaitingSurveys(response.data.waiting_surveys);
                        RMLiveMonitor.updateLastUpdate(response.data.timestamp);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('RM Live Monitor: Error loading stats', error);
                }
            });
        },
        
        /**
         * Update statistics cards
         */
        updateStats: function(data) {
            $('#rm-active-now').text(data.active_now);
            $('#rm-waiting-complete').text(data.waiting_complete);
            $('#rm-today-completed').text(data.today_completed);
            $('#rm-conversion-rate').text(data.conversion_rate + '%');
        },
        
        /**
         * Update active surveys table
         */
        updateActiveSurveys: function(surveys) {
            var $container = $('#rm-active-surveys-table');
            
            if (!surveys || surveys.length === 0) {
                $container.html(
                    '<div class="rm-empty-state">' +
                    '<div class="rm-empty-state-icon">😴</div>' +
                    '<p>No users are currently taking surveys</p>' +
                    '</div>'
                );
                return;
            }
            
            var html = '';
            $.each(surveys, function(index, survey) {
                var duration = RMLiveMonitor.calculateDuration(survey.start_time);
                var durationClass = duration > 10 ? 'rm-duration-danger' : (duration > 5 ? 'rm-duration-warning' : '');
                
                html += '<div class="rm-active-survey-row">';
                html += '<div class="rm-survey-user">';
                html += '<strong>' + RMLiveMonitor.escapeHtml(survey.display_name) + '</strong>';
                html += '<small>' + RMLiveMonitor.escapeHtml(survey.user_email || '') + '</small>';
                html += '</div>';
                html += '<div class="rm-survey-title">' + RMLiveMonitor.escapeHtml(survey.survey_title) + '</div>';
                html += '<div class="rm-survey-duration ' + durationClass + '">' + duration + ' min</div>';
                html += '<div class="rm-survey-status">';
                html += '<span class="rm-status-badge rm-status-active">🔴 Active</span>';
                html += '</div>';
                html += '</div>';
            });
            
            $container.html(html);
        },
        
        /**
         * Update waiting surveys table
         */
        updateWaitingSurveys: function(surveys) {
            var $container = $('#rm-waiting-surveys-table');
            
            if (!surveys || surveys.length === 0) {
                $container.html(
                    '<div class="rm-empty-state">' +
                    '<div class="rm-empty-state-icon">✅</div>' +
                    '<p>No surveys waiting for completion</p>' +
                    '</div>'
                );
                return;
            }
            
            var html = '';
            $.each(surveys, function(index, survey) {
                var waitingMinutes = parseInt(survey.minutes_waiting);
                var waitingClass = waitingMinutes > 120 ? 'rm-duration-danger' : (waitingMinutes > 60 ? 'rm-duration-warning' : '');
                
                html += '<div class="rm-waiting-survey-row">';
                html += '<div class="rm-survey-user">';
                html += '<strong>' + RMLiveMonitor.escapeHtml(survey.display_name) + '</strong>';
                html += '<small>' + RMLiveMonitor.escapeHtml(survey.user_email || '') + '</small>';
                html += '</div>';
                html += '<div class="rm-survey-title">' + RMLiveMonitor.escapeHtml(survey.survey_title) + '</div>';
                html += '<div class="rm-survey-duration ' + waitingClass + '">' + RMLiveMonitor.formatWaitingTime(waitingMinutes) + '</div>';
                html += '<div class="rm-survey-status">';
                html += '<span class="rm-status-badge rm-status-started">⏳ Waiting</span>';
                html += '</div>';
                html += '</div>';
            });
            
            $container.html(html);
        },
        
        /**
         * Load active users
         */
        loadActiveUsers: function() {
            $.ajax({
                url: rmLiveMonitor.ajax_url,
                type: 'POST',
                data: {
                    action: 'rm_get_active_users',
                    nonce: rmLiveMonitor.nonce
                },
                success: function(response) {
                    if (response.success) {
                        RMLiveMonitor.updateActiveUsers(response.data.active_users);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('RM Live Monitor: Error loading active users', error);
                }
            });
        },
        
        /**
         * Update active users list
         */
        updateActiveUsers: function(users) {
            var $container = $('#rm-active-users-list');
            
            if (!users || users.length === 0) {
                $container.html(
                    '<div class="rm-empty-state">' +
                    '<div class="rm-empty-state-icon">👤</div>' +
                    '<p>No users currently active on the site</p>' +
                    '</div>'
                );
                return;
            }
            
            var html = '';
            $.each(users, function(index, user) {
                var lastActive = RMLiveMonitor.timeAgo(user.last_activity);
                
                html += '<div class="rm-active-user-item">';
                html += '<div class="rm-user-avatar">👤</div>';
                html += '<div class="rm-user-info">';
                html += '<strong>' + RMLiveMonitor.escapeHtml(user.display_name) + '</strong>';
                html += '<small>Last active: ' + lastActive + '</small>';
                html += '</div>';
                html += '</div>';
            });
            
            $container.html(html);
        },
        
        /**
         * Update last update timestamp
         */
        updateLastUpdate: function(timestamp) {
            var now = new Date();
            var formatted = now.toLocaleTimeString();
            $('#rm-last-update-time').text(formatted);
        },
        
        /**
         * Calculate duration in minutes
         */
        calculateDuration: function(startTime) {
            var start = new Date(startTime.replace(/-/g, '/'));
            var now = new Date();
            var diff = Math.floor((now - start) / 1000 / 60);
            return diff;
        },
        
        /**
         * Format waiting time
         */
        formatWaitingTime: function(minutes) {
            if (minutes < 60) {
                return minutes + ' min';
            } else if (minutes < 1440) {
                var hours = Math.floor(minutes / 60);
                return hours + ' hour' + (hours > 1 ? 's' : '');
            } else {
                var days = Math.floor(minutes / 1440);
                return days + ' day' + (days > 1 ? 's' : '');
            }
        },
        
        /**
         * Time ago formatting
         */
        timeAgo: function(timestamp) {
            var date = new Date(timestamp.replace(/-/g, '/'));
            var now = new Date();
            var seconds = Math.floor((now - date) / 1000);
            
            if (seconds < 60) return 'just now';
            if (seconds < 120) return '1 minute ago';
            if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
            if (seconds < 7200) return '1 hour ago';
            return Math.floor(seconds / 3600) + ' hours ago';
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            if (!text) return '';
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        RMLiveMonitor.init();
    });
    
})(jQuery);