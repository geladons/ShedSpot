<?php
/**
 * Admin Settings Management Class
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
 * Handles all plugin settings and configuration in the admin area.
 *
 * @class SchedSpot_Admin_Settings
 * @version 1.0.0
 */
class SchedSpot_Admin_Settings {

    /**
     * Settings sections.
     *
     * @var array
     */
    private $settings_sections = array();

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize settings functionality.
     *
     * @since 1.0.0
     */
    public function init() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_ajax_schedspot_test_sms', array( $this, 'handle_test_sms' ) );
        add_action( 'wp_ajax_schedspot_geocode_address', array( $this, 'handle_geocode_test' ) );
        add_action( 'wp_ajax_schedspot_gcal_disconnect', array( $this, 'handle_gcal_disconnect' ) );
        add_action( 'wp_ajax_schedspot_gcal_sync_all', array( $this, 'handle_gcal_sync_all' ) );
        
        $this->define_settings_sections();
    }

    /**
     * Settings page callback.
     *
     * @since 1.0.0
     */
    public static function settings_page() {
        $instance = new self();
        
        // Handle form submission
        if ( isset( $_POST['submit'] ) ) {
            $instance->handle_settings_save();
        }
        
        // Get current tab
        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
        
        // Load template
        include SCHEDSPOT_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    /**
     * Define settings sections.
     *
     * @since 1.0.0
     */
    private function define_settings_sections() {
        $this->settings_sections = array(
            'general' => array(
                'title' => __( 'General Settings', 'schedspot' ),
                'fields' => array(
                    'business_name' => array(
                        'title' => __( 'Business Name', 'schedspot' ),
                        'type' => 'text',
                        'description' => __( 'Your business or company name.', 'schedspot' ),
                    ),
                    'business_email' => array(
                        'title' => __( 'Business Email', 'schedspot' ),
                        'type' => 'email',
                        'description' => __( 'Primary email for notifications and communications.', 'schedspot' ),
                    ),
                    'business_phone' => array(
                        'title' => __( 'Business Phone', 'schedspot' ),
                        'type' => 'text',
                        'description' => __( 'Primary phone number for your business.', 'schedspot' ),
                    ),
                    'timezone' => array(
                        'title' => __( 'Timezone', 'schedspot' ),
                        'type' => 'select',
                        'options' => $this->get_timezone_options(),
                        'description' => __( 'Default timezone for bookings and scheduling.', 'schedspot' ),
                    ),
                    'currency' => array(
                        'title' => __( 'Currency', 'schedspot' ),
                        'type' => 'select',
                        'options' => $this->get_currency_options(),
                        'description' => __( 'Currency for pricing and payments.', 'schedspot' ),
                    ),
                ),
            ),
            'booking' => array(
                'title' => __( 'Booking Settings', 'schedspot' ),
                'fields' => array(
                    'booking_advance_time' => array(
                        'title' => __( 'Advance Booking Time', 'schedspot' ),
                        'type' => 'number',
                        'description' => __( 'Minimum hours in advance for bookings.', 'schedspot' ),
                        'default' => 24,
                    ),
                    'booking_buffer_time' => array(
                        'title' => __( 'Buffer Time', 'schedspot' ),
                        'type' => 'number',
                        'description' => __( 'Minutes between bookings for preparation.', 'schedspot' ),
                        'default' => 15,
                    ),
                    'auto_confirm_bookings' => array(
                        'title' => __( 'Auto-Confirm Bookings', 'schedspot' ),
                        'type' => 'checkbox',
                        'description' => __( 'Automatically confirm new bookings without manual approval.', 'schedspot' ),
                    ),
                    'require_deposit' => array(
                        'title' => __( 'Require Deposit', 'schedspot' ),
                        'type' => 'checkbox',
                        'description' => __( 'Require deposit payment for booking confirmation.', 'schedspot' ),
                    ),
                    'deposit_percentage' => array(
                        'title' => __( 'Deposit Percentage', 'schedspot' ),
                        'type' => 'number',
                        'description' => __( 'Percentage of total price required as deposit.', 'schedspot' ),
                        'default' => 25,
                        'min' => 1,
                        'max' => 100,
                    ),
                    'worker_assignment_mode' => array(
                        'title' => __( 'Worker Assignment Mode', 'schedspot' ),
                        'type' => 'select',
                        'description' => __( 'How to handle "Any Worker" bookings.', 'schedspot' ),
                        'default' => 'auto',
                        'options' => array(
                            'auto' => __( 'Auto-assign best available worker', 'schedspot' ),
                            'manual' => __( 'Send to admin for manual assignment', 'schedspot' ),
                        ),
                    ),
                ),
            ),
            'notifications' => array(
                'title' => __( 'Notifications', 'schedspot' ),
                'fields' => array(
                    'email_notifications' => array(
                        'title' => __( 'Email Notifications', 'schedspot' ),
                        'type' => 'checkbox',
                        'description' => __( 'Enable email notifications for bookings and updates.', 'schedspot' ),
                        'default' => true,
                    ),
                    'sms_notifications' => array(
                        'title' => __( 'SMS Notifications', 'schedspot' ),
                        'type' => 'checkbox',
                        'description' => __( 'Enable SMS notifications (requires Twilio configuration).', 'schedspot' ),
                    ),
                    'admin_notifications' => array(
                        'title' => __( 'Admin Notifications', 'schedspot' ),
                        'type' => 'checkbox',
                        'description' => __( 'Send notifications to admin for new bookings.', 'schedspot' ),
                        'default' => true,
                    ),
                ),
            ),
            'integrations' => array(
                'title' => __( 'Integrations', 'schedspot' ),
                'fields' => array(
                    'google_maps_api_key' => array(
                        'title' => __( 'Google Maps API Key', 'schedspot' ),
                        'type' => 'text',
                        'description' => __( 'Required for geolocation and mapping features.', 'schedspot' ),
                    ),
                    'twilio_account_sid' => array(
                        'title' => __( 'Twilio Account SID', 'schedspot' ),
                        'type' => 'text',
                        'description' => __( 'Twilio Account SID for SMS notifications.', 'schedspot' ),
                    ),
                    'twilio_auth_token' => array(
                        'title' => __( 'Twilio Auth Token', 'schedspot' ),
                        'type' => 'password',
                        'description' => __( 'Twilio Auth Token for SMS notifications.', 'schedspot' ),
                    ),
                    'twilio_phone_number' => array(
                        'title' => __( 'Twilio Phone Number', 'schedspot' ),
                        'type' => 'text',
                        'description' => __( 'Your Twilio phone number for sending SMS.', 'schedspot' ),
                    ),
                ),
            ),
            'payments' => array(
                'title' => __( 'Payments', 'schedspot' ),
                'fields' => array(
                    'payment_method' => array(
                        'title' => __( 'Payment Method', 'schedspot' ),
                        'type' => 'select',
                        'options' => array(
                            'woocommerce' => __( 'WooCommerce', 'schedspot' ),
                            'stripe' => __( 'Stripe', 'schedspot' ),
                            'paypal' => __( 'PayPal', 'schedspot' ),
                        ),
                        'description' => __( 'Primary payment processing method.', 'schedspot' ),
                    ),
                    'commission_rate' => array(
                        'title' => __( 'Commission Rate (%)', 'schedspot' ),
                        'type' => 'number',
                        'description' => __( 'Platform commission percentage for worker payments.', 'schedspot' ),
                        'default' => 10,
                        'min' => 0,
                        'max' => 50,
                    ),
                    'payout_schedule' => array(
                        'title' => __( 'Payout Schedule', 'schedspot' ),
                        'type' => 'select',
                        'options' => array(
                            'immediate' => __( 'Immediate', 'schedspot' ),
                            'weekly' => __( 'Weekly', 'schedspot' ),
                            'monthly' => __( 'Monthly', 'schedspot' ),
                        ),
                        'description' => __( 'How often workers receive payments.', 'schedspot' ),
                    ),
                ),
            ),
        );
    }

    /**
     * Register settings.
     *
     * @since 1.0.0
     */
    public function register_settings() {
        foreach ( $this->settings_sections as $section_id => $section ) {
            foreach ( $section['fields'] as $field_id => $field ) {
                $option_name = 'schedspot_' . $field_id;
                register_setting( 'schedspot_settings', $option_name );
            }
        }
    }

    /**
     * Handle settings save.
     *
     * @since 1.0.0
     */
    private function handle_settings_save() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'schedspot_settings' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $updated = false;
        
        foreach ( $this->settings_sections as $section_id => $section ) {
            foreach ( $section['fields'] as $field_id => $field ) {
                $option_name = 'schedspot_' . $field_id;
                $value = isset( $_POST[ $option_name ] ) ? $_POST[ $option_name ] : '';
                
                // Sanitize based on field type
                switch ( $field['type'] ) {
                    case 'email':
                        $value = sanitize_email( $value );
                        break;
                    case 'number':
                        $value = floatval( $value );
                        break;
                    case 'checkbox':
                        $value = isset( $_POST[ $option_name ] ) ? '1' : '0';
                        break;
                    case 'password':
                        $value = sanitize_text_field( $value );
                        break;
                    default:
                        $value = sanitize_text_field( $value );
                        break;
                }
                
                if ( update_option( $option_name, $value ) ) {
                    $updated = true;
                }
            }
        }
        
        if ( $updated ) {
            add_settings_error( 'schedspot_settings', 'settings_updated', __( 'Settings saved successfully.', 'schedspot' ), 'updated' );
        }
    }

    /**
     * Get timezone options.
     *
     * @since 1.0.0
     * @return array Timezone options.
     */
    private function get_timezone_options() {
        $timezones = array();
        $timezone_identifiers = DateTimeZone::listIdentifiers();
        
        foreach ( $timezone_identifiers as $timezone ) {
            $timezones[ $timezone ] = $timezone;
        }
        
        return $timezones;
    }

    /**
     * Get currency options.
     *
     * @since 1.0.0
     * @return array Currency options.
     */
    private function get_currency_options() {
        return array(
            'USD' => __( 'US Dollar ($)', 'schedspot' ),
            'EUR' => __( 'Euro (€)', 'schedspot' ),
            'GBP' => __( 'British Pound (£)', 'schedspot' ),
            'CAD' => __( 'Canadian Dollar (C$)', 'schedspot' ),
            'AUD' => __( 'Australian Dollar (A$)', 'schedspot' ),
            'JPY' => __( 'Japanese Yen (¥)', 'schedspot' ),
        );
    }

    /**
     * Get settings sections.
     *
     * @since 1.0.0
     * @return array Settings sections.
     */
    public function get_settings_sections() {
        return $this->settings_sections;
    }

    /**
     * Get setting value.
     *
     * @since 1.0.0
     * @param string $key     Setting key.
     * @param mixed  $default Default value.
     * @return mixed Setting value.
     */
    public static function get_setting( $key, $default = '' ) {
        return get_option( 'schedspot_' . $key, $default );
    }

    /**
     * Handle SMS test.
     *
     * @since 1.0.0
     */
    public function handle_test_sms() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_test_sms' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        $phone = sanitize_text_field( $_POST['phone'] );
        
        // Test SMS sending
        $sms_service = new SchedSpot_SMS();
        $result = $sms_service->send_test_message( $phone );
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Test SMS sent successfully!', 'schedspot' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to send test SMS. Please check your Twilio settings.', 'schedspot' ) ) );
        }
    }

    /**
     * Handle geocode test.
     *
     * @since 1.0.0
     */
    public function handle_geocode_test() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_geolocation_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        $address = sanitize_text_field( $_POST['address'] );
        
        // Test geocoding
        $geolocation = new SchedSpot_Geolocation();
        $result = $geolocation->geocode_address( $address );
        
        if ( $result && isset( $result['lat'] ) && isset( $result['lng'] ) ) {
            wp_send_json_success( array(
                'lat' => $result['lat'],
                'lng' => $result['lng'],
                'formatted_address' => $result['formatted_address'] ?? $address,
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to geocode address. Please check your Google Maps API key.', 'schedspot' ) ) );
        }
    }

    /**
     * Handle Google Calendar disconnect.
     *
     * @since 1.0.0
     */
    public function handle_gcal_disconnect() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_gcal_disconnect' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        // Disconnect Google Calendar
        delete_option( 'schedspot_gcal_access_token' );
        delete_option( 'schedspot_gcal_refresh_token' );
        delete_option( 'schedspot_gcal_calendar_id' );
        
        wp_send_json_success( array( 'message' => __( 'Google Calendar disconnected successfully.', 'schedspot' ) ) );
    }

    /**
     * Handle Google Calendar sync all.
     *
     * @since 1.0.0
     */
    public function handle_gcal_sync_all() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_gcal_sync_all' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        // Sync all bookings to Google Calendar
        $gcal_service = new SchedSpot_Google_Calendar();
        $result = $gcal_service->sync_all_bookings();
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => sprintf( __( '%d bookings synced to Google Calendar.', 'schedspot' ), $result ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to sync bookings. Please check your Google Calendar connection.', 'schedspot' ) ) );
        }
    }
}
