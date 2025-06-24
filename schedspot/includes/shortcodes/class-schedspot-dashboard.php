<?php
/**
 * SchedSpot Dashboard Shortcode
 *
 * Handles the dashboard shortcode functionality
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Dashboard Class.
 *
 * @class SchedSpot_Dashboard
 * @version 1.0.0
 */
class SchedSpot_Dashboard {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize dashboard functionality.
     *
     * @since 1.0.0
     */
    public function init() {
        add_shortcode( 'schedspot_dashboard', array( $this, 'render_dashboard' ) );
        add_action( 'wp_ajax_schedspot_update_worker_availability', array( $this, 'update_worker_availability' ) );
        add_action( 'wp_ajax_schedspot_get_dashboard_data', array( $this, 'get_dashboard_data' ) );
    }

    /**
     * Render dashboard shortcode.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Dashboard HTML.
     */
    public function render_dashboard( $atts ) {
        if ( ! is_user_logged_in() ) {
            return $this->render_login_prompt();
        }

        $current_user = wp_get_current_user();
        $user_role = $this->get_user_primary_role( $current_user );

        $atts = shortcode_atts( array(
            'view' => 'auto', // auto, customer, worker
            'show_navigation' => 'true',
        ), $atts );

        // Determine view based on user role if auto
        if ( $atts['view'] === 'auto' ) {
            $atts['view'] = ( $user_role === 'schedspot_worker' ) ? 'worker' : 'customer';
        }

        ob_start();
        ?>
        <div id="schedspot-dashboard" class="schedspot-dashboard" data-user-role="<?php echo esc_attr( $user_role ); ?>">
            <?php if ( $atts['show_navigation'] === 'true' ) : ?>
                <?php $this->render_dashboard_navigation( $atts['view'] ); ?>
            <?php endif; ?>
            
            <div class="dashboard-content">
                <?php
                if ( $atts['view'] === 'worker' ) {
                    $this->render_worker_dashboard();
                } else {
                    $this->render_customer_dashboard();
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render login prompt for non-logged-in users.
     *
     * @since 1.0.0
     * @return string Login prompt HTML.
     */
    private function render_login_prompt() {
        ob_start();
        ?>
        <div class="schedspot-login-prompt">
            <div class="login-prompt-content">
                <h3><?php _e( 'Please Log In', 'schedspot' ); ?></h3>
                <p><?php _e( 'You need to be logged in to access your dashboard.', 'schedspot' ); ?></p>
                <div class="login-prompt-actions">
                    <a href="<?php echo wp_login_url( get_permalink() ); ?>" class="schedspot-btn schedspot-btn-primary">
                        <?php _e( 'Log In', 'schedspot' ); ?>
                    </a>
                    <a href="<?php echo wp_registration_url(); ?>" class="schedspot-btn schedspot-btn-secondary">
                        <?php _e( 'Register', 'schedspot' ); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render dashboard navigation.
     *
     * @since 1.0.0
     * @param string $view Current view (worker or customer).
     */
    private function render_dashboard_navigation( $view ) {
        ?>
        <div class="dashboard-navigation">
            <div class="dashboard-tabs">
                <button class="dashboard-tab active" data-tab="overview">
                    <span class="dashicons dashicons-dashboard"></span>
                    <?php _e( 'Overview', 'schedspot' ); ?>
                </button>
                
                <button class="dashboard-tab" data-tab="bookings">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php _e( 'Bookings', 'schedspot' ); ?>
                </button>
                
                <?php if ( $view === 'worker' ) : ?>
                    <button class="dashboard-tab" data-tab="earnings">
                        <span class="dashicons dashicons-money-alt"></span>
                        <?php _e( 'Earnings', 'schedspot' ); ?>
                    </button>
                    
                    <button class="dashboard-tab" data-tab="schedule">
                        <span class="dashicons dashicons-clock"></span>
                        <?php _e( 'Schedule', 'schedspot' ); ?>
                    </button>
                    
                    <button class="dashboard-tab" data-tab="services">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e( 'Services', 'schedspot' ); ?>
                    </button>
                <?php endif; ?>
                
                <button class="dashboard-tab" data-tab="messages">
                    <span class="dashicons dashicons-email-alt"></span>
                    <?php _e( 'Messages', 'schedspot' ); ?>
                </button>
                
                <button class="dashboard-tab" data-tab="profile">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php _e( 'Profile', 'schedspot' ); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Render worker dashboard.
     *
     * @since 1.0.0
     */
    private function render_worker_dashboard() {
        $current_user = wp_get_current_user();
        $worker_id = $current_user->ID;
        $availability_status = get_user_meta( $worker_id, 'schedspot_worker_available', true );
        ?>
        <div class="worker-dashboard">
            <!-- Overview Tab -->
            <div id="overview" class="tab-content active">
                <div class="dashboard-header">
                    <h2><?php printf( __( 'Welcome back, %s!', 'schedspot' ), $current_user->display_name ); ?></h2>
                    
                    <div class="availability-toggle-container">
                        <div class="availability-status">
                            <span id="availability-status" class="status-indicator <?php echo $availability_status ? 'available' : 'unavailable'; ?>">
                                <?php echo $availability_status ? __( 'Available', 'schedspot' ) : __( 'Unavailable', 'schedspot' ); ?>
                            </span>
                        </div>
                        <button id="availability-toggle" class="availability-toggle <?php echo $availability_status ? 'btn-danger' : 'btn-success'; ?>">
                            <?php echo $availability_status ? __( 'Go Offline', 'schedspot' ) : __( 'Go Online', 'schedspot' ); ?>
                        </button>
                    </div>
                </div>

                <div class="dashboard-stats">
                    <?php $this->render_worker_stats( $worker_id ); ?>
                </div>

                <div class="dashboard-widgets">
                    <div class="widget">
                        <h3><?php _e( 'Today\'s Bookings', 'schedspot' ); ?></h3>
                        <?php $this->render_todays_bookings( $worker_id ); ?>
                    </div>
                    
                    <div class="widget">
                        <h3><?php _e( 'Recent Messages', 'schedspot' ); ?></h3>
                        <?php $this->render_recent_messages( $worker_id ); ?>
                    </div>
                </div>
            </div>

            <!-- Bookings Tab -->
            <div id="bookings" class="tab-content">
                <h3><?php _e( 'My Bookings', 'schedspot' ); ?></h3>
                <?php $this->render_worker_bookings( $worker_id ); ?>
            </div>

            <!-- Earnings Tab -->
            <div id="earnings" class="tab-content">
                <h3><?php _e( 'Earnings Overview', 'schedspot' ); ?></h3>
                <?php $this->render_worker_earnings( $worker_id ); ?>
            </div>

            <!-- Schedule Tab -->
            <div id="schedule" class="tab-content">
                <h3><?php _e( 'Manage Schedule', 'schedspot' ); ?></h3>
                <?php $this->render_worker_schedule( $worker_id ); ?>
            </div>

            <!-- Services Tab -->
            <div id="services" class="tab-content">
                <h3><?php _e( 'My Services', 'schedspot' ); ?></h3>
                <?php $this->render_worker_services( $worker_id ); ?>
            </div>

            <!-- Messages Tab -->
            <div id="messages" class="tab-content">
                <h3><?php _e( 'Messages', 'schedspot' ); ?></h3>
                <?php echo do_shortcode( '[schedspot_messages]' ); ?>
            </div>

            <!-- Profile Tab -->
            <div id="profile" class="tab-content">
                <h3><?php _e( 'Profile Settings', 'schedspot' ); ?></h3>
                <?php echo do_shortcode( '[schedspot_profile]' ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render customer dashboard.
     *
     * @since 1.0.0
     */
    private function render_customer_dashboard() {
        $current_user = wp_get_current_user();
        $customer_id = $current_user->ID;
        ?>
        <div class="customer-dashboard">
            <!-- Overview Tab -->
            <div id="overview" class="tab-content active">
                <div class="dashboard-header">
                    <h2><?php printf( __( 'Welcome back, %s!', 'schedspot' ), $current_user->display_name ); ?></h2>
                    
                    <div class="quick-actions">
                        <a href="#" class="schedspot-btn schedspot-btn-primary book-service-btn">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e( 'Book a Service', 'schedspot' ); ?>
                        </a>
                    </div>
                </div>

                <div class="dashboard-stats">
                    <?php $this->render_customer_stats( $customer_id ); ?>
                </div>

                <div class="dashboard-widgets">
                    <div class="widget">
                        <h3><?php _e( 'Upcoming Bookings', 'schedspot' ); ?></h3>
                        <?php $this->render_upcoming_bookings( $customer_id ); ?>
                    </div>
                    
                    <div class="widget">
                        <h3><?php _e( 'Recent Messages', 'schedspot' ); ?></h3>
                        <?php $this->render_recent_messages( $customer_id ); ?>
                    </div>
                </div>
            </div>

            <!-- Bookings Tab -->
            <div id="bookings" class="tab-content">
                <h3><?php _e( 'My Bookings', 'schedspot' ); ?></h3>
                <?php $this->render_customer_bookings( $customer_id ); ?>
            </div>

            <!-- Messages Tab -->
            <div id="messages" class="tab-content">
                <h3><?php _e( 'Messages', 'schedspot' ); ?></h3>
                <?php echo do_shortcode( '[schedspot_messages]' ); ?>
            </div>

            <!-- Profile Tab -->
            <div id="profile" class="tab-content">
                <h3><?php _e( 'Profile Settings', 'schedspot' ); ?></h3>
                <?php echo do_shortcode( '[schedspot_profile]' ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render worker statistics.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID.
     */
    private function render_worker_stats( $worker_id ) {
        // Get worker statistics
        $stats = array(
            'total_bookings' => $this->get_worker_total_bookings( $worker_id ),
            'this_month_earnings' => $this->get_worker_monthly_earnings( $worker_id ),
            'average_rating' => $this->get_worker_average_rating( $worker_id ),
            'completion_rate' => $this->get_worker_completion_rate( $worker_id ),
        );
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo esc_html( $stats['total_bookings'] ); ?></div>
                <div class="stat-label"><?php _e( 'Total Bookings', 'schedspot' ); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number">$<?php echo esc_html( number_format( $stats['this_month_earnings'], 2 ) ); ?></div>
                <div class="stat-label"><?php _e( 'This Month', 'schedspot' ); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo esc_html( number_format( $stats['average_rating'], 1 ) ); ?></div>
                <div class="stat-label"><?php _e( 'Avg Rating', 'schedspot' ); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo esc_html( number_format( $stats['completion_rate'], 1 ) ); ?>%</div>
                <div class="stat-label"><?php _e( 'Completion Rate', 'schedspot' ); ?></div>
            </div>
        </div>
        <?php
    }

    /**
     * Render customer statistics.
     *
     * @since 1.0.0
     * @param int $customer_id Customer ID.
     */
    private function render_customer_stats( $customer_id ) {
        // Get customer statistics
        $stats = array(
            'total_bookings' => $this->get_customer_total_bookings( $customer_id ),
            'total_spent' => $this->get_customer_total_spent( $customer_id ),
            'favorite_service' => $this->get_customer_favorite_service( $customer_id ),
            'member_since' => $this->get_customer_member_since( $customer_id ),
        );
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo esc_html( $stats['total_bookings'] ); ?></div>
                <div class="stat-label"><?php _e( 'Total Bookings', 'schedspot' ); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number">$<?php echo esc_html( number_format( $stats['total_spent'], 2 ) ); ?></div>
                <div class="stat-label"><?php _e( 'Total Spent', 'schedspot' ); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo esc_html( $stats['favorite_service'] ); ?></div>
                <div class="stat-label"><?php _e( 'Favorite Service', 'schedspot' ); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo esc_html( $stats['member_since'] ); ?></div>
                <div class="stat-label"><?php _e( 'Member Since', 'schedspot' ); ?></div>
            </div>
        </div>
        <?php
    }

    /**
     * Update worker availability status.
     *
     * @since 1.0.0
     */
    public function update_worker_availability() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'schedspot' ) ) );
        }

        $user_id = get_current_user_id();
        $available = isset( $_POST['available'] ) ? (bool) $_POST['available'] : false;

        update_user_meta( $user_id, 'schedspot_worker_available', $available );

        wp_send_json_success( array(
            'message' => $available ? __( 'You are now available for bookings.', 'schedspot' ) : __( 'You are now offline.', 'schedspot' ),
            'available' => $available
        ) );
    }

    /**
     * Get dashboard data via AJAX.
     *
     * @since 1.0.0
     */
    public function get_dashboard_data() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'schedspot' ) ) );
        }

        $user_id = get_current_user_id();
        $user_role = $this->get_user_primary_role( wp_get_current_user() );

        $data = array(
            'notifications_count' => $this->get_user_notifications_count( $user_id ),
            'recent_activity' => $this->get_user_recent_activity( $user_id ),
        );

        if ( $user_role === 'schedspot_worker' ) {
            $data['stats'] = array(
                'total_bookings' => $this->get_worker_total_bookings( $user_id ),
                'this_month_earnings' => $this->get_worker_monthly_earnings( $user_id ),
                'average_rating' => $this->get_worker_average_rating( $user_id ),
                'completion_rate' => $this->get_worker_completion_rate( $user_id ),
            );
        } else {
            $data['stats'] = array(
                'total_bookings' => $this->get_customer_total_bookings( $user_id ),
                'total_spent' => $this->get_customer_total_spent( $user_id ),
            );
        }

        wp_send_json_success( $data );
    }

    /**
     * Get user's primary role.
     *
     * @since 1.0.0
     * @param WP_User $user User object.
     * @return string Primary role.
     */
    private function get_user_primary_role( $user ) {
        if ( empty( $user->roles ) ) {
            return 'subscriber';
        }
        
        // Check for SchedSpot roles first
        if ( in_array( 'schedspot_worker', $user->roles ) ) {
            return 'schedspot_worker';
        }
        
        return $user->roles[0];
    }

    /**
     * Get worker total bookings.
     *
     * @since 1.6.1
     * @param int $worker_id Worker ID.
     * @return int Total bookings count.
     */
    private function get_worker_total_bookings( $worker_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_bookings';

        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE worker_id = %d",
            $worker_id
        ) );

        return $count ? intval( $count ) : 0;
    }

    /**
     * Get worker monthly earnings.
     *
     * @since 1.6.1
     * @param int $worker_id Worker ID.
     * @return float Monthly earnings.
     */
    private function get_worker_monthly_earnings( $worker_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_bookings';

        $earnings = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(total_cost - commission_amount) FROM {$table_name}
             WHERE worker_id = %d AND status = 'completed'
             AND MONTH(booking_date) = MONTH(CURDATE())
             AND YEAR(booking_date) = YEAR(CURDATE())",
            $worker_id
        ) );

        return $earnings ? floatval( $earnings ) : 0.00;
    }

    /**
     * Get worker average rating.
     *
     * @since 1.6.1
     * @param int $worker_id Worker ID.
     * @return float Average rating.
     */
    private function get_worker_average_rating( $worker_id ) {
        $rating = get_user_meta( $worker_id, 'schedspot_worker_rating', true );
        return $rating ? floatval( $rating ) : 0.0;
    }

    /**
     * Get worker completion rate.
     *
     * @since 1.6.1
     * @param int $worker_id Worker ID.
     * @return float Completion rate percentage.
     */
    private function get_worker_completion_rate( $worker_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_bookings';

        $total = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE worker_id = %d AND status != 'pending'",
            $worker_id
        ) );

        $completed = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE worker_id = %d AND status = 'completed'",
            $worker_id
        ) );

        return $total > 0 ? ( $completed / $total ) * 100 : 0.0;
    }

    /**
     * Get customer total bookings.
     *
     * @since 1.6.1
     * @param int $customer_id Customer ID.
     * @return int Total bookings count.
     */
    private function get_customer_total_bookings( $customer_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_bookings';

        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE client_id = %d",
            $customer_id
        ) );

        return $count ? intval( $count ) : 0;
    }

    /**
     * Get customer total spent.
     *
     * @since 1.6.1
     * @param int $customer_id Customer ID.
     * @return float Total amount spent.
     */
    private function get_customer_total_spent( $customer_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_bookings';

        $total = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(total_cost) FROM {$table_name}
             WHERE client_id = %d AND status IN ('completed', 'confirmed')",
            $customer_id
        ) );

        return $total ? floatval( $total ) : 0.00;
    }

    /**
     * Get customer favorite service.
     *
     * @since 1.6.1
     * @param int $customer_id Customer ID.
     * @return string Favorite service name.
     */
    private function get_customer_favorite_service( $customer_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_bookings';

        $service_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT service_id FROM {$table_name}
             WHERE client_id = %d
             GROUP BY service_id
             ORDER BY COUNT(*) DESC
             LIMIT 1",
            $customer_id
        ) );

        if ( $service_id ) {
            $service = new SchedSpot_Service( $service_id );
            return $service->name ?: __( 'Unknown Service', 'schedspot' );
        }

        return __( 'No bookings yet', 'schedspot' );
    }

    /**
     * Get customer member since date.
     *
     * @since 1.6.1
     * @param int $customer_id Customer ID.
     * @return string Member since year.
     */
    private function get_customer_member_since( $customer_id ) {
        $user = get_user_by( 'ID', $customer_id );
        return $user ? date( 'Y', strtotime( $user->user_registered ) ) : date( 'Y' );
    }

    /**
     * Get user notifications count.
     *
     * @since 1.6.1
     * @param int $user_id User ID.
     * @return int Notifications count.
     */
    private function get_user_notifications_count( $user_id ) {
        // This would integrate with a notifications system
        // For now, return a placeholder count
        return get_user_meta( $user_id, 'schedspot_unread_notifications', true ) ?: 0;
    }

    /**
     * Get user recent activity.
     *
     * @since 1.6.1
     * @param int $user_id User ID.
     * @return array Recent activity items.
     */
    private function get_user_recent_activity( $user_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_bookings';

        $activities = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table_name}
             WHERE (client_id = %d OR worker_id = %d)
             ORDER BY created_at DESC
             LIMIT 5",
            $user_id, $user_id
        ) );

        $formatted_activities = array();
        foreach ( $activities as $activity ) {
            $formatted_activities[] = array(
                'type' => 'booking',
                'description' => sprintf( __( 'Booking #%d %s', 'schedspot' ), $activity->id, $activity->status ),
                'date' => $activity->created_at,
            );
        }

        return $formatted_activities;
    }

    /**
     * Render today's bookings for worker.
     *
     * @since 1.6.1
     * @param int $worker_id Worker ID.
     */
    private function render_todays_bookings( $worker_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_bookings';

        $bookings = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table_name}
             WHERE worker_id = %d AND booking_date = CURDATE()
             ORDER BY start_time ASC",
            $worker_id
        ) );

        if ( empty( $bookings ) ) {
            echo '<p>' . __( 'No bookings scheduled for today.', 'schedspot' ) . '</p>';
            return;
        }

        echo '<div class="todays-bookings">';
        foreach ( $bookings as $booking ) {
            $service = new SchedSpot_Service( $booking->service_id );
            echo '<div class="booking-item">';
            echo '<div class="booking-time">' . esc_html( $booking->start_time ) . '</div>';
            echo '<div class="booking-service">' . esc_html( $service->name ) . '</div>';
            echo '<div class="booking-client">' . esc_html( $booking->client_name ) . '</div>';
            echo '<div class="booking-status status-' . esc_attr( $booking->status ) . '">' . esc_html( ucfirst( $booking->status ) ) . '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Render recent messages for user.
     *
     * @since 1.6.1
     * @param int $user_id User ID.
     */
    private function render_recent_messages( $user_id ) {
        // This would integrate with the messaging system
        // For now, show a placeholder with link to messages
        echo '<div class="recent-messages">';
        echo '<p>' . __( 'Recent messages will appear here.', 'schedspot' ) . '</p>';
        echo '<a href="#" class="view-all-messages">' . __( 'View All Messages', 'schedspot' ) . '</a>';
        echo '</div>';
    }

    /**
     * Render worker bookings list.
     *
     * @since 1.6.1
     * @param int $worker_id Worker ID.
     */
    private function render_worker_bookings( $worker_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_bookings';

        $bookings = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table_name}
             WHERE worker_id = %d
             ORDER BY booking_date DESC, start_time DESC
             LIMIT 10",
            $worker_id
        ) );

        if ( empty( $bookings ) ) {
            echo '<p>' . __( 'No bookings found.', 'schedspot' ) . '</p>';
            return;
        }

        echo '<div class="worker-bookings-list">';
        foreach ( $bookings as $booking ) {
            $service = new SchedSpot_Service( $booking->service_id );
            echo '<div class="booking-card">';
            echo '<div class="booking-header">';
            echo '<h4>' . esc_html( $service->name ) . '</h4>';
            echo '<span class="booking-status status-' . esc_attr( $booking->status ) . '">' . esc_html( ucfirst( $booking->status ) ) . '</span>';
            echo '</div>';
            echo '<div class="booking-details">';
            echo '<p><strong>' . __( 'Date:', 'schedspot' ) . '</strong> ' . esc_html( date( 'F j, Y', strtotime( $booking->booking_date ) ) ) . '</p>';
            echo '<p><strong>' . __( 'Time:', 'schedspot' ) . '</strong> ' . esc_html( $booking->start_time ) . '</p>';
            echo '<p><strong>' . __( 'Client:', 'schedspot' ) . '</strong> ' . esc_html( $booking->client_name ) . '</p>';
            echo '<p><strong>' . __( 'Amount:', 'schedspot' ) . '</strong> $' . esc_html( number_format( $booking->total_cost, 2 ) ) . '</p>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Render worker earnings details.
     *
     * @since 1.6.1
     * @param int $worker_id Worker ID.
     */
    private function render_worker_earnings( $worker_id ) {
        $this_month = $this->get_worker_monthly_earnings( $worker_id );
        $total_earnings = $this->get_worker_total_earnings( $worker_id );

        echo '<div class="worker-earnings">';
        echo '<div class="earnings-summary">';
        echo '<div class="earning-item">';
        echo '<h4>' . __( 'This Month', 'schedspot' ) . '</h4>';
        echo '<span class="amount">$' . number_format( $this_month, 2 ) . '</span>';
        echo '</div>';
        echo '<div class="earning-item">';
        echo '<h4>' . __( 'Total Earnings', 'schedspot' ) . '</h4>';
        echo '<span class="amount">$' . number_format( $total_earnings, 2 ) . '</span>';
        echo '</div>';
        echo '</div>';
        echo '<a href="#" class="view-detailed-earnings">' . __( 'View Detailed Report', 'schedspot' ) . '</a>';
        echo '</div>';
    }

    /**
     * Get worker total earnings.
     *
     * @since 1.6.1
     * @param int $worker_id Worker ID.
     * @return float Total earnings.
     */
    private function get_worker_total_earnings( $worker_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_bookings';

        $earnings = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(total_cost - commission_amount) FROM {$table_name}
             WHERE worker_id = %d AND status = 'completed'",
            $worker_id
        ) );

        return $earnings ? floatval( $earnings ) : 0.00;
    }

    /**
     * Render worker schedule management.
     *
     * @since 1.6.1
     * @param int $worker_id Worker ID.
     */
    private function render_worker_schedule( $worker_id ) {
        echo '<div class="worker-schedule">';
        echo '<p>' . __( 'Schedule management interface will be implemented here.', 'schedspot' ) . '</p>';
        echo '<p>' . __( 'This will include availability settings, time slots, and calendar integration.', 'schedspot' ) . '</p>';
        echo '</div>';
    }

    /**
     * Render worker services management.
     *
     * @since 1.6.1
     * @param int $worker_id Worker ID.
     */
    private function render_worker_services( $worker_id ) {
        $worker_services = get_user_meta( $worker_id, 'schedspot_worker_services', true ) ?: array();

        echo '<div class="worker-services">';
        if ( empty( $worker_services ) ) {
            echo '<p>' . __( 'No services assigned yet.', 'schedspot' ) . '</p>';
        } else {
            echo '<div class="services-list">';
            foreach ( $worker_services as $service_id ) {
                $service = new SchedSpot_Service( $service_id );
                if ( $service->id ) {
                    echo '<div class="service-item">';
                    echo '<h4>' . esc_html( $service->name ) . '</h4>';
                    echo '<p>' . esc_html( $service->description ) . '</p>';
                    echo '<span class="service-price">$' . esc_html( number_format( $service->price, 2 ) ) . '</span>';
                    echo '</div>';
                }
            }
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Render upcoming bookings for customer.
     *
     * @since 1.6.1
     * @param int $customer_id Customer ID.
     */
    private function render_upcoming_bookings( $customer_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_bookings';

        $bookings = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table_name}
             WHERE client_id = %d AND booking_date >= CURDATE()
             ORDER BY booking_date ASC, start_time ASC
             LIMIT 5",
            $customer_id
        ) );

        if ( empty( $bookings ) ) {
            echo '<p>' . __( 'No upcoming bookings.', 'schedspot' ) . '</p>';
            return;
        }

        echo '<div class="upcoming-bookings">';
        foreach ( $bookings as $booking ) {
            $service = new SchedSpot_Service( $booking->service_id );
            $worker = get_user_by( 'ID', $booking->worker_id );

            echo '<div class="booking-item">';
            echo '<div class="booking-service">' . esc_html( $service->name ) . '</div>';
            echo '<div class="booking-date">' . esc_html( date( 'M j, Y', strtotime( $booking->booking_date ) ) ) . '</div>';
            echo '<div class="booking-time">' . esc_html( $booking->start_time ) . '</div>';
            echo '<div class="booking-worker">' . esc_html( $worker->display_name ) . '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Render customer bookings list.
     *
     * @since 1.6.1
     * @param int $customer_id Customer ID.
     */
    private function render_customer_bookings( $customer_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_bookings';

        $bookings = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table_name}
             WHERE client_id = %d
             ORDER BY booking_date DESC, start_time DESC
             LIMIT 10",
            $customer_id
        ) );

        if ( empty( $bookings ) ) {
            echo '<p>' . __( 'No bookings found.', 'schedspot' ) . '</p>';
            return;
        }

        echo '<div class="customer-bookings-list">';
        foreach ( $bookings as $booking ) {
            $service = new SchedSpot_Service( $booking->service_id );
            $worker = get_user_by( 'ID', $booking->worker_id );

            echo '<div class="booking-card">';
            echo '<div class="booking-header">';
            echo '<h4>' . esc_html( $service->name ) . '</h4>';
            echo '<span class="booking-status status-' . esc_attr( $booking->status ) . '">' . esc_html( ucfirst( $booking->status ) ) . '</span>';
            echo '</div>';
            echo '<div class="booking-details">';
            echo '<p><strong>' . __( 'Date:', 'schedspot' ) . '</strong> ' . esc_html( date( 'F j, Y', strtotime( $booking->booking_date ) ) ) . '</p>';
            echo '<p><strong>' . __( 'Time:', 'schedspot' ) . '</strong> ' . esc_html( $booking->start_time ) . '</p>';
            echo '<p><strong>' . __( 'Worker:', 'schedspot' ) . '</strong> ' . esc_html( $worker->display_name ) . '</p>';
            echo '<p><strong>' . __( 'Amount:', 'schedspot' ) . '</strong> $' . esc_html( number_format( $booking->total_cost, 2 ) ) . '</p>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
}
