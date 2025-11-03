jQuery(document).ready(function($) {
    'use strict';
    
    var currentStep = 1;
    var selectedMethod = null;
    var selectedMethodData = null;
    
    // Step navigation
    $('.next-step').on('click', function() {
        if (validateStep(currentStep)) {
            showStep(currentStep + 1);
        }
    });
    
    $('.prev-step').on('click', function() {
        showStep(currentStep - 1);
    });
    
    function showStep(step) {
        $('.form-step').removeClass('active');
        $('.form-step[data-step="' + step + '"]').addClass('active');
        currentStep = step;
        
        // Update based on step
        if (step === 2) {
            updateAmountLimits();
        } else if (step === 3) {
            generatePaymentFields();
        }
    }
    
    function validateStep(step) {
        if (step === 1) {
            var selected = $('input[name="payment_method"]:checked');
            if (selected.length === 0) {
                alert('Please select a payment method');
                return false;
            }
            
            selectedMethod = selected.val();
            selectedMethodData = {
                min: parseFloat(selected.data('min')),
                max: parseFloat(selected.data('max')),
                feeType: selected.data('fee-type'),
                feeValue: parseFloat(selected.data('fee-value')),
                fields: selected.data('fields')
            };
            
            return true;
        }
        
        if (step === 2) {
            var amount = parseFloat($('#withdrawal-amount').val());
            
            if (!amount || amount <= 0) {
                alert('Please enter a valid amount');
                return false;
            }
            
            if (amount < selectedMethodData.min) {
                alert('Minimum withdrawal is $' + selectedMethodData.min.toFixed(2));
                return false;
            }
            
            if (amount > selectedMethodData.max) {
                alert('Maximum withdrawal is $' + selectedMethodData.max.toFixed(2));
                return false;
            }
            
            return true;
        }
        
        if (step === 3) {
            var allValid = true;
            $('#payment-details-fields input[required], #payment-details-fields select[required]').each(function() {
                if (!$(this).val()) {
                    allValid = false;
                    $(this).addClass('error');
                } else {
                    $(this).removeClass('error');
                }
            });
            
            if (!allValid) {
                alert('Please fill in all required fields');
            }
            
            return allValid;
        }
        
        return true;
    }
    
    function updateAmountLimits() {
        var limitsText = 'Min: $' + selectedMethodData.min.toFixed(2);
        if (selectedMethodData.max < 999999) {
            limitsText += ' | Max: $' + selectedMethodData.max.toFixed(2);
        }
        
        $('.amount-limits').text(limitsText);
        
        $('#withdrawal-amount').attr('min', selectedMethodData.min);
        $('#withdrawal-amount').attr('max', selectedMethodData.max);
    }
    
    // Calculate fees and net amount
    $('#withdrawal-amount').on('input', function() {
        var amount = parseFloat($(this).val()) || 0;
        var fee = 0;
        
        if (selectedMethodData.feeType === 'percentage') {
            fee = (amount * selectedMethodData.feeValue) / 100;
        } else if (selectedMethodData.feeType === 'fixed') {
            fee = selectedMethodData.feeValue;
        }
        
        var net = amount - fee;
        
        $('.summary-amount').text('$' + amount.toFixed(2));
        $('.summary-fee').text('$' + fee.toFixed(2));
        $('.summary-net').text('$' + net.toFixed(2));
    });
    
    function generatePaymentFields() {
        var html = '';
        
        try {
            var fields = typeof selectedMethodData.fields === 'string' 
                ? JSON.parse(selectedMethodData.fields) 
                : selectedMethodData.fields;
            
            if (!fields || fields.length === 0) {
                html = '<p>No additional information required.</p>';
            } else {
                fields.forEach(function(field) {
                    html += '<div class="form-group">';
                    html += '<label for="field_' + field.name + '">';
                    html += field.label;
                    if (field.required) {
                        html += ' <span class="required">*</span>';
                    }
                    html += '</label>';
                    
                    if (field.type === 'email') {
                        html += '<input type="email" id="field_' + field.name + '" name="' + field.name + '" class="form-control"';
                    } else if (field.type === 'number') {
                        html += '<input type="number" id="field_' + field.name + '" name="' + field.name + '" class="form-control"';
                    } else {
                        html += '<input type="text" id="field_' + field.name + '" name="' + field.name + '" class="form-control"';
                    }
                    
                    if (field.required) {
                        html += ' required';
                    }
                    
                    html += '>';
                    html += '</div>';
                });
            }
        } catch(e) {
            console.error('Error parsing fields:', e);
            html = '<p>Error loading payment fields</p>';
        }
        
        $('#payment-details-fields').html(html);
    }
    
    // Form submission
    $('#rm-withdrawal-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateStep(3)) {
            return;
        }
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.html();
        
        // Collect payment details
        var paymentDetails = {};
        $('#payment-details-fields input, #payment-details-fields select').each(function() {
            paymentDetails[$(this).attr('name')] = $(this).val();
        });
        
        $submitBtn.prop('disabled', true).html('⏳ Submitting...');
        
        $.ajax({
            url: rmWithdrawal.ajax_url,
            type: 'POST',
            data: {
                action: 'rm_submit_withdrawal',
                nonce: rmWithdrawal.nonce,
                payment_method_id: selectedMethod,
                amount: $('#withdrawal-amount').val(),
                payment_details: paymentDetails
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.data.message);
                    
                    // Reset form and redirect to history
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Cancel withdrawal
    $(document).on('click', '.cancel-withdrawal-btn', function() {
        if (!confirm('Are you sure you want to cancel this withdrawal request?')) {
            return;
        }
        
        var $btn = $(this);
        var requestId = $btn.data('request-id');
        var originalText = $btn.html();
        
        $btn.prop('disabled', true).html('Cancelling...');
        
        $.ajax({
            url: rmWithdrawal.ajax_url,
            type: 'POST',
            data: {
                action: 'rm_cancel_withdrawal',
                nonce: rmWithdrawal.nonce,
                request_id: requestId
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.data.message);
                    
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
    
    function showSuccessMessage(message) {
        var $msg = $('<div class="rm-notice rm-notice-success">' + message + '</div>');
        $('.rm-withdrawal-form-container').prepend($msg);
        
        $('html, body').animate({
            scrollTop: $msg.offset().top - 100
        }, 500);
        
        setTimeout(function() {
            $msg.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
});