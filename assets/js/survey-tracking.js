// ============================================
// 3. JAVASCRIPT FOR SURVEY TRACKING
// ============================================

/**
 * Add this to assets/js/survey-tracking.js
 */
jQuery(document).ready(function($) {
    'use strict';
    
    // Survey tracking object
    var RMSurveyTracking = {
        
        // Initialize survey tracking
        init: function() {
            this.bindEvents();
            this.trackSurveyStart();
        },
        
        // Bind events
        bindEvents: function() {
            // Track when user clicks on survey link
            $('.rm-survey-button, .survey-button').on('click', function(e) {
                var $button = $(this);
                var surveyId = $button.data('survey-id');
                
                if (surveyId) {
                    RMSurveyTracking.startSurvey(surveyId);
                }
            });
            
            // Listen for survey completion messages
            window.addEventListener('message', function(e) {
                if (e.data && e.data.type === 'survey_complete') {
                    RMSurveyTracking.completeSurvey(e.data);
                }
            });
        },
        
        // Track survey start
        trackSurveyStart: function() {
            // Check if we're on a survey page
            if ($('body').hasClass('single-rm_survey')) {
                var surveyId = $('body').attr('data-survey-id');
                if (surveyId) {
                    this.startSurvey(surveyId);
                }
            }
        },
        
        // Start survey tracking
        startSurvey: function(surveyId) {
            $.ajax({
                url: rm_survey_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rm_start_survey',
                    survey_id: surveyId,
                    nonce: rm_survey_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Survey tracking started');
                        // Store response ID in session storage
                        sessionStorage.setItem('rm_survey_response_id', response.data.response_id);
                    }
                }
            });
        },
        
        // Complete survey tracking
        completeSurvey: function(data) {
            var surveyId = data.survey_id;
            var status = data.status; // success, quota_complete, or disqualified
            
            $.ajax({
                url: rm_survey_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rm_complete_survey',
                    survey_id: surveyId,
                    completion_status: status,
                    response_data: data.response_data || null,
                    nonce: rm_survey_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Survey completed:', status);
                        
                        // Show success message
                        RMSurveyTracking.showCompletionMessage(status);
                        
                        // Clear session storage
                        sessionStorage.removeItem('rm_survey_response_id');
                    }
                }
            });
        },
        
        // Show completion message
        showCompletionMessage: function(status) {
            var messages = {
                'success': 'Thank you for completing the survey! Your response has been recorded.',
                'quota_complete': 'This survey has reached its quota. Thank you for your interest!',
                'disqualified': 'Unfortunately, you did not qualify for this survey. Thank you for trying!'
            };
            
            var message = messages[status] || 'Survey completed.';
            
            // You can customize this to show a modal or notification
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Survey Completed',
                    text: message,
                    icon: status === 'success' ? 'success' : 'info',
                    confirmButtonText: 'OK'
                }).then(function() {
                    // Redirect to survey history or dashboard
                    window.location.href = rm_survey_ajax.dashboard_url;
                });
            } else {
                alert(message);
                window.location.href = rm_survey_ajax.dashboard_url;
            }
        }
    };
    
    // Initialize on document ready
    RMSurveyTracking.init();
});
