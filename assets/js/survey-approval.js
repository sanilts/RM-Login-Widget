jQuery(document).ready(function($) {
    'use strict';
    
    // Approve button
    $('.approve-btn').on('click', function() {
        const responseId = $(this).data('response-id');
        $('#approve-response-id').val(responseId);
        $('#approval-modal').fadeIn();
    });
    
    // Reject button
    $('.reject-btn').on('click', function() {
        const responseId = $(this).data('response-id');
        $('#reject-response-id').val(responseId);
        $('#rejection-modal').fadeIn();
    });
    
    // Close modal
    $('.close-modal, .cancel-modal').on('click', function() {
        $('.approval-modal').fadeOut();
    });
    
    // Approval form submit
    $('#approval-form').on('submit', function(e) {
        e.preventDefault();
        
        const responseId = $('#approve-response-id').val();
        const notes = $('#approval-notes').val();
        const $btn = $(this).find('button[type="submit"]');
        const originalText = $btn.text();
        
        $btn.prop('disabled', true).text(rmApprovalAjax.strings.approving);
        
        $.ajax({
            url: rmApprovalAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'rm_approve_survey',
                nonce: rmApprovalAjax.nonce,
                response_id: responseId,
                notes: notes
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || rmApprovalAjax.strings.error);
                    $btn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert(rmApprovalAjax.strings.error);
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Rejection form submit
    $('#rejection-form').on('submit', function(e) {
        e.preventDefault();
        
        const responseId = $('#reject-response-id').val();
        const notes = $('#rejection-notes').val();
        const $btn = $(this).find('button[type="submit"]');
        const originalText = $btn.text();
        
        if (!notes) {
            alert('Please provide a reason for rejection');
            return;
        }
        
        $btn.prop('disabled', true).text(rmApprovalAjax.strings.rejecting);
        
        $.ajax({
            url: rmApprovalAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'rm_reject_survey',
                nonce: rmApprovalAjax.nonce,
                response_id: responseId,
                notes: notes
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || rmApprovalAjax.strings.error);
                    $btn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert(rmApprovalAjax.strings.error);
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
});