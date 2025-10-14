/**
 * Survey Callback Admin JavaScript
 * File: assets/js/survey-callback-admin.js
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize clipboard.js
        if (typeof ClipboardJS !== 'undefined') {
            var clipboard = new ClipboardJS('.copy-url-btn');
            
            clipboard.on('success', function(e) {
                var btn = $(e.trigger);
                var originalText = btn.text();
                
                btn.addClass('copied');
                btn.text(rm_callback_ajax.strings.copied);
                
                setTimeout(function() {
                    btn.removeClass('copied');
                    btn.text(originalText);
                }, 2000);
                
                e.clearSelection();
            });
            
            clipboard.on('error', function(e) {
                console.error('Copy failed:', e.action);
            });
        }
        
        // Generate user-specific URLs
        $('#generate_user_specific_urls').on('click', function() {
            var $btn = $(this);
            var surveyId = $btn.data('survey-id');
            var $container = $('#user_specific_urls');
            var $content = $('#user_urls_content');
            
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
                        var urls = response.data;
                        
                        var html = '<div class="user-urls-list">';
                        html += '<div class="url-item">';
                        html += '<strong>Success:</strong><br>';
                        html += '<input type="text" value="' + urls.success + '" readonly class="widefat" />';
                        html += '</div>';
                        
                        html += '<div class="url-item">';
                        html += '<strong>Terminate:</strong><br>';
                        html += '<input type="text" value="' + urls.terminate + '" readonly class="widefat" />';
                        html += '</div>';
                        
                        html += '<div class="url-item">';
                        html += '<strong>Quota Full:</strong><br>';
                        html += '<input type="text" value="' + urls.quotafull + '" readonly class="widefat" />';
                        html += '</div>';
                        html += '</div>';
                        
                        $content.html(html);
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
        });
        
        // Make URL fields selectable on click
        $('.callback-url-field, #user_urls_content').on('click', 'input[readonly]', function() {
            $(this).select();
        });
    });
    
})(jQuery);