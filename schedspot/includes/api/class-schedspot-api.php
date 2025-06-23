<?php
/**
 * REST API Class
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_API Class.
 *
 * @class SchedSpot_API
 * @version 1.0.0
 */
class SchedSpot_API {

    /**
     * API namespace.
     *
     * @var string
     */
    private $namespace = 'schedspot/v1';

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize API functionality.
     *
     * @since 1.0.0
     */
    public function init() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST API routes.
     *
     * @since 1.0.0
     */
    public function register_routes() {
        // Bookings endpoints
        register_rest_route( $this->namespace, '/bookings', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_bookings' ),
                'permission_callback' => array( $this, 'check_bookings_permissions' ),
                'args'                => $this->get_bookings_args(),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_booking' ),
                'permission_callback' => array( $this, 'check_create_booking_permissions' ),
                'args'                => $this->get_create_booking_args(),
            ),
        ) );

        register_rest_route( $this->namespace, '/bookings/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_booking' ),
                'permission_callback' => array( $this, 'check_booking_permissions' ),
                'args'                => array(
                    'id' => array(
                        'validate_callback' => function( $param ) {
                            return is_numeric( $param );
                        }
                    ),
                ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_booking' ),
                'permission_callback' => array( $this, 'check_booking_permissions' ),
                'args'                => $this->get_update_booking_args(),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_booking' ),
                'permission_callback' => array( $this, 'check_booking_permissions' ),
            ),
        ) );

        // Services endpoints
        register_rest_route( $this->namespace, '/services', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_services' ),
                'permission_callback' => '__return_true',
                'args'                => $this->get_services_args(),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_service' ),
                'permission_callback' => array( $this, 'check_admin_permissions' ),
                'args'                => $this->get_create_service_args(),
            ),
        ) );

        register_rest_route( $this->namespace, '/services/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_service' ),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_service' ),
                'permission_callback' => array( $this, 'check_admin_permissions' ),
                'args'                => $this->get_update_service_args(),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_service' ),
                'permission_callback' => array( $this, 'check_admin_permissions' ),
            ),
        ) );

        // Workers endpoints
        register_rest_route( $this->namespace, '/workers', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_workers' ),
                'permission_callback' => '__return_true',
                'args'                => $this->get_workers_args(),
            ),
        ) );

        register_rest_route( $this->namespace, '/workers/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_worker' ),
                'permission_callback' => '__return_true',
            ),
        ) );

        // Availability endpoints
        register_rest_route( $this->namespace, '/availability/check', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'check_availability' ),
            'permission_callback' => '__return_true',
            'args'                => $this->get_availability_args(),
        ) );

        register_rest_route( $this->namespace, '/workers/(?P<id>\d+)/availability', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_worker_availability' ),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_worker_availability' ),
                'permission_callback' => array( $this, 'check_worker_permissions' ),
                'args'                => $this->get_availability_update_args(),
            ),
        ) );

        // Worker profile endpoints
        register_rest_route( $this->namespace, '/workers/(?P<id>\d+)/profile', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_worker_profile' ),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_worker_profile' ),
                'permission_callback' => array( $this, 'check_worker_permissions' ),
                'args'                => $this->get_worker_profile_args(),
            ),
        ) );

        // Worker statistics endpoint
        register_rest_route( $this->namespace, '/workers/(?P<id>\d+)/statistics', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_worker_statistics' ),
            'permission_callback' => array( $this, 'check_worker_permissions' ),
        ) );

        // Worker services endpoint
        register_rest_route( $this->namespace, '/workers/(?P<id>\d+)/services', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_worker_services' ),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_worker_services' ),
                'permission_callback' => array( $this, 'check_worker_permissions' ),
                'args'                => $this->get_worker_services_args(),
            ),
        ) );

        // Payment endpoints
        register_rest_route( $this->namespace, '/payments/create-order', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'create_payment_order' ),
            'permission_callback' => array( $this, 'check_create_booking_permissions' ),
            'args'                => $this->get_create_order_args(),
        ) );

        register_rest_route( $this->namespace, '/payments/process', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'process_payment' ),
            'permission_callback' => array( $this, 'check_create_booking_permissions' ),
            'args'                => $this->get_process_payment_args(),
        ) );

        register_rest_route( $this->namespace, '/payments/orders/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_payment_order' ),
            'permission_callback' => array( $this, 'check_booking_permissions' ),
        ) );

        register_rest_route( $this->namespace, '/payments/orders/(?P<id>\d+)/status', array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array( $this, 'update_payment_status' ),
            'permission_callback' => array( $this, 'check_admin_permissions' ),
            'args'                => $this->get_payment_status_args(),
        ) );

        // Messaging endpoints
        register_rest_route( $this->namespace, '/messages', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_messages' ),
                'permission_callback' => array( $this, 'check_messaging_permissions' ),
                'args'                => $this->get_messages_args(),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'send_message' ),
                'permission_callback' => array( $this, 'check_messaging_permissions' ),
                'args'                => $this->get_send_message_args(),
            ),
        ) );

        register_rest_route( $this->namespace, '/messages/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_message' ),
                'permission_callback' => array( $this, 'check_message_permissions' ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_message' ),
                'permission_callback' => array( $this, 'check_message_permissions' ),
                'args'                => $this->get_update_message_args(),
            ),
        ) );

        register_rest_route( $this->namespace, '/conversations/(?P<user_id>\d+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_conversation' ),
            'permission_callback' => array( $this, 'check_conversation_permissions' ),
            'args'                => $this->get_conversation_args(),
        ) );

        // Worker payment settings endpoint
        register_rest_route( $this->namespace, '/workers/(?P<id>\d+)/payment-settings', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_worker_payment_settings' ),
                'permission_callback' => array( $this, 'check_worker_permissions' ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_worker_payment_settings' ),
                'permission_callback' => array( $this, 'check_worker_permissions' ),
                'args'                => $this->get_worker_payment_settings_args(),
            ),
        ) );
    }

    /**
     * Get bookings.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_bookings( $request ) {
        $params = $request->get_params();
        
        $args = array(
            'limit'      => isset( $params['per_page'] ) ? absint( $params['per_page'] ) : 20,
            'offset'     => isset( $params['offset'] ) ? absint( $params['offset'] ) : 0,
            'user_id'    => isset( $params['user_id'] ) ? absint( $params['user_id'] ) : 0,
            'worker_id'  => isset( $params['worker_id'] ) ? absint( $params['worker_id'] ) : 0,
            'service_id' => isset( $params['service_id'] ) ? absint( $params['service_id'] ) : 0,
            'status'     => isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : '',
            'date_from'  => isset( $params['date_from'] ) ? sanitize_text_field( $params['date_from'] ) : '',
            'date_to'    => isset( $params['date_to'] ) ? sanitize_text_field( $params['date_to'] ) : '',
            'orderby'    => isset( $params['orderby'] ) ? sanitize_text_field( $params['orderby'] ) : 'booking_date',
            'order'      => isset( $params['order'] ) ? sanitize_text_field( $params['order'] ) : 'ASC',
        );

        // Filter by current user if not admin
        if ( ! current_user_can( 'manage_options' ) ) {
            $current_user_id = get_current_user_id();
            if ( current_user_can( 'schedspot_manage_bookings' ) ) {
                // Worker - show their bookings
                $args['worker_id'] = $current_user_id;
            } else {
                // Customer - show their bookings
                $args['user_id'] = $current_user_id;
            }
        }

        $bookings = SchedSpot_Booking::get_bookings( $args );
        $data = array();

        foreach ( $bookings as $booking ) {
            $data[] = $this->prepare_booking_for_response( $booking );
        }

        return rest_ensure_response( $data );
    }

    /**
     * Get single booking.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_booking( $request ) {
        $booking_id = absint( $request['id'] );
        $booking = new SchedSpot_Booking( $booking_id );

        if ( ! $booking->id ) {
            return new WP_Error( 'booking_not_found', __( 'Booking not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        return rest_ensure_response( $this->prepare_booking_for_response( $booking ) );
    }

    /**
     * Create booking.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function create_booking( $request ) {
        $params = $request->get_params();

        // Set user_id to current user if not provided
        if ( empty( $params['user_id'] ) ) {
            $params['user_id'] = get_current_user_id();
        }

        $booking_id = SchedSpot_Booking::create_booking( $params );

        if ( is_wp_error( $booking_id ) ) {
            return $booking_id;
        }

        $booking = new SchedSpot_Booking( $booking_id );
        return rest_ensure_response( $this->prepare_booking_for_response( $booking ) );
    }

    /**
     * Update booking.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function update_booking( $request ) {
        $booking_id = absint( $request['id'] );
        $booking = new SchedSpot_Booking( $booking_id );

        if ( ! $booking->id ) {
            return new WP_Error( 'booking_not_found', __( 'Booking not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        $params = $request->get_params();
        unset( $params['id'] ); // Remove ID from update data

        $result = $booking->update( $params );

        if ( ! $result ) {
            return new WP_Error( 'booking_update_failed', __( 'Failed to update booking.', 'schedspot' ), array( 'status' => 500 ) );
        }

        return rest_ensure_response( $this->prepare_booking_for_response( $booking ) );
    }

    /**
     * Delete booking.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function delete_booking( $request ) {
        $booking_id = absint( $request['id'] );
        $booking = new SchedSpot_Booking( $booking_id );

        if ( ! $booking->id ) {
            return new WP_Error( 'booking_not_found', __( 'Booking not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        $result = $booking->delete();

        if ( ! $result ) {
            return new WP_Error( 'booking_delete_failed', __( 'Failed to delete booking.', 'schedspot' ), array( 'status' => 500 ) );
        }

        return rest_ensure_response( array( 'deleted' => true ) );
    }

    /**
     * Check availability.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function check_availability( $request ) {
        $params = $request->get_params();
        
        $worker_id = absint( $params['worker_id'] );
        $date = sanitize_text_field( $params['date'] );
        $start_time = sanitize_text_field( $params['start_time'] );
        $duration = absint( $params['duration'] );

        // Calculate end time
        $start_datetime = new DateTime( $date . ' ' . $start_time );
        $end_datetime = clone $start_datetime;
        $end_datetime->add( new DateInterval( 'PT' . $duration . 'M' ) );
        $end_time = $end_datetime->format( 'H:i:s' );

        $is_available = true;
        $message = __( 'Time slot is available.', 'schedspot' );
        $available_workers = array();

        if ( $worker_id > 0 ) {
            // Check specific worker availability
            $conflict = SchedSpot_Booking::check_booking_conflict( $worker_id, $date, $start_time, $end_time );
            if ( $conflict ) {
                $is_available = false;
                $message = __( 'Selected worker is not available at this time.', 'schedspot' );
            } else {
                $worker = get_userdata( $worker_id );
                if ( $worker ) {
                    $available_workers[] = array(
                        'id'   => $worker->ID,
                        'name' => $worker->display_name,
                    );
                }
            }
        } else {
            // Check all workers
            $workers = get_users( array( 'role' => 'schedspot_worker' ) );

            foreach ( $workers as $worker ) {
                $conflict = SchedSpot_Booking::check_booking_conflict( $worker->ID, $date, $start_time, $end_time );
                if ( ! $conflict ) {
                    $available_workers[] = array(
                        'id'   => $worker->ID,
                        'name' => $worker->display_name,
                    );
                }
            }

            if ( empty( $available_workers ) ) {
                $is_available = false;
                $message = __( 'No workers are available at this time.', 'schedspot' );
            } else {
                $message = sprintf( __( '%d worker(s) available for this time slot.', 'schedspot' ), count( $available_workers ) );
            }
        }

        return rest_ensure_response( array(
            'available' => $is_available,
            'message'   => $message,
            'workers'   => $available_workers,
        ) );
    }

    /**
     * Prepare booking for response.
     *
     * @since 1.0.0
     * @param SchedSpot_Booking $booking Booking object.
     * @return array Prepared booking data.
     */
    private function prepare_booking_for_response( $booking ) {
        $worker = get_userdata( $booking->worker_id );
        $service = null;
        
        if ( $booking->service_id ) {
            global $wpdb;
            $service = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}schedspot_services WHERE id = %d",
                $booking->service_id
            ) );
        }

        return array(
            'id'                => $booking->id,
            'user_id'           => $booking->user_id,
            'worker_id'         => $booking->worker_id,
            'worker_name'       => $worker ? $worker->display_name : '',
            'service_id'        => $booking->service_id,
            'service_name'      => $service ? $service->name : '',
            'booking_date'      => $booking->booking_date,
            'start_time'        => $booking->start_time,
            'end_time'          => $booking->end_time,
            'duration'          => $booking->duration,
            'status'            => $booking->status,
            'total_cost'        => floatval( $booking->total_cost ),
            'deposit_amount'    => floatval( $booking->deposit_amount ),
            'commission_amount' => floatval( $booking->commission_amount ),
            'client_details'    => $booking->client_details,
            'notes'             => $booking->notes,
            'created_at'        => $booking->created_at,
            'updated_at'        => $booking->updated_at,
        );
    }

    // Permission callbacks
    public function check_bookings_permissions( $request ) {
        return current_user_can( 'schedspot_view_own_bookings' ) || current_user_can( 'manage_options' );
    }

    public function check_create_booking_permissions( $request ) {
        return current_user_can( 'schedspot_create_booking' ) || current_user_can( 'manage_options' );
    }

    public function check_booking_permissions( $request ) {
        $booking_id = absint( $request['id'] );
        $booking = new SchedSpot_Booking( $booking_id );
        
        if ( ! $booking->id ) {
            return false;
        }

        $current_user_id = get_current_user_id();
        
        // Admin can access all bookings
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        // Users can access their own bookings
        if ( $booking->user_id === $current_user_id || $booking->worker_id === $current_user_id ) {
            return true;
        }

        return false;
    }

    public function check_worker_permissions( $request ) {
        $worker_id = absint( $request['id'] );
        $current_user_id = get_current_user_id();
        
        return current_user_can( 'manage_options' ) || $worker_id === $current_user_id;
    }

    public function check_admin_permissions( $request ) {
        return current_user_can( 'manage_options' );
    }

    // Argument definitions for endpoints
    private function get_bookings_args() {
        return array(
            'per_page'   => array( 'default' => 20, 'sanitize_callback' => 'absint' ),
            'offset'     => array( 'default' => 0, 'sanitize_callback' => 'absint' ),
            'user_id'    => array( 'default' => 0, 'sanitize_callback' => 'absint' ),
            'worker_id'  => array( 'default' => 0, 'sanitize_callback' => 'absint' ),
            'service_id' => array( 'default' => 0, 'sanitize_callback' => 'absint' ),
            'status'     => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
            'date_from'  => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
            'date_to'    => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
            'orderby'    => array( 'default' => 'booking_date', 'sanitize_callback' => 'sanitize_text_field' ),
            'order'      => array( 'default' => 'ASC', 'sanitize_callback' => 'sanitize_text_field' ),
        );
    }

    private function get_create_booking_args() {
        return array(
            'user_id'        => array( 'required' => false, 'sanitize_callback' => 'absint' ),
            'worker_id'      => array( 'required' => true, 'sanitize_callback' => 'absint' ),
            'service_id'     => array( 'required' => false, 'sanitize_callback' => 'absint' ),
            'booking_date'   => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
            'start_time'     => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
            'duration'       => array( 'required' => true, 'sanitize_callback' => 'absint' ),
            'client_name'    => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
            'client_email'   => array( 'required' => true, 'sanitize_callback' => 'sanitize_email' ),
            'client_phone'   => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'client_address' => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
            'notes'          => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
        );
    }

    private function get_update_booking_args() {
        return array(
            'worker_id'      => array( 'required' => false, 'sanitize_callback' => 'absint' ),
            'service_id'     => array( 'required' => false, 'sanitize_callback' => 'absint' ),
            'booking_date'   => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'start_time'     => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'duration'       => array( 'required' => false, 'sanitize_callback' => 'absint' ),
            'status'         => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'total_cost'     => array( 'required' => false, 'sanitize_callback' => 'floatval' ),
            'notes'          => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
        );
    }

    private function get_availability_args() {
        return array(
            'worker_id'  => array( 'required' => false, 'sanitize_callback' => 'absint' ),
            'date'       => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
            'start_time' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
            'duration'   => array( 'required' => true, 'sanitize_callback' => 'absint' ),
        );
    }

    private function get_services_args() {
        return array(
            'per_page' => array( 'default' => 20, 'sanitize_callback' => 'absint' ),
            'category' => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
            'active'   => array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ),
        );
    }

    private function get_workers_args() {
        return array(
            'service_id' => array( 'default' => 0, 'sanitize_callback' => 'absint' ),
        );
    }

    private function get_create_service_args() {
        return array(
            'name'        => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
            'description' => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
            'duration'    => array( 'required' => true, 'sanitize_callback' => 'absint' ),
            'price_type'  => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
            'base_price'  => array( 'required' => true, 'sanitize_callback' => 'floatval' ),
            'category'    => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
        );
    }

    private function get_update_service_args() {
        return array(
            'name'        => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'description' => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
            'duration'    => array( 'required' => false, 'sanitize_callback' => 'absint' ),
            'price_type'  => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'base_price'  => array( 'required' => false, 'sanitize_callback' => 'floatval' ),
            'category'    => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'is_active'   => array( 'required' => false, 'sanitize_callback' => 'rest_sanitize_boolean' ),
        );
    }

    private function get_availability_update_args() {
        return array(
            'availability' => array( 'required' => true, 'sanitize_callback' => array( $this, 'sanitize_availability_data' ) ),
        );
    }

    private function get_worker_profile_args() {
        return array(
            'bio'               => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
            'skills'            => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'hourly_rate'       => array( 'required' => false, 'sanitize_callback' => 'floatval' ),
            'service_areas'     => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'phone'             => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'address'           => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
            'experience_years'  => array( 'required' => false, 'sanitize_callback' => 'absint' ),
            'certifications'    => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'languages'         => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'availability_note' => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
            'is_available'      => array( 'required' => false, 'sanitize_callback' => 'rest_sanitize_boolean' ),
        );
    }

    private function get_worker_services_args() {
        return array(
            'services' => array( 'required' => true, 'sanitize_callback' => array( $this, 'sanitize_worker_services_data' ) ),
        );
    }

    public function sanitize_worker_services_data( $data ) {
        if ( ! is_array( $data ) ) {
            return array();
        }

        $sanitized = array();
        foreach ( $data as $service ) {
            if ( isset( $service['service_id'] ) ) {
                $sanitized[] = array(
                    'service_id'   => absint( $service['service_id'] ),
                    'custom_price' => isset( $service['custom_price'] ) ? floatval( $service['custom_price'] ) : null,
                    'is_enabled'   => isset( $service['is_enabled'] ) ? rest_sanitize_boolean( $service['is_enabled'] ) : true,
                );
            }
        }

        return $sanitized;
    }

    // Payment endpoint argument definitions
    private function get_create_order_args() {
        return array(
            'booking_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
            'payment_type' => array( 'default' => 'full', 'sanitize_callback' => 'sanitize_text_field' ),
        );
    }

    private function get_process_payment_args() {
        return array(
            'order_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
            'payment_method' => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            'payment_data' => array( 'required' => false, 'sanitize_callback' => array( $this, 'sanitize_payment_data' ) ),
        );
    }

    private function get_payment_status_args() {
        return array(
            'status' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
        );
    }

    // Messaging endpoint argument definitions
    private function get_messages_args() {
        return array(
            'conversation_with' => array( 'default' => 0, 'sanitize_callback' => 'absint' ),
            'limit' => array( 'default' => 50, 'sanitize_callback' => 'absint' ),
            'offset' => array( 'default' => 0, 'sanitize_callback' => 'absint' ),
            'order' => array( 'default' => 'ASC', 'sanitize_callback' => 'sanitize_text_field' ),
            'booking_id' => array( 'default' => 0, 'sanitize_callback' => 'absint' ),
        );
    }

    private function get_send_message_args() {
        return array(
            'receiver_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
            'content' => array( 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field' ),
            'booking_id' => array( 'default' => 0, 'sanitize_callback' => 'absint' ),
            'message_type' => array( 'default' => 'text', 'sanitize_callback' => 'sanitize_text_field' ),
            'attachment_data' => array( 'default' => '', 'sanitize_callback' => array( $this, 'sanitize_attachment_data' ) ),
        );
    }

    private function get_update_message_args() {
        return array(
            'mark_as_read' => array( 'default' => false, 'sanitize_callback' => 'rest_sanitize_boolean' ),
        );
    }

    private function get_conversation_args() {
        return array(
            'limit' => array( 'default' => 50, 'sanitize_callback' => 'absint' ),
            'offset' => array( 'default' => 0, 'sanitize_callback' => 'absint' ),
            'order' => array( 'default' => 'ASC', 'sanitize_callback' => 'sanitize_text_field' ),
            'booking_id' => array( 'default' => 0, 'sanitize_callback' => 'absint' ),
        );
    }

    public function sanitize_payment_data( $data ) {
        if ( ! is_array( $data ) ) {
            return array();
        }

        // Sanitize payment data based on your payment gateway requirements
        $sanitized = array();
        foreach ( $data as $key => $value ) {
            $sanitized[ sanitize_key( $key ) ] = sanitize_text_field( $value );
        }

        return $sanitized;
    }

    public function sanitize_attachment_data( $data ) {
        if ( empty( $data ) ) {
            return '';
        }

        if ( is_string( $data ) ) {
            return sanitize_text_field( $data );
        }

        if ( is_array( $data ) ) {
            return wp_json_encode( $data );
        }

        return '';
    }

    public function sanitize_availability_data( $data ) {
        if ( ! is_array( $data ) ) {
            return array();
        }

        $sanitized = array();
        foreach ( $data as $slot ) {
            if ( isset( $slot['day_of_week'], $slot['start_time'], $slot['end_time'] ) ) {
                $sanitized[] = array(
                    'day_of_week'  => absint( $slot['day_of_week'] ),
                    'start_time'   => sanitize_text_field( $slot['start_time'] ),
                    'end_time'     => sanitize_text_field( $slot['end_time'] ),
                    'is_available' => isset( $slot['is_available'] ) ? rest_sanitize_boolean( $slot['is_available'] ) : true,
                );
            }
        }

        return $sanitized;
    }

    /**
     * Create payment order.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function create_payment_order( $request ) {
        $params = $request->get_params();
        $booking_id = absint( $params['booking_id'] );

        if ( ! $booking_id ) {
            return new WP_Error( 'missing_booking_id', __( 'Booking ID is required.', 'schedspot' ), array( 'status' => 400 ) );
        }

        $booking = new SchedSpot_Booking( $booking_id );
        if ( ! $booking->id ) {
            return new WP_Error( 'booking_not_found', __( 'Booking not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        // Check if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            return new WP_Error( 'woocommerce_not_active', __( 'WooCommerce is required for payments.', 'schedspot' ), array( 'status' => 400 ) );
        }

        // Create WooCommerce order
        $wc_integration = new SchedSpot_WooCommerce();
        $wc_integration->create_order_for_booking( $booking_id, $booking );

        $order_id = get_post_meta( $booking_id, 'schedspot_wc_order_id', true );
        if ( ! $order_id ) {
            return new WP_Error( 'order_creation_failed', __( 'Failed to create payment order.', 'schedspot' ), array( 'status' => 500 ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return new WP_Error( 'order_not_found', __( 'Payment order not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        return rest_ensure_response( array(
            'order_id'     => $order->get_id(),
            'order_key'    => $order->get_order_key(),
            'total'        => $order->get_total(),
            'currency'     => $order->get_currency(),
            'status'       => $order->get_status(),
            'checkout_url' => $order->get_checkout_payment_url(),
        ) );
    }

    /**
     * Process payment.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function process_payment( $request ) {
        $params = $request->get_params();
        $order_id = absint( $params['order_id'] );
        $payment_method = sanitize_text_field( $params['payment_method'] );

        if ( ! $order_id ) {
            return new WP_Error( 'missing_order_id', __( 'Order ID is required.', 'schedspot' ), array( 'status' => 400 ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return new WP_Error( 'order_not_found', __( 'Order not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        // Set payment method if provided
        if ( $payment_method ) {
            $order->set_payment_method( $payment_method );
            $order->save();
        }

        // Process payment through WooCommerce
        $payment_result = $order->payment_complete();

        if ( $payment_result ) {
            return rest_ensure_response( array(
                'success'    => true,
                'order_id'   => $order->get_id(),
                'status'     => $order->get_status(),
                'message'    => __( 'Payment processed successfully.', 'schedspot' ),
            ) );
        } else {
            return new WP_Error( 'payment_failed', __( 'Payment processing failed.', 'schedspot' ), array( 'status' => 500 ) );
        }
    }

    /**
     * Get payment order.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_payment_order( $request ) {
        $order_id = absint( $request['id'] );
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return new WP_Error( 'order_not_found', __( 'Order not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        return rest_ensure_response( array(
            'id'           => $order->get_id(),
            'order_key'    => $order->get_order_key(),
            'status'       => $order->get_status(),
            'total'        => $order->get_total(),
            'currency'     => $order->get_currency(),
            'date_created' => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
            'payment_url'  => $order->get_checkout_payment_url(),
            'items'        => $this->get_order_items( $order ),
        ) );
    }

    /**
     * Update payment status.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function update_payment_status( $request ) {
        $order_id = absint( $request['id'] );
        $status = sanitize_text_field( $request['status'] );

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return new WP_Error( 'order_not_found', __( 'Order not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        $order->update_status( $status );

        return rest_ensure_response( array(
            'success' => true,
            'order_id' => $order->get_id(),
            'status' => $order->get_status(),
        ) );
    }

    /**
     * Get order items.
     *
     * @since 1.0.0
     * @param WC_Order $order WooCommerce order object.
     * @return array Order items.
     */
    private function get_order_items( $order ) {
        $items = array();

        foreach ( $order->get_items() as $item ) {
            $items[] = array(
                'name'     => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total'    => $item->get_total(),
            );
        }

        return $items;
    }

    /**
     * Get services.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_services( $request ) {
        global $wpdb;

        $params = $request->get_params();
        $per_page = absint( $params['per_page'] );
        $category = sanitize_text_field( $params['category'] );
        $active = rest_sanitize_boolean( $params['active'] );

        $where_clauses = array();
        $query_params = array();

        if ( $active ) {
            $where_clauses[] = 'is_active = 1';
        }

        if ( ! empty( $category ) ) {
            $where_clauses[] = 'category = %s';
            $query_params[] = $category;
        }

        $where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';
        $limit_sql = $per_page > 0 ? 'LIMIT ' . $per_page : '';

        $sql = "SELECT * FROM {$wpdb->prefix}schedspot_services {$where_sql} ORDER BY name ASC {$limit_sql}";

        if ( ! empty( $query_params ) ) {
            $sql = $wpdb->prepare( $sql, $query_params );
        }

        $services = $wpdb->get_results( $sql );
        $data = array();

        foreach ( $services as $service ) {
            $data[] = $this->prepare_service_for_response( $service );
        }

        return rest_ensure_response( $data );
    }

    /**
     * Get single service.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_service( $request ) {
        global $wpdb;

        $service_id = absint( $request['id'] );
        $service = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}schedspot_services WHERE id = %d",
            $service_id
        ) );

        if ( ! $service ) {
            return new WP_Error( 'service_not_found', __( 'Service not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        return rest_ensure_response( $this->prepare_service_for_response( $service ) );
    }

    /**
     * Create service.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function create_service( $request ) {
        global $wpdb;

        $params = $request->get_params();

        $result = $wpdb->insert(
            $wpdb->prefix . 'schedspot_services',
            array(
                'name'        => $params['name'],
                'description' => $params['description'],
                'duration'    => $params['duration'],
                'price_type'  => $params['price_type'],
                'base_price'  => $params['base_price'],
                'category'    => $params['category'],
                'is_active'   => 1,
            ),
            array( '%s', '%s', '%d', '%s', '%f', '%s', '%d' )
        );

        if ( false === $result ) {
            return new WP_Error( 'service_create_failed', __( 'Failed to create service.', 'schedspot' ), array( 'status' => 500 ) );
        }

        $service_id = $wpdb->insert_id;
        $service = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}schedspot_services WHERE id = %d",
            $service_id
        ) );

        do_action( 'schedspot_service_created', $service_id, $params );

        return rest_ensure_response( $this->prepare_service_for_response( $service ) );
    }

    /**
     * Update service.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function update_service( $request ) {
        global $wpdb;

        $service_id = absint( $request['id'] );
        $params = $request->get_params();
        unset( $params['id'] );

        // Check if service exists
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}schedspot_services WHERE id = %d",
            $service_id
        ) );

        if ( ! $existing ) {
            return new WP_Error( 'service_not_found', __( 'Service not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        $update_data = array();
        $format = array();

        $allowed_fields = array(
            'name'        => '%s',
            'description' => '%s',
            'duration'    => '%d',
            'price_type'  => '%s',
            'base_price'  => '%f',
            'category'    => '%s',
            'is_active'   => '%d',
        );

        foreach ( $params as $key => $value ) {
            if ( isset( $allowed_fields[ $key ] ) ) {
                $update_data[ $key ] = $value;
                $format[] = $allowed_fields[ $key ];
            }
        }

        if ( empty( $update_data ) ) {
            return new WP_Error( 'no_data_to_update', __( 'No valid data to update.', 'schedspot' ), array( 'status' => 400 ) );
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'schedspot_services',
            $update_data,
            array( 'id' => $service_id ),
            $format,
            array( '%d' )
        );

        if ( false === $result ) {
            return new WP_Error( 'service_update_failed', __( 'Failed to update service.', 'schedspot' ), array( 'status' => 500 ) );
        }

        $service = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}schedspot_services WHERE id = %d",
            $service_id
        ) );

        do_action( 'schedspot_service_updated', $service_id, $params );

        return rest_ensure_response( $this->prepare_service_for_response( $service ) );
    }

    /**
     * Delete service.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function delete_service( $request ) {
        global $wpdb;

        $service_id = absint( $request['id'] );

        // Check if service exists
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}schedspot_services WHERE id = %d",
            $service_id
        ) );

        if ( ! $existing ) {
            return new WP_Error( 'service_not_found', __( 'Service not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        // Check if service has bookings
        $has_bookings = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE service_id = %d",
            $service_id
        ) );

        if ( $has_bookings > 0 ) {
            return new WP_Error( 'service_has_bookings', __( 'Cannot delete service with existing bookings.', 'schedspot' ), array( 'status' => 400 ) );
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'schedspot_services',
            array( 'id' => $service_id ),
            array( '%d' )
        );

        if ( false === $result ) {
            return new WP_Error( 'service_delete_failed', __( 'Failed to delete service.', 'schedspot' ), array( 'status' => 500 ) );
        }

        // Clean up worker-service relationships
        $wpdb->delete(
            $wpdb->prefix . 'schedspot_worker_services',
            array( 'service_id' => $service_id ),
            array( '%d' )
        );

        do_action( 'schedspot_service_deleted', $service_id );

        return rest_ensure_response( array( 'deleted' => true ) );
    }

    /**
     * Get workers.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_workers( $request ) {
        $params = $request->get_params();
        $service_id = absint( $params['service_id'] );

        if ( $service_id > 0 ) {
            // Get workers who offer this service
            global $wpdb;
            $worker_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT worker_id FROM {$wpdb->prefix}schedspot_worker_services
                 WHERE service_id = %d AND is_enabled = 1",
                $service_id
            ) );

            if ( empty( $worker_ids ) ) {
                return rest_ensure_response( array() );
            }

            $users = get_users( array(
                'include' => $worker_ids,
                'role'    => 'schedspot_worker',
            ) );
        } else {
            // Get all workers
            $users = get_users( array( 'role' => 'schedspot_worker' ) );
        }

        $data = array();
        foreach ( $users as $user ) {
            $data[] = $this->prepare_worker_for_response( $user, $service_id );
        }

        return rest_ensure_response( $data );
    }

    /**
     * Get single worker.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_worker( $request ) {
        $worker_id = absint( $request['id'] );
        $user = get_userdata( $worker_id );

        if ( ! $user || ! in_array( 'schedspot_worker', $user->roles ) ) {
            return new WP_Error( 'worker_not_found', __( 'Worker not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        return rest_ensure_response( $this->prepare_worker_for_response( $user ) );
    }

    /**
     * Get worker availability.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_worker_availability( $request ) {
        global $wpdb;

        $worker_id = absint( $request['id'] );

        $availability = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}schedspot_worker_availability
             WHERE worker_id = %d ORDER BY day_of_week ASC",
            $worker_id
        ) );

        $data = array();
        foreach ( $availability as $slot ) {
            $data[] = array(
                'id'           => $slot->id,
                'day_of_week'  => $slot->day_of_week,
                'start_time'   => $slot->start_time,
                'end_time'     => $slot->end_time,
                'is_available' => (bool) $slot->is_available,
            );
        }

        return rest_ensure_response( $data );
    }

    /**
     * Update worker availability.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function update_worker_availability( $request ) {
        global $wpdb;

        $worker_id = absint( $request['id'] );
        $params = $request->get_params();
        $availability_data = $params['availability'];

        // Clear existing availability
        $wpdb->delete(
            $wpdb->prefix . 'schedspot_worker_availability',
            array( 'worker_id' => $worker_id ),
            array( '%d' )
        );

        // Insert new availability
        foreach ( $availability_data as $slot ) {
            $wpdb->insert(
                $wpdb->prefix . 'schedspot_worker_availability',
                array(
                    'worker_id'    => $worker_id,
                    'day_of_week'  => $slot['day_of_week'],
                    'start_time'   => $slot['start_time'],
                    'end_time'     => $slot['end_time'],
                    'is_available' => $slot['is_available'] ? 1 : 0,
                ),
                array( '%d', '%d', '%s', '%s', '%d' )
            );
        }

        do_action( 'schedspot_worker_availability_updated', $worker_id, $availability_data );

        return $this->get_worker_availability( $request );
    }

    /**
     * Prepare service for response.
     *
     * @since 1.0.0
     * @param object $service Service object.
     * @return array Prepared service data.
     */
    private function prepare_service_for_response( $service ) {
        return array(
            'id'          => absint( $service->id ),
            'name'        => $service->name,
            'description' => $service->description,
            'duration'    => absint( $service->duration ),
            'price_type'  => $service->price_type,
            'base_price'  => floatval( $service->base_price ),
            'category'    => $service->category,
            'is_active'   => (bool) $service->is_active,
            'created_at'  => $service->created_at,
            'updated_at'  => $service->updated_at,
        );
    }

    /**
     * Get worker profile.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_worker_profile( $request ) {
        $worker_id = absint( $request['id'] );
        $worker = new SchedSpot_Worker( $worker_id );

        if ( ! $worker->id ) {
            return new WP_Error( 'worker_not_found', __( 'Worker not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        return rest_ensure_response( $worker->get_display_data() );
    }

    /**
     * Update worker profile.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function update_worker_profile( $request ) {
        $worker_id = absint( $request['id'] );
        $worker = new SchedSpot_Worker( $worker_id );

        if ( ! $worker->id ) {
            return new WP_Error( 'worker_not_found', __( 'Worker not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        $params = $request->get_params();
        unset( $params['id'] );

        $result = $worker->update_profile( $params );

        if ( ! $result ) {
            return new WP_Error( 'profile_update_failed', __( 'Failed to update worker profile.', 'schedspot' ), array( 'status' => 500 ) );
        }

        return rest_ensure_response( $worker->get_display_data() );
    }

    /**
     * Get worker statistics.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_worker_statistics( $request ) {
        $worker_id = absint( $request['id'] );
        $worker = new SchedSpot_Worker( $worker_id );

        if ( ! $worker->id ) {
            return new WP_Error( 'worker_not_found', __( 'Worker not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        return rest_ensure_response( $worker->get_statistics() );
    }

    /**
     * Get worker services.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_worker_services( $request ) {
        $worker_id = absint( $request['id'] );
        $worker = new SchedSpot_Worker( $worker_id );

        if ( ! $worker->id ) {
            return new WP_Error( 'worker_not_found', __( 'Worker not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        return rest_ensure_response( $worker->get_services() );
    }

    /**
     * Update worker services.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function update_worker_services( $request ) {
        global $wpdb;

        $worker_id = absint( $request['id'] );
        $worker = new SchedSpot_Worker( $worker_id );

        if ( ! $worker->id ) {
            return new WP_Error( 'worker_not_found', __( 'Worker not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        $params = $request->get_params();
        $services = $params['services'];

        // Clear existing service assignments
        $wpdb->delete(
            $wpdb->prefix . 'schedspot_worker_services',
            array( 'worker_id' => $worker_id ),
            array( '%d' )
        );

        // Add new service assignments
        foreach ( $services as $service ) {
            $service_id = absint( $service['service_id'] );
            $custom_price = isset( $service['custom_price'] ) ? floatval( $service['custom_price'] ) : null;
            $is_enabled = isset( $service['is_enabled'] ) ? (bool) $service['is_enabled'] : true;

            $wpdb->insert(
                $wpdb->prefix . 'schedspot_worker_services',
                array(
                    'worker_id'    => $worker_id,
                    'service_id'   => $service_id,
                    'custom_price' => $custom_price,
                    'is_enabled'   => $is_enabled ? 1 : 0,
                ),
                array( '%d', '%d', '%f', '%d' )
            );
        }

        do_action( 'schedspot_worker_services_updated', $worker_id, $services );

        return rest_ensure_response( $worker->get_services() );
    }

    /**
     * Prepare worker for response.
     *
     * @since 1.0.0
     * @param WP_User $user User object.
     * @param int     $service_id Optional service ID for custom pricing.
     * @return array Prepared worker data.
     */
    private function prepare_worker_for_response( $user, $service_id = 0 ) {
        global $wpdb;

        $data = array(
            'id'           => $user->ID,
            'name'         => $user->display_name,
            'email'        => $user->user_email,
            'registered'   => $user->user_registered,
            'services'     => array(),
        );

        // Get worker's services
        $services = $wpdb->get_results( $wpdb->prepare(
            "SELECT ws.*, s.name as service_name, s.base_price as service_base_price
             FROM {$wpdb->prefix}schedspot_worker_services ws
             JOIN {$wpdb->prefix}schedspot_services s ON ws.service_id = s.id
             WHERE ws.worker_id = %d AND ws.is_enabled = 1",
            $user->ID
        ) );

        foreach ( $services as $service ) {
            $data['services'][] = array(
                'id'           => absint( $service->service_id ),
                'name'         => $service->service_name,
                'custom_price' => $service->custom_price ? floatval( $service->custom_price ) : floatval( $service->service_base_price ),
                'is_enabled'   => (bool) $service->is_enabled,
            );
        }

        return $data;
    }

    /**
     * Get messages.
     *
     * @since 2.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_messages( $request ) {
        $params = $request->get_params();
        $user_id = get_current_user_id();

        if ( isset( $params['conversation_with'] ) ) {
            $other_user_id = absint( $params['conversation_with'] );
            $messages = SchedSpot_Message::get_conversation( $user_id, $other_user_id, $params );
        } else {
            $conversations = SchedSpot_Message::get_user_conversations( $user_id, $params );
            return rest_ensure_response( $conversations );
        }

        $formatted_messages = array();
        foreach ( $messages as $message ) {
            $formatted_messages[] = $message->get_formatted_data();
        }

        return rest_ensure_response( $formatted_messages );
    }

    /**
     * Send message.
     *
     * @since 2.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function send_message( $request ) {
        $params = $request->get_params();
        $params['sender_id'] = get_current_user_id();

        $messaging = new SchedSpot_Messaging();
        $result = $messaging->send_message( $params );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $message = new SchedSpot_Message( $result );
        return rest_ensure_response( $message->get_formatted_data() );
    }

    /**
     * Get single message.
     *
     * @since 2.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_message( $request ) {
        $message_id = absint( $request['id'] );
        $message = new SchedSpot_Message( $message_id );

        if ( ! $message->id ) {
            return new WP_Error( 'message_not_found', __( 'Message not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        return rest_ensure_response( $message->get_formatted_data() );
    }

    /**
     * Update message.
     *
     * @since 2.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function update_message( $request ) {
        $message_id = absint( $request['id'] );
        $message = new SchedSpot_Message( $message_id );

        if ( ! $message->id ) {
            return new WP_Error( 'message_not_found', __( 'Message not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        $params = $request->get_params();

        // Only allow marking as read for now
        if ( isset( $params['mark_as_read'] ) && $params['mark_as_read'] ) {
            $message->mark_as_read();
        }

        return rest_ensure_response( $message->get_formatted_data() );
    }

    /**
     * Get conversation.
     *
     * @since 2.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_conversation( $request ) {
        $user_id = get_current_user_id();
        $other_user_id = absint( $request['user_id'] );
        $params = $request->get_params();

        $messages = SchedSpot_Message::get_conversation( $user_id, $other_user_id, $params );

        $formatted_messages = array();
        foreach ( $messages as $message ) {
            $formatted_messages[] = $message->get_formatted_data();
        }

        return rest_ensure_response( $formatted_messages );
    }

    // Permission callbacks for messaging
    public function check_messaging_permissions( $request ) {
        return is_user_logged_in() && ( current_user_can( 'schedspot_send_messages' ) || current_user_can( 'manage_options' ) );
    }

    public function check_message_permissions( $request ) {
        $message_id = absint( $request['id'] );
        $message = new SchedSpot_Message( $message_id );

        if ( ! $message->id ) {
            return false;
        }

        $current_user_id = get_current_user_id();

        // Admin can access all messages
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        // Users can access their own messages
        return ( $message->sender_id === $current_user_id || $message->receiver_id === $current_user_id );
    }

    public function check_conversation_permissions( $request ) {
        $current_user_id = get_current_user_id();
        $other_user_id = absint( $request['user_id'] );

        // Admin can access all conversations
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        // Users can access conversations they're part of
        return $current_user_id > 0 && $other_user_id > 0;
    }

    /**
     * Get worker payment settings.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_worker_payment_settings( $request ) {
        $worker_id = absint( $request['id'] );
        $worker = new SchedSpot_Worker( $worker_id );

        if ( ! $worker->id ) {
            return new WP_Error( 'worker_not_found', __( 'Worker not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        $payment_settings = get_user_meta( $worker_id, 'schedspot_payment_settings', true );
        if ( ! $payment_settings ) {
            $payment_settings = array();
        }

        // Get earnings statistics
        $stats = $worker->get_statistics();

        $response_data = array_merge( $payment_settings, array(
            'total_earnings' => $stats['total_earnings'] ?? 0,
            'pending_payout' => $stats['pending_payout'] ?? 0,
            'commission_rate' => get_option( 'schedspot_commission_rate', 10 ),
        ) );

        return rest_ensure_response( $response_data );
    }

    /**
     * Update worker payment settings.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function update_worker_payment_settings( $request ) {
        $worker_id = absint( $request['id'] );
        $worker = new SchedSpot_Worker( $worker_id );

        if ( ! $worker->id ) {
            return new WP_Error( 'worker_not_found', __( 'Worker not found.', 'schedspot' ), array( 'status' => 404 ) );
        }

        $params = $request->get_params();

        $payment_settings = array(
            'payout_method' => sanitize_text_field( $params['payout_method'] ?? '' ),
            'payout_email' => sanitize_email( $params['payout_email'] ?? '' ),
            'tax_id' => sanitize_text_field( $params['tax_id'] ?? '' ),
        );

        $updated = update_user_meta( $worker_id, 'schedspot_payment_settings', $payment_settings );

        if ( $updated !== false ) {
            return rest_ensure_response( array( 'success' => true, 'settings' => $payment_settings ) );
        } else {
            return new WP_Error( 'update_failed', __( 'Failed to update payment settings.', 'schedspot' ), array( 'status' => 500 ) );
        }
    }

    /**
     * Get worker payment settings arguments.
     *
     * @since 1.0.0
     * @return array Arguments.
     */
    private function get_worker_payment_settings_args() {
        return array(
            'payout_method' => array( 'sanitize_callback' => 'sanitize_text_field' ),
            'payout_email' => array( 'sanitize_callback' => 'sanitize_email' ),
            'tax_id' => array( 'sanitize_callback' => 'sanitize_text_field' ),
        );
    }
}
