/**
 * Survey Approval V2 JavaScript
 * Handles survey response approval workflow
 * @package RM_Panel_Extensions
 * @version 2.1.0
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        modalFadeSpeed: 300,
        noticeDisplayTime: 3000,
        spinAnimation: 'spinning'
    };

    /**
     * Open approval modal
     */
    function openApprovalModal() {
        const responseId = $(this).data('response-id');
        const userId = $(this).data('user-id');
        const amount = $(this).data('amount');

        $('#approve-response-id').val(responseId);
        $('#approve-user-id').val(userId);
        $('#approve-amount').val(amount);
        $('.approval-amount').text(parseFloat(amount).toFixed(2));

        $('#approval-modal').fadeIn(config.modalFadeSpeed);
    }

    /**
     * Open rejection modal
     */
    function openRejectionModal() {
        const responseId = $(this).data('response-id');
        $('#reject-response-id').val(responseId);
        $('#rejection-modal').fadeIn(config.modalFadeSpeed);
    }

    /**
     * Close modals
     */
    function closeModal() {
        $('.rm-modal').fadeOut(config.modalFadeSpeed);
    }

    /**
     * Handle approval form submission
     */
    function handleApproval(e) {
        e.preventDefault();

        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const originalText = $btn.html();

        // Disable button and show loading
        $btn.prop('disabled', true)
            .html(`<span class="dashicons dashicons-update-alt ${config.spinAnimation}"></span> ${rmApprovalAjax.strings.approving}`);

        // Prepare data
        const data = {
            action: 'rm_approve_survey_v2',
            nonce: rmApprovalAjax.nonce,
            response_id: $('#approve-response-id').val(),
            user_id: $('#approve-user-id').val(),
            amount: $('#approve-amount').val(),
            notes: $('#approval-notes').val()
        };

        // Send AJAX request
        $.ajax({
            url: rmApprovalAjax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    closeModal();
                    showNotice(response.data.message, 'success');
                    removeResponseRow($('#approve-response-id').val());
                } else {
                    alert(response.data.message || rmApprovalAjax.strings.error);
                }
            },
            error: function() {
                alert(rmApprovalAjax.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    }

    /**
     * Handle rejection form submission
     */
    function handleRejection(e) {
        e.preventDefault();

        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const originalText = $btn.html();

        // Disable button and show loading
        $btn.prop('disabled', true)
            .html(`<span class="dashicons dashicons-update-alt ${config.spinAnimation}"></span> ${rmApprovalAjax.strings.rejecting}`);

        // Prepare data
        const data = {
            action: 'rm_reject_survey_v2',
            nonce: rmApprovalAjax.nonce,
            response_id: $('#reject-response-id').val(),
            notes: $('#rejection-notes').val()
        };

        // Send AJAX request
        $.ajax({
            url: rmApprovalAjax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    closeModal();
                    showNotice(response.data.message, 'success');
                    removeResponseRow($('#reject-response-id').val());
                } else {
                    alert(response.data.message || rmApprovalAjax.strings.error);
                }
            },
            error: function() {
                alert(rmApprovalAjax.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    }

    /**
     * Show notice message
     */
    function showNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $(`<div class="notice ${noticeClass} is-dismissible"><p>${message}</p></div>`);

        $('.wrap h1').after($notice);

        setTimeout(function() {
            $notice.fadeOut(config.modalFadeSpeed, function() {
                $(this).remove();
            });
        }, config.noticeDisplayTime);
    }

    /**
     * Remove response row from table
     */
    function removeResponseRow(responseId) {
        const $row = $(`tr[data-response-id="${responseId}"]`);

        $row.fadeOut(config.modalFadeSpeed, function() {
            $(this).remove();
            updatePendingCount();
        });
    }

    /**
     * Update pending count badge
     */
    function updatePendingCount() {
        const $count = $('.awaiting-mod .pending-count');
        const currentCount = parseInt($count.text(), 10);

        if (currentCount > 0) {
            $count.text(currentCount - 1);
        }
    }

    /**
     * Add spinning animation CSS
     */
    function addSpinningAnimation() {
        if (!$('#rm-approval-animations').length) {
            $(`<style id="rm-approval-animations">
                .${config.spinAnimation} { 
                    animation: spin 1s linear infinite; 
                } 
                @keyframes spin { 
                    100% { transform: rotate(360deg); } 
                }
            </style>`).appendTo('head');
        }
    }

    /**
     * Initialize all functionality
     */
    function init() {
        // Add animations
        addSpinningAnimation();

        // Event bindings
        $('.approve-btn').on('click', openApprovalModal);
        $('.reject-btn').on('click', openRejectionModal);
        $('.close-modal, .cancel-modal').on('click', closeModal);

        // Click outside modal to close
        $('.rm-modal').on('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Form submissions
        $('#approval-form').on('submit', handleApproval);
        $('#rejection-form').on('submit', handleRejection);
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
