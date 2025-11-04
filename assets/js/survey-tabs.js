/**
 * Survey Tabs JavaScript
 * Handles tab switching and survey tracking
 * @package RM_Panel_Extensions
 * @version 2.1.0
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        activeClass: 'active',
        fadeSpeed: 300
    };

    /**
     * Switch between tabs
     */
    function switchTab() {
        const $btn = $(this);
        const tabId = $btn.data('tab');

        // Update button states
        $('.rm-tab-btn')
            .removeClass(config.activeClass)
            .attr('aria-selected', 'false');
        
        $btn.addClass(config.activeClass)
            .attr('aria-selected', 'true');

        // Update content
        $('.rm-tab-content')
            .removeClass(config.activeClass)
            .attr('aria-hidden', 'true');
        
        $(`#tab-${tabId}`)
            .addClass(config.activeClass)
            .attr('aria-hidden', 'false');

        // Store active tab in session storage
        sessionStorage.setItem('rm_active_tab', tabId);
    }

    /**
     * Track survey start
     */
    function trackSurveyStart() {
        const $btn = $(this);
        const surveyId = $btn.data('survey-id');

        if (!surveyId || typeof rm_survey_ajax === 'undefined') {
            return;
        }

        // Send AJAX request to track survey start
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
                    console.log('Survey start tracked:', surveyId);
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to track survey start:', error);
            }
        });
    }

    /**
     * Restore active tab from session storage
     */
    function restoreActiveTab() {
        const savedTab = sessionStorage.getItem('rm_active_tab');
        
        if (savedTab) {
            const $targetBtn = $(`.rm-tab-btn[data-tab="${savedTab}"]`);
            if ($targetBtn.length) {
                $targetBtn.trigger('click');
            }
        }
    }

    /**
     * Add keyboard navigation for tabs
     */
    function addKeyboardNavigation() {
        $('.rm-tab-btn').on('keydown', function(e) {
            const $current = $(this);
            let $target;

            switch(e.key) {
                case 'ArrowLeft':
                    $target = $current.prev('.rm-tab-btn');
                    break;
                case 'ArrowRight':
                    $target = $current.next('.rm-tab-btn');
                    break;
                case 'Home':
                    $target = $('.rm-tab-btn:first');
                    break;
                case 'End':
                    $target = $('.rm-tab-btn:last');
                    break;
                default:
                    return;
            }

            if ($target && $target.length) {
                e.preventDefault();
                $target.trigger('click').focus();
            }
        });
    }

    /**
     * Count surveys in each tab
     */
    function updateTabCounts() {
        $('.rm-tab-content').each(function() {
            const $content = $(this);
            const tabId = $content.attr('id').replace('tab-', '');
            const count = $content.find('.survey-card, .rm-survey-item').length;
            
            $(`.rm-tab-btn[data-tab="${tabId}"] .tab-count`).text(`(${count})`);
        });
    }

    /**
     * Initialize all functionality
     */
    function init() {
        // Event bindings
        $('.rm-tab-btn').on('click', switchTab);
        $('.survey-card-button, .rm-survey-button').on('click', trackSurveyStart);

        // Additional features
        restoreActiveTab();
        addKeyboardNavigation();
        updateTabCounts();

        // Set ARIA attributes
        $('.rm-tab-btn').attr('role', 'tab');
        $('.rm-tab-content').attr('role', 'tabpanel');
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
