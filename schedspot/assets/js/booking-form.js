/**
 * SchedSpot Booking Form JavaScript
 * 
 * @package SchedSpot
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Global variables
    let selectedWorkerId = null;
    let availableWorkers = [];
    let bookingMap = null;
    let clientMarker = null;

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

        // Form validation and enhancement
        $('#schedspot-booking-form').on('submit', handleFormSubmission);

        // Worker selection mode toggle
        $('input[name="worker_selection_mode"]').change(handleWorkerSelectionModeChange);

        // Worker card selection
        $(document).on('click', '.worker-card.available', handleWorkerCardClick);
        $(document).on('click', '.select-worker-btn', handleSelectWorkerButton);

        // Service selection change handler
        $('#schedspot_service_id').change(handleServiceChange);

        // Auto-resize textareas
        $('textarea').on('input', autoResizeTextarea);

        // Form field focus effects
        $('.schedspot-form-row input, .schedspot-form-row select, .schedspot-form-row textarea')
            .on('focus', handleFieldFocus)
            .on('blur', handleFieldBlur);

        // Geolocation functionality
        if (typeof schedspot_frontend !== 'undefined' && schedspot_frontend.geolocation_enabled) {
            initGeolocation();
        }
    }

    /**
     * Handle form submission with validation
     */
    function handleFormSubmission(e) {
        let isValid = true;
        let firstError = null;

        // Clear previous errors
        $('.schedspot-form-row').removeClass('error');
        $('.error-message').remove();

        // Validate required fields
        $(this).find('[required]').each(function() {
            if (!$(this).val().trim()) {
                isValid = false;
                const $row = $(this).closest('.schedspot-form-row');
                $row.addClass('error');

                if (!firstError) {
                    firstError = $row;
                }

                const label = $row.find('label').text().replace('*', '').trim();
                $row.append('<div class="error-message">' + label + ' is required.</div>');
            }
        });

        // Validate email format
        const email = $('#schedspot_client_email').val();
        if (email && !isValidEmail(email)) {
            isValid = false;
            const $row = $('#schedspot_client_email').closest('.schedspot-form-row');
            $row.addClass('error');
            $row.append('<div class="error-message">Please enter a valid email address.</div>');

            if (!firstError) {
                firstError = $row;
            }
        }

        // Validate phone format (basic)
        const phone = $('#schedspot_client_phone').val();
        if (phone && phone.length < 10) {
            isValid = false;
            const $row = $('#schedspot_client_phone').closest('.schedspot-form-row');
            $row.addClass('error');
            $row.append('<div class="error-message">Please enter a valid phone number.</div>');

            if (!firstError) {
                firstError = $row;
            }
        }

        if (!isValid) {
            e.preventDefault();

            // Scroll to first error
            if (firstError) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 500);
            }

            // Show error notification
            showNotification('Please correct the errors below.', 'error');
            return false;
        }

        // Show loading state
        const $submitBtn = $(this).find('[type="submit"]');
        $submitBtn.prop('disabled', true);
        $submitBtn.html('<span class="dashicons dashicons-update spin"></span> Processing...');
    }

    /**
     * Handle worker selection mode change
     */
    function handleWorkerSelectionModeChange() {
        if ($(this).val() === 'manual') {
            $('#manual-worker-selection').slideDown();
        } else {
            $('#manual-worker-selection').slideUp();
            $('#schedspot_worker_id').val('');
            $('.worker-card').removeClass('selected');
        }
    }

    /**
     * Handle worker card click
     */
    function handleWorkerCardClick() {
        const workerId = $(this).data('worker-id');
        selectWorker(workerId);
    }

    /**
     * Handle select worker button click
     */
    function handleSelectWorkerButton(e) {
        e.stopPropagation();
        const workerId = $(this).data('worker-id');
        selectWorker(workerId);
    }

    /**
     * Handle service selection change
     */
    function handleServiceChange() {
        const serviceId = $(this).val();
        if (serviceId) {
            loadServiceDetails(serviceId);
        }
    }

    /**
     * Auto-resize textarea
     */
    function autoResizeTextarea() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    }

    /**
     * Handle form field focus
     */
    function handleFieldFocus() {
        $(this).closest('.schedspot-form-row').addClass('focused');
    }

    /**
     * Handle form field blur
     */
    function handleFieldBlur() {
        $(this).closest('.schedspot-form-row').removeClass('focused');

        // Clear error state when user starts typing
        if ($(this).val().trim()) {
            $(this).closest('.schedspot-form-row').removeClass('error');
            $(this).siblings('.error-message').remove();
        }
    }

    /**
     * Select a worker
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
        const workerName = $('.worker-card[data-worker-id="' + workerId + '"] h4').text();
        const message = schedspot_frontend.strings.selected_worker + ': ' + workerName;
        showNotification(message, 'success');
    }

    /**
     * Load service details
     */
    function loadServiceDetails(serviceId) {
        // This could load service-specific information
        // For now, we'll just update the worker list if needed
        if ($('input[name="worker_selection_mode"][value="manual"]').is(':checked')) {
            loadAvailableWorkers(serviceId);
        }
    }

    /**
     * Load available workers for a service
     */
    function loadAvailableWorkers(serviceId) {
        const $workersList = $('#available-workers-list');
        $workersList.html('<div class="workers-loading"><span class="dashicons dashicons-update spin"></span><br>Loading workers...</div>');

        $.ajax({
            url: schedspot_frontend.rest_url + 'workers/available',
            method: 'GET',
            data: {
                service_id: serviceId,
                date: $('#schedspot_booking_date').val(),
                time: $('#schedspot_start_time').val()
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (response && response.length > 0) {
                    availableWorkers = response;
                    renderWorkersGrid(response);
                } else {
                    $workersList.html('<div class="no-workers-available">No workers available for this service and time.</div>');
                }
            },
            error: function() {
                $workersList.html('<div class="no-workers-available">Error loading workers. Please try again.</div>');
            }
        });
    }

    /**
     * Render workers grid
     */
    function renderWorkersGrid(workers) {
        let html = '<div class="schedspot-workers-grid">';
        
        workers.forEach(function(worker) {
            html += `
                <div class="worker-card available" data-worker-id="${worker.id}">
                    <div class="worker-availability-indicator ${worker.availability_status}"></div>
                    <img src="${worker.avatar || schedspot_frontend.default_avatar}" alt="${worker.name}" class="worker-avatar">
                    <h4 class="worker-name">${worker.name}</h4>
                    <div class="worker-rating">
                        <span class="stars">${generateStars(worker.rating)}</span>
                        <span class="rating-text">(${worker.rating}/5)</span>
                    </div>
                    <div class="worker-hourly-rate">$${worker.hourly_rate}/hr</div>
                    <div class="worker-skills">
                        <div class="worker-skills-label">Skills:</div>
                        <div class="worker-skills-list">
                            ${worker.skills.map(skill => `<span class="skill-tag">${skill}</span>`).join('')}
                        </div>
                    </div>
                    <div class="worker-stats">
                        <div class="stat">
                            <span class="stat-number">${worker.completed_jobs}</span>
                            <span class="stat-label">Jobs</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">${worker.response_time}</span>
                            <span class="stat-label">Response</span>
                        </div>
                    </div>
                    <div class="worker-actions">
                        <button class="select-worker-btn" data-worker-id="${worker.id}">
                            <span class="dashicons dashicons-yes"></span>
                            Select Worker
                        </button>
                        <button class="message-worker-btn" onclick="messageWorker(${worker.id}, 0)">
                            <span class="dashicons dashicons-email-alt"></span>
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        $('#available-workers-list').html(html);
    }

    /**
     * Generate star rating HTML
     */
    function generateStars(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= rating) {
                stars += '★';
            } else {
                stars += '☆';
            }
        }
        return stars;
    }

    /**
     * Initialize geolocation functionality
     */
    function initGeolocation() {
        // Get current location button
        $('.schedspot-get-location').on('click', getCurrentLocation);

        // Address input geocoding
        $('#schedspot-client-address').on('blur', geocodeAddress);

        // Initialize map if Google Maps is available
        if (typeof google !== 'undefined' && google.maps) {
            initBookingMap();
        }
    }

    /**
     * Get current location
     */
    function getCurrentLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    $('#schedspot-client-lat').val(lat);
                    $('#schedspot-client-lng').val(lng);
                    
                    // Reverse geocode to get address
                    reverseGeocode(lat, lng);
                    
                    // Update map
                    if (bookingMap) {
                        updateMapLocation(lat, lng);
                    }
                },
                function(error) {
                    showNotification('Unable to get your location. Please enter your address manually.', 'error');
                }
            );
        } else {
            showNotification('Geolocation is not supported by this browser.', 'error');
        }
    }

    /**
     * Validate email format
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
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

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#schedspot-booking-form').length) {
            initBookingForm();
        }
    });

})(jQuery);
