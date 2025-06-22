<?php
/**
 * SMS Integration Class
 *
 * @package SchedSpot
 * @version 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_SMS Class.
 *
 * @class SchedSpot_SMS
 * @version 2.0.0
 */
class SchedSpot_SMS {

    /**
     * SMS provider.
     *
     * @var string
     */
    private $provider = 'twilio';

    /**
     * Twilio API credentials.
     *
     * @var array
     */
    private $twilio_config = array();

    /**
     * Constructor.
     *
     * @since 2.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize SMS integration.
     *
     * @since 2.0.0
     */
    public function init() {
        // Check if SMS integration is enabled
        if ( ! $this->is_enabled() ) {
            return;
        }

        // Load configuration
        $this->load_config();

        // Initialize hooks
        add_action( 'schedspot_booking_status_changed', array( $this, 'send_booking_notification' ), 10, 3 );
        add_action( 'schedspot_payment_completed', array( $this, 'send_payment_confirmation' ), 10, 2 );
        add_action( 'wp_ajax_schedspot_send_sms_code', array( $this, 'ajax_send_verification_code' ) );
        add_action( 'wp_ajax_nopriv_schedspot_send_sms_code', array( $this, 'ajax_send_verification_code' ) );
        add_action( 'wp_ajax_schedspot_verify_sms_code', array( $this, 'ajax_verify_code' ) );
        add_action( 'wp_ajax_nopriv_schedspot_verify_sms_code', array( $this, 'ajax_verify_code' ) );
        add_action( 'wp_ajax_schedspot_test_sms', array( $this, 'ajax_test_sms' ) );
        
        // Authentication hooks
        add_filter( 'authenticate', array( $this, 'sms_authenticate' ), 30, 3 );
        add_action( 'wp_login', array( $this, 'handle_login_verification' ), 10, 2 );
        
        // Registration hooks
        add_action( 'user_register', array( $this, 'send_welcome_sms' ) );

        // Cron hooks
        add_action( 'schedspot_sms_daily_reminders', array( $this, 'send_booking_reminders' ) );

        // Schedule daily reminders if not already scheduled
        if ( ! wp_next_scheduled( 'schedspot_sms_daily_reminders' ) ) {
            wp_schedule_event( time(), 'daily', 'schedspot_sms_daily_reminders' );
        }
    }

    /**
     * Check if SMS integration is enabled.
     *
     * @since 2.0.0
     * @return bool True if enabled, false otherwise.
     */
    public function is_enabled() {
        return get_option( 'schedspot_sms_enabled', false );
    }

    /**
     * Load SMS configuration.
     *
     * @since 2.0.0
     */
    private function load_config() {
        $this->provider = get_option( 'schedspot_sms_provider', 'twilio' );
        
        if ( $this->provider === 'twilio' ) {
            $this->twilio_config = array(
                'account_sid' => get_option( 'schedspot_twilio_account_sid', '' ),
                'auth_token'  => get_option( 'schedspot_twilio_auth_token', '' ),
                'from_number' => get_option( 'schedspot_twilio_from_number', '' ),
            );
        }
    }

    /**
     * Send SMS message.
     *
     * @since 2.0.0
     * @param string $to Phone number to send to.
     * @param string $message Message content.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function send_sms( $to, $message ) {
        if ( ! $this->is_enabled() ) {
            return new WP_Error( 'sms_disabled', __( 'SMS integration is disabled.', 'schedspot' ) );
        }

        // Sanitize phone number
        $to = $this->sanitize_phone_number( $to );
        
        if ( ! $to ) {
            return new WP_Error( 'invalid_phone', __( 'Invalid phone number.', 'schedspot' ) );
        }

        // Send via provider
        switch ( $this->provider ) {
            case 'twilio':
                return $this->send_via_twilio( $to, $message );
            default:
                return new WP_Error( 'unsupported_provider', __( 'Unsupported SMS provider.', 'schedspot' ) );
        }
    }

    /**
     * Send SMS via Twilio.
     *
     * @since 2.0.0
     * @param string $to Phone number.
     * @param string $message Message content.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    private function send_via_twilio( $to, $message ) {
        $account_sid = $this->twilio_config['account_sid'];
        $auth_token = $this->twilio_config['auth_token'];
        $from_number = $this->twilio_config['from_number'];

        if ( empty( $account_sid ) || empty( $auth_token ) || empty( $from_number ) ) {
            return new WP_Error( 'twilio_config', __( 'Twilio configuration is incomplete.', 'schedspot' ) );
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
        
        $data = array(
            'From' => $from_number,
            'To'   => $to,
            'Body' => $message,
        );

        $response = wp_remote_post( $url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $account_sid . ':' . $auth_token ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body'    => $data,
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( 'SchedSpot SMS: Twilio request failed - ' . $response->get_error_message() );
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $response_code >= 200 && $response_code < 300 ) {
            do_action( 'schedspot_sms_sent', $to, $message, $data );
            return true;
        } else {
            $error_message = isset( $data['message'] ) ? $data['message'] : __( 'Unknown Twilio error.', 'schedspot' );
            error_log( 'SchedSpot SMS: Twilio error - ' . $error_message );
            return new WP_Error( 'twilio_error', $error_message );
        }
    }

    /**
     * Sanitize phone number.
     *
     * @since 2.0.0
     * @param string $phone Phone number.
     * @return string|false Sanitized phone number or false if invalid.
     */
    private function sanitize_phone_number( $phone ) {
        // Remove all non-digit characters
        $phone = preg_replace( '/[^0-9+]/', '', $phone );
        
        // Add country code if missing
        if ( substr( $phone, 0, 1 ) !== '+' ) {
            $default_country_code = get_option( 'schedspot_sms_default_country_code', '+1' );
            $phone = $default_country_code . ltrim( $phone, '0' );
        }

        // Validate phone number format
        if ( preg_match( '/^\+[1-9]\d{1,14}$/', $phone ) ) {
            return $phone;
        }

        return false;
    }

    /**
     * Generate verification code.
     *
     * @since 2.0.0
     * @param int $user_id User ID.
     * @return string Verification code.
     */
    public function generate_verification_code( $user_id ) {
        $code = wp_rand( 100000, 999999 );
        
        // Store code with expiration
        $expiration = time() + ( 10 * MINUTE_IN_SECONDS ); // 10 minutes
        update_user_meta( $user_id, 'schedspot_sms_verification_code', $code );
        update_user_meta( $user_id, 'schedspot_sms_code_expiration', $expiration );
        
        return $code;
    }

    /**
     * Verify SMS code.
     *
     * @since 2.0.0
     * @param int    $user_id User ID.
     * @param string $code Code to verify.
     * @return bool True if valid, false otherwise.
     */
    public function verify_code( $user_id, $code ) {
        $stored_code = get_user_meta( $user_id, 'schedspot_sms_verification_code', true );
        $expiration = get_user_meta( $user_id, 'schedspot_sms_code_expiration', true );

        if ( empty( $stored_code ) || empty( $expiration ) ) {
            return false;
        }

        if ( time() > $expiration ) {
            // Code expired
            delete_user_meta( $user_id, 'schedspot_sms_verification_code' );
            delete_user_meta( $user_id, 'schedspot_sms_code_expiration' );
            return false;
        }

        if ( $stored_code === $code ) {
            // Code is valid, clean up
            delete_user_meta( $user_id, 'schedspot_sms_verification_code' );
            delete_user_meta( $user_id, 'schedspot_sms_code_expiration' );
            update_user_meta( $user_id, 'schedspot_sms_verified', true );
            return true;
        }

        return false;
    }

    /**
     * Send booking notification SMS.
     *
     * @since 2.0.0
     * @param int    $booking_id Booking ID.
     * @param string $old_status Old status.
     * @param string $new_status New status.
     */
    public function send_booking_notification( $booking_id, $old_status, $new_status ) {
        $booking = new SchedSpot_Booking( $booking_id );
        
        if ( ! $booking->id ) {
            return;
        }

        $client_phone = $booking->client_details['phone'] ?? '';
        $worker = get_userdata( $booking->worker_id );
        $worker_phone = get_user_meta( $booking->worker_id, 'schedspot_phone', true );

        // Send to client
        if ( $client_phone ) {
            $message = $this->get_booking_notification_message( $booking, $new_status, 'client' );
            $this->send_sms( $client_phone, $message );
        }

        // Send to worker
        if ( $worker_phone ) {
            $message = $this->get_booking_notification_message( $booking, $new_status, 'worker' );
            $this->send_sms( $worker_phone, $message );
        }
    }

    /**
     * Get booking notification message.
     *
     * @since 2.0.0
     * @param SchedSpot_Booking $booking Booking object.
     * @param string            $status Booking status.
     * @param string            $recipient Recipient type (client/worker).
     * @return string Message content.
     */
    private function get_booking_notification_message( $booking, $status, $recipient ) {
        $service = new SchedSpot_Service( $booking->service_id );
        $date = date( 'M j, Y', strtotime( $booking->booking_date ) );
        $time = date( 'g:i A', strtotime( $booking->start_time ) );

        switch ( $status ) {
            case 'confirmed':
                if ( $recipient === 'client' ) {
                    return sprintf(
                        __( 'Your %s booking for %s at %s has been confirmed. Booking ID: %d', 'schedspot' ),
                        $service->name,
                        $date,
                        $time,
                        $booking->id
                    );
                } else {
                    return sprintf(
                        __( 'New booking: %s on %s at %s. Client: %s. Booking ID: %d', 'schedspot' ),
                        $service->name,
                        $date,
                        $time,
                        $booking->client_details['name'],
                        $booking->id
                    );
                }
                break;
                
            case 'cancelled':
                return sprintf(
                    __( 'Booking cancelled: %s on %s at %s. Booking ID: %d', 'schedspot' ),
                    $service->name,
                    $date,
                    $time,
                    $booking->id
                );
                break;
                
            case 'completed':
                if ( $recipient === 'client' ) {
                    return sprintf(
                        __( 'Service completed: %s. Thank you for choosing us! Booking ID: %d', 'schedspot' ),
                        $service->name,
                        $booking->id
                    );
                } else {
                    return sprintf(
                        __( 'Service marked as completed: %s. Booking ID: %d', 'schedspot' ),
                        $service->name,
                        $booking->id
                    );
                }
                break;
                
            default:
                return sprintf(
                    __( 'Booking update: %s status changed to %s. Booking ID: %d', 'schedspot' ),
                    $service->name,
                    $status,
                    $booking->id
                );
        }
    }

    /**
     * Send payment confirmation SMS.
     *
     * @since 2.0.0
     * @param int $booking_id Booking ID.
     * @param int $order_id Order ID.
     */
    public function send_payment_confirmation( $booking_id, $order_id ) {
        $booking = new SchedSpot_Booking( $booking_id );

        if ( ! $booking->id ) {
            return;
        }

        $client_phone = $booking->client_details['phone'] ?? '';

        if ( $client_phone ) {
            $service = new SchedSpot_Service( $booking->service_id );
            $message = sprintf(
                __( 'Payment confirmed for %s booking. Amount: $%.2f. Booking ID: %d', 'schedspot' ),
                $service->name,
                $booking->total_cost,
                $booking->id
            );

            $this->send_sms( $client_phone, $message );
        }
    }

    /**
     * Send welcome SMS to new user.
     *
     * @since 2.0.0
     * @param int $user_id User ID.
     */
    public function send_welcome_sms( $user_id ) {
        $phone = get_user_meta( $user_id, 'schedspot_phone', true );

        if ( $phone ) {
            $user = get_userdata( $user_id );
            $message = sprintf(
                __( 'Welcome to %s, %s! Your account has been created successfully.', 'schedspot' ),
                get_bloginfo( 'name' ),
                $user->display_name
            );

            $this->send_sms( $phone, $message );
        }
    }

    /**
     * AJAX handler for sending verification code.
     *
     * @since 2.0.0
     */
    public function ajax_send_verification_code() {
        check_ajax_referer( 'schedspot_sms_nonce', 'nonce' );

        $phone = sanitize_text_field( $_POST['phone'] ?? '' );

        if ( ! $phone ) {
            wp_send_json_error( array( 'message' => __( 'Phone number is required.', 'schedspot' ) ) );
        }

        // Find user by phone
        $users = get_users( array(
            'meta_key'   => 'schedspot_phone',
            'meta_value' => $phone,
            'number'     => 1,
        ) );

        if ( empty( $users ) ) {
            wp_send_json_error( array( 'message' => __( 'No account found with this phone number.', 'schedspot' ) ) );
        }

        $user = $users[0];
        $code = $this->generate_verification_code( $user->ID );
        $message = sprintf(
            __( 'Your verification code for %s: %s', 'schedspot' ),
            get_bloginfo( 'name' ),
            $code
        );

        $result = $this->send_sms( $phone, $message );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => __( 'Verification code sent successfully.', 'schedspot' ) ) );
    }

    /**
     * AJAX handler for verifying SMS code.
     *
     * @since 2.0.0
     */
    public function ajax_verify_code() {
        check_ajax_referer( 'schedspot_sms_nonce', 'nonce' );

        $phone = sanitize_text_field( $_POST['phone'] ?? '' );
        $code = sanitize_text_field( $_POST['code'] ?? '' );

        if ( ! $phone || ! $code ) {
            wp_send_json_error( array( 'message' => __( 'Phone number and code are required.', 'schedspot' ) ) );
        }

        // Find user by phone
        $users = get_users( array(
            'meta_key'   => 'schedspot_phone',
            'meta_value' => $phone,
            'number'     => 1,
        ) );

        if ( empty( $users ) ) {
            wp_send_json_error( array( 'message' => __( 'No account found with this phone number.', 'schedspot' ) ) );
        }

        $user = $users[0];

        if ( $this->verify_code( $user->ID, $code ) ) {
            // Log user in
            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID );

            wp_send_json_success( array(
                'message' => __( 'Verification successful. You are now logged in.', 'schedspot' ),
                'redirect' => home_url()
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Invalid or expired verification code.', 'schedspot' ) ) );
        }
    }

    /**
     * Send reminder SMS for upcoming bookings.
     *
     * @since 2.0.0
     */
    public function send_booking_reminders() {
        global $wpdb;

        // Get bookings for tomorrow
        $tomorrow = date( 'Y-m-d', strtotime( '+1 day' ) );

        $bookings = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}schedspot_bookings
             WHERE booking_date = %s AND status = 'confirmed'",
            $tomorrow
        ) );

        foreach ( $bookings as $booking_data ) {
            $booking = new SchedSpot_Booking( $booking_data->id );
            $client_phone = $booking->client_details['phone'] ?? '';

            if ( $client_phone ) {
                $service = new SchedSpot_Service( $booking->service_id );
                $time = date( 'g:i A', strtotime( $booking->start_time ) );

                $message = sprintf(
                    __( 'Reminder: You have a %s appointment tomorrow at %s. Booking ID: %d', 'schedspot' ),
                    $service->name,
                    $time,
                    $booking->id
                );

                $this->send_sms( $client_phone, $message );
            }
        }
    }

    /**
     * SMS authentication filter.
     *
     * @since 2.0.0
     * @param WP_User|WP_Error|null $user User object or error.
     * @param string                $username Username.
     * @param string                $password Password.
     * @return WP_User|WP_Error|null User object or error.
     */
    public function sms_authenticate( $user, $username, $password ) {
        // Only proceed if SMS 2FA is enabled
        if ( ! get_option( 'schedspot_sms_2fa_enabled', false ) ) {
            return $user;
        }

        // Skip if already an error or no user
        if ( is_wp_error( $user ) || ! $user ) {
            return $user;
        }

        // Check if user has SMS verification enabled
        $sms_enabled = get_user_meta( $user->ID, 'schedspot_sms_2fa_enabled', true );

        if ( ! $sms_enabled ) {
            return $user;
        }

        // Check if already verified in this session
        if ( isset( $_SESSION['schedspot_sms_verified'] ) && $_SESSION['schedspot_sms_verified'] === $user->ID ) {
            return $user;
        }

        // Generate and send verification code
        $phone = get_user_meta( $user->ID, 'schedspot_phone', true );

        if ( ! $phone ) {
            return new WP_Error( 'sms_no_phone', __( 'No phone number found for SMS verification.', 'schedspot' ) );
        }

        $code = $this->generate_verification_code( $user->ID );
        $message = sprintf(
            __( 'Your login verification code for %s: %s', 'schedspot' ),
            get_bloginfo( 'name' ),
            $code
        );

        $result = $this->send_sms( $phone, $message );

        if ( is_wp_error( $result ) ) {
            return new WP_Error( 'sms_send_failed', __( 'Failed to send SMS verification code.', 'schedspot' ) );
        }

        // Store user ID for verification step
        update_user_meta( $user->ID, 'schedspot_sms_pending_login', time() );

        return new WP_Error( 'sms_verification_required', __( 'SMS verification code sent. Please check your phone.', 'schedspot' ) );
    }

    /**
     * Handle login verification.
     *
     * @since 2.0.0
     * @param string  $user_login Username.
     * @param WP_User $user User object.
     */
    public function handle_login_verification( $user_login, $user ) {
        // Clean up pending login meta after successful login
        delete_user_meta( $user->ID, 'schedspot_sms_pending_login' );

        // Mark as verified in session
        if ( ! session_id() ) {
            session_start();
        }
        $_SESSION['schedspot_sms_verified'] = $user->ID;
    }

    /**
     * AJAX handler for testing SMS.
     *
     * @since 2.0.0
     */
    public function ajax_test_sms() {
        check_ajax_referer( 'schedspot_test_sms', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'schedspot' ) ) );
        }

        $phone = sanitize_text_field( $_POST['phone'] ?? '' );

        if ( ! $phone ) {
            wp_send_json_error( array( 'message' => __( 'Phone number is required.', 'schedspot' ) ) );
        }

        $message = sprintf(
            __( 'Test SMS from %s. Your SMS integration is working correctly!', 'schedspot' ),
            get_bloginfo( 'name' )
        );

        $result = $this->send_sms( $phone, $message );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => __( 'Test SMS sent successfully!', 'schedspot' ) ) );
    }
}