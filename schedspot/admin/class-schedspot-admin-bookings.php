<?php
/**
 * Admin Bookings Management Class
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Admin_Bookings Class.
 *
 * Handles booking management in the admin area including
 * listing, editing, status updates, and booking details.
 *
 * @class SchedSpot_Admin_Bookings
 * @version 1.0.0
 */
class SchedSpot_Admin_Bookings {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize booking management functionality.
     *
     * @since 1.0.0
     */
    public function init() {
        add_action( 'wp_ajax_schedspot_update_booking_status', array( $this, 'handle_status_update' ) );
        add_action( 'wp_ajax_schedspot_delete_booking', array( $this, 'handle_booking_deletion' ) );
        add_action( 'wp_ajax_schedspot_refresh_bookings', array( $this, 'handle_refresh_bookings' ) );

        // Payment management AJAX handlers
        add_action( 'wp_ajax_schedspot_request_deposit', array( $this, 'handle_request_deposit' ) );
        add_action( 'wp_ajax_schedspot_mark_deposit_paid', array( $this, 'handle_mark_deposit_paid' ) );
        add_action( 'wp_ajax_schedspot_request_final_payment', array( $this, 'handle_request_final_payment' ) );
        add_action( 'wp_ajax_schedspot_generate_invoice', array( $this, 'handle_generate_invoice' ) );
        add_action( 'wp_ajax_schedspot_send_payment_reminder', array( $this, 'handle_send_payment_reminder' ) );
        add_action( 'wp_ajax_schedspot_process_refund', array( $this, 'handle_process_refund' ) );
    }

    /**
     * Bookings page callback.
     *
     * @since 1.0.0
     */
    public static function bookings_page() {
        $instance = new self();
        
        // Handle bulk actions
        if ( isset( $_POST['action'] ) && $_POST['action'] !== '-1' ) {
            $instance->handle_bulk_action();
        }

        // Handle individual booking actions
        if ( isset( $_GET['action'] ) && isset( $_GET['booking_id'] ) ) {
            $instance->handle_individual_action();
        }

        // Get bookings with filters
        $bookings = $instance->get_filtered_bookings();
        $total_bookings = $instance->get_bookings_count();
        
        // Load template
        include SCHEDSPOT_PLUGIN_DIR . 'templates/admin/bookings-list.php';
    }

    /**
     * Get filtered bookings.
     *
     * @since 1.0.0
     * @return array Filtered bookings.
     */
    private function get_filtered_bookings() {
        global $wpdb;
        
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;
        
        $where_clauses = array( '1=1' );
        $where_values = array();
        
        // Status filter
        if ( isset( $_GET['status'] ) && $_GET['status'] !== 'all' ) {
            $where_clauses[] = 'status = %s';
            $where_values[] = sanitize_text_field( $_GET['status'] );
        }
        
        // Date range filter
        if ( isset( $_GET['date_from'] ) && $_GET['date_from'] ) {
            $where_clauses[] = 'booking_date >= %s';
            $where_values[] = sanitize_text_field( $_GET['date_from'] );
        }
        
        if ( isset( $_GET['date_to'] ) && $_GET['date_to'] ) {
            $where_clauses[] = 'booking_date <= %s';
            $where_values[] = sanitize_text_field( $_GET['date_to'] );
        }
        
        // Search filter
        if ( isset( $_GET['s'] ) && $_GET['s'] ) {
            $search = '%' . $wpdb->esc_like( sanitize_text_field( $_GET['s'] ) ) . '%';
            $where_clauses[] = '(client_name LIKE %s OR client_email LIKE %s OR notes LIKE %s)';
            $where_values[] = $search;
            $where_values[] = $search;
            $where_values[] = $search;
        }
        
        $where_clause = implode( ' AND ', $where_clauses );
        
        $query = "SELECT * FROM {$wpdb->prefix}schedspot_bookings WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_values[] = $per_page;
        $where_values[] = $offset;
        
        if ( ! empty( $where_values ) ) {
            $query = $wpdb->prepare( $query, $where_values );
        }
        
        return $wpdb->get_results( $query );
    }

    /**
     * Get total bookings count.
     *
     * @since 1.0.0
     * @return int Total bookings count.
     */
    private function get_bookings_count() {
        global $wpdb;
        
        $where_clauses = array( '1=1' );
        $where_values = array();
        
        // Apply same filters as get_filtered_bookings
        if ( isset( $_GET['status'] ) && $_GET['status'] !== 'all' ) {
            $where_clauses[] = 'status = %s';
            $where_values[] = sanitize_text_field( $_GET['status'] );
        }
        
        if ( isset( $_GET['date_from'] ) && $_GET['date_from'] ) {
            $where_clauses[] = 'booking_date >= %s';
            $where_values[] = sanitize_text_field( $_GET['date_from'] );
        }
        
        if ( isset( $_GET['date_to'] ) && $_GET['date_to'] ) {
            $where_clauses[] = 'booking_date <= %s';
            $where_values[] = sanitize_text_field( $_GET['date_to'] );
        }
        
        if ( isset( $_GET['s'] ) && $_GET['s'] ) {
            $search = '%' . $wpdb->esc_like( sanitize_text_field( $_GET['s'] ) ) . '%';
            $where_clauses[] = '(client_name LIKE %s OR client_email LIKE %s OR notes LIKE %s)';
            $where_values[] = $search;
            $where_values[] = $search;
            $where_values[] = $search;
        }
        
        $where_clause = implode( ' AND ', $where_clauses );
        $query = "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE {$where_clause}";
        
        if ( ! empty( $where_values ) ) {
            $query = $wpdb->prepare( $query, $where_values );
        }
        
        return $wpdb->get_var( $query );
    }

    /**
     * Handle bulk actions.
     *
     * @since 1.0.0
     */
    private function handle_bulk_action() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-bookings' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }
        
        $action = sanitize_text_field( $_POST['action'] );
        $booking_ids = array_map( 'intval', $_POST['booking'] ?? array() );
        
        if ( empty( $booking_ids ) ) {
            return;
        }
        
        switch ( $action ) {
            case 'delete':
                $this->bulk_delete_bookings( $booking_ids );
                break;
            case 'confirm':
                $this->bulk_update_status( $booking_ids, 'confirmed' );
                break;
            case 'complete':
                $this->bulk_update_status( $booking_ids, 'completed' );
                break;
            case 'cancel':
                $this->bulk_update_status( $booking_ids, 'cancelled' );
                break;
        }
    }

    /**
     * Handle individual booking actions.
     *
     * @since 1.0.0
     */
    private function handle_individual_action() {
        $action = sanitize_text_field( $_GET['action'] );
        $booking_id = intval( $_GET['booking_id'] );
        
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'booking_action_' . $booking_id ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }
        
        switch ( $action ) {
            case 'view':
                $this->view_booking_details( $booking_id );
                break;
            case 'edit':
                $this->edit_booking( $booking_id );
                break;
            case 'delete':
                $this->delete_booking( $booking_id );
                break;
        }
    }

    /**
     * Bulk delete bookings.
     *
     * @since 1.0.0
     * @param array $booking_ids Booking IDs to delete.
     */
    private function bulk_delete_bookings( $booking_ids ) {
        global $wpdb;
        
        $placeholders = implode( ',', array_fill( 0, count( $booking_ids ), '%d' ) );
        $query = "DELETE FROM {$wpdb->prefix}schedspot_bookings WHERE id IN ($placeholders)";
        
        $deleted = $wpdb->query( $wpdb->prepare( $query, $booking_ids ) );
        
        if ( $deleted ) {
            $message = sprintf( 
                _n( '%d booking deleted.', '%d bookings deleted.', $deleted, 'schedspot' ), 
                $deleted 
            );
            add_settings_error( 'schedspot_bookings', 'bookings_deleted', $message, 'updated' );
        }
    }

    /**
     * Bulk update booking status.
     *
     * @since 1.0.0
     * @param array  $booking_ids Booking IDs to update.
     * @param string $status      New status.
     */
    private function bulk_update_status( $booking_ids, $status ) {
        global $wpdb;
        
        $placeholders = implode( ',', array_fill( 0, count( $booking_ids ), '%d' ) );
        $query = "UPDATE {$wpdb->prefix}schedspot_bookings SET status = %s WHERE id IN ($placeholders)";
        
        $params = array_merge( array( $status ), $booking_ids );
        $updated = $wpdb->query( $wpdb->prepare( $query, $params ) );
        
        if ( $updated ) {
            $message = sprintf( 
                _n( '%d booking updated.', '%d bookings updated.', $updated, 'schedspot' ), 
                $updated 
            );
            add_settings_error( 'schedspot_bookings', 'bookings_updated', $message, 'updated' );
        }
    }

    /**
     * View booking details.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     */
    private function view_booking_details( $booking_id ) {
        $booking = SchedSpot_Booking::get_booking_by_id( $booking_id );

        if ( ! $booking ) {
            wp_die( __( 'Booking not found.', 'schedspot' ) );
        }

        include SCHEDSPOT_PLUGIN_DIR . 'templates/admin/booking-details.php';
    }

    /**
     * Edit booking.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     */
    private function edit_booking( $booking_id ) {
        $booking = SchedSpot_Booking::get_booking_by_id( $booking_id );

        if ( ! $booking ) {
            wp_die( __( 'Booking not found.', 'schedspot' ) );
        }

        // Handle form submission
        if ( isset( $_POST['update_booking'] ) ) {
            $this->process_booking_update( $booking_id );
        }

        include SCHEDSPOT_PLUGIN_DIR . 'templates/admin/booking-edit.php';
    }

    /**
     * Delete booking.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     */
    private function delete_booking( $booking_id ) {
        global $wpdb;
        
        $deleted = $wpdb->delete( 
            $wpdb->prefix . 'schedspot_bookings', 
            array( 'id' => $booking_id ), 
            array( '%d' ) 
        );
        
        if ( $deleted ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-bookings&deleted=1' ) );
            exit;
        } else {
            wp_die( __( 'Failed to delete booking.', 'schedspot' ) );
        }
    }

    /**
     * Process booking update.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     */
    private function process_booking_update( $booking_id ) {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'update_booking_' . $booking_id ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }
        
        global $wpdb;
        
        $update_data = array(
            'status' => sanitize_text_field( $_POST['status'] ),
            'booking_date' => sanitize_text_field( $_POST['booking_date'] ),
            'start_time' => sanitize_text_field( $_POST['start_time'] ),
            'notes' => sanitize_textarea_field( $_POST['notes'] ),
        );
        
        $updated = $wpdb->update(
            $wpdb->prefix . 'schedspot_bookings',
            $update_data,
            array( 'id' => $booking_id ),
            array( '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );
        
        if ( $updated !== false ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-bookings&updated=1' ) );
            exit;
        } else {
            add_settings_error( 'schedspot_bookings', 'update_failed', __( 'Failed to update booking.', 'schedspot' ), 'error' );
        }
    }

    /**
     * Handle AJAX status update.
     *
     * @since 1.0.0
     */
    public function handle_status_update() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_booking_status' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }
        
        $booking_id = intval( $_POST['booking_id'] );
        $status = sanitize_text_field( $_POST['status'] );
        
        global $wpdb;
        $updated = $wpdb->update(
            $wpdb->prefix . 'schedspot_bookings',
            array( 'status' => $status ),
            array( 'id' => $booking_id ),
            array( '%s' ),
            array( '%d' )
        );
        
        if ( $updated !== false ) {
            wp_send_json_success( array( 
                'message' => __( 'Booking status updated successfully.', 'schedspot' ) 
            ) );
        } else {
            wp_send_json_error( array( 
                'message' => __( 'Failed to update booking status.', 'schedspot' ) 
            ) );
        }
    }

    /**
     * Handle booking deletion.
     *
     * @since 1.0.0
     */
    public function handle_booking_deletion() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_delete_booking' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }
        
        $booking_id = intval( $_POST['booking_id'] );
        
        global $wpdb;
        $deleted = $wpdb->delete( 
            $wpdb->prefix . 'schedspot_bookings', 
            array( 'id' => $booking_id ), 
            array( '%d' ) 
        );
        
        if ( $deleted ) {
            wp_send_json_success( array( 
                'message' => __( 'Booking deleted successfully.', 'schedspot' ) 
            ) );
        } else {
            wp_send_json_error( array( 
                'message' => __( 'Failed to delete booking.', 'schedspot' ) 
            ) );
        }
    }

    /**
     * Handle refresh bookings AJAX.
     *
     * @since 1.0.0
     */
    public function handle_refresh_bookings() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_refresh_bookings' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }
        
        $bookings = $this->get_filtered_bookings();
        
        ob_start();
        foreach ( $bookings as $booking ) {
            include SCHEDSPOT_PLUGIN_DIR . 'templates/admin/booking-row.php';
        }
        $html = ob_get_clean();
        
        wp_send_json_success( array( 'html' => $html ) );
    }

    /**
     * Handle deposit request.
     *
     * @since 1.0.0
     */
    public function handle_request_deposit() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_payment_action' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        $booking_id = intval( $_POST['booking_id'] );
        $booking = SchedSpot_Booking::get_booking_by_id( $booking_id );

        if ( ! $booking ) {
            wp_send_json_error( array( 'message' => __( 'Booking not found.', 'schedspot' ) ) );
        }

        // Calculate deposit amount (30% of total cost)
        $deposit_amount = $booking->total_cost * 0.3;

        // Update booking with deposit amount
        $booking->update( array( 'deposit_amount' => $deposit_amount ) );

        // Mark deposit as requested
        update_post_meta( $booking_id, 'schedspot_deposit_requested', current_time( 'mysql' ) );

        // Send notification to client (integrate with existing notification system)
        do_action( 'schedspot_deposit_requested', $booking_id, $deposit_amount );

        wp_send_json_success( array(
            'message' => __( 'Deposit request sent successfully.', 'schedspot' ),
            'deposit_amount' => $deposit_amount
        ) );
    }

    /**
     * Handle mark deposit as paid.
     *
     * @since 1.0.0
     */
    public function handle_mark_deposit_paid() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_payment_action' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        $booking_id = intval( $_POST['booking_id'] );
        $booking = SchedSpot_Booking::get_booking_by_id( $booking_id );

        if ( ! $booking ) {
            wp_send_json_error( array( 'message' => __( 'Booking not found.', 'schedspot' ) ) );
        }

        // Mark deposit as paid
        update_post_meta( $booking_id, 'schedspot_deposit_paid', current_time( 'mysql' ) );
        update_post_meta( $booking_id, 'schedspot_payment_status', 'partial' );

        // Fire action hook
        do_action( 'schedspot_deposit_paid', $booking_id );

        wp_send_json_success( array(
            'message' => __( 'Deposit marked as paid.', 'schedspot' )
        ) );
    }

    /**
     * Handle final payment request.
     *
     * @since 1.0.0
     */
    public function handle_request_final_payment() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_payment_action' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        $booking_id = intval( $_POST['booking_id'] );
        $booking = SchedSpot_Booking::get_booking_by_id( $booking_id );

        if ( ! $booking ) {
            wp_send_json_error( array( 'message' => __( 'Booking not found.', 'schedspot' ) ) );
        }

        // Calculate remaining amount
        $remaining_amount = $booking->total_cost - $booking->deposit_amount;

        // Mark final payment as requested
        update_post_meta( $booking_id, 'schedspot_final_payment_requested', current_time( 'mysql' ) );
        update_post_meta( $booking_id, 'schedspot_remaining_amount', $remaining_amount );

        // Send notification to client
        do_action( 'schedspot_final_payment_requested', $booking_id, $remaining_amount );

        wp_send_json_success( array(
            'message' => __( 'Final payment request sent successfully.', 'schedspot' ),
            'remaining_amount' => $remaining_amount
        ) );
    }

    /**
     * Handle invoice generation.
     *
     * @since 1.7.0
     */
    public function handle_generate_invoice() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_payment_action' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        $booking_id = intval( $_POST['booking_id'] );
        $invoice_type = sanitize_text_field( $_POST['invoice_type'] ); // 'deposit' or 'final'

        $booking = SchedSpot_Booking::get_booking_by_id( $booking_id );

        if ( ! $booking ) {
            wp_send_json_error( array( 'message' => __( 'Booking not found.', 'schedspot' ) ) );
        }

        // Generate invoice
        $invoice_data = $this->generate_invoice_data( $booking, $invoice_type );
        $invoice_pdf = $this->create_invoice_pdf( $invoice_data );

        if ( $invoice_pdf ) {
            // Send invoice via email
            $this->send_invoice_email( $booking, $invoice_pdf, $invoice_type );

            // Store invoice reference
            $invoice_meta_key = $invoice_type === 'deposit' ? 'schedspot_deposit_invoice' : 'schedspot_final_invoice';
            update_post_meta( $booking_id, $invoice_meta_key, $invoice_pdf );

            wp_send_json_success( array(
                'message' => __( 'Invoice generated and sent successfully.', 'schedspot' ),
                'invoice_url' => $invoice_pdf
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to generate invoice.', 'schedspot' ) ) );
        }
    }

    /**
     * Handle payment reminder.
     *
     * @since 1.7.0
     */
    public function handle_send_payment_reminder() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_payment_action' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        $booking_id = intval( $_POST['booking_id'] );
        $reminder_type = sanitize_text_field( $_POST['reminder_type'] ); // 'deposit' or 'final'

        $booking = SchedSpot_Booking::get_booking_by_id( $booking_id );

        if ( ! $booking ) {
            wp_send_json_error( array( 'message' => __( 'Booking not found.', 'schedspot' ) ) );
        }

        // Send payment reminder email
        $this->send_payment_reminder_email( $booking, $reminder_type );

        // Update reminder count
        $reminder_count_key = $reminder_type === 'deposit' ? 'schedspot_deposit_reminders' : 'schedspot_final_reminders';
        $current_count = intval( get_post_meta( $booking_id, $reminder_count_key, true ) );
        update_post_meta( $booking_id, $reminder_count_key, $current_count + 1 );
        update_post_meta( $booking_id, $reminder_count_key . '_last_sent', current_time( 'mysql' ) );

        wp_send_json_success( array(
            'message' => __( 'Payment reminder sent successfully.', 'schedspot' )
        ) );
    }

    /**
     * Handle refund processing.
     *
     * @since 1.7.0
     */
    public function handle_process_refund() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_payment_action' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        $booking_id = intval( $_POST['booking_id'] );
        $refund_amount = floatval( $_POST['refund_amount'] );
        $refund_reason = sanitize_textarea_field( $_POST['refund_reason'] );

        $booking = SchedSpot_Booking::get_booking_by_id( $booking_id );

        if ( ! $booking ) {
            wp_send_json_error( array( 'message' => __( 'Booking not found.', 'schedspot' ) ) );
        }

        // Process refund through WooCommerce if order exists
        $wc_order_id = get_post_meta( $booking_id, 'schedspot_wc_order_id', true );

        if ( $wc_order_id && class_exists( 'WooCommerce' ) ) {
            $order = wc_get_order( $wc_order_id );

            if ( $order ) {
                // Create WooCommerce refund
                $refund = wc_create_refund( array(
                    'order_id' => $wc_order_id,
                    'amount' => $refund_amount,
                    'reason' => $refund_reason,
                ) );

                if ( is_wp_error( $refund ) ) {
                    wp_send_json_error( array( 'message' => $refund->get_error_message() ) );
                }
            }
        }

        // Update booking status and refund information
        $booking->update( array( 'status' => 'refunded' ) );
        update_post_meta( $booking_id, 'schedspot_refund_amount', $refund_amount );
        update_post_meta( $booking_id, 'schedspot_refund_reason', $refund_reason );
        update_post_meta( $booking_id, 'schedspot_refund_date', current_time( 'mysql' ) );

        // Send refund confirmation email
        $this->send_refund_confirmation_email( $booking, $refund_amount, $refund_reason );

        wp_send_json_success( array(
            'message' => __( 'Refund processed successfully.', 'schedspot' )
        ) );
    }

    /**
     * Generate invoice data.
     *
     * @since 1.7.0
     * @param object $booking Booking object.
     * @param string $type Invoice type.
     * @return array Invoice data.
     */
    private function generate_invoice_data( $booking, $type ) {
        $invoice_number = 'SS-' . $booking->id . '-' . strtoupper( $type ) . '-' . date( 'Ymd' );

        $amount = $type === 'deposit' ? $booking->deposit_amount : ( $booking->total_cost - $booking->deposit_amount );

        return array(
            'invoice_number' => $invoice_number,
            'booking_id' => $booking->id,
            'client_name' => $booking->client_name,
            'client_email' => $booking->client_email,
            'service_name' => $booking->service_name,
            'booking_date' => $booking->booking_date,
            'start_time' => $booking->start_time,
            'duration' => $booking->duration,
            'amount' => $amount,
            'type' => $type,
            'due_date' => date( 'Y-m-d', strtotime( '+7 days' ) ),
            'created_date' => current_time( 'Y-m-d' ),
        );
    }

    /**
     * Create invoice PDF.
     *
     * @since 1.7.0
     * @param array $invoice_data Invoice data.
     * @return string|false PDF file path or false on failure.
     */
    private function create_invoice_pdf( $invoice_data ) {
        // This is a simplified version - in production, you'd use a PDF library like TCPDF or DOMPDF
        $upload_dir = wp_upload_dir();
        $invoice_dir = $upload_dir['basedir'] . '/schedspot-invoices/';

        if ( ! file_exists( $invoice_dir ) ) {
            wp_mkdir_p( $invoice_dir );
        }

        $filename = $invoice_data['invoice_number'] . '.pdf';
        $file_path = $invoice_dir . $filename;

        // For now, create a simple HTML file (in production, convert to PDF)
        $html_content = $this->generate_invoice_html( $invoice_data );
        file_put_contents( str_replace( '.pdf', '.html', $file_path ), $html_content );

        return $upload_dir['baseurl'] . '/schedspot-invoices/' . str_replace( '.pdf', '.html', $filename );
    }

    /**
     * Generate invoice HTML.
     *
     * @since 1.7.0
     * @param array $invoice_data Invoice data.
     * @return string Invoice HTML.
     */
    private function generate_invoice_html( $invoice_data ) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Invoice <?php echo esc_html( $invoice_data['invoice_number'] ); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .header { text-align: center; margin-bottom: 40px; }
                .invoice-details { margin-bottom: 30px; }
                .invoice-table { width: 100%; border-collapse: collapse; }
                .invoice-table th, .invoice-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
                .total { font-weight: bold; font-size: 18px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
                <h2>Invoice</h2>
            </div>

            <div class="invoice-details">
                <p><strong>Invoice Number:</strong> <?php echo esc_html( $invoice_data['invoice_number'] ); ?></p>
                <p><strong>Date:</strong> <?php echo esc_html( $invoice_data['created_date'] ); ?></p>
                <p><strong>Due Date:</strong> <?php echo esc_html( $invoice_data['due_date'] ); ?></p>
                <p><strong>Client:</strong> <?php echo esc_html( $invoice_data['client_name'] ); ?></p>
                <p><strong>Email:</strong> <?php echo esc_html( $invoice_data['client_email'] ); ?></p>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo esc_html( $invoice_data['service_name'] ); ?></td>
                        <td><?php echo esc_html( date( 'F j, Y', strtotime( $invoice_data['booking_date'] ) ) ); ?></td>
                        <td><?php echo esc_html( date( 'g:i A', strtotime( $invoice_data['start_time'] ) ) ); ?></td>
                        <td><?php echo esc_html( $invoice_data['duration'] ); ?> minutes</td>
                        <td class="total">$<?php echo esc_html( number_format( $invoice_data['amount'], 2 ) ); ?></td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top: 30px;">
                <p><strong>Payment Type:</strong> <?php echo esc_html( ucfirst( $invoice_data['type'] ) ); ?> Payment</p>
                <p><strong>Total Amount Due:</strong> $<?php echo esc_html( number_format( $invoice_data['amount'], 2 ) ); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Send invoice email.
     *
     * @since 1.7.0
     * @param object $booking Booking object.
     * @param string $invoice_url Invoice URL.
     * @param string $type Invoice type.
     */
    private function send_invoice_email( $booking, $invoice_url, $type ) {
        $to = $booking->client_email;
        $subject = sprintf( __( 'Invoice for your %s booking - %s', 'schedspot' ), $type, get_bloginfo( 'name' ) );

        $message = sprintf( __( 'Dear %s,

Please find attached your %s payment invoice for the following booking:

Service: %s
Date: %s
Time: %s
Duration: %s minutes

You can view your invoice here: %s

Please complete your payment by the due date shown on the invoice.

If you have any questions, please don\'t hesitate to contact us.

Best regards,
%s Team', 'schedspot' ),
            $booking->client_name,
            $type,
            $booking->service_name,
            date( 'F j, Y', strtotime( $booking->booking_date ) ),
            date( 'g:i A', strtotime( $booking->start_time ) ),
            $booking->duration,
            $invoice_url,
            get_bloginfo( 'name' )
        );

        wp_mail( $to, $subject, $message );
    }

    /**
     * Send payment reminder email.
     *
     * @since 1.7.0
     * @param object $booking Booking object.
     * @param string $type Reminder type.
     */
    private function send_payment_reminder_email( $booking, $type ) {
        $to = $booking->client_email;
        $subject = sprintf( __( 'Payment Reminder - %s', 'schedspot' ), get_bloginfo( 'name' ) );

        $amount = $type === 'deposit' ? $booking->deposit_amount : ( $booking->total_cost - $booking->deposit_amount );

        $message = sprintf( __( 'Dear %s,

This is a friendly reminder that your %s payment for the following booking is still pending:

Service: %s
Date: %s
Time: %s
Amount Due: $%s

Please complete your payment as soon as possible to secure your booking.

If you have already made the payment, please disregard this message.

Best regards,
%s Team', 'schedspot' ),
            $booking->client_name,
            $type,
            $booking->service_name,
            date( 'F j, Y', strtotime( $booking->booking_date ) ),
            date( 'g:i A', strtotime( $booking->start_time ) ),
            number_format( $amount, 2 ),
            get_bloginfo( 'name' )
        );

        wp_mail( $to, $subject, $message );
    }

    /**
     * Send refund confirmation email.
     *
     * @since 1.7.0
     * @param object $booking Booking object.
     * @param float $refund_amount Refund amount.
     * @param string $refund_reason Refund reason.
     */
    private function send_refund_confirmation_email( $booking, $refund_amount, $refund_reason ) {
        $to = $booking->client_email;
        $subject = sprintf( __( 'Refund Confirmation - %s', 'schedspot' ), get_bloginfo( 'name' ) );

        $message = sprintf( __( 'Dear %s,

We have processed a refund for your booking:

Service: %s
Date: %s
Refund Amount: $%s
Reason: %s

The refund will appear in your original payment method within 3-5 business days.

If you have any questions about this refund, please contact us.

Best regards,
%s Team', 'schedspot' ),
            $booking->client_name,
            $booking->service_name,
            date( 'F j, Y', strtotime( $booking->booking_date ) ),
            number_format( $refund_amount, 2 ),
            $refund_reason,
            get_bloginfo( 'name' )
        );

        wp_mail( $to, $subject, $message );
    }
}
