<?php
/**
 * Booking Form Shortcode Class
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Shortcode_Booking_Form Class.
 *
 * Handles the booking form shortcode functionality.
 *
 * @class SchedSpot_Shortcode_Booking_Form
 * @version 1.0.0
 */
class SchedSpot_Shortcode_Booking_Form {

    /**
     * Render booking form shortcode.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Rendered output.
     */
    public static function render( $atts ) {
        $instance = new self();
        return $instance->render_form( $atts );
    }

    /**
     * Render the booking form.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Rendered output.
     */
    public function render_form( $atts ) {
        // Parse attributes
        $atts = shortcode_atts( array(
            'service_id' => '',
            'worker_id' => '',
            'show_workers' => 'true',
            'style' => 'wizard', // Default to wizard style
            'layout' => 'modern',
        ), $atts, 'schedspot_booking_form' );

        // Handle form submission
        if ( isset( $_POST['schedspot_submit_booking'] ) ) {
            $result = $this->process_booking_submission();
            if ( $result['success'] ) {
                return $this->render_success_message( $result['booking_id'] );
            } else {
                $error_message = $result['message'];
            }
        }

        // Get form data
        $services = $this->get_available_services();
        $workers = $this->get_available_workers( $atts['service_id'] );
        $selected_service = $atts['service_id'] ? $this->get_service( $atts['service_id'] ) : null;
        $selected_worker = $atts['worker_id'] ? $this->get_worker( $atts['worker_id'] ) : null;

        // Start output buffering
        ob_start();

        // Choose template based on style
        if ( $atts['style'] === 'wizard' ) {
            include SCHEDSPOT_PLUGIN_DIR . 'templates/shortcodes/booking-wizard.php';
        } else {
            include SCHEDSPOT_PLUGIN_DIR . 'templates/shortcodes/booking-form.php';
        }

        return ob_get_clean();
    }

    /**
     * Process booking form submission.
     *
     * @since 1.0.0
     * @return array Result array with success status and message.
     */
    private function process_booking_submission() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['schedspot_booking_nonce'], 'schedspot_booking_form' ) ) {
            return array(
                'success' => false,
                'message' => __( 'Security check failed. Please try again.', 'schedspot' )
            );
        }

        // Validate required fields
        $validation = $this->validate_booking_data( $_POST );
        if ( ! $validation['valid'] ) {
            return array(
                'success' => false,
                'message' => $validation['message']
            );
        }

        // Prepare booking data
        $booking_data = $this->prepare_booking_data( $_POST );

        // Create booking
        $booking_id = $this->create_booking( $booking_data );

        if ( $booking_id ) {
            // Send notifications
            $this->send_booking_notifications( $booking_id );

            return array(
                'success' => true,
                'booking_id' => $booking_id,
                'message' => __( 'Booking submitted successfully!', 'schedspot' )
            );
        } else {
            return array(
                'success' => false,
                'message' => __( 'Failed to create booking. Please try again.', 'schedspot' )
            );
        }
    }

    /**
     * Validate booking form data.
     *
     * @since 1.0.0
     * @param array $data Form data.
     * @return array Validation result.
     */
    private function validate_booking_data( $data ) {
        $errors = array();

        // Required fields
        $required_fields = array(
            'schedspot_service_id' => __( 'Service', 'schedspot' ),
            'schedspot_booking_date' => __( 'Booking date', 'schedspot' ),
            'schedspot_booking_time' => __( 'Booking time', 'schedspot' ),
            'schedspot_client_name' => __( 'Name', 'schedspot' ),
            'schedspot_client_email' => __( 'Email', 'schedspot' ),
            'schedspot_client_phone' => __( 'Phone', 'schedspot' ),
        );

        foreach ( $required_fields as $field => $label ) {
            if ( empty( $data[ $field ] ) ) {
                $errors[] = sprintf( __( '%s is required.', 'schedspot' ), $label );
            }
        }

        // Email validation
        if ( ! empty( $data['schedspot_client_email'] ) && ! is_email( $data['schedspot_client_email'] ) ) {
            $errors[] = __( 'Please enter a valid email address.', 'schedspot' );
        }

        // Date validation
        if ( ! empty( $data['schedspot_booking_date'] ) ) {
            $booking_date = strtotime( $data['schedspot_booking_date'] );
            if ( $booking_date < strtotime( 'today' ) ) {
                $errors[] = __( 'Booking date cannot be in the past.', 'schedspot' );
            }
        }

        // Worker availability validation
        if ( ! empty( $data['schedspot_worker_id'] ) ) {
            $is_available = $this->check_worker_availability( 
                $data['schedspot_worker_id'], 
                $data['schedspot_booking_date'], 
                $data['schedspot_booking_time'] 
            );

            if ( ! $is_available ) {
                $errors[] = __( 'Selected worker is not available at the requested time.', 'schedspot' );
            }
        }

        if ( ! empty( $errors ) ) {
            return array(
                'valid' => false,
                'message' => implode( '<br>', $errors )
            );
        }

        return array( 'valid' => true );
    }

    /**
     * Prepare booking data for database insertion.
     *
     * @since 1.0.0
     * @param array $data Form data.
     * @return array Prepared booking data.
     */
    private function prepare_booking_data( $data ) {
        $service = $this->get_service( intval( $data['schedspot_service_id'] ) );
        $worker_id = ! empty( $data['schedspot_worker_id'] ) ? intval( $data['schedspot_worker_id'] ) : null;

        // Handle auto-assignment if no specific worker selected
        $worker_selection_mode = isset( $data['worker_selection_mode'] ) ? $data['worker_selection_mode'] : 'auto';
        if ( $worker_selection_mode === 'auto' || ! $worker_id ) {
            $auto_assigned_worker = $this->auto_assign_worker(
                intval( $data['schedspot_service_id'] ),
                $data['schedspot_booking_date'],
                $data['schedspot_booking_time']
            );

            if ( $auto_assigned_worker ) {
                $worker_id = $auto_assigned_worker->ID;
            }
        }

        // Calculate total price
        $total_price = $service ? $service->base_price : 0;
        if ( $worker_id ) {
            $worker_profile = get_user_meta( $worker_id, 'schedspot_worker_profile', true );
            if ( isset( $worker_profile['hourly_rate'] ) && $worker_profile['hourly_rate'] > 0 ) {
                $total_price = $worker_profile['hourly_rate'];
            }
        }

        return array(
            'service_id' => intval( $data['schedspot_service_id'] ),
            'worker_id' => $worker_id,
            'booking_date' => sanitize_text_field( $data['schedspot_booking_date'] ),
            'start_time' => sanitize_text_field( $data['schedspot_booking_time'] ),
            'client_details' => json_encode( array(
                'name' => sanitize_text_field( $data['schedspot_client_name'] ),
                'email' => sanitize_email( $data['schedspot_client_email'] ),
                'phone' => sanitize_text_field( $data['schedspot_client_phone'] ),
                'address' => sanitize_text_field( $data['schedspot_client_address'] ?? '' ),
            ) ),
            'service_details' => json_encode( array(
                'name' => $service ? $service->name : '',
                'description' => $service ? $service->description : '',
                'duration' => $service ? $service->duration : 0,
            ) ),
            'total_cost' => $total_price,
            'status' => 'pending',
            'notes' => sanitize_textarea_field( $data['schedspot_notes'] ?? '' ),
            'created_at' => current_time( 'mysql' ),
        );
    }

    /**
     * Create booking in database.
     *
     * @since 1.0.0
     * @param array $booking_data Booking data.
     * @return int|false Booking ID or false on failure.
     */
    private function create_booking( $booking_data ) {
        global $wpdb;

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'schedspot_bookings',
            $booking_data,
            array( '%d', '%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s' )
        );

        return $inserted ? $wpdb->insert_id : false;
    }

    /**
     * Send booking notifications.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     */
    private function send_booking_notifications( $booking_id ) {
        $booking = SchedSpot_Booking::get_booking( $booking_id );

        if ( ! $booking ) {
            return;
        }

        // Send email to client
        $this->send_client_confirmation_email( $booking );

        // Send email to admin
        $this->send_admin_notification_email( $booking );

        // Send email to worker if assigned
        if ( $booking->worker_id ) {
            $this->send_worker_notification_email( $booking );
        }
    }

    /**
     * Send confirmation email to client.
     *
     * @since 1.0.0
     * @param object $booking Booking object.
     */
    private function send_client_confirmation_email( $booking ) {
        $client_details = json_decode( $booking->client_details, true );
        $service_details = json_decode( $booking->service_details, true );

        $subject = sprintf( __( 'Booking Confirmation - %s', 'schedspot' ), $service_details['name'] );

        $message = sprintf(
            __( 'Dear %s,

Your booking has been received and is pending confirmation.

Booking Details:
- Service: %s
- Date: %s
- Time: %s
- Total: $%.2f

We will contact you shortly to confirm your booking.

Thank you!', 'schedspot' ),
            $client_details['name'],
            $service_details['name'],
            date( 'F j, Y', strtotime( $booking->booking_date ) ),
            date( 'g:i A', strtotime( $booking->start_time ) ),
            $booking->total_cost
        );

        wp_mail( $client_details['email'], $subject, $message );
    }

    /**
     * Send notification email to admin.
     *
     * @since 1.0.0
     * @param object $booking Booking object.
     */
    private function send_admin_notification_email( $booking ) {
        $admin_email = get_option( 'admin_email' );
        $client_details = json_decode( $booking->client_details, true );
        $service_details = json_decode( $booking->service_details, true );

        $subject = __( 'New Booking Received', 'schedspot' );

        $message = sprintf(
            __( 'A new booking has been received:

Client: %s (%s)
Service: %s
Date: %s
Time: %s
Total: $%.2f

Please review and confirm the booking in the admin area.', 'schedspot' ),
            $client_details['name'],
            $client_details['email'],
            $service_details['name'],
            date( 'F j, Y', strtotime( $booking->booking_date ) ),
            date( 'g:i A', strtotime( $booking->start_time ) ),
            $booking->total_cost
        );

        wp_mail( $admin_email, $subject, $message );
    }

    /**
     * Send notification email to worker.
     *
     * @since 1.0.0
     * @param object $booking Booking object.
     */
    private function send_worker_notification_email( $booking ) {
        $worker = get_user_by( 'ID', $booking->worker_id );

        if ( ! $worker ) {
            return;
        }

        $client_details = json_decode( $booking->client_details, true );
        $service_details = json_decode( $booking->service_details, true );

        $subject = __( 'New Booking Assignment', 'schedspot' );

        $message = sprintf(
            __( 'Hello %s,

You have been assigned a new booking:

Client: %s
Service: %s
Date: %s
Time: %s

Please check your dashboard for more details.', 'schedspot' ),
            $worker->display_name,
            $client_details['name'],
            $service_details['name'],
            date( 'F j, Y', strtotime( $booking->booking_date ) ),
            date( 'g:i A', strtotime( $booking->start_time ) )
        );

        wp_mail( $worker->user_email, $subject, $message );
    }

    /**
     * Render success message.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     * @return string Success message HTML.
     */
    private function render_success_message( $booking_id ) {
        ob_start();
        include SCHEDSPOT_PLUGIN_DIR . 'templates/shortcodes/booking-success.php';
        return ob_get_clean();
    }

    /**
     * Get available services.
     *
     * @since 1.0.0
     * @return array Available services.
     */
    private function get_available_services() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}schedspot_services WHERE is_active = 1 ORDER BY name ASC"
        );
    }

    /**
     * Get available workers.
     *
     * @since 1.0.0
     * @param string $service_id Optional service ID to filter by.
     * @return array Available workers.
     */
    private function get_available_workers( $service_id = '' ) {
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
            $profile = get_user_meta( $user->ID, 'schedspot_worker_profile', true );
            $assigned_services = get_user_meta( $user->ID, 'schedspot_assigned_services', true );

            // Filter by service if specified
            if ( $service_id ) {
                // If worker has no assigned services, skip them
                if ( empty( $assigned_services ) || ! is_array( $assigned_services ) ) {
                    continue;
                }

                // Check if the service is in the worker's assigned services
                if ( ! in_array( intval( $service_id ), $assigned_services ) ) {
                    continue;
                }
            }

            $workers[] = (object) array(
                'ID' => $user->ID,
                'display_name' => $user->display_name,
                'profile' => $profile ?: array(),
                'avatar' => get_avatar_url( $user->ID ),
                'rating' => 4.5, // Placeholder
                'hourly_rate' => isset( $profile['hourly_rate'] ) ? $profile['hourly_rate'] : 0,
                'skills' => isset( $profile['skills'] ) ? $profile['skills'] : array(),
            );
        }

        return $workers;
    }

    /**
     * Get service by ID.
     *
     * @since 1.0.0
     * @param int $service_id Service ID.
     * @return object|null Service object or null.
     */
    private function get_service( $service_id ) {
        global $wpdb;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}schedspot_services WHERE id = %d AND is_active = 1",
            $service_id
        ) );
    }

    /**
     * Get worker by ID.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID.
     * @return object|null Worker object or null.
     */
    private function get_worker( $worker_id ) {
        $user = get_user_by( 'ID', $worker_id );

        if ( ! $user || ! in_array( 'schedspot_worker', $user->roles ) ) {
            return null;
        }

        $profile = get_user_meta( $user->ID, 'schedspot_worker_profile', true );

        return (object) array(
            'ID' => $user->ID,
            'display_name' => $user->display_name,
            'profile' => $profile ?: array(),
            'avatar' => get_avatar_url( $user->ID ),
        );
    }

    /**
     * Check worker availability.
     *
     * @since 1.0.0
     * @param int    $worker_id Worker ID.
     * @param string $date      Booking date.
     * @param string $time      Booking time.
     * @return bool Whether worker is available.
     */
    private function check_worker_availability( $worker_id, $date, $time ) {
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

    /**
     * Auto-assign best available worker for a booking.
     *
     * @since 1.7.0
     * @param int    $service_id Service ID.
     * @param string $date       Booking date.
     * @param string $time       Booking time.
     * @return object|null Best available worker or null.
     */
    private function auto_assign_worker( $service_id, $date, $time ) {
        // Get worker assignment mode from settings
        $assignment_mode = get_option( 'schedspot_worker_assignment_mode', 'auto' );

        if ( $assignment_mode === 'manual' ) {
            // For manual assignment, return null so booking goes to admin for assignment
            return null;
        }

        // Get available workers for this service
        $available_workers = $this->get_available_workers( $service_id );

        if ( empty( $available_workers ) ) {
            return null;
        }

        // Filter workers who are available at the requested time
        $available_at_time = array();
        foreach ( $available_workers as $worker ) {
            if ( $this->check_worker_availability( $worker->ID, $date, $time ) ) {
                $available_at_time[] = $worker;
            }
        }

        if ( empty( $available_at_time ) ) {
            return null;
        }

        // Score workers based on various factors
        $scored_workers = array();
        foreach ( $available_at_time as $worker ) {
            $score = $this->calculate_worker_score( $worker, $service_id );
            $scored_workers[] = array(
                'worker' => $worker,
                'score' => $score
            );
        }

        // Sort by score (highest first)
        usort( $scored_workers, function( $a, $b ) {
            return $b['score'] <=> $a['score'];
        } );

        // Return the best worker
        return $scored_workers[0]['worker'];
    }

    /**
     * Calculate worker score for auto-assignment.
     *
     * @since 1.7.0
     * @param object $worker     Worker object.
     * @param int    $service_id Service ID.
     * @return float Worker score.
     */
    private function calculate_worker_score( $worker, $service_id ) {
        $score = 0;

        // Base score for being available
        $score += 10;

        // Bonus for having the service assigned
        $assigned_services = get_user_meta( $worker->ID, 'schedspot_assigned_services', true );
        if ( $assigned_services && in_array( $service_id, $assigned_services ) ) {
            $score += 20;
        }

        // Bonus for rating (if available)
        if ( isset( $worker->rating ) && $worker->rating > 0 ) {
            $score += $worker->rating * 2; // Max 10 points for 5-star rating
        }

        // Bonus for experience (number of completed bookings)
        $completed_bookings = $this->get_worker_completed_bookings( $worker->ID );
        $score += min( $completed_bookings * 0.5, 10 ); // Max 10 points for experience

        // Bonus for lower hourly rate (more affordable)
        if ( isset( $worker->hourly_rate ) && $worker->hourly_rate > 0 ) {
            $rate_score = max( 0, 10 - ( $worker->hourly_rate / 10 ) ); // Lower rate = higher score
            $score += $rate_score;
        }

        return $score;
    }

    /**
     * Get number of completed bookings for a worker.
     *
     * @since 1.7.0
     * @param int $worker_id Worker ID.
     * @return int Number of completed bookings.
     */
    private function get_worker_completed_bookings( $worker_id ) {
        global $wpdb;

        return intval( $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings
             WHERE worker_id = %d AND status = 'completed'",
            $worker_id
        ) ) );
    }
}
