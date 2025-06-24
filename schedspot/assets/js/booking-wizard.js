/**
 * SchedSpot Booking Wizard JavaScript
 * Modern, step-by-step booking interface
 *
 * @package SchedSpot
 * @version 1.0.0
 */

(function($) {
    'use strict';

    class SchedSpotBookingWizard {
        constructor() {
            this.currentStep = 1;
            this.totalSteps = 5;
            this.bookingData = {};
            this.init();
        }

        init() {
            this.bindEvents();
            this.updateProgress();
            this.loadInitialData();
        }

        bindEvents() {
            // Navigation buttons
            $(document).on('click', '.nav-btn-next', () => this.nextStep());
            $(document).on('click', '.nav-btn-prev', () => this.prevStep());

            // Service selection
            $(document).on('click', '.service-card', (e) => this.selectService(e));

            // Worker selection
            $(document).on('click', '.worker-card', (e) => this.selectWorker(e));

            // Date selection
            $(document).on('change', '#booking_date', (e) => this.selectDate(e));

            // Time slot selection
            $(document).on('click', '.time-slot:not(.unavailable)', (e) => this.selectTime(e));

            // Form inputs
            $(document).on('input', '.form-input, .form-textarea', (e) => this.updateContactInfo(e));

            // Final submission
            $(document).on('click', '.submit-booking', () => this.submitBooking());

            // Keyboard navigation
            $(document).on('keydown', (e) => this.handleKeyboard(e));
        }

        nextStep() {
            if (!this.validateCurrentStep()) {
                return;
            }

            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                this.updateStep();
                this.updateProgress();
            }
        }

        prevStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.updateStep();
                this.updateProgress();
            }
        }

        updateStep() {
            // Hide all steps
            $('.booking-step').removeClass('active prev');

            // Show current step
            $(`.booking-step[data-step="${this.currentStep}"]`).addClass('active');

            // Mark previous steps
            for (let i = 1; i < this.currentStep; i++) {
                $(`.booking-step[data-step="${i}"]`).addClass('prev');
            }

            // Update navigation buttons
            $('.nav-btn-prev').prop('disabled', this.currentStep === 1);
            $('.nav-btn-next').toggle(this.currentStep < this.totalSteps);
            $('.submit-booking').toggle(this.currentStep === this.totalSteps);

            // Load step-specific data
            this.loadStepData();

            // Scroll to top of wizard
            $('.schedspot-booking-wizard')[0].scrollIntoView({ behavior: 'smooth' });
        }

        updateProgress() {
            // Update progress bar
            $('.progress-step').each((index, element) => {
                const stepNumber = index + 1;
                $(element).removeClass('completed active');

                if (stepNumber < this.currentStep) {
                    $(element).addClass('completed');
                } else if (stepNumber === this.currentStep) {
                    $(element).addClass('active');
                }
            });

            // Update step info
            $('.progress-step-info').text(`Step ${this.currentStep} of ${this.totalSteps}`);
        }

        validateCurrentStep() {
            switch (this.currentStep) {
                case 1: // Service selection
                    if (!this.bookingData.service_id) {
                        this.showError('Please select a service');
                        return false;
                    }
                    break;

                case 2: // Worker selection
                    if (!this.bookingData.worker_id) {
                        this.showError('Please select a service provider');
                        return false;
                    }
                    break;

                case 3: // Date & Time
                    if (!this.bookingData.date || !this.bookingData.time) {
                        this.showError('Please select both date and time');
                        return false;
                    }
                    break;

                case 4: // Contact info
                    const requiredFields = ['name', 'email', 'phone'];
                    for (const field of requiredFields) {
                        if (!this.bookingData[field] || this.bookingData[field].trim() === '') {
                            this.showError(`Please fill in your ${field}`);
                            return false;
                        }
                    }
                    
                    // Validate email
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(this.bookingData.email)) {
                        this.showError('Please enter a valid email address');
                        return false;
                    }
                    break;
            }

            return true;
        }

        selectService(e) {
            const $card = $(e.currentTarget);
            const serviceId = $card.data('service-id');

            // Update selection
            $('.service-card').removeClass('selected');
            $card.addClass('selected');

            // Store data
            this.bookingData.service_id = serviceId;
            this.bookingData.service_name = $card.find('.service-name').text();
            this.bookingData.service_price = $card.data('price');
            this.bookingData.service_duration = $card.data('duration');

            // Enable next button
            this.updateNavigationState();
        }

        selectWorker(e) {
            const $card = $(e.currentTarget);
            const workerId = $card.data('worker-id');

            // Update selection
            $('.worker-card').removeClass('selected');
            $card.addClass('selected');

            // Store data
            this.bookingData.worker_id = workerId;
            this.bookingData.worker_name = $card.find('.worker-name').text();

            // Enable next button
            this.updateNavigationState();
        }

        selectDate(e) {
            const date = $(e.target).val();
            this.bookingData.date = date;

            if (date && this.bookingData.service_id && this.bookingData.worker_id) {
                this.loadTimeSlots();
            }

            this.updateNavigationState();
        }

        selectTime(e) {
            const $slot = $(e.currentTarget);
            const time = $slot.data('time');

            // Update selection
            $('.time-slot').removeClass('selected');
            $slot.addClass('selected');

            // Store data
            this.bookingData.time = time;

            // Enable next button
            this.updateNavigationState();
        }

        updateContactInfo(e) {
            const $input = $(e.target);
            const field = $input.attr('name');
            const value = $input.val();

            this.bookingData[field] = value;
            this.updateNavigationState();
        }

        loadStepData() {
            switch (this.currentStep) {
                case 2: // Load workers for selected service
                    if (this.bookingData.service_id) {
                        this.loadWorkers();
                    }
                    break;

                case 5: // Update confirmation summary
                    this.updateConfirmationSummary();
                    break;
            }
        }

        loadWorkers() {
            const $container = $('.workers-grid');
            $container.html('<div class="loading">Loading service providers...</div>');

            $.ajax({
                url: schedspot_frontend.rest_url + 'workers',
                type: 'GET',
                data: {
                    service_id: this.bookingData.service_id
                },
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
                },
                success: (response) => {
                    console.log('Workers API Response:', response); // Debug log
                    if (response && response.length > 0) {
                        this.displayWorkers(response);
                    } else {
                        $container.html('<div class="error">No service providers available for this service</div>');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Workers API Error:', xhr.responseText, status, error); // Debug log
                    $container.html('<div class="error">Error loading service providers. Please try again.</div>');
                }
            });
        }

        displayWorkers(workers) {
            const $container = $('.workers-grid');
            let html = '';

            workers.forEach(worker => {
                // Handle both API response formats (ID vs id, display_name vs name)
                const workerId = worker.id || worker.ID;
                const workerName = worker.name || worker.display_name;
                const workerAvatar = worker.avatar || schedspot_frontend.default_avatar || 'https://via.placeholder.com/60';
                const workerRating = worker.rating || '5.0';
                const workerRate = worker.hourly_rate || worker.rate || '50';

                html += `
                    <div class="worker-card" data-worker-id="${workerId}">
                        <div class="worker-avatar">
                            <img src="${workerAvatar}" alt="${workerName}">
                        </div>
                        <div class="worker-name">${workerName}</div>
                        <div class="worker-rating">★★★★★ (${workerRating})</div>
                        <div class="worker-rate">$${workerRate}/hr</div>
                        <div class="worker-bio">${worker.bio || ''}</div>
                    </div>
                `;
            });

            $container.html(html);
        }

        loadTimeSlots() {
            const $container = $('.time-slots');
            $container.html('<div class="loading">Loading available times...</div>');

            $.ajax({
                url: schedspot_frontend.rest_url + 'availability',
                type: 'GET',
                data: {
                    date: this.bookingData.date,
                    service_id: this.bookingData.service_id,
                    worker_id: this.bookingData.worker_id
                },
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
                },
                success: (response) => {
                    if (response && response.time_slots) {
                        this.displayTimeSlots(response.time_slots);
                    } else {
                        $container.html('<div class="error">No available times for this date</div>');
                    }
                },
                error: () => {
                    $container.html('<div class="error">Error loading available times</div>');
                }
            });
        }

        displayTimeSlots(timeSlots) {
            const $container = $('.time-slots');
            let html = '';

            timeSlots.forEach(slot => {
                const availableClass = slot.available ? '' : ' unavailable';
                html += `
                    <div class="time-slot${availableClass}" data-time="${slot.time}">
                        ${slot.formatted_time}
                    </div>
                `;
            });

            $container.html(html);
        }

        updateConfirmationSummary() {
            const summary = {
                'Service': this.bookingData.service_name,
                'Provider': this.bookingData.worker_name,
                'Date': this.formatDate(this.bookingData.date),
                'Time': this.bookingData.time,
                'Duration': `${this.bookingData.service_duration} minutes`,
                'Total Cost': `$${parseFloat(this.bookingData.service_price).toFixed(2)}`
            };

            let html = '';
            Object.entries(summary).forEach(([label, value]) => {
                html += `
                    <div class="summary-item">
                        <span class="summary-label">${label}:</span>
                        <span class="summary-value">${value}</span>
                    </div>
                `;
            });

            $('.booking-summary').html(`
                <div class="summary-title">Booking Summary</div>
                ${html}
            `);
        }

        submitBooking() {
            const $btn = $('.submit-booking');
            $btn.addClass('loading').prop('disabled', true);

            $.ajax({
                url: schedspot_frontend.rest_url + 'bookings',
                type: 'POST',
                data: JSON.stringify(this.bookingData),
                contentType: 'application/json',
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
                },
                success: (response) => {
                    if (response && response.id) {
                        this.showSuccess('Booking submitted successfully!');
                        setTimeout(() => {
                            window.location.href = '/?schedspot_action=dashboard';
                        }, 2000);
                    } else {
                        this.showError('Error submitting booking');
                        $btn.removeClass('loading').prop('disabled', false);
                    }
                },
                error: () => {
                    this.showError('Error submitting booking. Please try again.');
                    $btn.removeClass('loading').prop('disabled', false);
                }
            });
        }

        updateNavigationState() {
            const isValid = this.validateCurrentStep();
            $('.nav-btn-next').prop('disabled', !isValid);
        }

        loadInitialData() {
            // Pre-populate with URL parameters if available
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.get('service_id')) {
                this.bookingData.service_id = urlParams.get('service_id');
                $(`.service-card[data-service-id="${this.bookingData.service_id}"]`).click();
            }

            if (urlParams.get('worker_id')) {
                this.bookingData.worker_id = urlParams.get('worker_id');
            }
        }

        handleKeyboard(e) {
            if (e.key === 'Enter' && !$(e.target).is('textarea')) {
                e.preventDefault();
                if (this.currentStep < this.totalSteps) {
                    this.nextStep();
                } else {
                    this.submitBooking();
                }
            }
        }

        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        showError(message) {
            this.showNotification(message, 'error');
        }

        showSuccess(message) {
            this.showNotification(message, 'success');
        }

        showNotification(message, type = 'info') {
            // Remove existing notifications
            $('.booking-notification').remove();

            const notification = $(`
                <div class="booking-notification ${type}">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">&times;</button>
                </div>
            `);

            $('.schedspot-booking-wizard').prepend(notification);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);

            // Manual close
            notification.find('.notification-close').on('click', () => {
                notification.fadeOut(() => notification.remove());
            });
        }
    }

    // Initialize booking wizard when DOM is ready
    $(document).ready(() => {
        if ($('.schedspot-booking-wizard').length > 0) {
            window.schedspotBookingWizard = new SchedSpotBookingWizard();
        }
    });

})(jQuery);
