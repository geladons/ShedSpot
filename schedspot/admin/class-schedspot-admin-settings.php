<?php
/**
 * SchedSpot Admin Settings Management
 *
 * Handles all settings-related admin functionality
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Admin_Settings Class.
 *
 * @class SchedSpot_Admin_Settings
 * @version 1.0.0
 */
class SchedSpot_Admin_Settings {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize settings admin functionality.
     *
     * @since 1.0.0
     */
    public function init() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Register all plugin settings.
     *
     * @since 1.0.0
     */
    public function register_settings() {
        // General settings
        register_setting( 'schedspot_general_settings', 'schedspot_default_timezone' );
        register_setting( 'schedspot_general_settings', 'schedspot_date_format' );
        register_setting( 'schedspot_general_settings', 'schedspot_time_format' );
        register_setting( 'schedspot_general_settings', 'schedspot_currency' );

        // Booking settings
        register_setting( 'schedspot_booking_settings', 'schedspot_default_slot_length' );
        register_setting( 'schedspot_booking_settings', 'schedspot_minimum_notice' );
        register_setting( 'schedspot_booking_settings', 'schedspot_auto_approve_bookings' );
        register_setting( 'schedspot_booking_settings', 'schedspot_allow_guest_booking' );
        register_setting( 'schedspot_booking_settings', 'schedspot_require_deposit' );
        register_setting( 'schedspot_booking_settings', 'schedspot_deposit_percentage' );

        // Payment settings
        register_setting( 'schedspot_payment_settings', 'schedspot_payment_method' );
        register_setting( 'schedspot_payment_settings', 'schedspot_stripe_publishable_key' );
        register_setting( 'schedspot_payment_settings', 'schedspot_stripe_secret_key' );
        register_setting( 'schedspot_payment_settings', 'schedspot_paypal_client_id' );
        register_setting( 'schedspot_payment_settings', 'schedspot_paypal_client_secret' );
        register_setting( 'schedspot_payment_settings', 'schedspot_commission_rate' );

        // Calendar settings
        register_setting( 'schedspot_calendar_settings', 'schedspot_google_calendar_enabled' );
        register_setting( 'schedspot_calendar_settings', 'schedspot_google_client_id' );
        register_setting( 'schedspot_calendar_settings', 'schedspot_google_client_secret' );

        // SMS settings
        register_setting( 'schedspot_sms_settings', 'schedspot_sms_enabled' );
        register_setting( 'schedspot_sms_settings', 'schedspot_twilio_account_sid' );
        register_setting( 'schedspot_sms_settings', 'schedspot_twilio_auth_token' );
        register_setting( 'schedspot_sms_settings', 'schedspot_twilio_phone_number' );

        // Messaging settings
        register_setting( 'schedspot_messaging_settings', 'schedspot_messaging_enabled' );
        register_setting( 'schedspot_messaging_settings', 'schedspot_email_notifications' );
        register_setting( 'schedspot_messaging_settings', 'schedspot_sms_notifications' );

        // Email settings
        register_setting( 'schedspot_email_settings', 'schedspot_email_from_name' );
        register_setting( 'schedspot_email_settings', 'schedspot_email_from_address' );
        register_setting( 'schedspot_email_settings', 'schedspot_booking_confirmation_template' );
        register_setting( 'schedspot_email_settings', 'schedspot_booking_reminder_template' );

        // Geolocation settings
        register_setting( 'schedspot_geolocation_settings', 'schedspot_enable_geofencing' );
        register_setting( 'schedspot_geolocation_settings', 'schedspot_google_maps_api_key' );
        register_setting( 'schedspot_geolocation_settings', 'schedspot_default_radius' );
        register_setting( 'schedspot_geolocation_settings', 'schedspot_distance_unit' );

        // Advanced settings
        register_setting( 'schedspot_advanced_settings', 'schedspot_enable_debug_mode' );
        register_setting( 'schedspot_advanced_settings', 'schedspot_cache_duration' );
        register_setting( 'schedspot_advanced_settings', 'schedspot_api_rate_limit' );
        register_setting( 'schedspot_advanced_settings', 'schedspot_cleanup_old_data' );
        register_setting( 'schedspot_advanced_settings', 'schedspot_data_retention_days' );

        // Add settings sections
        $this->add_settings_sections();
    }

    /**
     * Add settings sections and fields.
     *
     * @since 1.0.0
     */
    private function add_settings_sections() {
        // General settings section
        add_settings_section(
            'schedspot_general_section',
            __( 'General Settings', 'schedspot' ),
            array( $this, 'general_section_callback' ),
            'schedspot_general_settings'
        );

        add_settings_field(
            'schedspot_default_timezone',
            __( 'Default Timezone', 'schedspot' ),
            array( $this, 'timezone_field_callback' ),
            'schedspot_general_settings',
            'schedspot_general_section'
        );

        add_settings_field(
            'schedspot_date_format',
            __( 'Date Format', 'schedspot' ),
            array( $this, 'date_format_field_callback' ),
            'schedspot_general_settings',
            'schedspot_general_section'
        );

        add_settings_field(
            'schedspot_time_format',
            __( 'Time Format', 'schedspot' ),
            array( $this, 'time_format_field_callback' ),
            'schedspot_general_settings',
            'schedspot_general_section'
        );

        add_settings_field(
            'schedspot_currency',
            __( 'Currency', 'schedspot' ),
            array( $this, 'currency_field_callback' ),
            'schedspot_general_settings',
            'schedspot_general_section'
        );

        // Booking settings section
        add_settings_section(
            'schedspot_booking_section',
            __( 'Booking Settings', 'schedspot' ),
            array( $this, 'booking_section_callback' ),
            'schedspot_booking_settings'
        );

        add_settings_field(
            'schedspot_default_slot_length',
            __( 'Default Slot Length (minutes)', 'schedspot' ),
            array( $this, 'slot_length_field_callback' ),
            'schedspot_booking_settings',
            'schedspot_booking_section'
        );

        add_settings_field(
            'schedspot_minimum_notice',
            __( 'Minimum Notice (hours)', 'schedspot' ),
            array( $this, 'minimum_notice_field_callback' ),
            'schedspot_booking_settings',
            'schedspot_booking_section'
        );

        add_settings_field(
            'schedspot_auto_approve_bookings',
            __( 'Auto-approve Bookings', 'schedspot' ),
            array( $this, 'auto_approve_field_callback' ),
            'schedspot_booking_settings',
            'schedspot_booking_section'
        );

        add_settings_field(
            'schedspot_allow_guest_booking',
            __( 'Allow Guest Booking', 'schedspot' ),
            array( $this, 'guest_booking_field_callback' ),
            'schedspot_booking_settings',
            'schedspot_booking_section'
        );

        // Payment settings section
        add_settings_section(
            'schedspot_payment_section',
            __( 'Payment Settings', 'schedspot' ),
            array( $this, 'payment_section_callback' ),
            'schedspot_payment_settings'
        );

        add_settings_field(
            'schedspot_payment_method',
            __( 'Payment Method', 'schedspot' ),
            array( $this, 'payment_method_field_callback' ),
            'schedspot_payment_settings',
            'schedspot_payment_section'
        );

        add_settings_field(
            'schedspot_commission_rate',
            __( 'Commission Rate (%)', 'schedspot' ),
            array( $this, 'commission_rate_field_callback' ),
            'schedspot_payment_settings',
            'schedspot_payment_section'
        );

        // Advanced settings section
        add_settings_section(
            'schedspot_advanced_section',
            __( 'Advanced Settings', 'schedspot' ),
            array( $this, 'advanced_section_callback' ),
            'schedspot_advanced_settings'
        );

        add_settings_field(
            'schedspot_enable_debug_mode',
            __( 'Enable Debug Mode', 'schedspot' ),
            array( $this, 'debug_mode_field_callback' ),
            'schedspot_advanced_settings',
            'schedspot_advanced_section'
        );

        add_settings_field(
            'schedspot_cache_duration',
            __( 'Cache Duration (minutes)', 'schedspot' ),
            array( $this, 'cache_duration_field_callback' ),
            'schedspot_advanced_settings',
            'schedspot_advanced_section'
        );
    }

    /**
     * Settings page callback.
     *
     * @since 1.0.0
     */
    public function settings_page() {
        $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
        ?>
        <div class="wrap">
            <h1><?php _e( 'SchedSpot Settings', 'schedspot' ); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=schedspot-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e( 'General', 'schedspot' ); ?></a>
                <a href="?page=schedspot-settings&tab=booking" class="nav-tab <?php echo $active_tab == 'booking' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Booking', 'schedspot' ); ?></a>
                <a href="?page=schedspot-settings&tab=payment" class="nav-tab <?php echo $active_tab == 'payment' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Payment', 'schedspot' ); ?></a>
                <a href="?page=schedspot-settings&tab=calendar" class="nav-tab <?php echo $active_tab == 'calendar' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Calendar', 'schedspot' ); ?></a>
                <a href="?page=schedspot-settings&tab=sms" class="nav-tab <?php echo $active_tab == 'sms' ? 'nav-tab-active' : ''; ?>"><?php _e( 'SMS', 'schedspot' ); ?></a>
                <a href="?page=schedspot-settings&tab=messaging" class="nav-tab <?php echo $active_tab == 'messaging' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Messaging', 'schedspot' ); ?></a>
                <a href="?page=schedspot-settings&tab=email" class="nav-tab <?php echo $active_tab == 'email' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Email', 'schedspot' ); ?></a>
                <a href="?page=schedspot-settings&tab=geolocation" class="nav-tab <?php echo $active_tab == 'geolocation' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Geolocation', 'schedspot' ); ?></a>
                <a href="?page=schedspot-settings&tab=advanced" class="nav-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Advanced', 'schedspot' ); ?></a>
            </h2>
            
            <?php if ( $active_tab == 'calendar' ) : ?>
                <?php $this->render_calendar_settings(); ?>
            <?php elseif ( $active_tab == 'sms' ) : ?>
                <?php $this->render_sms_settings(); ?>
            <?php elseif ( $active_tab == 'geolocation' ) : ?>
                <?php $this->render_geolocation_settings(); ?>
            <?php else : ?>
                <form method="post" action="options.php">
                    <?php
                    if ( $active_tab == 'general' ) {
                        settings_fields( 'schedspot_general_settings' );
                        do_settings_sections( 'schedspot_general_settings' );
                    } elseif ( $active_tab == 'booking' ) {
                        settings_fields( 'schedspot_booking_settings' );
                        do_settings_sections( 'schedspot_booking_settings' );
                    } elseif ( $active_tab == 'payment' ) {
                        settings_fields( 'schedspot_payment_settings' );
                        do_settings_sections( 'schedspot_payment_settings' );
                    } elseif ( $active_tab == 'messaging' ) {
                        settings_fields( 'schedspot_messaging_settings' );
                        do_settings_sections( 'schedspot_messaging_settings' );
                    } elseif ( $active_tab == 'email' ) {
                        settings_fields( 'schedspot_email_settings' );
                        do_settings_sections( 'schedspot_email_settings' );
                    } elseif ( $active_tab == 'advanced' ) {
                        settings_fields( 'schedspot_advanced_settings' );
                        do_settings_sections( 'schedspot_advanced_settings' );
                    }
                    submit_button();
                    ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * General section callback.
     *
     * @since 1.0.0
     */
    public function general_section_callback() {
        echo '<p>' . __( 'Configure general plugin settings.', 'schedspot' ) . '</p>';
    }

    /**
     * Booking section callback.
     *
     * @since 1.0.0
     */
    public function booking_section_callback() {
        echo '<p>' . __( 'Configure booking-related settings.', 'schedspot' ) . '</p>';
    }

    /**
     * Payment section callback.
     *
     * @since 1.0.0
     */
    public function payment_section_callback() {
        echo '<p>' . __( 'Configure payment processing settings.', 'schedspot' ) . '</p>';
    }

    /**
     * Advanced section callback.
     *
     * @since 1.0.0
     */
    public function advanced_section_callback() {
        echo '<p>' . __( 'Advanced configuration options for developers.', 'schedspot' ) . '</p>';
    }

    /**
     * Timezone field callback.
     *
     * @since 1.0.0
     */
    public function timezone_field_callback() {
        $value = get_option( 'schedspot_default_timezone', 'America/New_York' );
        $timezones = timezone_identifiers_list();
        echo '<select name="schedspot_default_timezone">';
        foreach ( $timezones as $timezone ) {
            echo '<option value="' . esc_attr( $timezone ) . '" ' . selected( $value, $timezone, false ) . '>' . esc_html( $timezone ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __( 'Default timezone for bookings and scheduling.', 'schedspot' ) . '</p>';
    }

    /**
     * Date format field callback.
     *
     * @since 1.0.0
     */
    public function date_format_field_callback() {
        $value = get_option( 'schedspot_date_format', 'Y-m-d' );
        $formats = array(
            'Y-m-d' => date( 'Y-m-d' ),
            'm/d/Y' => date( 'm/d/Y' ),
            'd/m/Y' => date( 'd/m/Y' ),
            'F j, Y' => date( 'F j, Y' ),
        );
        echo '<select name="schedspot_date_format">';
        foreach ( $formats as $format => $example ) {
            echo '<option value="' . esc_attr( $format ) . '" ' . selected( $value, $format, false ) . '>' . esc_html( $example ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __( 'Date format for displaying dates.', 'schedspot' ) . '</p>';
    }

    /**
     * Time format field callback.
     *
     * @since 1.0.0
     */
    public function time_format_field_callback() {
        $value = get_option( 'schedspot_time_format', 'H:i' );
        $formats = array(
            'H:i' => date( 'H:i' ),
            'g:i A' => date( 'g:i A' ),
            'g:i a' => date( 'g:i a' ),
        );
        echo '<select name="schedspot_time_format">';
        foreach ( $formats as $format => $example ) {
            echo '<option value="' . esc_attr( $format ) . '" ' . selected( $value, $format, false ) . '>' . esc_html( $example ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __( 'Time format for displaying times.', 'schedspot' ) . '</p>';
    }

    /**
     * Currency field callback.
     *
     * @since 1.0.0
     */
    public function currency_field_callback() {
        $value = get_option( 'schedspot_currency', 'USD' );
        $currencies = array(
            'USD' => 'US Dollar ($)',
            'EUR' => 'Euro (€)',
            'GBP' => 'British Pound (£)',
            'CAD' => 'Canadian Dollar (C$)',
            'AUD' => 'Australian Dollar (A$)',
        );
        echo '<select name="schedspot_currency">';
        foreach ( $currencies as $code => $name ) {
            echo '<option value="' . esc_attr( $code ) . '" ' . selected( $value, $code, false ) . '>' . esc_html( $name ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __( 'Currency for pricing and payments.', 'schedspot' ) . '</p>';
    }

    /**
     * Slot length field callback.
     *
     * @since 1.0.0
     */
    public function slot_length_field_callback() {
        $value = get_option( 'schedspot_default_slot_length', 60 );
        echo '<input type="number" name="schedspot_default_slot_length" value="' . esc_attr( $value ) . '" min="15" step="15" />';
        echo '<p class="description">' . __( 'Default length for booking slots in minutes.', 'schedspot' ) . '</p>';
    }

    /**
     * Minimum notice field callback.
     *
     * @since 1.0.0
     */
    public function minimum_notice_field_callback() {
        $value = get_option( 'schedspot_minimum_notice', 24 );
        echo '<input type="number" name="schedspot_minimum_notice" value="' . esc_attr( $value ) . '" min="1" />';
        echo '<p class="description">' . __( 'Minimum notice required for bookings in hours.', 'schedspot' ) . '</p>';
    }

    /**
     * Auto approve field callback.
     *
     * @since 1.0.0
     */
    public function auto_approve_field_callback() {
        $value = get_option( 'schedspot_auto_approve_bookings', false );
        echo '<input type="checkbox" name="schedspot_auto_approve_bookings" value="1" ' . checked( $value, true, false ) . ' />';
        echo '<p class="description">' . __( 'Automatically approve new bookings without manual review.', 'schedspot' ) . '</p>';
    }

    /**
     * Guest booking field callback.
     *
     * @since 1.0.0
     */
    public function guest_booking_field_callback() {
        $value = get_option( 'schedspot_allow_guest_booking', true );
        echo '<input type="checkbox" name="schedspot_allow_guest_booking" value="1" ' . checked( $value, true, false ) . ' />';
        echo '<p class="description">' . __( 'Allow users to book services without creating an account.', 'schedspot' ) . '</p>';
    }

    /**
     * Payment method field callback.
     *
     * @since 1.0.0
     */
    public function payment_method_field_callback() {
        $value = get_option( 'schedspot_payment_method', 'stripe' );
        $methods = array(
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'woocommerce' => 'WooCommerce',
        );
        echo '<select name="schedspot_payment_method">';
        foreach ( $methods as $method => $name ) {
            echo '<option value="' . esc_attr( $method ) . '" ' . selected( $value, $method, false ) . '>' . esc_html( $name ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __( 'Payment processing method.', 'schedspot' ) . '</p>';
    }

    /**
     * Commission rate field callback.
     *
     * @since 1.0.0
     */
    public function commission_rate_field_callback() {
        $value = get_option( 'schedspot_commission_rate', 10 );
        echo '<input type="number" name="schedspot_commission_rate" value="' . esc_attr( $value ) . '" min="0" max="100" step="0.1" />';
        echo '<p class="description">' . __( 'Commission rate charged on bookings (percentage).', 'schedspot' ) . '</p>';
    }

    /**
     * Debug mode field callback.
     *
     * @since 1.0.0
     */
    public function debug_mode_field_callback() {
        $value = get_option( 'schedspot_enable_debug_mode', false );
        echo '<input type="checkbox" name="schedspot_enable_debug_mode" value="1" ' . checked( $value, true, false ) . ' />';
        echo '<p class="description">' . __( 'Enable debug mode for troubleshooting.', 'schedspot' ) . '</p>';
    }

    /**
     * Cache duration field callback.
     *
     * @since 1.0.0
     */
    public function cache_duration_field_callback() {
        $value = get_option( 'schedspot_cache_duration', 60 );
        echo '<input type="number" name="schedspot_cache_duration" value="' . esc_attr( $value ) . '" min="5" max="1440" />';
        echo '<p class="description">' . __( 'Cache duration in minutes for API responses and data.', 'schedspot' ) . '</p>';
    }

    /**
     * Render calendar settings.
     *
     * @since 1.0.0
     */
    private function render_calendar_settings() {
        // This method would contain calendar-specific settings
        echo '<h3>' . __( 'Google Calendar Integration', 'schedspot' ) . '</h3>';
        echo '<p>' . __( 'Calendar integration settings will be implemented here.', 'schedspot' ) . '</p>';
    }

    /**
     * Render SMS settings.
     *
     * @since 1.0.0
     */
    private function render_sms_settings() {
        // This method would contain SMS-specific settings
        echo '<h3>' . __( 'SMS Notifications', 'schedspot' ) . '</h3>';
        echo '<p>' . __( 'SMS notification settings will be implemented here.', 'schedspot' ) . '</p>';
    }

    /**
     * Render geolocation settings.
     *
     * @since 1.0.0
     */
    private function render_geolocation_settings() {
        // This method would contain geolocation-specific settings
        echo '<h3>' . __( 'Geolocation & Maps', 'schedspot' ) . '</h3>';
        echo '<p>' . __( 'Geolocation and mapping settings will be implemented here.', 'schedspot' ) . '</p>';
    }
}
