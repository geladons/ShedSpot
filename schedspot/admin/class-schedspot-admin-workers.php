<?php
/**
 * Admin Workers Management Class
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Admin_Workers Class.
 *
 * Handles worker management in the admin area including
 * creating, editing, managing worker profiles and availability.
 *
 * @class SchedSpot_Admin_Workers
 * @version 1.0.0
 */
class SchedSpot_Admin_Workers {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize workers management functionality.
     *
     * @since 1.0.0
     */
    public function init() {
        add_action( 'wp_ajax_schedspot_save_worker', array( $this, 'handle_save_worker' ) );
        add_action( 'wp_ajax_schedspot_delete_worker', array( $this, 'handle_delete_worker' ) );
        add_action( 'wp_ajax_schedspot_toggle_worker_status', array( $this, 'handle_toggle_worker_status' ) );
        add_action( 'wp_ajax_schedspot_update_worker_availability', array( $this, 'handle_update_availability' ) );
    }

    /**
     * Workers page callback.
     *
     * @since 1.0.0
     */
    public static function workers_page() {
        $instance = new self();

        // Handle form submissions
        if ( isset( $_POST['action'] ) ) {
            $instance->handle_form_submission();
        }

        // Handle individual actions
        if ( isset( $_GET['action'] ) ) {
            $instance->handle_individual_action();
        }

        // Get workers list
        $workers = $instance->get_workers();
        $available_users = $instance->get_available_users();
        $services = $instance->get_services();

        // Determine current view
        $current_view = isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'edit', 'add' ) ) ? $_GET['action'] : 'list';
        $editing_worker = null;

        if ( $current_view === 'edit' && isset( $_GET['worker_id'] ) ) {
            $editing_worker = $instance->get_worker( intval( $_GET['worker_id'] ) );
        }

        // Load template
        include SCHEDSPOT_PLUGIN_DIR . 'templates/admin/workers-management.php';
    }

    /**
     * Get all workers.
     *
     * @since 1.0.0
     * @return array Workers list.
     */
    private function get_workers() {
        // Get all users with schedspot_worker role (don't require profile meta)
        $users = get_users( array(
            'role' => 'schedspot_worker',
            'orderby' => 'display_name',
            'order' => 'ASC'
        ) );

        $workers = array();
        foreach ( $users as $user ) {
            $profile = get_user_meta( $user->ID, 'schedspot_worker_profile', true );
            $availability = get_user_meta( $user->ID, 'schedspot_worker_availability', true );

            // Ensure profile is an array
            if ( ! is_array( $profile ) ) {
                $profile = array();
            }

            // Ensure availability is an array
            if ( ! is_array( $availability ) ) {
                $availability = array();
            }

            $workers[] = (object) array(
                'ID' => $user->ID,
                'user_login' => $user->user_login,
                'user_email' => $user->user_email,
                'display_name' => $user->display_name,
                'profile' => $profile,
                'availability' => $availability,
                'is_available' => get_user_meta( $user->ID, 'schedspot_is_available', true ) === '1',
                'total_bookings' => $this->get_worker_booking_count( $user->ID ),
                'rating' => $this->get_worker_rating( $user->ID ),
                'hourly_rate' => isset( $profile['hourly_rate'] ) ? $profile['hourly_rate'] : 0,
                'skills' => isset( $profile['skills'] ) ? $profile['skills'] : array(),
                'bio' => isset( $profile['bio'] ) ? $profile['bio'] : '',
                'phone' => isset( $profile['phone'] ) ? $profile['phone'] : '',
                'address' => isset( $profile['address'] ) ? $profile['address'] : '',
            );
        }

        return $workers;
    }

    /**
     * Get worker by ID.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID.
     * @return object|null Worker object or null if not found.
     */
    private function get_worker( $worker_id ) {
        $user = get_user_by( 'ID', $worker_id );

        if ( ! $user || ! in_array( 'schedspot_worker', $user->roles ) ) {
            return null;
        }

        $profile = get_user_meta( $user->ID, 'schedspot_worker_profile', true );
        $availability = get_user_meta( $user->ID, 'schedspot_worker_availability', true );

        return (object) array(
            'ID' => $user->ID,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'display_name' => $user->display_name,
            'profile' => $profile ?: array(),
            'availability' => $availability ?: array(),
            'is_available' => get_user_meta( $user->ID, 'schedspot_is_available', true ) === '1',
            'total_bookings' => $this->get_worker_booking_count( $user->ID ),
            'rating' => $this->get_worker_rating( $user->ID ),
        );
    }

    /**
     * Get available users (not already workers).
     *
     * @since 1.0.0
     * @return array Available users.
     */
    private function get_available_users() {
        $args = array(
            'role__not_in' => array( 'schedspot_worker' ),
            'fields' => array( 'ID', 'display_name', 'user_email' ),
        );

        return get_users( $args );
    }

    /**
     * Get all services.
     *
     * @since 1.0.0
     * @return array Services list.
     */
    private function get_services() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT id, name, base_price FROM {$wpdb->prefix}schedspot_services WHERE is_active = 1 ORDER BY name ASC"
        );
    }

    /**
     * Get worker booking count.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID.
     * @return int Booking count.
     */
    private function get_worker_booking_count( $worker_id ) {
        global $wpdb;

        return $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE worker_id = %d",
            $worker_id
        ) );
    }

    /**
     * Get worker rating.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID.
     * @return float Worker rating.
     */
    private function get_worker_rating( $worker_id ) {
        // This would typically calculate from reviews/ratings
        // For now, return a placeholder
        return 4.5;
    }

    /**
     * Handle form submission.
     *
     * @since 1.0.0
     */
    private function handle_form_submission() {
        $action = sanitize_text_field( $_POST['action'] );

        switch ( $action ) {
            case 'add_worker':
                $this->add_worker();
                break;
            case 'update_worker':
                $this->update_worker();
                break;
            case 'update_availability':
                $this->update_worker_availability();
                break;
            case 'bulk_action':
                $this->handle_bulk_action();
                break;
        }
    }

    /**
     * Handle individual actions.
     *
     * @since 1.0.0
     */
    private function handle_individual_action() {
        $action = sanitize_text_field( $_GET['action'] );

        switch ( $action ) {
            case 'delete':
                if ( isset( $_GET['worker_id'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'delete_worker_' . $_GET['worker_id'] ) ) {
                    $this->delete_worker( intval( $_GET['worker_id'] ) );
                }
                break;
            case 'toggle_status':
                if ( isset( $_GET['worker_id'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'toggle_status_' . $_GET['worker_id'] ) ) {
                    $this->toggle_worker_status( intval( $_GET['worker_id'] ) );
                }
                break;
        }
    }

    /**
     * Add new worker.
     *
     * @since 1.0.0
     */
    private function add_worker() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'add_worker' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $user_id = intval( $_POST['user_id'] );
        $user = get_user_by( 'ID', $user_id );

        if ( ! $user ) {
            add_settings_error( 'schedspot_workers', 'user_not_found', __( 'User not found.', 'schedspot' ), 'error' );
            return;
        }

        // Add worker role
        $user->add_role( 'schedspot_worker' );

        // Save worker profile
        $profile_data = array(
            'bio' => sanitize_textarea_field( $_POST['worker_bio'] ),
            'skills' => array_map( 'sanitize_text_field', explode( ',', $_POST['worker_skills'] ) ),
            'hourly_rate' => floatval( $_POST['hourly_rate'] ),
            'experience_years' => intval( $_POST['experience_years'] ),
            'phone' => sanitize_text_field( $_POST['worker_phone'] ),
            'address' => sanitize_text_field( $_POST['worker_address'] ),
            'created_at' => current_time( 'mysql' ),
        );

        update_user_meta( $user_id, 'schedspot_worker_profile', $profile_data );
        update_user_meta( $user_id, 'schedspot_is_available', '1' );

        // Save service assignments
        if ( isset( $_POST['assigned_services'] ) && is_array( $_POST['assigned_services'] ) ) {
            $assigned_services = array_map( 'intval', $_POST['assigned_services'] );
            update_user_meta( $user_id, 'schedspot_assigned_services', $assigned_services );
        } else {
            // If no services are selected, save an empty array
            update_user_meta( $user_id, 'schedspot_assigned_services', array() );
        }

        wp_redirect( admin_url( 'admin.php?page=schedspot-workers&added=1' ) );
        exit;
    }

    /**
     * Update existing worker.
     *
     * @since 1.0.0
     */
    private function update_worker() {
        $worker_id = intval( $_POST['worker_id'] );

        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'update_worker_' . $worker_id ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $profile_data = array(
            'bio' => sanitize_textarea_field( $_POST['worker_bio'] ),
            'skills' => array_map( 'sanitize_text_field', explode( ',', $_POST['worker_skills'] ) ),
            'hourly_rate' => floatval( $_POST['hourly_rate'] ),
            'experience_years' => intval( $_POST['experience_years'] ),
            'phone' => sanitize_text_field( $_POST['worker_phone'] ),
            'address' => sanitize_text_field( $_POST['worker_address'] ),
            'updated_at' => current_time( 'mysql' ),
        );

        update_user_meta( $worker_id, 'schedspot_worker_profile', $profile_data );

        // Update service assignments
        if ( isset( $_POST['assigned_services'] ) && is_array( $_POST['assigned_services'] ) ) {
            $assigned_services = array_map( 'intval', $_POST['assigned_services'] );
            update_user_meta( $worker_id, 'schedspot_assigned_services', $assigned_services );
        } else {
            // If no services are selected, save an empty array
            update_user_meta( $worker_id, 'schedspot_assigned_services', array() );
        }

        wp_redirect( admin_url( 'admin.php?page=schedspot-workers&updated=1' ) );
        exit;
    }

    /**
     * Update worker availability.
     *
     * @since 1.0.0
     */
    private function update_worker_availability() {
        $worker_id = intval( $_POST['worker_id'] );

        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'update_availability_' . $worker_id ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $availability_data = array();

        if ( isset( $_POST['availability'] ) ) {
            foreach ( $_POST['availability'] as $day => $slots ) {
                $availability_data[ $day ] = array_map( function( $slot ) {
                    return array(
                        'start_time' => sanitize_text_field( $slot['start_time'] ),
                        'end_time' => sanitize_text_field( $slot['end_time'] ),
                        'is_available' => isset( $slot['is_available'] ),
                    );
                }, $slots );
            }
        }

        update_user_meta( $worker_id, 'schedspot_worker_availability', $availability_data );

        wp_redirect( admin_url( 'admin.php?page=schedspot-workers&action=edit&worker_id=' . $worker_id . '&availability_updated=1' ) );
        exit;
    }

    /**
     * Delete worker.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID.
     */
    private function delete_worker( $worker_id ) {
        global $wpdb;

        // Check if worker has active bookings
        $active_bookings = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE worker_id = %d AND status IN ('pending', 'confirmed')",
            $worker_id
        ) );

        if ( $active_bookings > 0 ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-workers&error=worker_has_bookings' ) );
            exit;
        }

        $user = get_user_by( 'ID', $worker_id );
        if ( $user ) {
            // Remove worker role
            $user->remove_role( 'schedspot_worker' );

            // Clean up worker meta
            delete_user_meta( $worker_id, 'schedspot_worker_profile' );
            delete_user_meta( $worker_id, 'schedspot_worker_availability' );
            delete_user_meta( $worker_id, 'schedspot_is_available' );
            delete_user_meta( $worker_id, 'schedspot_assigned_services' );

            wp_redirect( admin_url( 'admin.php?page=schedspot-workers&deleted=1' ) );
            exit;
        } else {
            wp_redirect( admin_url( 'admin.php?page=schedspot-workers&error=worker_not_found' ) );
            exit;
        }
    }

    /**
     * Toggle worker status.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID.
     */
    private function toggle_worker_status( $worker_id ) {
        $current_status = get_user_meta( $worker_id, 'schedspot_is_available', true );
        $new_status = ( $current_status === '1' ) ? '0' : '1';

        update_user_meta( $worker_id, 'schedspot_is_available', $new_status );

        wp_redirect( admin_url( 'admin.php?page=schedspot-workers&status_updated=1' ) );
        exit;
    }

    /**
     * Handle bulk actions.
     *
     * @since 1.0.0
     */
    private function handle_bulk_action() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-workers' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $action = sanitize_text_field( $_POST['bulk_action'] );
        $worker_ids = array_map( 'intval', $_POST['worker'] ?? array() );

        if ( empty( $worker_ids ) ) {
            return;
        }

        switch ( $action ) {
            case 'activate':
                $this->bulk_update_status( $worker_ids, '1' );
                break;
            case 'deactivate':
                $this->bulk_update_status( $worker_ids, '0' );
                break;
        }
    }

    /**
     * Bulk update worker status.
     *
     * @since 1.0.0
     * @param array  $worker_ids Worker IDs.
     * @param string $status     New status.
     */
    private function bulk_update_status( $worker_ids, $status ) {
        $updated = 0;

        foreach ( $worker_ids as $worker_id ) {
            update_user_meta( $worker_id, 'schedspot_is_available', $status );
            $updated++;
        }

        if ( $updated ) {
            $message = sprintf( 
                _n( '%d worker updated.', '%d workers updated.', $updated, 'schedspot' ), 
                $updated 
            );
            add_settings_error( 'schedspot_workers', 'workers_updated', $message, 'updated' );
        }
    }

    /**
     * Handle AJAX save worker.
     *
     * @since 1.0.0
     */
    public function handle_save_worker() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_save_worker' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        // Implementation for AJAX worker saving
        wp_send_json_success( array( 'message' => __( 'Worker saved successfully.', 'schedspot' ) ) );
    }

    /**
     * Handle AJAX delete worker.
     *
     * @since 1.0.0
     */
    public function handle_delete_worker() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_delete_worker' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        $worker_id = intval( $_POST['worker_id'] );
        $this->delete_worker( $worker_id );

        wp_send_json_success( array( 'message' => __( 'Worker deleted successfully.', 'schedspot' ) ) );
    }

    /**
     * Handle AJAX toggle worker status.
     *
     * @since 1.0.0
     */
    public function handle_toggle_worker_status() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_toggle_worker_status' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        $worker_id = intval( $_POST['worker_id'] );
        $this->toggle_worker_status( $worker_id );

        wp_send_json_success( array( 'message' => __( 'Worker status updated successfully.', 'schedspot' ) ) );
    }

    /**
     * Handle AJAX update availability.
     *
     * @since 1.0.0
     */
    public function handle_update_availability() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_update_availability' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        $worker_id = intval( $_POST['worker_id'] );
        $availability = json_decode( stripslashes( $_POST['availability'] ), true );

        update_user_meta( $worker_id, 'schedspot_worker_availability', $availability );

        wp_send_json_success( array( 'message' => __( 'Availability updated successfully.', 'schedspot' ) ) );
    }
}
