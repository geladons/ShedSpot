<?php
/**
 * Google Calendar Integration Class
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_GCal Class.
 *
 * @class SchedSpot_GCal
 * @version 1.0.0
 */
class SchedSpot_GCal {

    /**
     * Google Calendar API base URL.
     *
     * @var string
     */
    private $api_base_url = 'https://www.googleapis.com/calendar/v3';

    /**
     * Google OAuth 2.0 URLs.
     *
     * @var array
     */
    private $oauth_urls = array(
        'auth'  => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token' => 'https://oauth2.googleapis.com/token',
    );

    /**
     * Required OAuth scopes.
     *
     * @var array
     */
    private $scopes = array(
        'https://www.googleapis.com/auth/calendar',
        'https://www.googleapis.com/auth/calendar.events',
    );

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize Google Calendar integration.
     *
     * @since 1.0.0
     */
    public function init() {
        // Check if Google Calendar integration is enabled
        if ( ! $this->is_enabled() ) {
            return;
        }

        // Initialize hooks
        add_action( 'schedspot_payment_completed', array( $this, 'sync_booking_on_payment' ), 10, 2 );
        add_action( 'schedspot_booking_status_changed', array( $this, 'handle_booking_status_change' ), 10, 3 );
        add_action( 'schedspot_booking_updated', array( $this, 'update_calendar_event' ), 10, 2 );
        add_action( 'schedspot_booking_deleted', array( $this, 'delete_calendar_event' ) );
        
        // Admin hooks
        add_action( 'admin_init', array( $this, 'handle_oauth_callback' ) );
        add_action( 'wp_ajax_schedspot_gcal_disconnect', array( $this, 'disconnect_calendar' ) );
        add_action( 'wp_ajax_schedspot_gcal_sync_all', array( $this, 'sync_all_bookings' ) );
    }

    /**
     * Check if Google Calendar integration is enabled.
     *
     * @since 1.0.0
     * @return bool True if enabled, false otherwise.
     */
    public function is_enabled() {
        return get_option( 'schedspot_gcal_enabled', false );
    }

    /**
     * Check if Google Calendar is connected.
     *
     * @since 1.0.0
     * @return bool True if connected, false otherwise.
     */
    public function is_connected() {
        $access_token = get_option( 'schedspot_gcal_access_token' );
        return ! empty( $access_token );
    }

    /**
     * Get Google OAuth authorization URL.
     *
     * @since 1.0.0
     * @return string Authorization URL.
     */
    public function get_auth_url() {
        $client_id = get_option( 'schedspot_gcal_client_id' );
        $redirect_uri = admin_url( 'admin.php?page=schedspot-settings&tab=calendar' );
        
        $params = array(
            'client_id'     => $client_id,
            'redirect_uri'  => $redirect_uri,
            'scope'         => implode( ' ', $this->scopes ),
            'response_type' => 'code',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => wp_create_nonce( 'schedspot_gcal_auth' ),
        );

        return $this->oauth_urls['auth'] . '?' . http_build_query( $params );
    }

    /**
     * Handle OAuth callback.
     *
     * @since 1.0.0
     */
    public function handle_oauth_callback() {
        if ( ! isset( $_GET['code'] ) || ! isset( $_GET['state'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_GET['state'], 'schedspot_gcal_auth' ) ) {
            wp_die( __( 'Invalid authorization request.', 'schedspot' ) );
        }

        $code = sanitize_text_field( $_GET['code'] );
        $tokens = $this->exchange_code_for_tokens( $code );

        if ( $tokens && isset( $tokens['access_token'] ) ) {
            update_option( 'schedspot_gcal_access_token', $tokens['access_token'] );
            
            if ( isset( $tokens['refresh_token'] ) ) {
                update_option( 'schedspot_gcal_refresh_token', $tokens['refresh_token'] );
            }

            // Get user's calendar list
            $calendars = $this->get_calendar_list();
            if ( $calendars ) {
                update_option( 'schedspot_gcal_calendars', $calendars );
            }

            wp_redirect( admin_url( 'admin.php?page=schedspot-settings&tab=calendar&connected=1' ) );
            exit;
        } else {
            wp_redirect( admin_url( 'admin.php?page=schedspot-settings&tab=calendar&error=1' ) );
            exit;
        }
    }

    /**
     * Exchange authorization code for access tokens.
     *
     * @since 1.0.0
     * @param string $code Authorization code.
     * @return array|false Token data or false on failure.
     */
    private function exchange_code_for_tokens( $code ) {
        $client_id = get_option( 'schedspot_gcal_client_id' );
        $client_secret = get_option( 'schedspot_gcal_client_secret' );
        $redirect_uri = admin_url( 'admin.php?page=schedspot-settings&tab=calendar' );

        $data = array(
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $redirect_uri,
        );

        $response = wp_remote_post( $this->oauth_urls['token'], array(
            'body'    => $data,
            'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( 'SchedSpot Google Calendar: Token exchange failed - ' . $response->get_error_message() );
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $tokens = json_decode( $body, true );

        if ( isset( $tokens['error'] ) ) {
            error_log( 'SchedSpot Google Calendar: Token exchange error - ' . $tokens['error'] );
            return false;
        }

        return $tokens;
    }

    /**
     * Refresh access token.
     *
     * @since 1.0.0
     * @return bool True on success, false on failure.
     */
    private function refresh_access_token() {
        $refresh_token = get_option( 'schedspot_gcal_refresh_token' );
        
        if ( ! $refresh_token ) {
            return false;
        }

        $client_id = get_option( 'schedspot_gcal_client_id' );
        $client_secret = get_option( 'schedspot_gcal_client_secret' );

        $data = array(
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token,
            'grant_type'    => 'refresh_token',
        );

        $response = wp_remote_post( $this->oauth_urls['token'], array(
            'body'    => $data,
            'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( 'SchedSpot Google Calendar: Token refresh failed - ' . $response->get_error_message() );
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $tokens = json_decode( $body, true );

        if ( isset( $tokens['access_token'] ) ) {
            update_option( 'schedspot_gcal_access_token', $tokens['access_token'] );
            return true;
        }

        return false;
    }

    /**
     * Make authenticated API request.
     *
     * @since 1.0.0
     * @param string $endpoint API endpoint.
     * @param string $method HTTP method.
     * @param array  $data Request data.
     * @return array|false Response data or false on failure.
     */
    private function make_api_request( $endpoint, $method = 'GET', $data = array() ) {
        $access_token = get_option( 'schedspot_gcal_access_token' );
        
        if ( ! $access_token ) {
            return false;
        }

        $url = $this->api_base_url . $endpoint;
        $headers = array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
        );

        $args = array(
            'method'  => $method,
            'headers' => $headers,
            'timeout' => 30,
        );

        if ( ! empty( $data ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ) ) ) {
            $args['body'] = json_encode( $data );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            error_log( 'SchedSpot Google Calendar: API request failed - ' . $response->get_error_message() );
            return false;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        
        // Handle token expiration
        if ( $response_code === 401 ) {
            if ( $this->refresh_access_token() ) {
                // Retry the request with new token
                $headers['Authorization'] = 'Bearer ' . get_option( 'schedspot_gcal_access_token' );
                $args['headers'] = $headers;
                $response = wp_remote_request( $url, $args );
            } else {
                return false;
            }
        }

        $body = wp_remote_retrieve_body( $response );
        return json_decode( $body, true );
    }

    /**
     * Get user's calendar list.
     *
     * @since 1.0.0
     * @return array|false Calendar list or false on failure.
     */
    public function get_calendar_list() {
        $response = $this->make_api_request( '/users/me/calendarList' );

        if ( $response && isset( $response['items'] ) ) {
            return $response['items'];
        }

        return false;
    }

    /**
     * Sync booking on payment completion.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     * @param int $order_id Order ID.
     */
    public function sync_booking_on_payment( $booking_id, $order_id ) {
        $this->sync_booking( $booking_id );
    }

    /**
     * Handle booking status change.
     *
     * @since 1.0.0
     * @param int    $booking_id Booking ID.
     * @param string $old_status Old status.
     * @param string $new_status New status.
     */
    public function handle_booking_status_change( $booking_id, $old_status, $new_status ) {
        if ( $new_status === 'confirmed' ) {
            $this->sync_booking( $booking_id );
        } elseif ( $new_status === 'cancelled' ) {
            $this->delete_calendar_event( $booking_id );
        }
    }

    /**
     * Sync booking to Google Calendar.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     * @return bool True on success, false on failure.
     */
    public function sync_booking( $booking_id ) {
        if ( ! $this->is_connected() ) {
            return false;
        }

        $booking = new SchedSpot_Booking( $booking_id );

        if ( ! $booking->id || $booking->status !== 'confirmed' ) {
            return false;
        }

        // Check if event already exists
        $event_id = get_post_meta( $booking_id, 'schedspot_gcal_event_id', true );

        if ( $event_id ) {
            return $this->update_calendar_event( $booking_id, array() );
        } else {
            return $this->create_calendar_event( $booking );
        }
    }

    /**
     * Create calendar event for booking.
     *
     * @since 1.0.0
     * @param SchedSpot_Booking $booking Booking object.
     * @return bool True on success, false on failure.
     */
    private function create_calendar_event( $booking ) {
        $calendar_id = get_option( 'schedspot_gcal_calendar_id', 'primary' );
        $service = new SchedSpot_Service( $booking->service_id );
        $worker = get_userdata( $booking->worker_id );
        $client_name = $booking->client_details['name'];

        // Calculate event times
        $start_datetime = $booking->booking_date . 'T' . $booking->start_time;
        $end_time = date( 'H:i:s', strtotime( $booking->start_time ) + ( $booking->duration * 60 ) );
        $end_datetime = $booking->booking_date . 'T' . $end_time;

        // Create event data
        $event_data = array(
            'summary'     => sprintf( __( '%s - %s', 'schedspot' ), $service->name, $client_name ),
            'description' => $this->build_event_description( $booking, $service, $worker ),
            'start'       => array(
                'dateTime' => $start_datetime,
                'timeZone' => get_option( 'timezone_string', 'UTC' ),
            ),
            'end'         => array(
                'dateTime' => $end_datetime,
                'timeZone' => get_option( 'timezone_string', 'UTC' ),
            ),
            'attendees'   => array(
                array(
                    'email' => $booking->client_details['email'],
                    'displayName' => $client_name,
                ),
                array(
                    'email' => $worker->user_email,
                    'displayName' => $worker->display_name,
                ),
            ),
            'reminders'   => array(
                'useDefault' => false,
                'overrides'  => array(
                    array( 'method' => 'email', 'minutes' => 1440 ), // 24 hours
                    array( 'method' => 'popup', 'minutes' => 60 ),   // 1 hour
                ),
            ),
        );

        // Add location if available
        if ( ! empty( $booking->client_details['address'] ) ) {
            $event_data['location'] = $booking->client_details['address'];
        }

        $response = $this->make_api_request( "/calendars/{$calendar_id}/events", 'POST', $event_data );

        if ( $response && isset( $response['id'] ) ) {
            update_post_meta( $booking->id, 'schedspot_gcal_event_id', $response['id'] );
            update_post_meta( $booking->id, 'schedspot_gcal_synced', current_time( 'mysql' ) );

            do_action( 'schedspot_gcal_event_created', $booking->id, $response['id'] );
            return true;
        }

        return false;
    }

    /**
     * Update calendar event.
     *
     * @since 1.0.0
     * @param int   $booking_id Booking ID.
     * @param array $booking_data Updated booking data.
     * @return bool True on success, false on failure.
     */
    public function update_calendar_event( $booking_id, $booking_data ) {
        $event_id = get_post_meta( $booking_id, 'schedspot_gcal_event_id', true );

        if ( ! $event_id ) {
            // Create event if it doesn't exist
            $booking = new SchedSpot_Booking( $booking_id );
            return $this->create_calendar_event( $booking );
        }

        $booking = new SchedSpot_Booking( $booking_id );
        $calendar_id = get_option( 'schedspot_gcal_calendar_id', 'primary' );
        $service = new SchedSpot_Service( $booking->service_id );
        $worker = get_userdata( $booking->worker_id );
        $client_name = $booking->client_details['name'];

        // Calculate event times
        $start_datetime = $booking->booking_date . 'T' . $booking->start_time;
        $end_time = date( 'H:i:s', strtotime( $booking->start_time ) + ( $booking->duration * 60 ) );
        $end_datetime = $booking->booking_date . 'T' . $end_time;

        // Update event data
        $event_data = array(
            'summary'     => sprintf( __( '%s - %s', 'schedspot' ), $service->name, $client_name ),
            'description' => $this->build_event_description( $booking, $service, $worker ),
            'start'       => array(
                'dateTime' => $start_datetime,
                'timeZone' => get_option( 'timezone_string', 'UTC' ),
            ),
            'end'         => array(
                'dateTime' => $end_datetime,
                'timeZone' => get_option( 'timezone_string', 'UTC' ),
            ),
        );

        // Add location if available
        if ( ! empty( $booking->client_details['address'] ) ) {
            $event_data['location'] = $booking->client_details['address'];
        }

        $response = $this->make_api_request( "/calendars/{$calendar_id}/events/{$event_id}", 'PUT', $event_data );

        if ( $response && isset( $response['id'] ) ) {
            update_post_meta( $booking_id, 'schedspot_gcal_synced', current_time( 'mysql' ) );

            do_action( 'schedspot_gcal_event_updated', $booking_id, $event_id );
            return true;
        }

        return false;
    }

    /**
     * Delete calendar event.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     * @return bool True on success, false on failure.
     */
    public function delete_calendar_event( $booking_id ) {
        $event_id = get_post_meta( $booking_id, 'schedspot_gcal_event_id', true );

        if ( ! $event_id ) {
            return false;
        }

        $calendar_id = get_option( 'schedspot_gcal_calendar_id', 'primary' );
        $response = $this->make_api_request( "/calendars/{$calendar_id}/events/{$event_id}", 'DELETE' );

        if ( $response !== false ) {
            delete_post_meta( $booking_id, 'schedspot_gcal_event_id' );
            delete_post_meta( $booking_id, 'schedspot_gcal_synced' );

            do_action( 'schedspot_gcal_event_deleted', $booking_id, $event_id );
            return true;
        }

        return false;
    }

    /**
     * Build event description.
     *
     * @since 1.0.0
     * @param SchedSpot_Booking $booking Booking object.
     * @param SchedSpot_Service $service Service object.
     * @param WP_User           $worker Worker object.
     * @return string Event description.
     */
    private function build_event_description( $booking, $service, $worker ) {
        $description = sprintf( __( 'Service: %s', 'schedspot' ), $service->name ) . "\n";
        $description .= sprintf( __( 'Worker: %s', 'schedspot' ), $worker->display_name ) . "\n";
        $description .= sprintf( __( 'Client: %s', 'schedspot' ), $booking->client_details['name'] ) . "\n";
        $description .= sprintf( __( 'Duration: %d minutes', 'schedspot' ), $booking->duration ) . "\n";

        if ( ! empty( $booking->client_details['phone'] ) ) {
            $description .= sprintf( __( 'Phone: %s', 'schedspot' ), $booking->client_details['phone'] ) . "\n";
        }

        if ( ! empty( $booking->notes ) ) {
            $description .= "\n" . __( 'Notes:', 'schedspot' ) . "\n" . $booking->notes;
        }

        return $description;
    }

    /**
     * Disconnect Google Calendar.
     *
     * @since 1.0.0
     */
    public function disconnect_calendar() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_gcal_disconnect' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        // Clear stored tokens and data
        delete_option( 'schedspot_gcal_access_token' );
        delete_option( 'schedspot_gcal_refresh_token' );
        delete_option( 'schedspot_gcal_calendars' );
        delete_option( 'schedspot_gcal_calendar_id' );

        wp_send_json_success( array( 'message' => __( 'Google Calendar disconnected successfully.', 'schedspot' ) ) );
    }

    /**
     * Sync all confirmed bookings to Google Calendar.
     *
     * @since 1.0.0
     */
    public function sync_all_bookings() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_gcal_sync_all' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        if ( ! $this->is_connected() ) {
            wp_send_json_error( array( 'message' => __( 'Google Calendar is not connected.', 'schedspot' ) ) );
        }

        global $wpdb;

        // Get all confirmed bookings without calendar events
        $bookings = $wpdb->get_results( $wpdb->prepare(
            "SELECT b.id FROM {$wpdb->prefix}schedspot_bookings b
             LEFT JOIN {$wpdb->postmeta} pm ON b.id = pm.post_id AND pm.meta_key = 'schedspot_gcal_event_id'
             WHERE b.status = %s AND pm.meta_value IS NULL
             ORDER BY b.booking_date ASC, b.start_time ASC",
            'confirmed'
        ) );

        $synced_count = 0;
        $failed_count = 0;

        foreach ( $bookings as $booking_row ) {
            if ( $this->sync_booking( $booking_row->id ) ) {
                $synced_count++;
            } else {
                $failed_count++;
            }
        }

        $message = sprintf(
            __( 'Sync completed. %d bookings synced, %d failed.', 'schedspot' ),
            $synced_count,
            $failed_count
        );

        wp_send_json_success( array( 'message' => $message ) );
    }

    /**
     * Get calendar events for date range.
     *
     * @since 1.0.0
     * @param string $start_date Start date (Y-m-d format).
     * @param string $end_date End date (Y-m-d format).
     * @return array|false Events or false on failure.
     */
    public function get_calendar_events( $start_date, $end_date ) {
        if ( ! $this->is_connected() ) {
            return false;
        }

        $calendar_id = get_option( 'schedspot_gcal_calendar_id', 'primary' );
        $start_datetime = $start_date . 'T00:00:00Z';
        $end_datetime = $end_date . 'T23:59:59Z';

        $params = array(
            'timeMin' => $start_datetime,
            'timeMax' => $end_datetime,
            'singleEvents' => 'true',
            'orderBy' => 'startTime',
        );

        $endpoint = "/calendars/{$calendar_id}/events?" . http_build_query( $params );
        $response = $this->make_api_request( $endpoint );

        if ( $response && isset( $response['items'] ) ) {
            return $response['items'];
        }

        return false;
    }

    /**
     * Export booking as ICS file.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     * @return string|false ICS content or false on failure.
     */
    public function export_booking_ics( $booking_id ) {
        $booking = new SchedSpot_Booking( $booking_id );

        if ( ! $booking->id ) {
            return false;
        }

        $service = new SchedSpot_Service( $booking->service_id );
        $worker = get_userdata( $booking->worker_id );

        // Calculate event times
        $start_datetime = date( 'Ymd\THis\Z', strtotime( $booking->booking_date . ' ' . $booking->start_time ) );
        $end_time = strtotime( $booking->start_time ) + ( $booking->duration * 60 );
        $end_datetime = date( 'Ymd\THis\Z', strtotime( $booking->booking_date ) + $end_time );
        $created_datetime = date( 'Ymd\THis\Z', strtotime( $booking->created_at ) );

        $ics_content = "BEGIN:VCALENDAR\r\n";
        $ics_content .= "VERSION:2.0\r\n";
        $ics_content .= "PRODID:-//SchedSpot//SchedSpot Booking//EN\r\n";
        $ics_content .= "BEGIN:VEVENT\r\n";
        $ics_content .= "UID:schedspot-booking-{$booking_id}@" . parse_url( home_url(), PHP_URL_HOST ) . "\r\n";
        $ics_content .= "DTSTAMP:{$created_datetime}\r\n";
        $ics_content .= "DTSTART:{$start_datetime}\r\n";
        $ics_content .= "DTEND:{$end_datetime}\r\n";
        $ics_content .= "SUMMARY:" . $this->escape_ics_text( sprintf( __( '%s - %s', 'schedspot' ), $service->name, $booking->client_details['name'] ) ) . "\r\n";
        $ics_content .= "DESCRIPTION:" . $this->escape_ics_text( $this->build_event_description( $booking, $service, $worker ) ) . "\r\n";

        if ( ! empty( $booking->client_details['address'] ) ) {
            $ics_content .= "LOCATION:" . $this->escape_ics_text( $booking->client_details['address'] ) . "\r\n";
        }

        $ics_content .= "STATUS:CONFIRMED\r\n";
        $ics_content .= "END:VEVENT\r\n";
        $ics_content .= "END:VCALENDAR\r\n";

        return $ics_content;
    }

    /**
     * Escape text for ICS format.
     *
     * @since 1.0.0
     * @param string $text Text to escape.
     * @return string Escaped text.
     */
    private function escape_ics_text( $text ) {
        $text = str_replace( array( "\r\n", "\n", "\r" ), '\\n', $text );
        $text = str_replace( array( ',', ';', '\\' ), array( '\\,', '\\;', '\\\\' ), $text );
        return $text;
    }
}
