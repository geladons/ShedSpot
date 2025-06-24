<?php
/**
 * Admin Analytics and Reporting Class
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Admin_Analytics Class.
 *
 * Handles analytics, reporting, and statistics in the admin area.
 *
 * @class SchedSpot_Admin_Analytics
 * @version 1.0.0
 */
class SchedSpot_Admin_Analytics {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize analytics functionality.
     *
     * @since 1.0.0
     */
    public function init() {
        add_action( 'wp_ajax_schedspot_refresh_stats', array( $this, 'handle_refresh_stats' ) );
        add_action( 'wp_ajax_schedspot_export_report', array( $this, 'handle_export_report' ) );
        add_action( 'wp_ajax_schedspot_get_chart_data', array( $this, 'handle_get_chart_data' ) );
    }

    /**
     * Analytics page callback.
     *
     * @since 1.0.0
     */
    public static function analytics_page() {
        $instance = new self();
        
        // Get date range from request
        $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : date( 'Y-m-01' );
        $date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : date( 'Y-m-t' );
        
        // Get analytics data
        $overview_stats = $instance->get_overview_stats( $date_from, $date_to );
        $booking_trends = $instance->get_booking_trends( $date_from, $date_to );
        $worker_performance = $instance->get_worker_performance( $date_from, $date_to );
        $service_popularity = $instance->get_service_popularity( $date_from, $date_to );
        $revenue_data = $instance->get_revenue_data( $date_from, $date_to );
        
        // Load template
        include SCHEDSPOT_PLUGIN_DIR . 'templates/admin/analytics.php';
    }

    /**
     * Get overview statistics.
     *
     * @since 1.0.0
     * @param string $date_from Start date.
     * @param string $date_to   End date.
     * @return array Overview statistics.
     */
    private function get_overview_stats( $date_from, $date_to ) {
        global $wpdb;
        
        $stats = array();
        
        // Total bookings
        $stats['total_bookings'] = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings 
             WHERE booking_date BETWEEN %s AND %s",
            $date_from, $date_to
        ) );
        
        // Confirmed bookings
        $stats['confirmed_bookings'] = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings 
             WHERE booking_date BETWEEN %s AND %s AND status = 'confirmed'",
            $date_from, $date_to
        ) );
        
        // Completed bookings
        $stats['completed_bookings'] = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings 
             WHERE booking_date BETWEEN %s AND %s AND status = 'completed'",
            $date_from, $date_to
        ) );
        
        // Cancelled bookings
        $stats['cancelled_bookings'] = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings 
             WHERE booking_date BETWEEN %s AND %s AND status = 'cancelled'",
            $date_from, $date_to
        ) );
        
        // Total revenue
        $stats['total_revenue'] = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(total_price) FROM {$wpdb->prefix}schedspot_bookings 
             WHERE booking_date BETWEEN %s AND %s AND status IN ('confirmed', 'completed')",
            $date_from, $date_to
        ) ) ?: 0;
        
        // Average booking value
        $stats['average_booking_value'] = $stats['confirmed_bookings'] > 0 ? 
            $stats['total_revenue'] / $stats['confirmed_bookings'] : 0;
        
        // Active workers
        $stats['active_workers'] = count( get_users( array(
            'role' => 'schedspot_worker',
            'meta_query' => array(
                array(
                    'key' => 'schedspot_is_available',
                    'value' => '1',
                    'compare' => '='
                )
            )
        ) ) );
        
        // Total customers
        $stats['total_customers'] = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT JSON_UNQUOTE(JSON_EXTRACT(client_details, '$.email'))) 
             FROM {$wpdb->prefix}schedspot_bookings 
             WHERE booking_date BETWEEN %s AND %s",
            $date_from, $date_to
        ) );
        
        return $stats;
    }

    /**
     * Get booking trends data.
     *
     * @since 1.0.0
     * @param string $date_from Start date.
     * @param string $date_to   End date.
     * @return array Booking trends data.
     */
    private function get_booking_trends( $date_from, $date_to ) {
        global $wpdb;
        
        $trends = $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(booking_date) as date, 
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
             FROM {$wpdb->prefix}schedspot_bookings 
             WHERE booking_date BETWEEN %s AND %s
             GROUP BY DATE(booking_date)
             ORDER BY date ASC",
            $date_from, $date_to
        ) );
        
        return $trends;
    }

    /**
     * Get worker performance data.
     *
     * @since 1.0.0
     * @param string $date_from Start date.
     * @param string $date_to   End date.
     * @return array Worker performance data.
     */
    private function get_worker_performance( $date_from, $date_to ) {
        global $wpdb;
        
        $performance = $wpdb->get_results( $wpdb->prepare(
            "SELECT b.worker_id,
                    u.display_name as worker_name,
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
                    SUM(CASE WHEN b.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
                    SUM(CASE WHEN b.status IN ('confirmed', 'completed') THEN b.total_price ELSE 0 END) as total_revenue,
                    AVG(CASE WHEN b.status IN ('confirmed', 'completed') THEN b.total_price ELSE NULL END) as avg_booking_value
             FROM {$wpdb->prefix}schedspot_bookings b
             LEFT JOIN {$wpdb->users} u ON b.worker_id = u.ID
             WHERE b.booking_date BETWEEN %s AND %s
             GROUP BY b.worker_id, u.display_name
             ORDER BY total_revenue DESC",
            $date_from, $date_to
        ) );
        
        return $performance;
    }

    /**
     * Get service popularity data.
     *
     * @since 1.0.0
     * @param string $date_from Start date.
     * @param string $date_to   End date.
     * @return array Service popularity data.
     */
    private function get_service_popularity( $date_from, $date_to ) {
        global $wpdb;
        
        $popularity = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.name as service_name,
                    COUNT(b.id) as booking_count,
                    SUM(CASE WHEN b.status IN ('confirmed', 'completed') THEN b.total_price ELSE 0 END) as total_revenue,
                    AVG(CASE WHEN b.status IN ('confirmed', 'completed') THEN b.total_price ELSE NULL END) as avg_price
             FROM {$wpdb->prefix}schedspot_bookings b
             LEFT JOIN {$wpdb->prefix}schedspot_services s ON b.service_id = s.id
             WHERE b.booking_date BETWEEN %s AND %s
             GROUP BY b.service_id, s.name
             ORDER BY booking_count DESC",
            $date_from, $date_to
        ) );
        
        return $popularity;
    }

    /**
     * Get revenue data.
     *
     * @since 1.0.0
     * @param string $date_from Start date.
     * @param string $date_to   End date.
     * @return array Revenue data.
     */
    private function get_revenue_data( $date_from, $date_to ) {
        global $wpdb;
        
        $revenue = $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(booking_date) as date,
                    SUM(CASE WHEN status IN ('confirmed', 'completed') THEN total_price ELSE 0 END) as daily_revenue,
                    COUNT(CASE WHEN status IN ('confirmed', 'completed') THEN 1 ELSE NULL END) as paid_bookings
             FROM {$wpdb->prefix}schedspot_bookings 
             WHERE booking_date BETWEEN %s AND %s
             GROUP BY DATE(booking_date)
             ORDER BY date ASC",
            $date_from, $date_to
        ) );
        
        return $revenue;
    }

    /**
     * Get conversion rates.
     *
     * @since 1.0.0
     * @param string $date_from Start date.
     * @param string $date_to   End date.
     * @return array Conversion rates.
     */
    private function get_conversion_rates( $date_from, $date_to ) {
        global $wpdb;
        
        $total_bookings = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings 
             WHERE booking_date BETWEEN %s AND %s",
            $date_from, $date_to
        ) );
        
        $confirmed_bookings = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings 
             WHERE booking_date BETWEEN %s AND %s AND status = 'confirmed'",
            $date_from, $date_to
        ) );
        
        $completed_bookings = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings 
             WHERE booking_date BETWEEN %s AND %s AND status = 'completed'",
            $date_from, $date_to
        ) );
        
        return array(
            'booking_to_confirmed' => $total_bookings > 0 ? ($confirmed_bookings / $total_bookings) * 100 : 0,
            'confirmed_to_completed' => $confirmed_bookings > 0 ? ($completed_bookings / $confirmed_bookings) * 100 : 0,
            'booking_to_completed' => $total_bookings > 0 ? ($completed_bookings / $total_bookings) * 100 : 0,
        );
    }

    /**
     * Handle refresh stats AJAX.
     *
     * @since 1.0.0
     */
    public function handle_refresh_stats() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_refresh_stats' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }
        
        $date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( $_POST['date_from'] ) : date( 'Y-m-01' );
        $date_to = isset( $_POST['date_to'] ) ? sanitize_text_field( $_POST['date_to'] ) : date( 'Y-m-t' );
        
        $stats = $this->get_overview_stats( $date_from, $date_to );
        
        ob_start();
        include SCHEDSPOT_PLUGIN_DIR . 'templates/admin/analytics-stats-widget.php';
        $html = ob_get_clean();
        
        wp_send_json_success( array( 'html' => $html ) );
    }

    /**
     * Handle export report AJAX.
     *
     * @since 1.0.0
     */
    public function handle_export_report() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_export_report' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }
        
        $report_type = sanitize_text_field( $_POST['report_type'] );
        $date_from = sanitize_text_field( $_POST['date_from'] );
        $date_to = sanitize_text_field( $_POST['date_to'] );
        
        $export_url = $this->generate_export_report( $report_type, $date_from, $date_to );
        
        if ( $export_url ) {
            wp_send_json_success( array( 
                'download_url' => $export_url,
                'message' => __( 'Report generated successfully.', 'schedspot' )
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to generate report.', 'schedspot' ) ) );
        }
    }

    /**
     * Handle get chart data AJAX.
     *
     * @since 1.0.0
     */
    public function handle_get_chart_data() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_get_chart_data' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }
        
        $chart_type = sanitize_text_field( $_POST['chart_type'] );
        $date_from = sanitize_text_field( $_POST['date_from'] );
        $date_to = sanitize_text_field( $_POST['date_to'] );
        
        $data = array();
        
        switch ( $chart_type ) {
            case 'booking_trends':
                $data = $this->get_booking_trends( $date_from, $date_to );
                break;
            case 'revenue_data':
                $data = $this->get_revenue_data( $date_from, $date_to );
                break;
            case 'service_popularity':
                $data = $this->get_service_popularity( $date_from, $date_to );
                break;
            case 'worker_performance':
                $data = $this->get_worker_performance( $date_from, $date_to );
                break;
        }
        
        wp_send_json_success( array( 'data' => $data ) );
    }

    /**
     * Generate export report.
     *
     * @since 1.0.0
     * @param string $report_type Report type.
     * @param string $date_from   Start date.
     * @param string $date_to     End date.
     * @return string|false Export URL or false on failure.
     */
    private function generate_export_report( $report_type, $date_from, $date_to ) {
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/schedspot-exports/';
        
        if ( ! file_exists( $export_dir ) ) {
            wp_mkdir_p( $export_dir );
        }
        
        $filename = 'schedspot-' . $report_type . '-' . $date_from . '-to-' . $date_to . '-' . time() . '.csv';
        $filepath = $export_dir . $filename;
        
        $data = array();
        
        switch ( $report_type ) {
            case 'bookings':
                $data = $this->get_bookings_export_data( $date_from, $date_to );
                break;
            case 'revenue':
                $data = $this->get_revenue_export_data( $date_from, $date_to );
                break;
            case 'workers':
                $data = $this->get_workers_export_data( $date_from, $date_to );
                break;
        }
        
        if ( $this->write_csv_file( $filepath, $data ) ) {
            return $upload_dir['baseurl'] . '/schedspot-exports/' . $filename;
        }
        
        return false;
    }

    /**
     * Get bookings export data.
     *
     * @since 1.0.0
     * @param string $date_from Start date.
     * @param string $date_to   End date.
     * @return array Export data.
     */
    private function get_bookings_export_data( $date_from, $date_to ) {
        global $wpdb;
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT b.id, b.booking_date, b.booking_time, b.status, b.total_price,
                    JSON_UNQUOTE(JSON_EXTRACT(b.client_details, '$.name')) as client_name,
                    JSON_UNQUOTE(JSON_EXTRACT(b.client_details, '$.email')) as client_email,
                    u.display_name as worker_name,
                    s.name as service_name,
                    b.created_at
             FROM {$wpdb->prefix}schedspot_bookings b
             LEFT JOIN {$wpdb->users} u ON b.worker_id = u.ID
             LEFT JOIN {$wpdb->prefix}schedspot_services s ON b.service_id = s.id
             WHERE b.booking_date BETWEEN %s AND %s
             ORDER BY b.created_at DESC",
            $date_from, $date_to
        ), ARRAY_A );
    }

    /**
     * Get revenue export data.
     *
     * @since 1.0.0
     * @param string $date_from Start date.
     * @param string $date_to   End date.
     * @return array Export data.
     */
    private function get_revenue_export_data( $date_from, $date_to ) {
        return $this->get_revenue_data( $date_from, $date_to );
    }

    /**
     * Get workers export data.
     *
     * @since 1.0.0
     * @param string $date_from Start date.
     * @param string $date_to   End date.
     * @return array Export data.
     */
    private function get_workers_export_data( $date_from, $date_to ) {
        return $this->get_worker_performance( $date_from, $date_to );
    }

    /**
     * Write CSV file.
     *
     * @since 1.0.0
     * @param string $filepath File path.
     * @param array  $data     Data to write.
     * @return bool Success status.
     */
    private function write_csv_file( $filepath, $data ) {
        if ( empty( $data ) ) {
            return false;
        }
        
        $file = fopen( $filepath, 'w' );
        
        if ( ! $file ) {
            return false;
        }
        
        // Write headers
        $headers = array_keys( (array) $data[0] );
        fputcsv( $file, $headers );
        
        // Write data
        foreach ( $data as $row ) {
            fputcsv( $file, (array) $row );
        }
        
        fclose( $file );
        
        return true;
    }
}
