/**
 * Survey Tracking JavaScript
 * Handles survey start/completion tracking and callbacks
 * @package RM_Panel_Extensions
 * @version 2.1.0
 */

(function($) {
    'use strict';

    /**
     * Survey Tracking Manager
     */
    const RMSurveyTracking = {
        
        /**
         * Configuration
         */
        config: {
            storageKey: 'rm_survey_response_id',
            messageTimeout: 5000
        },

        /**
         * Initialize tracking
         */
        init: function() {
            this.bindEvents();
            this.trackSurveyStart();
            this.listenForCallbacks();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Track when user clicks on survey button
            $('.rm-survey-button, .survey-button, .survey-card-button').on('click', (e) => {
                const $button = $(e.currentTarget);
                const surveyId = $button.data('survey-id');

                if (surveyId) {
                    this.startSurvey(surveyId);
                }
            });
        },

        /**
         * Track survey start on single survey page
         */
        trackSurveyStart: function() {
            if (!$('body').hasClass('single-rm_survey')) {
                return;
            }

            const surveyId = $('body').attr('data-survey-id') || this.getSurveyIdFromUrl();
            
            if (surveyId) {
                this.startSurvey(surveyId);
            }
        },

        /**
         * Get survey ID from URL or post
         */
        getSurveyIdFromUrl: function() {
            // Try to get from URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const surveyId = urlParams.get('survey_id');
            
            if (surveyId) {
                return parseInt(surveyId, 10);
            }

            // Try to get from post ID in body classes
            const bodyClasses = $('body').attr('class');
            const match = bodyClasses.match(/postid-(\d+)/);
            
            return match ? parseInt(match[1], 10) : null;
        },

        /**
         * Start survey tracking
         */
        startSurvey: function(surveyId) {
            if (!rm_survey_ajax || !rm_survey_ajax.is_logged_in) {
                return;
            }

            $.ajax({
                url: rm_survey_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rm_start_survey',
                    survey_id: surveyId,
                    nonce: rm_survey_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        console.log('Survey tracking started:', surveyId);
                        
                        // Store response ID
                        if (response.data && response.data.response_id) {
                            sessionStorage.setItem(
                                this.config.storageKey, 
                                response.data.response_id
                            );
                        }
                    } else {
                        console.warn('Survey start failed:', response.data?.message);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Survey start error:', error);
                }
            });
        },

        /**
         * Listen for survey completion messages from iframe
         */
        listenForCallbacks: function() {
            window.addEventListener('message', (e) => {
                if (!e.data || e.data.type !== 'survey_complete') {
                    return;
                }

                this.completeSurvey(e.data);
            });
        },

        /**
         * Complete survey tracking
         */
        completeSurvey: function(data) {
            const surveyId = data.survey_id;
            const status = data.status || 'success';
            const responseData = data.response_data || null;

            if (!surveyId) {
                console.error('Survey ID missing from completion data');
                return;
            }

            $.ajax({
                url: rm_survey_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rm_complete_survey',
                    survey_id: surveyId,
                    completion_status: status,
                    response_data: responseData,
                    nonce: rm_survey_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        console.log('Survey completed:', status);
                        this.showCompletionMessage(status, surveyId);
                        sessionStorage.removeItem(this.config.storageKey);
                    } else {
                        console.error('Survey completion failed:', response.data?.message);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Survey completion error:', error);
                }
            });
        },

        /**
         * Show completion message
         */
        showCompletionMessage: function(status, surveyId) {
            const messages = {
                'success': 'Thank you for completing the survey! Your response has been recorded.',
                'quota_complete': 'This survey has reached its quota. Thank you for your interest!',
                'disqualified': 'Unfortunately, you did not qualify for this survey. Thank you for trying!'
            };

            const message = messages[status] || 'Survey completed.';
            const icon = status === 'success' ? 'success' : 'info';

            // Use SweetAlert if available
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Survey Completed',
                    text: message,
                    icon: icon,
                    confirmButtonText: 'OK',
                    timer: this.config.messageTimeout
                }).then(() => {
                    this.redirectToDashboard();
                });
            } else {
                // Fallback to alert
                alert(message);
                this.redirectToDashboard();
            }
        },

        /**
         * Redirect to dashboard
         */
        redirectToDashboard: function() {
            if (rm_survey_ajax && rm_survey_ajax.dashboard_url) {
                window.location.href = rm_survey_ajax.dashboard_url;
            }
        },

        /**
         * Get stored response ID
         */
        getResponseId: function() {
            return sessionStorage.getItem(this.config.storageKey);
        },

        /**
         * Clear stored response ID
         */
        clearResponseId: function() {
            sessionStorage.removeItem(this.config.storageKey);
        }
    };

    /**
     * Public API
     */
    window.RMSurveyTracking = {
        startSurvey: function(surveyId) {
            return RMSurveyTracking.startSurvey(surveyId);
        },
        completeSurvey: function(data) {
            return RMSurveyTracking.completeSurvey(data);
        },
        getResponseId: function() {
            return RMSurveyTracking.getResponseId();
        }
    };

    // Initialize on document ready
    $(document).ready(() => {
        RMSurveyTracking.init();
    });

})(jQuery);
