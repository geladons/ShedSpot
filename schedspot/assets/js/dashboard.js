/**
 * SchedSpot Dashboard JavaScript
 * 
 * @package SchedSpot
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Global variables
    let currentUserRole = null;
    let dashboardData = {};
    let refreshInterval = null;

    /**
     * Initialize dashboard functionality
     */
    function initDashboard() {
        // Get current user role
        currentUserRole = $('#schedspot-dashboard').data('user-role') || 'customer';
        
        // Initialize based on user role
        if (currentUserRole === 'schedspot_worker') {
            initWorkerDashboard();
        } else {
            initCustomerDashboard();
        }

        // Common dashboard functionality
        initCommonDashboard();
    }

    /**
     * Initialize worker dashboard
     */
    function initWorkerDashboard() {
        // Availability toggle
        $('.availability-toggle').on('click', toggleAvailability);
        
        // Settings modal
        $('.open-settings-modal').on('click', openSettingsModal);
        $('.close-settings-modal').on('click', closeSettingsModal);
        
        // Settings form submission
        $('#worker-settings-form').on('submit', handleSettingsSubmission);
        
        // Schedule management
        $('.schedule-slot').on('click', handleScheduleSlotClick);
        
        // Service management
        $('.service-toggle').on('change', handleServiceToggle);
        $('.custom-price-input').on('blur', handleCustomPriceChange);
        
        // Payment requests
        $('.request-deposit-btn').on('click', handleDepositRequest);
        $('.request-progress-btn').on('click', handleProgressPayment);
        $('.request-final-btn').on('click', handleFinalPayment);
        $('.generate-invoice-btn').on('click', handleInvoiceGeneration);
    }

    /**
     * Initialize customer dashboard
     */
    function initCustomerDashboard() {
        // Booking actions
        $('.cancel-booking-btn').on('click', handleBookingCancellation);
        $('.reschedule-booking-btn').on('click', handleBookingReschedule);
        $('.review-booking-btn').on('click', handleBookingReview);
        
        // Message actions
        $('.message-worker-btn').on('click', handleMessageWorker);
    }

    /**
     * Initialize common dashboard functionality
     */
    function initCommonDashboard() {
        // Refresh data periodically
        refreshInterval = setInterval(refreshDashboardData, 30000); // 30 seconds
        
        // Tab switching
        $('.dashboard-tab').on('click', handleTabSwitch);
        
        // Search and filters
        $('.dashboard-search').on('input', handleSearch);
        $('.dashboard-filter').on('change', handleFilter);
        
        // Pagination
        $('.pagination-btn').on('click', handlePagination);
        
        // Modal handling
        $('.modal-close').on('click', closeModal);
        $(document).on('click', '.modal-overlay', closeModal);
        
        // Escape key to close modals
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    }

    /**
     * Toggle worker availability
     */
    function toggleAvailability() {
        const $toggle = $(this);
        const $status = $('#availability-status');
        const currentStatus = $status.hasClass('available');
        const newStatus = !currentStatus;
        
        // Show loading state
        $toggle.prop('disabled', true);
        $toggle.html('<span class="dashicons dashicons-update spin"></span> Updating...');
        
        $.ajax({
            url: schedspot_frontend.rest_url + 'workers/profile',
            method: 'POST',
            data: {
                available: newStatus ? 1 : 0
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (response.success) {
                    // Update UI
                    $status.toggleClass('available', newStatus);
                    $status.toggleClass('unavailable', !newStatus);
                    $status.text(newStatus ? 'Available' : 'Unavailable');
                    
                    // Update toggle button
                    $toggle.html(newStatus ? 'Go Offline' : 'Go Online');
                    $toggle.toggleClass('btn-danger', newStatus);
                    $toggle.toggleClass('btn-success', !newStatus);
                    
                    showNotification(
                        newStatus ? 'You are now available for bookings' : 'You are now offline',
                        'success'
                    );
                } else {
                    showNotification('Failed to update availability', 'error');
                }
            },
            error: function() {
                showNotification('Error updating availability', 'error');
            },
            complete: function() {
                $toggle.prop('disabled', false);
            }
        });
    }

    /**
     * Open settings modal
     */
    function openSettingsModal() {
        $('#worker-settings-modal').addClass('active');
        $('body').addClass('modal-open');
    }

    /**
     * Close settings modal
     */
    function closeSettingsModal() {
        $('#worker-settings-modal').removeClass('active');
        $('body').removeClass('modal-open');
    }

    /**
     * Handle settings form submission
     */
    function handleSettingsSubmission(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('[type="submit"]');
        const formData = new FormData(this);
        
        // Show loading state
        $submitBtn.prop('disabled', true);
        $submitBtn.html('<span class="dashicons dashicons-update spin"></span> Saving...');
        
        $.ajax({
            url: schedspot_frontend.rest_url + 'workers/profile',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Settings saved successfully', 'success');
                    closeSettingsModal();
                    // Refresh dashboard data
                    refreshDashboardData();
                } else {
                    showNotification(response.message || 'Failed to save settings', 'error');
                }
            },
            error: function() {
                showNotification('Error saving settings', 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false);
                $submitBtn.html('<span class="dashicons dashicons-yes"></span> Save Changes');
            }
        });
    }

    /**
     * Handle schedule slot click
     */
    function handleScheduleSlotClick() {
        const $slot = $(this);
        const day = $slot.data('day');
        const time = $slot.data('time');
        const isAvailable = $slot.hasClass('available');
        
        // Toggle availability
        $slot.toggleClass('available');
        $slot.toggleClass('unavailable');
        
        // Update backend
        updateScheduleSlot(day, time, !isAvailable);
    }

    /**
     * Update schedule slot
     */
    function updateScheduleSlot(day, time, available) {
        $.ajax({
            url: schedspot_frontend.rest_url + 'workers/schedule',
            method: 'POST',
            data: {
                day: day,
                time: time,
                available: available ? 1 : 0
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (!response.success) {
                    showNotification('Failed to update schedule', 'error');
                    // Revert UI change
                    const $slot = $(`.schedule-slot[data-day="${day}"][data-time="${time}"]`);
                    $slot.toggleClass('available');
                    $slot.toggleClass('unavailable');
                }
            },
            error: function() {
                showNotification('Error updating schedule', 'error');
            }
        });
    }

    /**
     * Handle service toggle
     */
    function handleServiceToggle() {
        const $toggle = $(this);
        const serviceId = $toggle.data('service-id');
        const enabled = $toggle.is(':checked');
        
        $.ajax({
            url: schedspot_frontend.rest_url + 'workers/services',
            method: 'POST',
            data: {
                service_id: serviceId,
                enabled: enabled ? 1 : 0
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (response.success) {
                    showNotification(
                        enabled ? 'Service enabled' : 'Service disabled',
                        'success'
                    );
                } else {
                    showNotification('Failed to update service', 'error');
                    $toggle.prop('checked', !enabled);
                }
            },
            error: function() {
                showNotification('Error updating service', 'error');
                $toggle.prop('checked', !enabled);
            }
        });
    }

    /**
     * Handle custom price change
     */
    function handleCustomPriceChange() {
        const $input = $(this);
        const serviceId = $input.data('service-id');
        const price = parseFloat($input.val()) || 0;
        
        if (price > 0) {
            $.ajax({
                url: schedspot_frontend.rest_url + 'workers/services/price',
                method: 'POST',
                data: {
                    service_id: serviceId,
                    custom_price: price
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        $input.addClass('saved');
                        setTimeout(() => $input.removeClass('saved'), 2000);
                    } else {
                        showNotification('Failed to update price', 'error');
                    }
                },
                error: function() {
                    showNotification('Error updating price', 'error');
                }
            });
        }
    }

    /**
     * Handle tab switching
     */
    function handleTabSwitch(e) {
        e.preventDefault();
        
        const $tab = $(this);
        const targetTab = $tab.data('tab');
        
        // Update active tab
        $('.dashboard-tab').removeClass('active');
        $tab.addClass('active');
        
        // Show target content
        $('.tab-content').removeClass('active');
        $(`#${targetTab}`).addClass('active');
        
        // Load tab data if needed
        loadTabData(targetTab);
    }

    /**
     * Load tab data
     */
    function loadTabData(tab) {
        // Implementation depends on specific tab requirements
        switch (tab) {
            case 'bookings':
                loadBookings();
                break;
            case 'earnings':
                loadEarnings();
                break;
            case 'messages':
                loadMessages();
                break;
        }
    }

    /**
     * Refresh dashboard data
     */
    function refreshDashboardData() {
        $.ajax({
            url: schedspot_frontend.rest_url + 'dashboard/data',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (response.success) {
                    updateDashboardUI(response.data);
                }
            },
            error: function() {
                // Silently fail for background refresh
            }
        });
    }

    /**
     * Update dashboard UI with new data
     */
    function updateDashboardUI(data) {
        // Update stats
        if (data.stats) {
            Object.keys(data.stats).forEach(function(key) {
                $(`.stat-${key}`).text(data.stats[key]);
            });
        }
        
        // Update notifications count
        if (data.notifications_count) {
            $('.notifications-count').text(data.notifications_count);
        }
        
        // Update recent activity
        if (data.recent_activity) {
            updateRecentActivity(data.recent_activity);
        }
    }

    /**
     * Show notification
     */
    function showNotification(message, type) {
        const notification = $(`
            <div class="schedspot-notification ${type}">
                ${message}
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Close modal
     */
    function closeModal() {
        $('.modal').removeClass('active');
        $('body').removeClass('modal-open');
    }

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#schedspot-dashboard').length) {
            initDashboard();
        }
    });

    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });

})(jQuery);
