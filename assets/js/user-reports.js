/**
 * RM Panel Extensions - User Reports JavaScript
 * Version: 1.1.0
 * Datepicker and filtering functionality
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize datepickers
        if ($.fn.datepicker) {
            $('.rm-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                maxDate: 0, // Today
                yearRange: '-10:+0'
            });
        }
        
        // Highlight rows with pending payments
        $('.rm-user-reports-table tbody tr').each(function() {
            var $pending = $(this).find('.rm-amount-pending strong');
            if ($pending.length && parseFloat($pending.text().replace(/[^0-9.]/g, '')) > 0) {
                $(this).css('border-left', '3px solid #f0b849');
            }
        });
        
        // Add tooltip for active users
        $('.rm-active-now').attr('title', 'User is currently online');
        
    });
    
})(jQuery);