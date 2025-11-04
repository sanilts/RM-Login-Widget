/**
 * Survey Callback Admin JavaScript
 * Handles survey callback URL management
 * @package RM_Panel_Extensions
 * @version 2.1.0
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        copiedDisplayTime: 2000
    };

    /**
     * Initialize Clipboard.js for URL copying
     */
    function initClipboard() {
        if (typeof ClipboardJS === 'undefined') {
            console.warn('ClipboardJS library not loaded');
            return;
        }

        const clipboard = new ClipboardJS('.copy-url-btn');

        clipboard.on('success', handleCopySuccess);
        clipboard.on('error', handleCopyError);
    }

    /**
     * Handle successful copy
     */
    function handleCopySuccess(e) {
        const $btn = $(e.trigger);
        const originalText = $btn.text();

        $btn.addClass('copied')
            .text(rm_callback_ajax.strings.copied);

        setTimeout(function() {
            $btn.removeClass('copied')
                .text(originalText);
        }, config.copiedDisplayTime);

        e.clearSelection();
    }

    /**
     * Handle copy error
     */
    function handleCopyError(e) {
        console.error('Copy failed:', e.action);
        alert('Failed to copy URL. Please copy manually.');
    }

    /**
     * Generate user-specific callback URLs
     */
    function generateUserSpecificUrls() {
        const $btn = $(this);
        const surveyId = $btn.data('survey-id');
        const $container = $('#user_specific_urls');
        const $content = $('#user_urls_content');

        $btn.prop('disabled', true);

        $.ajax({
            url: rm_callback_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'copy_callback_urls',
                survey_id: surveyId,
                user_id: rm_callback_ajax.current_user_id,
                nonce: rm_callback_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderUserUrls(response.data, $content);
                    $container.slideDown();
                } else {
                    alert(rm_callback_ajax.strings.error);
                }
            },
            error: function() {
                alert(rm_callback_ajax.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    }

    /**
     * Render user-specific URLs
     */
    function renderUserUrls(urls, $container) {
        const urlTypes = ['success', 'terminate', 'quotafull'];
        let html = '<div class="user-urls-list">';

        urlTypes.forEach(function(type) {
            const label = type.charAt(0).toUpperCase() + type.slice(1);
            html += `
                <div class="url-item">
                    <strong>${label}:</strong><br>
                    <input type="text" 
                           value="${urls[type]}" 
                           readonly 
                           class="widefat" />
                </div>
            `;
        });

        html += '</div>';
        $container.html(html);
    }

    /**
     * Make URL fields selectable on click
     */
    function makeUrlFieldsSelectable() {
        $(document).on('click', '.callback-url-field input[readonly], #user_urls_content input[readonly]', function() {
            $(this).select();
        });
    }

    /**
     * Initialize all functionality
     */
    function init() {
        initClipboard();
        makeUrlFieldsSelectable();

        // Event bindings
        $('#generate_user_specific_urls').on('click', generateUserSpecificUrls);
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
