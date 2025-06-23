<?php
/**
 * SchedSpot Booking Form Shortcode
 *
 * Handles the booking form shortcode functionality
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Booking_Form Class.
 *
 * @class SchedSpot_Booking_Form
 * @version 1.0.0
 */
class SchedSpot_Booking_Form {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize booking form functionality.
     *
     * @since 1.0.0
     */
    public function init() {
        add_shortcode( 'schedspot_booking_form', array( $this, 'render_booking_form' ) );
        add_action( 'wp_ajax_schedspot_submit_booking', array( $this, 'handle_booking_submission' ) );
        add_action( 'wp_ajax_nopriv_schedspot_submit_booking', array( $this, 'handle_booking_submission' ) );
        add_action( 'wp_ajax_schedspot_get_available_workers', array( $this, 'get_available_workers' ) );
        add_action( 'wp_ajax_nopriv_schedspot_get_available_workers', array( $this, 'get_available_workers' ) );
    }

    /**
     * Render booking form shortcode.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Booking form HTML.
     */
    public function render_booking_form( $atts ) {
        $atts = shortcode_atts( array(
            'service_id' => '',
            'worker_id' => '',
            'show_worker_selection' => 'true',
            'show_payment_info' => 'true',
            'redirect_after_booking' => '',
        ), $atts );

        ob_start();
        ?>
        <div class="schedspot-booking-form-container">
            <form id="schedspot-booking-form" class="schedspot-form" method="post">
                <?php wp_nonce_field( 'schedspot_booking_form', 'schedspot_booking_nonce' ); ?>
                
                <div class="schedspot-form-section">
                    <h3><?php _e( 'Service Selection', 'schedspot' ); ?></h3>
                    
                    <div class="schedspot-form-row">
                        <label for="schedspot_service_id"><?php _e( 'Select Service', 'schedspot' ); ?> *</label>
                        <select id="schedspot_service_id" name="service_id" required>
                            <option value=""><?php _e( 'Choose a service...', 'schedspot' ); ?></option>
                            <?php
                            $services = SchedSpot_Service::get_all_services();
                            foreach ( $services as $service ) {
                                $selected = ( $atts['service_id'] == $service->id ) ? 'selected' : '';
                                echo '<option value="' . esc_attr( $service->id ) . '" ' . $selected . '>' . esc_html( $service->name ) . ' - $' . esc_html( number_format( $service->price, 2 ) ) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="schedspot-form-section">
                    <h3><?php _e( 'Date & Time', 'schedspot' ); ?></h3>
                    
                    <div class="schedspot-form-grid">
                        <div class="schedspot-form-row">
                            <label for="schedspot_booking_date"><?php _e( 'Date', 'schedspot' ); ?> *</label>
                            <input type="date" id="schedspot_booking_date" name="booking_date" required min="<?php echo date( 'Y-m-d' ); ?>">
                        </div>
                        
                        <div class="schedspot-form-row">
                            <label for="schedspot_start_time"><?php _e( 'Start Time', 'schedspot' ); ?> *</label>
                            <input type="time" id="schedspot_start_time" name="start_time" required>
                        </div>
                        
                        <div class="schedspot-form-row">
                            <label for="schedspot_duration"><?php _e( 'Duration (hours)', 'schedspot' ); ?> *</label>
                            <select id="schedspot_duration" name="duration" required>
                                <option value="1">1 hour</option>
                                <option value="2">2 hours</option>
                                <option value="3">3 hours</option>
                                <option value="4">4 hours</option>
                                <option value="6">6 hours</option>
                                <option value="8">8 hours</option>
                            </select>
                        </div>
                    </div>
                </div>

                <?php if ( $atts['show_worker_selection'] === 'true' ) : ?>
                <div class="schedspot-form-section">
                    <h3><?php _e( 'Worker Selection', 'schedspot' ); ?></h3>
                    
                    <div class="worker-selection-mode">
                        <label>
                            <input type="radio" name="worker_selection_mode" value="auto" checked>
                            <?php _e( 'Auto-assign best available worker', 'schedspot' ); ?>
                        </label>
                        <label>
                            <input type="radio" name="worker_selection_mode" value="manual">
                            <?php _e( 'Let me choose a specific worker', 'schedspot' ); ?>
                        </label>
                    </div>
                    
                    <div id="manual-worker-selection" style="display: none;">
                        <div id="available-workers-list">
                            <!-- Workers will be loaded via AJAX -->
                        </div>
                        <input type="hidden" id="schedspot_worker_id" name="worker_id" value="<?php echo esc_attr( $atts['worker_id'] ); ?>">
                    </div>
                </div>
                <?php endif; ?>

                <div class="schedspot-form-section">
                    <h3><?php _e( 'Your Information', 'schedspot' ); ?></h3>
                    
                    <div class="schedspot-form-grid">
                        <div class="schedspot-form-row">
                            <label for="schedspot_client_name"><?php _e( 'Full Name', 'schedspot' ); ?> *</label>
                            <input type="text" id="schedspot_client_name" name="client_name" required>
                        </div>
                        
                        <div class="schedspot-form-row">
                            <label for="schedspot_client_email"><?php _e( 'Email Address', 'schedspot' ); ?> *</label>
                            <input type="email" id="schedspot_client_email" name="client_email" required>
                        </div>
                        
                        <div class="schedspot-form-row">
                            <label for="schedspot_client_phone"><?php _e( 'Phone Number', 'schedspot' ); ?></label>
                            <input type="tel" id="schedspot_client_phone" name="client_phone">
                        </div>
                    </div>
                    
                    <div class="schedspot-form-row">
                        <label for="schedspot_client_address"><?php _e( 'Service Address', 'schedspot' ); ?> *</label>
                        <textarea id="schedspot_client_address" name="client_address" rows="3" required placeholder="<?php _e( 'Enter the address where the service should be performed...', 'schedspot' ); ?>"></textarea>
                        <?php if ( get_option( 'schedspot_enable_geofencing', false ) ) : ?>
                            <button type="button" class="schedspot-get-location">
                                <span class="dashicons dashicons-location"></span>
                                <?php _e( 'Use My Current Location', 'schedspot' ); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="schedspot-form-row">
                        <label for="schedspot_special_instructions"><?php _e( 'Special Instructions', 'schedspot' ); ?></label>
                        <textarea id="schedspot_special_instructions" name="special_instructions" rows="3" placeholder="<?php _e( 'Any special requirements or instructions for the worker...', 'schedspot' ); ?>"></textarea>
                    </div>
                </div>

                <?php if ( $atts['show_payment_info'] === 'true' ) : ?>
                <div class="schedspot-form-section">
                    <h3><?php _e( 'Payment Information', 'schedspot' ); ?></h3>
                    
                    <div class="schedspot-payment-info">
                        <div class="payment-details">
                            <p><strong><?php _e( 'How Payment Works:', 'schedspot' ); ?></strong></p>
                            <p><?php _e( 'You will be charged after the service is completed. We accept all major credit cards and PayPal.', 'schedspot' ); ?></p>
                            
                            <?php if ( get_option( 'schedspot_require_deposit', false ) ) : ?>
                                <p><strong><?php _e( 'Deposit Required:', 'schedspot' ); ?></strong> 
                                   <?php echo esc_html( get_option( 'schedspot_deposit_percentage', 25 ) ); ?>% 
                                   <?php _e( 'of the total cost will be charged as a deposit when you book.', 'schedspot' ); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="payment-methods">
                            <p><strong><?php _e( 'Accepted Payment Methods:', 'schedspot' ); ?></strong></p>
                            <div class="payment-icons">
                                <span class="payment-method">Visa</span>
                                <span class="payment-method">Mastercard</span>
                                <span class="payment-method">PayPal</span>
                                <span class="payment-method">Apple Pay</span>
                            </div>
                        </div>
                        
                        <div class="security-notice">
                            <span class="dashicons dashicons-lock"></span>
                            <?php _e( 'Your payment information is secure and encrypted.', 'schedspot' ); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( get_option( 'schedspot_enable_geofencing', false ) ) : ?>
                <div class="schedspot-form-section">
                    <h3><?php _e( 'Service Location', 'schedspot' ); ?></h3>
                    <div id="schedspot-booking-map"></div>
                    <input type="hidden" id="schedspot-client-lat" name="client_lat">
                    <input type="hidden" id="schedspot-client-lng" name="client_lng">
                    <div id="schedspot-nearby-workers"></div>
                </div>
                <?php endif; ?>

                <div class="schedspot-form-actions">
                    <button type="submit" class="schedspot-btn schedspot-btn-primary">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php _e( 'Book Service', 'schedspot' ); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle booking form submission.
     *
     * @since 1.0.0
     */
    public function handle_booking_submission() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['schedspot_booking_nonce'], 'schedspot_booking_form' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        // Sanitize and validate input
        $booking_data = array(
            'service_id' => absint( $_POST['service_id'] ),
            'worker_id' => absint( $_POST['worker_id'] ),
            'booking_date' => sanitize_text_field( $_POST['booking_date'] ),
            'start_time' => sanitize_text_field( $_POST['start_time'] ),
            'duration' => absint( $_POST['duration'] ),
            'client_name' => sanitize_text_field( $_POST['client_name'] ),
            'client_email' => sanitize_email( $_POST['client_email'] ),
            'client_phone' => sanitize_text_field( $_POST['client_phone'] ),
            'client_address' => sanitize_textarea_field( $_POST['client_address'] ),
            'special_instructions' => sanitize_textarea_field( $_POST['special_instructions'] ),
            'client_lat' => floatval( $_POST['client_lat'] ),
            'client_lng' => floatval( $_POST['client_lng'] ),
        );

        // Validate required fields
        $required_fields = array( 'service_id', 'booking_date', 'start_time', 'duration', 'client_name', 'client_email', 'client_address' );
        foreach ( $required_fields as $field ) {
            if ( empty( $booking_data[ $field ] ) ) {
                wp_send_json_error( array( 'message' => sprintf( __( '%s is required.', 'schedspot' ), ucfirst( str_replace( '_', ' ', $field ) ) ) ) );
            }
        }

        // Create booking
        $booking = new SchedSpot_Booking();
        $booking_id = $booking->create( $booking_data );

        if ( $booking_id ) {
            // Send confirmation email
            $this->send_booking_confirmation( $booking_id );
            
            wp_send_json_success( array(
                'message' => __( 'Booking created successfully!', 'schedspot' ),
                'booking_id' => $booking_id,
                'redirect_url' => $this->get_redirect_url( $booking_id )
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to create booking. Please try again.', 'schedspot' ) ) );
        }
    }

    /**
     * Get available workers for a service.
     *
     * @since 1.0.0
     */
    public function get_available_workers() {
        $service_id = absint( $_GET['service_id'] );
        $date = sanitize_text_field( $_GET['date'] );
        $time = sanitize_text_field( $_GET['time'] );

        if ( ! $service_id || ! $date || ! $time ) {
            wp_send_json_error( array( 'message' => __( 'Missing required parameters.', 'schedspot' ) ) );
        }

        // Get workers for this service
        $workers = SchedSpot_Worker::get_available_workers( $service_id, $date, $time );

        if ( empty( $workers ) ) {
            wp_send_json_error( array( 'message' => __( 'No workers available for this service and time.', 'schedspot' ) ) );
        }

        wp_send_json_success( $workers );
    }

    /**
     * Send booking confirmation email.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     */
    private function send_booking_confirmation( $booking_id ) {
        $booking = new SchedSpot_Booking( $booking_id );
        
        if ( ! $booking->id ) {
            return;
        }

        $to = $booking->client_email;
        $subject = sprintf( __( 'Booking Confirmation - %s', 'schedspot' ), get_bloginfo( 'name' ) );
        
        $message = sprintf(
            __( 'Dear %s,

Your booking has been confirmed!

Booking Details:
- Service: %s
- Date: %s
- Time: %s
- Duration: %s hours
- Worker: %s

We will send you a reminder before your appointment.

Thank you for choosing %s!', 'schedspot' ),
            $booking->client_name,
            $booking->get_service_name(),
            date( 'F j, Y', strtotime( $booking->booking_date ) ),
            $booking->start_time,
            $booking->duration,
            $booking->get_worker_name(),
            get_bloginfo( 'name' )
        );

        wp_mail( $to, $subject, $message );
    }

    /**
     * Get redirect URL after booking.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     * @return string Redirect URL.
     */
    private function get_redirect_url( $booking_id ) {
        // Default to current page with success parameter
        $redirect_url = add_query_arg( array(
            'booking_success' => 1,
            'booking_id' => $booking_id
        ), wp_get_referer() );

        return apply_filters( 'schedspot_booking_redirect_url', $redirect_url, $booking_id );
    }
}
