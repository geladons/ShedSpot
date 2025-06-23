<?php
/**
 * Shortcodes Class
 *
 * @package SchedSpot
 * @version 0.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Shortcodes Class.
 *
 * @class SchedSpot_Shortcodes
 * @version 0.1.0
 */
class SchedSpot_Shortcodes {

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize shortcodes.
     *
     * @since 0.1.0
     */
    public function init() {
        $shortcodes = array(
            'schedspot_booking_form' => 'booking_form',
            'schedspot_service_list' => 'service_list',
            'schedspot_dashboard'    => 'dashboard',
        );

        foreach ( $shortcodes as $shortcode => $function ) {
            add_shortcode( $shortcode, array( $this, $function ) );
        }
    }

    /**
     * Booking form shortcode.
     *
     * @since 0.1.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function booking_form( $atts ) {
        $atts = shortcode_atts( array(
            'service_id' => 0,
            'worker_id'  => 0,
            'class'      => 'schedspot-booking-form',
        ), $atts, 'schedspot_booking_form' );

        ob_start();

        // Enqueue necessary scripts and styles
        $this->enqueue_booking_assets();

        // Handle form submission
        if ( isset( $_POST['schedspot_submit_booking'] ) && wp_verify_nonce( $_POST['schedspot_booking_nonce'], 'schedspot_booking_form' ) ) {
            $this->handle_booking_submission();
        }

        ?>
        <div class="<?php echo esc_attr( $atts['class'] ); ?>">
            <form method="post" id="schedspot-booking-form">
                <?php wp_nonce_field( 'schedspot_booking_form', 'schedspot_booking_nonce' ); ?>
                
                <div class="schedspot-form-section">
                    <h3><?php _e( 'Service Details', 'schedspot' ); ?></h3>
                    
                    <div class="schedspot-form-row">
                        <label for="schedspot_service_id"><?php _e( 'Service', 'schedspot' ); ?> <span class="required">*</span></label>
                        <select name="service_id" id="schedspot_service_id" required>
                            <option value=""><?php _e( 'Select a service', 'schedspot' ); ?></option>
                            <?php echo $this->get_services_options( $atts['service_id'] ); ?>
                        </select>
                    </div>

                    <div class="schedspot-form-row">
                        <label for="schedspot_worker_id"><?php _e( 'Preferred Worker', 'schedspot' ); ?></label>
                        <select name="worker_id" id="schedspot_worker_id">
                            <option value=""><?php _e( 'Any available worker', 'schedspot' ); ?></option>
                            <?php echo $this->get_workers_options( $atts['worker_id'] ); ?>
                        </select>
                    </div>
                </div>

                <div class="schedspot-form-section">
                    <h3><?php _e( 'Date & Time', 'schedspot' ); ?></h3>
                    
                    <div class="schedspot-form-row">
                        <label for="schedspot_booking_date"><?php _e( 'Date', 'schedspot' ); ?> <span class="required">*</span></label>
                        <input type="date" name="booking_date" id="schedspot_booking_date" required min="<?php echo date( 'Y-m-d' ); ?>">
                    </div>

                    <div class="schedspot-form-row">
                        <label for="schedspot_start_time"><?php _e( 'Start Time', 'schedspot' ); ?> <span class="required">*</span></label>
                        <input type="time" name="start_time" id="schedspot_start_time" required>
                    </div>

                    <div class="schedspot-form-row">
                        <label for="schedspot_duration"><?php _e( 'Duration (minutes)', 'schedspot' ); ?> <span class="required">*</span></label>
                        <select name="duration" id="schedspot_duration" required>
                            <option value="30">30 <?php _e( 'minutes', 'schedspot' ); ?></option>
                            <option value="60" selected>1 <?php _e( 'hour', 'schedspot' ); ?></option>
                            <option value="90">1.5 <?php _e( 'hours', 'schedspot' ); ?></option>
                            <option value="120">2 <?php _e( 'hours', 'schedspot' ); ?></option>
                            <option value="180">3 <?php _e( 'hours', 'schedspot' ); ?></option>
                            <option value="240">4 <?php _e( 'hours', 'schedspot' ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="schedspot-form-section">
                    <h3><?php _e( 'Contact Information', 'schedspot' ); ?></h3>
                    
                    <div class="schedspot-form-row">
                        <label for="schedspot_client_name"><?php _e( 'Full Name', 'schedspot' ); ?> <span class="required">*</span></label>
                        <input type="text" name="client_name" id="schedspot_client_name" required value="<?php echo esc_attr( $this->get_current_user_name() ); ?>">
                    </div>

                    <div class="schedspot-form-row">
                        <label for="schedspot_client_email"><?php _e( 'Email Address', 'schedspot' ); ?> <span class="required">*</span></label>
                        <input type="email" name="client_email" id="schedspot_client_email" required value="<?php echo esc_attr( $this->get_current_user_email() ); ?>">
                    </div>

                    <div class="schedspot-form-row">
                        <label for="schedspot_client_phone"><?php _e( 'Phone Number', 'schedspot' ); ?></label>
                        <input type="tel" name="client_phone" id="schedspot_client_phone">
                    </div>

                    <div class="schedspot-form-row">
                        <label for="schedspot_client_address"><?php _e( 'Service Address', 'schedspot' ); ?></label>
                        <textarea name="client_address" id="schedspot-client-address" rows="3" placeholder="<?php _e( 'Enter the address where the service should be performed', 'schedspot' ); ?>"></textarea>
                        <?php if ( get_option( 'schedspot_enable_geofencing', false ) ) : ?>
                        <button type="button" class="schedspot-btn schedspot-btn-secondary schedspot-get-location" style="margin-top: 10px;">
                            <?php _e( 'Use My Current Location', 'schedspot' ); ?>
                        </button>
                        <?php endif; ?>
                    </div>

                    <?php if ( get_option( 'schedspot_enable_geofencing', false ) ) : ?>
                    <!-- Hidden location fields -->
                    <input type="hidden" name="client_lat" id="schedspot-client-lat" value="">
                    <input type="hidden" name="client_lng" id="schedspot-client-lng" value="">

                    <!-- Location Map -->
                    <div class="schedspot-form-row">
                        <label><?php _e( 'Service Location', 'schedspot' ); ?></label>
                        <div id="schedspot-booking-map" style="height: 300px; width: 100%; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px;"></div>
                        <p class="description"><?php _e( 'Click on the map or enter an address above to set the service location.', 'schedspot' ); ?></p>
                    </div>

                    <!-- Nearby Workers Display -->
                    <div id="schedspot-nearby-workers" style="margin-top: 15px;">
                        <!-- Nearby workers will be populated here by JavaScript -->
                    </div>
                    <?php endif; ?>
                </div>

                <div class="schedspot-form-section">
                    <h3><?php _e( 'Additional Information', 'schedspot' ); ?></h3>
                    
                    <div class="schedspot-form-row">
                        <label for="schedspot_notes"><?php _e( 'Notes', 'schedspot' ); ?></label>
                        <textarea name="notes" id="schedspot_notes" rows="4" placeholder="<?php _e( 'Any additional details or special requirements...', 'schedspot' ); ?>"></textarea>
                    </div>
                </div>

                <div class="schedspot-form-actions">
                    <button type="submit" name="schedspot_submit_booking" class="schedspot-btn schedspot-btn-primary">
                        <?php _e( 'Submit Booking Request', 'schedspot' ); ?>
                    </button>
                </div>
            </form>

            <!-- Payment Information -->
            <div class="schedspot-payment-info">
                <h3><?php _e( 'Payment Information', 'schedspot' ); ?></h3>
                <div class="payment-details">
                    <?php
                    $payment_required = get_option( 'schedspot_payment_required', 'deposit' );
                    $deposit_rate = get_option( 'schedspot_deposit_rate', 30 );
                    ?>

                    <?php if ( $payment_required === 'full' ) : ?>
                        <p><?php _e( 'Full payment is required to confirm your booking.', 'schedspot' ); ?></p>
                    <?php elseif ( $payment_required === 'deposit' ) : ?>
                        <p><?php printf( __( 'A %d%% deposit is required to confirm your booking. The remaining balance will be collected after service completion.', 'schedspot' ), $deposit_rate ); ?></p>
                    <?php else : ?>
                        <p><?php _e( 'Payment will be collected after service completion.', 'schedspot' ); ?></p>
                    <?php endif; ?>

                    <div class="payment-methods">
                        <p><strong><?php _e( 'Accepted Payment Methods:', 'schedspot' ); ?></strong></p>
                        <div class="payment-icons">
                            <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                                <?php
                                $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
                                foreach ( $available_gateways as $gateway ) :
                                    if ( $gateway->enabled === 'yes' ) :
                                ?>
                                    <span class="payment-method"><?php echo esc_html( $gateway->get_title() ); ?></span>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            <?php else : ?>
                                <span class="payment-method"><?php _e( 'Credit Card', 'schedspot' ); ?></span>
                                <span class="payment-method"><?php _e( 'PayPal', 'schedspot' ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="security-notice">
                        <small><?php _e( '🔒 Your payment information is secure and encrypted.', 'schedspot' ); ?></small>
                    </div>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Service list shortcode.
     *
     * @since 0.1.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function service_list( $atts ) {
        $atts = shortcode_atts( array(
            'limit'    => 10,
            'category' => '',
            'class'    => 'schedspot-service-list',
        ), $atts, 'schedspot_service_list' );

        ob_start();

        $services = $this->get_services( array(
            'limit'    => absint( $atts['limit'] ),
            'category' => sanitize_text_field( $atts['category'] ),
        ) );

        ?>
        <div class="<?php echo esc_attr( $atts['class'] ); ?>">
            <?php if ( ! empty( $services ) ) : ?>
                <div class="schedspot-services-grid">
                    <?php foreach ( $services as $service ) : ?>
                        <div class="schedspot-service-item">
                            <h3 class="service-name"><?php echo esc_html( $service->name ); ?></h3>
                            <?php if ( ! empty( $service->description ) ) : ?>
                                <p class="service-description"><?php echo esc_html( $service->description ); ?></p>
                            <?php endif; ?>
                            <div class="service-meta">
                                <span class="service-duration"><?php printf( __( 'Duration: %d minutes', 'schedspot' ), $service->duration ); ?></span>
                                <span class="service-price"><?php printf( __( 'From $%.2f', 'schedspot' ), $service->base_price ); ?></span>
                            </div>
                            <div class="service-actions">
                                <a href="<?php echo esc_url( add_query_arg( 'service_id', $service->id, get_permalink( get_option( 'schedspot_booking_page' ) ) ) ); ?>" class="schedspot-btn schedspot-btn-primary">
                                    <?php _e( 'Book Now', 'schedspot' ); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="schedspot-no-services"><?php _e( 'No services available at the moment.', 'schedspot' ); ?></p>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Dashboard shortcode.
     *
     * @since 0.1.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function dashboard( $atts ) {
        $atts = shortcode_atts( array(
            'class' => 'schedspot-dashboard',
        ), $atts, 'schedspot_dashboard' );

        if ( ! is_user_logged_in() ) {
            return '<p>' . __( 'Please log in to access your dashboard.', 'schedspot' ) . '</p>';
        }

        ob_start();

        $current_user = wp_get_current_user();
        $user_role = $this->get_user_schedspot_role( $current_user->ID );

        ?>
        <div class="<?php echo esc_attr( $atts['class'] ); ?>">
            <div class="schedspot-dashboard-header">
                <h2><?php printf( __( 'Welcome, %s', 'schedspot' ), esc_html( $current_user->display_name ) ); ?></h2>
                <p class="user-role"><?php echo esc_html( $this->get_role_display_name( $user_role ) ); ?></p>
            </div>

            <div class="schedspot-dashboard-content">
                <?php if ( 'schedspot_customer' === $user_role ) : ?>
                    <?php $this->render_customer_dashboard( $current_user->ID ); ?>
                <?php elseif ( 'schedspot_worker' === $user_role ) : ?>
                    <?php $this->render_worker_dashboard( $current_user->ID ); ?>
                <?php else : ?>
                    <p><?php _e( 'Your account is not set up for SchedSpot services. Please contact the administrator.', 'schedspot' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Handle booking form submission.
     *
     * @since 0.1.0
     */
    private function handle_booking_submission() {
        // Get current user or create guest user
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            // For now, require login. In future versions, we can create guest bookings
            wp_die( __( 'You must be logged in to make a booking.', 'schedspot' ) );
        }

        // Sanitize and validate form data
        $booking_data = array(
            'user_id'        => $user_id,
            'service_id'     => absint( $_POST['service_id'] ),
            'worker_id'      => absint( $_POST['worker_id'] ),
            'booking_date'   => sanitize_text_field( $_POST['booking_date'] ),
            'start_time'     => sanitize_text_field( $_POST['start_time'] ),
            'duration'       => absint( $_POST['duration'] ),
            'client_name'    => sanitize_text_field( $_POST['client_name'] ),
            'client_email'   => sanitize_email( $_POST['client_email'] ),
            'client_phone'   => sanitize_text_field( $_POST['client_phone'] ),
            'client_address' => sanitize_textarea_field( $_POST['client_address'] ),
            'client_lat'     => isset( $_POST['client_lat'] ) ? floatval( $_POST['client_lat'] ) : null,
            'client_lng'     => isset( $_POST['client_lng'] ) ? floatval( $_POST['client_lng'] ) : null,
            'notes'          => sanitize_textarea_field( $_POST['notes'] ),
        );

        // Validate location if geofencing is enabled
        if ( get_option( 'schedspot_enable_geofencing', false ) ) {
            $geolocation = new SchedSpot_Geolocation();
            $location_errors = array();
            $location_errors = $geolocation->validate_booking_location( $booking_data, $location_errors );

            if ( ! empty( $location_errors ) ) {
                echo '<div class="schedspot-notice schedspot-notice-error">';
                foreach ( $location_errors as $error ) {
                    echo '<p>' . esc_html( $error ) . '</p>';
                }
                echo '</div>';
                return;
            }
        }

        // Calculate end time
        $start_datetime = new DateTime( $booking_data['booking_date'] . ' ' . $booking_data['start_time'] );
        $end_datetime = clone $start_datetime;
        $end_datetime->add( new DateInterval( 'PT' . $booking_data['duration'] . 'M' ) );
        $booking_data['end_time'] = $end_datetime->format( 'H:i:s' );

        // Auto-assign worker if not specified
        if ( empty( $booking_data['worker_id'] ) ) {
            $booking_data['worker_id'] = $this->find_available_worker( $booking_data );
        }

        if ( empty( $booking_data['worker_id'] ) ) {
            echo '<div class="schedspot-notice schedspot-notice-error">' . __( 'No workers are available for the selected time slot.', 'schedspot' ) . '</div>';
            return;
        }

        // Create booking
        $booking_id = SchedSpot_Booking::create_booking( $booking_data );

        if ( is_wp_error( $booking_id ) ) {
            echo '<div class="schedspot-notice schedspot-notice-error">' . esc_html( $booking_id->get_error_message() ) . '</div>';
        } else {
            // Check if payment is required
            $payment_required = get_option( 'schedspot_payment_required', 'deposit' );

            if ( $payment_required !== 'none' && class_exists( 'WooCommerce' ) ) {
                // Get payment URL
                $wc_integration = new SchedSpot_WooCommerce();
                $payment_url = $wc_integration->get_booking_payment_url( $booking_id );

                if ( $payment_url ) {
                    echo '<div class="schedspot-notice schedspot-notice-success">';
                    echo '<p>' . __( 'Booking request submitted successfully!', 'schedspot' ) . '</p>';
                    echo '<p>' . __( 'Please complete your payment to confirm the booking.', 'schedspot' ) . '</p>';
                    echo '<p><a href="' . esc_url( $payment_url ) . '" class="schedspot-btn schedspot-btn-primary">' . __( 'Pay Now', 'schedspot' ) . '</a></p>';
                    echo '</div>';
                } else {
                    echo '<div class="schedspot-notice schedspot-notice-success">' . __( 'Booking request submitted successfully! You will receive a confirmation email shortly.', 'schedspot' ) . '</div>';
                }
            } else {
                echo '<div class="schedspot-notice schedspot-notice-success">' . __( 'Booking request submitted successfully! You will receive a confirmation email shortly.', 'schedspot' ) . '</div>';
            }
        }
    }

    /**
     * Get services options for select dropdown.
     *
     * @since 0.1.0
     * @param int $selected_id Selected service ID.
     * @return string HTML options.
     */
    private function get_services_options( $selected_id = 0 ) {
        $services = $this->get_services();
        $options = '';

        foreach ( $services as $service ) {
            $selected = selected( $selected_id, $service->id, false );
            $options .= sprintf(
                '<option value="%d" %s>%s - $%.2f</option>',
                $service->id,
                $selected,
                esc_html( $service->name ),
                $service->base_price
            );
        }

        return $options;
    }

    /**
     * Get workers options for select dropdown.
     *
     * @since 0.1.0
     * @param int $selected_id Selected worker ID.
     * @return string HTML options.
     */
    private function get_workers_options( $selected_id = 0 ) {
        $workers = get_users( array( 'role' => 'schedspot_worker' ) );
        $options = '';

        foreach ( $workers as $worker ) {
            $selected = selected( $selected_id, $worker->ID, false );
            $options .= sprintf(
                '<option value="%d" %s>%s</option>',
                $worker->ID,
                $selected,
                esc_html( $worker->display_name )
            );
        }

        return $options;
    }

    /**
     * Get services from database.
     *
     * @since 0.1.0
     * @param array $args Query arguments.
     * @return array Array of service objects.
     */
    private function get_services( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'limit'    => 10,
            'category' => '',
        );

        $args = wp_parse_args( $args, $defaults );

        $where_clauses = array( 'is_active = 1' );
        $params = array();

        if ( ! empty( $args['category'] ) ) {
            $where_clauses[] = 'category = %s';
            $params[] = $args['category'];
        }

        $where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
        $limit_sql = sprintf( 'LIMIT %d', absint( $args['limit'] ) );

        $sql = "SELECT * FROM {$wpdb->prefix}schedspot_services {$where_sql} ORDER BY name ASC {$limit_sql}";

        if ( ! empty( $params ) ) {
            $sql = $wpdb->prepare( $sql, $params );
        }

        return $wpdb->get_results( $sql );
    }

    /**
     * Get current user's name.
     *
     * @since 0.1.0
     * @return string User's display name.
     */
    private function get_current_user_name() {
        $current_user = wp_get_current_user();
        return $current_user->exists() ? $current_user->display_name : '';
    }

    /**
     * Get current user's email.
     *
     * @since 0.1.0
     * @return string User's email address.
     */
    private function get_current_user_email() {
        $current_user = wp_get_current_user();
        return $current_user->exists() ? $current_user->user_email : '';
    }

    /**
     * Get user's SchedSpot role.
     *
     * @since 0.1.0
     * @param int $user_id User ID.
     * @return string User's SchedSpot role.
     */
    private function get_user_schedspot_role( $user_id ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return '';
        }

        if ( in_array( 'schedspot_customer', $user->roles ) ) {
            return 'schedspot_customer';
        } elseif ( in_array( 'schedspot_worker', $user->roles ) ) {
            return 'schedspot_worker';
        } elseif ( in_array( 'administrator', $user->roles ) ) {
            return 'administrator';
        }

        return '';
    }

    /**
     * Get role display name.
     *
     * @since 0.1.0
     * @param string $role Role name.
     * @return string Role display name.
     */
    private function get_role_display_name( $role ) {
        $role_names = array(
            'schedspot_customer' => __( 'Customer', 'schedspot' ),
            'schedspot_worker'   => __( 'Service Provider', 'schedspot' ),
            'administrator'      => __( 'Administrator', 'schedspot' ),
        );

        return isset( $role_names[ $role ] ) ? $role_names[ $role ] : __( 'User', 'schedspot' );
    }

    /**
     * Render customer dashboard.
     *
     * @since 0.1.0
     * @param int $user_id User ID.
     */
    private function render_customer_dashboard( $user_id ) {
        $bookings = SchedSpot_Booking::get_bookings( array(
            'user_id' => $user_id,
            'limit'   => 10,
        ) );

        ?>
        <div class="schedspot-customer-dashboard">
            <h3><?php _e( 'My Bookings', 'schedspot' ); ?></h3>
            <?php if ( ! empty( $bookings ) ) : ?>
                <div class="schedspot-bookings-list">
                    <?php foreach ( $bookings as $booking ) : ?>
                        <div class="schedspot-booking-item">
                            <div class="booking-date"><?php echo esc_html( date( 'M j, Y', strtotime( $booking->booking_date ) ) ); ?></div>
                            <div class="booking-time"><?php echo esc_html( date( 'g:i A', strtotime( $booking->start_time ) ) ); ?></div>
                            <div class="booking-status"><?php echo esc_html( ucfirst( $booking->status ) ); ?></div>
                            <div class="booking-cost">$<?php echo esc_html( number_format( $booking->total_cost, 2 ) ); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p><?php _e( 'You have no bookings yet.', 'schedspot' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render worker dashboard.
     *
     * @since 1.0.0
     * @param int $user_id User ID.
     */
    private function render_worker_dashboard( $user_id ) {
        $worker = new SchedSpot_Worker( $user_id );
        $stats = $worker->get_statistics();
        $bookings = SchedSpot_Booking::get_bookings( array(
            'worker_id' => $user_id,
            'limit'     => 10,
            'orderby'   => 'booking_date',
            'order'     => 'DESC',
        ) );

        ?>
        <div class="schedspot-worker-dashboard">
            <!-- Statistics Overview -->
            <div class="schedspot-dashboard-stats">
                <h3><?php _e( 'Overview', 'schedspot' ); ?></h3>
                <div class="schedspot-stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo esc_html( $stats['month_bookings'] ); ?></div>
                        <div class="stat-label"><?php _e( 'This Month', 'schedspot' ); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">$<?php echo esc_html( number_format( $stats['month_earnings'], 2 ) ); ?></div>
                        <div class="stat-label"><?php _e( 'Month Earnings', 'schedspot' ); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo esc_html( $stats['completion_rate'] ); ?>%</div>
                        <div class="stat-label"><?php _e( 'Completion Rate', 'schedspot' ); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo esc_html( number_format( $stats['rating'], 1 ) ); ?></div>
                        <div class="stat-label"><?php _e( 'Rating', 'schedspot' ); ?></div>
                    </div>
                </div>
            </div>

            <!-- Profile Completion -->
            <?php if ( $stats['profile_completion'] < 100 ) : ?>
                <div class="schedspot-profile-completion">
                    <h4><?php _e( 'Complete Your Profile', 'schedspot' ); ?></h4>
                    <div class="schedspot-progress-bar">
                        <div class="progress-fill" style="width: <?php echo esc_attr( $stats['profile_completion'] ); ?>%;"></div>
                        <span class="progress-text"><?php echo esc_html( $stats['profile_completion'] ); ?>% <?php _e( 'Complete', 'schedspot' ); ?></span>
                    </div>
                    <p><?php _e( 'A complete profile helps you get more bookings!', 'schedspot' ); ?></p>
                </div>
            <?php endif; ?>

            <!-- Service Areas Management -->
            <?php if ( get_option( 'schedspot_enable_geofencing', false ) ) : ?>
            <div class="schedspot-service-areas">
                <h4><?php _e( 'Service Areas', 'schedspot' ); ?></h4>
                <p><?php _e( 'Define the areas where you provide services. This helps customers find you when they\'re in your service area.', 'schedspot' ); ?></p>
                <div id="schedspot-service-area-map" style="height: 400px; width: 100%; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px;"></div>
                <div id="schedspot-service-areas-list">
                    <p><?php _e( 'Loading service areas...', 'schedspot' ); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="schedspot-quick-actions">
                <h4><?php _e( 'Quick Actions', 'schedspot' ); ?></h4>
                <div class="action-buttons">
                    <button class="schedspot-btn schedspot-btn-primary" onclick="toggleAvailability()"><?php _e( 'Toggle Availability', 'schedspot' ); ?></button>
                    <button class="schedspot-btn" onclick="openAvailabilityEditor()"><?php _e( 'Edit Schedule', 'schedspot' ); ?></button>
                    <button class="schedspot-btn" onclick="viewEarnings()"><?php _e( 'View Earnings', 'schedspot' ); ?></button>
                    <?php if ( get_option( 'schedspot_enable_geofencing', false ) ) : ?>
                    <button class="schedspot-btn" onclick="manageServiceAreas()"><?php _e( 'Manage Service Areas', 'schedspot' ); ?></button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="schedspot-recent-bookings">
                <h3><?php _e( 'Recent Jobs', 'schedspot' ); ?></h3>
                <?php if ( ! empty( $bookings ) ) : ?>
                    <div class="schedspot-bookings-list">
                        <?php foreach ( $bookings as $booking ) : ?>
                            <div class="schedspot-booking-item" data-booking-id="<?php echo esc_attr( $booking->id ); ?>">
                                <div class="booking-header">
                                    <div class="booking-client">
                                        <strong><?php echo esc_html( $booking->client_details['name'] ); ?></strong>
                                        <span class="booking-status status-<?php echo esc_attr( $booking->status ); ?>">
                                            <?php echo esc_html( ucfirst( str_replace( '_', ' ', $booking->status ) ) ); ?>
                                        </span>
                                    </div>
                                    <div class="booking-earnings">$<?php echo esc_html( number_format( $booking->total_cost - $booking->commission_amount, 2 ) ); ?></div>
                                </div>
                                <div class="booking-details">
                                    <div class="booking-datetime">
                                        <span class="booking-date"><?php echo esc_html( date( 'M j, Y', strtotime( $booking->booking_date ) ) ); ?></span>
                                        <span class="booking-time"><?php echo esc_html( date( 'g:i A', strtotime( $booking->start_time ) ) ); ?></span>
                                        <span class="booking-duration"><?php printf( __( '%d min', 'schedspot' ), $booking->duration ); ?></span>
                                    </div>
                                    <?php if ( ! empty( $booking->client_details['address'] ) ) : ?>
                                        <div class="booking-address"><?php echo esc_html( $booking->client_details['address'] ); ?></div>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $booking->notes ) ) : ?>
                                        <div class="booking-notes"><?php echo esc_html( $booking->notes ); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="booking-actions">
                                    <?php if ( $booking->status === 'pending' ) : ?>
                                        <button class="schedspot-btn schedspot-btn-small" onclick="updateBookingStatus(<?php echo esc_attr( $booking->id ); ?>, 'confirmed')"><?php _e( 'Accept', 'schedspot' ); ?></button>
                                        <button class="schedspot-btn schedspot-btn-small schedspot-btn-secondary" onclick="updateBookingStatus(<?php echo esc_attr( $booking->id ); ?>, 'cancelled')"><?php _e( 'Decline', 'schedspot' ); ?></button>
                                    <?php elseif ( $booking->status === 'confirmed' ) : ?>
                                        <button class="schedspot-btn schedspot-btn-small" onclick="updateBookingStatus(<?php echo esc_attr( $booking->id ); ?>, 'in_progress')"><?php _e( 'Start Job', 'schedspot' ); ?></button>
                                        <button class="schedspot-btn schedspot-btn-small schedspot-btn-secondary" onclick="rescheduleBooking(<?php echo esc_attr( $booking->id ); ?>)"><?php _e( 'Reschedule', 'schedspot' ); ?></button>
                                    <?php elseif ( $booking->status === 'in_progress' ) : ?>
                                        <button class="schedspot-btn schedspot-btn-small" onclick="updateBookingStatus(<?php echo esc_attr( $booking->id ); ?>, 'completed')"><?php _e( 'Complete', 'schedspot' ); ?></button>
                                    <?php endif; ?>
                                    <button class="schedspot-btn schedspot-btn-small schedspot-btn-link" onclick="contactClient('<?php echo esc_attr( $booking->client_details['email'] ); ?>', '<?php echo esc_attr( $booking->client_details['phone'] ); ?>')"><?php _e( 'Contact', 'schedspot' ); ?></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="schedspot-view-all">
                        <a href="#" onclick="loadAllBookings()" class="schedspot-btn schedspot-btn-link"><?php _e( 'View All Jobs', 'schedspot' ); ?></a>
                    </div>
                <?php else : ?>
                    <div class="schedspot-empty-state">
                        <p><?php _e( 'You have no jobs scheduled.', 'schedspot' ); ?></p>
                        <p><?php _e( 'Complete your profile and set your availability to start receiving bookings!', 'schedspot' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
        // Worker dashboard JavaScript functions
        function toggleAvailability() {
            const restUrl = '<?php echo rest_url( 'schedspot/v1/workers/' . $user_id . '/profile' ); ?>';
            const nonce = '<?php echo wp_create_nonce( 'wp_rest' ); ?>';

            fetch(restUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                body: JSON.stringify({
                    is_available: <?php echo $worker->profile['is_available'] ? 'false' : 'true'; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.is_available !== undefined) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function updateBookingStatus(bookingId, status) {
            const restUrl = '<?php echo rest_url( 'schedspot/v1/bookings/' ); ?>' + bookingId;
            const nonce = '<?php echo wp_create_nonce( 'wp_rest' ); ?>';

            fetch(restUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.id) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function contactClient(email, phone) {
            const message = '<?php _e( 'Choose contact method:', 'schedspot' ); ?>';
            const emailText = '<?php _e( 'Email', 'schedspot' ); ?>';
            const phoneText = '<?php _e( 'Phone', 'schedspot' ); ?>';

            if (email && phone) {
                const choice = confirm(message + '\n\n' + emailText + ': ' + email + '\n' + phoneText + ': ' + phone + '\n\n<?php _e( 'Click OK for email, Cancel for phone', 'schedspot' ); ?>');
                if (choice) {
                    window.location.href = 'mailto:' + email;
                } else {
                    window.location.href = 'tel:' + phone;
                }
            } else if (email) {
                window.location.href = 'mailto:' + email;
            } else if (phone) {
                window.location.href = 'tel:' + phone;
            }
        }

        function openAvailabilityEditor() {
            // This would open a modal or redirect to availability management
            alert('<?php _e( 'Availability editor coming soon!', 'schedspot' ); ?>');
        }

        function viewEarnings() {
            // This would show detailed earnings breakdown
            alert('<?php _e( 'Detailed earnings view coming soon!', 'schedspot' ); ?>');
        }

        function rescheduleBooking(bookingId) {
            // This would open a reschedule interface
            alert('<?php _e( 'Reschedule feature coming soon!', 'schedspot' ); ?>');
        }

        function loadAllBookings() {
            // This would load all bookings with pagination
            alert('<?php _e( 'Full booking history coming soon!', 'schedspot' ); ?>');
        }
        </script>
        <?php
    }

    /**
     * Find available worker for booking.
     *
     * @since 0.1.0
     * @param array $booking_data Booking data.
     * @return int Worker ID or 0 if none found.
     */
    private function find_available_worker( $booking_data ) {
        $workers = get_users( array( 'role' => 'schedspot_worker' ) );

        foreach ( $workers as $worker ) {
            $conflict = SchedSpot_Booking::check_booking_conflict(
                $worker->ID,
                $booking_data['booking_date'],
                $booking_data['start_time'],
                $booking_data['end_time']
            );

            if ( ! $conflict ) {
                return $worker->ID;
            }
        }

        return 0;
    }

    /**
     * Enqueue booking form assets.
     *
     * @since 0.1.0
     */
    private function enqueue_booking_assets() {
        // For now, we'll add inline styles. In future versions, we'll use separate CSS files
        add_action( 'wp_footer', array( $this, 'output_booking_styles' ) );

        // Enqueue geolocation scripts if enabled
        if ( get_option( 'schedspot_enable_geofencing', false ) ) {
            $geolocation = new SchedSpot_Geolocation();
            $geolocation->enqueue_frontend_scripts();
        }
    }

    /**
     * Output booking form styles.
     *
     * @since 0.1.0
     */
    public function output_booking_styles() {
        ?>
        <style>
        .schedspot-booking-form {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .schedspot-form-section {
            margin-bottom: 30px;
        }
        .schedspot-form-section h3 {
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 5px;
        }
        .schedspot-form-row {
            margin-bottom: 15px;
        }
        .schedspot-form-row label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .schedspot-form-row input,
        .schedspot-form-row select,
        .schedspot-form-row textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
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
            border-radius: 3px;
            cursor: pointer;
        }
        .schedspot-btn:hover {
            background: #005a87;
        }
        .schedspot-notice {
            padding: 10px;
            margin: 15px 0;
            border-radius: 3px;
        }
        .schedspot-notice-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .schedspot-notice-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .required {
            color: red;
        }
        </style>
        <?php
    }
}
