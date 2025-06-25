<?php
/**
 * Worker Model Class
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Worker Class.
 *
 * @class SchedSpot_Worker
 * @version 1.0.0
 */
class SchedSpot_Worker {

    /**
     * Worker ID (WordPress User ID).
     *
     * @var int
     */
    public $id = 0;

    /**
     * WordPress User object.
     *
     * @var WP_User
     */
    public $user = null;

    /**
     * Worker profile data.
     *
     * @var array
     */
    public $profile = array();

    /**
     * Constructor.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID (WordPress User ID).
     */
    public function __construct( $worker_id = 0 ) {
        if ( $worker_id > 0 ) {
            $this->id = absint( $worker_id );
            $this->load_worker_data();
        }
    }

    /**
     * Load worker data.
     *
     * @since 1.0.0
     * @return bool True if worker found, false otherwise.
     */
    private function load_worker_data() {
        $this->user = get_userdata( $this->id );

        if ( ! $this->user || ! in_array( 'schedspot_worker', $this->user->roles ) ) {
            return false;
        }

        // Load profile data from user meta
        $this->profile = array(
            'bio'                => get_user_meta( $this->id, 'schedspot_bio', true ),
            'skills'             => get_user_meta( $this->id, 'schedspot_skills', true ),
            'hourly_rate'        => get_user_meta( $this->id, 'schedspot_hourly_rate', true ),
            'service_areas'      => get_user_meta( $this->id, 'schedspot_service_areas', true ),
            'phone'              => get_user_meta( $this->id, 'schedspot_phone', true ),
            'address'            => get_user_meta( $this->id, 'schedspot_address', true ),
            'experience_years'   => get_user_meta( $this->id, 'schedspot_experience_years', true ),
            'certifications'     => get_user_meta( $this->id, 'schedspot_certifications', true ),
            'languages'          => get_user_meta( $this->id, 'schedspot_languages', true ),
            'availability_note'  => get_user_meta( $this->id, 'schedspot_availability_note', true ),
            'is_available'       => get_user_meta( $this->id, 'schedspot_is_available', true ),
            'rating'             => get_user_meta( $this->id, 'schedspot_rating', true ),
            'total_bookings'     => get_user_meta( $this->id, 'schedspot_total_bookings', true ),
            'profile_completion' => get_user_meta( $this->id, 'schedspot_profile_completion', true ),
        );

        // Set defaults
        $defaults = array(
            'bio'                => '',
            'skills'             => array(),
            'hourly_rate'        => 0.00,
            'service_areas'      => array(),
            'phone'              => '',
            'address'            => '',
            'experience_years'   => 0,
            'certifications'     => array(),
            'languages'          => array(),
            'availability_note'  => '',
            'is_available'       => true,
            'rating'             => 0.0,
            'total_bookings'     => 0,
            'profile_completion' => 0,
        );

        $this->profile = wp_parse_args( $this->profile, $defaults );

        return true;
    }

    /**
     * Update worker profile.
     *
     * @since 1.0.0
     * @param array $data Profile data to update.
     * @return bool True on success, false on failure.
     */
    public function update_profile( $data ) {
        if ( ! $this->id ) {
            return false;
        }

        $allowed_fields = array(
            'bio', 'skills', 'hourly_rate', 'service_areas', 'phone', 'address',
            'experience_years', 'certifications', 'languages', 'availability_note', 'is_available'
        );

        $updated = false;

        foreach ( $data as $key => $value ) {
            if ( in_array( $key, $allowed_fields ) ) {
                // Sanitize data based on field type
                $sanitized_value = $this->sanitize_profile_field( $key, $value );

                update_user_meta( $this->id, 'schedspot_' . $key, $sanitized_value );
                $this->profile[ $key ] = $sanitized_value;
                $updated = true;
            }
        }

        if ( $updated ) {
            // Update profile completion percentage
            $this->update_profile_completion();

            // Fire action hook
            do_action( 'schedspot_worker_profile_updated', $this->id, $data );
        }

        return $updated;
    }

    /**
     * Sanitize profile field data.
     *
     * @since 1.0.0
     * @param string $field Field name.
     * @param mixed  $value Field value.
     * @return mixed Sanitized value.
     */
    private function sanitize_profile_field( $field, $value ) {
        switch ( $field ) {
            case 'bio':
            case 'availability_note':
                return sanitize_textarea_field( $value );

            case 'skills':
            case 'service_areas':
            case 'certifications':
            case 'languages':
                if ( is_string( $value ) ) {
                    // Convert comma-separated string to array
                    $value = explode( ',', $value );
                }
                return array_map( 'sanitize_text_field', (array) $value );

            case 'hourly_rate':
                return floatval( $value );

            case 'experience_years':
                return absint( $value );

            case 'is_available':
                return (bool) $value;

            case 'phone':
            case 'address':
            default:
                return sanitize_text_field( $value );
        }
    }

    /**
     * Calculate and update profile completion percentage.
     *
     * @since 1.0.0
     * @return int Profile completion percentage.
     */
    private function update_profile_completion() {
        $required_fields = array(
            'bio', 'skills', 'hourly_rate', 'phone', 'experience_years'
        );

        $completed = 0;
        $total = count( $required_fields );

        foreach ( $required_fields as $field ) {
            if ( ! empty( $this->profile[ $field ] ) ) {
                $completed++;
            }
        }

        // Check if user has avatar
        if ( get_avatar_url( $this->id ) !== get_avatar_url( 0 ) ) {
            $completed++;
            $total++;
        }

        $percentage = $total > 0 ? round( ( $completed / $total ) * 100 ) : 0;

        update_user_meta( $this->id, 'schedspot_profile_completion', $percentage );
        $this->profile['profile_completion'] = $percentage;

        return $percentage;
    }

    /**
     * Get worker services.
     *
     * @since 1.0.0
     * @return array Array of services with custom pricing.
     */
    public function get_services() {
        global $wpdb;

        $services = $wpdb->get_results( $wpdb->prepare(
            "SELECT ws.*, s.name, s.description, s.duration, s.base_price, s.category
             FROM {$wpdb->prefix}schedspot_worker_services ws
             JOIN {$wpdb->prefix}schedspot_services s ON ws.service_id = s.id
             WHERE ws.worker_id = %d AND ws.is_enabled = 1 AND s.is_active = 1
             ORDER BY s.name ASC",
            $this->id
        ) );

        $worker_services = array();
        foreach ( $services as $service ) {
            $worker_services[] = array(
                'id'           => absint( $service->service_id ),
                'name'         => $service->name,
                'description'  => $service->description,
                'duration'     => absint( $service->duration ),
                'base_price'   => floatval( $service->base_price ),
                'custom_price' => $service->custom_price ? floatval( $service->custom_price ) : floatval( $service->base_price ),
                'category'     => $service->category,
                'is_enabled'   => (bool) $service->is_enabled,
            );
        }

        return $worker_services;
    }

    /**
     * Get worker availability.
     *
     * @since 1.0.0
     * @return array Array of availability slots.
     */
    public function get_availability() {
        global $wpdb;

        $availability = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}schedspot_worker_availability 
             WHERE worker_id = %d ORDER BY day_of_week ASC, start_time ASC",
            $this->id
        ) );

        $schedule = array();
        foreach ( $availability as $slot ) {
            $schedule[] = array(
                'id'           => absint( $slot->id ),
                'day_of_week'  => absint( $slot->day_of_week ),
                'start_time'   => $slot->start_time,
                'end_time'     => $slot->end_time,
                'is_available' => (bool) $slot->is_available,
            );
        }

        return $schedule;
    }

    /**
     * Get worker statistics.
     *
     * @since 1.0.0
     * @return array Worker statistics.
     */
    public function get_statistics() {
        global $wpdb;

        // Get booking statistics
        $stats = $wpdb->get_row( $wpdb->prepare(
            "SELECT 
                COUNT(*) as total_bookings,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
                SUM(CASE WHEN status = 'completed' THEN total_cost - commission_amount ELSE 0 END) as total_earnings,
                AVG(CASE WHEN status = 'completed' THEN total_cost - commission_amount ELSE NULL END) as avg_booking_value
             FROM {$wpdb->prefix}schedspot_bookings 
             WHERE worker_id = %d",
            $this->id
        ) );

        // Get this month's statistics
        $current_month = date( 'Y-m' );
        $month_stats = $wpdb->get_row( $wpdb->prepare(
            "SELECT 
                COUNT(*) as month_bookings,
                SUM(CASE WHEN status = 'completed' THEN total_cost - commission_amount ELSE 0 END) as month_earnings
             FROM {$wpdb->prefix}schedspot_bookings 
             WHERE worker_id = %d AND DATE_FORMAT(booking_date, '%%Y-%%m') = %s",
            $this->id,
            $current_month
        ) );

        return array(
            'total_bookings'     => absint( $stats->total_bookings ),
            'completed_bookings' => absint( $stats->completed_bookings ),
            'cancelled_bookings' => absint( $stats->cancelled_bookings ),
            'total_earnings'     => floatval( $stats->total_earnings ),
            'avg_booking_value'  => floatval( $stats->avg_booking_value ),
            'month_bookings'     => absint( $month_stats->month_bookings ),
            'month_earnings'     => floatval( $month_stats->month_earnings ),
            'completion_rate'    => $stats->total_bookings > 0 ? round( ( $stats->completed_bookings / $stats->total_bookings ) * 100, 1 ) : 0,
            'profile_completion' => $this->profile['profile_completion'],
            'rating'             => floatval( $this->profile['rating'] ),
        );
    }

    /**
     * Get all workers.
     *
     * @since 1.0.0
     * @param array $args Query arguments.
     * @return array Array of worker objects.
     */
    public static function get_workers( $args = array() ) {
        $defaults = array(
            'number'     => 20,
            'offset'     => 0,
            'meta_query' => array(),
            'orderby'    => 'display_name',
            'order'      => 'ASC',
        );

        $args = wp_parse_args( $args, $defaults );
        $args['role'] = 'schedspot_worker';

        $users = get_users( $args );
        $workers = array();

        foreach ( $users as $user ) {
            $worker = new self( $user->ID );
            $workers[] = $worker;
        }

        return $workers;
    }

    /**
     * Create worker from user.
     *
     * @since 1.0.0
     * @param int   $user_id User ID.
     * @param array $profile_data Initial profile data.
     * @return SchedSpot_Worker|WP_Error Worker object or error.
     */
    public static function create_worker( $user_id, $profile_data = array() ) {
        $user = get_userdata( $user_id );

        if ( ! $user ) {
            return new WP_Error( 'user_not_found', __( 'User not found.', 'schedspot' ) );
        }

        // Add worker role
        $user->add_role( 'schedspot_worker' );

        // Create worker object
        $worker = new self( $user_id );

        // Update profile if data provided
        if ( ! empty( $profile_data ) ) {
            $worker->update_profile( $profile_data );
        }

        // Fire action hook
        do_action( 'schedspot_worker_created', $user_id, $profile_data );

        return $worker;
    }

    /**
     * Delete worker (remove role and clean up data).
     *
     * @since 1.0.0
     * @return bool True on success, false on failure.
     */
    public function delete() {
        if ( ! $this->id || ! $this->user ) {
            return false;
        }

        // Check if worker has active bookings
        global $wpdb;
        $active_bookings = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings 
             WHERE worker_id = %d AND status IN ('pending', 'confirmed', 'in_progress')",
            $this->id
        ) );

        if ( $active_bookings > 0 ) {
            return new WP_Error( 'worker_has_active_bookings', __( 'Cannot delete worker with active bookings.', 'schedspot' ) );
        }

        // Remove worker role
        $this->user->remove_role( 'schedspot_worker' );

        // Clean up worker-specific meta
        $meta_keys = array(
            'schedspot_bio', 'schedspot_skills', 'schedspot_hourly_rate', 'schedspot_service_areas',
            'schedspot_phone', 'schedspot_address', 'schedspot_experience_years', 'schedspot_certifications',
            'schedspot_languages', 'schedspot_availability_note', 'schedspot_is_available',
            'schedspot_rating', 'schedspot_total_bookings', 'schedspot_profile_completion'
        );

        foreach ( $meta_keys as $meta_key ) {
            delete_user_meta( $this->id, $meta_key );
        }

        // Clean up worker services and availability
        $wpdb->delete( $wpdb->prefix . 'schedspot_worker_services', array( 'worker_id' => $this->id ), array( '%d' ) );
        $wpdb->delete( $wpdb->prefix . 'schedspot_worker_availability', array( 'worker_id' => $this->id ), array( '%d' ) );

        // Fire action hook
        do_action( 'schedspot_worker_deleted', $this->id );

        return true;
    }

    /**
     * Check if user is a worker.
     *
     * @since 1.0.0
     * @param int $user_id User ID.
     * @return bool True if user is a worker, false otherwise.
     */
    public static function is_worker( $user_id ) {
        $user = get_userdata( $user_id );
        return $user && in_array( 'schedspot_worker', $user->roles );
    }

    /**
     * Get worker display data for API/frontend.
     *
     * @since 1.0.0
     * @return array Worker display data.
     */
    public function get_display_data() {
        if ( ! $this->user ) {
            return array();
        }

        return array(
            'id'                 => $this->id,
            'name'               => $this->user->display_name,
            'email'              => $this->user->user_email,
            'avatar_url'         => get_avatar_url( $this->id ),
            'bio'                => $this->profile['bio'],
            'skills'             => $this->profile['skills'],
            'hourly_rate'        => $this->profile['hourly_rate'],
            'service_areas'      => $this->profile['service_areas'],
            'experience_years'   => $this->profile['experience_years'],
            'languages'          => $this->profile['languages'],
            'rating'             => $this->profile['rating'],
            'total_bookings'     => $this->profile['total_bookings'],
            'profile_completion' => $this->profile['profile_completion'],
            'is_available'       => $this->profile['is_available'],
            'services'           => $this->get_services(),
            'member_since'       => date( 'Y-m-d', strtotime( $this->user->user_registered ) ),
        );
    }

    /**
     * Get available workers for a service at a specific date and time.
     *
     * @since 1.0.0
     * @param int    $service_id Service ID.
     * @param string $date       Booking date (Y-m-d format).
     * @param string $time       Booking time (H:i format).
     * @return array Available workers.
     */
    public static function get_available_workers( $service_id, $date, $time ) {
        // Get all workers with the schedspot_worker role
        $args = array(
            'role' => 'schedspot_worker',
            'meta_query' => array(
                array(
                    'key' => 'schedspot_is_available',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );

        $users = get_users( $args );
        $workers = array();

        foreach ( $users as $user ) {
            $worker_id = $user->ID;

            // Check if worker is assigned to this service
            $assigned_services = get_user_meta( $worker_id, 'schedspot_assigned_services', true );
            if ( ! $assigned_services || ! in_array( intval( $service_id ), $assigned_services ) ) {
                continue;
            }

            // Check worker availability for the specific date and time
            if ( ! self::check_worker_availability( $worker_id, $date, $time ) ) {
                continue;
            }

            // Get worker profile data
            $profile = get_user_meta( $worker_id, 'schedspot_worker_profile', true );
            if ( ! $profile ) {
                $profile = array();
            }

            $workers[] = array(
                'id' => $worker_id,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'avatar_url' => get_avatar_url( $worker_id ),
                'bio' => isset( $profile['bio'] ) ? $profile['bio'] : '',
                'hourly_rate' => isset( $profile['hourly_rate'] ) ? $profile['hourly_rate'] : 0,
                'rating' => isset( $profile['rating'] ) ? $profile['rating'] : 0,
                'total_bookings' => isset( $profile['total_bookings'] ) ? $profile['total_bookings'] : 0,
                'skills' => isset( $profile['skills'] ) ? $profile['skills'] : array(),
                'experience_years' => isset( $profile['experience_years'] ) ? $profile['experience_years'] : 0,
            );
        }

        return $workers;
    }

    /**
     * Check if a worker is available at a specific date and time.
     *
     * @since 1.0.0
     * @param int    $worker_id Worker ID.
     * @param string $date      Booking date (Y-m-d format).
     * @param string $time      Booking time (H:i format).
     * @return bool True if available, false otherwise.
     */
    public static function check_worker_availability( $worker_id, $date, $time ) {
        // Check if worker is generally available
        $is_available = get_user_meta( $worker_id, 'schedspot_is_available', true );
        if ( $is_available !== '1' ) {
            return false;
        }

        // Check for existing bookings at the same time
        global $wpdb;
        $existing_booking = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}schedspot_bookings
             WHERE worker_id = %d AND booking_date = %s AND start_time = %s AND status IN ('pending', 'confirmed')",
            $worker_id, $date, $time
        ) );

        return ! $existing_booking;
    }
}
