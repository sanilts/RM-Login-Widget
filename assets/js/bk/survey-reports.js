/**
 * RM Panel Extensions - Survey Reports JavaScript
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
        
        // Table row hover effects
        $('.rm-reports-table-wrapper tbody tr').hover(
            function() {
                $(this).css('background-color', '#f9f9f9');
            },
            function() {
                $(this).css('background-color', '');
            }
        );
        
    });
    
})(jQuery);