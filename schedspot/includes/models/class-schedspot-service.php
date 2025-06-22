<?php
/**
 * Service Model Class
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Service Class.
 *
 * @class SchedSpot_Service
 * @version 1.0.0
 */
class SchedSpot_Service {

    /**
     * Service ID.
     *
     * @var int
     */
    public $id = 0;

    /**
     * Service name.
     *
     * @var string
     */
    public $name = '';

    /**
     * Service description.
     *
     * @var string
     */
    public $description = '';

    /**
     * Duration in minutes.
     *
     * @var int
     */
    public $duration = 60;

    /**
     * Price type (hourly or fixed).
     *
     * @var string
     */
    public $price_type = 'hourly';

    /**
     * Base price.
     *
     * @var float
     */
    public $base_price = 0.00;

    /**
     * Service category.
     *
     * @var string
     */
    public $category = '';

    /**
     * Is active.
     *
     * @var bool
     */
    public $is_active = true;

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
     * Constructor.
     *
     * @since 1.0.0
     * @param int|object $service Service ID or service object.
     */
    public function __construct( $service = 0 ) {
        if ( is_numeric( $service ) && $service > 0 ) {
            $this->id = absint( $service );
            $this->get_service( $this->id );
        } elseif ( is_object( $service ) ) {
            $this->init( $service );
        }
    }

    /**
     * Initialize service from object.
     *
     * @since 1.0.0
     * @param object $service Service object.
     */
    private function init( $service ) {
        $this->id          = absint( $service->id );
        $this->name        = $service->name;
        $this->description = $service->description;
        $this->duration    = absint( $service->duration );
        $this->price_type  = $service->price_type;
        $this->base_price  = floatval( $service->base_price );
        $this->category    = $service->category;
        $this->is_active   = (bool) $service->is_active;
        $this->created_at  = $service->created_at;
        $this->updated_at  = $service->updated_at;
    }

    /**
     * Get service from database.
     *
     * @since 1.0.0
     * @param int $id Service ID.
     * @return bool True if service found, false otherwise.
     */
    private function get_service( $id ) {
        global $wpdb;

        $service = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}schedspot_services WHERE id = %d",
                $id
            )
        );

        if ( $service ) {
            $this->init( $service );
            return true;
        }

        return false;
    }

    /**
     * Create a new service.
     *
     * @since 1.0.0
     * @param array $data Service data.
     * @return int|WP_Error Service ID on success, WP_Error on failure.
     */
    public static function create_service( $data ) {
        global $wpdb;

        // Validate required fields
        $required_fields = array( 'name', 'duration', 'price_type', 'base_price' );
        foreach ( $required_fields as $field ) {
            if ( empty( $data[ $field ] ) ) {
                return new WP_Error( 'missing_field', sprintf( __( 'Missing required field: %s', 'schedspot' ), $field ) );
            }
        }

        // Validate price type
        if ( ! in_array( $data['price_type'], array( 'hourly', 'fixed' ) ) ) {
            return new WP_Error( 'invalid_price_type', __( 'Price type must be either "hourly" or "fixed".', 'schedspot' ) );
        }

        // Prepare data for insertion
        $insert_data = array(
            'name'        => sanitize_text_field( $data['name'] ),
            'description' => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
            'duration'    => absint( $data['duration'] ),
            'price_type'  => sanitize_text_field( $data['price_type'] ),
            'base_price'  => floatval( $data['base_price'] ),
            'category'    => isset( $data['category'] ) ? sanitize_text_field( $data['category'] ) : '',
            'is_active'   => isset( $data['is_active'] ) ? (bool) $data['is_active'] : true,
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'schedspot_services',
            $insert_data,
            array( '%s', '%s', '%d', '%s', '%f', '%s', '%d' )
        );

        if ( false === $result ) {
            return new WP_Error( 'db_error', __( 'Failed to create service.', 'schedspot' ) );
        }

        $service_id = $wpdb->insert_id;

        // Fire action hook
        do_action( 'schedspot_service_created', $service_id, $data );

        return $service_id;
    }

    /**
     * Update service.
     *
     * @since 1.0.0
     * @param array $data Service data to update.
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
            'name'        => '%s',
            'description' => '%s',
            'duration'    => '%d',
            'price_type'  => '%s',
            'base_price'  => '%f',
            'category'    => '%s',
            'is_active'   => '%d',
        );

        foreach ( $data as $key => $value ) {
            if ( isset( $allowed_fields[ $key ] ) ) {
                if ( $key === 'price_type' && ! in_array( $value, array( 'hourly', 'fixed' ) ) ) {
                    continue; // Skip invalid price types
                }
                
                $update_data[ $key ] = $value;
                $format[] = $allowed_fields[ $key ];
            }
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'schedspot_services',
            $update_data,
            array( 'id' => $this->id ),
            $format,
            array( '%d' )
        );

        if ( false !== $result ) {
            // Refresh object data
            $this->get_service( $this->id );

            // Fire action hook
            do_action( 'schedspot_service_updated', $this->id, $data );

            return true;
        }

        return false;
    }

    /**
     * Delete service.
     *
     * @since 1.0.0
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function delete() {
        global $wpdb;

        if ( ! $this->id ) {
            return false;
        }

        // Check if service has bookings
        $has_bookings = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE service_id = %d",
            $this->id
        ) );

        if ( $has_bookings > 0 ) {
            return new WP_Error( 'service_has_bookings', __( 'Cannot delete service with existing bookings.', 'schedspot' ) );
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'schedspot_services',
            array( 'id' => $this->id ),
            array( '%d' )
        );

        if ( false !== $result ) {
            // Clean up worker-service relationships
            $wpdb->delete(
                $wpdb->prefix . 'schedspot_worker_services',
                array( 'service_id' => $this->id ),
                array( '%d' )
            );

            // Fire action hook
            do_action( 'schedspot_service_deleted', $this->id );

            return true;
        }

        return false;
    }

    /**
     * Get services by criteria.
     *
     * @since 1.0.0
     * @param array $args Query arguments.
     * @return array Array of service objects.
     */
    public static function get_services( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'category'   => '',
            'is_active'  => null,
            'limit'      => 20,
            'offset'     => 0,
            'orderby'    => 'name',
            'order'      => 'ASC',
        );

        $args = wp_parse_args( $args, $defaults );

        $where_clauses = array();
        $params = array();

        if ( ! empty( $args['category'] ) ) {
            $where_clauses[] = 'category = %s';
            $params[] = $args['category'];
        }

        if ( null !== $args['is_active'] ) {
            $where_clauses[] = 'is_active = %d';
            $params[] = $args['is_active'] ? 1 : 0;
        }

        $where_sql = '';
        if ( ! empty( $where_clauses ) ) {
            $where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
        }

        $order_sql = sprintf( 'ORDER BY %s %s', esc_sql( $args['orderby'] ), esc_sql( $args['order'] ) );
        $limit_sql = sprintf( 'LIMIT %d OFFSET %d', absint( $args['limit'] ), absint( $args['offset'] ) );

        $sql = "SELECT * FROM {$wpdb->prefix}schedspot_services {$where_sql} {$order_sql} {$limit_sql}";

        if ( ! empty( $params ) ) {
            $sql = $wpdb->prepare( $sql, $params );
        }

        $results = $wpdb->get_results( $sql );

        $services = array();
        foreach ( $results as $service_data ) {
            $services[] = new self( $service_data );
        }

        return $services;
    }

    /**
     * Get service categories.
     *
     * @since 1.0.0
     * @return array Array of categories.
     */
    public static function get_categories() {
        global $wpdb;

        $categories = $wpdb->get_col(
            "SELECT DISTINCT category FROM {$wpdb->prefix}schedspot_services 
             WHERE category != '' AND category IS NOT NULL 
             ORDER BY category ASC"
        );

        return apply_filters( 'schedspot_service_categories', $categories );
    }

    /**
     * Get workers assigned to this service.
     *
     * @since 1.0.0
     * @return array Array of worker data.
     */
    public function get_workers() {
        global $wpdb;

        if ( ! $this->id ) {
            return array();
        }

        $worker_data = $wpdb->get_results( $wpdb->prepare(
            "SELECT ws.*, u.display_name, u.user_email 
             FROM {$wpdb->prefix}schedspot_worker_services ws
             JOIN {$wpdb->users} u ON ws.worker_id = u.ID
             WHERE ws.service_id = %d AND ws.is_enabled = 1",
            $this->id
        ) );

        $workers = array();
        foreach ( $worker_data as $worker ) {
            $workers[] = array(
                'id'           => absint( $worker->worker_id ),
                'name'         => $worker->display_name,
                'email'        => $worker->user_email,
                'custom_price' => $worker->custom_price ? floatval( $worker->custom_price ) : $this->base_price,
                'is_enabled'   => (bool) $worker->is_enabled,
            );
        }

        return $workers;
    }

    /**
     * Assign worker to service.
     *
     * @since 1.0.0
     * @param int   $worker_id Worker ID.
     * @param float $custom_price Optional custom price.
     * @return bool True on success, false on failure.
     */
    public function assign_worker( $worker_id, $custom_price = null ) {
        global $wpdb;

        if ( ! $this->id ) {
            return false;
        }

        // Check if assignment already exists
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}schedspot_worker_services 
             WHERE worker_id = %d AND service_id = %d",
            $worker_id,
            $this->id
        ) );

        if ( $existing ) {
            // Update existing assignment
            return $wpdb->update(
                $wpdb->prefix . 'schedspot_worker_services',
                array(
                    'custom_price' => $custom_price,
                    'is_enabled'   => 1,
                ),
                array(
                    'worker_id'  => $worker_id,
                    'service_id' => $this->id,
                ),
                array( '%f', '%d' ),
                array( '%d', '%d' )
            ) !== false;
        } else {
            // Create new assignment
            return $wpdb->insert(
                $wpdb->prefix . 'schedspot_worker_services',
                array(
                    'worker_id'    => $worker_id,
                    'service_id'   => $this->id,
                    'custom_price' => $custom_price,
                    'is_enabled'   => 1,
                ),
                array( '%d', '%d', '%f', '%d' )
            ) !== false;
        }
    }

    /**
     * Remove worker from service.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID.
     * @return bool True on success, false on failure.
     */
    public function remove_worker( $worker_id ) {
        global $wpdb;

        if ( ! $this->id ) {
            return false;
        }

        return $wpdb->delete(
            $wpdb->prefix . 'schedspot_worker_services',
            array(
                'worker_id'  => $worker_id,
                'service_id' => $this->id,
            ),
            array( '%d', '%d' )
        ) !== false;
    }
}
