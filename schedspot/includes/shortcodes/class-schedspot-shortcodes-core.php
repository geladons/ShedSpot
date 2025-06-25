<?php
/**
 * Shortcodes Core Class
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Shortcodes_Core Class.
 *
 * Handles shortcode registration and core functionality.
 *
 * @class SchedSpot_Shortcodes_Core
 * @version 1.0.0
 */
class SchedSpot_Shortcodes_Core {

    /**
     * Registered shortcodes.
     *
     * @var array
     */
    private $shortcodes = array();

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize shortcodes functionality.
     *
     * @since 1.0.0
     */
    public function init() {
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_shortcode_assets' ) );
        add_filter( 'the_content', array( $this, 'handle_shortcode_navigation' ) );

        $this->define_shortcodes();
    }

    /**
     * Define available shortcodes.
     *
     * @since 1.0.0
     */
    private function define_shortcodes() {
        $this->shortcodes = array(
            'schedspot_booking_form' => array(
                'callback' => array( 'SchedSpot_Shortcode_Booking_Form', 'render' ),
                'class' => 'SchedSpot_Shortcode_Booking_Form',
                'assets' => array( 'frontend-enhanced', 'booking-form' ),
                'description' => __( 'Display the service booking form', 'schedspot' ),
                'attributes' => array(
                    'service_id' => array(
                        'type' => 'number',
                        'description' => __( 'Specific service ID to book', 'schedspot' ),
                        'default' => '',
                    ),
                    'worker_id' => array(
                        'type' => 'number',
                        'description' => __( 'Specific worker ID to book with', 'schedspot' ),
                        'default' => '',
                    ),
                    'show_workers' => array(
                        'type' => 'boolean',
                        'description' => __( 'Show worker selection', 'schedspot' ),
                        'default' => 'true',
                    ),
                ),
            ),
            'schedspot_dashboard' => array(
                'callback' => array( 'SchedSpot_Shortcode_Dashboard', 'render' ),
                'class' => 'SchedSpot_Shortcode_Dashboard',
                'assets' => array( 'frontend-enhanced', 'dashboard' ),
                'description' => __( 'Display user dashboard', 'schedspot' ),
                'attributes' => array(
                    'view' => array(
                        'type' => 'string',
                        'description' => __( 'Default view (bookings, profile, etc.)', 'schedspot' ),
                        'default' => 'bookings',
                    ),
                ),
            ),
            'schedspot_messages' => array(
                'callback' => array( 'SchedSpot_Shortcode_Messages', 'render' ),
                'class' => 'SchedSpot_Shortcode_Messages',
                'assets' => array( 'frontend-enhanced', 'messaging' ),
                'description' => __( 'Display messaging interface', 'schedspot' ),
                'attributes' => array(
                    'user_id' => array(
                        'type' => 'number',
                        'description' => __( 'Start conversation with specific user', 'schedspot' ),
                        'default' => '',
                    ),
                ),
            ),
            'schedspot_profile' => array(
                'callback' => array( 'SchedSpot_Shortcode_Profile', 'render' ),
                'class' => 'SchedSpot_Shortcode_Profile',
                'assets' => array( 'frontend-enhanced', 'profile' ),
                'description' => __( 'Display user profile management', 'schedspot' ),
                'attributes' => array(
                    'tab' => array(
                        'type' => 'string',
                        'description' => __( 'Default tab to show', 'schedspot' ),
                        'default' => 'general',
                    ),
                ),
            ),
            'schedspot_services' => array(
                'callback' => array( $this, 'render_services_list' ),
                'class' => 'SchedSpot_Shortcodes_Core',
                'assets' => array( 'frontend-enhanced', 'booking-form' ),
                'description' => __( 'Display services list', 'schedspot' ),
                'attributes' => array(
                    'limit' => array(
                        'type' => 'number',
                        'description' => __( 'Number of services to show', 'schedspot' ),
                        'default' => '12',
                    ),
                    'category' => array(
                        'type' => 'string',
                        'description' => __( 'Filter by category', 'schedspot' ),
                        'default' => '',
                    ),
                    'columns' => array(
                        'type' => 'number',
                        'description' => __( 'Number of columns', 'schedspot' ),
                        'default' => '3',
                    ),
                ),
            ),
            'schedspot_workers_grid' => array(
                'callback' => array( $this, 'render_workers_grid' ),
                'class' => 'SchedSpot_Shortcodes_Core',
                'assets' => array( 'frontend-enhanced', 'booking-form' ),
                'description' => __( 'Display workers grid', 'schedspot' ),
                'attributes' => array(
                    'limit' => array(
                        'type' => 'number',
                        'description' => __( 'Number of workers to show', 'schedspot' ),
                        'default' => '12',
                    ),
                    'service_id' => array(
                        'type' => 'number',
                        'description' => __( 'Filter by service ID', 'schedspot' ),
                        'default' => '',
                    ),
                ),
            ),
        );
    }

    /**
     * Register all shortcodes.
     *
     * @since 1.0.0
     */
    public function register_shortcodes() {
        foreach ( $this->shortcodes as $tag => $config ) {
            add_shortcode( $tag, $config['callback'] );
        }
    }

    /**
     * Enqueue shortcode assets.
     *
     * @since 1.0.0
     */
    public function enqueue_shortcode_assets() {
        global $post;

        if ( ! $post ) {
            return;
        }

        $content = $post->post_content;
        $assets_to_load = array();

        // Check which shortcodes are present in content
        foreach ( $this->shortcodes as $tag => $config ) {
            if ( has_shortcode( $content, $tag ) ) {
                $assets_to_load = array_merge( $assets_to_load, $config['assets'] );
            }
        }

        // Remove duplicates
        $assets_to_load = array_unique( $assets_to_load );

        // Enqueue required assets
        foreach ( $assets_to_load as $asset ) {
            $this->enqueue_asset( $asset );
        }

        // Always enqueue common frontend assets if any shortcode is present
        if ( ! empty( $assets_to_load ) ) {
            $this->enqueue_common_assets();
        }
    }

    /**
     * Enqueue specific asset.
     *
     * @since 1.0.0
     * @param string $asset Asset name.
     */
    private function enqueue_asset( $asset ) {
        // Check if CSS file exists and enqueue
        $css_path = SCHEDSPOT_PLUGIN_DIR . 'assets/css/' . $asset . '.css';
        if ( file_exists( $css_path ) ) {
            wp_enqueue_style(
                'schedspot-' . $asset,
                SCHEDSPOT_PLUGIN_URL . 'assets/css/' . $asset . '.css',
                array(),
                SCHEDSPOT_VERSION
            );
        }

        // Check if JS file exists and enqueue
        $js_path = SCHEDSPOT_PLUGIN_DIR . 'assets/js/' . $asset . '.js';
        if ( file_exists( $js_path ) ) {
            wp_enqueue_script(
                'schedspot-' . $asset,
                SCHEDSPOT_PLUGIN_URL . 'assets/js/' . $asset . '.js',
                array( 'jquery' ),
                SCHEDSPOT_VERSION,
                true
            );
        }
    }

    /**
     * Enqueue common frontend assets.
     *
     * @since 1.0.0
     */
    private function enqueue_common_assets() {
        // Ensure frontend.js is loaded for common functionality
        $this->enqueue_asset( 'frontend' );

        // Localize script with common data - use frontend script as fallback
        $script_handle = wp_script_is( 'schedspot-booking-form', 'enqueued' ) ? 'schedspot-booking-form' : 'schedspot-frontend';
        wp_localize_script( $script_handle, 'schedspot_frontend', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'rest_url' => rest_url( 'schedspot/v1/' ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'current_user_id' => get_current_user_id(),
            'user_id' => get_current_user_id(),
            'is_logged_in' => is_user_logged_in(),
            'messages_url' => $this->get_messages_url(),
            'dashboard_url' => $this->get_dashboard_url(),
            'profile_url' => $this->get_profile_url(),
            'max_file_size' => 5, // MB
            'allowed_file_types' => 'jpg,jpeg,png,gif,pdf,doc,docx',
            'strings' => array(
                'processing' => __( 'Processing...', 'schedspot' ),
                'error' => __( 'An error occurred. Please try again.', 'schedspot' ),
                'success' => __( 'Success!', 'schedspot' ),
                'confirm_cancel' => __( 'Are you sure you want to cancel this booking?', 'schedspot' ),
                'confirm_complete' => __( 'Mark this booking as completed?', 'schedspot' ),
                'confirm_deposit_request' => __( 'Request deposit payment from client?', 'schedspot' ),
                'confirm_progress_request' => __( 'Request progress payment from client?', 'schedspot' ),
                'confirm_final_request' => __( 'Request final payment from client?', 'schedspot' ),
                'selected_worker' => __( 'Selected worker', 'schedspot' ),
                'invalid_email' => __( 'Please enter a valid email address.', 'schedspot' ),
                'invalid_phone' => __( 'Please enter a valid phone number.', 'schedspot' ),
                'field_required' => __( 'This field is required.', 'schedspot' ),
                'reschedule_booking' => __( 'Reschedule Booking', 'schedspot' ),
                'new_date' => __( 'New Date', 'schedspot' ),
                'new_time' => __( 'New Time', 'schedspot' ),
                'reason_optional' => __( 'Reason (Optional)', 'schedspot' ),
                'cancel' => __( 'Cancel', 'schedspot' ),
                'reschedule' => __( 'Reschedule', 'schedspot' ),
                'reschedule_success' => __( 'Booking rescheduled successfully!', 'schedspot' ),
                'available' => __( 'Available', 'schedspot' ),
                'unavailable' => __( 'Unavailable', 'schedspot' ),
                'go_available' => __( 'Go Available', 'schedspot' ),
                'go_unavailable' => __( 'Go Unavailable', 'schedspot' ),
                'no_conversations' => __( 'No conversations yet.', 'schedspot' ),
                'no_messages' => __( 'No messages in this conversation.', 'schedspot' ),
                'select_conversation' => __( 'Please select a conversation first.', 'schedspot' ),
                'error_loading_conversations' => __( 'Error loading conversations.', 'schedspot' ),
                'error_loading_messages' => __( 'Error loading messages.', 'schedspot' ),
                'error_sending_message' => __( 'Error sending message.', 'schedspot' ),
                'file_too_large' => __( 'File is too large. Maximum size is 5MB.', 'schedspot' ),
                'file_type_not_allowed' => __( 'File type not allowed.', 'schedspot' ),
                'image_too_large' => __( 'Image is too large. Maximum size is 5MB.', 'schedspot' ),
                'invalid_image_type' => __( 'Invalid image type. Please use JPG, PNG, or GIF.', 'schedspot' ),
                'setting_saved' => __( 'Setting saved successfully.', 'schedspot' ),
                'getting_location' => __( 'Getting location...', 'schedspot' ),
                'get_current_location' => __( 'Get Current Location', 'schedspot' ),
                'location_updated' => __( 'Location updated successfully.', 'schedspot' ),
                'location_error' => __( 'Error getting location.', 'schedspot' ),
                'geolocation_not_supported' => __( 'Geolocation is not supported by this browser.', 'schedspot' ),
                'saving' => __( 'Saving...', 'schedspot' ),
            ),
        ) );
    }

    /**
     * Handle shortcode navigation.
     *
     * @since 1.0.0
     * @param string $content Post content.
     * @return string Modified content.
     */
    public function handle_shortcode_navigation( $content ) {
        // Handle navigation between shortcodes via URL parameters
        if ( isset( $_GET['schedspot_action'] ) ) {
            $action = sanitize_text_field( $_GET['schedspot_action'] );

            switch ( $action ) {
                case 'booking_form':
                    return do_shortcode( '[schedspot_booking_form]' );
                case 'dashboard':
                    return do_shortcode( '[schedspot_dashboard]' );
                case 'messages':
                    return do_shortcode( '[schedspot_messages]' );
                case 'profile':
                    return do_shortcode( '[schedspot_profile]' );
            }
        }

        return $content;
    }

    /**
     * Render services list shortcode.
     *
     * @since 1.7.0
     * @param array $atts Shortcode attributes.
     * @return string Rendered output.
     */
    public function render_services_list( $atts ) {
        $atts = shortcode_atts( array(
            'limit' => 12,
            'category' => '',
            'columns' => 3,
        ), $atts, 'schedspot_services' );

        $services = $this->get_services_for_list( $atts );

        ob_start();
        include SCHEDSPOT_PLUGIN_DIR . 'templates/shortcodes/services-list.php';
        return ob_get_clean();
    }

    /**
     * Render workers grid shortcode.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Rendered output.
     */
    public function render_workers_grid( $atts ) {
        $atts = shortcode_atts( array(
            'limit' => 12,
            'service_id' => '',
        ), $atts, 'schedspot_workers_grid' );

        $workers = $this->get_workers_for_grid( $atts );

        ob_start();
        include SCHEDSPOT_PLUGIN_DIR . 'templates/shortcodes/workers-grid.php';
        return ob_get_clean();
    }

    /**
     * Get services for list display.
     *
     * @since 1.7.0
     * @param array $atts Shortcode attributes.
     * @return array Services data.
     */
    private function get_services_for_list( $atts ) {
        global $wpdb;

        $where_clause = 'WHERE is_active = 1';
        $params = array();

        if ( ! empty( $atts['category'] ) ) {
            $where_clause .= ' AND category = %s';
            $params[] = $atts['category'];
        }

        $limit_clause = '';
        if ( intval( $atts['limit'] ) > 0 ) {
            $limit_clause = ' LIMIT %d';
            $params[] = intval( $atts['limit'] );
        }

        $query = "SELECT * FROM {$wpdb->prefix}schedspot_services {$where_clause} ORDER BY name ASC{$limit_clause}";

        if ( ! empty( $params ) ) {
            $query = $wpdb->prepare( $query, $params );
        }

        $services = $wpdb->get_results( $query );

        return $services ?: array();
    }

    /**
     * Get workers for grid display.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return array Workers data.
     */
    private function get_workers_for_grid( $atts ) {
        $args = array(
            'role' => 'schedspot_worker',
            'number' => intval( $atts['limit'] ),
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
            if ( $atts['service_id'] && $assigned_services ) {
                if ( ! in_array( intval( $atts['service_id'] ), $assigned_services ) ) {
                    continue;
                }
            }

            $workers[] = array(
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
     * Get messages URL.
     *
     * @since 1.0.0
     * @return string Messages URL.
     */
    private function get_messages_url() {
        // Use real WordPress page instead of virtual page
        return $this->get_page_url( 'schedspot_messages_page', '/messages/' );
    }

    /**
     * Get dashboard URL.
     *
     * @since 1.0.0
     * @return string Dashboard URL.
     */
    private function get_dashboard_url() {
        // Use real WordPress page instead of virtual page
        return $this->get_page_url( 'schedspot_dashboard_page', '/my-account/' );
    }

    /**
     * Get profile URL.
     *
     * @since 1.0.0
     * @return string Profile URL.
     */
    private function get_profile_url() {
        // Use real WordPress page instead of virtual page
        return $this->get_page_url( 'schedspot_profile_page', '/profile/' );
    }

    /**
     * Get page URL by option name or fallback slug.
     *
     * @since 1.0.0
     * @param string $option_name Option name storing page ID.
     * @param string $fallback_slug Fallback slug if page not found.
     * @return string Page URL.
     */
    private function get_page_url( $option_name, $fallback_slug ) {
        $page_id = get_option( $option_name );

        if ( $page_id && get_post_status( $page_id ) === 'publish' ) {
            return get_permalink( $page_id );
        }

        // Fallback to slug-based URL
        return home_url( $fallback_slug );
    }

    /**
     * Get registered shortcodes.
     *
     * @since 1.0.0
     * @return array Registered shortcodes.
     */
    public function get_shortcodes() {
        return $this->shortcodes;
    }
}
