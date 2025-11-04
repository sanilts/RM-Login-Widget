jQuery(document).ready(function($) {
    'use strict';
    
    // Open approval modal
    $('.approve-btn').on('click', function() {
        var responseId = $(this).data('response-id');
        var userId = $(this).data('user-id');
        var amount = $(this).data('amount');
        
        $('#approve-response-id').val(responseId);
        $('#approve-user-id').val(userId);
        $('#approve-amount').val(amount);
        $('.approval-amount').text(parseFloat(amount).toFixed(2));
        
        $('#approval-modal').fadeIn();
    });
    
    // Open rejection modal
    $('.reject-btn').on('click', function() {
        var responseId = $(this).data('response-id');
        $('#reject-response-id').val(responseId);
        $('#rejection-modal').fadeIn();
    });
    
    // Close modals
    $('.close-modal, .cancel-modal').on('click', function() {
        $('.rm-modal').fadeOut();
    });
    
    // Click outside modal to close
    $('.rm-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut();
        }
    });
    
    // Handle approval form submission
    $('#approval-form').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        
        $btn.prop('disabled', true)
            .html('<span class="dashicons dashicons-update-alt spinning"></span> ' + rmApprovalAjax.strings.approving);
        
        $.ajax({
            url: rmApprovalAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'rm_approve_survey_v2',
                nonce: rmApprovalAjax.nonce,
                response_id: $('#approve-response-id').val(),
                user_id: $('#approve-user-id').val(),
                amount: $('#approve-amount').val(),
                notes: $('#approval-notes').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#approval-modal').fadeOut();
                    
                    // Show success message
                    $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
                        .insertAfter('.wrap h1')
                        .delay(3000)
                        .fadeOut();
                    
                    // Remove the row
                    $('tr[data-response-id="' + $('#approve-response-id').val() + '"]').fadeOut(function() {
                        $(this).remove();
                        
                        // Update pending count
                        var $count = $('.awaiting-mod .pending-count');
                        var currentCount = parseInt($count.text());
                        if (currentCount > 0) {
                            $count.text(currentCount - 1);
                        }
                    });
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert(rmApprovalAjax.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Handle rejection form submission
    $('#rejection-form').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        
        $btn.prop('disabled', true)
            .html('<span class="dashicons dashicons-update-alt spinning"></span> ' + rmApprovalAjax.strings.rejecting);
        
        $.ajax({
            url: rmApprovalAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'rm_reject_survey_v2',
                nonce: rmApprovalAjax.nonce,
                response_id: $('#reject-response-id').val(),
                notes: $('#rejection-notes').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#rejection-modal').fadeOut();
                    
                    // Show success message
                    $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
                        .insertAfter('.wrap h1')
                        .delay(3000)
                        .fadeOut();
                    
                    // Remove the row
                    $('tr[data-response-id="' + $('#reject-response-id').val() + '"]').fadeOut(function() {
                        $(this).remove();
                        
                        // Update pending count
                        var $count = $('.awaiting-mod .pending-count');
                        var currentCount = parseInt($count.text());
                        if (currentCount > 0) {
                            $count.text(currentCount - 1);
                        }
                    });
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert(rmApprovalAjax.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Add spinning animation CSS
    $('<style>.spinning { animation: spin 1s linear infinite; } @keyframes spin { 100% { transform: rotate(360deg); } }</style>')
        .appendTo('head');
});