/**
 * Survey Reports JavaScript
 * Handles datepicker and reporting functionality
 * @package RM_Panel_Extensions
 * @version 2.1.0
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        dateFormat: 'yy-mm-dd',
        hoverColor: '#f9f9f9',
        animationSpeed: 200
    };

    /**
     * Initialize jQuery UI datepickers
     */
    function initDatepickers() {
        if (!$.fn.datepicker) {
            console.warn('jQuery UI Datepicker not loaded');
            return;
        }

        $('.rm-datepicker').datepicker({
            dateFormat: config.dateFormat,
            changeMonth: true,
            changeYear: true,
            maxDate: 0, // Today
            yearRange: '-10:+0'
        });
    }

    /**
     * Add table row hover effects
     */
    function addTableHoverEffects() {
        const $rows = $('.rm-reports-table-wrapper tbody tr');

        $rows.hover(
            function() {
                $(this).css('background-color', config.hoverColor);
            },
            function() {
                $(this).css('background-color', '');
            }
        );
    }

    /**
     * Format currency values in table
     */
    function formatCurrencyValues() {
        $('.rm-reports-table-wrapper .currency-value').each(function() {
            const value = parseFloat($(this).text());
            if (!isNaN(value)) {
                $(this).text('$' + value.toFixed(2));
            }
        });
    }

    /**
     * Add export functionality
     */
    function initExportButton() {
        $('#export-reports').on('click', function() {
            const $table = $('.rm-reports-table-wrapper table');
            
            if ($table.length) {
                exportTableToCSV($table, 'survey-reports.csv');
            }
        });
    }

    /**
     * Export table to CSV
     */
    function exportTableToCSV($table, filename) {
        const csv = [];
        const rows = $table.find('tr');

        rows.each(function() {
            const cols = $(this).find('td, th');
            const rowData = [];

            cols.each(function() {
                rowData.push('"' + $(this).text().trim().replace(/"/g, '""') + '"');
            });

            csv.push(rowData.join(','));
        });

        downloadCSV(csv.join('\n'), filename);
    }

    /**
     * Download CSV file
     */
    function downloadCSV(csv, filename) {
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');

        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }

    /**
     * Initialize all functionality
     */
    function init() {
        initDatepickers();
        addTableHoverEffects();
        formatCurrencyValues();
        initExportButton();
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
