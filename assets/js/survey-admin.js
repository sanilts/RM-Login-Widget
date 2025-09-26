/**
 * Survey Admin JavaScript with Survey ID support
 * File: assets/js/survey-admin.js
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Only run on survey edit pages
    if ($('body').hasClass('post-type-rm_survey')) {
        
        // Toggle payment amount field
        function togglePaymentAmount() {
            var surveyType = $('#rm_survey_type').val();
            if (surveyType === 'paid') {
                $('#survey_amount_field').slideDown();
            } else {
                $('#survey_amount_field').slideUp();
            }
        }
        
        // Toggle duration fields
        function toggleDurationFields() {
            var durationType = $('#rm_survey_duration_type').val();
            if (durationType === 'date_range') {
                $('.survey-date-fields').slideDown();
            } else {
                $('.survey-date-fields').slideUp();
            }
        }
        
        // Update preview URL
        function updatePreviewUrl() {
            var baseUrl = $('#rm_survey_url').val();
            var $previewDiv = $('#preview-url');
            
            if (!baseUrl) {
                $previewDiv.text('Enter a survey URL above to see preview');
                return;
            }
            
            // Build parameters
            var params = [];
            $('#survey-parameters-table tbody tr').each(function() {
                var field = $(this).find('select[name*="[field]"]').val();
                var variable = $(this).find('input[name*="[variable]"]').val();
                var customValue = $(this).find('input[name*="[custom_value]"]').val();
                
                if (variable) {
                    var value = '';
                    switch(field) {
                        case 'survey_id':
                            value = $('input[readonly][value]').first().val() || '{SURVEY_ID}';
                            break;
                        case 'user_id':
                            value = '{USER_ID}';
                            break;
                        case 'username':
                            value = '{USERNAME}';
                            break;
                        case 'email':
                            value = '{EMAIL}';
                            break;
                        case 'first_name':
                            value = '{FIRST_NAME}';
                            break;
                        case 'last_name':
                            value = '{LAST_NAME}';
                            break;
                        case 'display_name':
                            value = '{DISPLAY_NAME}';
                            break;
                        case 'user_role':
                            value = '{USER_ROLE}';
                            break;
                        case 'timestamp':
                            value = '{TIMESTAMP}';
                            break;
                        case 'custom':
                            value = customValue || '{CUSTOM}';
                            break;
                    }
                    
                    if (value) {
                        params.push(variable + '=' + value);
                    }
                }
            });
            
            // Build final URL
            var finalUrl = baseUrl;
            if (params.length > 0) {
                var separator = baseUrl.indexOf('?') !== -1 ? '&' : '?';
                finalUrl = baseUrl + separator + params.join('&');
            }
            
            $previewDiv.text(finalUrl);
        }
        
        // Initialize on page load
        togglePaymentAmount();
        toggleDurationFields();
        updatePreviewUrl();
        
        // Bind change events
        $('#rm_survey_type').on('change', togglePaymentAmount);
        $('#rm_survey_duration_type').on('change', toggleDurationFields);
        $('#rm_survey_url').on('input', updatePreviewUrl);
        
        // Calculate the next parameter index
        function getNextParameterIndex() {
            var maxIndex = -1;
            $('#survey-parameters-table tbody tr').each(function() {
                var nameAttr = $(this).find('select').attr('name');
                if (nameAttr) {
                    var matches = nameAttr.match(/\[(\d+)\]/);
                    if (matches) {
                        var index = parseInt(matches[1]);
                        if (index > maxIndex) {
                            maxIndex = index;
                        }
                    }
                }
            });
            return maxIndex + 1;
        }
        
        // Add parameter row
        $('#add_survey_parameter').on('click', function(e) {
            e.preventDefault();
            
            var parameterIndex = getNextParameterIndex();
            
            var html = '<tr class="survey-parameter-row">' +
                '<td>' +
                    '<select name="rm_survey_parameters[' + parameterIndex + '][field]">' +
                        '<option value="survey_id">Survey ID</option>' +
                        '<option value="user_id">User ID</option>' +
                        '<option value="username">Username</option>' +
                        '<option value="email">Email</option>' +
                        '<option value="first_name">First Name</option>' +
                        '<option value="last_name">Last Name</option>' +
                        '<option value="display_name">Display Name</option>' +
                        '<option value="user_role">User Role</option>' +
                        '<option value="timestamp">Timestamp</option>' +
                        '<option value="custom">Custom Field</option>' +
                    '</select>' +
                '</td>' +
                '<td>' +
                    '<input type="text" name="rm_survey_parameters[' + parameterIndex + '][variable]" placeholder="e.g., uid" />' +
                '</td>' +
                '<td>' +
                    '<input type="text" name="rm_survey_parameters[' + parameterIndex + '][custom_value]" placeholder="For custom field only" />' +
                '</td>' +
                '<td>' +
                    '<button type="button" class="button remove-parameter">Remove</button>' +
                '</td>' +
            '</tr>';
            
            $('#survey-parameters-table tbody').append(html);
            updatePreviewUrl();
        });
        
        // Remove parameter row
        $(document).on('click', '.remove-parameter', function(e) {
            e.preventDefault();
            $(this).closest('tr').remove();
            updatePreviewUrl();
        });
        
        // Show/hide custom value field based on field selection
        $(document).on('change', '#survey-parameters-table select', function() {
            var $row = $(this).closest('tr');
            var $customValueField = $row.find('input[name*="[custom_value]"]');
            
            if ($(this).val() === 'custom') {
                $customValueField.prop('disabled', false).css('opacity', '1');
            } else {
                $customValueField.prop('disabled', true).css('opacity', '0.5').val('');
            }
            
            updatePreviewUrl();
        });
        
        // Update preview when parameter values change
        $(document).on('input', '#survey-parameters-table input', function() {
            updatePreviewUrl();
        });
        
        // Initialize custom value fields on page load
        $('#survey-parameters-table select').each(function() {
            var $row = $(this).closest('tr');
            var $customValueField = $row.find('input[name*="[custom_value]"]');
            
            if ($(this).val() !== 'custom') {
                $customValueField.prop('disabled', true).css('opacity', '0.5');
            }
        });
        
        // Trigger initial preview update for existing parameters
        if ($('#survey-parameters-table tbody tr').length > 0) {
            updatePreviewUrl();
        }
    }
});