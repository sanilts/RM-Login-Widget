jQuery(document).ready(function($) {
    'use strict';
    
    // Open add method modal
    $('#add-payment-method-btn').on('click', function() {
        resetForm();
        $('#modal-title').text('Add Payment Method');
        $('#method-id').val('');
        $('#payment-method-modal').fadeIn();
    });
    
    // Open edit method modal
    $(document).on('click', '.edit-method-btn', function() {
        var methodId = $(this).data('method-id');
        var method = rmPaymentMethods.methods.find(m => m.id == methodId);
        
        if (!method) return;
        
        resetForm();
        $('#modal-title').text('Edit Payment Method');
        $('#method-id').val(method.id);
        $('#method-name').val(method.method_name);
        $('#method-type').val(method.method_type);
        $('#method-icon').val(method.icon);
        $('#method-description').val(method.description);
        $('#min-withdrawal').val(method.min_withdrawal);
        $('#max-withdrawal').val(method.max_withdrawal || '');
        $('#processing-fee-type').val(method.processing_fee_type);
        $('#processing-fee-value').val(method.processing_fee_value);
        $('#processing-days').val(method.processing_days);
        $('#method-instructions').val(method.instructions);
        
        // Load required fields
        if (method.required_fields) {
            try {
                var fields = JSON.parse(method.required_fields);
                fields.forEach(function(field) {
                    addRequiredFieldRow(field);
                });
            } catch(e) {
                console.error('Error parsing required fields:', e);
            }
        }
        
        updateFeeSuffix();
        $('#payment-method-modal').fadeIn();
    });
    
    // Close modals
    $('.close-modal, .cancel-modal').on('click', function() {
        $('.rm-modal').fadeOut();
    });
    
    $('.rm-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut();
        }
    });
    
    // Add required field
    $('#add-required-field').on('click', function() {
        addRequiredFieldRow();
    });
    
    // Remove required field
    $(document).on('click', '.remove-field-btn', function() {
        $(this).closest('.required-field-row').remove();
    });
    
    // Update fee suffix
    $('#processing-fee-type').on('change', updateFeeSuffix);
    
    function updateFeeSuffix() {
        var type = $('#processing-fee-type').val();
        var suffix = '';
        
        if (type === 'percentage') {
            suffix = '%';
            $('#processing-fee-value').attr('placeholder', 'e.g., 2.5');
        } else if (type === 'fixed') {
            suffix = ' USD';
            $('#processing-fee-value').attr('placeholder', 'e.g., 5.00');
        } else {
            $('#processing-fee-value').val('0').prop('disabled', true);
        }
        
        $('#processing-fee-value').prop('disabled', type === 'none');
        $('#fee-suffix').text(suffix);
    }
    
    function addRequiredFieldRow(field) {
        field = field || {name: '', label: '', type: 'text', required: true};
        
        var html = '<div class="required-field-row">' +
            '<input type="text" placeholder="Field Name (e.g., paypal_email)" value="' + (field.name || '') + '" class="field-name">' +
            '<input type="text" placeholder="Field Label" value="' + (field.label || '') + '" class="field-label">' +
            '<select class="field-type">' +
                '<option value="text"' + (field.type === 'text' ? ' selected' : '') + '>Text</option>' +
                '<option value="email"' + (field.type === 'email' ? ' selected' : '') + '>Email</option>' +
                '<option value="number"' + (field.type === 'number' ? ' selected' : '') + '>Number</option>' +
            '</select>' +
            '<label><input type="checkbox" class="field-required"' + (field.required ? ' checked' : '') + '> Required</label>' +
            '<button type="button" class="button-link-delete remove-field-btn"><span class="dashicons dashicons-trash"></span></button>' +
        '</div>';
        
        $('#add-required-field').before(html);
    }
    
    function resetForm() {
        $('#payment-method-form')[0].reset();
        $('.required-field-row').remove();
        updateFeeSuffix();
    }
    
    function collectRequiredFields() {
        var fields = [];
        $('.required-field-row').each(function() {
            var $row = $(this);
            var name = $row.find('.field-name').val();
            var label = $row.find('.field-label').val();
            
            if (name && label) {
                fields.push({
                    name: name,
                    label: label,
                    type: $row.find('.field-type').val(),
                    required: $row.find('.field-required').is(':checked')
                });
            }
        });
        return fields;
    }
    
    // Form submission
    $('#payment-method-form').on('submit', function(e) {
        e.preventDefault();
        
        var methodId = $('#method-id').val();
        var action = methodId ? 'rm_update_payment_method' : 'rm_add_payment_method';
        
        var data = {
            action: action,
            nonce: rmPaymentMethods.nonce,
            method_data: {
                method_name: $('#method-name').val(),
                method_type: $('#method-type').val(),
                icon: $('#method-icon').val(),
                description: $('#method-description').val(),
                required_fields: collectRequiredFields(),
                min_withdrawal: $('#min-withdrawal').val(),
                max_withdrawal: $('#max-withdrawal').val(),
                processing_fee_type: $('#processing-fee-type').val(),
                processing_fee_value: $('#processing-fee-value').val(),
                processing_days: $('#processing-days').val(),
                instructions: $('#method-instructions').val()
            }
        };
        
        if (methodId) {
            data.method_id = methodId;
        }
        
        $.ajax({
            url: rmPaymentMethods.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $('#payment-method-modal').fadeOut();
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred');
            }
        });
    });
    
    // Toggle method active status
    $(document).on('change', '.toggle-method', function() {
        var $checkbox = $(this);
        var methodId = $checkbox.data('method-id');
        var isActive = $checkbox.is(':checked') ? 1 : 0;
        
        $.ajax({
            url: rmPaymentMethods.ajax_url,
            type: 'POST',
            data: {
                action: 'rm_toggle_payment_method',
                nonce: rmPaymentMethods.nonce,
                method_id: methodId,
                is_active: isActive
            },
            success: function(response) {
                if (response.success) {
                    var $row = $checkbox.closest('tr');
                    if (isActive) {
                        $row.removeClass('inactive-method');
                    } else {
                        $row.addClass('inactive-method');
                    }
                } else {
                    alert(response.data.message);
                    $checkbox.prop('checked', !isActive);
                }
            }
        });
    });
    
    // Delete payment method
    $(document).on('click', '.delete-method-btn', function() {
        if (!confirm('Are you sure you want to delete this payment method?')) {
            return;
        }
        
        var methodId = $(this).data('method-id');
        var $row = $(this).closest('tr');
        
        $.ajax({
            url: rmPaymentMethods.ajax_url,
            type: 'POST',
            data: {
                action: 'rm_delete_payment_method',
                nonce: rmPaymentMethods.nonce,
                method_id: methodId
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
});