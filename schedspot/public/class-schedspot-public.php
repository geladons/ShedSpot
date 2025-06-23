<?php
/**
 * Public Class
 *
 * @package SchedSpot
 * @version 0.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Public Class.
 *
 * @class SchedSpot_Public
 * @version 0.1.0
 */
class SchedSpot_Public {

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize public functionality.
     *
     * @since 0.1.0
     */
    public function init() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        // Legacy AJAX handlers kept for backward compatibility
        add_action( 'wp_ajax_schedspot_check_availability', array( $this, 'ajax_check_availability' ) );
        add_action( 'wp_ajax_nopriv_schedspot_check_availability', array( $this, 'ajax_check_availability' ) );
        add_action( 'wp_ajax_schedspot_get_workers', array( $this, 'ajax_get_workers' ) );
        add_action( 'wp_ajax_nopriv_schedspot_get_workers', array( $this, 'ajax_get_workers' ) );
    }

    /**
     * Enqueue public scripts and styles.
     *
     * @since 0.1.0
     */
    public function enqueue_scripts() {
        // Only enqueue on pages that contain SchedSpot shortcodes
        if ( $this->has_schedspot_shortcode() ) {
            wp_enqueue_script( 'jquery' );

            // Enqueue main frontend CSS
            wp_enqueue_style( 'schedspot-frontend-enhanced', SCHEDSPOT_PLUGIN_URL . 'assets/css/frontend-enhanced.css', array(), SCHEDSPOT_VERSION );

            // Enqueue main frontend JavaScript
            wp_enqueue_script( 'schedspot-frontend', SCHEDSPOT_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), SCHEDSPOT_VERSION, true );

            // Localize script with data
            wp_localize_script( 'schedspot-frontend', 'schedspot_frontend', array(
                'rest_url' => rest_url( 'schedspot/v1/' ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'default_avatar' => get_avatar_url( 0 ),
                'current_user_id' => get_current_user_id(),
                'payment_url' => home_url( '/checkout/' ), // WooCommerce checkout URL
                'strings' => array(
                    'any_worker' => __( 'Any available worker', 'schedspot' ),
                    'loading_workers' => __( 'Loading workers...', 'schedspot' ),
                    'error_loading_workers' => __( 'Error loading workers. Please try again.', 'schedspot' ),
                    'no_workers_available' => __( 'No workers available for this service.', 'schedspot' ),
                    'select_worker' => __( 'Select Worker', 'schedspot' ),
                    'error_checking_availability' => __( 'Error checking availability. Please try again.', 'schedspot' ),
                    'field_required' => __( 'This field is required.', 'schedspot' ),
                    'invalid_email' => __( 'Please enter a valid email address.', 'schedspot' ),
                    'processing' => __( 'Processing...', 'schedspot' ),
                    'submit_booking' => __( 'Submit Booking', 'schedspot' ),
                    'error_submitting_form' => __( 'Error submitting form. Please try again.', 'schedspot' ),
                ),
            ) );

            // Add legacy inline JavaScript for backward compatibility
            wp_add_inline_script( 'jquery', $this->get_inline_javascript() );
        }
    }

    /**
     * Check if current page has SchedSpot shortcodes.
     *
     * @since 0.1.0
     * @return bool True if page has SchedSpot shortcodes.
     */
    private function has_schedspot_shortcode() {
        global $post;
        
        if ( ! is_a( $post, 'WP_Post' ) ) {
            return false;
        }
        
        $shortcodes = array(
            'schedspot_booking_form',
            'schedspot_service_list',
            'schedspot_dashboard',
            'schedspot_messages',
        );
        
        foreach ( $shortcodes as $shortcode ) {
            if ( has_shortcode( $post->post_content, $shortcode ) ) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * AJAX handler for checking availability.
     *
     * @since 0.1.0
     */
    public function ajax_check_availability() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_ajax_nonce' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $worker_id = absint( $_POST['worker_id'] );
        $date = sanitize_text_field( $_POST['date'] );
        $start_time = sanitize_text_field( $_POST['start_time'] );
        $duration = absint( $_POST['duration'] );

        // Calculate end time
        $start_datetime = new DateTime( $date . ' ' . $start_time );
        $end_datetime = clone $start_datetime;
        $end_datetime->add( new DateInterval( 'PT' . $duration . 'M' ) );
        $end_time = $end_datetime->format( 'H:i:s' );

        $is_available = true;
        $message = __( 'Time slot is available.', 'schedspot' );

        if ( $worker_id > 0 ) {
            // Check specific worker availability
            $conflict = SchedSpot_Booking::check_booking_conflict( $worker_id, $date, $start_time, $end_time );
            if ( $conflict ) {
                $is_available = false;
                $message = __( 'Selected worker is not available at this time.', 'schedspot' );
            }
        } else {
            // Check if any worker is available
            $workers = get_users( array( 'role' => 'schedspot_worker' ) );
            $available_workers = array();

            foreach ( $workers as $worker ) {
                $conflict = SchedSpot_Booking::check_booking_conflict( $worker->ID, $date, $start_time, $end_time );
                if ( ! $conflict ) {
                    $available_workers[] = $worker;
                }
            }

            if ( empty( $available_workers ) ) {
                $is_available = false;
                $message = __( 'No workers are available at this time.', 'schedspot' );
            } else {
                $message = sprintf( __( '%d worker(s) available for this time slot.', 'schedspot' ), count( $available_workers ) );
            }
        }

        wp_send_json_success( array(
            'available' => $is_available,
            'message'   => $message,
        ) );
    }

    /**
     * AJAX handler for getting workers by service.
     *
     * @since 0.1.0
     */
    public function ajax_get_workers() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_ajax_nonce' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $service_id = absint( $_POST['service_id'] );
        $workers = array();

        if ( $service_id > 0 ) {
            // Get workers who offer this service
            global $wpdb;
            $worker_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT worker_id FROM {$wpdb->prefix}schedspot_worker_services 
                 WHERE service_id = %d AND is_enabled = 1",
                $service_id
            ) );

            if ( ! empty( $worker_ids ) ) {
                $users = get_users( array(
                    'include' => $worker_ids,
                    'role'    => 'schedspot_worker',
                ) );

                foreach ( $users as $user ) {
                    $workers[] = array(
                        'id'   => $user->ID,
                        'name' => $user->display_name,
                    );
                }
            }
        } else {
            // Get all workers
            $users = get_users( array( 'role' => 'schedspot_worker' ) );
            foreach ( $users as $user ) {
                $workers[] = array(
                    'id'   => $user->ID,
                    'name' => $user->display_name,
                );
            }
        }

        wp_send_json_success( array(
            'workers' => $workers,
        ) );
    }

    /**
     * Get inline JavaScript for public functionality.
     *
     * @since 0.1.0
     * @return string JavaScript code.
     */
    private function get_inline_javascript() {
        $rest_url = rest_url( 'schedspot/v1/' );
        $nonce = wp_create_nonce( 'wp_rest' );

        return "
        jQuery(document).ready(function($) {
            // Service selection change handler
            $('#schedspot_service_id').on('change', function() {
                var serviceId = $(this).val();
                var workerSelect = $('#schedspot_worker_id');

                if (serviceId) {
                    $.ajax({
                        url: '{$rest_url}workers',
                        method: 'GET',
                        data: { service_id: serviceId },
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', '{$nonce}');
                        },
                        success: function(workers) {
                            workerSelect.empty();
                            workerSelect.append('<option value=\"\">" . __( 'Any available worker', 'schedspot' ) . "</option>');

                            $.each(workers, function(index, worker) {
                                workerSelect.append('<option value=\"' + worker.id + '\">' + worker.name + '</option>');
                            });
                        }
                    });
                }
            });

            // Availability check on date/time change
            $('#schedspot_booking_date, #schedspot_start_time, #schedspot_duration, #schedspot_worker_id').on('change', function() {
                checkAvailability();
            });

            function checkAvailability() {
                var date = $('#schedspot_booking_date').val();
                var startTime = $('#schedspot_start_time').val();
                var duration = $('#schedspot_duration').val();
                var workerId = $('#schedspot_worker_id').val();

                if (date && startTime && duration) {
                    $.ajax({
                        url: '{$rest_url}availability/check',
                        method: 'POST',
                        data: {
                            worker_id: workerId,
                            date: date,
                            start_time: startTime,
                            duration: duration
                        },
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', '{$nonce}');
                        },
                        success: function(response) {
                            var messageClass = response.available ? 'schedspot-notice-success' : 'schedspot-notice-error';
                            var messageHtml = '<div class=\"schedspot-notice ' + messageClass + '\">' + response.message + '</div>';

                            $('.schedspot-availability-message').remove();
                            $('#schedspot-booking-form').prepend(messageHtml);
                        }
                    });
                }
            }

            // Form validation
            $('#schedspot-booking-form').on('submit', function(e) {
                var isValid = true;
                var errorMessages = [];

                // Check required fields
                $(this).find('[required]').each(function() {
                    if (!$(this).val()) {
                        isValid = false;
                        var label = $(this).closest('.schedspot-form-row').find('label').text().replace('*', '').trim();
                        errorMessages.push('" . __( 'Please fill in the', 'schedspot' ) . " ' + label + ' " . __( 'field.', 'schedspot' ) . "');
                    }
                });

                // Check date is not in the past
                var selectedDate = new Date($('#schedspot_booking_date').val());
                var today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    isValid = false;
                    errorMessages.push('" . __( 'Please select a future date.', 'schedspot' ) . "');
                }

                if (!isValid) {
                    e.preventDefault();
                    var errorHtml = '<div class=\"schedspot-notice schedspot-notice-error\"><ul>';
                    $.each(errorMessages, function(index, message) {
                        errorHtml += '<li>' + message + '</li>';
                    });
                    errorHtml += '</ul></div>';
                    
                    $('.schedspot-notice').remove();
                    $('#schedspot-booking-form').prepend(errorHtml);
                    
                    $('html, body').animate({
                        scrollTop: $('#schedspot-booking-form').offset().top - 50
                    }, 500);
                }
            });
        });
        ";
    }

    /**
     * Get public styles.
     *
     * @since 0.1.0
     * @return string CSS styles.
     */
    private function get_public_styles() {
        return '
        /* SchedSpot Public Styles */
        .schedspot-booking-form {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #fff;
        }

        .schedspot-form-section {
            margin-bottom: 30px;
        }

        .schedspot-form-section h3 {
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 5px;
            font-size: 18px;
        }

        .schedspot-form-row {
            margin-bottom: 15px;
        }

        .schedspot-form-row label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .schedspot-form-row input,
        .schedspot-form-row select,
        .schedspot-form-row textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .schedspot-form-row input:focus,
        .schedspot-form-row select:focus,
        .schedspot-form-row textarea:focus {
            border-color: #0073aa;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 115, 170, 0.3);
        }

        .schedspot-form-actions {
            text-align: center;
            margin-top: 30px;
        }

        .schedspot-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #0073aa;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .schedspot-btn:hover {
            background: #005a87;
            color: white;
        }

        .schedspot-btn-primary {
            background: #0073aa;
        }

        .schedspot-btn-primary:hover {
            background: #005a87;
        }

        .schedspot-notice {
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }

        .schedspot-notice-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }

        .schedspot-notice-error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }

        .schedspot-notice ul {
            margin: 0;
            padding-left: 20px;
        }

        .required {
            color: #dc3545;
        }

        /* Service List Styles */
        .schedspot-service-list {
            margin: 20px 0;
        }

        .schedspot-services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .schedspot-service-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #fff;
            transition: box-shadow 0.3s ease;
        }

        .schedspot-service-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .schedspot-service-item .service-name {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 20px;
        }

        .schedspot-service-item .service-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .schedspot-service-item .service-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
            color: #777;
        }

        .schedspot-service-item .service-actions {
            text-align: center;
        }

        .schedspot-no-services {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }

        /* Dashboard Styles */
        .schedspot-dashboard {
            max-width: 800px;
            margin: 0 auto;
        }

        .schedspot-dashboard-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #0073aa;
        }

        .schedspot-dashboard-header h2 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .schedspot-dashboard-header .user-role {
            color: #666;
            font-style: italic;
        }

        .schedspot-dashboard-content {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .schedspot-customer-dashboard h3,
        .schedspot-worker-dashboard h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        /* Worker Dashboard Enhancements */
        .schedspot-dashboard-stats {
            margin-bottom: 30px;
        }

        .schedspot-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .stat-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e9ecef;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .schedspot-profile-completion {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .schedspot-progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            position: relative;
            margin: 10px 0;
        }

        .progress-fill {
            background: linear-gradient(90deg, #0073aa, #005a87);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12px;
            font-weight: bold;
            color: #333;
        }

        .schedspot-quick-actions {
            margin-bottom: 30px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .schedspot-bookings-list {
            margin-top: 20px;
        }

        .schedspot-booking-item {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
            transition: box-shadow 0.2s ease;
        }

        .schedspot-booking-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .booking-client {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .booking-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-in_progress {
            background: #d4edda;
            color: #155724;
        }

        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .booking-earnings {
            font-weight: bold;
            color: #28a745;
            font-size: 16px;
        }

        .booking-details {
            padding: 15px 20px;
        }

        .booking-datetime {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .booking-date,
        .booking-time {
            font-weight: bold;
            color: #333;
        }

        .booking-duration {
            color: #666;
        }

        .booking-address,
        .booking-notes {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .booking-actions {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .schedspot-btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .schedspot-btn-secondary {
            background: #6c757d;
            color: white;
        }

        .schedspot-btn-secondary:hover {
            background: #5a6268;
        }

        .schedspot-btn-link {
            background: transparent;
            color: #0073aa;
            border: 1px solid #0073aa;
        }

        .schedspot-btn-link:hover {
            background: #0073aa;
            color: white;
        }

        .schedspot-empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .schedspot-view-all {
            text-align: center;
            margin-top: 20px;
        }

        .booking-cost,
        .booking-earnings {
            font-weight: bold;
            color: #0073aa;
        }

        /* Payment Information Styles */
        .schedspot-payment-info {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }

        .schedspot-payment-info h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
            font-size: 18px;
        }

        .payment-details p {
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .payment-methods {
            margin: 20px 0;
        }

        .payment-methods p {
            margin-bottom: 10px;
        }

        .payment-icons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .payment-method {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 12px;
            color: #666;
        }

        .security-notice {
            margin-top: 15px;
            padding: 10px;
            background: #e8f5e8;
            border-left: 4px solid #28a745;
            border-radius: 4px;
        }

        .security-notice small {
            color: #155724;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .schedspot-booking-form {
                margin: 10px;
                padding: 15px;
            }

            .schedspot-services-grid {
                grid-template-columns: 1fr;
            }

            .schedspot-booking-item {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .schedspot-service-item .service-meta {
                flex-direction: column;
                gap: 5px;
            }
        }
        ';
    }
}
