jQuery(document).ready(function($) {
    'use strict';
    
    // View payment details
    $(document).on('click', '.view-details-btn', function() {
        var requestId = $(this).data('request-id');
        
        $.ajax({
            url: rmWithdrawal.ajax_url,
            type: 'POST',
            data: {
                action: 'rm_get_withdrawal_details',
                nonce: rmWithdrawal.nonce,
                request_id: requestId
            },
            success: function(response) {
                if (response.success) {
                    showPaymentDetails(response.data);
                }
            }
        });
    });
    
    function showPaymentDetails(data) {
        var html = '<div class="payment-details-grid">';
        
        if (data.payment_details) {
            try {
                var details = JSON.parse(data.payment_details);
                for (var key in details) {
                    html += '<div class="payment-detail-row">';
                    html += '<span class="payment-detail-label">' + formatFieldName(key) + ':</span>';
                    html += '<span class="payment-detail-value">' + escapeHtml(details[key]) + '</span>';
                    html += '</div>';
                }
            } catch(e) {
                html += '<p>Error displaying payment details</p>';
            }
        }
        
        html += '</div>';
        
        $('#payment-details-content').html(html);
        $('#payment-details-modal').fadeIn();
    }
    
    function formatFieldName(name) {
        return name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Close modals
    $('.close-modal, .cancel-modal').on('click', function() {
        $('.rm-modal').fadeOut();
    });
    
    $('.rm-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut();
        }
    });
    
    // Approve withdrawal
    $(document).on('click', '.approve-withdrawal-btn', function() {
        var requestId = $(this).data('request-id');
        $('#approve-request-id').val(requestId);
        $('#approve-withdrawal-modal').fadeIn();
    });
    
    $('#approve-withdrawal-form').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        
        $btn.prop('disabled', true)
            .html('<span class="dashicons dashicons-update-alt spinning"></span> Processing...');
        
        $.ajax({
            url: rmWithdrawal.ajax_url,
            type: 'POST',
            data: {
                action: 'rm_approve_withdrawal',
                nonce: rmWithdrawal.nonce,
                request_id: $('#approve-request-id').val(),
                notes: $('#approve-notes').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#approve-withdrawal-modal').fadeOut();
                    showNotice(response.data.message, 'success');
                    
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Reject withdrawal
    $(document).on('click', '.reject-withdrawal-btn', function() {
        var requestId = $(this).data('request-id');
        $('#reject-request-id').val(requestId);
        $('#reject-withdrawal-modal').fadeIn();
    });
    
    $('#reject-withdrawal-form').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        
        $btn.prop('disabled', true)
            .html('<span class="dashicons dashicons-update-alt spinning"></span> Processing...');
        
        $.ajax({
            url: rmWithdrawal.ajax_url,
            type: 'POST',
            data: {
                action: 'rm_reject_withdrawal',
                nonce: rmWithdrawal.nonce,
                request_id: $('#reject-request-id').val(),
                reason: $('#reject-reason').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#reject-withdrawal-modal').fadeOut();
                    showNotice(response.data.message, 'success');
                    
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Complete withdrawal
    $(document).on('click', '.complete-withdrawal-btn', function() {
        var requestId = $(this).data('request-id');
        $('#complete-request-id').val(requestId);
        $('#complete-withdrawal-modal').fadeIn();
    });
    
    $('#complete-withdrawal-form').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        
        $btn.prop('disabled', true)
            .html('<span class="dashicons dashicons-update-alt spinning"></span> Processing...');
        
        $.ajax({
            url: rmWithdrawal.ajax_url,
            type: 'POST',
            data: {
                action: 'rm_complete_withdrawal',
                nonce: rmWithdrawal.nonce,
                request_id: $('#complete-request-id').val(),
                transaction_reference: $('#transaction-reference').val(),
                notes: $('#completion-notes').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#complete-withdrawal-modal').fadeOut();
                    showNotice(response.data.message, 'success');
                    
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    function showNotice(message, type) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Add spinning animation
    $('<style>.spinning { animation: spin 1s linear infinite; } @keyframes spin { 100% { transform: rotate(360deg); } }</style>')
        .appendTo('head');
});