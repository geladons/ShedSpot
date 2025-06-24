/**
 * SchedSpot Admin Dashboard JavaScript
 *
 * @package SchedSpot
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initBookingStatusUpdates();
        initAvailabilityScheduler();
        initGoogleCalendarIntegration();
        initSMSTestFunctionality();
        initGeolocationTesting();
        initWorkerManagement();
    });

    /**
     * Initialize booking status update functionality
     */
    function initBookingStatusUpdates() {
        window.updateBookingStatus = function(bookingId, status) {
            if (confirm(schedspot_admin.strings.confirm_status_update)) {
                $.post(ajaxurl, {
                    action: 'schedspot_update_booking_status',
                    booking_id: bookingId,
                    status: status,
                    nonce: schedspot_admin.nonces.booking_status
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(schedspot_admin.strings.error_updating_status);
                    }
                });
            }
        };
    }

    /**
     * Initialize availability scheduler functionality
     */
    function initAvailabilityScheduler() {
        // Add new time slot
        $(document).on('click', '.add-slot', function() {
            var day = $(this).data('day');
            var row = $(this).closest('tr');
            var index = $('tr[data-day="' + day + '"]').length;

            var newRow = row.clone();
            newRow.find('td:first').html('<input type="hidden" name="availability[' + day + '][' + index + '][day_of_week]" value="' + day + '">');
            newRow.find('input[type="checkbox"]').attr('name', 'availability[' + day + '][' + index + '][is_available]').prop('checked', false);
            newRow.find('input[type="time"]:first').attr('name', 'availability[' + day + '][' + index + '][start_time]').val('09:00');
            newRow.find('input[type="time"]:last').attr('name', 'availability[' + day + '][' + index + '][end_time]').val('17:00');
            newRow.find('.add-slot').remove();
            newRow.append('<td><button type="button" class="button remove-slot">' + schedspot_admin.strings.remove + '</button></td>');

            row.after(newRow);
        });

        // Remove time slot
        $(document).on('click', '.remove-slot', function() {
            $(this).closest('tr').remove();
        });
    }

    /**
     * Initialize Google Calendar integration functionality
     */
    function initGoogleCalendarIntegration() {
        window.disconnectGoogleCalendar = function() {
            if (confirm(schedspot_admin.strings.confirm_gcal_disconnect)) {
                $.post(ajaxurl, {
                    action: 'schedspot_gcal_disconnect',
                    nonce: schedspot_admin.nonces.gcal_disconnect
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(schedspot_admin.strings.error_gcal_disconnect);
                    }
                });
            }
        };

        window.syncAllBookings = function() {
            if (confirm(schedspot_admin.strings.confirm_gcal_sync)) {
                $.post(ajaxurl, {
                    action: 'schedspot_gcal_sync_all',
                    nonce: schedspot_admin.nonces.gcal_sync_all
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert(response.data.message || schedspot_admin.strings.error_gcal_sync);
                    }
                });
            }
        };
    }

    /**
     * Initialize SMS test functionality
     */
    function initSMSTestFunctionality() {
        window.sendTestSMS = function() {
            var phone = document.getElementById('test_phone').value;
            if (!phone) {
                alert(schedspot_admin.strings.enter_phone_number);
                return;
            }

            $.post(ajaxurl, {
                action: 'schedspot_test_sms',
                phone: phone,
                nonce: schedspot_admin.nonces.test_sms
            }, function(response) {
                var result = document.getElementById('test_result');
                if (response.success) {
                    result.innerHTML = '<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>';
                } else {
                    result.innerHTML = '<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>';
                }
            });
        };
    }

    /**
     * Initialize geolocation testing functionality
     */
    function initGeolocationTesting() {
        window.testGeocoding = function() {
            var address = document.getElementById('test_address').value;
            if (!address) {
                alert(schedspot_admin.strings.enter_address);
                return;
            }

            $.post(ajaxurl, {
                action: 'schedspot_geocode_address',
                address: address,
                nonce: schedspot_admin.nonces.geolocation
            }, function(response) {
                var result = document.getElementById('geocoding_result');
                if (response.success) {
                    result.innerHTML = '<div class="notice notice-success inline"><p>' +
                        schedspot_admin.strings.geocoding_success + ' ' +
                        response.data.lat + ', ' + response.data.lng +
                        '<br>' + schedspot_admin.strings.formatted_address + ' ' + response.data.formatted_address +
                        '</p></div>';
                } else {
                    result.innerHTML = '<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>';
                }
            });
        };
    }

    /**
     * Initialize worker management functionality
     */
    function initWorkerManagement() {
        // Worker deletion confirmation
        $(document).on('click', '.delete-worker', function(e) {
            if (!confirm(schedspot_admin.strings.confirm_delete_worker)) {
                e.preventDefault();
                return false;
            }
        });

        // Service deletion confirmation
        $(document).on('click', '.delete-service', function(e) {
            if (!confirm(schedspot_admin.strings.confirm_delete_service)) {
                e.preventDefault();
                return false;
            }
        });

        // Booking deletion confirmation
        $(document).on('click', '.delete-booking', function(e) {
            if (!confirm(schedspot_admin.strings.confirm_delete_booking)) {
                e.preventDefault();
                return false;
            }
        });
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        // Service form validation
        $('#schedspot-service-form').on('submit', function(e) {
            var name = $('#service_name').val().trim();
            var price = $('#service_price').val();

            if (!name) {
                alert(schedspot_admin.strings.service_name_required);
                $('#service_name').focus();
                e.preventDefault();
                return false;
            }

            if (price && (isNaN(price) || parseFloat(price) < 0)) {
                alert(schedspot_admin.strings.invalid_price);
                $('#service_price').focus();
                e.preventDefault();
                return false;
            }
        });

        // Worker form validation
        $('#schedspot-worker-form').on('submit', function(e) {
            var userId = $('#user_id').val();
            var hourlyRate = $('#hourly_rate').val();

            if (!userId) {
                alert(schedspot_admin.strings.select_user);
                $('#user_id').focus();
                e.preventDefault();
                return false;
            }

            if (hourlyRate && (isNaN(hourlyRate) || parseFloat(hourlyRate) < 0)) {
                alert(schedspot_admin.strings.invalid_hourly_rate);
                $('#hourly_rate').focus();
                e.preventDefault();
                return false;
            }
        });
    }

    /**
     * Initialize dashboard widgets
     */
    function initDashboardWidgets() {
        // Auto-refresh stats every 5 minutes
        setInterval(function() {
            refreshQuickStats();
        }, 300000);

        // Refresh recent bookings every 2 minutes
        setInterval(function() {
            refreshRecentBookings();
        }, 120000);
    }

    /**
     * Refresh quick stats widget
     */
    function refreshQuickStats() {
        $.post(ajaxurl, {
            action: 'schedspot_refresh_stats',
            nonce: schedspot_admin.nonces.refresh_stats
        }, function(response) {
            if (response.success) {
                $('.schedspot-stats-grid').html(response.data.html);
            }
        });
    }

    /**
     * Refresh recent bookings widget
     */
    function refreshRecentBookings() {
        $.post(ajaxurl, {
            action: 'schedspot_refresh_bookings',
            nonce: schedspot_admin.nonces.refresh_bookings
        }, function(response) {
            if (response.success) {
                $('.recent-bookings-widget').html(response.data.html);
            }
        });
    }

    /**
     * Initialize tooltips and help text
     */
    function initTooltips() {
        // Add tooltips to help icons
        $('.schedspot-help-tip').tooltip({
            position: {
                my: 'center bottom-20',
                at: 'center top',
                using: function(position, feedback) {
                    $(this).css(position);
                    $('<div>')
                        .addClass('arrow')
                        .addClass(feedback.vertical)
                        .addClass(feedback.horizontal)
                        .appendTo(this);
                }
            }
        });
    }

    /**
     * Initialize settings tabs
     */
    function initSettingsTabs() {
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            var tab = $(this).attr('href').split('tab=')[1];
            
            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Show/hide tab content
            $('.tab-content').hide();
            $('#tab-' + tab).show();
            
            // Update URL without page reload
            if (history.pushState) {
                history.pushState(null, null, $(this).attr('href'));
            }
        });
    }

    // Initialize all functionality
    initFormValidation();
    initDashboardWidgets();
    initTooltips();
    initSettingsTabs();

})(jQuery);
