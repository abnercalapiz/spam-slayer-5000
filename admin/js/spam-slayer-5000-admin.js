/**
 * Admin JavaScript for Spam Slayer 5000
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/admin/js
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize color picker
        $('.ss5k-color-picker').wpColorPicker();

        // Tab navigation
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.ss5k-tab-content').hide();
            $(target).show();
            
            // Update URL without reload
            window.history.pushState({}, '', $(this).attr('href'));
        });

        // Show active tab on load
        var activeTab = window.location.hash || '#general';
        $('.nav-tab[href="' + activeTab + '"]').click();

        // Test API Provider
        $('.ss5k-test-provider').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var provider = $button.data('provider');
            var $status = $button.siblings('.ss5k-test-status');
            
            $button.prop('disabled', true);
            $status.html('<span class="sfs-loading"></span> Testing...');
            
            $.ajax({
                url: ss5k_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ss5k_test_provider',
                    provider: provider,
                    nonce: ss5k_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('<span style="color: green;">✓ Connection successful</span>');
                    } else {
                        $status.html('<span style="color: red;">✗ ' + response.data + '</span>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: red;">✗ Connection failed</span>');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });

        // Submission Actions
        $('.ss5k-action-btn').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var action = $button.data('action');
            var submissionId = $button.data('id');
            
            if (action === 'delete' && !confirm(ss5k_admin.strings.confirm_delete)) {
                return;
            }
            
            $button.prop('disabled', true).text(ss5k_admin.strings.processing);
            
            $.ajax({
                url: ss5k_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ss5k_update_submission_status',
                    submission_id: submissionId,
                    status: action,
                    nonce: ss5k_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || ss5k_admin.strings.error);
                        $button.prop('disabled', false).text($button.data('original-text'));
                    }
                },
                error: function() {
                    alert(ss5k_admin.strings.error);
                    $button.prop('disabled', false).text($button.data('original-text'));
                }
            });
        });

        // Bulk Actions
        $('#doaction, #doaction2').on('click', function(e) {
            var action = $(this).prev('select').val();
            
            if (action === '-1') {
                return;
            }
            
            var checkedItems = $('input[name="submission[]"]:checked');
            
            if (checkedItems.length === 0) {
                alert('Please select at least one item.');
                e.preventDefault();
                return;
            }
            
            if (action === 'delete' && !confirm(ss5k_admin.strings.confirm_bulk_delete)) {
                e.preventDefault();
                return;
            }
        });

        // Select All
        $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('input[name="submission[]"]').prop('checked', isChecked);
        });

        // Add to Whitelist
        $('.ss5k-add-whitelist').on('click', function(e) {
            e.preventDefault();
            
            var email = $(this).data('email');
            var reason = prompt('Enter reason for whitelisting (optional):');
            
            if (reason === null) {
                return;
            }
            
            var $button = $(this);
            $button.prop('disabled', true);
            
            $.ajax({
                url: ss5k_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ss5k_add_to_whitelist',
                    email: email,
                    reason: reason,
                    nonce: ss5k_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Email added to whitelist successfully.');
                        $button.text('Whitelisted').removeClass('button-primary');
                    } else {
                        alert(response.data || ss5k_admin.strings.error);
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    alert(ss5k_admin.strings.error);
                    $button.prop('disabled', false);
                }
            });
        });

        // Remove from Whitelist
        $('.ss5k-remove-whitelist').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to remove this email from whitelist?')) {
                return;
            }
            
            var $button = $(this);
            var id = $button.data('id');
            
            $button.prop('disabled', true);
            
            $.ajax({
                url: ss5k_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ss5k_remove_from_whitelist',
                    id: id,
                    nonce: ss5k_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('tr').fadeOut();
                    } else {
                        alert(response.data || ss5k_admin.strings.error);
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    alert(ss5k_admin.strings.error);
                    $button.prop('disabled', false);
                }
            });
        });

        // Export Data
        $('#sfs-export-btn').on('click', function(e) {
            e.preventDefault();
            
            var exportType = $('#sfs-export-type').val();
            var dateFrom = $('#sfs-export-date-from').val();
            var dateTo = $('#sfs-export-date-to').val();
            
            window.location.href = ss5k_admin.ajax_url + '?' + $.param({
                action: 'ss5k_export_data',
                export_type: exportType,
                date_from: dateFrom,
                date_to: dateTo,
                nonce: ss5k_admin.nonce
            });
        });

        // Clear Logs
        $('#sfs-clear-logs').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
                return;
            }
            
            var $button = $(this);
            $button.prop('disabled', true);
            
            $.ajax({
                url: ss5k_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ss5k_clear_logs',
                    nonce: ss5k_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#sfs-log-viewer').text('Logs cleared successfully.');
                    } else {
                        alert(response.data || ss5k_admin.strings.error);
                    }
                },
                error: function() {
                    alert(ss5k_admin.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });

        // Modal Handler
        $('.ss5k-modal-trigger').on('click', function(e) {
            e.preventDefault();
            var targetModal = $(this).data('modal');
            $('#' + targetModal).show();
        });

        $('.ss5k-modal-close, .ss5k-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).closest('.ss5k-modal').hide();
            }
        });

        // Analytics Date Range
        $('#sfs-analytics-range').on('change', function() {
            var range = $(this).val();
            var customRange = $('.ss5k-custom-range');
            
            if (range === 'custom') {
                customRange.show();
            } else {
                customRange.hide();
                loadAnalytics(range);
            }
        });

        // Load Analytics
        function loadAnalytics(range, startDate, endDate) {
            $('.ss5k-analytics-content').html('<div class="sfs-loading"></div> Loading analytics...');
            
            $.ajax({
                url: ss5k_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ss5k_get_analytics',
                    range: range,
                    start_date: startDate,
                    end_date: endDate,
                    nonce: ss5k_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateAnalyticsDisplay(response.data);
                    } else {
                        $('.ss5k-analytics-content').html('<p>Error loading analytics.</p>');
                    }
                },
                error: function() {
                    $('.ss5k-analytics-content').html('<p>Error loading analytics.</p>');
                }
            });
        }

        // Update Analytics Display
        function updateAnalyticsDisplay(data) {
            // This would update charts and stats
            // Implementation depends on charting library used
        }

        // Form Validation
        $('form.ss5k-settings-form').on('submit', function(e) {
            var hasError = false;
            
            // Validate threshold
            var threshold = $('#spam_threshold').val();
            if (threshold && (threshold < 0 || threshold > 100)) {
                alert('Spam threshold must be between 0 and 100.');
                hasError = true;
            }
            
            // Validate email
            var email = $('#notification_email').val();
            if (email && !isValidEmail(email)) {
                alert('Please enter a valid email address.');
                hasError = true;
            }
            
            if (hasError) {
                e.preventDefault();
            }
        });

        // Email validation
        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        // Store original button text
        $('.ss5k-action-btn').each(function() {
            $(this).data('original-text', $(this).text());
        });
    });

})(jQuery);