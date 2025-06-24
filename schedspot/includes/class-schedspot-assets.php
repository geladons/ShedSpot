<?php
/**
 * SchedSpot Assets Manager
 *
 * Handles proper enqueuing of CSS and JavaScript files
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Assets Class.
 *
 * @class SchedSpot_Assets
 * @version 1.0.0
 */
class SchedSpot_Assets {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize asset management.
     *
     * @since 1.0.0
     */
    public function init() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Enqueue frontend assets.
     *
     * @since 1.0.0
     */
    public function enqueue_frontend_assets() {
        global $post;

        // Only enqueue on pages that need SchedSpot assets
        if ( ! $this->should_enqueue_frontend_assets() ) {
            return;
        }

        // Enqueue core frontend styles
        wp_enqueue_style(
            'schedspot-frontend',
            SCHEDSPOT_PLUGIN_URL . 'assets/css/frontend-enhanced.css',
            array(),
            SCHEDSPOT_VERSION
        );

        // Enqueue core frontend script
        wp_enqueue_script(
            'schedspot-frontend',
            SCHEDSPOT_PLUGIN_URL . 'assets/js/frontend.js',
            array( 'jquery' ),
            SCHEDSPOT_VERSION,
            true
        );

        // Conditional asset loading based on shortcodes
        $this->enqueue_conditional_assets();

        // Localize frontend script
        $this->localize_frontend_script();
    }

    /**
     * Enqueue conditional assets based on page content.
     *
     * @since 1.0.0
     */
    private function enqueue_conditional_assets() {
        global $post;

        if ( ! is_a( $post, 'WP_Post' ) ) {
            return;
        }

        $content = $post->post_content;

        // Booking form assets
        if ( has_shortcode( $content, 'schedspot_booking_form' ) || $this->is_virtual_booking_page() ) {
            $this->enqueue_booking_form_assets();
        }

        // Dashboard assets
        if ( has_shortcode( $content, 'schedspot_dashboard' ) || $this->is_virtual_dashboard_page() ) {
            $this->enqueue_dashboard_assets();
        }

        // Messaging assets
        if ( has_shortcode( $content, 'schedspot_messages' ) || $this->is_virtual_messages_page() ) {
            $this->enqueue_messaging_assets();
        }

        // Profile assets
        if ( has_shortcode( $content, 'schedspot_profile' ) || $this->is_virtual_profile_page() ) {
            $this->enqueue_profile_assets();
        }

        // Workers grid assets (used in multiple places)
        if ( $this->needs_workers_grid() ) {
            $this->enqueue_workers_grid_assets();
        }
    }

    /**
     * Enqueue booking form specific assets.
     *
     * @since 1.0.0
     */
    private function enqueue_booking_form_assets() {
        // Booking form CSS
        wp_enqueue_style(
            'schedspot-booking-form',
            SCHEDSPOT_PLUGIN_URL . 'assets/css/booking-form.css',
            array( 'schedspot-frontend' ),
            SCHEDSPOT_VERSION
        );

        // Booking form JavaScript
        wp_enqueue_script(
            'schedspot-booking-form',
            SCHEDSPOT_PLUGIN_URL . 'assets/js/booking-form.js',
            array( 'jquery', 'jquery-ui-datepicker', 'schedspot-frontend' ),
            SCHEDSPOT_VERSION,
            true
        );

        // jQuery UI Datepicker CSS
        wp_enqueue_style( 'jquery-ui-datepicker' );

        // Geolocation assets if enabled
        if ( get_option( 'schedspot_enable_geofencing', false ) ) {
            $this->enqueue_geolocation_assets();
        }
    }

    /**
     * Enqueue dashboard specific assets.
     *
     * @since 1.0.0
     */
    private function enqueue_dashboard_assets() {
        // Dashboard CSS
        wp_enqueue_style(
            'schedspot-dashboard',
            SCHEDSPOT_PLUGIN_URL . 'assets/css/dashboard.css',
            array( 'schedspot-frontend' ),
            SCHEDSPOT_VERSION
        );

        // Dashboard JavaScript
        wp_enqueue_script(
            'schedspot-dashboard',
            SCHEDSPOT_PLUGIN_URL . 'assets/js/dashboard.js',
            array( 'jquery', 'schedspot-frontend' ),
            SCHEDSPOT_VERSION,
            true
        );
    }

    /**
     * Enqueue messaging specific assets.
     *
     * @since 1.0.0
     */
    private function enqueue_messaging_assets() {
        // Messaging CSS
        wp_enqueue_style(
            'schedspot-messaging',
            SCHEDSPOT_PLUGIN_URL . 'assets/css/messaging.css',
            array( 'schedspot-frontend' ),
            SCHEDSPOT_VERSION
        );

        // Messaging JavaScript
        wp_enqueue_script(
            'schedspot-messaging',
            SCHEDSPOT_PLUGIN_URL . 'assets/js/messaging.js',
            array( 'jquery', 'schedspot-frontend' ),
            SCHEDSPOT_VERSION,
            true
        );
    }

    /**
     * Enqueue profile specific assets.
     *
     * @since 1.0.0
     */
    private function enqueue_profile_assets() {
        // Profile CSS
        wp_enqueue_style(
            'schedspot-profile',
            SCHEDSPOT_PLUGIN_URL . 'assets/css/profile.css',
            array( 'schedspot-frontend' ),
            SCHEDSPOT_VERSION
        );

        // Profile JavaScript
        wp_enqueue_script(
            'schedspot-profile',
            SCHEDSPOT_PLUGIN_URL . 'assets/js/profile.js',
            array( 'jquery', 'schedspot-frontend' ),
            SCHEDSPOT_VERSION,
            true
        );
    }

    /**
     * Enqueue workers grid specific assets.
     *
     * @since 1.0.0
     */
    private function enqueue_workers_grid_assets() {
        // Workers grid CSS
        wp_enqueue_style(
            'schedspot-workers-grid',
            SCHEDSPOT_PLUGIN_URL . 'assets/css/workers-grid.css',
            array( 'schedspot-frontend' ),
            SCHEDSPOT_VERSION
        );
    }

    /**
     * Enqueue geolocation assets.
     *
     * @since 1.0.0
     */
    private function enqueue_geolocation_assets() {
        $google_maps_api_key = get_option( 'schedspot_google_maps_api_key' );
        
        if ( $google_maps_api_key ) {
            // Google Maps API
            wp_enqueue_script(
                'google-maps',
                'https://maps.googleapis.com/maps/api/js?key=' . $google_maps_api_key . '&libraries=places,drawing',
                array(),
                null,
                true
            );

            // Geolocation JavaScript
            wp_enqueue_script(
                'schedspot-geolocation',
                SCHEDSPOT_PLUGIN_URL . 'assets/js/geolocation.js',
                array( 'jquery', 'google-maps', 'schedspot-frontend' ),
                SCHEDSPOT_VERSION,
                true
            );
        }
    }

    /**
     * Enqueue admin assets.
     *
     * @since 1.0.0
     */
    public function enqueue_admin_assets( $hook ) {
        // Only enqueue on SchedSpot admin pages
        if ( ! $this->is_schedspot_admin_page( $hook ) ) {
            return;
        }

        // Admin CSS
        wp_enqueue_style(
            'schedspot-admin',
            SCHEDSPOT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SCHEDSPOT_VERSION
        );

        // Admin JavaScript
        wp_enqueue_script(
            'schedspot-admin',
            SCHEDSPOT_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            SCHEDSPOT_VERSION,
            true
        );

        // Localize admin script
        wp_localize_script( 'schedspot-admin', 'schedspot_admin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'rest_url' => rest_url( 'schedspot/v1/' ),
            'nonce' => wp_create_nonce( 'schedspot_admin_nonce' ),
            'strings' => array(
                'confirm_delete' => __( 'Are you sure you want to delete this item?', 'schedspot' ),
                'saving' => __( 'Saving...', 'schedspot' ),
                'saved' => __( 'Saved!', 'schedspot' ),
                'error' => __( 'Error occurred. Please try again.', 'schedspot' ),
            ),
        ) );
    }

    /**
     * Localize frontend script with data.
     *
     * @since 1.0.0
     */
    private function localize_frontend_script() {
        wp_localize_script( 'schedspot-frontend', 'schedspot_frontend', array(
            'rest_url' => rest_url( 'schedspot/v1/' ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'default_avatar' => get_avatar_url( 0 ),
            'geolocation_enabled' => get_option( 'schedspot_enable_geofencing', false ),
            'strings' => array(
                'loading' => __( 'Loading...', 'schedspot' ),
                'error' => __( 'Error occurred. Please try again.', 'schedspot' ),
                'success' => __( 'Success!', 'schedspot' ),
                'confirm' => __( 'Are you sure?', 'schedspot' ),
                'processing' => __( 'Processing...', 'schedspot' ),
                'field_required' => __( 'This field is required.', 'schedspot' ),
                'invalid_email' => __( 'Please enter a valid email address.', 'schedspot' ),
                'selected_worker' => __( 'Selected worker', 'schedspot' ),
                'confirm_data_export' => __( 'Request a copy of your personal data? You will receive an email with download instructions.', 'schedspot' ),
                'confirm_account_deletion' => __( 'This will permanently delete your account and all associated data. This action cannot be undone. Are you sure?', 'schedspot' ),
            ),
        ) );
    }

    /**
     * Check if frontend assets should be enqueued.
     *
     * @since 1.0.0
     * @return bool
     */
    private function should_enqueue_frontend_assets() {
        global $post;

        // Always enqueue on SchedSpot virtual pages
        if ( isset( $_GET['schedspot_action'] ) ) {
            return true;
        }

        // Check if current page has SchedSpot shortcodes
        if ( is_a( $post, 'WP_Post' ) ) {
            $shortcodes = array(
                'schedspot_booking_form',
                'schedspot_service_list',
                'schedspot_dashboard',
                'schedspot_messages',
                'schedspot_profile',
            );

            foreach ( $shortcodes as $shortcode ) {
                if ( has_shortcode( $post->post_content, $shortcode ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if current page is a SchedSpot admin page.
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook.
     * @return bool
     */
    private function is_schedspot_admin_page( $hook ) {
        $schedspot_pages = array(
            'toplevel_page_schedspot',
            'schedspot_page_schedspot-bookings',
            'schedspot_page_schedspot-services',
            'schedspot_page_schedspot-workers',
            'schedspot_page_schedspot-settings',
            'schedspot_page_schedspot-analytics',
        );

        return in_array( $hook, $schedspot_pages );
    }

    /**
     * Check if current page is a virtual booking page.
     *
     * @since 1.0.0
     * @return bool
     */
    private function is_virtual_booking_page() {
        return isset( $_GET['schedspot_action'] ) && $_GET['schedspot_action'] === 'booking_form';
    }

    /**
     * Check if current page is a virtual dashboard page.
     *
     * @since 1.0.0
     * @return bool
     */
    private function is_virtual_dashboard_page() {
        return isset( $_GET['schedspot_action'] ) && $_GET['schedspot_action'] === 'dashboard';
    }

    /**
     * Check if current page is a virtual messages page.
     *
     * @since 1.0.0
     * @return bool
     */
    private function is_virtual_messages_page() {
        return isset( $_GET['schedspot_action'] ) && $_GET['schedspot_action'] === 'messages';
    }

    /**
     * Check if current page is a virtual profile page.
     *
     * @since 1.0.0
     * @return bool
     */
    private function is_virtual_profile_page() {
        return isset( $_GET['schedspot_action'] ) && $_GET['schedspot_action'] === 'profile';
    }

    /**
     * Check if current page needs workers grid assets.
     *
     * @since 1.0.0
     * @return bool
     */
    private function needs_workers_grid() {
        global $post;

        if ( ! is_a( $post, 'WP_Post' ) ) {
            return false;
        }

        // Check for shortcodes that use workers grid
        return has_shortcode( $post->post_content, 'schedspot_booking_form' ) ||
               has_shortcode( $post->post_content, 'schedspot_service_list' ) ||
               $this->is_virtual_booking_page();
    }
}
