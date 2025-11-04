jQuery(document).ready(function($) {
    'use strict';
    
    console.log('RM Withdrawal Admin JS Loaded'); // Debug
    console.log('rmWithdrawal object:', rmWithdrawal); // Debug
    
    // View payment details - IMPROVED VERSION
    $(document).on('click', '.view-details-btn', function(e) {
        e.preventDefault();
        console.log('View details button clicked'); // Debug
        
        var $btn = $(this);
        var requestId = $btn.data('request-id');
        var paymentDetails = $btn.data('payment-details');
        
        console.log('Request ID:', requestId); // Debug
        console.log('Payment Details:', paymentDetails); // Debug
        
        // If payment details are already in the button, use them directly
        if (paymentDetails) {
            try {
                var details = typeof paymentDetails === 'string' ? JSON.parse(paymentDetails) : paymentDetails;
                showPaymentDetails(details);
            } catch(e) {
                console.error('Error parsing payment details from button:', e);
                // Fall back to AJAX
                fetchPaymentDetailsViaAjax(requestId);
            }
        } else {
            // Fetch via AJAX
            fetchPaymentDetailsViaAjax(requestId);
        }
    });
    
    function fetchPaymentDetailsViaAjax(requestId) {
        console.log('Fetching via AJAX for request:', requestId); // Debug
        
        $.ajax({
            url: rmWithdrawal.ajax_url,
            type: 'POST',
            data: {
                action: 'rm_get_withdrawal_details',
                nonce: rmWithdrawal.nonce,
                request_id: requestId
            },
            success: function(response) {
                console.log('AJAX Response:', response); // Debug
                
                if (response.success && response.data) {
                    try {
                        var details = typeof response.data.payment_details === 'string' 
                            ? JSON.parse(response.data.payment_details) 
                            : response.data.payment_details;
                        
                        showPaymentDetails(details, response.data);
                    } catch(e) {
                        console.error('Error parsing AJAX payment details:', e);
                        alert('Error loading payment details');
                    }
                } else {
                    console.error('Failed to load payment details');
                    alert('Failed to load payment details');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Error loading payment details: ' + error);
            }
        });
    }
    
    function showPaymentDetails(details, extraData) {
        console.log('Showing payment details:', details); // Debug
        
        var html = '<div class="payment-details-grid">';
        
        if (details && typeof details === 'object') {
            // Show extra data if available
            if (extraData) {
                html += '<div class="payment-detail-row">';
                html += '<span class="payment-detail-label">Amount:</span>';
                html += '<span class="payment-detail-value">$' + parseFloat(extraData.amount).toFixed(2) + '</span>';
                html += '</div>';
                
                html += '<div class="payment-detail-row">';
                html += '<span class="payment-detail-label">Processing Fee:</span>';
                html += '<span class="payment-detail-value">$' + parseFloat(extraData.fee).toFixed(2) + '</span>';
                html += '</div>';
                
                html += '<div class="payment-detail-row">';
                html += '<span class="payment-detail-label">Net Amount:</span>';
                html += '<span class="payment-detail-value"><strong>$' + parseFloat(extraData.net_amount).toFixed(2) + '</strong></span>';
                html += '</div>';
                
                html += '<hr style="margin: 15px 0;">';
            }
            
            // Show payment details
            for (var key in details) {
                if (details.hasOwnProperty(key)) {
                    html += '<div class="payment-detail-row">';
                    html += '<span class="payment-detail-label">' + formatFieldName(key) + ':</span>';
                    html += '<span class="payment-detail-value">' + escapeHtml(details[key]) + '</span>';
                    html += '</div>';
                }
            }
        } else {
            html += '<p>No payment details available</p>';
        }
        
        html += '</div>';
        
        $('#payment-details-content').html(html);
        $('#payment-details-modal').fadeIn();
    }
    
    function formatFieldName(name) {
        return name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
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
    $(document).on('click', '.approve-withdrawal-btn', function(e) {
        e.preventDefault();
        console.log('Approve button clicked'); // Debug
        
        var requestId = $(this).data('request-id');
        $('#approve-request-id').val(requestId);
        $('#approve-withdrawal-modal').fadeIn();
    });
    
    $('#approve-withdrawal-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Approve form submitted'); // Debug
        
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
                console.log('Approve response:', response); // Debug
                
                if (response.success) {
                    $('#approve-withdrawal-modal').fadeOut();
                    showNotice(response.data.message, 'success');
                    
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    alert(response.data.message || 'An error occurred');
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('Approve error:', error);
                alert('An error occurred: ' + error);
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Reject withdrawal
    $(document).on('click', '.reject-withdrawal-btn', function(e) {
        e.preventDefault();
        console.log('Reject button clicked'); // Debug
        
        var requestId = $(this).data('request-id');
        $('#reject-request-id').val(requestId);
        $('#reject-withdrawal-modal').fadeIn();
    });
    
    $('#reject-withdrawal-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Reject form submitted'); // Debug
        
        var reason = $('#reject-reason').val().trim();
        if (!reason) {
            alert('Please provide a reason for rejection');
            return;
        }
        
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
                reason: reason
            },
            success: function(response) {
                console.log('Reject response:', response); // Debug
                
                if (response.success) {
                    $('#reject-withdrawal-modal').fadeOut();
                    showNotice(response.data.message, 'success');
                    
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    alert(response.data.message || 'An error occurred');
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('Reject error:', error);
                alert('An error occurred: ' + error);
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Complete withdrawal
    $(document).on('click', '.complete-withdrawal-btn', function(e) {
        e.preventDefault();
        console.log('Complete button clicked'); // Debug
        
        var requestId = $(this).data('request-id');
        $('#complete-request-id').val(requestId);
        $('#complete-withdrawal-modal').fadeIn();
    });
    
    $('#complete-withdrawal-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Complete form submitted'); // Debug
        
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
                console.log('Complete response:', response); // Debug
                
                if (response.success) {
                    $('#complete-withdrawal-modal').fadeOut();
                    showNotice(response.data.message, 'success');
                    
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    alert(response.data.message || 'An error occurred');
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('Complete error:', error);
                alert('An error occurred: ' + error);
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    function showNotice(message, type) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + escapeHtml(message) + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Add spinning animation
    if (!$('#rm-withdrawal-spinner-style').length) {
        $('<style id="rm-withdrawal-spinner-style">' +
          '.spinning { animation: spin 1s linear infinite; display: inline-block; } ' +
          '@keyframes spin { 100% { transform: rotate(360deg); } }' +
          '</style>').appendTo('head');
    }
});