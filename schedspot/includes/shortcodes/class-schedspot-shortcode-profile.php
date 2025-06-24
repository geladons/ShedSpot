<?php
/**
 * Profile Shortcode Class
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Shortcode_Profile Class.
 *
 * Handles the user profile management shortcode functionality.
 *
 * @class SchedSpot_Shortcode_Profile
 * @version 1.0.0
 */
class SchedSpot_Shortcode_Profile {

    /**
     * Render profile shortcode.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Rendered output.
     */
    public static function render( $atts ) {
        $instance = new self();
        return $instance->render_profile( $atts );
    }

    /**
     * Render the profile management interface.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Rendered output.
     */
    public function render_profile( $atts ) {
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            return $this->render_login_required();
        }

        // Parse attributes
        $atts = shortcode_atts( array(
            'tab' => 'general',
        ), $atts, 'schedspot_profile' );

        $current_user = wp_get_current_user();

        // Use SchedSpot's effective user role detection for admin role switching
        $user_role = SchedSpot()->get_effective_user_role();

        // If no effective role found, fall back to primary role detection
        if ( empty( $user_role ) ) {
            $user_role = $this->get_user_primary_role( $current_user );
        }
        
        // Handle form submissions
        $this->handle_profile_actions();
        
        // Get profile data
        $profile_data = $this->get_profile_data( $current_user, $user_role );
        
        // Get available tabs based on user role
        $available_tabs = $this->get_available_tabs( $user_role );
        
        // Validate current tab
        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : $atts['tab'];
        if ( ! array_key_exists( $current_tab, $available_tabs ) ) {
            $current_tab = 'general';
        }

        // Start output buffering
        ob_start();
        
        // Load template
        include SCHEDSPOT_PLUGIN_DIR . 'templates/shortcodes/profile.php';
        
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
            return 'administrator';
        }
        
        return 'subscriber';
    }

    /**
     * Get available tabs for user role.
     *
     * @since 1.0.0
     * @param string $user_role User role.
     * @return array Available tabs.
     */
    private function get_available_tabs( $user_role ) {
        $tabs = array(
            'general' => __( 'General', 'schedspot' ),
            'notifications' => __( 'Notifications', 'schedspot' ),
            'privacy' => __( 'Privacy', 'schedspot' ),
        );

        if ( $user_role === 'schedspot_worker' ) {
            $tabs['professional'] = __( 'Professional Info', 'schedspot' );
            $tabs['availability'] = __( 'Availability', 'schedspot' );
            $tabs['services'] = __( 'Services', 'schedspot' );
            $tabs['location'] = __( 'Location', 'schedspot' );
        }

        return $tabs;
    }

    /**
     * Get profile data for user.
     *
     * @since 1.0.0
     * @param WP_User $user      User object.
     * @param string  $user_role User role.
     * @return array Profile data.
     */
    private function get_profile_data( $user, $user_role ) {
        $data = array(
            'user' => $user,
            'role' => $user_role,
            'general' => array(
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->user_email,
                'phone' => get_user_meta( $user->ID, 'phone', true ),
                'bio' => get_user_meta( $user->ID, 'description', true ),
            ),
            'notifications' => array(
                'email_notifications' => get_user_meta( $user->ID, 'schedspot_email_notifications', true ) !== '0',
                'sms_notifications' => get_user_meta( $user->ID, 'schedspot_sms_notifications', true ) === '1',
                'booking_reminders' => get_user_meta( $user->ID, 'schedspot_booking_reminders', true ) !== '0',
                'marketing_emails' => get_user_meta( $user->ID, 'schedspot_marketing_emails', true ) === '1',
            ),
            'privacy' => array(
                'profile_visibility' => get_user_meta( $user->ID, 'schedspot_profile_visibility', true ) ?: 'public',
                'show_contact_info' => get_user_meta( $user->ID, 'schedspot_show_contact_info', true ) !== '0',
                'allow_direct_booking' => get_user_meta( $user->ID, 'schedspot_allow_direct_booking', true ) !== '0',
            ),
        );

        if ( $user_role === 'schedspot_worker' ) {
            $worker_profile = get_user_meta( $user->ID, 'schedspot_worker_profile', true ) ?: array();
            $availability = get_user_meta( $user->ID, 'schedspot_worker_availability', true ) ?: array();
            $assigned_services = get_user_meta( $user->ID, 'schedspot_assigned_services', true ) ?: array();

            $data['professional'] = array(
                'bio' => $worker_profile['bio'] ?? '',
                'skills' => $worker_profile['skills'] ?? array(),
                'experience_years' => $worker_profile['experience_years'] ?? '',
                'hourly_rate' => $worker_profile['hourly_rate'] ?? '',
                'certifications' => $worker_profile['certifications'] ?? array(),
            );

            $data['availability'] = $availability;
            $data['assigned_services'] = $assigned_services;
            $data['location'] = array(
                'address' => $worker_profile['address'] ?? '',
                'latitude' => get_user_meta( $user->ID, 'schedspot_latitude', true ),
                'longitude' => get_user_meta( $user->ID, 'schedspot_longitude', true ),
                'service_radius' => get_user_meta( $user->ID, 'schedspot_service_radius', true ) ?: 25,
            );
        }

        return $data;
    }

    /**
     * Handle profile form actions.
     *
     * @since 1.0.0
     */
    private function handle_profile_actions() {
        if ( ! isset( $_POST['action'] ) ) {
            return;
        }

        $action = sanitize_text_field( $_POST['action'] );
        
        switch ( $action ) {
            case 'update_general':
                $this->handle_general_update();
                break;
            case 'update_professional':
                $this->handle_professional_update();
                break;
            case 'update_availability':
                $this->handle_availability_update();
                break;
            case 'update_notifications':
                $this->handle_notifications_update();
                break;
            case 'update_privacy':
                $this->handle_privacy_update();
                break;
            case 'update_location':
                $this->handle_location_update();
                break;
            case 'upload_avatar':
                $this->handle_avatar_upload();
                break;
        }
    }

    /**
     * Handle general profile update.
     *
     * @since 1.0.0
     */
    private function handle_general_update() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'update_general_profile' ) ) {
            return;
        }

        $user_id = get_current_user_id();
        
        // Update user data
        $user_data = array(
            'ID' => $user_id,
            'first_name' => sanitize_text_field( $_POST['first_name'] ),
            'last_name' => sanitize_text_field( $_POST['last_name'] ),
            'user_email' => sanitize_email( $_POST['email'] ),
            'description' => sanitize_textarea_field( $_POST['bio'] ),
        );
        
        wp_update_user( $user_data );
        
        // Update phone
        update_user_meta( $user_id, 'phone', sanitize_text_field( $_POST['phone'] ) );
        
        wp_redirect( add_query_arg( array( 'tab' => 'general', 'updated' => 'general' ) ) );
        exit;
    }

    /**
     * Handle professional profile update.
     *
     * @since 1.0.0
     */
    private function handle_professional_update() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'update_professional_profile' ) ) {
            return;
        }

        $user_id = get_current_user_id();
        $profile = get_user_meta( $user_id, 'schedspot_worker_profile', true ) ?: array();
        
        $profile['bio'] = sanitize_textarea_field( $_POST['professional_bio'] );
        $profile['skills'] = array_map( 'sanitize_text_field', explode( ',', $_POST['skills'] ) );
        $profile['experience_years'] = intval( $_POST['experience_years'] );
        $profile['hourly_rate'] = floatval( $_POST['hourly_rate'] );
        $profile['certifications'] = array_map( 'sanitize_text_field', explode( ',', $_POST['certifications'] ) );
        $profile['updated_at'] = current_time( 'mysql' );
        
        update_user_meta( $user_id, 'schedspot_worker_profile', $profile );
        
        wp_redirect( add_query_arg( array( 'tab' => 'professional', 'updated' => 'professional' ) ) );
        exit;
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
        $availability_data = json_decode( stripslashes( $_POST['availability_data'] ), true );
        
        update_user_meta( $user_id, 'schedspot_worker_availability', $availability_data );
        
        wp_redirect( add_query_arg( array( 'tab' => 'availability', 'updated' => 'availability' ) ) );
        exit;
    }

    /**
     * Handle notifications update.
     *
     * @since 1.0.0
     */
    private function handle_notifications_update() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'update_notifications' ) ) {
            return;
        }

        $user_id = get_current_user_id();
        
        update_user_meta( $user_id, 'schedspot_email_notifications', isset( $_POST['email_notifications'] ) ? '1' : '0' );
        update_user_meta( $user_id, 'schedspot_sms_notifications', isset( $_POST['sms_notifications'] ) ? '1' : '0' );
        update_user_meta( $user_id, 'schedspot_booking_reminders', isset( $_POST['booking_reminders'] ) ? '1' : '0' );
        update_user_meta( $user_id, 'schedspot_marketing_emails', isset( $_POST['marketing_emails'] ) ? '1' : '0' );
        
        wp_redirect( add_query_arg( array( 'tab' => 'notifications', 'updated' => 'notifications' ) ) );
        exit;
    }

    /**
     * Handle privacy settings update.
     *
     * @since 1.0.0
     */
    private function handle_privacy_update() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'update_privacy' ) ) {
            return;
        }

        $user_id = get_current_user_id();
        
        update_user_meta( $user_id, 'schedspot_profile_visibility', sanitize_text_field( $_POST['profile_visibility'] ) );
        update_user_meta( $user_id, 'schedspot_show_contact_info', isset( $_POST['show_contact_info'] ) ? '1' : '0' );
        update_user_meta( $user_id, 'schedspot_allow_direct_booking', isset( $_POST['allow_direct_booking'] ) ? '1' : '0' );
        
        wp_redirect( add_query_arg( array( 'tab' => 'privacy', 'updated' => 'privacy' ) ) );
        exit;
    }

    /**
     * Handle location update.
     *
     * @since 1.0.0
     */
    private function handle_location_update() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'update_location' ) ) {
            return;
        }

        $user_id = get_current_user_id();
        $profile = get_user_meta( $user_id, 'schedspot_worker_profile', true ) ?: array();
        
        $profile['address'] = sanitize_text_field( $_POST['address'] );
        
        update_user_meta( $user_id, 'schedspot_worker_profile', $profile );
        update_user_meta( $user_id, 'schedspot_latitude', floatval( $_POST['latitude'] ) );
        update_user_meta( $user_id, 'schedspot_longitude', floatval( $_POST['longitude'] ) );
        update_user_meta( $user_id, 'schedspot_service_radius', intval( $_POST['service_radius'] ) );
        
        wp_redirect( add_query_arg( array( 'tab' => 'location', 'updated' => 'location' ) ) );
        exit;
    }

    /**
     * Handle avatar upload.
     *
     * @since 1.0.0
     */
    private function handle_avatar_upload() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'upload_avatar' ) ) {
            return;
        }

        if ( ! isset( $_FILES['avatar'] ) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK ) {
            wp_redirect( add_query_arg( array( 'tab' => 'general', 'error' => 'upload_failed' ) ) );
            exit;
        }

        $uploaded_file = $this->handle_avatar_file_upload( $_FILES['avatar'] );
        
        if ( $uploaded_file ) {
            $user_id = get_current_user_id();
            update_user_meta( $user_id, 'schedspot_custom_avatar', $uploaded_file );
            
            wp_redirect( add_query_arg( array( 'tab' => 'general', 'updated' => 'avatar' ) ) );
            exit;
        } else {
            wp_redirect( add_query_arg( array( 'tab' => 'general', 'error' => 'upload_failed' ) ) );
            exit;
        }
    }

    /**
     * Handle avatar file upload.
     *
     * @since 1.0.0
     * @param array $file File data from $_FILES.
     * @return string|false Upload URL or false on failure.
     */
    private function handle_avatar_file_upload( $file ) {
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        $upload_overrides = array(
            'test_form' => false,
            'mimes' => array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'gif' => 'image/gif',
                'png' => 'image/png',
            ),
        );

        $uploaded_file = wp_handle_upload( $file, $upload_overrides );

        if ( $uploaded_file && ! isset( $uploaded_file['error'] ) ) {
            return $uploaded_file['url'];
        }

        return false;
    }

    /**
     * Get available services for worker.
     *
     * @since 1.0.0
     * @return array Available services.
     */
    public function get_available_services() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT id, name, base_price FROM {$wpdb->prefix}schedspot_services WHERE is_active = 1 ORDER BY name ASC"
        );
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
            <p><?php _e( 'Please log in to access your profile settings.', 'schedspot' ); ?></p>
            <a href="<?php echo wp_login_url( get_permalink() ); ?>" class="schedspot-btn schedspot-btn-primary">
                <?php _e( 'Login', 'schedspot' ); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get default availability schedule.
     *
     * @since 1.0.0
     * @return array Default schedule.
     */
    public static function get_default_availability() {
        $days = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
        $schedule = array();

        foreach ( $days as $day ) {
            $schedule[ $day ] = array(
                array(
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'is_available' => in_array( $day, array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday' ) ),
                )
            );
        }

        return $schedule;
    }

    /**
     * Format availability for display.
     *
     * @since 1.0.0
     * @param array $availability Availability data.
     * @return array Formatted availability.
     */
    public static function format_availability_display( $availability ) {
        $days = array(
            'monday' => __( 'Monday', 'schedspot' ),
            'tuesday' => __( 'Tuesday', 'schedspot' ),
            'wednesday' => __( 'Wednesday', 'schedspot' ),
            'thursday' => __( 'Thursday', 'schedspot' ),
            'friday' => __( 'Friday', 'schedspot' ),
            'saturday' => __( 'Saturday', 'schedspot' ),
            'sunday' => __( 'Sunday', 'schedspot' ),
        );

        $formatted = array();

        foreach ( $days as $day_key => $day_name ) {
            $day_schedule = $availability[ $day_key ] ?? array();
            $formatted[ $day_key ] = array(
                'name' => $day_name,
                'slots' => $day_schedule,
                'is_available' => ! empty( array_filter( $day_schedule, function( $slot ) {
                    return $slot['is_available'] ?? false;
                } ) ),
            );
        }

        return $formatted;
    }
}
