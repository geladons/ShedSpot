<?php
/**
 * Shortcodes Class
 *
 * @package SchedSpot
 * @version 0.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Shortcodes Class.
 *
 * @class SchedSpot_Shortcodes
 * @version 0.1.0
 */
class SchedSpot_Shortcodes {

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize shortcodes.
     *
     * @since 0.1.0
     */
    public function init() {
        $shortcodes = array(
            'schedspot_booking_form' => 'booking_form',
            'schedspot_service_list' => 'service_list',
            'schedspot_dashboard'    => 'dashboard',
            'schedspot_messages'     => 'messages',
        );

        foreach ( $shortcodes as $shortcode => $function ) {
            add_shortcode( $shortcode, array( $this, $function ) );
        }

        // Hook to mark pages with shortcodes for navigation
        add_action( 'save_post', array( $this, 'mark_pages_with_shortcodes' ) );
    }

    /**
     * Mark pages that contain SchedSpot shortcodes for easier navigation.
     *
     * @since 1.0.0
     * @param int $post_id Post ID.
     */
    public function mark_pages_with_shortcodes( $post_id ) {
        if ( get_post_type( $post_id ) !== 'page' ) {
            return;
        }

        $content = get_post_field( 'post_content', $post_id );

        // Check for booking form shortcode
        if ( has_shortcode( $content, 'schedspot_booking_form' ) ) {
            update_post_meta( $post_id, '_schedspot_has_booking_form', '1' );
        } else {
            delete_post_meta( $post_id, '_schedspot_has_booking_form' );
        }

        // Check for dashboard shortcode
        if ( has_shortcode( $content, 'schedspot_dashboard' ) ) {
            update_post_meta( $post_id, '_schedspot_has_dashboard', '1' );
        } else {
            delete_post_meta( $post_id, '_schedspot_has_dashboard' );
        }
    }

    /**
     * Booking form shortcode.
     *
     * @since 0.1.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function booking_form( $atts ) {
        $atts = shortcode_atts( array(
            'service_id' => 0,
            'worker_id'  => 0,
            'class'      => 'schedspot-booking-form',
        ), $atts, 'schedspot_booking_form' );

        ob_start();

        // Enqueue necessary scripts and styles
        $this->enqueue_booking_assets();

        // Handle form submission
        if ( isset( $_POST['schedspot_submit_booking'] ) && wp_verify_nonce( $_POST['schedspot_booking_nonce'], 'schedspot_booking_form' ) ) {
            $this->handle_booking_submission();
        }

        ?>
        <div class="<?php echo esc_attr( $atts['class'] ); ?>">
            <!-- Navigation Bar -->
            <?php if ( is_user_logged_in() ) : ?>
            <div class="schedspot-navigation">
                <div class="schedspot-nav-links">
                    <a href="<?php echo esc_url( $this->get_booking_form_url() ); ?>" class="schedspot-nav-link active">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php _e( 'Book a Service', 'schedspot' ); ?>
                    </a>
                    <a href="<?php echo esc_url( $this->get_dashboard_url() ); ?>" class="schedspot-nav-link">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e( 'My Bookings', 'schedspot' ); ?>
                    </a>
                    <?php if ( get_option( 'schedspot_enable_messaging', true ) ) : ?>
                    <a href="<?php echo esc_url( $this->get_messages_url() ); ?>" class="schedspot-nav-link">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php _e( 'Messages', 'schedspot' ); ?>
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( $this->get_profile_url() ); ?>" class="schedspot-nav-link">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php _e( 'Profile/Settings', 'schedspot' ); ?>
                    </a>
                    <?php if ( current_user_can( 'manage_options' ) ) : ?>
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-role-switcher' ); ?>" class="schedspot-nav-link admin-switcher">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php printf( __( 'Admin: %s', 'schedspot' ), $this->get_role_display_name( SchedSpot()->get_effective_user_role() ) ); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="schedspot-form-container">
                <form method="post" id="schedspot-booking-form">
                    <?php wp_nonce_field( 'schedspot_booking_form', 'schedspot_booking_nonce' ); ?>
                
                <div class="schedspot-form-section">
                    <h3><?php _e( 'Service Details', 'schedspot' ); ?></h3>

                    <div class="schedspot-form-grid">
                        <div class="schedspot-form-row">
                            <label for="schedspot_service_id"><?php _e( 'Service', 'schedspot' ); ?> <span class="required">*</span></label>
                            <select name="service_id" id="schedspot_service_id" required>
                                <option value=""><?php _e( 'Select a service', 'schedspot' ); ?></option>
                                <?php echo $this->get_services_options( $atts['service_id'] ); ?>
                            </select>
                        </div>

                        <div class="schedspot-form-row">
                            <label for="schedspot_description"><?php _e( 'Service Description', 'schedspot' ); ?></label>
                            <textarea name="description" id="schedspot_description" placeholder="<?php esc_attr_e( 'Please describe what you need in detail. Include any specific requirements, materials needed, or special instructions...', 'schedspot' ); ?>"></textarea>
                        </div>
                    </div>

                    <div class="schedspot-form-row">
                        <label for="schedspot_worker_id"><?php _e( 'Select Worker', 'schedspot' ); ?></label>
                        <div id="schedspot-worker-selection">
                            <div class="worker-selection-mode">
                                <label>
                                    <input type="radio" name="worker_selection_mode" value="auto" checked>
                                    <?php _e( 'Auto-assign available worker', 'schedspot' ); ?>
                                </label>
                                <label>
                                    <input type="radio" name="worker_selection_mode" value="manual">
                                    <?php _e( 'Choose specific worker', 'schedspot' ); ?>
                                </label>
                            </div>

                            <div id="manual-worker-selection" style="display: none;">
                                <div id="available-workers-list">
                                    <?php echo $this->get_workers_grid(); ?>
                                </div>
                            </div>

                            <input type="hidden" name="worker_id" id="schedspot_worker_id" value="<?php echo esc_attr( $atts['worker_id'] ); ?>">
                        </div>
                    </div>
                </div>

                <div class="schedspot-form-section">
                    <h3><?php _e( 'Date & Time', 'schedspot' ); ?></h3>

                    <div class="schedspot-form-grid">
                        <div class="schedspot-form-row">
                            <label for="schedspot_booking_date"><?php _e( 'Date', 'schedspot' ); ?> <span class="required">*</span></label>
                            <input type="date" name="booking_date" id="schedspot_booking_date" required min="<?php echo date( 'Y-m-d' ); ?>">
                        </div>

                        <div class="schedspot-form-row">
                            <label for="schedspot_start_time"><?php _e( 'Start Time', 'schedspot' ); ?> <span class="required">*</span></label>
                            <input type="time" name="start_time" id="schedspot_start_time" required>
                        </div>

                        <div class="schedspot-form-row">
                            <label for="schedspot_duration"><?php _e( 'Duration', 'schedspot' ); ?> <span class="required">*</span></label>
                            <select name="duration" id="schedspot_duration" required>
                                <option value="30">30 minutes</option>
                                <option value="60" selected>1 hour</option>
                                <option value="90">1.5 hours</option>
                                <option value="120">2 hours</option>
                                <option value="180">3 hours</option>
                                <option value="240">4 hours</option>
                                <option value="480">8 hours (full day)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="schedspot-form-section">
                    <h3><?php _e( 'Contact Information', 'schedspot' ); ?></h3>

                    <div class="schedspot-form-grid">
                        <div class="schedspot-form-row">
                            <label for="schedspot_client_name"><?php _e( 'Full Name', 'schedspot' ); ?> <span class="required">*</span></label>
                            <input type="text" name="client_name" id="schedspot_client_name" required value="<?php echo esc_attr( $this->get_current_user_name() ); ?>" placeholder="<?php esc_attr_e( 'Enter your full name', 'schedspot' ); ?>">
                        </div>

                        <div class="schedspot-form-row">
                            <label for="schedspot_client_email"><?php _e( 'Email Address', 'schedspot' ); ?> <span class="required">*</span></label>
                            <input type="email" name="client_email" id="schedspot_client_email" required value="<?php echo esc_attr( $this->get_current_user_email() ); ?>" placeholder="<?php esc_attr_e( 'your.email@example.com', 'schedspot' ); ?>">
                        </div>

                        <div class="schedspot-form-row">
                            <label for="schedspot_client_phone"><?php _e( 'Phone Number', 'schedspot' ); ?></label>
                            <input type="tel" name="client_phone" id="schedspot_client_phone" placeholder="<?php esc_attr_e( '(555) 123-4567', 'schedspot' ); ?>">
                        </div>
                    </div>

                    <div class="schedspot-form-row">
                        <label for="schedspot_client_address"><?php _e( 'Service Address', 'schedspot' ); ?></label>
                        <textarea name="client_address" id="schedspot-client-address" rows="3" placeholder="<?php _e( 'Enter the complete address where the service should be performed, including apartment/unit number if applicable', 'schedspot' ); ?>"></textarea>
                        <?php if ( get_option( 'schedspot_enable_geofencing', false ) ) : ?>
                        <button type="button" class="schedspot-btn schedspot-btn-secondary schedspot-get-location" style="margin-top: 10px;">
                            <span class="dashicons dashicons-location"></span>
                            <?php _e( 'Use My Current Location', 'schedspot' ); ?>
                        </button>
                        <?php endif; ?>
                    </div>

                    <?php if ( get_option( 'schedspot_enable_geofencing', false ) ) : ?>
                    <!-- Hidden location fields -->
                    <input type="hidden" name="client_lat" id="schedspot-client-lat" value="">
                    <input type="hidden" name="client_lng" id="schedspot-client-lng" value="">

                    <!-- Location Map -->
                    <div class="schedspot-form-row">
                        <label><?php _e( 'Service Location', 'schedspot' ); ?></label>
                        <div id="schedspot-booking-map" style="height: 300px; width: 100%; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px;"></div>
                        <p class="description"><?php _e( 'Click on the map or enter an address above to set the service location.', 'schedspot' ); ?></p>
                    </div>

                    <!-- Nearby Workers Display -->
                    <div id="schedspot-nearby-workers" style="margin-top: 15px;">
                        <!-- Nearby workers will be populated here by JavaScript -->
                    </div>
                    <?php endif; ?>
                </div>

                <div class="schedspot-form-section">
                    <h3><?php _e( 'Additional Information', 'schedspot' ); ?></h3>

                    <div class="schedspot-form-row">
                        <label for="schedspot_notes"><?php _e( 'Special Instructions & Notes', 'schedspot' ); ?></label>
                        <textarea name="notes" id="schedspot_notes" rows="4" placeholder="<?php _e( 'Any additional details, special requirements, preferred timing, access instructions, or other important information for the worker...', 'schedspot' ); ?>"></textarea>
                    </div>
                </div>

                <div class="schedspot-form-actions">
                    <button type="submit" name="schedspot_submit_booking" class="schedspot-btn schedspot-btn-primary">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e( 'Submit Booking Request', 'schedspot' ); ?>
                    </button>
                </div>
                </form>

                <!-- Payment Information -->
                <div class="schedspot-payment-info">
                    <h3><?php _e( 'Payment Information', 'schedspot' ); ?></h3>
                    <div class="payment-details">
                        <?php
                        $payment_required = get_option( 'schedspot_payment_required', 'deposit' );
                        $deposit_rate = get_option( 'schedspot_deposit_rate', 30 );
                        ?>

                        <?php if ( $payment_required === 'full' ) : ?>
                            <p><?php _e( 'Full payment is required to confirm your booking.', 'schedspot' ); ?></p>
                        <?php elseif ( $payment_required === 'deposit' ) : ?>
                            <p><?php printf( __( 'A %d%% deposit is required to confirm your booking. The remaining balance will be collected after service completion.', 'schedspot' ), $deposit_rate ); ?></p>
                        <?php else : ?>
                            <p><?php _e( 'Payment will be collected after service completion.', 'schedspot' ); ?></p>
                        <?php endif; ?>

                        <div class="payment-methods">
                            <p><strong><?php _e( 'Accepted Payment Methods:', 'schedspot' ); ?></strong></p>
                            <div class="payment-icons">
                                <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                                    <?php
                                    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
                                    foreach ( $available_gateways as $gateway ) :
                                        if ( $gateway->enabled === 'yes' ) :
                                    ?>
                                        <span class="payment-method"><?php echo esc_html( $gateway->get_title() ); ?></span>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                <?php else : ?>
                                    <span class="payment-method"><?php _e( 'Credit Card', 'schedspot' ); ?></span>
                                    <span class="payment-method"><?php _e( 'PayPal', 'schedspot' ); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="security-notice">
                            <small><?php _e( '🔒 Your payment information is secure and encrypted.', 'schedspot' ); ?></small>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <script>
        jQuery(document).ready(function($) {
            // Initialize datepicker with modern styling
            $('#schedspot_booking_date').datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0,
                showAnim: 'slideDown',
                changeMonth: true,
                changeYear: true
            });

            // Form validation and enhancement
            $('#schedspot-booking-form').on('submit', function(e) {
                var isValid = true;
                var firstError = null;

                // Clear previous errors
                $('.schedspot-form-row').removeClass('error');
                $('.error-message').remove();

                // Validate required fields
                $(this).find('[required]').each(function() {
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
                var $submitBtn = $(this).find('[type="submit"]');
                $submitBtn.prop('disabled', true);
                $submitBtn.html('<span class="dashicons dashicons-update spin"></span> Processing...');
            });

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
                    // You could load service-specific information here
                    loadServiceDetails(serviceId);
                }
            });

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

            function showWorkerSelected(workerId) {
                var workerName = $('.worker-card[data-worker-id="' + workerId + '"] h4').text();
                var message = '<?php _e( 'Selected worker:', 'schedspot' ); ?> ' + workerName;

                // Remove existing notifications
                $('.worker-selection-notice').remove();

                // Add new notification
                $('#schedspot-worker-selection').prepend(
                    '<div class="worker-selection-notice schedspot-notice schedspot-notice-success">' +
                    message +
                    '</div>'
                );

                // Auto-hide after 3 seconds
                setTimeout(function() {
                    $('.worker-selection-notice').fadeOut();
                }, 3000);
            }

            function loadServiceDetails(serviceId) {
                // Placeholder for loading service-specific details
                // This could fetch pricing, duration estimates, etc.
            }

            function isValidEmail(email) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            function showNotification(message, type) {
                var notificationClass = 'schedspot-notice-' + (type || 'info');
                var notification = $('<div class="schedspot-notice ' + notificationClass + '">' + message + '</div>');

                $('.schedspot-form-container').prepend(notification);

                setTimeout(function() {
                    notification.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        });
        </script>

        <style>
        /* Additional form enhancements */
        .schedspot-form-row.focused {
            transform: translateY(-2px);
        }

        .schedspot-form-row.error input,
        .schedspot-form-row.error select,
        .schedspot-form-row.error textarea {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            font-weight: 600;
        }

        .dashicons.spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Enhanced button states */
        .schedspot-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Form section animations */
        .schedspot-form-section {
            opacity: 0;
            animation: slideInUp 0.6s ease-out forwards;
        }

        .schedspot-form-section:nth-child(1) { animation-delay: 0.1s; }
        .schedspot-form-section:nth-child(2) { animation-delay: 0.2s; }
        .schedspot-form-section:nth-child(3) { animation-delay: 0.3s; }
        .schedspot-form-section:nth-child(4) { animation-delay: 0.4s; }
        .schedspot-form-section:nth-child(5) { animation-delay: 0.5s; }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        </style>
        <?php

        return ob_get_clean();
    }

    /**
     * Service list shortcode.
     *
     * @since 0.1.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function service_list( $atts ) {
        $atts = shortcode_atts( array(
            'limit'    => 10,
            'category' => '',
            'class'    => 'schedspot-service-list',
        ), $atts, 'schedspot_service_list' );

        ob_start();

        $services = $this->get_services( array(
            'limit'    => absint( $atts['limit'] ),
            'category' => sanitize_text_field( $atts['category'] ),
        ) );

        ?>
        <div class="<?php echo esc_attr( $atts['class'] ); ?>">
            <?php if ( ! empty( $services ) ) : ?>
                <div class="schedspot-services-grid">
                    <?php foreach ( $services as $service ) : ?>
                        <div class="schedspot-service-item">
                            <h3 class="service-name"><?php echo esc_html( $service->name ); ?></h3>
                            <?php if ( ! empty( $service->description ) ) : ?>
                                <p class="service-description"><?php echo esc_html( $service->description ); ?></p>
                            <?php endif; ?>
                            <div class="service-meta">
                                <span class="service-duration"><?php printf( __( 'Duration: %d minutes', 'schedspot' ), $service->duration ); ?></span>
                                <span class="service-price"><?php printf( __( 'From $%.2f', 'schedspot' ), $service->base_price ); ?></span>
                            </div>
                            <div class="service-actions">
                                <a href="<?php echo esc_url( add_query_arg( 'service_id', $service->id, get_permalink( get_option( 'schedspot_booking_page' ) ) ) ); ?>" class="schedspot-btn schedspot-btn-primary">
                                    <?php _e( 'Book Now', 'schedspot' ); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="schedspot-no-services"><?php _e( 'No services available at the moment.', 'schedspot' ); ?></p>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Dashboard shortcode.
     *
     * @since 0.1.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function dashboard( $atts ) {
        $atts = shortcode_atts( array(
            'class' => 'schedspot-dashboard',
        ), $atts, 'schedspot_dashboard' );

        if ( ! is_user_logged_in() ) {
            return '<p>' . __( 'Please log in to access your dashboard.', 'schedspot' ) . '</p>';
        }

        ob_start();

        $current_user = wp_get_current_user();
        $user_role = SchedSpot()->get_effective_user_role( $current_user->ID );

        // Get display user for impersonation
        $display_user = $current_user;
        if ( current_user_can( 'manage_options' ) ) {
            $impersonate_user_id = get_user_meta( get_current_user_id(), 'schedspot_admin_impersonate_user', true );
            if ( $impersonate_user_id && $impersonate_user_id != get_current_user_id() ) {
                $display_user = get_userdata( $impersonate_user_id );
            }
        }

        ?>
        <div class="<?php echo esc_attr( $atts['class'] ); ?>">
            <!-- Navigation Bar -->
            <div class="schedspot-navigation">
                <div class="schedspot-nav-links">
                    <a href="<?php echo esc_url( $this->get_booking_form_url() ); ?>" class="schedspot-nav-link">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php _e( 'Book a Service', 'schedspot' ); ?>
                    </a>
                    <a href="<?php echo esc_url( $this->get_dashboard_url() ); ?>" class="schedspot-nav-link active">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e( 'My Bookings', 'schedspot' ); ?>
                    </a>
                    <?php if ( get_option( 'schedspot_enable_messaging', true ) ) : ?>
                    <a href="<?php echo esc_url( $this->get_messages_url() ); ?>" class="schedspot-nav-link">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php _e( 'Messages', 'schedspot' ); ?>
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( $this->get_profile_url() ); ?>" class="schedspot-nav-link">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php _e( 'Profile/Settings', 'schedspot' ); ?>
                    </a>
                    <?php if ( $user_role === 'schedspot_worker' ) : ?>
                    <a href="#" class="schedspot-nav-link" onclick="openWorkerSettings()">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e( 'Worker Settings', 'schedspot' ); ?>
                    </a>
                    <?php endif; ?>
                    <?php if ( current_user_can( 'manage_options' ) ) : ?>
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-role-switcher' ); ?>" class="schedspot-nav-link admin-switcher">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php printf( __( 'Admin: %s', 'schedspot' ), $this->get_role_display_name( $user_role ) ); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="schedspot-dashboard-header">
                <h2><?php printf( __( 'Welcome, %s', 'schedspot' ), esc_html( $display_user->display_name ) ); ?></h2>
                <p class="user-role">
                    <?php echo esc_html( $this->get_role_display_name( $user_role ) ); ?>
                    <?php if ( current_user_can( 'manage_options' ) && $display_user->ID !== get_current_user_id() ) : ?>
                        <span class="admin-mode-indicator"><?php _e( '(Admin Mode)', 'schedspot' ); ?></span>
                    <?php endif; ?>
                </p>
            </div>

            <div class="schedspot-dashboard-content">
                <?php if ( 'schedspot_customer' === $user_role ) : ?>
                    <?php $this->render_customer_dashboard( $display_user->ID ); ?>
                <?php elseif ( 'schedspot_worker' === $user_role ) : ?>
                    <?php $this->render_worker_dashboard( $display_user->ID ); ?>
                <?php elseif ( 'administrator' === $user_role ) : ?>
                    <?php $this->render_admin_dashboard( $display_user->ID ); ?>
                <?php else : ?>
                    <p><?php _e( 'Your account is not set up for SchedSpot services. Please contact the administrator.', 'schedspot' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Handle booking form submission.
     *
     * @since 0.1.0
     */
    private function handle_booking_submission() {
        // Get current user or handle guest booking
        $user_id = get_current_user_id();
        $is_guest = false;

        if ( ! $user_id ) {
            // Handle guest booking
            $guest_email = sanitize_email( $_POST['schedspot_client_email'] ?? '' );
            $guest_name = sanitize_text_field( $_POST['schedspot_client_name'] ?? '' );

            if ( empty( $guest_email ) || empty( $guest_name ) ) {
                wp_die( __( 'Guest bookings require a valid name and email address.', 'schedspot' ) );
            }

            // Check if user exists with this email
            $existing_user = get_user_by( 'email', $guest_email );
            if ( $existing_user ) {
                $user_id = $existing_user->ID;
            } else {
                // Create a temporary guest user ID (negative number to distinguish from real users)
                $user_id = 0; // We'll handle guest bookings with user_id = 0
                $is_guest = true;
            }
        }

        // Sanitize and validate form data
        $booking_data = array(
            'user_id'        => $user_id,
            'service_id'     => absint( $_POST['service_id'] ),
            'worker_id'      => absint( $_POST['worker_id'] ),
            'booking_date'   => sanitize_text_field( $_POST['booking_date'] ),
            'start_time'     => sanitize_text_field( $_POST['start_time'] ),
            'duration'       => absint( $_POST['duration'] ),
            'client_name'    => sanitize_text_field( $_POST['client_name'] ),
            'client_email'   => sanitize_email( $_POST['client_email'] ),
            'client_phone'   => sanitize_text_field( $_POST['client_phone'] ),
            'client_address' => sanitize_textarea_field( $_POST['client_address'] ),
            'client_lat'     => isset( $_POST['client_lat'] ) ? floatval( $_POST['client_lat'] ) : null,
            'client_lng'     => isset( $_POST['client_lng'] ) ? floatval( $_POST['client_lng'] ) : null,
            'notes'          => sanitize_textarea_field( $_POST['notes'] ),
            'is_guest'       => $is_guest,
        );

        // Validate location if geofencing is enabled
        if ( get_option( 'schedspot_enable_geofencing', false ) ) {
            $geolocation = new SchedSpot_Geolocation();
            $location_errors = array();
            $location_errors = $geolocation->validate_booking_location( $booking_data, $location_errors );

            if ( ! empty( $location_errors ) ) {
                echo '<div class="schedspot-notice schedspot-notice-error">';
                foreach ( $location_errors as $error ) {
                    echo '<p>' . esc_html( $error ) . '</p>';
                }
                echo '</div>';
                return;
            }
        }

        // Calculate end time
        $start_datetime = new DateTime( $booking_data['booking_date'] . ' ' . $booking_data['start_time'] );
        $end_datetime = clone $start_datetime;
        $end_datetime->add( new DateInterval( 'PT' . $booking_data['duration'] . 'M' ) );
        $booking_data['end_time'] = $end_datetime->format( 'H:i:s' );

        // Auto-assign worker if not specified
        if ( empty( $booking_data['worker_id'] ) ) {
            $booking_data['worker_id'] = $this->find_available_worker( $booking_data );
        }

        if ( empty( $booking_data['worker_id'] ) ) {
            echo '<div class="schedspot-notice schedspot-notice-error">' . __( 'No workers are available for the selected time slot.', 'schedspot' ) . '</div>';
            return;
        }

        // Create booking
        $booking_id = SchedSpot_Booking::create_booking( $booking_data );

        if ( is_wp_error( $booking_id ) ) {
            echo '<div class="schedspot-notice schedspot-notice-error">' . esc_html( $booking_id->get_error_message() ) . '</div>';
        } else {
            // Check if payment is required
            $payment_required = get_option( 'schedspot_payment_required', 'deposit' );

            if ( $payment_required !== 'none' && class_exists( 'WooCommerce' ) ) {
                // Get payment URL
                $wc_integration = new SchedSpot_WooCommerce();
                $payment_url = $wc_integration->get_booking_payment_url( $booking_id );

                if ( $payment_url ) {
                    echo '<div class="schedspot-notice schedspot-notice-success">';
                    echo '<p>' . __( 'Booking request submitted successfully!', 'schedspot' ) . '</p>';
                    echo '<p>' . __( 'Please complete your payment to confirm the booking.', 'schedspot' ) . '</p>';
                    echo '<p><a href="' . esc_url( $payment_url ) . '" class="schedspot-btn schedspot-btn-primary">' . __( 'Pay Now', 'schedspot' ) . '</a></p>';
                    echo '</div>';
                } else {
                    echo '<div class="schedspot-notice schedspot-notice-success">' . __( 'Booking request submitted successfully! You will receive a confirmation email shortly.', 'schedspot' ) . '</div>';
                }
            } else {
                echo '<div class="schedspot-notice schedspot-notice-success">' . __( 'Booking request submitted successfully! You will receive a confirmation email shortly.', 'schedspot' ) . '</div>';
            }
        }
    }

    /**
     * Get services options for select dropdown.
     *
     * @since 0.1.0
     * @param int $selected_id Selected service ID.
     * @return string HTML options.
     */
    private function get_services_options( $selected_id = 0 ) {
        $services = $this->get_services();
        $options = '';

        foreach ( $services as $service ) {
            $selected = selected( $selected_id, $service->id, false );
            $options .= sprintf(
                '<option value="%d" %s>%s - $%.2f</option>',
                $service->id,
                $selected,
                esc_html( $service->name ),
                $service->base_price
            );
        }

        return $options;
    }

    /**
     * Get workers options for select dropdown.
     *
     * @since 0.1.0
     * @param int $selected_id Selected worker ID.
     * @return string HTML options.
     */
    private function get_workers_options( $selected_id = 0 ) {
        $workers = get_users( array( 'role' => 'schedspot_worker' ) );
        $options = '';

        foreach ( $workers as $worker ) {
            $selected = selected( $selected_id, $worker->ID, false );
            $options .= sprintf(
                '<option value="%d" %s>%s</option>',
                $worker->ID,
                $selected,
                esc_html( $worker->display_name )
            );
        }

        return $options;
    }

    /**
     * Get workers grid for enhanced selection.
     *
     * @since 1.0.0
     * @return string HTML workers grid.
     */
    private function get_workers_grid() {
        $workers = SchedSpot_Worker::get_workers( array( 'number' => 20 ) );

        if ( empty( $workers ) ) {
            return '<p>' . __( 'No workers available at this time.', 'schedspot' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="schedspot-workers-grid">
            <?php foreach ( $workers as $worker ) : ?>
                <?php
                $stats = $worker->get_statistics();
                $is_available = $worker->profile['is_available'] ?? false;
                ?>
                <div class="worker-card <?php echo $is_available ? 'available' : 'unavailable'; ?>" data-worker-id="<?php echo esc_attr( $worker->id ); ?>">
                    <div class="worker-avatar">
                        <img src="<?php echo esc_url( get_avatar_url( $worker->id, array( 'size' => 64 ) ) ); ?>" alt="<?php echo esc_attr( $worker->user->display_name ); ?>">
                        <div class="availability-indicator">
                            <?php if ( $is_available ) : ?>
                                <span class="status-dot available" title="<?php _e( 'Available', 'schedspot' ); ?>"></span>
                            <?php else : ?>
                                <span class="status-dot unavailable" title="<?php _e( 'Unavailable', 'schedspot' ); ?>"></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="worker-info">
                        <h4><?php echo esc_html( $worker->user->display_name ); ?></h4>

                        <?php if ( ! empty( $worker->profile['bio'] ) ) : ?>
                            <p class="worker-bio"><?php echo esc_html( wp_trim_words( $worker->profile['bio'], 15 ) ); ?></p>
                        <?php endif; ?>

                        <div class="worker-stats">
                            <?php if ( ! empty( $worker->profile['hourly_rate'] ) ) : ?>
                                <div class="stat">
                                    <span class="label"><?php _e( 'Rate:', 'schedspot' ); ?></span>
                                    <span class="value">$<?php echo esc_html( number_format( $worker->profile['hourly_rate'], 2 ) ); ?>/hr</span>
                                </div>
                            <?php endif; ?>

                            <div class="stat">
                                <span class="label"><?php _e( 'Rating:', 'schedspot' ); ?></span>
                                <span class="value">
                                    <?php echo esc_html( number_format( $stats['rating'], 1 ) ); ?>/5
                                    <span class="rating-stars">
                                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                            <span class="star <?php echo $i <= $stats['rating'] ? 'filled' : 'empty'; ?>">★</span>
                                        <?php endfor; ?>
                                    </span>
                                </span>
                            </div>

                            <div class="stat">
                                <span class="label"><?php _e( 'Jobs:', 'schedspot' ); ?></span>
                                <span class="value"><?php echo esc_html( $stats['total_bookings'] ); ?></span>
                            </div>
                        </div>

                        <?php if ( ! empty( $worker->profile['skills'] ) ) : ?>
                            <div class="worker-skills">
                                <?php
                                $skills = is_array( $worker->profile['skills'] ) ? $worker->profile['skills'] : explode( ',', $worker->profile['skills'] );
                                $skills = array_slice( array_map( 'trim', $skills ), 0, 3 );
                                foreach ( $skills as $skill ) :
                                ?>
                                    <span class="skill-tag"><?php echo esc_html( $skill ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="worker-actions">
                        <?php if ( $is_available ) : ?>
                            <button type="button" class="schedspot-btn schedspot-btn-primary select-worker-btn" data-worker-id="<?php echo esc_attr( $worker->id ); ?>">
                                <?php _e( 'Select Worker', 'schedspot' ); ?>
                            </button>
                        <?php else : ?>
                            <button type="button" class="schedspot-btn schedspot-btn-disabled" disabled>
                                <?php _e( 'Unavailable', 'schedspot' ); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <style>
        .schedspot-workers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .worker-card {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #fff;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .worker-card:hover {
            border-color: #0073aa;
            box-shadow: 0 2px 8px rgba(0,115,170,0.1);
        }

        .worker-card.selected {
            border-color: #0073aa;
            background: #f0f8ff;
        }

        .worker-card.unavailable {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .worker-avatar {
            position: relative;
            text-align: center;
            margin-bottom: 15px;
        }

        .worker-avatar img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .availability-indicator {
            position: absolute;
            bottom: 5px;
            right: calc(50% - 40px);
        }

        .status-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .status-dot.available {
            background: #46b450;
        }

        .status-dot.unavailable {
            background: #dc3232;
        }

        .worker-info h4 {
            margin: 0 0 10px 0;
            text-align: center;
            color: #333;
        }

        .worker-bio {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            text-align: center;
        }

        .worker-stats {
            margin-bottom: 15px;
        }

        .worker-stats .stat {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .worker-stats .label {
            color: #666;
        }

        .worker-stats .value {
            font-weight: 600;
            color: #333;
        }

        .rating-stars {
            margin-left: 5px;
        }

        .rating-stars .star {
            color: #ddd;
            font-size: 12px;
        }

        .rating-stars .star.filled {
            color: #ffb900;
        }

        .worker-skills {
            margin-bottom: 15px;
            text-align: center;
        }

        .skill-tag {
            display: inline-block;
            background: #f0f0f0;
            color: #333;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin: 2px;
        }

        .worker-actions {
            text-align: center;
        }

        .schedspot-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .schedspot-btn-primary {
            background: #0073aa;
            color: #fff;
        }

        .schedspot-btn-primary:hover {
            background: #005a87;
        }

        .schedspot-btn-disabled {
            background: #ccc;
            color: #666;
            cursor: not-allowed;
        }

        .worker-selection-mode {
            margin-bottom: 15px;
        }

        .worker-selection-mode label {
            display: block;
            margin-bottom: 8px;
            cursor: pointer;
        }

        .worker-selection-mode input[type="radio"] {
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .schedspot-workers-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Get services from database.
     *
     * @since 0.1.0
     * @param array $args Query arguments.
     * @return array Array of service objects.
     */
    private function get_services( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'limit'    => 10,
            'category' => '',
        );

        $args = wp_parse_args( $args, $defaults );

        $where_clauses = array( 'is_active = 1' );
        $params = array();

        if ( ! empty( $args['category'] ) ) {
            $where_clauses[] = 'category = %s';
            $params[] = $args['category'];
        }

        $where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
        $limit_sql = sprintf( 'LIMIT %d', absint( $args['limit'] ) );

        $sql = "SELECT * FROM {$wpdb->prefix}schedspot_services {$where_sql} ORDER BY name ASC {$limit_sql}";

        if ( ! empty( $params ) ) {
            $sql = $wpdb->prepare( $sql, $params );
        }

        return $wpdb->get_results( $sql );
    }

    /**
     * Get current user's name.
     *
     * @since 0.1.0
     * @return string User's display name.
     */
    private function get_current_user_name() {
        $current_user = wp_get_current_user();
        return $current_user->exists() ? $current_user->display_name : '';
    }

    /**
     * Get current user's email.
     *
     * @since 0.1.0
     * @return string User's email address.
     */
    private function get_current_user_email() {
        $current_user = wp_get_current_user();
        return $current_user->exists() ? $current_user->user_email : '';
    }

    /**
     * Get user's SchedSpot role.
     *
     * @since 0.1.0
     * @param int $user_id User ID.
     * @return string User's SchedSpot role.
     */
    private function get_user_schedspot_role( $user_id ) {
        return SchedSpot()->get_effective_user_role( $user_id );
    }

    /**
     * Get booking form URL.
     *
     * @since 1.0.0
     * @return string Booking form URL.
     */
    private function get_booking_form_url() {
        // Try to find a page with the booking form shortcode
        $pages = get_posts( array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_schedspot_has_booking_form',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'numberposts' => 1
        ) );

        if ( ! empty( $pages ) ) {
            return get_permalink( $pages[0]->ID );
        }

        // Fallback: search for shortcode in content
        $pages = get_posts( array(
            'post_type' => 'page',
            'post_status' => 'publish',
            's' => '[schedspot_booking_form]',
            'numberposts' => 1
        ) );

        if ( ! empty( $pages ) ) {
            return get_permalink( $pages[0]->ID );
        }

        return home_url( '?schedspot_action=booking_form' );
    }

    /**
     * Get dashboard URL.
     *
     * @since 1.0.0
     * @return string Dashboard URL.
     */
    private function get_dashboard_url() {
        // Try to find a page with the dashboard shortcode
        $pages = get_posts( array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_schedspot_has_dashboard',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'numberposts' => 1
        ) );

        if ( ! empty( $pages ) ) {
            return get_permalink( $pages[0]->ID );
        }

        // Fallback: search for shortcode in content
        $pages = get_posts( array(
            'post_type' => 'page',
            'post_status' => 'publish',
            's' => '[schedspot_dashboard]',
            'numberposts' => 1
        ) );

        if ( ! empty( $pages ) ) {
            return get_permalink( $pages[0]->ID );
        }

        return home_url( '?schedspot_action=dashboard' );
    }

    /**
     * Get messages URL.
     *
     * @since 1.0.0
     * @return string Messages URL.
     */
    private function get_messages_url() {
        // Try to find a page with the messages shortcode
        $pages = get_posts( array(
            'post_type' => 'page',
            'post_status' => 'publish',
            's' => '[schedspot_messages]',
            'numberposts' => 1
        ) );

        if ( ! empty( $pages ) ) {
            return get_permalink( $pages[0]->ID );
        }

        return home_url( '/messages/' );
    }

    /**
     * Get profile URL.
     *
     * @since 1.0.0
     * @return string Profile URL.
     */
    private function get_profile_url() {
        // For now, redirect to dashboard with profile tab
        return $this->get_dashboard_url() . '#profile';
    }

    /**
     * Get role display name.
     *
     * @since 0.1.0
     * @param string $role Role name.
     * @return string Role display name.
     */
    private function get_role_display_name( $role ) {
        $role_names = array(
            'schedspot_customer' => __( 'Customer', 'schedspot' ),
            'schedspot_worker'   => __( 'Service Provider', 'schedspot' ),
            'administrator'      => __( 'Administrator', 'schedspot' ),
        );

        return isset( $role_names[ $role ] ) ? $role_names[ $role ] : __( 'User', 'schedspot' );
    }

    /**
     * Render customer dashboard.
     *
     * @since 0.1.0
     * @param int $user_id User ID.
     */
    private function render_customer_dashboard( $user_id ) {
        $bookings = SchedSpot_Booking::get_bookings( array(
            'user_id' => $user_id,
            'limit'   => 10,
        ) );

        ?>
        <div class="schedspot-customer-dashboard">
            <!-- Messages Section -->
            <?php if ( get_option( 'schedspot_enable_messaging', true ) && current_user_can( 'schedspot_read_messages' ) ) : ?>
                <?php
                $messaging = new SchedSpot_Messaging();
                $unread_count = $messaging->get_unread_count( $user_id );
                ?>
                <div class="schedspot-messages-section">
                    <h3>
                        <?php _e( 'Messages', 'schedspot' ); ?>
                        <?php if ( $unread_count > 0 ) : ?>
                            <span class="unread-badge"><?php echo esc_html( $unread_count ); ?></span>
                        <?php endif; ?>
                    </h3>
                    <div class="messages-preview">
                        <?php echo do_shortcode( '[schedspot_messages]' ); ?>
                    </div>
                </div>
            <?php endif; ?>

            <h3><?php _e( 'My Bookings', 'schedspot' ); ?></h3>
            <?php if ( ! empty( $bookings ) ) : ?>
                <div class="schedspot-bookings-list">
                    <?php foreach ( $bookings as $booking ) : ?>
                        <div class="schedspot-booking-item">
                            <div class="booking-date"><?php echo esc_html( date( 'M j, Y', strtotime( $booking->booking_date ) ) ); ?></div>
                            <div class="booking-time"><?php echo esc_html( date( 'g:i A', strtotime( $booking->start_time ) ) ); ?></div>
                            <div class="booking-status"><?php echo esc_html( ucfirst( $booking->status ) ); ?></div>
                            <div class="booking-cost">$<?php echo esc_html( number_format( $booking->total_cost, 2 ) ); ?></div>
                            <div class="booking-actions">
                                <button class="schedspot-btn schedspot-btn-small view-booking-details" data-booking-id="<?php echo esc_attr( $booking->id ); ?>">
                                    <?php _e( 'View Details', 'schedspot' ); ?>
                                </button>
                                <?php if ( get_option( 'schedspot_enable_messaging', true ) ) : ?>
                                    <button class="schedspot-btn schedspot-btn-small schedspot-btn-secondary" onclick="messageWorker(<?php echo esc_attr( $booking->worker_id ); ?>, <?php echo esc_attr( $booking->id ); ?>)">
                                        <?php _e( 'Message Worker', 'schedspot' ); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p><?php _e( 'You have no bookings yet.', 'schedspot' ); ?></p>
            <?php endif; ?>
        </div>

        <script>
        function messageWorker(workerId, bookingId) {
            // This would open a messaging interface or redirect to messages page
            var messagesUrl = '<?php echo home_url( '/messages/' ); ?>?user_id=' + workerId + '&booking_id=' + bookingId;
            window.location.href = messagesUrl;
        }
        </script>
        <?php
    }

    /**
     * Render worker dashboard.
     *
     * @since 1.0.0
     * @param int $user_id User ID.
     */
    private function render_worker_dashboard( $user_id ) {
        $worker = new SchedSpot_Worker( $user_id );
        $stats = $worker->get_statistics();
        $bookings = SchedSpot_Booking::get_bookings( array(
            'worker_id' => $user_id,
            'limit'     => 10,
            'orderby'   => 'booking_date',
            'order'     => 'DESC',
        ) );

        ?>
        <div class="schedspot-worker-dashboard">
            <!-- Statistics Overview -->
            <div class="schedspot-dashboard-stats">
                <h3><?php _e( 'Overview', 'schedspot' ); ?></h3>
                <div class="schedspot-stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo esc_html( $stats['month_bookings'] ); ?></div>
                        <div class="stat-label"><?php _e( 'This Month', 'schedspot' ); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">$<?php echo esc_html( number_format( $stats['month_earnings'], 2 ) ); ?></div>
                        <div class="stat-label"><?php _e( 'Month Earnings', 'schedspot' ); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo esc_html( $stats['completion_rate'] ); ?>%</div>
                        <div class="stat-label"><?php _e( 'Completion Rate', 'schedspot' ); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo esc_html( number_format( $stats['rating'], 1 ) ); ?></div>
                        <div class="stat-label"><?php _e( 'Rating', 'schedspot' ); ?></div>
                    </div>
                </div>
            </div>

            <!-- Profile Completion -->
            <?php if ( $stats['profile_completion'] < 100 ) : ?>
                <div class="schedspot-profile-completion">
                    <h4><?php _e( 'Complete Your Profile', 'schedspot' ); ?></h4>
                    <div class="schedspot-progress-bar">
                        <div class="progress-fill" style="width: <?php echo esc_attr( $stats['profile_completion'] ); ?>%;"></div>
                        <span class="progress-text"><?php echo esc_html( $stats['profile_completion'] ); ?>% <?php _e( 'Complete', 'schedspot' ); ?></span>
                    </div>
                    <p><?php _e( 'A complete profile helps you get more bookings!', 'schedspot' ); ?></p>
                </div>
            <?php endif; ?>

            <!-- Service Areas Management -->
            <?php if ( get_option( 'schedspot_enable_geofencing', false ) ) : ?>
            <div class="schedspot-service-areas">
                <h4><?php _e( 'Service Areas', 'schedspot' ); ?></h4>
                <p><?php _e( 'Define the areas where you provide services. This helps customers find you when they\'re in your service area.', 'schedspot' ); ?></p>
                <div id="schedspot-service-area-map" style="height: 400px; width: 100%; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px;"></div>
                <div id="schedspot-service-areas-list">
                    <p><?php _e( 'Loading service areas...', 'schedspot' ); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="schedspot-quick-actions">
                <h4><?php _e( 'Quick Actions', 'schedspot' ); ?></h4>

                <!-- Availability Status Display -->
                <div class="availability-status-section">
                    <div class="availability-status-display">
                        <span class="availability-label"><?php _e( 'Current Status:', 'schedspot' ); ?></span>
                        <span class="availability-status <?php echo $worker->profile['is_available'] ? 'available' : 'unavailable'; ?>" id="availability-status">
                            <span class="status-indicator"></span>
                            <span class="status-text"><?php echo $worker->profile['is_available'] ? __( 'Available', 'schedspot' ) : __( 'Unavailable', 'schedspot' ); ?></span>
                        </span>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="schedspot-btn schedspot-btn-primary availability-toggle" onclick="toggleAvailability()" id="availability-toggle-btn">
                        <?php echo $worker->profile['is_available'] ? __( 'Set Unavailable', 'schedspot' ) : __( 'Set Available', 'schedspot' ); ?>
                    </button>
                    <button class="schedspot-btn" onclick="openAvailabilityEditor()"><?php _e( 'Edit Schedule', 'schedspot' ); ?></button>
                    <button class="schedspot-btn" onclick="viewEarnings()"><?php _e( 'View Earnings', 'schedspot' ); ?></button>
                    <button class="schedspot-btn" onclick="manageProfile()"><?php _e( 'Manage Profile', 'schedspot' ); ?></button>
                    <button class="schedspot-btn" onclick="manageServices()"><?php _e( 'Manage Services', 'schedspot' ); ?></button>
                    <button class="schedspot-btn" onclick="managePayments()"><?php _e( 'Payment Settings', 'schedspot' ); ?></button>
                    <?php if ( get_option( 'schedspot_enable_geofencing', false ) ) : ?>
                    <button class="schedspot-btn" onclick="manageServiceAreas()"><?php _e( 'Service Areas', 'schedspot' ); ?></button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Worker Settings Modal Container -->
            <div id="schedspot-worker-settings-modal" class="schedspot-modal" style="display: none;">
                <div class="schedspot-modal-content schedspot-settings-modal">
                    <div class="schedspot-modal-header">
                        <h3 id="settings-modal-title"><?php _e( 'Worker Settings', 'schedspot' ); ?></h3>
                        <span class="schedspot-modal-close" onclick="closeWorkerSettings()">&times;</span>
                    </div>
                    <div class="schedspot-modal-body">
                        <div class="schedspot-settings-tabs">
                            <div class="settings-tab-nav">
                                <button class="settings-tab-btn active" onclick="showSettingsTab('profile')"><?php _e( 'Profile', 'schedspot' ); ?></button>
                                <button class="settings-tab-btn" onclick="showSettingsTab('schedule')"><?php _e( 'Schedule', 'schedspot' ); ?></button>
                                <button class="settings-tab-btn" onclick="showSettingsTab('services')"><?php _e( 'Services', 'schedspot' ); ?></button>
                                <button class="settings-tab-btn" onclick="showSettingsTab('payments')"><?php _e( 'Payments', 'schedspot' ); ?></button>
                                <?php if ( get_option( 'schedspot_enable_geofencing', false ) ) : ?>
                                <button class="settings-tab-btn" onclick="showSettingsTab('geolocation')"><?php _e( 'Service Areas', 'schedspot' ); ?></button>
                                <?php endif; ?>
                            </div>
                            <div class="settings-tab-content">
                                <div id="settings-tab-profile" class="settings-tab-pane active">
                                    <div id="profile-settings-content">
                                        <p><?php _e( 'Loading profile settings...', 'schedspot' ); ?></p>
                                    </div>
                                </div>
                                <div id="settings-tab-schedule" class="settings-tab-pane">
                                    <div id="schedule-settings-content">
                                        <p><?php _e( 'Loading schedule settings...', 'schedspot' ); ?></p>
                                    </div>
                                </div>
                                <div id="settings-tab-services" class="settings-tab-pane">
                                    <div id="services-settings-content">
                                        <p><?php _e( 'Loading services settings...', 'schedspot' ); ?></p>
                                    </div>
                                </div>
                                <div id="settings-tab-payments" class="settings-tab-pane">
                                    <div id="payments-settings-content">
                                        <p><?php _e( 'Loading payment settings...', 'schedspot' ); ?></p>
                                    </div>
                                </div>
                                <?php if ( get_option( 'schedspot_enable_geofencing', false ) ) : ?>
                                <div id="settings-tab-geolocation" class="settings-tab-pane">
                                    <div id="geolocation-settings-content">
                                        <p><?php _e( 'Loading geolocation settings...', 'schedspot' ); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="schedspot-recent-bookings">
                <h3><?php _e( 'Recent Jobs', 'schedspot' ); ?></h3>
                <?php if ( ! empty( $bookings ) ) : ?>
                    <div class="schedspot-bookings-list">
                        <?php foreach ( $bookings as $booking ) : ?>
                            <div class="schedspot-booking-item" data-booking-id="<?php echo esc_attr( $booking->id ); ?>">
                                <div class="booking-header">
                                    <div class="booking-client">
                                        <strong><?php echo esc_html( $booking->client_details['name'] ); ?></strong>
                                        <span class="booking-status status-<?php echo esc_attr( $booking->status ); ?>">
                                            <?php echo esc_html( ucfirst( str_replace( '_', ' ', $booking->status ) ) ); ?>
                                        </span>
                                    </div>
                                    <div class="booking-earnings">$<?php echo esc_html( number_format( $booking->total_cost - $booking->commission_amount, 2 ) ); ?></div>
                                </div>
                                <div class="booking-details">
                                    <div class="booking-datetime">
                                        <span class="booking-date"><?php echo esc_html( date( 'M j, Y', strtotime( $booking->booking_date ) ) ); ?></span>
                                        <span class="booking-time"><?php echo esc_html( date( 'g:i A', strtotime( $booking->start_time ) ) ); ?></span>
                                        <span class="booking-duration"><?php printf( __( '%d min', 'schedspot' ), $booking->duration ); ?></span>
                                    </div>
                                    <?php if ( ! empty( $booking->client_details['address'] ) ) : ?>
                                        <div class="booking-address"><?php echo esc_html( $booking->client_details['address'] ); ?></div>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $booking->notes ) ) : ?>
                                        <div class="booking-notes"><?php echo esc_html( $booking->notes ); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="booking-actions">
                                    <button class="schedspot-btn schedspot-btn-small view-booking-details" data-booking-id="<?php echo esc_attr( $booking->id ); ?>">
                                        <?php _e( 'View Details', 'schedspot' ); ?>
                                    </button>
                                    <?php if ( $booking->status === 'pending' ) : ?>
                                        <button class="schedspot-btn schedspot-btn-small schedspot-btn-success" onclick="updateBookingStatus(<?php echo esc_attr( $booking->id ); ?>, 'confirmed')"><?php _e( 'Accept', 'schedspot' ); ?></button>
                                        <button class="schedspot-btn schedspot-btn-small schedspot-btn-secondary" onclick="updateBookingStatus(<?php echo esc_attr( $booking->id ); ?>, 'cancelled')"><?php _e( 'Decline', 'schedspot' ); ?></button>
                                    <?php elseif ( $booking->status === 'confirmed' ) : ?>
                                        <button class="schedspot-btn schedspot-btn-small schedspot-btn-success" onclick="updateBookingStatus(<?php echo esc_attr( $booking->id ); ?>, 'in_progress')"><?php _e( 'Start Job', 'schedspot' ); ?></button>
                                        <button class="schedspot-btn schedspot-btn-small schedspot-btn-success" onclick="requestDeposit(<?php echo esc_attr( $booking->id ); ?>)"><?php _e( 'Request Deposit', 'schedspot' ); ?></button>
                                        <button class="schedspot-btn schedspot-btn-small schedspot-btn-secondary" onclick="rescheduleBooking(<?php echo esc_attr( $booking->id ); ?>)"><?php _e( 'Reschedule', 'schedspot' ); ?></button>
                                    <?php elseif ( $booking->status === 'in_progress' ) : ?>
                                        <button class="schedspot-btn schedspot-btn-small" onclick="updateBookingStatus(<?php echo esc_attr( $booking->id ); ?>, 'completed')"><?php _e( 'Complete', 'schedspot' ); ?></button>
                                        <button class="schedspot-btn schedspot-btn-small schedspot-btn-warning" onclick="requestProgress(<?php echo esc_attr( $booking->id ); ?>)"><?php _e( 'Request Progress Payment', 'schedspot' ); ?></button>
                                    <?php elseif ( $booking->status === 'completed' ) : ?>
                                        <button class="schedspot-btn schedspot-btn-small schedspot-btn-success" onclick="requestFinalPayment(<?php echo esc_attr( $booking->id ); ?>)"><?php _e( 'Request Final Payment', 'schedspot' ); ?></button>
                                        <button class="schedspot-btn schedspot-btn-small schedspot-btn-link" onclick="generateInvoice(<?php echo esc_attr( $booking->id ); ?>)"><?php _e( 'Generate Invoice', 'schedspot' ); ?></button>
                                    <?php endif; ?>
                                    <?php if ( get_option( 'schedspot_enable_messaging', true ) ) : ?>
                                        <button class="schedspot-btn schedspot-btn-small schedspot-btn-link" onclick="messageClient(<?php echo esc_attr( $booking->user_id ); ?>, <?php echo esc_attr( $booking->id ); ?>)"><?php _e( 'Message', 'schedspot' ); ?></button>
                                    <?php endif; ?>
                                    <button class="schedspot-btn schedspot-btn-small schedspot-btn-link" onclick="contactClient('<?php echo esc_attr( $booking->client_details['email'] ); ?>', '<?php echo esc_attr( $booking->client_details['phone'] ); ?>')"><?php _e( 'Contact', 'schedspot' ); ?></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="schedspot-view-all">
                        <a href="#" onclick="loadAllBookings()" class="schedspot-btn schedspot-btn-link"><?php _e( 'View All Jobs', 'schedspot' ); ?></a>
                    </div>
                <?php else : ?>
                    <div class="schedspot-empty-state">
                        <p><?php _e( 'You have no jobs scheduled.', 'schedspot' ); ?></p>
                        <p><?php _e( 'Complete your profile and set your availability to start receiving bookings!', 'schedspot' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
        // Worker dashboard JavaScript functions
        function toggleAvailability() {
            const restUrl = '<?php echo rest_url( 'schedspot/v1/workers/' . $user_id . '/profile' ); ?>';
            const nonce = '<?php echo wp_create_nonce( 'wp_rest' ); ?>';
            const currentStatus = document.getElementById('availability-status').classList.contains('available');
            const newStatus = !currentStatus;

            // Update button state immediately for better UX
            const toggleBtn = document.getElementById('availability-toggle-btn');
            const statusElement = document.getElementById('availability-status');
            const statusText = statusElement.querySelector('.status-text');

            toggleBtn.disabled = true;
            toggleBtn.textContent = '<?php _e( 'Updating...', 'schedspot' ); ?>';

            fetch(restUrl, {
                method: 'PUT',
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
                if (data.is_available !== undefined) {
                    // Update UI immediately without page reload
                    if (data.is_available) {
                        statusElement.className = 'availability-status available';
                        statusText.textContent = '<?php _e( 'Available', 'schedspot' ); ?>';
                        toggleBtn.textContent = '<?php _e( 'Set Unavailable', 'schedspot' ); ?>';
                    } else {
                        statusElement.className = 'availability-status unavailable';
                        statusText.textContent = '<?php _e( 'Unavailable', 'schedspot' ); ?>';
                        toggleBtn.textContent = '<?php _e( 'Set Available', 'schedspot' ); ?>';
                    }

                    // Show success message
                    showNotification('<?php _e( 'Availability status updated successfully!', 'schedspot' ); ?>', 'success');
                } else {
                    throw new Error('Invalid response');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('<?php _e( 'Failed to update availability status. Please try again.', 'schedspot' ); ?>', 'error');

                // Reset button text on error
                toggleBtn.textContent = currentStatus ? '<?php _e( 'Set Unavailable', 'schedspot' ); ?>' : '<?php _e( 'Set Available', 'schedspot' ); ?>';
            })
            .finally(() => {
                toggleBtn.disabled = false;
            });
        }

        function showNotification(message, type) {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.schedspot-notification');
            existingNotifications.forEach(notification => notification.remove());

            // Create new notification
            const notification = document.createElement('div');
            notification.className = `schedspot-notification ${type}`;
            notification.innerHTML = `
                <span class="notification-icon">${type === 'success' ? '✓' : '⚠'}</span>
                <span class="notification-message">${message}</span>
                <button class="notification-close" onclick="this.parentElement.remove()">×</button>
            `;

            // Add to page
            document.body.appendChild(notification);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        function updateBookingStatus(bookingId, status) {
            const restUrl = '<?php echo rest_url( 'schedspot/v1/bookings/' ); ?>' + bookingId;
            const nonce = '<?php echo wp_create_nonce( 'wp_rest' ); ?>';

            fetch(restUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.id) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function contactClient(email, phone) {
            const message = '<?php _e( 'Choose contact method:', 'schedspot' ); ?>';
            const emailText = '<?php _e( 'Email', 'schedspot' ); ?>';
            const phoneText = '<?php _e( 'Phone', 'schedspot' ); ?>';

            if (email && phone) {
                const choice = confirm(message + '\n\n' + emailText + ': ' + email + '\n' + phoneText + ': ' + phone + '\n\n<?php _e( 'Click OK for email, Cancel for phone', 'schedspot' ); ?>');
                if (choice) {
                    window.location.href = 'mailto:' + email;
                } else {
                    window.location.href = 'tel:' + phone;
                }
            } else if (email) {
                window.location.href = 'mailto:' + email;
            } else if (phone) {
                window.location.href = 'tel:' + phone;
            }
        }

        function openAvailabilityEditor() {
            // Redirect to availability management page
            window.location.href = '<?php echo admin_url( 'admin.php?page=schedspot-workers&action=availability&worker_id=' ); ?>' + getCurrentUserId();
        }

        function viewEarnings() {
            // Show detailed earnings breakdown
            showEarningsModal();
        }

        function rescheduleBooking(bookingId) {
            // Open reschedule interface
            showRescheduleModal(bookingId);
        }

        function loadAllBookings() {
            // Load all bookings with pagination
            loadBookingsPage(1);
        }

        function showEarningsModal() {
            // Create and show earnings modal
            var modal = document.createElement('div');
            modal.className = 'schedspot-modal';
            modal.innerHTML = `
                <div class="schedspot-modal-content">
                    <div class="schedspot-modal-header">
                        <h3><?php _e( 'Earnings Breakdown', 'schedspot' ); ?></h3>
                        <span class="schedspot-modal-close" onclick="closeModal(this)">&times;</span>
                    </div>
                    <div class="schedspot-modal-body">
                        <div id="earnings-content">
                            <p><?php _e( 'Loading earnings data...', 'schedspot' ); ?></p>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Load earnings data via AJAX
            loadEarningsData();
        }

        function showRescheduleModal(bookingId) {
            // Create and show reschedule modal
            var modal = document.createElement('div');
            modal.className = 'schedspot-modal';
            modal.innerHTML = `
                <div class="schedspot-modal-content">
                    <div class="schedspot-modal-header">
                        <h3><?php _e( 'Reschedule Booking', 'schedspot' ); ?></h3>
                        <span class="schedspot-modal-close" onclick="closeModal(this)">&times;</span>
                    </div>
                    <div class="schedspot-modal-body">
                        <div id="reschedule-content">
                            <p><?php _e( 'Loading booking details...', 'schedspot' ); ?></p>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Load booking data and reschedule form
            loadRescheduleForm(bookingId);
        }

        function loadBookingsPage(page) {
            // Load bookings with pagination
            var container = document.getElementById('worker-bookings');
            if (!container) return;

            container.innerHTML = '<p><?php _e( 'Loading bookings...', 'schedspot' ); ?></p>';

            // AJAX call to load bookings
            fetch('<?php echo rest_url( 'schedspot/v1/bookings' ); ?>?per_page=10&offset=' + ((page - 1) * 10), {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                displayBookings(data, page);
            })
            .catch(error => {
                container.innerHTML = '<p><?php _e( 'Error loading bookings.', 'schedspot' ); ?></p>';
            });
        }

        function loadEarningsData() {
            fetch('<?php echo rest_url( 'schedspot/v1/workers/' ); ?>' + getCurrentUserId() + '/statistics', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                displayEarningsData(data);
            })
            .catch(error => {
                document.getElementById('earnings-content').innerHTML = '<p><?php _e( 'Error loading earnings data.', 'schedspot' ); ?></p>';
            });
        }

        function loadRescheduleForm(bookingId) {
            fetch('<?php echo rest_url( 'schedspot/v1/bookings/' ); ?>' + bookingId, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                displayRescheduleForm(data);
            })
            .catch(error => {
                document.getElementById('reschedule-content').innerHTML = '<p><?php _e( 'Error loading booking details.', 'schedspot' ); ?></p>';
            });
        }

        function displayBookings(bookings, page) {
            var container = document.getElementById('worker-bookings');
            var html = '<div class="schedspot-bookings-list">';

            if (bookings.length === 0) {
                html += '<p><?php _e( 'No bookings found.', 'schedspot' ); ?></p>';
            } else {
                bookings.forEach(function(booking) {
                    html += `
                        <div class="schedspot-booking-item">
                            <div class="booking-info">
                                <h4>${booking.service_name || '<?php _e( 'General Service', 'schedspot' ); ?>'}</h4>
                                <p><strong><?php _e( 'Date:', 'schedspot' ); ?></strong> ${booking.booking_date}</p>
                                <p><strong><?php _e( 'Time:', 'schedspot' ); ?></strong> ${booking.start_time} - ${booking.end_time}</p>
                                <p><strong><?php _e( 'Status:', 'schedspot' ); ?></strong> ${booking.status}</p>
                                <p><strong><?php _e( 'Cost:', 'schedspot' ); ?></strong> $${booking.total_cost}</p>
                            </div>
                            <div class="booking-actions">
                                <button onclick="rescheduleBooking(${booking.id})" class="button"><?php _e( 'Reschedule', 'schedspot' ); ?></button>
                                <button onclick="messageClient(${booking.user_id}, ${booking.id})" class="button"><?php _e( 'Message Client', 'schedspot' ); ?></button>
                            </div>
                        </div>
                    `;
                });
            }

            html += '</div>';
            container.innerHTML = html;
        }

        function displayEarningsData(data) {
            var content = document.getElementById('earnings-content');
            var html = `
                <div class="schedspot-earnings-summary">
                    <div class="earnings-stat">
                        <h4><?php _e( 'Total Earnings', 'schedspot' ); ?></h4>
                        <p class="amount">$${data.total_earnings || '0.00'}</p>
                    </div>
                    <div class="earnings-stat">
                        <h4><?php _e( 'This Month', 'schedspot' ); ?></h4>
                        <p class="amount">$${data.monthly_earnings || '0.00'}</p>
                    </div>
                    <div class="earnings-stat">
                        <h4><?php _e( 'Completed Jobs', 'schedspot' ); ?></h4>
                        <p class="count">${data.completed_bookings || '0'}</p>
                    </div>
                    <div class="earnings-stat">
                        <h4><?php _e( 'Average Rating', 'schedspot' ); ?></h4>
                        <p class="rating">${data.average_rating || 'N/A'}</p>
                    </div>
                </div>
            `;
            content.innerHTML = html;
        }

        function displayRescheduleForm(booking) {
            var content = document.getElementById('reschedule-content');
            var html = `
                <form id="reschedule-form">
                    <input type="hidden" name="booking_id" value="${booking.id}">
                    <div class="form-group">
                        <label><?php _e( 'Current Date:', 'schedspot' ); ?></label>
                        <p>${booking.booking_date} ${booking.start_time} - ${booking.end_time}</p>
                    </div>
                    <div class="form-group">
                        <label for="new_date"><?php _e( 'New Date:', 'schedspot' ); ?></label>
                        <input type="date" id="new_date" name="new_date" required>
                    </div>
                    <div class="form-group">
                        <label for="new_start_time"><?php _e( 'New Start Time:', 'schedspot' ); ?></label>
                        <input type="time" id="new_start_time" name="new_start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="reschedule_reason"><?php _e( 'Reason for Reschedule:', 'schedspot' ); ?></label>
                        <textarea id="reschedule_reason" name="reschedule_reason" rows="3"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="button button-primary"><?php _e( 'Reschedule Booking', 'schedspot' ); ?></button>
                        <button type="button" onclick="closeModal(this)" class="button"><?php _e( 'Cancel', 'schedspot' ); ?></button>
                    </div>
                </form>
            `;
            content.innerHTML = html;

            // Add form submit handler
            document.getElementById('reschedule-form').addEventListener('submit', handleRescheduleSubmit);
        }

        function handleRescheduleSubmit(e) {
            e.preventDefault();
            var formData = new FormData(e.target);
            var bookingId = formData.get('booking_id');

            var updateData = {
                booking_date: formData.get('new_date'),
                start_time: formData.get('new_start_time'),
                notes: formData.get('reschedule_reason')
            };

            fetch('<?php echo rest_url( 'schedspot/v1/bookings/' ); ?>' + bookingId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
                },
                body: JSON.stringify(updateData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.id) {
                    alert('<?php _e( 'Booking rescheduled successfully!', 'schedspot' ); ?>');
                    closeModal(document.querySelector('.schedspot-modal-close'));
                    location.reload(); // Refresh to show updated booking
                } else {
                    alert('<?php _e( 'Error rescheduling booking. Please try again.', 'schedspot' ); ?>');
                }
            })
            .catch(error => {
                alert('<?php _e( 'Error rescheduling booking. Please try again.', 'schedspot' ); ?>');
            });
        }

        function closeModal(element) {
            var modal = element.closest('.schedspot-modal');
            if (modal) {
                modal.remove();
            }
        }

        function getCurrentUserId() {
            return <?php echo get_current_user_id(); ?>;
        }

        function messageClient(clientId, bookingId) {
            // This would open a messaging interface or redirect to messages page
            var messagesUrl = '<?php echo home_url( '/messages/' ); ?>?user_id=' + clientId + '&booking_id=' + bookingId;
            window.location.href = messagesUrl;
        }

        function requestDeposit(bookingId) {
            if (confirm('<?php _e( 'Send deposit request to client?', 'schedspot' ); ?>')) {
                jQuery.post(ajaxurl, {
                    action: 'schedspot_request_deposit',
                    booking_id: bookingId,
                    nonce: '<?php echo wp_create_nonce( 'schedspot_payment_request' ); ?>'
                }, function(response) {
                    if (response.success) {
                        showNotification('<?php _e( 'Deposit request sent to client.', 'schedspot' ); ?>', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showNotification(response.data.message || '<?php _e( 'Error sending deposit request.', 'schedspot' ); ?>', 'error');
                    }
                });
            }
        }

        function requestProgress(bookingId) {
            if (confirm('<?php _e( 'Send progress payment request to client?', 'schedspot' ); ?>')) {
                jQuery.post(ajaxurl, {
                    action: 'schedspot_request_progress_payment',
                    booking_id: bookingId,
                    nonce: '<?php echo wp_create_nonce( 'schedspot_payment_request' ); ?>'
                }, function(response) {
                    if (response.success) {
                        showNotification('<?php _e( 'Progress payment request sent to client.', 'schedspot' ); ?>', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showNotification(response.data.message || '<?php _e( 'Error sending payment request.', 'schedspot' ); ?>', 'error');
                    }
                });
            }
        }

        function requestFinalPayment(bookingId) {
            if (confirm('<?php _e( 'Send final payment request to client?', 'schedspot' ); ?>')) {
                jQuery.post(ajaxurl, {
                    action: 'schedspot_request_final_payment',
                    booking_id: bookingId,
                    nonce: '<?php echo wp_create_nonce( 'schedspot_payment_request' ); ?>'
                }, function(response) {
                    if (response.success) {
                        showNotification('<?php _e( 'Final payment request sent to client.', 'schedspot' ); ?>', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showNotification(response.data.message || '<?php _e( 'Error sending payment request.', 'schedspot' ); ?>', 'error');
                    }
                });
            }
        }

        function generateInvoice(bookingId) {
            var invoiceUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>?action=schedspot_generate_invoice&booking_id=' + bookingId + '&nonce=<?php echo wp_create_nonce( 'schedspot_invoice' ); ?>';
            window.open(invoiceUrl, '_blank');
        }

        // Enhanced Worker Settings Functions
        function openWorkerSettings() {
            document.getElementById('schedspot-worker-settings-modal').style.display = 'block';
            showSettingsTab('profile');
        }

        function closeWorkerSettings() {
            document.getElementById('schedspot-worker-settings-modal').style.display = 'none';
        }

        function showSettingsTab(tabName) {
            // Hide all tab panes
            var panes = document.querySelectorAll('.settings-tab-pane');
            panes.forEach(function(pane) {
                pane.classList.remove('active');
            });

            // Remove active class from all tab buttons
            var buttons = document.querySelectorAll('.settings-tab-btn');
            buttons.forEach(function(btn) {
                btn.classList.remove('active');
            });

            // Show selected tab pane
            document.getElementById('settings-tab-' + tabName).classList.add('active');

            // Add active class to selected button
            event.target.classList.add('active');

            // Load content for the selected tab
            loadSettingsTabContent(tabName);
        }

        function loadSettingsTabContent(tabName) {
            var contentDiv = document.getElementById(tabName + '-settings-content');

            switch(tabName) {
                case 'profile':
                    loadProfileSettings(contentDiv);
                    break;
                case 'schedule':
                    loadScheduleSettings(contentDiv);
                    break;
                case 'services':
                    loadServicesSettings(contentDiv);
                    break;
                case 'payments':
                    loadPaymentSettings(contentDiv);
                    break;
                case 'geolocation':
                    loadGeolocationSettings(contentDiv);
                    break;
            }
        }

        function loadProfileSettings(container) {
            fetch('<?php echo rest_url( 'schedspot/v1/workers/' ); ?>' + getCurrentUserId() + '/profile', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                container.innerHTML = `
                    <form id="profile-settings-form">
                        <div class="form-group">
                            <label for="worker_bio"><?php _e( 'Bio', 'schedspot' ); ?></label>
                            <textarea id="worker_bio" name="bio" rows="4" placeholder="<?php _e( 'Tell clients about yourself...', 'schedspot' ); ?>">${data.bio || ''}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="worker_skills"><?php _e( 'Skills', 'schedspot' ); ?></label>
                            <input type="text" id="worker_skills" name="skills" value="${data.skills || ''}" placeholder="<?php _e( 'e.g., Plumbing, Electrical, Carpentry', 'schedspot' ); ?>">
                        </div>
                        <div class="form-group">
                            <label for="worker_hourly_rate"><?php _e( 'Hourly Rate ($)', 'schedspot' ); ?></label>
                            <input type="number" id="worker_hourly_rate" name="hourly_rate" value="${data.hourly_rate || ''}" step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label for="worker_phone"><?php _e( 'Phone Number', 'schedspot' ); ?></label>
                            <input type="tel" id="worker_phone" name="phone" value="${data.phone || ''}">
                        </div>
                        <div class="form-group">
                            <label for="worker_certifications"><?php _e( 'Certifications', 'schedspot' ); ?></label>
                            <textarea id="worker_certifications" name="certifications" rows="3" placeholder="<?php _e( 'List your certifications...', 'schedspot' ); ?>">${data.certifications || ''}</textarea>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_available" ${data.is_available ? 'checked' : ''}>
                                <?php _e( 'Currently Available for Bookings', 'schedspot' ); ?>
                            </label>
                        </div>
                        <button type="submit" class="schedspot-btn schedspot-btn-primary"><?php _e( 'Save Profile', 'schedspot' ); ?></button>
                    </form>
                `;

                document.getElementById('profile-settings-form').addEventListener('submit', saveProfileSettings);
            })
            .catch(error => {
                container.innerHTML = '<p><?php _e( 'Error loading profile settings.', 'schedspot' ); ?></p>';
            });
        }

        // Quick action functions
        function manageProfile() {
            openWorkerSettings();
            showSettingsTab('profile');
        }

        function manageServices() {
            openWorkerSettings();
            showSettingsTab('services');
        }

        function managePayments() {
            openWorkerSettings();
            showSettingsTab('payments');
        }

        function manageServiceAreas() {
            openWorkerSettings();
            showSettingsTab('geolocation');
        }

        // Save functions
        function saveProfileSettings(e) {
            e.preventDefault();
            var formData = new FormData(e.target);
            var profileData = {};

            for (var pair of formData.entries()) {
                if (pair[0] === 'is_available') {
                    profileData[pair[0]] = true;
                } else {
                    profileData[pair[0]] = pair[1];
                }
            }

            if (!formData.has('is_available')) {
                profileData.is_available = false;
            }

            fetch('<?php echo rest_url( 'schedspot/v1/workers/' ); ?>' + getCurrentUserId() + '/profile', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
                },
                body: JSON.stringify(profileData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.id) {
                    alert('<?php _e( 'Profile updated successfully!', 'schedspot' ); ?>');
                    location.reload();
                } else {
                    alert('<?php _e( 'Error updating profile.', 'schedspot' ); ?>');
                }
            })
            .catch(error => {
                alert('<?php _e( 'Error updating profile.', 'schedspot' ); ?>');
            });
        }
        </script>
        <?php
    }

    /**
     * Render admin dashboard.
     *
     * @since 1.0.0
     * @param int $user_id User ID.
     */
    private function render_admin_dashboard( $user_id ) {
        ?>
        <div class="schedspot-admin-dashboard">
            <div class="admin-dashboard-notice">
                <h3><?php _e( 'Administrator Dashboard', 'schedspot' ); ?></h3>
                <p><?php _e( 'You are viewing the system as an administrator. Use the role switcher to test different user experiences.', 'schedspot' ); ?></p>
                <div class="admin-quick-links">
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot' ); ?>" class="button button-primary"><?php _e( 'Admin Dashboard', 'schedspot' ); ?></a>
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-role-switcher' ); ?>" class="button"><?php _e( 'Role Switcher', 'schedspot' ); ?></a>
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-settings' ); ?>" class="button"><?php _e( 'Settings', 'schedspot' ); ?></a>
                </div>
            </div>

            <!-- Quick Stats for Admin -->
            <div class="schedspot-dashboard-stats">
                <h3><?php _e( 'System Overview', 'schedspot' ); ?></h3>
                <div class="schedspot-stats-grid">
                    <?php
                    global $wpdb;
                    $total_bookings = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings" );
                    $pending_bookings = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE status = 'pending'" );
                    $total_workers = count( get_users( array( 'role' => 'schedspot_worker' ) ) );
                    $total_customers = count( get_users( array( 'role' => 'schedspot_customer' ) ) );
                    ?>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo esc_html( $total_bookings ); ?></div>
                        <div class="stat-label"><?php _e( 'Total Bookings', 'schedspot' ); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo esc_html( $pending_bookings ); ?></div>
                        <div class="stat-label"><?php _e( 'Pending Bookings', 'schedspot' ); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo esc_html( $total_workers ); ?></div>
                        <div class="stat-label"><?php _e( 'Workers', 'schedspot' ); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo esc_html( $total_customers ); ?></div>
                        <div class="stat-label"><?php _e( 'Customers', 'schedspot' ); ?></div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="schedspot-recent-activity">
                <h4><?php _e( 'Recent Bookings', 'schedspot' ); ?></h4>
                <?php
                $recent_bookings = SchedSpot_Booking::get_bookings( array( 'limit' => 5 ) );
                if ( ! empty( $recent_bookings ) ) :
                ?>
                <table class="schedspot-table">
                    <thead>
                        <tr>
                            <th><?php _e( 'Service', 'schedspot' ); ?></th>
                            <th><?php _e( 'Customer', 'schedspot' ); ?></th>
                            <th><?php _e( 'Worker', 'schedspot' ); ?></th>
                            <th><?php _e( 'Date', 'schedspot' ); ?></th>
                            <th><?php _e( 'Status', 'schedspot' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $recent_bookings as $booking ) : ?>
                        <tr>
                            <td><?php echo esc_html( $booking->service_name ?: __( 'General Service', 'schedspot' ) ); ?></td>
                            <td><?php echo esc_html( get_userdata( $booking->user_id )->display_name ); ?></td>
                            <td><?php echo esc_html( get_userdata( $booking->worker_id )->display_name ); ?></td>
                            <td><?php echo esc_html( $booking->booking_date ); ?></td>
                            <td><span class="status-<?php echo esc_attr( $booking->status ); ?>"><?php echo esc_html( ucfirst( $booking->status ) ); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                <p><?php _e( 'No recent bookings found.', 'schedspot' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Find available worker for booking.
     *
     * @since 0.1.0
     * @param array $booking_data Booking data.
     * @return int Worker ID or 0 if none found.
     */
    private function find_available_worker( $booking_data ) {
        $workers = get_users( array( 'role' => 'schedspot_worker' ) );

        foreach ( $workers as $worker ) {
            $conflict = SchedSpot_Booking::check_booking_conflict(
                $worker->ID,
                $booking_data['booking_date'],
                $booking_data['start_time'],
                $booking_data['end_time']
            );

            if ( ! $conflict ) {
                return $worker->ID;
            }
        }

        return 0;
    }

    /**
     * Enqueue booking form assets.
     *
     * @since 0.1.0
     */
    private function enqueue_booking_assets() {
        // Enqueue enhanced CSS file
        wp_enqueue_style( 'schedspot-frontend-enhanced', SCHEDSPOT_PLUGIN_URL . 'assets/css/frontend-enhanced.css', array(), SCHEDSPOT_VERSION );

        // Enqueue frontend JavaScript
        wp_enqueue_script( 'schedspot-frontend', SCHEDSPOT_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), SCHEDSPOT_VERSION, true );

        // Localize script with data
        wp_localize_script( 'schedspot-frontend', 'schedspot_frontend', array(
            'rest_url' => rest_url( 'schedspot/v1/' ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'default_avatar' => get_avatar_url( 0 ),
            'strings' => array(
                'any_worker' => __( 'Any available worker', 'schedspot' ),
                'loading_workers' => __( 'Loading workers...', 'schedspot' ),
                'error_loading_workers' => __( 'Error loading workers. Please try again.', 'schedspot' ),
                'no_workers_available' => __( 'No workers available for this service.', 'schedspot' ),
                'select_worker' => __( 'Select Worker', 'schedspot' ),
                'error_checking_availability' => __( 'Error checking availability. Please try again.', 'schedspot' ),
                'field_required' => __( 'This field is required.', 'schedspot' ),
                'invalid_email' => __( 'Please enter a valid email address.', 'schedspot' ),
                'processing' => __( 'Processing...', 'schedspot' ),
                'submit_booking' => __( 'Submit Booking', 'schedspot' ),
                'error_submitting_form' => __( 'Error submitting form. Please try again.', 'schedspot' ),
            ),
        ) );

        // Enqueue geolocation scripts if enabled
        if ( get_option( 'schedspot_enable_geofencing', false ) ) {
            $geolocation = new SchedSpot_Geolocation();
            $geolocation->enqueue_frontend_scripts();
        }
    }

    /**
     * Output booking form styles (deprecated - styles moved to CSS file).
     *
     * @since 0.1.0
     * @deprecated 1.0.0 Styles moved to frontend-enhanced.css
     */
    public function output_booking_styles() {
        // Styles have been moved to assets/css/frontend-enhanced.css
        // This method is kept for backward compatibility
    }

    /**
     * Messages shortcode.
     *
     * @since 2.0.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function messages( $atts ) {
        $atts = shortcode_atts( array(
            'class'       => 'schedspot-messaging',
            'user_id'     => 0,
            'booking_id'  => 0,
        ), $atts, 'schedspot_messages' );

        if ( ! is_user_logged_in() ) {
            return '<p>' . __( 'Please log in to access messaging.', 'schedspot' ) . '</p>';
        }

        if ( ! current_user_can( 'schedspot_read_messages' ) ) {
            return '<p>' . __( 'You do not have permission to access messaging.', 'schedspot' ) . '</p>';
        }

        ob_start();

        $current_user_id = get_current_user_id();
        $target_user_id = absint( $atts['user_id'] );
        $booking_id = absint( $atts['booking_id'] );

        ?>
        <div class="<?php echo esc_attr( $atts['class'] ); ?>">
            <div class="schedspot-conversations" id="schedspot-conversations">
                <div class="conversations-header">
                    <h3><?php _e( 'Conversations', 'schedspot' ); ?></h3>
                </div>
                <div class="conversations-list">
                    <!-- Conversations will be loaded here by JavaScript -->
                    <div class="loading"><?php _e( 'Loading conversations...', 'schedspot' ); ?></div>
                </div>
            </div>

            <div class="schedspot-chat-area">
                <div class="chat-header">
                    <h3 id="chat-title"><?php _e( 'Select a conversation', 'schedspot' ); ?></h3>
                </div>

                <div class="schedspot-messages" id="schedspot-messages">
                    <div class="no-conversation">
                        <p><?php _e( 'Select a conversation from the left to start messaging.', 'schedspot' ); ?></p>
                    </div>
                </div>

                <form class="schedspot-message-form" id="schedspot-message-form" style="display: none;">
                    <input type="hidden" id="schedspot-receiver-id" name="receiver_id" value="<?php echo esc_attr( $target_user_id ); ?>">
                    <?php if ( $booking_id ) : ?>
                        <input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking_id ); ?>">
                    <?php endif; ?>

                    <div class="schedspot-message-input">
                        <textarea
                            id="schedspot-message-content"
                            name="content"
                            placeholder="<?php esc_attr_e( 'Type your message...', 'schedspot' ); ?>"
                            rows="1"
                            required
                        ></textarea>

                        <div class="message-actions">
                            <label for="schedspot-message-attachment" class="attachment-button" title="<?php esc_attr_e( 'Attach File', 'schedspot' ); ?>">
                                📎
                                <input type="file" id="schedspot-message-attachment" name="attachment" style="display: none;" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt">
                            </label>

                            <button type="submit" class="send-button">
                                <?php _e( 'Send', 'schedspot' ); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ( $target_user_id ) : ?>
        <script>
        jQuery(document).ready(function($) {
            // Auto-select conversation if user_id is specified
            setTimeout(function() {
                SchedSpotMessaging.selectConversation(<?php echo $target_user_id; ?>);
            }, 1000);
        });
        </script>
        <?php endif; ?>

        <style>
        .schedspot-messaging {
            display: flex;
            height: 600px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: white;
        }

        .schedspot-conversations {
            width: 300px;
            border-right: 1px solid #ddd;
            background: #f9f9f9;
            display: flex;
            flex-direction: column;
        }

        .conversations-header {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            background: #fff;
        }

        .conversations-header h3 {
            margin: 0;
            font-size: 16px;
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }

        .schedspot-conversation-item {
            display: flex;
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .schedspot-conversation-item:hover {
            background: #f0f0f0;
        }

        .schedspot-conversation-item.active {
            background: #0073aa;
            color: white;
        }

        .schedspot-conversation-item .user-avatar {
            margin-right: 10px;
        }

        .schedspot-conversation-item .user-avatar img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .schedspot-conversation-item .conversation-info {
            flex: 1;
            min-width: 0;
        }

        .schedspot-conversation-item .user-name {
            font-weight: bold;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .schedspot-conversation-item .unread-count {
            background: #e74c3c;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: normal;
        }

        .schedspot-conversation-item .last-message {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .schedspot-conversation-item.active .last-message {
            color: rgba(255, 255, 255, 0.8);
        }

        .schedspot-conversation-item .time-ago {
            font-size: 11px;
            color: #999;
        }

        .schedspot-conversation-item.active .time-ago {
            color: rgba(255, 255, 255, 0.7);
        }

        .schedspot-chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            background: #fff;
        }

        .chat-header h3 {
            margin: 0;
            font-size: 16px;
        }

        .schedspot-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #fafafa;
        }

        .schedspot-message {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }

        .schedspot-message.own {
            flex-direction: row-reverse;
        }

        .schedspot-message .avatar {
            margin: 0 10px;
        }

        .schedspot-message .avatar img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }

        .schedspot-message .content {
            max-width: 70%;
            background: white;
            padding: 10px 15px;
            border-radius: 18px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .schedspot-message.own .content {
            background: #0073aa;
            color: white;
        }

        .schedspot-message .text {
            margin-bottom: 5px;
        }

        .schedspot-message .time {
            font-size: 11px;
            color: #999;
        }

        .schedspot-message.own .time {
            color: rgba(255, 255, 255, 0.7);
        }

        .schedspot-attachment {
            display: inline-block;
            padding: 5px 10px;
            background: rgba(0,0,0,0.1);
            border-radius: 10px;
            margin-top: 5px;
            text-decoration: none;
            color: inherit;
            font-size: 12px;
        }

        .schedspot-attachment:hover {
            background: rgba(0,0,0,0.2);
        }

        .schedspot-message-form {
            padding: 20px;
            border-top: 1px solid #ddd;
            background: white;
        }

        .schedspot-message-input {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .schedspot-message-input textarea {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            resize: none;
            min-height: 40px;
            max-height: 120px;
            font-family: inherit;
        }

        .message-actions {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .attachment-button {
            padding: 10px;
            background: #f1f1f1;
            border-radius: 50%;
            cursor: pointer;
            transition: background-color 0.2s;
            font-size: 16px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .attachment-button:hover {
            background: #e1e1e1;
        }

        .send-button {
            padding: 10px 20px;
            background: #0073aa;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.2s;
        }

        .send-button:hover {
            background: #005a87;
        }

        .send-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .loading, .no-conversation, .no-messages {
            text-align: center;
            color: #666;
            padding: 40px 20px;
        }

        .attachment-preview {
            margin-top: 10px;
            padding: 5px 10px;
            background: #e1f5fe;
            border-radius: 5px;
            font-size: 12px;
            color: #0277bd;
        }

        @media (max-width: 768px) {
            .schedspot-messaging {
                flex-direction: column;
                height: auto;
            }

            .schedspot-conversations {
                width: 100%;
                height: 200px;
            }

            .schedspot-chat-area {
                height: 400px;
            }
        }
        </style>
        <?php

        return ob_get_clean();
    }
}
