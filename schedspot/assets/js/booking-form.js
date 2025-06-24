/**
 * SchedSpot Booking Form JavaScript
 *
 * @package SchedSpot
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initBookingForm();
        initWorkerSelection();
        initFormValidation();
        initFormEnhancements();
    });

    /**
     * Initialize booking form functionality
     */
    function initBookingForm() {
        // Initialize datepicker with modern styling
        $('#schedspot_booking_date').datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 0,
            showAnim: 'slideDown',
            changeMonth: true,
            changeYear: true
        });

        // Form submission handler
        $('#schedspot-booking-form').on('submit', function(e) {
            var isValid = validateForm($(this));
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }

            // Show loading state
            var $submitBtn = $(this).find('[type="submit"]');
            $submitBtn.prop('disabled', true);
            $submitBtn.html('<span class="dashicons dashicons-update spin"></span> ' + schedspot_frontend.strings.processing);
        });
    }

    /**
     * Initialize worker selection functionality
     */
    function initWorkerSelection() {
        // Worker selection mode toggle
        $('input[name="worker_selection_mode"]').change(function() {
            if ($(this).val() === 'manual') {
                $('#manual-worker-selection').slideDown();
            } else {
                $('#manual-worker-selection').slideUp();
                $('#schedspot_worker_id').val('');
                $('.worker-card').removeClass('selected');
            }
        });

        // Worker card selection
        $(document).on('click', '.worker-card.available', function() {
            var workerId = $(this).data('worker-id');
            selectWorker(workerId);
        });

        // Worker selection button
        $(document).on('click', '.select-worker-btn', function(e) {
            e.stopPropagation();
            var workerId = $(this).data('worker-id');
            selectWorker(workerId);
        });

        // Service selection change handler
        $('#schedspot_service_id').change(function() {
            var serviceId = $(this).val();
            if (serviceId) {
                loadServiceDetails(serviceId);
            }
        });
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        // Real-time validation on blur
        $('#schedspot-booking-form input[required], #schedspot-booking-form select[required]').on('blur', function() {
            validateField($(this));
        });

        // Email validation
        $('#schedspot_client_email').on('blur', function() {
            var email = $(this).val();
            if (email && !isValidEmail(email)) {
                showFieldError($(this), schedspot_frontend.strings.invalid_email);
            }
        });

        // Phone validation
        $('#schedspot_client_phone').on('blur', function() {
            var phone = $(this).val();
            if (phone && phone.length < 10) {
                showFieldError($(this), 'Please enter a valid phone number.');
            }
        });
    }

    /**
     * Initialize form enhancements
     */
    function initFormEnhancements() {
        // Auto-resize textareas
        $('textarea').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Form field focus effects
        $('.schedspot-form-row input, .schedspot-form-row select, .schedspot-form-row textarea').on('focus', function() {
            $(this).closest('.schedspot-form-row').addClass('focused');
        }).on('blur', function() {
            $(this).closest('.schedspot-form-row').removeClass('focused');

            // Clear error state when user starts typing
            if ($(this).val().trim()) {
                $(this).closest('.schedspot-form-row').removeClass('error');
                $(this).siblings('.error-message').remove();
            }
        });
    }

    /**
     * Validate entire form
     */
    function validateForm($form) {
        var isValid = true;
        var firstError = null;

        // Clear previous errors
        $('.schedspot-form-row').removeClass('error');
        $('.error-message').remove();

        // Validate required fields
        $form.find('[required]').each(function() {
            if (!$(this).val().trim()) {
                isValid = false;
                var $row = $(this).closest('.schedspot-form-row');
                $row.addClass('error');

                if (!firstError) {
                    firstError = $row;
                }

                var label = $row.find('label').text().replace('*', '').trim();
                $row.append('<div class="error-message">' + label + ' is required.</div>');
            }
        });

        // Validate email format
        var email = $('#schedspot_client_email').val();
        if (email && !isValidEmail(email)) {
            isValid = false;
            var $row = $('#schedspot_client_email').closest('.schedspot-form-row');
            $row.addClass('error');
            $row.append('<div class="error-message">Please enter a valid email address.</div>');

            if (!firstError) {
                firstError = $row;
            }
        }

        // Validate phone format (basic)
        var phone = $('#schedspot_client_phone').val();
        if (phone && phone.length < 10) {
            isValid = false;
            var $row = $('#schedspot_client_phone').closest('.schedspot-form-row');
            $row.addClass('error');
            $row.append('<div class="error-message">Please enter a valid phone number.</div>');

            if (!firstError) {
                firstError = $row;
            }
        }

        if (!isValid) {
            // Scroll to first error
            if (firstError) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 500);
            }

            // Show error notification
            showNotification('Please correct the errors below.', 'error');
        }

        return isValid;
    }

    /**
     * Validate individual field
     */
    function validateField($field) {
        var $row = $field.closest('.schedspot-form-row');
        var value = $field.val().trim();

        // Clear previous error
        $row.removeClass('error');
        $row.find('.error-message').remove();

        // Check if required field is empty
        if ($field.prop('required') && !value) {
            var label = $row.find('label').text().replace('*', '').trim();
            showFieldError($field, label + ' is required.');
            return false;
        }

        return true;
    }

    /**
     * Show field error
     */
    function showFieldError($field, message) {
        var $row = $field.closest('.schedspot-form-row');
        $row.addClass('error');
        $row.find('.error-message').remove();
        $row.append('<div class="error-message">' + message + '</div>');
    }

    /**
     * Select worker
     */
    function selectWorker(workerId) {
        // Update hidden field
        $('#schedspot_worker_id').val(workerId);

        // Update UI
        $('.worker-card').removeClass('selected');
        $('.worker-card[data-worker-id="' + workerId + '"]').addClass('selected');

        // Switch to manual mode if not already
        $('input[name="worker_selection_mode"][value="manual"]').prop('checked', true);
        $('#manual-worker-selection').show();

        // Show confirmation
        showWorkerSelected(workerId);
    }

    /**
     * Show worker selected confirmation
     */
    function showWorkerSelected(workerId) {
        var workerName = $('.worker-card[data-worker-id="' + workerId + '"] h4').text();
        var message = schedspot_frontend.strings.selected_worker + ': ' + workerName;
        showNotification(message, 'success');
    }

    /**
     * Load service details
     */
    function loadServiceDetails(serviceId) {
        // This could load service-specific information
        // For now, just update worker availability based on service
        if (typeof updateWorkerAvailability === 'function') {
            updateWorkerAvailability(serviceId);
        }
    }

    /**
     * Show notification
     */
    function showNotification(message, type) {
        // Remove existing notifications
        $('.schedspot-notice').remove();

        var notificationClass = 'schedspot-notice';
        if (type) {
            notificationClass += ' ' + type;
        }

        var notification = $('<div class="' + notificationClass + '">' + message + '</div>');
        $('.schedspot-form-container').prepend(notification);

        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Validate email format
     */
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Message worker function (for dashboard)
     */
    window.messageWorker = function(workerId, bookingId) {
        var messagesUrl = schedspot_frontend.messages_url + '?user_id=' + workerId + '&booking_id=' + bookingId;
        window.location.href = messagesUrl;
    };

    // Export functions for global access
    window.SchedSpotBookingForm = {
        selectWorker: selectWorker,
        showNotification: showNotification,
        validateForm: validateForm,
        isValidEmail: isValidEmail
    };

})(jQuery);
