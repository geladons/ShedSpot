<?php
/**
 * Booking Model Class
 *
 * @package SchedSpot
 * @version 0.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Booking Class.
 *
 * @class SchedSpot_Booking
 * @version 0.1.0
 */
class SchedSpot_Booking {

    /**
     * Booking ID.
     *
     * @var int
     */
    public $id = 0;

    /**
     * User ID (client).
     *
     * @var int
     */
    public $user_id = 0;

    /**
     * Worker ID.
     *
     * @var int
     */
    public $worker_id = 0;

    /**
     * Service ID.
     *
     * @var int
     */
    public $service_id = 0;

    /**
     * Booking date.
     *
     * @var string
     */
    public $booking_date = '';

    /**
     * Start time.
     *
     * @var string
     */
    public $start_time = '';

    /**
     * End time.
     *
     * @var string
     */
    public $end_time = '';

    /**
     * Duration in minutes.
     *
     * @var int
     */
    public $duration = 60;

    /**
     * Booking status.
     *
     * @var string
     */
    public $status = 'pending';

    /**
     * Total cost.
     *
     * @var float
     */
    public $total_cost = 0.00;

    /**
     * Deposit amount.
     *
     * @var float
     */
    public $deposit_amount = 0.00;

    /**
     * Commission amount.
     *
     * @var float
     */
    public $commission_amount = 0.00;

    /**
     * Client details.
     *
     * @var array
     */
    public $client_details = array();

    /**
     * Notes.
     *
     * @var string
     */
    public $notes = '';

    /**
     * Created at.
     *
     * @var string
     */
    public $created_at = '';

    /**
     * Updated at.
     *
     * @var string
     */
    public $updated_at = '';

    /**
     * Service name (cached).
     *
     * @var string
     */
    public $service_name = '';

    /**
     * Constructor.
     *
     * @since 0.1.0
     * @param int|object $booking Booking ID or booking object.
     */
    public function __construct( $booking = 0 ) {
        if ( is_numeric( $booking ) && $booking > 0 ) {
            $this->id = absint( $booking );
            $this->get_booking( $this->id );
        } elseif ( is_object( $booking ) ) {
            $this->init( $booking );
        }
    }

    /**
     * Initialize booking from object.
     *
     * @since 0.1.0
     * @param object $booking Booking object.
     */
    private function init( $booking ) {
        $this->id                = absint( $booking->id );
        $this->user_id           = absint( $booking->user_id );
        $this->worker_id         = absint( $booking->worker_id );
        $this->service_id        = absint( $booking->service_id );
        $this->booking_date      = $booking->booking_date;
        $this->start_time        = $booking->start_time;
        $this->end_time          = $booking->end_time;
        $this->duration          = absint( $booking->duration );
        $this->status            = $booking->status;
        $this->total_cost        = floatval( $booking->total_cost );
        $this->deposit_amount    = floatval( $booking->deposit_amount );
        $this->commission_amount = floatval( $booking->commission_amount );
        $this->notes             = $booking->notes;
        $this->created_at        = $booking->created_at;
        $this->updated_at        = $booking->updated_at;

        // Set client details
        $this->client_details = array(
            'name'    => $booking->client_name,
            'email'   => $booking->client_email,
            'phone'   => $booking->client_phone,
            'address' => $booking->client_address,
            'lat'     => $booking->client_lat,
            'lng'     => $booking->client_lng,
        );

        // Get service name if service_id is set
        if ( $this->service_id ) {
            $this->service_name = $this->get_service_name();
        }
    }

    /**
     * Get booking from database.
     *
     * @since 0.1.0
     * @param int $id Booking ID.
     * @return bool True if booking found, false otherwise.
     */
    private function get_booking( $id ) {
        global $wpdb;

        $booking = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}schedspot_bookings WHERE id = %d",
                $id
            )
        );

        if ( $booking ) {
            $this->init( $booking );
            return true;
        }

        return false;
    }

    /**
     * Create a new booking.
     *
     * @since 0.1.0
     * @param array $data Booking data.
     * @return int|WP_Error Booking ID on success, WP_Error on failure.
     */
    public static function create_booking( $data ) {
        global $wpdb;

        // Validate required fields (user_id can be 0 for guest bookings)
        $required_fields = array( 'worker_id', 'booking_date', 'start_time', 'end_time', 'client_name', 'client_email' );
        foreach ( $required_fields as $field ) {
            if ( empty( $data[ $field ] ) ) {
                return new WP_Error( 'missing_field', sprintf( __( 'Missing required field: %s', 'schedspot' ), $field ) );
            }
        }

        // For guest bookings, user_id can be 0
        if ( ! isset( $data['user_id'] ) ) {
            $data['user_id'] = 0;
        }

        // Check for booking conflicts
        $conflict = self::check_booking_conflict( $data['worker_id'], $data['booking_date'], $data['start_time'], $data['end_time'] );
        if ( $conflict ) {
            return new WP_Error( 'booking_conflict', __( 'Worker is not available at the requested time.', 'schedspot' ) );
        }

        // Prepare data for insertion
        $insert_data = array(
            'user_id'           => absint( $data['user_id'] ),
            'worker_id'         => absint( $data['worker_id'] ),
            'service_id'        => isset( $data['service_id'] ) ? absint( $data['service_id'] ) : null,
            'booking_date'      => sanitize_text_field( $data['booking_date'] ),
            'start_time'        => sanitize_text_field( $data['start_time'] ),
            'end_time'          => sanitize_text_field( $data['end_time'] ),
            'duration'          => isset( $data['duration'] ) ? absint( $data['duration'] ) : 60,
            'status'            => isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'pending',
            'total_cost'        => isset( $data['total_cost'] ) ? floatval( $data['total_cost'] ) : 0.00,
            'deposit_amount'    => isset( $data['deposit_amount'] ) ? floatval( $data['deposit_amount'] ) : 0.00,
            'commission_amount' => isset( $data['commission_amount'] ) ? floatval( $data['commission_amount'] ) : 0.00,
            'client_name'       => sanitize_text_field( $data['client_name'] ),
            'client_email'      => sanitize_email( $data['client_email'] ),
            'client_phone'      => isset( $data['client_phone'] ) ? sanitize_text_field( $data['client_phone'] ) : null,
            'client_address'    => isset( $data['client_address'] ) ? sanitize_textarea_field( $data['client_address'] ) : null,
            'client_lat'        => isset( $data['client_lat'] ) ? floatval( $data['client_lat'] ) : null,
            'client_lng'        => isset( $data['client_lng'] ) ? floatval( $data['client_lng'] ) : null,
            'notes'             => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : null,
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'schedspot_bookings',
            $insert_data,
            array(
                '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%f', '%f', '%f',
                '%s', '%s', '%s', '%s', '%f', '%f', '%s'
            )
        );

        if ( false === $result ) {
            return new WP_Error( 'db_error', __( 'Failed to create booking.', 'schedspot' ) );
        }

        $booking_id = $wpdb->insert_id;

        // Fire action hook
        do_action( 'schedspot_booking_created', $booking_id, $data );

        return $booking_id;
    }

    /**
     * Update booking.
     *
     * @since 0.1.0
     * @param array $data Booking data to update.
     * @return bool True on success, false on failure.
     */
    public function update( $data ) {
        global $wpdb;

        if ( ! $this->id ) {
            return false;
        }

        $update_data = array();
        $format = array();

        // Define allowed fields and their formats
        $allowed_fields = array(
            'worker_id'         => '%d',
            'service_id'        => '%d',
            'booking_date'      => '%s',
            'start_time'        => '%s',
            'end_time'          => '%s',
            'duration'          => '%d',
            'status'            => '%s',
            'total_cost'        => '%f',
            'deposit_amount'    => '%f',
            'commission_amount' => '%f',
            'client_name'       => '%s',
            'client_email'      => '%s',
            'client_phone'      => '%s',
            'client_address'    => '%s',
            'client_lat'        => '%f',
            'client_lng'        => '%f',
            'notes'             => '%s',
        );

        foreach ( $data as $key => $value ) {
            if ( isset( $allowed_fields[ $key ] ) ) {
                $update_data[ $key ] = $value;
                $format[] = $allowed_fields[ $key ];
            }
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'schedspot_bookings',
            $update_data,
            array( 'id' => $this->id ),
            $format,
            array( '%d' )
        );

        if ( false !== $result ) {
            // Check if status changed
            $old_status = $this->status;

            // Refresh object data
            $this->get_booking( $this->id );

            // Fire status change hook if status changed
            if ( isset( $data['status'] ) && $old_status !== $data['status'] ) {
                do_action( 'schedspot_booking_status_changed', $this->id, $old_status, $data['status'] );
            }

            // Fire general update hook
            do_action( 'schedspot_booking_updated', $this->id, $data );

            return true;
        }

        return false;
    }

    /**
     * Delete booking.
     *
     * @since 0.1.0
     * @return bool True on success, false on failure.
     */
    public function delete() {
        global $wpdb;

        if ( ! $this->id ) {
            return false;
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'schedspot_bookings',
            array( 'id' => $this->id ),
            array( '%d' )
        );

        if ( false !== $result ) {
            // Fire action hook
            do_action( 'schedspot_booking_deleted', $this->id );

            return true;
        }

        return false;
    }

    /**
     * Check for booking conflicts.
     *
     * @since 0.1.0
     * @param int    $worker_id Worker ID.
     * @param string $date Booking date.
     * @param string $start_time Start time.
     * @param string $end_time End time.
     * @param int    $exclude_booking_id Booking ID to exclude from conflict check.
     * @return bool True if conflict exists, false otherwise.
     */
    public static function check_booking_conflict( $worker_id, $date, $start_time, $end_time, $exclude_booking_id = 0 ) {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings 
                WHERE worker_id = %d 
                AND booking_date = %s 
                AND status NOT IN ('cancelled', 'completed')
                AND (
                    (start_time < %s AND end_time > %s) OR
                    (start_time < %s AND end_time > %s) OR
                    (start_time >= %s AND end_time <= %s)
                )";

        $params = array(
            $worker_id,
            $date,
            $end_time,
            $start_time,
            $start_time,
            $start_time,
            $start_time,
            $end_time
        );

        if ( $exclude_booking_id > 0 ) {
            $sql .= " AND id != %d";
            $params[] = $exclude_booking_id;
        }

        $conflict_count = $wpdb->get_var( $wpdb->prepare( $sql, $params ) );

        return $conflict_count > 0;
    }

    /**
     * Get bookings by criteria.
     *
     * @since 0.1.0
     * @param array $args Query arguments.
     * @return array Array of booking objects.
     */
    public static function get_bookings( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'user_id'    => 0,
            'worker_id'  => 0,
            'service_id' => 0,
            'status'     => '',
            'date_from'  => '',
            'date_to'    => '',
            'limit'      => 20,
            'offset'     => 0,
            'orderby'    => 'booking_date',
            'order'      => 'ASC',
        );

        $args = wp_parse_args( $args, $defaults );

        $where_clauses = array();
        $params = array();

        if ( $args['user_id'] > 0 ) {
            $where_clauses[] = 'user_id = %d';
            $params[] = $args['user_id'];
        }

        if ( $args['worker_id'] > 0 ) {
            $where_clauses[] = 'worker_id = %d';
            $params[] = $args['worker_id'];
        }

        if ( $args['service_id'] > 0 ) {
            $where_clauses[] = 'service_id = %d';
            $params[] = $args['service_id'];
        }

        if ( ! empty( $args['status'] ) ) {
            $where_clauses[] = 'status = %s';
            $params[] = $args['status'];
        }

        if ( ! empty( $args['date_from'] ) ) {
            $where_clauses[] = 'booking_date >= %s';
            $params[] = $args['date_from'];
        }

        if ( ! empty( $args['date_to'] ) ) {
            $where_clauses[] = 'booking_date <= %s';
            $params[] = $args['date_to'];
        }

        $where_sql = '';
        if ( ! empty( $where_clauses ) ) {
            $where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
        }

        $order_sql = sprintf( 'ORDER BY %s %s', esc_sql( $args['orderby'] ), esc_sql( $args['order'] ) );
        $limit_sql = sprintf( 'LIMIT %d OFFSET %d', absint( $args['limit'] ), absint( $args['offset'] ) );

        $sql = "SELECT * FROM {$wpdb->prefix}schedspot_bookings {$where_sql} {$order_sql} {$limit_sql}";

        if ( ! empty( $params ) ) {
            $sql = $wpdb->prepare( $sql, $params );
        }

        $results = $wpdb->get_results( $sql );

        $bookings = array();
        foreach ( $results as $booking_data ) {
            $bookings[] = new self( $booking_data );
        }

        return $bookings;
    }

    /**
     * Get booking statuses.
     *
     * @since 0.1.0
     * @return array Array of booking statuses.
     */
    public static function get_booking_statuses() {
        return apply_filters( 'schedspot_booking_statuses', array(
            'pending'    => __( 'Pending', 'schedspot' ),
            'confirmed'  => __( 'Confirmed', 'schedspot' ),
            'in_progress' => __( 'In Progress', 'schedspot' ),
            'completed'  => __( 'Completed', 'schedspot' ),
            'cancelled'  => __( 'Cancelled', 'schedspot' ),
        ) );
    }

    /**
     * Get service name for this booking.
     *
     * @since 1.0.0
     * @return string Service name or empty string.
     */
    public function get_service_name() {
        if ( ! $this->service_id ) {
            return '';
        }

        global $wpdb;
        $service_name = $wpdb->get_var( $wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}schedspot_services WHERE id = %d",
            $this->service_id
        ) );

        return $service_name ? $service_name : '';
    }

    /**
     * Get worker name for this booking.
     *
     * @since 1.0.0
     * @return string Worker name or empty string.
     */
    public function get_worker_name() {
        if ( ! $this->worker_id ) {
            return '';
        }

        $worker = get_userdata( $this->worker_id );
        return $worker ? $worker->display_name : '';
    }

    /**
     * Get client name for this booking.
     *
     * @since 1.0.0
     * @return string Client name.
     */
    public function get_client_name() {
        return isset( $this->client_details['name'] ) ? $this->client_details['name'] : '';
    }
}
