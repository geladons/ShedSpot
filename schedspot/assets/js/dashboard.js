/**
 * SchedSpot Dashboard JavaScript
 *
 * @package SchedSpot
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initDashboard();
        initAvailabilityToggle();
        initBookingActions();
        initPaymentRequests();
        initModalHandlers();
    });

    /**
     * Initialize dashboard functionality
     */
    function initDashboard() {
        // Auto-refresh dashboard data every 5 minutes
        setInterval(function() {
            refreshDashboardData();
        }, 300000);

        // Initialize tooltips
        initTooltips();

        // Initialize status filters
        initStatusFilters();
    }

    /**
     * Initialize availability toggle
     */
    function initAvailabilityToggle() {
        // Handle new dashboard availability toggle button
        $('.availability-toggle-btn').on('click', function() {
            var $button = $(this);
            var workerId = $button.data('worker-id');

            if (!workerId) {
                showNotification('Worker ID not found.', 'error');
                return;
            }

            $button.prop('disabled', true).text('Updating...');

            $.ajax({
                url: schedspot_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'schedspot_toggle_worker_availability',
                    worker_id: workerId,
                    nonce: schedspot_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update availability status
                        var $status = $('.availability-status');
                        var $icon = $status.find('.dashicons');

                        if (response.data.is_available) {
                            $status.removeClass('unavailable').addClass('available');
                            $status.find('span:not(.dashicons)').text('Available for Bookings');
                            $icon.removeClass('dashicons-dismiss').addClass('dashicons-yes-alt');
                            $button.text('Set Unavailable');
                        } else {
                            $status.removeClass('available').addClass('unavailable');
                            $status.find('span:not(.dashicons)').text('Currently Unavailable');
                            $icon.removeClass('dashicons-yes-alt').addClass('dashicons-dismiss');
                            $button.text('Set Available');
                        }

                        showNotification(response.data.message, 'success');
                    } else {
                        showNotification(response.data.message || 'Failed to update availability.', 'error');
                    }
                },
                error: function() {
                    showNotification('Network error. Please try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });

        // Legacy availability toggle function for backward compatibility
        window.toggleAvailability = function() {
            const restUrl = schedspot_frontend.rest_url + 'workers/' + schedspot_frontend.user_id + '/profile';
            const nonce = schedspot_frontend.nonce;
            const currentStatus = document.getElementById('availability-status').classList.contains('available');
            const newStatus = !currentStatus;

            // Show loading state
            const toggleBtn = document.querySelector('.availability-toggle');
            if (toggleBtn) {
                toggleBtn.disabled = true;
                toggleBtn.textContent = schedspot_frontend.strings.processing;
            }

            fetch(restUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                body: JSON.stringify({
                    is_available: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    const statusElement = document.getElementById('availability-status');
                    const statusText = document.getElementById('availability-text');
                    
                    if (newStatus) {
                        statusElement.classList.add('available');
                        statusElement.classList.remove('unavailable');
                        statusText.textContent = schedspot_frontend.strings.available;
                    } else {
                        statusElement.classList.add('unavailable');
                        statusElement.classList.remove('available');
                        statusText.textContent = schedspot_frontend.strings.unavailable;
                    }

                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message || schedspot_frontend.strings.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification(schedspot_frontend.strings.error, 'error');
            })
            .finally(() => {
                // Reset button state
                if (toggleBtn) {
                    toggleBtn.disabled = false;
                    toggleBtn.textContent = newStatus ? 
                        schedspot_frontend.strings.go_unavailable : 
                        schedspot_frontend.strings.go_available;
                }
            });
        };
    }

    /**
     * Initialize booking actions
     */
    function initBookingActions() {
        // Reschedule booking
        window.rescheduleBooking = function(bookingId) {
            // Create modal for rescheduling
            const modal = createRescheduleModal(bookingId);
            $('body').append(modal);
        };

        // Cancel booking
        window.cancelBooking = function(bookingId) {
            if (confirm(schedspot_frontend.strings.confirm_cancel)) {
                updateBookingStatus(bookingId, 'cancelled');
            }
        };

        // Complete booking
        window.completeBooking = function(bookingId) {
            if (confirm(schedspot_frontend.strings.confirm_complete)) {
                updateBookingStatus(bookingId, 'completed');
            }
        };
    }

    /**
     * Initialize payment request functions
     */
    function initPaymentRequests() {
        window.requestDeposit = function(bookingId) {
            if (confirm(schedspot_frontend.strings.confirm_deposit_request)) {
                $.post(schedspot_frontend.ajax_url, {
                    action: 'schedspot_request_deposit',
                    booking_id: bookingId,
                    nonce: schedspot_frontend.nonce
                }, function(response) {
                    if (response.success) {
                        showNotification(response.data.message, 'success');
                    } else {
                        showNotification(response.data.message || schedspot_frontend.strings.error, 'error');
                    }
                });
            }
        };

        window.requestProgress = function(bookingId) {
            if (confirm(schedspot_frontend.strings.confirm_progress_request)) {
                $.post(schedspot_frontend.ajax_url, {
                    action: 'schedspot_request_progress_payment',
                    booking_id: bookingId,
                    nonce: schedspot_frontend.nonce
                }, function(response) {
                    if (response.success) {
                        showNotification(response.data.message, 'success');
                    } else {
                        showNotification(response.data.message || schedspot_frontend.strings.error, 'error');
                    }
                });
            }
        };

        window.requestFinalPayment = function(bookingId) {
            if (confirm(schedspot_frontend.strings.confirm_final_request)) {
                $.post(schedspot_frontend.ajax_url, {
                    action: 'schedspot_request_final_payment',
                    booking_id: bookingId,
                    nonce: schedspot_frontend.nonce
                }, function(response) {
                    if (response.success) {
                        showNotification(response.data.message, 'success');
                    } else {
                        showNotification(response.data.message || schedspot_frontend.strings.error, 'error');
                    }
                });
            }
        };
    }

    /**
     * Initialize modal handlers
     */
    function initModalHandlers() {
        // Close modal handler
        $(document).on('click', '.schedspot-modal-close, .schedspot-modal-overlay', function(e) {
            if (e.target === this) {
                closeModal($(this));
            }
        });

        // Escape key to close modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.schedspot-modal').remove();
            }
        });
    }

    /**
     * Update booking status
     */
    function updateBookingStatus(bookingId, status) {
        $.post(schedspot_frontend.ajax_url, {
            action: 'schedspot_update_booking_status',
            booking_id: bookingId,
            status: status,
            nonce: schedspot_frontend.nonce
        }, function(response) {
            if (response.success) {
                showNotification(response.data.message, 'success');
                // Refresh the page or update the booking row
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showNotification(response.data.message || schedspot_frontend.strings.error, 'error');
            }
        });
    }

    /**
     * Create reschedule modal
     */
    function createRescheduleModal(bookingId) {
        const modalHtml = `
            <div class="schedspot-modal">
                <div class="schedspot-modal-overlay"></div>
                <div class="schedspot-modal-content">
                    <div class="schedspot-modal-header">
                        <h3>${schedspot_frontend.strings.reschedule_booking}</h3>
                        <button class="schedspot-modal-close">&times;</button>
                    </div>
                    <div class="schedspot-modal-body">
                        <form id="reschedule-form">
                            <div class="schedspot-form-group">
                                <label for="new_date">${schedspot_frontend.strings.new_date}</label>
                                <input type="date" id="new_date" name="new_date" required min="${new Date().toISOString().split('T')[0]}">
                            </div>
                            <div class="schedspot-form-group">
                                <label for="new_time">${schedspot_frontend.strings.new_time}</label>
                                <input type="time" id="new_time" name="new_time" required>
                            </div>
                            <div class="schedspot-form-group">
                                <label for="reschedule_reason">${schedspot_frontend.strings.reason_optional}</label>
                                <textarea id="reschedule_reason" name="reschedule_reason" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="schedspot-modal-footer">
                        <button type="button" class="schedspot-btn schedspot-btn-secondary schedspot-modal-close">
                            ${schedspot_frontend.strings.cancel}
                        </button>
                        <button type="button" class="schedspot-btn schedspot-btn-primary" onclick="submitReschedule(${bookingId})">
                            ${schedspot_frontend.strings.reschedule}
                        </button>
                    </div>
                </div>
            </div>
        `;
        return modalHtml;
    }

    /**
     * Submit reschedule request
     */
    window.submitReschedule = function(bookingId) {
        const form = document.getElementById('reschedule-form');
        const formData = new FormData(form);
        
        const data = {
            action: 'schedspot_reschedule_booking',
            booking_id: bookingId,
            new_date: formData.get('new_date'),
            new_time: formData.get('new_time'),
            reason: formData.get('reschedule_reason'),
            nonce: schedspot_frontend.nonce
        };

        $.post(schedspot_frontend.ajax_url, data, function(response) {
            if (response.success) {
                showNotification(schedspot_frontend.strings.reschedule_success, 'success');
                closeModal($('.schedspot-modal-close'));
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                showNotification(response.data.message || schedspot_frontend.strings.error, 'error');
            }
        });
    };

    /**
     * Close modal
     */
    function closeModal($element) {
        var modal = $element.closest('.schedspot-modal');
        if (modal.length) {
            modal.remove();
        }
    }

    /**
     * Show notification
     */
    function showNotification(message, type) {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.schedspot-notification');
        existingNotifications.forEach(notification => notification.remove());

        // Create new notification
        const notification = document.createElement('div');
        notification.className = `schedspot-notification ${type}`;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    /**
     * Refresh dashboard data
     */
    function refreshDashboardData() {
        // Refresh booking counts and recent bookings
        $.post(schedspot_frontend.ajax_url, {
            action: 'schedspot_refresh_dashboard',
            nonce: schedspot_frontend.nonce
        }, function(response) {
            if (response.success && response.data) {
                // Update dashboard widgets
                if (response.data.booking_counts) {
                    updateBookingCounts(response.data.booking_counts);
                }
                if (response.data.recent_bookings) {
                    updateRecentBookings(response.data.recent_bookings);
                }
            }
        });
    }

    /**
     * Update booking counts
     */
    function updateBookingCounts(counts) {
        Object.keys(counts).forEach(function(key) {
            const element = document.querySelector(`[data-count="${key}"]`);
            if (element) {
                element.textContent = counts[key];
            }
        });
    }

    /**
     * Update recent bookings
     */
    function updateRecentBookings(bookings) {
        const container = document.querySelector('.recent-bookings-list');
        if (container && bookings) {
            container.innerHTML = bookings;
        }
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        $('[data-tooltip]').each(function() {
            $(this).attr('title', $(this).data('tooltip'));
        });
    }

    /**
     * Initialize status filters
     */
    function initStatusFilters() {
        $('.status-filter').on('click', function() {
            const status = $(this).data('status');
            filterBookingsByStatus(status);
        });
    }

    /**
     * Filter bookings by status
     */
    function filterBookingsByStatus(status) {
        $('.booking-item').each(function() {
            const bookingStatus = $(this).data('status');
            if (status === 'all' || bookingStatus === status) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        // Update active filter
        $('.status-filter').removeClass('active');
        $(`.status-filter[data-status="${status}"]`).addClass('active');
    }

    // Export functions for global access
    window.SchedSpotDashboard = {
        showNotification: showNotification,
        updateBookingStatus: updateBookingStatus,
        refreshDashboardData: refreshDashboardData,
        closeModal: closeModal
    };

})(jQuery);
