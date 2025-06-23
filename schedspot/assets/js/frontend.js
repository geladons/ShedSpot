/**
 * SchedSpot Frontend JavaScript
 *
 * @package SchedSpot
 * @version 1.0.0
 */

(function($) {
    'use strict';

    var SchedSpotFrontend = {
        restUrl: '',
        nonce: '',
        
        /**
         * Initialize frontend functionality
         */
        init: function() {
            this.restUrl = schedspot_frontend.rest_url;
            this.nonce = schedspot_frontend.nonce;
            
            this.bindEvents();
            this.initFormValidation();
            this.initWorkerSelection();
            this.initAvailabilityCheck();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Service selection change handler
            $(document).on('change', '#schedspot_service_id', this.handleServiceChange.bind(this));
            
            // Worker selection change handler
            $(document).on('change', 'input[name="worker_selection_mode"]', this.handleWorkerSelectionMode.bind(this));
            
            // Availability check on date/time change
            $(document).on('change', '#schedspot_date, #schedspot_start_time, #schedspot_duration', this.checkAvailability.bind(this));
            
            // Form submission
            $(document).on('submit', '#schedspot-booking-form', this.handleFormSubmission.bind(this));
            
            // Auto-resize textareas
            $(document).on('input', 'textarea', this.autoResizeTextarea);

            // Booking detail view handlers
            $(document).on('click', '.view-booking-details', this.showBookingDetails.bind(this));
            $(document).on('click', '.close-booking-modal, .schedspot-modal-overlay', this.closeBookingModal.bind(this));

            // Prevent modal from closing when clicking inside modal content
            $(document).on('click', '.schedspot-modal', function(e) {
                e.stopPropagation();
            });

            // Payment action handlers
            $(document).on('click', '.request-deposit', this.requestDeposit.bind(this));
            $(document).on('click', '.send-invoice', this.sendInvoice.bind(this));
            $(document).on('click', '.pay-invoice', this.payInvoice.bind(this));
        },

        /**
         * Handle service selection change
         */
        handleServiceChange: function(e) {
            var serviceId = $(e.target).val();
            var workerSelect = $('#schedspot_worker_id');
            var workerGrid = $('.schedspot-workers-grid');

            if (!serviceId) {
                workerSelect.empty().append('<option value="">' + schedspot_frontend.strings.any_worker + '</option>');
                workerGrid.empty();
                return;
            }

            // Show loading state
            workerSelect.prop('disabled', true);
            workerGrid.html('<div class="loading">' + schedspot_frontend.strings.loading_workers + '</div>');

            // Fetch workers for this service
            $.ajax({
                url: this.restUrl + 'workers',
                method: 'GET',
                data: { service_id: serviceId },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                }.bind(this),
                success: function(workers) {
                    this.populateWorkers(workers, workerSelect, workerGrid);
                }.bind(this),
                error: function() {
                    this.showError(schedspot_frontend.strings.error_loading_workers);
                }.bind(this),
                complete: function() {
                    workerSelect.prop('disabled', false);
                }
            });
        },

        /**
         * Populate workers in select and grid
         */
        populateWorkers: function(workers, workerSelect, workerGrid) {
            // Populate select dropdown
            workerSelect.empty();
            workerSelect.append('<option value="">' + schedspot_frontend.strings.any_worker + '</option>');

            // Populate worker grid
            workerGrid.empty();

            if (workers.length === 0) {
                workerGrid.html('<p>' + schedspot_frontend.strings.no_workers_available + '</p>');
                return;
            }

            workers.forEach(function(worker) {
                // Add to select
                workerSelect.append('<option value="' + worker.id + '">' + worker.name + '</option>');

                // Add to grid
                var workerCard = this.createWorkerCard(worker);
                workerGrid.append(workerCard);
            }.bind(this));
        },

        /**
         * Create worker card HTML
         */
        createWorkerCard: function(worker) {
            var ratingStars = this.generateStars(worker.rating || 0);
            var skills = worker.skills ? worker.skills.join(', ') : '';
            
            return `
                <div class="schedspot-worker-card" data-worker-id="${worker.id}">
                    <div class="worker-avatar">
                        <img src="${worker.avatar || schedspot_frontend.default_avatar}" alt="${worker.name}">
                        <div class="availability-indicator ${worker.available ? 'available' : 'busy'}"></div>
                    </div>
                    <div class="worker-info">
                        <h4>${worker.name}</h4>
                        <div class="worker-rating">
                            ${ratingStars}
                            <span class="rating-count">(${worker.review_count || 0})</span>
                        </div>
                        <div class="worker-rate">$${worker.hourly_rate}/hr</div>
                        <div class="worker-skills">${skills}</div>
                        <div class="worker-stats">
                            <span>${worker.completed_jobs || 0} jobs completed</span>
                        </div>
                    </div>
                    <div class="worker-actions">
                        <button type="button" class="schedspot-btn schedspot-btn-small select-worker" data-worker-id="${worker.id}">
                            ${schedspot_frontend.strings.select_worker}
                        </button>
                    </div>
                </div>
            `;
        },

        /**
         * Generate star rating HTML
         */
        generateStars: function(rating) {
            var stars = '';
            var fullStars = Math.floor(rating);
            var hasHalfStar = rating % 1 !== 0;

            for (var i = 0; i < 5; i++) {
                if (i < fullStars) {
                    stars += '<span class="star filled">★</span>';
                } else if (i === fullStars && hasHalfStar) {
                    stars += '<span class="star half">★</span>';
                } else {
                    stars += '<span class="star empty">☆</span>';
                }
            }

            return stars;
        },

        /**
         * Handle worker selection mode change
         */
        handleWorkerSelectionMode: function(e) {
            var mode = $(e.target).val();
            var workerSelect = $('#schedspot_worker_id').closest('.schedspot-form-row');
            var workerGrid = $('.schedspot-workers-grid').closest('.schedspot-form-row');

            if (mode === 'auto') {
                workerSelect.hide();
                workerGrid.hide();
            } else {
                workerSelect.show();
                workerGrid.show();
            }
        },

        /**
         * Check availability when date/time changes
         */
        checkAvailability: function() {
            var workerId = $('#schedspot_worker_id').val();
            var date = $('#schedspot_date').val();
            var startTime = $('#schedspot_start_time').val();
            var duration = $('#schedspot_duration').val();

            if (!date || !startTime || !duration) {
                return;
            }

            $.ajax({
                url: this.restUrl + 'availability/check',
                method: 'POST',
                data: {
                    worker_id: workerId,
                    date: date,
                    start_time: startTime,
                    duration: duration
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                }.bind(this),
                success: function(response) {
                    this.showAvailabilityMessage(response);
                }.bind(this),
                error: function() {
                    this.showError(schedspot_frontend.strings.error_checking_availability);
                }.bind(this)
            });
        },

        /**
         * Show availability message
         */
        showAvailabilityMessage: function(response) {
            var messageClass = response.available ? 'schedspot-notice-success' : 'schedspot-notice-error';
            var messageHtml = '<div class="schedspot-notice ' + messageClass + '">' + response.message + '</div>';

            $('.schedspot-availability-message').remove();
            $('#schedspot-booking-form').prepend(messageHtml);

            // Auto-hide success messages after 5 seconds
            if (response.available) {
                setTimeout(function() {
                    $('.schedspot-notice-success').fadeOut();
                }, 5000);
            }
        },

        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            // Real-time validation
            $(document).on('blur', 'input[required], select[required], textarea[required]', function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    $field.addClass('error');
                    this.showFieldError($field, schedspot_frontend.strings.field_required);
                } else {
                    $field.removeClass('error');
                    this.hideFieldError($field);
                }
            }.bind(this));

            // Email validation
            $(document).on('blur', 'input[type="email"]', function() {
                var $field = $(this);
                var email = $field.val().trim();
                
                if (email && !this.isValidEmail(email)) {
                    $field.addClass('error');
                    this.showFieldError($field, schedspot_frontend.strings.invalid_email);
                } else {
                    $field.removeClass('error');
                    this.hideFieldError($field);
                }
            }.bind(this));
        },

        /**
         * Initialize worker selection
         */
        initWorkerSelection: function() {
            $(document).on('click', '.select-worker', function(e) {
                var workerId = $(e.target).data('worker-id');
                $('#schedspot_worker_id').val(workerId);
                
                // Update visual selection
                $('.schedspot-worker-card').removeClass('selected');
                $(e.target).closest('.schedspot-worker-card').addClass('selected');
                
                // Check availability for selected worker
                this.checkAvailability();
            }.bind(this));
        },

        /**
         * Initialize availability checking
         */
        initAvailabilityCheck: function() {
            // Debounce availability checks
            var availabilityTimeout;
            $(document).on('change', '#schedspot_date, #schedspot_start_time, #schedspot_duration', function() {
                clearTimeout(availabilityTimeout);
                availabilityTimeout = setTimeout(this.checkAvailability.bind(this), 500);
            }.bind(this));
        },

        /**
         * Handle form submission
         */
        handleFormSubmission: function(e) {
            e.preventDefault();
            
            var $form = $(e.target);
            var $submitBtn = $form.find('button[type="submit"]');
            
            // Validate form
            if (!this.validateForm($form)) {
                return false;
            }
            
            // Show loading state
            $submitBtn.prop('disabled', true).text(schedspot_frontend.strings.processing);
            
            // Submit form via AJAX
            var formData = new FormData($form[0]);
            
            $.ajax({
                url: $form.attr('action') || window.location.href,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        this.showSuccess(response.data.message);
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 2000);
                        }
                    } else {
                        this.showError(response.data.message);
                    }
                }.bind(this),
                error: function() {
                    this.showError(schedspot_frontend.strings.error_submitting_form);
                }.bind(this),
                complete: function() {
                    $submitBtn.prop('disabled', false).text(schedspot_frontend.strings.submit_booking);
                }
            });
        },

        /**
         * Validate form
         */
        validateForm: function($form) {
            var isValid = true;
            
            $form.find('input[required], select[required], textarea[required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    $field.addClass('error');
                    this.showFieldError($field, schedspot_frontend.strings.field_required);
                    isValid = false;
                } else {
                    $field.removeClass('error');
                    this.hideFieldError($field);
                }
            }.bind(this));
            
            return isValid;
        },

        /**
         * Auto-resize textarea
         */
        autoResizeTextarea: function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        },

        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            var $error = $field.siblings('.field-error');
            if ($error.length === 0) {
                $error = $('<div class="field-error"></div>');
                $field.after($error);
            }
            $error.text(message);
        },

        /**
         * Hide field error
         */
        hideFieldError: function($field) {
            $field.siblings('.field-error').remove();
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            var messageHtml = '<div class="schedspot-notice schedspot-notice-success">' + message + '</div>';
            $('.schedspot-notice').remove();
            $('#schedspot-booking-form').prepend(messageHtml);
            
            $('html, body').animate({
                scrollTop: $('#schedspot-booking-form').offset().top - 50
            }, 500);
        },

        /**
         * Show error message
         */
        showError: function(message) {
            var messageHtml = '<div class="schedspot-notice schedspot-notice-error">' + message + '</div>';
            $('.schedspot-notice').remove();
            $('#schedspot-booking-form').prepend(messageHtml);
            
            $('html, body').animate({
                scrollTop: $('#schedspot-booking-form').offset().top - 50
            }, 500);
        },

        /**
         * Validate email format
         */
        isValidEmail: function(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        /**
         * Initialize messaging functionality
         */
        initMessaging: function() {
            if (typeof SchedSpotMessaging !== 'undefined') {
                SchedSpotMessaging.init();
            }
        },

        /**
         * Show booking details modal
         */
        showBookingDetails: function(e) {
            e.preventDefault();
            var bookingId = $(e.target).data('booking-id');

            if (!bookingId) {
                this.showError('Invalid booking ID');
                return;
            }

            // Show loading modal
            this.showModal('Loading booking details...', '<div class="loading">Please wait...</div>');

            // Fetch booking details
            $.ajax({
                url: this.restUrl + 'bookings/' + bookingId,
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                }.bind(this),
                success: function(booking) {
                    this.renderBookingDetails(booking);
                }.bind(this),
                error: function() {
                    this.showError('Error loading booking details. Please try again.');
                    this.closeBookingModal();
                }.bind(this)
            });
        },

        /**
         * Render booking details in modal
         */
        renderBookingDetails: function(booking) {
            var modalContent = this.generateBookingDetailsHTML(booking);
            this.showModal('Booking Details', modalContent);
        },

        /**
         * Generate booking details HTML
         */
        generateBookingDetailsHTML: function(booking) {
            var statusClass = 'status-' + booking.status;
            var paymentStatusClass = 'payment-' + (booking.payment_status || 'pending');

            return `
                <div class="booking-details-modal">
                    <div class="booking-header">
                        <h3>Booking #${booking.id}</h3>
                        <span class="status-badge ${statusClass}">${this.capitalizeFirst(booking.status)}</span>
                    </div>

                    <div class="booking-info-grid">
                        <div class="booking-section">
                            <h4>Service Information</h4>
                            <div class="info-row">
                                <span class="label">Service:</span>
                                <span class="value">${booking.service_name || 'General Service'}</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Date:</span>
                                <span class="value">${this.formatDate(booking.booking_date)}</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Time:</span>
                                <span class="value">${this.formatTime(booking.start_time)} - ${this.formatTime(booking.end_time)}</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Duration:</span>
                                <span class="value">${booking.duration} minutes</span>
                            </div>
                        </div>

                        <div class="booking-section">
                            <h4>Contact Information</h4>
                            <div class="info-row">
                                <span class="label">Customer:</span>
                                <span class="value">${booking.customer_name}</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Worker:</span>
                                <span class="value">${booking.worker_name}</span>
                            </div>
                            ${booking.client_details && booking.client_details.phone ? `
                            <div class="info-row">
                                <span class="label">Phone:</span>
                                <span class="value">${booking.client_details.phone}</span>
                            </div>
                            ` : ''}
                            ${booking.client_details && booking.client_details.address ? `
                            <div class="info-row">
                                <span class="label">Address:</span>
                                <span class="value">${booking.client_details.address}</span>
                            </div>
                            ` : ''}
                        </div>

                        <div class="booking-section">
                            <h4>Payment Information</h4>
                            <div class="info-row">
                                <span class="label">Total Cost:</span>
                                <span class="value">$${parseFloat(booking.total_cost).toFixed(2)}</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Payment Status:</span>
                                <span class="payment-status ${paymentStatusClass}">${this.capitalizeFirst(booking.payment_status || 'pending')}</span>
                            </div>
                            ${booking.deposit_amount ? `
                            <div class="info-row">
                                <span class="label">Deposit:</span>
                                <span class="value">$${parseFloat(booking.deposit_amount).toFixed(2)}</span>
                            </div>
                            ` : ''}
                        </div>

                        ${booking.notes ? `
                        <div class="booking-section full-width">
                            <h4>Notes</h4>
                            <div class="booking-notes">${booking.notes}</div>
                        </div>
                        ` : ''}
                    </div>

                    <div class="booking-actions">
                        ${this.generateBookingActionButtons(booking)}
                    </div>
                </div>
            `;
        },

        /**
         * Generate action buttons based on booking status and user role
         */
        generateBookingActionButtons: function(booking) {
            var buttons = [];
            var currentUserId = schedspot_frontend.current_user_id;
            var isWorker = booking.worker_id == currentUserId;
            var isCustomer = booking.user_id == currentUserId;

            // Worker actions
            if (isWorker) {
                if (booking.status === 'pending') {
                    buttons.push('<button class="schedspot-btn schedspot-btn-success accept-booking" data-booking-id="' + booking.id + '">Accept Booking</button>');
                    buttons.push('<button class="schedspot-btn schedspot-btn-secondary decline-booking" data-booking-id="' + booking.id + '">Decline</button>');
                }

                if (booking.status === 'confirmed' && booking.payment_status !== 'completed') {
                    buttons.push('<button class="schedspot-btn request-deposit" data-booking-id="' + booking.id + '">Request Deposit</button>');
                }

                if (booking.status === 'in_progress') {
                    buttons.push('<button class="schedspot-btn schedspot-btn-success complete-booking" data-booking-id="' + booking.id + '">Mark Complete</button>');
                }

                if (booking.status === 'completed' && booking.payment_status !== 'completed') {
                    buttons.push('<button class="schedspot-btn send-invoice" data-booking-id="' + booking.id + '">Send Invoice</button>');
                }
            }

            // Customer actions
            if (isCustomer) {
                if (booking.payment_status === 'pending' || booking.payment_status === 'partial') {
                    buttons.push('<button class="schedspot-btn schedspot-btn-success pay-invoice" data-booking-id="' + booking.id + '">Pay Now</button>');
                }

                if (booking.status === 'pending' || booking.status === 'confirmed') {
                    buttons.push('<button class="schedspot-btn schedspot-btn-warning reschedule-booking" data-booking-id="' + booking.id + '">Reschedule</button>');
                    buttons.push('<button class="schedspot-btn schedspot-btn-secondary cancel-booking" data-booking-id="' + booking.id + '">Cancel</button>');
                }
            }

            // Common actions
            buttons.push('<button class="schedspot-btn schedspot-btn-link send-message" data-user-id="' + (isWorker ? booking.user_id : booking.worker_id) + '" data-booking-id="' + booking.id + '">Send Message</button>');

            return buttons.join(' ');
        },

        /**
         * Show modal dialog
         */
        showModal: function(title, content) {
            var modalHTML = `
                <div class="schedspot-modal-overlay">
                    <div class="schedspot-modal">
                        <div class="schedspot-modal-header">
                            <h3>${title}</h3>
                            <button class="close-booking-modal">&times;</button>
                        </div>
                        <div class="schedspot-modal-content">
                            ${content}
                        </div>
                    </div>
                </div>
            `;

            // Remove existing modal
            $('.schedspot-modal-overlay').remove();

            // Add new modal
            $('body').append(modalHTML);

            // Prevent body scroll
            $('body').addClass('modal-open');
        },

        /**
         * Close booking modal
         */
        closeBookingModal: function() {
            $('.schedspot-modal-overlay').remove();
            $('body').removeClass('modal-open');
        },

        /**
         * Request deposit payment
         */
        requestDeposit: function(e) {
            var bookingId = $(e.target).data('booking-id');

            $.ajax({
                url: this.restUrl + 'payments/request-deposit',
                method: 'POST',
                data: {
                    booking_id: bookingId
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                }.bind(this),
                success: function(response) {
                    this.showSuccess('Deposit request sent successfully!');
                    this.closeBookingModal();
                }.bind(this),
                error: function() {
                    this.showError('Error sending deposit request. Please try again.');
                }.bind(this)
            });
        },

        /**
         * Send invoice to customer
         */
        sendInvoice: function(e) {
            var bookingId = $(e.target).data('booking-id');

            $.ajax({
                url: this.restUrl + 'payments/send-invoice',
                method: 'POST',
                data: {
                    booking_id: bookingId
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', this.nonce);
                }.bind(this),
                success: function(response) {
                    this.showSuccess('Invoice sent successfully!');
                    this.closeBookingModal();
                }.bind(this),
                error: function() {
                    this.showError('Error sending invoice. Please try again.');
                }.bind(this)
            });
        },

        /**
         * Pay invoice
         */
        payInvoice: function(e) {
            var bookingId = $(e.target).data('booking-id');

            // Redirect to payment page
            window.location.href = schedspot_frontend.payment_url + '?booking_id=' + bookingId;
        },

        /**
         * Utility functions
         */
        capitalizeFirst: function(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        },

        formatDate: function(dateStr) {
            var date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        formatTime: function(timeStr) {
            var time = new Date('1970-01-01T' + timeStr);
            return time.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if (typeof schedspot_frontend !== 'undefined') {
            SchedSpotFrontend.init();
            SchedSpotFrontend.initMessaging();
        }
    });

})(jQuery);
