<?php
/**
 * Dashboard Shortcode Class
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Shortcode_Dashboard Class.
 *
 * Handles the user dashboard shortcode functionality.
 *
 * @class SchedSpot_Shortcode_Dashboard
 * @version 1.0.0
 */
class SchedSpot_Shortcode_Dashboard {

    /**
     * Render dashboard shortcode.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Rendered output.
     */
    public static function render( $atts ) {
        $instance = new self();
        return $instance->render_dashboard( $atts );
    }

    /**
     * Render the dashboard.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Rendered output.
     */
    public function render_dashboard( $atts ) {
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            return $this->render_login_required();
        }

        // Enqueue dashboard assets
        wp_enqueue_style( 'schedspot-frontend-enhanced' );
        wp_enqueue_style( 'schedspot-dashboard', SCHEDSPOT_PLUGIN_URL . 'assets/css/dashboard.css', array(), SCHEDSPOT_VERSION );
        wp_enqueue_script( 'schedspot-frontend' );
        wp_enqueue_script( 'schedspot-dashboard', SCHEDSPOT_PLUGIN_URL . 'assets/js/dashboard.js', array( 'jquery', 'schedspot-frontend' ), SCHEDSPOT_VERSION, true );

        // Parse attributes
        $atts = shortcode_atts( array(
            'view' => 'bookings',
        ), $atts, 'schedspot_dashboard' );

        $current_user = wp_get_current_user();

        // Use SchedSpot's effective user role detection for admin role switching
        $user_role = SchedSpot()->get_effective_user_role();

        // If no effective role found, fall back to primary role detection
        if ( empty( $user_role ) ) {
            $user_role = $this->get_user_primary_role( $current_user );
        }

        // Get dashboard data based on effective user role
        $dashboard_data = $this->get_dashboard_data( $current_user, $user_role );

        // Handle AJAX requests
        $this->handle_dashboard_actions();

        // Start output buffering
        ob_start();

        // Load appropriate template based on user role
        if ( $user_role === 'schedspot_worker' ) {
            include SCHEDSPOT_PLUGIN_DIR . 'templates/shortcodes/dashboard-worker.php';
        } elseif ( $user_role === 'schedspot_customer' ) {
            include SCHEDSPOT_PLUGIN_DIR . 'templates/shortcodes/dashboard-customer.php';
        } elseif ( $user_role === 'administrator' ) {
            include SCHEDSPOT_PLUGIN_DIR . 'templates/shortcodes/dashboard-admin.php';
        } else {
            include SCHEDSPOT_PLUGIN_DIR . 'templates/shortcodes/dashboard-general.php';
        }

        return ob_get_clean();
    }

    /**
     * Get user's primary role.
     *
     * @since 1.0.0
     * @param WP_User $user User object.
     * @return string Primary role.
     */
    private function get_user_primary_role( $user ) {
        if ( in_array( 'schedspot_worker', $user->roles ) ) {
            return 'schedspot_worker';
        } elseif ( in_array( 'schedspot_customer', $user->roles ) ) {
            return 'schedspot_customer';
        } elseif ( in_array( 'administrator', $user->roles ) ) {
            // Check admin role mode
            $admin_role_mode = get_user_meta( $user->ID, 'schedspot_admin_role_mode', true );
            return $admin_role_mode ?: 'administrator';
        }

        return 'subscriber';
    }

    /**
     * Get dashboard data for user.
     *
     * @since 1.0.0
     * @param WP_User $user      User object.
     * @param string  $user_role User role.
     * @return array Dashboard data.
     */
    private function get_dashboard_data( $user, $user_role ) {
        $data = array(
            'user' => $user,
            'role' => $user_role,
            'bookings' => array(),
            'stats' => array(),
        );

        switch ( $user_role ) {
            case 'schedspot_worker':
                $data = array_merge( $data, $this->get_worker_dashboard_data( $user->ID ) );
                break;
            case 'schedspot_customer':
                $data = array_merge( $data, $this->get_customer_dashboard_data( $user->ID ) );
                break;
            case 'administrator':
                $data = array_merge( $data, $this->get_admin_dashboard_data( $user->ID ) );
                break;
            default:
                $data = array_merge( $data, $this->get_general_dashboard_data( $user->ID ) );
                break;
        }

        return $data;
    }

    /**
     * Get worker dashboard data.
     *
     * @since 1.0.0
     * @param int $user_id User ID.
     * @return array Worker dashboard data.
     */
    private function get_worker_dashboard_data( $user_id ) {
        global $wpdb;

        // Get worker profile
        $profile = get_user_meta( $user_id, 'schedspot_worker_profile', true );
        $is_available = get_user_meta( $user_id, 'schedspot_is_available', true ) === '1';

        // Get bookings with proper client details structure
        $raw_bookings = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}schedspot_bookings
             WHERE worker_id = %d
             ORDER BY booking_date DESC, start_time DESC
             LIMIT 10",
            $user_id
        ) );

        // Process bookings to ensure client_details property exists
        $bookings = array();
        foreach ( $raw_bookings as $booking ) {
            // Ensure client_details property exists
            if ( ! isset( $booking->client_details ) ) {
                $booking->client_details = array(
                    'name'    => $booking->client_name ?? '',
                    'email'   => $booking->client_email ?? '',
                    'phone'   => $booking->client_phone ?? '',
                    'address' => $booking->client_address ?? '',
                    'lat'     => $booking->client_lat ?? null,
                    'lng'     => $booking->client_lng ?? null,
                );
            }
            $bookings[] = $booking;
        }

        // Get statistics
        $stats = array(
            'total_bookings' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE worker_id = %d",
                $user_id
            ) ),
            'pending_bookings' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE worker_id = %d AND status = 'pending'",
                $user_id
            ) ),
            'confirmed_bookings' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE worker_id = %d AND status = 'confirmed'",
                $user_id
            ) ),
            'completed_bookings' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE worker_id = %d AND status = 'completed'",
                $user_id
            ) ),
            'total_earnings' => $wpdb->get_var( $wpdb->prepare(
                "SELECT SUM(total_cost) FROM {$wpdb->prefix}schedspot_bookings
                 WHERE worker_id = %d AND status IN ('confirmed', 'completed')",
                $user_id
            ) ) ?: 0,
            'this_month_earnings' => $wpdb->get_var( $wpdb->prepare(
                "SELECT SUM(total_cost) FROM {$wpdb->prefix}schedspot_bookings
                 WHERE worker_id = %d AND status IN ('confirmed', 'completed')
                 AND MONTH(booking_date) = MONTH(CURDATE()) AND YEAR(booking_date) = YEAR(CURDATE())",
                $user_id
            ) ) ?: 0,
        );

        // Prepare worker profile with availability status
        $worker_profile = $profile ?: array();
        $worker_profile['is_available'] = $is_available;

        return array(
            'profile' => $profile ?: array(),
            'worker_profile' => $worker_profile,
            'is_available' => $is_available,
            'bookings' => $bookings,
            'stats' => $stats,
            'upcoming_bookings' => $this->get_upcoming_bookings( $user_id ),
            'recent_messages' => $this->get_recent_messages( $user_id ),
            'user' => get_user_by( 'ID', $user_id ), // Add user object for template
        );
    }

    /**
     * Get customer dashboard data.
     *
     * @since 1.0.0
     * @param int $user_id User ID.
     * @return array Customer dashboard data.
     */
    private function get_customer_dashboard_data( $user_id ) {
        global $wpdb;

        $user = get_user_by( 'ID', $user_id );

        // Get bookings by email (for guest bookings) with service and worker names
        $bookings = $wpdb->get_results( $wpdb->prepare(
            "SELECT b.*, s.name as service_name, u.display_name as worker_name 
             FROM {$wpdb->prefix}schedspot_bookings b
             LEFT JOIN {$wpdb->prefix}schedspot_services s ON b.service_id = s.id
             LEFT JOIN {$wpdb->users} u ON b.worker_id = u.ID
             WHERE b.client_email = %s 
             ORDER BY b.booking_date DESC, b.start_time DESC 
             LIMIT 10",
            $user->user_email
        ) );

        // Get statistics
        $stats = array(
            'total_bookings' => count( $bookings ),
            'pending_bookings' => count( array_filter( $bookings, function( $b ) { return $b->status === 'pending'; } ) ),
            'confirmed_bookings' => count( array_filter( $bookings, function( $b ) { return $b->status === 'confirmed'; } ) ),
            'completed_bookings' => count( array_filter( $bookings, function( $b ) { return $b->status === 'completed'; } ) ),
            'total_spent' => array_sum( array_map( function( $b ) { 
                return in_array( $b->status, array( 'confirmed', 'completed' ) ) ? $b->total_cost : 0; 
            }, $bookings ) ),
            'unread_messages' => 0, // TODO: Implement messaging system integration
        );

        // Get user profile data
        $profile = array(
            'phone' => get_user_meta( $user_id, 'schedspot_customer_phone', true ),
            'bio' => get_user_meta( $user_id, 'schedspot_customer_bio', true ),
            'address' => get_user_meta( $user_id, 'schedspot_customer_address', true ),
        );

        return array(
            'bookings' => $bookings,
            'recent_bookings' => $bookings, // Alias for template compatibility
            'stats' => $stats,
            'upcoming_bookings' => $this->get_upcoming_customer_bookings( $user->user_email ),
            'recent_messages' => $this->get_recent_messages( $user_id ),
            'profile' => $profile,
        );
    }

    /**
     * Get admin dashboard data.
     *
     * @since 1.7.3
     * @param int $user_id User ID.
     * @return array Admin dashboard data.
     */
    private function get_admin_dashboard_data( $user_id ) {
        global $wpdb;

        // Get all bookings for admin overview
        $all_bookings = $wpdb->get_results(
            "SELECT b.*, s.name as service_name, u.display_name as worker_name 
             FROM {$wpdb->prefix}schedspot_bookings b
             LEFT JOIN {$wpdb->prefix}schedspot_services s ON b.service_id = s.id
             LEFT JOIN {$wpdb->users} u ON b.worker_id = u.ID
             ORDER BY b.booking_date DESC, b.start_time DESC
             LIMIT 20"
        );

        // Get comprehensive statistics
        $stats = array(
            'total_bookings' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings"
            ),
            'pending_bookings' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE status = 'pending'"
            ),
            'confirmed_bookings' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE status = 'confirmed'"
            ),
            'completed_bookings' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE status = 'completed'"
            ),
            'total_workers' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->users} u 
                 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
                 WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
                 AND um.meta_value LIKE '%schedspot_worker%'"
            ),
            'total_revenue' => $wpdb->get_var(
                "SELECT SUM(total_cost) FROM {$wpdb->prefix}schedspot_bookings 
                 WHERE status IN ('confirmed', 'completed')"
            ) ?: 0,
            'unread_messages' => 0, // TODO: Implement messaging system integration
        );

        return array(
            'bookings' => $all_bookings,
            'recent_bookings' => $all_bookings, // Alias for template compatibility
            'stats' => $stats,
            'upcoming_bookings' => array(), // Not relevant for admin view
            'recent_messages' => array(), // TODO: Implement messaging system integration
        );
    }

    /**
     * Get general dashboard data.
     *
     * @since 1.0.0
     * @param int $user_id User ID.
     * @return array General dashboard data.
     */
    private function get_general_dashboard_data( $user_id ) {
        return array(
            'bookings' => array(),
            'stats' => array(
                'total_bookings' => 0,
                'pending_bookings' => 0,
                'confirmed_bookings' => 0,
                'completed_bookings' => 0,
            ),
            'message' => __( 'Welcome to SchedSpot! To get started, please contact an administrator to set up your account.', 'schedspot' ),
        );
    }

    /**
     * Get upcoming bookings for worker.
     *
     * @since 1.0.0
     * @param int $user_id User ID.
     * @return array Upcoming bookings.
     */
    private function get_upcoming_bookings( $user_id ) {
        global $wpdb;

        $raw_bookings = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}schedspot_bookings
             WHERE worker_id = %d
             AND booking_date >= CURDATE()
             AND status IN ('pending', 'confirmed')
             ORDER BY booking_date ASC, start_time ASC
             LIMIT 5",
            $user_id
        ) );

        // Process bookings to ensure proper data structure
        $bookings = array();
        foreach ( $raw_bookings as $booking ) {
            // Ensure client_details property exists
            if ( ! isset( $booking->client_details ) ) {
                $booking->client_details = json_encode( array(
                    'name'    => $booking->client_name ?? '',
                    'email'   => $booking->client_email ?? '',
                    'phone'   => $booking->client_phone ?? '',
                    'address' => $booking->client_address ?? '',
                ) );
            }

            // Ensure service_details property exists
            if ( ! isset( $booking->service_details ) ) {
                $service = $wpdb->get_row( $wpdb->prepare(
                    "SELECT name, description, duration FROM {$wpdb->prefix}schedspot_services WHERE id = %d",
                    $booking->service_id
                ) );

                $booking->service_details = json_encode( array(
                    'name'        => $service ? $service->name : __( 'Unknown Service', 'schedspot' ),
                    'description' => $service ? $service->description : '',
                    'duration'    => $service ? $service->duration : 60,
                ) );
            }

            $bookings[] = $booking;
        }

        return $bookings;
    }

    /**
     * Get upcoming bookings for customer.
     *
     * @since 1.0.0
     * @param string $email Customer email.
     * @return array Upcoming bookings.
     */
    private function get_upcoming_customer_bookings( $email ) {
        global $wpdb;

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT b.*, s.name as service_name, u.display_name as worker_name 
             FROM {$wpdb->prefix}schedspot_bookings b
             LEFT JOIN {$wpdb->prefix}schedspot_services s ON b.service_id = s.id
             LEFT JOIN {$wpdb->users} u ON b.worker_id = u.ID
             WHERE b.client_email = %s 
             AND b.booking_date >= CURDATE() 
             AND b.status IN ('pending', 'confirmed')
             ORDER BY b.booking_date ASC, b.start_time ASC 
             LIMIT 5",
            $email
        ) );
    }

    /**
     * Get recent messages for user.
     *
     * @since 1.0.0
     * @param int $user_id User ID.
     * @return array Recent messages.
     */
    private function get_recent_messages( $user_id ) {
        // This would integrate with the messaging system
        // For now, return empty array
        return array();
    }

    /**
     * Handle dashboard actions.
     *
     * @since 1.0.0
     */
    private function handle_dashboard_actions() {
        // Handle form submissions and AJAX requests
        if ( isset( $_POST['action'] ) ) {
            $action = sanitize_text_field( $_POST['action'] );

            switch ( $action ) {
                case 'update_availability':
                    $this->handle_availability_update();
                    break;
                case 'update_profile':
                    $this->handle_profile_update();
                    break;
            }
        }
    }

    /**
     * Handle availability update.
     *
     * @since 1.0.0
     */
    private function handle_availability_update() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'update_availability' ) ) {
            return;
        }

        $user_id = get_current_user_id();
        $is_available = isset( $_POST['is_available'] ) ? '1' : '0';

        update_user_meta( $user_id, 'schedspot_is_available', $is_available );

        wp_redirect( add_query_arg( 'updated', 'availability' ) );
        exit;
    }

    /**
     * Handle profile update.
     *
     * @since 1.0.0
     */
    private function handle_profile_update() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'update_profile' ) ) {
            return;
        }

        $user_id = get_current_user_id();
        $profile_data = array(
            'bio' => sanitize_textarea_field( $_POST['bio'] ?? '' ),
            'phone' => sanitize_text_field( $_POST['phone'] ?? '' ),
            'address' => sanitize_text_field( $_POST['address'] ?? '' ),
            'updated_at' => current_time( 'mysql' ),
        );

        update_user_meta( $user_id, 'schedspot_worker_profile', $profile_data );

        wp_redirect( add_query_arg( 'updated', 'profile' ) );
        exit;
    }

    /**
     * Render login required message.
     *
     * @since 1.0.0
     * @return string Login required HTML.
     */
    private function render_login_required() {
        ob_start();
        ?>
        <div class="schedspot-login-required">
            <h3><?php _e( 'Login Required', 'schedspot' ); ?></h3>
            <p><?php _e( 'Please log in to access your dashboard.', 'schedspot' ); ?></p>
            <a href="<?php echo wp_login_url( get_permalink() ); ?>" class="schedspot-btn schedspot-btn-primary">
                <?php _e( 'Login', 'schedspot' ); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Format booking status for display.
     *
     * @since 1.0.0
     * @param string $status Booking status.
     * @return string Formatted status.
     */
    public static function format_booking_status( $status ) {
        $statuses = array(
            'pending' => __( 'Pending', 'schedspot' ),
            'confirmed' => __( 'Confirmed', 'schedspot' ),
            'completed' => __( 'Completed', 'schedspot' ),
            'cancelled' => __( 'Cancelled', 'schedspot' ),
        );

        return isset( $statuses[ $status ] ) ? $statuses[ $status ] : ucfirst( $status );
    }

    /**
     * Get booking actions for user role.
     *
     * @since 1.0.0
     * @param object $booking   Booking object.
     * @param string $user_role User role.
     * @return array Available actions.
     */
    public static function get_booking_actions( $booking, $user_role ) {
        $actions = array();

        if ( $user_role === 'schedspot_worker' ) {
            if ( $booking->status === 'pending' ) {
                $actions[] = array(
                    'label' => __( 'Accept', 'schedspot' ),
                    'action' => 'confirm',
                    'class' => 'schedspot-btn-primary',
                );
                $actions[] = array(
                    'label' => __( 'Decline', 'schedspot' ),
                    'action' => 'cancel',
                    'class' => 'schedspot-btn-secondary',
                );
            } elseif ( $booking->status === 'confirmed' ) {
                $actions[] = array(
                    'label' => __( 'Complete', 'schedspot' ),
                    'action' => 'complete',
                    'class' => 'schedspot-btn-primary',
                );
                $actions[] = array(
                    'label' => __( 'Reschedule', 'schedspot' ),
                    'action' => 'reschedule',
                    'class' => 'schedspot-btn-secondary',
                );
            }
        } elseif ( $user_role === 'schedspot_customer' ) {
            if ( in_array( $booking->status, array( 'pending', 'confirmed' ) ) ) {
                $actions[] = array(
                    'label' => __( 'Cancel', 'schedspot' ),
                    'action' => 'cancel',
                    'class' => 'schedspot-btn-secondary',
                );
                $actions[] = array(
                    'label' => __( 'Reschedule', 'schedspot' ),
                    'action' => 'reschedule',
                    'class' => 'schedspot-btn-secondary',
                );
            }
        }

        return $actions;
    }
}
