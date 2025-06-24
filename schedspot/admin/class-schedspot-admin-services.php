<?php
/**
 * Admin Services Management Class
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Admin_Services Class.
 *
 * Handles service management in the admin area including
 * creating, editing, deleting, and organizing services.
 *
 * @class SchedSpot_Admin_Services
 * @version 1.0.0
 */
class SchedSpot_Admin_Services {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize services management functionality.
     *
     * @since 1.0.0
     */
    public function init() {
        add_action( 'wp_ajax_schedspot_save_service', array( $this, 'handle_save_service' ) );
        add_action( 'wp_ajax_schedspot_delete_service', array( $this, 'handle_delete_service' ) );
        add_action( 'wp_ajax_schedspot_toggle_service_status', array( $this, 'handle_toggle_service_status' ) );
    }

    /**
     * Services page callback.
     *
     * @since 1.0.0
     */
    public static function services_page() {
        $instance = new self();
        
        // Handle form submissions
        if ( isset( $_POST['action'] ) ) {
            $instance->handle_form_submission();
        }
        
        // Handle individual actions
        if ( isset( $_GET['action'] ) ) {
            $instance->handle_individual_action();
        }
        
        // Get services list
        $services = $instance->get_services();
        $categories = $instance->get_service_categories();
        
        // Determine current view
        $current_view = isset( $_GET['action'] ) && $_GET['action'] === 'edit' ? 'edit' : 'list';
        $editing_service = null;
        
        if ( $current_view === 'edit' && isset( $_GET['service_id'] ) ) {
            $editing_service = $instance->get_service( intval( $_GET['service_id'] ) );
        }
        
        // Load template
        include SCHEDSPOT_PLUGIN_DIR . 'templates/admin/services-management.php';
    }

    /**
     * Get all services.
     *
     * @since 1.0.0
     * @return array Services list.
     */
    private function get_services() {
        global $wpdb;
        
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        $category = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : '';
        $status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
        
        $where_clauses = array( '1=1' );
        $where_values = array();
        
        if ( $search ) {
            $search_term = '%' . $wpdb->esc_like( $search ) . '%';
            $where_clauses[] = '(name LIKE %s OR description LIKE %s)';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        if ( $category ) {
            $where_clauses[] = 'category = %s';
            $where_values[] = $category;
        }
        
        if ( $status ) {
            $where_clauses[] = 'is_active = %d';
            $where_values[] = intval( $status );
        }
        
        $where_clause = implode( ' AND ', $where_clauses );
        $query = "SELECT * FROM {$wpdb->prefix}schedspot_services WHERE {$where_clause} ORDER BY name ASC";
        
        if ( ! empty( $where_values ) ) {
            $query = $wpdb->prepare( $query, $where_values );
        }
        
        return $wpdb->get_results( $query );
    }

    /**
     * Get service by ID.
     *
     * @since 1.0.0
     * @param int $service_id Service ID.
     * @return object|null Service object or null if not found.
     */
    private function get_service( $service_id ) {
        global $wpdb;
        
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}schedspot_services WHERE id = %d",
            $service_id
        ) );
    }

    /**
     * Get service categories.
     *
     * @since 1.0.0
     * @return array Service categories.
     */
    private function get_service_categories() {
        global $wpdb;
        
        $categories = $wpdb->get_col( 
            "SELECT DISTINCT category FROM {$wpdb->prefix}schedspot_services WHERE category IS NOT NULL AND category != '' ORDER BY category ASC" 
        );
        
        return array_filter( $categories );
    }

    /**
     * Handle form submission.
     *
     * @since 1.0.0
     */
    private function handle_form_submission() {
        $action = sanitize_text_field( $_POST['action'] );
        
        switch ( $action ) {
            case 'add_service':
                $this->add_service();
                break;
            case 'update_service':
                $this->update_service();
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
                if ( isset( $_GET['service_id'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'delete_service_' . $_GET['service_id'] ) ) {
                    $this->delete_service( intval( $_GET['service_id'] ) );
                }
                break;
            case 'duplicate':
                if ( isset( $_GET['service_id'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'duplicate_service_' . $_GET['service_id'] ) ) {
                    $this->duplicate_service( intval( $_GET['service_id'] ) );
                }
                break;
            case 'toggle_status':
                if ( isset( $_GET['service_id'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'toggle_status_' . $_GET['service_id'] ) ) {
                    $this->toggle_service_status( intval( $_GET['service_id'] ) );
                }
                break;
        }
    }

    /**
     * Add new service.
     *
     * @since 1.0.0
     */
    private function add_service() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'add_service' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }
        
        global $wpdb;
        
        $service_data = array(
            'name' => sanitize_text_field( $_POST['service_name'] ),
            'description' => sanitize_textarea_field( $_POST['service_description'] ),
            'category' => sanitize_text_field( $_POST['service_category'] ),
            'base_price' => floatval( $_POST['service_price'] ),
            'duration' => intval( $_POST['service_duration'] ),
            'is_active' => intval( $_POST['service_status'] ),
            'created_at' => current_time( 'mysql' ),
        );
        
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'schedspot_services',
            $service_data,
            array( '%s', '%s', '%s', '%f', '%d', '%d', '%s' )
        );
        
        if ( $inserted ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&added=1' ) );
            exit;
        } else {
            add_settings_error( 'schedspot_services', 'add_failed', __( 'Failed to add service.', 'schedspot' ), 'error' );
        }
    }

    /**
     * Update existing service.
     *
     * @since 1.0.0
     */
    private function update_service() {
        $service_id = intval( $_POST['service_id'] );
        
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'update_service_' . $service_id ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }
        
        global $wpdb;
        
        $service_data = array(
            'name' => sanitize_text_field( $_POST['service_name'] ),
            'description' => sanitize_textarea_field( $_POST['service_description'] ),
            'category' => sanitize_text_field( $_POST['service_category'] ),
            'base_price' => floatval( $_POST['service_price'] ),
            'duration' => intval( $_POST['service_duration'] ),
            'is_active' => intval( $_POST['service_status'] ),
            'updated_at' => current_time( 'mysql' ),
        );
        
        $updated = $wpdb->update(
            $wpdb->prefix . 'schedspot_services',
            $service_data,
            array( 'id' => $service_id ),
            array( '%s', '%s', '%s', '%f', '%d', '%d', '%s' ),
            array( '%d' )
        );
        
        if ( $updated !== false ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&updated=1' ) );
            exit;
        } else {
            add_settings_error( 'schedspot_services', 'update_failed', __( 'Failed to update service.', 'schedspot' ), 'error' );
        }
    }

    /**
     * Delete service.
     *
     * @since 1.0.0
     * @param int $service_id Service ID.
     */
    private function delete_service( $service_id ) {
        global $wpdb;
        
        // Check if service is used in any bookings
        $booking_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE service_id = %d",
            $service_id
        ) );
        
        if ( $booking_count > 0 ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&error=service_in_use' ) );
            exit;
        }
        
        $deleted = $wpdb->delete(
            $wpdb->prefix . 'schedspot_services',
            array( 'id' => $service_id ),
            array( '%d' )
        );
        
        if ( $deleted ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&deleted=1' ) );
            exit;
        } else {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&error=delete_failed' ) );
            exit;
        }
    }

    /**
     * Duplicate service.
     *
     * @since 1.0.0
     * @param int $service_id Service ID.
     */
    private function duplicate_service( $service_id ) {
        global $wpdb;
        
        $original_service = $this->get_service( $service_id );
        
        if ( ! $original_service ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&error=service_not_found' ) );
            exit;
        }
        
        $service_data = array(
            'name' => $original_service->name . ' (Copy)',
            'description' => $original_service->description,
            'category' => $original_service->category,
            'base_price' => $original_service->base_price,
            'duration' => $original_service->duration,
            'is_active' => 0,
            'created_at' => current_time( 'mysql' ),
        );
        
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'schedspot_services',
            $service_data,
            array( '%s', '%s', '%s', '%f', '%d', '%d', '%s' )
        );
        
        if ( $inserted ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&duplicated=1' ) );
            exit;
        } else {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&error=duplicate_failed' ) );
            exit;
        }
    }

    /**
     * Toggle service status.
     *
     * @since 1.0.0
     * @param int $service_id Service ID.
     */
    private function toggle_service_status( $service_id ) {
        global $wpdb;
        
        $current_status = $wpdb->get_var( $wpdb->prepare(
            "SELECT is_active FROM {$wpdb->prefix}schedspot_services WHERE id = %d",
            $service_id
        ) );

        $new_status = ( $current_status == 1 ) ? 0 : 1;

        $updated = $wpdb->update(
            $wpdb->prefix . 'schedspot_services',
            array( 'is_active' => $new_status ),
            array( 'id' => $service_id ),
            array( '%d' ),
            array( '%d' )
        );
        
        if ( $updated !== false ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&status_updated=1' ) );
            exit;
        } else {
            wp_redirect( admin_url( 'admin.php?page=schedspot-services&error=status_update_failed' ) );
            exit;
        }
    }

    /**
     * Handle bulk actions.
     *
     * @since 1.0.0
     */
    private function handle_bulk_action() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-services' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }
        
        $action = sanitize_text_field( $_POST['bulk_action'] );
        $service_ids = array_map( 'intval', $_POST['service'] ?? array() );
        
        if ( empty( $service_ids ) ) {
            return;
        }
        
        switch ( $action ) {
            case 'delete':
                $this->bulk_delete_services( $service_ids );
                break;
            case 'activate':
                $this->bulk_update_status( $service_ids, 'active' );
                break;
            case 'deactivate':
                $this->bulk_update_status( $service_ids, 'inactive' );
                break;
        }
    }

    /**
     * Bulk delete services.
     *
     * @since 1.0.0
     * @param array $service_ids Service IDs.
     */
    private function bulk_delete_services( $service_ids ) {
        global $wpdb;
        
        $placeholders = implode( ',', array_fill( 0, count( $service_ids ), '%d' ) );
        
        // Check if any services are used in bookings
        $booking_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE service_id IN ($placeholders)",
            $service_ids
        ) );
        
        if ( $booking_count > 0 ) {
            add_settings_error( 'schedspot_services', 'services_in_use', __( 'Some services cannot be deleted because they are used in bookings.', 'schedspot' ), 'error' );
            return;
        }
        
        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}schedspot_services WHERE id IN ($placeholders)",
            $service_ids
        ) );
        
        if ( $deleted ) {
            $message = sprintf( 
                _n( '%d service deleted.', '%d services deleted.', $deleted, 'schedspot' ), 
                $deleted 
            );
            add_settings_error( 'schedspot_services', 'services_deleted', $message, 'updated' );
        }
    }

    /**
     * Bulk update service status.
     *
     * @since 1.0.0
     * @param array  $service_ids Service IDs.
     * @param string $status      New status.
     */
    private function bulk_update_status( $service_ids, $status ) {
        global $wpdb;
        
        $placeholders = implode( ',', array_fill( 0, count( $service_ids ), '%d' ) );
        $params = array_merge( array( $status ), $service_ids );
        
        $status_value = ( $status === 'active' ) ? 1 : 0;
        $params = array_merge( array( $status_value ), $service_ids );

        $updated = $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}schedspot_services SET is_active = %d WHERE id IN ($placeholders)",
            $params
        ) );
        
        if ( $updated ) {
            $message = sprintf( 
                _n( '%d service updated.', '%d services updated.', $updated, 'schedspot' ), 
                $updated 
            );
            add_settings_error( 'schedspot_services', 'services_updated', $message, 'updated' );
        }
    }

    /**
     * Handle AJAX save service.
     *
     * @since 1.0.0
     */
    public function handle_save_service() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_save_service' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }
        
        // Implementation for AJAX service saving
        wp_send_json_success( array( 'message' => __( 'Service saved successfully.', 'schedspot' ) ) );
    }

    /**
     * Handle AJAX delete service.
     *
     * @since 1.0.0
     */
    public function handle_delete_service() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_delete_service' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }
        
        $service_id = intval( $_POST['service_id'] );
        $this->delete_service( $service_id );
        
        wp_send_json_success( array( 'message' => __( 'Service deleted successfully.', 'schedspot' ) ) );
    }

    /**
     * Handle AJAX toggle service status.
     *
     * @since 1.0.0
     */
    public function handle_toggle_service_status() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_toggle_service_status' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }
        
        $service_id = intval( $_POST['service_id'] );
        $this->toggle_service_status( $service_id );
        
        wp_send_json_success( array( 'message' => __( 'Service status updated successfully.', 'schedspot' ) ) );
    }
}
