/**
 * Survey Admin JavaScript
 * Handles survey editing interface functionality
 * @package RM_Panel_Extensions
 * @version 2.1.0
 */

(function($) {
    'use strict';

    // Cache DOM elements
    const $surveyType = $('#rm_survey_type');
    const $surveyAmountField = $('#survey_amount_field');
    const $surveyDurationType = $('#rm_survey_duration_type');
    const $dateFields = $('.survey-date-fields');
    const $surveyUrl = $('#rm_survey_url');
    const $previewDiv = $('#preview-url');
    const $parametersTable = $('#survey-parameters-table');
    const $addParameterBtn = $('#add_survey_parameter');

    // Get actual survey ID from page
    const actualSurveyId = $('#post_ID').val();

    /**
     * Toggle payment amount field visibility
     */
    function togglePaymentAmount() {
        const surveyType = $surveyType.val();
        $surveyAmountField.toggle(surveyType === 'paid');
    }

    /**
     * Toggle duration fields visibility
     */
    function toggleDurationFields() {
        const durationType = $surveyDurationType.val();
        $dateFields.toggle(durationType === 'date_range');
    }

    /**
     * Update preview URL with parameters
     */
    function updatePreviewUrl() {
        const baseUrl = $surveyUrl.val();

        if (!baseUrl) {
            $previewDiv.text('Enter a survey URL above to see preview');
            return;
        }

        const params = [];

        // Collect parameters from table
        $parametersTable.find('tbody tr').each(function() {
            const $row = $(this);
            const field = $row.find('select[name*="[field]"]').val();
            const variable = $row.find('input[name*="[variable]"]').val();
            const customValue = $row.find('input[name*="[custom_value]"]').val();

            // Skip if no variable name
            if (!variable) return;

            // Get value based on field type
            const valueMap = {
                'survey_id': actualSurveyId,
                'user_id': '{USER_ID}',
                'username': '{USERNAME}',
                'email': '{EMAIL}',
                'first_name': '{FIRST_NAME}',
                'last_name': '{LAST_NAME}',
                'display_name': '{DISPLAY_NAME}',
                'user_role': '{USER_ROLE}',
                'timestamp': '{TIMESTAMP}',
                'custom': customValue || '{CUSTOM}'
            };

            const value = valueMap[field];
            if (value) {
                params.push(encodeURIComponent(variable) + '=' + encodeURIComponent(value));
            }
        });

        // Build final URL
        let finalUrl = baseUrl;
        if (params.length > 0) {
            const separator = baseUrl.includes('?') ? '&' : '?';
            finalUrl = baseUrl + separator + params.join('&');
        }

        $previewDiv.text(finalUrl);
    }

    /**
     * Get next parameter index
     */
    function getNextParameterIndex() {
        let maxIndex = -1;

        $parametersTable.find('tbody tr').each(function() {
            const nameAttr = $(this).find('select').attr('name');
            if (nameAttr) {
                const matches = nameAttr.match(/\[(\d+)\]/);
                if (matches) {
                    const index = parseInt(matches[1], 10);
                    if (index > maxIndex) {
                        maxIndex = index;
                    }
                }
            }
        });

        return maxIndex + 1;
    }

    /**
     * Add parameter row HTML template
     */
    function getParameterRowHtml(index) {
        return `
            <tr class="survey-parameter-row">
                <td>
                    <select name="rm_survey_parameters[${index}][field]">
                        <option value="username">Username</option>
                        <option value="email">Email</option>
                        <option value="first_name">First Name</option>
                        <option value="last_name">Last Name</option>
                        <option value="display_name">Display Name</option>
                        <option value="user_role">User Role</option>
                        <option value="timestamp">Timestamp</option>
                        <option value="custom">Custom Field</option>
                    </select>
                </td>
                <td>
                    <input type="text" 
                           name="rm_survey_parameters[${index}][variable]" 
                           placeholder="e.g., username" />
                </td>
                <td>
                    <input type="text" 
                           name="rm_survey_parameters[${index}][custom_value]" 
                           placeholder="For custom field only" 
                           disabled 
                           style="opacity: 0.5;" />
                </td>
                <td>
                    <button type="button" class="button remove-parameter">Remove</button>
                </td>
            </tr>
        `;
    }

    /**
     * Add parameter row
     */
    function addParameterRow() {
        const index = getNextParameterIndex();
        $parametersTable.find('tbody').append(getParameterRowHtml(index));
        updatePreviewUrl();
    }

    /**
     * Remove parameter row
     */
    function removeParameterRow() {
        $(this).closest('tr').remove();
        updatePreviewUrl();
    }

    /**
     * Toggle custom value field
     */
    function toggleCustomValueField() {
        const $row = $(this).closest('tr');
        const $customValueField = $row.find('input[name*="[custom_value]"]');
        const isCustom = $(this).val() === 'custom';

        $customValueField
            .prop('disabled', !isCustom)
            .css('opacity', isCustom ? '1' : '0.5');

        if (!isCustom) {
            $customValueField.val('');
        }

        updatePreviewUrl();
    }

    /**
     * Initialize custom value fields on page load
     */
    function initializeCustomValueFields() {
        $parametersTable.find('select[name*="[field]"]').each(function() {
            const $row = $(this).closest('tr');
            const $customValueField = $row.find('input[name*="[custom_value]"]');

            if ($(this).val() !== 'custom') {
                $customValueField.prop('disabled', true).css('opacity', '0.5');
            }
        });
    }

    /**
     * Initialize all functionality
     */
    function init() {
        // Only run on survey edit pages
        if (!$('body').hasClass('post-type-rm_survey')) {
            return;
        }

        // Initial state
        togglePaymentAmount();
        toggleDurationFields();
        initializeCustomValueFields();
        updatePreviewUrl();

        // Event bindings
        $surveyType.on('change', togglePaymentAmount);
        $surveyDurationType.on('change', toggleDurationFields);
        $surveyUrl.on('input', updatePreviewUrl);
        $addParameterBtn.on('click', addParameterRow);

        // Delegated events for dynamic elements
        $(document).on('click', '.remove-parameter', removeParameterRow);
        $(document).on('change', '#survey-parameters-table select[name*="[field]"]', toggleCustomValueField);
        $(document).on('input change', '#survey-parameters-table input[name*="[variable]"]', updatePreviewUrl);
        $(document).on('input', '#survey-parameters-table input[name*="[custom_value]"]', updatePreviewUrl);
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
