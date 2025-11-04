jQuery(document).ready(function($) {
    'use strict';
    
    // Tab switching
    $('.rm-tab-btn').on('click', function() {
        const tabId = $(this).data('tab');
        
        // Update buttons
        $('.rm-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        // Update content
        $('.rm-tab-content').removeClass('active');
        $('#tab-' + tabId).addClass('active');
    });
    
    // Track survey start
    $('.survey-card-button').on('click', function() {
        const surveyId = $(this).data('survey-id');
        
        // Track survey start via AJAX (optional)
        if (typeof rm_survey_ajax !== 'undefined') {
            $.ajax({
                url: rm_survey_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'rm_start_survey',
                    survey_id: surveyId,
                    nonce: rm_survey_ajax.nonce
                }
            });
        }
    });
});