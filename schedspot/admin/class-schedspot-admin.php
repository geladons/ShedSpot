<?php
/**
 * Admin Class
 *
 * @package SchedSpot
 * @version 0.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Admin Class.
 *
 * @class SchedSpot_Admin
 * @version 0.1.0
 */
class SchedSpot_Admin {

    /**
     * Constructor.
     *
     * @since 0.1.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize admin functionality.
     *
     * @since 0.1.0
     */
    public function init() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_filter( 'plugin_action_links_' . SCHEDSPOT_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
        add_action( 'wp_ajax_schedspot_switch_role', array( $this, 'handle_role_switch' ) );
        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_role_switcher' ), 100 );
    }

    /**
     * Add admin menu items.
     *
     * @since 0.1.0
     */
    public function admin_menu() {
        // Main menu
        add_menu_page(
            __( 'SchedSpot', 'schedspot' ),
            __( 'SchedSpot', 'schedspot' ),
            'manage_options',
            'schedspot',
            array( $this, 'dashboard_page' ),
            'dashicons-calendar-alt',
            30
        );

        // Bookings submenu
        add_submenu_page(
            'schedspot',
            __( 'Bookings', 'schedspot' ),
            __( 'Bookings', 'schedspot' ),
            'manage_options',
            'schedspot-bookings',
            array( $this, 'bookings_page' )
        );

        // Services submenu
        add_submenu_page(
            'schedspot',
            __( 'Services', 'schedspot' ),
            __( 'Services', 'schedspot' ),
            'manage_options',
            'schedspot-services',
            array( $this, 'services_page' )
        );

        // Workers submenu
        add_submenu_page(
            'schedspot',
            __( 'Workers', 'schedspot' ),
            __( 'Workers', 'schedspot' ),
            'manage_options',
            'schedspot-workers',
            array( $this, 'workers_page' )
        );

        // Settings submenu
        add_submenu_page(
            'schedspot',
            __( 'Settings', 'schedspot' ),
            __( 'Settings', 'schedspot' ),
            'manage_options',
            'schedspot-settings',
            array( $this, 'settings_page' )
        );

        // Role Switcher submenu
        add_submenu_page(
            'schedspot',
            __( 'Role Switcher', 'schedspot' ),
            __( 'Role Switcher', 'schedspot' ),
            'manage_options',
            'schedspot-role-switcher',
            array( $this, 'role_switcher_page' )
        );
    }

    /**
     * Initialize admin settings.
     *
     * @since 0.1.0
     */
    public function admin_init() {
        // Register settings
        register_setting( 'schedspot_general_settings', 'schedspot_default_timezone' );
        register_setting( 'schedspot_general_settings', 'schedspot_date_format' );
        register_setting( 'schedspot_general_settings', 'schedspot_time_format' );
        register_setting( 'schedspot_general_settings', 'schedspot_currency' );

        register_setting( 'schedspot_booking_settings', 'schedspot_default_slot_length' );
        register_setting( 'schedspot_booking_settings', 'schedspot_minimum_notice' );
        register_setting( 'schedspot_booking_settings', 'schedspot_auto_approve_bookings' );

        register_setting( 'schedspot_payment_settings', 'schedspot_system_fee_per_hour' );
        register_setting( 'schedspot_payment_settings', 'schedspot_commission_rate' );
        register_setting( 'schedspot_payment_settings', 'schedspot_payment_required' );
        register_setting( 'schedspot_payment_settings', 'schedspot_deposit_rate' );

        // Geolocation settings
        register_setting( 'schedspot_geolocation_settings', 'schedspot_enable_geofencing' );
        register_setting( 'schedspot_geolocation_settings', 'schedspot_google_maps_api_key' );
        register_setting( 'schedspot_geolocation_settings', 'schedspot_default_service_radius' );
        register_setting( 'schedspot_geolocation_settings', 'schedspot_distance_unit' );
        register_setting( 'schedspot_payment_settings', 'schedspot_enable_payments' );

        // Google Calendar settings
        register_setting( 'schedspot_calendar_settings', 'schedspot_gcal_enabled' );
        register_setting( 'schedspot_calendar_settings', 'schedspot_gcal_client_id' );
        register_setting( 'schedspot_calendar_settings', 'schedspot_gcal_client_secret' );
        register_setting( 'schedspot_calendar_settings', 'schedspot_gcal_calendar_id' );

        // SMS settings
        register_setting( 'schedspot_sms_settings', 'schedspot_sms_enabled' );
        register_setting( 'schedspot_sms_settings', 'schedspot_sms_provider' );
        register_setting( 'schedspot_sms_settings', 'schedspot_twilio_account_sid' );
        register_setting( 'schedspot_sms_settings', 'schedspot_twilio_auth_token' );
        register_setting( 'schedspot_sms_settings', 'schedspot_twilio_phone_number' );
        register_setting( 'schedspot_sms_settings', 'schedspot_sms_booking_notifications' );
        register_setting( 'schedspot_sms_settings', 'schedspot_sms_message_notifications' );

        // Messaging settings
        register_setting( 'schedspot_messaging_settings', 'schedspot_enable_messaging' );
        register_setting( 'schedspot_messaging_settings', 'schedspot_allow_file_attachments' );
        register_setting( 'schedspot_messaging_settings', 'schedspot_max_file_size' );
        register_setting( 'schedspot_messaging_settings', 'schedspot_allowed_file_types' );
        register_setting( 'schedspot_messaging_settings', 'schedspot_message_retention_days' );

        // Email notification settings
        register_setting( 'schedspot_email_settings', 'schedspot_email_notifications_enabled' );
        register_setting( 'schedspot_email_settings', 'schedspot_admin_email' );
        register_setting( 'schedspot_email_settings', 'schedspot_email_from_name' );
        register_setting( 'schedspot_email_settings', 'schedspot_email_from_address' );
        register_setting( 'schedspot_email_settings', 'schedspot_booking_confirmation_template' );
        register_setting( 'schedspot_email_settings', 'schedspot_booking_reminder_template' );

        // Advanced settings
        register_setting( 'schedspot_advanced_settings', 'schedspot_enable_debug_mode' );
        register_setting( 'schedspot_advanced_settings', 'schedspot_cache_duration' );
        register_setting( 'schedspot_advanced_settings', 'schedspot_api_rate_limit' );
        register_setting( 'schedspot_advanced_settings', 'schedspot_cleanup_old_data' );
        register_setting( 'schedspot_advanced_settings', 'schedspot_data_retention_days' );

        // Add settings sections
        add_settings_section(
            'schedspot_general_section',
            __( 'General Settings', 'schedspot' ),
            array( $this, 'general_section_callback' ),
            'schedspot_general_settings'
        );

        add_settings_section(
            'schedspot_messaging_section',
            __( 'Messaging Settings', 'schedspot' ),
            array( $this, 'messaging_section_callback' ),
            'schedspot_messaging_settings'
        );

        add_settings_section(
            'schedspot_email_section',
            __( 'Email Settings', 'schedspot' ),
            array( $this, 'email_section_callback' ),
            'schedspot_email_settings'
        );

        add_settings_section(
            'schedspot_advanced_section',
            __( 'Advanced Settings', 'schedspot' ),
            array( $this, 'advanced_section_callback' ),
            'schedspot_advanced_settings'
        );

        add_settings_section(
            'schedspot_booking_section',
            __( 'Booking Settings', 'schedspot' ),
            array( $this, 'booking_section_callback' ),
            'schedspot_booking_settings'
        );

        add_settings_section(
            'schedspot_payment_section',
            __( 'Payment Settings', 'schedspot' ),
            array( $this, 'payment_section_callback' ),
            'schedspot_payment_settings'
        );

        add_settings_section(
            'schedspot_geolocation_section',
            __( 'Geolocation Settings', 'schedspot' ),
            array( $this, 'geolocation_section_callback' ),
            'schedspot_geolocation_settings'
        );

        // Add settings fields
        $this->add_settings_fields();
    }

    /**
     * Add settings fields.
     *
     * @since 0.1.0
     */
    private function add_settings_fields() {
        // General settings fields
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

        // Booking settings fields
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

        // Payment settings fields
        add_settings_field(
            'schedspot_system_fee_per_hour',
            __( 'System Fee per Hour ($)', 'schedspot' ),
            array( $this, 'system_fee_field_callback' ),
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

        add_settings_field(
            'schedspot_payment_required',
            __( 'Payment Required', 'schedspot' ),
            array( $this, 'payment_required_field_callback' ),
            'schedspot_payment_settings',
            'schedspot_payment_section'
        );

        add_settings_field(
            'schedspot_deposit_rate',
            __( 'Deposit Rate (%)', 'schedspot' ),
            array( $this, 'deposit_rate_field_callback' ),
            'schedspot_payment_settings',
            'schedspot_payment_section'
        );

        add_settings_field(
            'schedspot_enable_payments',
            __( 'Enable WooCommerce Integration', 'schedspot' ),
            array( $this, 'enable_payments_field_callback' ),
            'schedspot_payment_settings',
            'schedspot_payment_section'
        );

        // Geolocation settings fields
        add_settings_field(
            'schedspot_enable_geofencing',
            __( 'Enable Geofencing', 'schedspot' ),
            array( $this, 'enable_geofencing_field_callback' ),
            'schedspot_geolocation_settings',
            'schedspot_geolocation_section'
        );

        add_settings_field(
            'schedspot_google_maps_api_key',
            __( 'Google Maps API Key', 'schedspot' ),
            array( $this, 'google_maps_api_key_field_callback' ),
            'schedspot_geolocation_settings',
            'schedspot_geolocation_section'
        );

        add_settings_field(
            'schedspot_default_service_radius',
            __( 'Default Service Radius (km)', 'schedspot' ),
            array( $this, 'default_service_radius_field_callback' ),
            'schedspot_geolocation_settings',
            'schedspot_geolocation_section'
        );

        add_settings_field(
            'schedspot_distance_unit',
            __( 'Distance Unit', 'schedspot' ),
            array( $this, 'distance_unit_field_callback' ),
            'schedspot_geolocation_settings',
            'schedspot_geolocation_section'
        );

        // Messaging settings fields
        add_settings_field(
            'schedspot_enable_messaging',
            __( 'Enable Messaging', 'schedspot' ),
            array( $this, 'enable_messaging_field_callback' ),
            'schedspot_messaging_settings',
            'schedspot_messaging_section'
        );

        add_settings_field(
            'schedspot_allow_file_attachments',
            __( 'Allow File Attachments', 'schedspot' ),
            array( $this, 'allow_file_attachments_field_callback' ),
            'schedspot_messaging_settings',
            'schedspot_messaging_section'
        );

        add_settings_field(
            'schedspot_max_file_size',
            __( 'Max File Size (MB)', 'schedspot' ),
            array( $this, 'max_file_size_field_callback' ),
            'schedspot_messaging_settings',
            'schedspot_messaging_section'
        );

        add_settings_field(
            'schedspot_allowed_file_types',
            __( 'Allowed File Types', 'schedspot' ),
            array( $this, 'allowed_file_types_field_callback' ),
            'schedspot_messaging_settings',
            'schedspot_messaging_section'
        );

        add_settings_field(
            'schedspot_message_retention_days',
            __( 'Message Retention (Days)', 'schedspot' ),
            array( $this, 'message_retention_days_field_callback' ),
            'schedspot_messaging_settings',
            'schedspot_messaging_section'
        );

        // Email settings fields
        add_settings_field(
            'schedspot_email_notifications_enabled',
            __( 'Enable Email Notifications', 'schedspot' ),
            array( $this, 'email_notifications_enabled_field_callback' ),
            'schedspot_email_settings',
            'schedspot_email_section'
        );

        add_settings_field(
            'schedspot_admin_email',
            __( 'Admin Email', 'schedspot' ),
            array( $this, 'admin_email_field_callback' ),
            'schedspot_email_settings',
            'schedspot_email_section'
        );

        add_settings_field(
            'schedspot_email_from_name',
            __( 'From Name', 'schedspot' ),
            array( $this, 'email_from_name_field_callback' ),
            'schedspot_email_settings',
            'schedspot_email_section'
        );

        add_settings_field(
            'schedspot_email_from_address',
            __( 'From Address', 'schedspot' ),
            array( $this, 'email_from_address_field_callback' ),
            'schedspot_email_settings',
            'schedspot_email_section'
        );

        // Advanced settings fields
        add_settings_field(
            'schedspot_enable_debug_mode',
            __( 'Enable Debug Mode', 'schedspot' ),
            array( $this, 'enable_debug_mode_field_callback' ),
            'schedspot_advanced_settings',
            'schedspot_advanced_section'
        );

        add_settings_field(
            'schedspot_cache_duration',
            __( 'Cache Duration (Minutes)', 'schedspot' ),
            array( $this, 'cache_duration_field_callback' ),
            'schedspot_advanced_settings',
            'schedspot_advanced_section'
        );

        add_settings_field(
            'schedspot_api_rate_limit',
            __( 'API Rate Limit (Requests/Hour)', 'schedspot' ),
            array( $this, 'api_rate_limit_field_callback' ),
            'schedspot_advanced_settings',
            'schedspot_advanced_section'
        );

        add_settings_field(
            'schedspot_data_retention_days',
            __( 'Data Retention (Days)', 'schedspot' ),
            array( $this, 'data_retention_days_field_callback' ),
            'schedspot_advanced_settings',
            'schedspot_advanced_section'
        );
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @since 0.1.0
     * @param string $hook Current admin page hook.
     */
    public function admin_scripts( $hook ) {
        // Only load on SchedSpot admin pages
        if ( strpos( $hook, 'schedspot' ) === false ) {
            return;
        }

        // Add inline admin styles for now
        wp_add_inline_style( 'wp-admin', $this->get_admin_styles() );
    }

    /**
     * Add plugin action links.
     *
     * @since 0.1.0
     * @param array $links Existing action links.
     * @return array Modified action links.
     */
    public function plugin_action_links( $links ) {
        $settings_link = '<a href="' . admin_url( 'admin.php?page=schedspot-settings' ) . '">' . __( 'Settings', 'schedspot' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Dashboard page callback.
     *
     * @since 0.1.0
     */
    public function dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'SchedSpot Dashboard', 'schedspot' ); ?></h1>
            
            <div class="schedspot-dashboard-widgets">
                <div class="schedspot-widget">
                    <h3><?php _e( 'Recent Bookings', 'schedspot' ); ?></h3>
                    <?php $this->render_recent_bookings_widget(); ?>
                </div>
                
                <div class="schedspot-widget">
                    <h3><?php _e( 'Quick Stats', 'schedspot' ); ?></h3>
                    <?php $this->render_quick_stats_widget(); ?>
                </div>
                
                <div class="schedspot-widget">
                    <h3><?php _e( 'Quick Actions', 'schedspot' ); ?></h3>
                    <?php $this->render_quick_actions_widget(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Bookings page callback.
     *
     * @since 0.1.0
     */
    public function bookings_page() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
        $booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;

        // Handle form submissions
        if ( isset( $_POST['schedspot_booking_action'] ) ) {
            $this->handle_booking_form_submission();
        }

        switch ( $action ) {
            case 'view':
                $this->render_booking_details( $booking_id );
                break;
            case 'edit':
                $this->render_edit_booking_form( $booking_id );
                break;
            case 'delete':
                $this->handle_delete_booking( $booking_id );
                break;
            default:
                $this->render_bookings_list();
                break;
        }
    }

    /**
     * Services page callback.
     *
     * @since 1.0.0
     */
    public function services_page() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
        $service_id = isset( $_GET['service_id'] ) ? absint( $_GET['service_id'] ) : 0;

        // Handle form submissions
        if ( isset( $_POST['schedspot_service_action'] ) ) {
            $this->handle_service_form_submission();
        }

        switch ( $action ) {
            case 'add':
                $this->render_add_service_form();
                break;
            case 'edit':
                $this->render_edit_service_form( $service_id );
                break;
            case 'delete':
                $this->handle_delete_service( $service_id );
                break;
            default:
                $this->render_services_list();
                break;
        }
    }

    /**
     * Workers page callback.
     *
     * @since 1.0.0
     */
    public function workers_page() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
        $worker_id = isset( $_GET['worker_id'] ) ? absint( $_GET['worker_id'] ) : 0;

        // Handle form submissions
        if ( isset( $_POST['schedspot_worker_action'] ) ) {
            $this->handle_worker_form_submission();
        }

        switch ( $action ) {
            case 'add':
                $this->render_add_worker_form();
                break;
            case 'edit':
                $this->render_edit_worker_form( $worker_id );
                break;
            case 'view':
                $this->render_worker_profile( $worker_id );
                break;
            case 'availability':
                $this->render_worker_availability( $worker_id );
                break;
            case 'delete':
                $this->handle_delete_worker( $worker_id );
                break;
            default:
                $this->render_workers_list();
                break;
        }
    }

    /**
     * Settings page callback.
     *
     * @since 0.1.0
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
                    } elseif ( $active_tab == 'geolocation' ) {
                        settings_fields( 'schedspot_geolocation_settings' );
                        do_settings_sections( 'schedspot_geolocation_settings' );
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
     * Role switcher page callback.
     *
     * @since 1.0.0
     */
    public function role_switcher_page() {
        // Handle role switch request
        if ( isset( $_POST['switch_role'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'schedspot_switch_role' ) ) {
            $this->process_role_switch();
        }

        $current_user = wp_get_current_user();
        $current_role = $this->get_current_admin_role_mode();
        ?>
        <div class="wrap">
            <h1><?php _e( 'Admin Role Switcher', 'schedspot' ); ?></h1>

            <div class="schedspot-role-switcher">
                <div class="current-role-info">
                    <h3><?php _e( 'Current Mode', 'schedspot' ); ?></h3>
                    <p><strong><?php echo esc_html( $this->get_role_display_name( $current_role ) ); ?></strong></p>
                    <p><?php _e( 'You are currently viewing the system as:', 'schedspot' ); ?>
                       <em><?php echo esc_html( $this->get_role_description( $current_role ) ); ?></em>
                    </p>
                </div>

                <div class="role-switch-form">
                    <h3><?php _e( 'Switch to Different Role', 'schedspot' ); ?></h3>
                    <form method="post" action="">
                        <?php wp_nonce_field( 'schedspot_switch_role' ); ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e( 'Switch to Role', 'schedspot' ); ?></th>
                                <td>
                                    <select name="target_role" required>
                                        <option value=""><?php _e( 'Select a role...', 'schedspot' ); ?></option>
                                        <option value="administrator" <?php selected( $current_role, 'administrator' ); ?>><?php _e( 'Administrator (Default)', 'schedspot' ); ?></option>
                                        <option value="schedspot_worker" <?php selected( $current_role, 'schedspot_worker' ); ?>><?php _e( 'Worker View', 'schedspot' ); ?></option>
                                        <option value="schedspot_customer" <?php selected( $current_role, 'schedspot_customer' ); ?>><?php _e( 'Customer View', 'schedspot' ); ?></option>
                                    </select>
                                    <p class="description"><?php _e( 'Switch your view to test different user experiences without logging out.', 'schedspot' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e( 'Test User (Optional)', 'schedspot' ); ?></th>
                                <td>
                                    <select name="test_user_id">
                                        <option value=""><?php _e( 'Use current admin account', 'schedspot' ); ?></option>
                                        <?php
                                        $workers = get_users( array( 'role' => 'schedspot_worker', 'number' => 20 ) );
                                        foreach ( $workers as $worker ) {
                                            echo '<option value="' . esc_attr( $worker->ID ) . '">' . esc_html( $worker->display_name . ' (Worker)' ) . '</option>';
                                        }

                                        $customers = get_users( array( 'role' => 'schedspot_customer', 'number' => 20 ) );
                                        foreach ( $customers as $customer ) {
                                            echo '<option value="' . esc_attr( $customer->ID ) . '">' . esc_html( $customer->display_name . ' (Customer)' ) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <p class="description"><?php _e( 'Optionally impersonate a specific user to test their exact experience.', 'schedspot' ); ?></p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <input type="submit" name="switch_role" class="button-primary" value="<?php _e( 'Switch Role', 'schedspot' ); ?>" />
                            <?php if ( $current_role !== 'administrator' ) : ?>
                                <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=schedspot-role-switcher&reset_role=1' ), 'schedspot_reset_role' ); ?>" class="button"><?php _e( 'Reset to Administrator', 'schedspot' ); ?></a>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>

                <div class="role-descriptions">
                    <h3><?php _e( 'Role Descriptions', 'schedspot' ); ?></h3>
                    <div class="role-description-grid">
                        <div class="role-desc">
                            <h4><?php _e( 'Administrator', 'schedspot' ); ?></h4>
                            <p><?php _e( 'Full access to all plugin features, settings, and management capabilities.', 'schedspot' ); ?></p>
                        </div>
                        <div class="role-desc">
                            <h4><?php _e( 'Worker View', 'schedspot' ); ?></h4>
                            <p><?php _e( 'Experience the system as a service provider - manage bookings, availability, and earnings.', 'schedspot' ); ?></p>
                        </div>
                        <div class="role-desc">
                            <h4><?php _e( 'Customer View', 'schedspot' ); ?></h4>
                            <p><?php _e( 'Experience the system as a client - book services, view history, and communicate with workers.', 'schedspot' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .schedspot-role-switcher {
            max-width: 800px;
        }
        .current-role-info {
            background: #f1f1f1;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .role-descriptions {
            margin-top: 30px;
        }
        .role-description-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        .role-desc {
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .role-desc h4 {
            margin-top: 0;
            color: #0073aa;
        }
        </style>
        <?php
    }

    /**
     * Process role switch request.
     *
     * @since 1.0.0
     */
    private function process_role_switch() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to switch roles.', 'schedspot' ) );
        }

        $target_role = sanitize_text_field( $_POST['target_role'] );
        $test_user_id = isset( $_POST['test_user_id'] ) ? absint( $_POST['test_user_id'] ) : 0;

        // Validate target role
        $allowed_roles = array( 'administrator', 'schedspot_worker', 'schedspot_customer' );
        if ( ! in_array( $target_role, $allowed_roles ) ) {
            add_settings_error( 'schedspot_role_switcher', 'invalid_role', __( 'Invalid role selected.', 'schedspot' ) );
            return;
        }

        // Store the role switch in user meta
        update_user_meta( get_current_user_id(), 'schedspot_admin_role_mode', $target_role );

        if ( $test_user_id > 0 ) {
            update_user_meta( get_current_user_id(), 'schedspot_admin_impersonate_user', $test_user_id );
        } else {
            delete_user_meta( get_current_user_id(), 'schedspot_admin_impersonate_user' );
        }

        add_settings_error( 'schedspot_role_switcher', 'role_switched',
            sprintf( __( 'Successfully switched to %s mode.', 'schedspot' ), $this->get_role_display_name( $target_role ) ),
            'updated'
        );
    }

    /**
     * Get current admin role mode.
     *
     * @since 1.0.0
     * @return string Current role mode.
     */
    private function get_current_admin_role_mode() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return '';
        }

        // Check for quick switch request
        if ( isset( $_GET['quick_switch'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'schedspot_quick_switch' ) ) {
            $target_role = sanitize_text_field( $_GET['quick_switch'] );
            $allowed_roles = array( 'administrator', 'schedspot_worker', 'schedspot_customer' );

            if ( in_array( $target_role, $allowed_roles ) ) {
                update_user_meta( get_current_user_id(), 'schedspot_admin_role_mode', $target_role );

                // Redirect to remove the query parameters
                wp_redirect( remove_query_arg( array( 'quick_switch', '_wpnonce' ) ) );
                exit;
            }
        }

        // Check for reset request
        if ( isset( $_GET['reset_role'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'schedspot_reset_role' ) ) {
            delete_user_meta( get_current_user_id(), 'schedspot_admin_role_mode' );
            delete_user_meta( get_current_user_id(), 'schedspot_admin_impersonate_user' );
            return 'administrator';
        }

        return get_user_meta( get_current_user_id(), 'schedspot_admin_role_mode', true ) ?: 'administrator';
    }

    /**
     * Get role display name.
     *
     * @since 1.0.0
     * @param string $role Role name.
     * @return string Display name.
     */
    private function get_role_display_name( $role ) {
        $names = array(
            'administrator'       => __( 'Administrator', 'schedspot' ),
            'schedspot_worker'    => __( 'Worker', 'schedspot' ),
            'schedspot_customer'  => __( 'Customer', 'schedspot' ),
        );

        return isset( $names[ $role ] ) ? $names[ $role ] : $role;
    }

    /**
     * Get role description.
     *
     * @since 1.0.0
     * @param string $role Role name.
     * @return string Description.
     */
    private function get_role_description( $role ) {
        $descriptions = array(
            'administrator'       => __( 'Full administrative access', 'schedspot' ),
            'schedspot_worker'    => __( 'Service provider experience', 'schedspot' ),
            'schedspot_customer'  => __( 'Client booking experience', 'schedspot' ),
        );

        return isset( $descriptions[ $role ] ) ? $descriptions[ $role ] : '';
    }

    /**
     * Add role switcher to admin bar.
     *
     * @since 1.0.0
     * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
     */
    public function add_admin_bar_role_switcher( $wp_admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $current_role = $this->get_current_admin_role_mode();

        $wp_admin_bar->add_node( array(
            'id'    => 'schedspot-role-switcher',
            'title' => sprintf( __( 'SchedSpot: %s', 'schedspot' ), $this->get_role_display_name( $current_role ) ),
            'href'  => admin_url( 'admin.php?page=schedspot-role-switcher' ),
            'meta'  => array(
                'title' => __( 'Switch SchedSpot admin role view', 'schedspot' ),
            ),
        ) );

        // Add quick switch options
        $roles = array(
            'administrator'       => __( 'Administrator', 'schedspot' ),
            'schedspot_worker'    => __( 'Worker View', 'schedspot' ),
            'schedspot_customer'  => __( 'Customer View', 'schedspot' ),
        );

        foreach ( $roles as $role => $name ) {
            if ( $role === $current_role ) {
                continue;
            }

            $wp_admin_bar->add_node( array(
                'parent' => 'schedspot-role-switcher',
                'id'     => 'schedspot-switch-' . $role,
                'title'  => $name,
                'href'   => wp_nonce_url(
                    admin_url( 'admin.php?page=schedspot-role-switcher&quick_switch=' . $role ),
                    'schedspot_quick_switch'
                ),
            ) );
        }
    }

    /**
     * Handle AJAX role switch.
     *
     * @since 1.0.0
     */
    public function handle_role_switch() {
        if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $_POST['nonce'], 'schedspot_role_switch' ) ) {
            wp_die( __( 'Permission denied.', 'schedspot' ) );
        }

        $target_role = sanitize_text_field( $_POST['role'] );
        update_user_meta( get_current_user_id(), 'schedspot_admin_role_mode', $target_role );

        wp_send_json_success( array(
            'message' => sprintf( __( 'Switched to %s mode', 'schedspot' ), $this->get_role_display_name( $target_role ) ),
            'role' => $target_role,
        ) );
    }

    /**
     * Render recent bookings widget.
     *
     * @since 0.1.0
     */
    private function render_recent_bookings_widget() {
        $bookings = SchedSpot_Booking::get_bookings( array( 'limit' => 5 ) );
        
        if ( ! empty( $bookings ) ) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . __( 'Client', 'schedspot' ) . '</th><th>' . __( 'Date', 'schedspot' ) . '</th><th>' . __( 'Status', 'schedspot' ) . '</th></tr></thead>';
            echo '<tbody>';
            
            foreach ( $bookings as $booking ) {
                echo '<tr>';
                echo '<td>' . esc_html( $booking->client_details['name'] ) . '</td>';
                echo '<td>' . esc_html( date( 'M j, Y', strtotime( $booking->booking_date ) ) ) . '</td>';
                echo '<td>' . esc_html( ucfirst( $booking->status ) ) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . __( 'No bookings found.', 'schedspot' ) . '</p>';
        }
    }

    /**
     * Render quick stats widget.
     *
     * @since 0.1.0
     */
    private function render_quick_stats_widget() {
        global $wpdb;
        
        $total_bookings = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings" );
        $pending_bookings = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings WHERE status = 'pending'" );
        $total_workers = count( get_users( array( 'role' => 'schedspot_worker' ) ) );
        $total_customers = count( get_users( array( 'role' => 'schedspot_customer' ) ) );
        
        ?>
        <div class="schedspot-stats-grid">
            <div class="stat-item">
                <div class="stat-number"><?php echo esc_html( $total_bookings ); ?></div>
                <div class="stat-label"><?php _e( 'Total Bookings', 'schedspot' ); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo esc_html( $pending_bookings ); ?></div>
                <div class="stat-label"><?php _e( 'Pending Bookings', 'schedspot' ); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo esc_html( $total_workers ); ?></div>
                <div class="stat-label"><?php _e( 'Workers', 'schedspot' ); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo esc_html( $total_customers ); ?></div>
                <div class="stat-label"><?php _e( 'Customers', 'schedspot' ); ?></div>
            </div>
        </div>
        <?php
    }

    /**
     * Render quick actions widget.
     *
     * @since 0.1.0
     */
    private function render_quick_actions_widget() {
        ?>
        <div class="schedspot-quick-actions">
            <a href="<?php echo admin_url( 'admin.php?page=schedspot-bookings' ); ?>" class="button button-primary"><?php _e( 'View All Bookings', 'schedspot' ); ?></a>
            <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers' ); ?>" class="button"><?php _e( 'Manage Workers', 'schedspot' ); ?></a>
            <a href="<?php echo admin_url( 'admin.php?page=schedspot-settings' ); ?>" class="button"><?php _e( 'Settings', 'schedspot' ); ?></a>
        </div>
        <?php
    }

    /**
     * Render bookings list.
     *
     * @since 0.1.0
     */
    private function render_bookings_list() {
        $bookings = SchedSpot_Booking::get_bookings( array( 'limit' => 50 ) );
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e( 'ID', 'schedspot' ); ?></th>
                    <th><?php _e( 'Client', 'schedspot' ); ?></th>
                    <th><?php _e( 'Worker', 'schedspot' ); ?></th>
                    <th><?php _e( 'Date', 'schedspot' ); ?></th>
                    <th><?php _e( 'Time', 'schedspot' ); ?></th>
                    <th><?php _e( 'Status', 'schedspot' ); ?></th>
                    <th><?php _e( 'Total', 'schedspot' ); ?></th>
                    <th><?php _e( 'Actions', 'schedspot' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $bookings ) ) : ?>
                    <?php foreach ( $bookings as $booking ) : ?>
                        <tr>
                            <td><?php echo esc_html( $booking->id ); ?></td>
                            <td><?php echo esc_html( $booking->client_details['name'] ); ?></td>
                            <td><?php echo esc_html( get_userdata( $booking->worker_id )->display_name ); ?></td>
                            <td><?php echo esc_html( date( 'M j, Y', strtotime( $booking->booking_date ) ) ); ?></td>
                            <td><?php echo esc_html( date( 'g:i A', strtotime( $booking->start_time ) ) ); ?></td>
                            <td><?php echo esc_html( ucfirst( $booking->status ) ); ?></td>
                            <td>$<?php echo esc_html( number_format( $booking->total_cost, 2 ) ); ?></td>
                            <td>
                                <a href="<?php echo admin_url( 'admin.php?page=schedspot-bookings&action=view&booking_id=' . $booking->id ); ?>" class="button button-small"><?php _e( 'View', 'schedspot' ); ?></a>
                                <a href="<?php echo admin_url( 'admin.php?page=schedspot-bookings&action=edit&booking_id=' . $booking->id ); ?>" class="button button-small"><?php _e( 'Edit', 'schedspot' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8"><?php _e( 'No bookings found.', 'schedspot' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render detailed booking view.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     */
    private function render_booking_details( $booking_id ) {
        $booking = new SchedSpot_Booking( $booking_id );

        if ( ! $booking->id ) {
            echo '<div class="notice notice-error"><p>' . __( 'Booking not found.', 'schedspot' ) . '</p></div>';
            return;
        }

        $customer = get_userdata( $booking->user_id );
        $worker = get_userdata( $booking->worker_id );

        ?>
        <div class="wrap">
            <h1><?php printf( __( 'Booking #%d Details', 'schedspot' ), $booking->id ); ?></h1>

            <a href="<?php echo admin_url( 'admin.php?page=schedspot-bookings' ); ?>" class="button"><?php _e( '← Back to Bookings', 'schedspot' ); ?></a>

            <div class="schedspot-booking-details">
                <div class="booking-info-grid">
                    <!-- Basic Information -->
                    <div class="booking-section">
                        <h3><?php _e( 'Booking Information', 'schedspot' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><?php _e( 'Booking ID', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $booking->id ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Status', 'schedspot' ); ?></th>
                                <td><span class="status-badge status-<?php echo esc_attr( $booking->status ); ?>"><?php echo esc_html( ucfirst( $booking->status ) ); ?></span></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Service', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $booking->service_name ?: __( 'General Service', 'schedspot' ) ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Date & Time', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( date( 'F j, Y g:i A', strtotime( $booking->booking_date . ' ' . $booking->start_time ) ) ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Duration', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $booking->duration ); ?> <?php _e( 'minutes', 'schedspot' ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Total Cost', 'schedspot' ); ?></th>
                                <td><strong>$<?php echo esc_html( number_format( $booking->total_cost, 2 ) ); ?></strong></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Created', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( date( 'F j, Y g:i A', strtotime( $booking->created_at ) ) ); ?></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Customer Information -->
                    <div class="booking-section">
                        <h3><?php _e( 'Customer Information', 'schedspot' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><?php _e( 'Name', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $customer->display_name ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Email', 'schedspot' ); ?></th>
                                <td><a href="mailto:<?php echo esc_attr( $customer->user_email ); ?>"><?php echo esc_html( $customer->user_email ); ?></a></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Phone', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $booking->client_details['phone'] ?? __( 'Not provided', 'schedspot' ) ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Address', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $booking->client_details['address'] ?? __( 'Not provided', 'schedspot' ) ); ?></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Worker Information -->
                    <div class="booking-section">
                        <h3><?php _e( 'Worker Information', 'schedspot' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><?php _e( 'Name', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $worker->display_name ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Email', 'schedspot' ); ?></th>
                                <td><a href="mailto:<?php echo esc_attr( $worker->user_email ); ?>"><?php echo esc_html( $worker->user_email ); ?></a></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Profile', 'schedspot' ); ?></th>
                                <td><a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=view&worker_id=' . $worker->ID ); ?>" class="button button-small"><?php _e( 'View Profile', 'schedspot' ); ?></a></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Payment Information -->
                    <div class="booking-section">
                        <h3><?php _e( 'Payment Information', 'schedspot' ); ?></h3>
                        <?php
                        $payment_status = get_post_meta( $booking->id, 'schedspot_payment_status', true ) ?: 'pending';
                        $wc_order_id = get_post_meta( $booking->id, 'schedspot_wc_order_id', true );
                        ?>
                        <table class="form-table">
                            <tr>
                                <th><?php _e( 'Payment Status', 'schedspot' ); ?></th>
                                <td><span class="payment-status payment-<?php echo esc_attr( $payment_status ); ?>"><?php echo esc_html( ucfirst( $payment_status ) ); ?></span></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Total Amount', 'schedspot' ); ?></th>
                                <td>$<?php echo esc_html( number_format( $booking->total_cost, 2 ) ); ?></td>
                            </tr>
                            <?php if ( $wc_order_id ) : ?>
                            <tr>
                                <th><?php _e( 'WooCommerce Order', 'schedspot' ); ?></th>
                                <td><a href="<?php echo admin_url( 'post.php?post=' . $wc_order_id . '&action=edit' ); ?>" target="_blank">#<?php echo esc_html( $wc_order_id ); ?></a></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <!-- Messages Section -->
                <div class="booking-section full-width">
                    <h3><?php _e( 'Messages', 'schedspot' ); ?></h3>
                    <?php $this->render_booking_messages( $booking->id ); ?>
                </div>

                <!-- Booking Timeline -->
                <div class="booking-section full-width">
                    <h3><?php _e( 'Booking Timeline', 'schedspot' ); ?></h3>
                    <?php $this->render_booking_timeline( $booking->id ); ?>
                </div>

                <!-- Actions -->
                <div class="booking-actions">
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-bookings&action=edit&booking_id=' . $booking->id ); ?>" class="button button-primary"><?php _e( 'Edit Booking', 'schedspot' ); ?></a>
                    <?php if ( $booking->status === 'pending' ) : ?>
                        <button type="button" class="button" onclick="updateBookingStatus(<?php echo $booking->id; ?>, 'confirmed')"><?php _e( 'Confirm Booking', 'schedspot' ); ?></button>
                    <?php endif; ?>
                    <?php if ( in_array( $booking->status, array( 'pending', 'confirmed' ) ) ) : ?>
                        <button type="button" class="button" onclick="updateBookingStatus(<?php echo $booking->id; ?>, 'cancelled')"><?php _e( 'Cancel Booking', 'schedspot' ); ?></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <style>
        .booking-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .booking-section {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
        }
        .booking-section.full-width {
            grid-column: 1 / -1;
        }
        .booking-section h3 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .payment-status {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .payment-pending { background: #fff3cd; color: #856404; }
        .payment-paid { background: #d4edda; color: #155724; }
        .payment-failed { background: #f8d7da; color: #721c24; }
        .booking-actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .booking-actions .button {
            margin-right: 10px;
        }
        </style>

        <script>
        function updateBookingStatus(bookingId, status) {
            if (confirm('<?php _e( 'Are you sure you want to update this booking status?', 'schedspot' ); ?>')) {
                jQuery.post(ajaxurl, {
                    action: 'schedspot_update_booking_status',
                    booking_id: bookingId,
                    status: status,
                    nonce: '<?php echo wp_create_nonce( 'schedspot_booking_status' ); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('<?php _e( 'Error updating booking status.', 'schedspot' ); ?>');
                    }
                });
            }
        }
        </script>
        <?php
    }

    /**
     * Render edit booking form.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     */
    private function render_edit_booking_form( $booking_id ) {
        $booking = new SchedSpot_Booking( $booking_id );

        if ( ! $booking->id ) {
            echo '<div class="notice notice-error"><p>' . __( 'Booking not found.', 'schedspot' ) . '</p></div>';
            return;
        }

        $workers = get_users( array( 'role' => 'schedspot_worker' ) );
        $services = SchedSpot_Service::get_services();

        ?>
        <div class="wrap">
            <h1><?php printf( __( 'Edit Booking #%d', 'schedspot' ), $booking->id ); ?></h1>

            <a href="<?php echo admin_url( 'admin.php?page=schedspot-bookings' ); ?>" class="button"><?php _e( '← Back to Bookings', 'schedspot' ); ?></a>

            <form method="post" action="">
                <?php wp_nonce_field( 'schedspot_booking_form', 'schedspot_booking_nonce' ); ?>
                <input type="hidden" name="schedspot_booking_action" value="edit">
                <input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking->id ); ?>">

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e( 'Client Name', 'schedspot' ); ?></th>
                        <td>
                            <input type="text" name="client_name" value="<?php echo esc_attr( $booking->client_details['name'] ); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Client Email', 'schedspot' ); ?></th>
                        <td>
                            <input type="email" name="client_email" value="<?php echo esc_attr( $booking->client_details['email'] ); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Client Phone', 'schedspot' ); ?></th>
                        <td>
                            <input type="tel" name="client_phone" value="<?php echo esc_attr( $booking->client_details['phone'] ); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Service', 'schedspot' ); ?></th>
                        <td>
                            <select name="service_id" required>
                                <option value=""><?php _e( 'Select Service', 'schedspot' ); ?></option>
                                <?php foreach ( $services as $service ) : ?>
                                    <option value="<?php echo esc_attr( $service->id ); ?>" <?php selected( $booking->service_id, $service->id ); ?>>
                                        <?php echo esc_html( $service->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Worker', 'schedspot' ); ?></th>
                        <td>
                            <select name="worker_id">
                                <option value=""><?php _e( 'Auto-assign', 'schedspot' ); ?></option>
                                <?php foreach ( $workers as $worker ) : ?>
                                    <option value="<?php echo esc_attr( $worker->ID ); ?>" <?php selected( $booking->worker_id, $worker->ID ); ?>>
                                        <?php echo esc_html( $worker->display_name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Date', 'schedspot' ); ?></th>
                        <td>
                            <input type="date" name="booking_date" value="<?php echo esc_attr( $booking->booking_date ); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Start Time', 'schedspot' ); ?></th>
                        <td>
                            <input type="time" name="start_time" value="<?php echo esc_attr( substr( $booking->start_time, 0, 5 ) ); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Duration (minutes)', 'schedspot' ); ?></th>
                        <td>
                            <input type="number" name="duration" value="<?php echo esc_attr( $booking->duration ); ?>" min="15" step="15" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Status', 'schedspot' ); ?></th>
                        <td>
                            <select name="status">
                                <?php foreach ( SchedSpot_Booking::get_booking_statuses() as $status_key => $status_label ) : ?>
                                    <option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $booking->status, $status_key ); ?>>
                                        <?php echo esc_html( $status_label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Total Cost', 'schedspot' ); ?></th>
                        <td>
                            <input type="number" name="total_cost" value="<?php echo esc_attr( $booking->total_cost ); ?>" step="0.01" min="0">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Notes', 'schedspot' ); ?></th>
                        <td>
                            <textarea name="notes" rows="4" cols="50"><?php echo esc_textarea( $booking->notes ); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Address', 'schedspot' ); ?></th>
                        <td>
                            <textarea name="client_address" rows="3" cols="50"><?php echo esc_textarea( $booking->client_details['address'] ); ?></textarea>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Update Booking', 'schedspot' ) ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render booking messages.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     */
    private function render_booking_messages( $booking_id ) {
        $booking = new SchedSpot_Booking( $booking_id );
        $messages = SchedSpot_Message::get_conversation( $booking->user_id, $booking->worker_id, array( 'booking_id' => $booking_id ) );

        if ( empty( $messages ) ) {
            echo '<p>' . __( 'No messages found for this booking.', 'schedspot' ) . '</p>';
            return;
        }

        echo '<div class="booking-messages">';
        foreach ( $messages as $message ) {
            $sender = get_userdata( $message->sender_id );
            $is_worker = $message->sender_id == $booking->worker_id;

            echo '<div class="message-item ' . ( $is_worker ? 'worker-message' : 'customer-message' ) . '">';
            echo '<div class="message-header">';
            echo '<strong>' . esc_html( $sender->display_name ) . '</strong>';
            echo '<span class="message-time">' . esc_html( date( 'M j, Y g:i A', strtotime( $message->created_at ) ) ) . '</span>';
            echo '</div>';
            echo '<div class="message-content">' . wp_kses_post( $message->content ) . '</div>';
            echo '</div>';
        }
        echo '</div>';

        echo '<style>
        .booking-messages {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
        }
        .message-item {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
        }
        .worker-message {
            background: #e3f2fd;
            margin-left: 20px;
        }
        .customer-message {
            background: #f3e5f5;
            margin-right: 20px;
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }
        .message-time {
            color: #666;
        }
        .message-content {
            line-height: 1.4;
        }
        </style>';
    }

    /**
     * Render booking timeline.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     */
    private function render_booking_timeline( $booking_id ) {
        $booking = new SchedSpot_Booking( $booking_id );

        // Get booking status changes from meta or logs
        $timeline_events = array(
            array(
                'date' => $booking->created_at,
                'event' => __( 'Booking Created', 'schedspot' ),
                'description' => __( 'Customer submitted booking request', 'schedspot' ),
                'status' => 'created'
            )
        );

        // Add status change events if available
        $status_changes = get_post_meta( $booking_id, 'schedspot_status_changes', true );
        if ( is_array( $status_changes ) ) {
            foreach ( $status_changes as $change ) {
                $timeline_events[] = array(
                    'date' => $change['date'],
                    'event' => sprintf( __( 'Status changed to %s', 'schedspot' ), ucfirst( $change['status'] ) ),
                    'description' => $change['note'] ?? '',
                    'status' => $change['status']
                );
            }
        }

        // Sort by date
        usort( $timeline_events, function( $a, $b ) {
            return strtotime( $a['date'] ) - strtotime( $b['date'] );
        } );

        echo '<div class="booking-timeline">';
        foreach ( $timeline_events as $event ) {
            echo '<div class="timeline-item">';
            echo '<div class="timeline-marker status-' . esc_attr( $event['status'] ) . '"></div>';
            echo '<div class="timeline-content">';
            echo '<div class="timeline-header">';
            echo '<strong>' . esc_html( $event['event'] ) . '</strong>';
            echo '<span class="timeline-date">' . esc_html( date( 'M j, Y g:i A', strtotime( $event['date'] ) ) ) . '</span>';
            echo '</div>';
            if ( ! empty( $event['description'] ) ) {
                echo '<div class="timeline-description">' . esc_html( $event['description'] ) . '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';

        echo '<style>
        .booking-timeline {
            position: relative;
            padding-left: 30px;
        }
        .booking-timeline::before {
            content: "";
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #ddd;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-marker {
            position: absolute;
            left: -25px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid #fff;
            background: #ddd;
        }
        .timeline-marker.status-created { background: #007cba; }
        .timeline-marker.status-confirmed { background: #46b450; }
        .timeline-marker.status-completed { background: #00a0d2; }
        .timeline-marker.status-cancelled { background: #dc3232; }
        .timeline-content {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            border-left: 3px solid #ddd;
        }
        .timeline-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .timeline-date {
            color: #666;
            font-size: 12px;
        }
        .timeline-description {
            color: #666;
            font-size: 14px;
        }
        </style>';
    }

    /**
     * Render workers list.
     *
     * @since 1.0.0
     */
    private function render_workers_list() {
        $workers = SchedSpot_Worker::get_workers( array( 'number' => 50 ) );

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Workers', 'schedspot' ); ?></h1>
            <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=add' ); ?>" class="page-title-action"><?php _e( 'Add New', 'schedspot' ); ?></a>
            <hr class="wp-header-end">

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Worker', 'schedspot' ); ?></th>
                        <th><?php _e( 'Contact', 'schedspot' ); ?></th>
                        <th><?php _e( 'Profile', 'schedspot' ); ?></th>
                        <th><?php _e( 'Services', 'schedspot' ); ?></th>
                        <th><?php _e( 'Bookings', 'schedspot' ); ?></th>
                        <th><?php _e( 'Status', 'schedspot' ); ?></th>
                        <th><?php _e( 'Actions', 'schedspot' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $workers ) ) : ?>
                        <?php foreach ( $workers as $worker ) : ?>
                            <?php $stats = $worker->get_statistics(); ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <img src="<?php echo esc_url( get_avatar_url( $worker->id, array( 'size' => 32 ) ) ); ?>" style="width: 32px; height: 32px; border-radius: 50%; margin-right: 10px;">
                                        <div>
                                            <strong><?php echo esc_html( $worker->user->display_name ); ?></strong>
                                            <br><small>ID: <?php echo esc_html( $worker->id ); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php echo esc_html( $worker->user->user_email ); ?><br>
                                    <small><?php echo esc_html( $worker->profile['phone'] ); ?></small>
                                </td>
                                <td>
                                    <div class="schedspot-progress-bar" style="background: #f0f0f0; border-radius: 10px; height: 20px; position: relative;">
                                        <div style="background: #0073aa; height: 100%; border-radius: 10px; width: <?php echo esc_attr( $stats['profile_completion'] ); ?>%;"></div>
                                        <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px; color: #333;">
                                            <?php echo esc_html( $stats['profile_completion'] ); ?>%
                                        </span>
                                    </div>
                                    <small><?php printf( __( 'Rating: %.1f', 'schedspot' ), $stats['rating'] ); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $services = $worker->get_services();
                                    echo esc_html( count( $services ) );
                                    ?>
                                </td>
                                <td>
                                    <?php echo esc_html( $stats['total_bookings'] ); ?><br>
                                    <small><?php printf( __( '%.1f%% completion', 'schedspot' ), $stats['completion_rate'] ); ?></small>
                                </td>
                                <td>
                                    <?php if ( $worker->profile['is_available'] ) : ?>
                                        <span style="color: green;">●</span> <?php _e( 'Available', 'schedspot' ); ?>
                                    <?php else : ?>
                                        <span style="color: red;">●</span> <?php _e( 'Unavailable', 'schedspot' ); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=view&worker_id=' . $worker->id ); ?>" class="button button-small"><?php _e( 'View', 'schedspot' ); ?></a>
                                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=edit&worker_id=' . $worker->id ); ?>" class="button button-small"><?php _e( 'Edit', 'schedspot' ); ?></a>
                                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=availability&worker_id=' . $worker->id ); ?>" class="button button-small"><?php _e( 'Schedule', 'schedspot' ); ?></a>
                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=schedspot-workers&action=delete&worker_id=' . $worker->id ), 'delete_worker_' . $worker->id ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php _e( 'Are you sure you want to delete this worker? This action cannot be undone.', 'schedspot' ); ?>')"><?php _e( 'Delete', 'schedspot' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7"><?php _e( 'No workers found.', 'schedspot' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // Settings field callbacks
    public function general_section_callback() {
        echo '<p>' . __( 'Configure general plugin settings.', 'schedspot' ) . '</p>';
    }

    public function booking_section_callback() {
        echo '<p>' . __( 'Configure booking-related settings.', 'schedspot' ) . '</p>';
    }

    public function payment_section_callback() {
        echo '<p>' . __( 'Configure payment and commission settings.', 'schedspot' ) . '</p>';
    }

    public function timezone_field_callback() {
        $value = get_option( 'schedspot_default_timezone', 'UTC' );
        echo '<select name="schedspot_default_timezone">';
        echo '<option value="UTC"' . selected( $value, 'UTC', false ) . '>UTC</option>';
        echo '<option value="America/New_York"' . selected( $value, 'America/New_York', false ) . '>Eastern Time</option>';
        echo '<option value="America/Chicago"' . selected( $value, 'America/Chicago', false ) . '>Central Time</option>';
        echo '<option value="America/Denver"' . selected( $value, 'America/Denver', false ) . '>Mountain Time</option>';
        echo '<option value="America/Los_Angeles"' . selected( $value, 'America/Los_Angeles', false ) . '>Pacific Time</option>';
        echo '</select>';
    }

    public function date_format_field_callback() {
        $value = get_option( 'schedspot_date_format', 'Y-m-d' );
        echo '<input type="text" name="schedspot_date_format" value="' . esc_attr( $value ) . '" />';
        echo '<p class="description">' . __( 'PHP date format. Default: Y-m-d', 'schedspot' ) . '</p>';
    }

    public function time_format_field_callback() {
        $value = get_option( 'schedspot_time_format', 'H:i' );
        echo '<input type="text" name="schedspot_time_format" value="' . esc_attr( $value ) . '" />';
        echo '<p class="description">' . __( 'PHP time format. Default: H:i', 'schedspot' ) . '</p>';
    }

    public function currency_field_callback() {
        $value = get_option( 'schedspot_currency', 'USD' );
        echo '<select name="schedspot_currency">';
        echo '<option value="USD"' . selected( $value, 'USD', false ) . '>USD ($)</option>';
        echo '<option value="EUR"' . selected( $value, 'EUR', false ) . '>EUR (€)</option>';
        echo '<option value="GBP"' . selected( $value, 'GBP', false ) . '>GBP (£)</option>';
        echo '</select>';
    }

    public function slot_length_field_callback() {
        $value = get_option( 'schedspot_default_slot_length', 60 );
        echo '<input type="number" name="schedspot_default_slot_length" value="' . esc_attr( $value ) . '" min="15" max="480" />';
        echo '<p class="description">' . __( 'Default booking slot length in minutes.', 'schedspot' ) . '</p>';
    }

    public function minimum_notice_field_callback() {
        $value = get_option( 'schedspot_minimum_notice', 24 );
        echo '<input type="number" name="schedspot_minimum_notice" value="' . esc_attr( $value ) . '" min="1" max="168" />';
        echo '<p class="description">' . __( 'Minimum notice required for bookings in hours.', 'schedspot' ) . '</p>';
    }

    public function auto_approve_field_callback() {
        $value = get_option( 'schedspot_auto_approve_bookings', 'no' );
        echo '<input type="checkbox" name="schedspot_auto_approve_bookings" value="yes"' . checked( $value, 'yes', false ) . ' />';
        echo '<label>' . __( 'Automatically approve new bookings', 'schedspot' ) . '</label>';
    }

    public function system_fee_field_callback() {
        $value = get_option( 'schedspot_system_fee_per_hour', 0 );
        echo '<input type="number" name="schedspot_system_fee_per_hour" value="' . esc_attr( $value ) . '" min="0" step="0.01" />';
        echo '<p class="description">' . __( 'Fixed fee added per hour of service.', 'schedspot' ) . '</p>';
    }

    public function commission_rate_field_callback() {
        $value = get_option( 'schedspot_commission_rate', 10 );
        echo '<input type="number" name="schedspot_commission_rate" value="' . esc_attr( $value ) . '" min="0" max="100" step="0.1" />';
        echo '<p class="description">' . __( 'Commission percentage taken from worker earnings.', 'schedspot' ) . '</p>';
    }

    public function payment_required_field_callback() {
        $value = get_option( 'schedspot_payment_required', 'deposit' );
        echo '<select name="schedspot_payment_required">';
        echo '<option value="none"' . selected( $value, 'none', false ) . '>' . __( 'No Payment Required', 'schedspot' ) . '</option>';
        echo '<option value="deposit"' . selected( $value, 'deposit', false ) . '>' . __( 'Deposit Required', 'schedspot' ) . '</option>';
        echo '<option value="full"' . selected( $value, 'full', false ) . '>' . __( 'Full Payment Required', 'schedspot' ) . '</option>';
        echo '</select>';
        echo '<p class="description">' . __( 'When payment is required from customers.', 'schedspot' ) . '</p>';
    }

    public function deposit_rate_field_callback() {
        $value = get_option( 'schedspot_deposit_rate', 30 );
        echo '<input type="number" name="schedspot_deposit_rate" value="' . esc_attr( $value ) . '" min="10" max="100" step="5" />';
        echo '<p class="description">' . __( 'Percentage of total cost required as deposit.', 'schedspot' ) . '</p>';
    }

    public function enable_payments_field_callback() {
        $value = get_option( 'schedspot_enable_payments', 'yes' );
        echo '<label>';
        echo '<input type="checkbox" name="schedspot_enable_payments" value="yes"' . checked( $value, 'yes', false ) . ' />';
        echo __( 'Enable payment processing through WooCommerce', 'schedspot' );
        echo '</label>';
        if ( ! class_exists( 'WooCommerce' ) ) {
            echo '<p class="description" style="color: #d63638;">' . __( 'WooCommerce is not installed. Please install and activate WooCommerce to enable payment processing.', 'schedspot' ) . '</p>';
        }
    }

    /**
     * Render services list.
     *
     * @since 1.0.0
     */
    private function render_services_list() {
        $services = SchedSpot_Service::get_services( array( 'limit' => 50 ) );

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Services', 'schedspot' ); ?></h1>
            <a href="<?php echo admin_url( 'admin.php?page=schedspot-services&action=add' ); ?>" class="page-title-action"><?php _e( 'Add New', 'schedspot' ); ?></a>
            <hr class="wp-header-end">

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Name', 'schedspot' ); ?></th>
                        <th><?php _e( 'Category', 'schedspot' ); ?></th>
                        <th><?php _e( 'Duration', 'schedspot' ); ?></th>
                        <th><?php _e( 'Price Type', 'schedspot' ); ?></th>
                        <th><?php _e( 'Base Price', 'schedspot' ); ?></th>
                        <th><?php _e( 'Status', 'schedspot' ); ?></th>
                        <th><?php _e( 'Actions', 'schedspot' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $services ) ) : ?>
                        <?php foreach ( $services as $service ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $service->name ); ?></strong></td>
                                <td><?php echo esc_html( $service->category ); ?></td>
                                <td><?php printf( __( '%d minutes', 'schedspot' ), $service->duration ); ?></td>
                                <td><?php echo esc_html( ucfirst( $service->price_type ) ); ?></td>
                                <td>$<?php echo esc_html( number_format( $service->base_price, 2 ) ); ?></td>
                                <td><?php echo $service->is_active ? __( 'Active', 'schedspot' ) : __( 'Inactive', 'schedspot' ); ?></td>
                                <td>
                                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-services&action=edit&service_id=' . $service->id ); ?>" class="button button-small"><?php _e( 'Edit', 'schedspot' ); ?></a>
                                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-services&action=delete&service_id=' . $service->id ); ?>" class="button button-small" onclick="return confirm('<?php _e( 'Are you sure you want to delete this service?', 'schedspot' ); ?>')"><?php _e( 'Delete', 'schedspot' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7"><?php _e( 'No services found.', 'schedspot' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render add service form.
     *
     * @since 1.0.0
     */
    private function render_add_service_form() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Add New Service', 'schedspot' ); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field( 'schedspot_service_form', 'schedspot_service_nonce' ); ?>
                <input type="hidden" name="schedspot_service_action" value="add">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="service_name"><?php _e( 'Service Name', 'schedspot' ); ?> <span class="description">(required)</span></label></th>
                        <td><input type="text" id="service_name" name="service_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_description"><?php _e( 'Description', 'schedspot' ); ?></label></th>
                        <td><textarea id="service_description" name="service_description" rows="4" cols="50"></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_category"><?php _e( 'Category', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="text" id="service_category" name="service_category" class="regular-text" list="service_categories">
                            <datalist id="service_categories">
                                <?php foreach ( SchedSpot_Service::get_categories() as $category ) : ?>
                                    <option value="<?php echo esc_attr( $category ); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_duration"><?php _e( 'Duration (minutes)', 'schedspot' ); ?> <span class="description">(required)</span></label></th>
                        <td><input type="number" id="service_duration" name="service_duration" min="15" max="480" value="60" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_price_type"><?php _e( 'Price Type', 'schedspot' ); ?> <span class="description">(required)</span></label></th>
                        <td>
                            <select id="service_price_type" name="service_price_type" required>
                                <option value="hourly"><?php _e( 'Hourly', 'schedspot' ); ?></option>
                                <option value="fixed"><?php _e( 'Fixed Price', 'schedspot' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_base_price"><?php _e( 'Base Price ($)', 'schedspot' ); ?> <span class="description">(required)</span></label></th>
                        <td><input type="number" id="service_base_price" name="service_base_price" min="0" step="0.01" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_is_active"><?php _e( 'Status', 'schedspot' ); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="service_is_active" name="service_is_active" value="1" checked>
                                <?php _e( 'Active', 'schedspot' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Add Service', 'schedspot' ) ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render edit service form.
     *
     * @since 1.0.0
     * @param int $service_id Service ID.
     */
    private function render_edit_service_form( $service_id ) {
        $service = new SchedSpot_Service( $service_id );

        if ( ! $service->id ) {
            wp_die( __( 'Service not found.', 'schedspot' ) );
        }

        ?>
        <div class="wrap">
            <h1><?php _e( 'Edit Service', 'schedspot' ); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field( 'schedspot_service_form', 'schedspot_service_nonce' ); ?>
                <input type="hidden" name="schedspot_service_action" value="edit">
                <input type="hidden" name="service_id" value="<?php echo esc_attr( $service->id ); ?>">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="service_name"><?php _e( 'Service Name', 'schedspot' ); ?> <span class="description">(required)</span></label></th>
                        <td><input type="text" id="service_name" name="service_name" class="regular-text" value="<?php echo esc_attr( $service->name ); ?>" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_description"><?php _e( 'Description', 'schedspot' ); ?></label></th>
                        <td><textarea id="service_description" name="service_description" rows="4" cols="50"><?php echo esc_textarea( $service->description ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_category"><?php _e( 'Category', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="text" id="service_category" name="service_category" class="regular-text" value="<?php echo esc_attr( $service->category ); ?>" list="service_categories">
                            <datalist id="service_categories">
                                <?php foreach ( SchedSpot_Service::get_categories() as $category ) : ?>
                                    <option value="<?php echo esc_attr( $category ); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_duration"><?php _e( 'Duration (minutes)', 'schedspot' ); ?> <span class="description">(required)</span></label></th>
                        <td><input type="number" id="service_duration" name="service_duration" min="15" max="480" value="<?php echo esc_attr( $service->duration ); ?>" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_price_type"><?php _e( 'Price Type', 'schedspot' ); ?> <span class="description">(required)</span></label></th>
                        <td>
                            <select id="service_price_type" name="service_price_type" required>
                                <option value="hourly" <?php selected( $service->price_type, 'hourly' ); ?>><?php _e( 'Hourly', 'schedspot' ); ?></option>
                                <option value="fixed" <?php selected( $service->price_type, 'fixed' ); ?>><?php _e( 'Fixed Price', 'schedspot' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_base_price"><?php _e( 'Base Price ($)', 'schedspot' ); ?> <span class="description">(required)</span></label></th>
                        <td><input type="number" id="service_base_price" name="service_base_price" min="0" step="0.01" value="<?php echo esc_attr( $service->base_price ); ?>" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_is_active"><?php _e( 'Status', 'schedspot' ); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="service_is_active" name="service_is_active" value="1" <?php checked( $service->is_active ); ?>>
                                <?php _e( 'Active', 'schedspot' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Update Service', 'schedspot' ) ); ?>
            </form>

            <h2><?php _e( 'Assigned Workers', 'schedspot' ); ?></h2>
            <?php $this->render_service_workers( $service ); ?>
        </div>
        <?php
    }

    /**
     * Render service workers section.
     *
     * @since 1.0.0
     * @param SchedSpot_Service $service Service object.
     */
    private function render_service_workers( $service ) {
        $workers = $service->get_workers();
        $all_workers = get_users( array( 'role' => 'schedspot_worker' ) );

        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e( 'Worker', 'schedspot' ); ?></th>
                    <th><?php _e( 'Custom Price', 'schedspot' ); ?></th>
                    <th><?php _e( 'Status', 'schedspot' ); ?></th>
                    <th><?php _e( 'Actions', 'schedspot' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $workers ) ) : ?>
                    <?php foreach ( $workers as $worker ) : ?>
                        <tr>
                            <td><?php echo esc_html( $worker['name'] ); ?></td>
                            <td>$<?php echo esc_html( number_format( $worker['custom_price'], 2 ) ); ?></td>
                            <td><?php echo $worker['is_enabled'] ? __( 'Enabled', 'schedspot' ) : __( 'Disabled', 'schedspot' ); ?></td>
                            <td>
                                <a href="#" class="button button-small" onclick="removeWorkerFromService(<?php echo $worker['id']; ?>, <?php echo $service->id; ?>)"><?php _e( 'Remove', 'schedspot' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4"><?php _e( 'No workers assigned to this service.', 'schedspot' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h3><?php _e( 'Assign Worker', 'schedspot' ); ?></h3>
        <form method="post" action="" style="margin-top: 20px;">
            <?php wp_nonce_field( 'schedspot_assign_worker', 'schedspot_assign_worker_nonce' ); ?>
            <input type="hidden" name="schedspot_service_action" value="assign_worker">
            <input type="hidden" name="service_id" value="<?php echo esc_attr( $service->id ); ?>">

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="worker_id"><?php _e( 'Worker', 'schedspot' ); ?></label></th>
                    <td>
                        <select id="worker_id" name="worker_id" required>
                            <option value=""><?php _e( 'Select a worker', 'schedspot' ); ?></option>
                            <?php foreach ( $all_workers as $worker ) : ?>
                                <option value="<?php echo esc_attr( $worker->ID ); ?>"><?php echo esc_html( $worker->display_name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="custom_price"><?php _e( 'Custom Price ($)', 'schedspot' ); ?></label></th>
                    <td>
                        <input type="number" id="custom_price" name="custom_price" min="0" step="0.01" value="<?php echo esc_attr( $service->base_price ); ?>">
                        <p class="description"><?php _e( 'Leave empty to use base price.', 'schedspot' ); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Assign Worker', 'schedspot' ), 'secondary' ); ?>
        </form>
        <?php
    }

    /**
     * Handle service form submission.
     *
     * @since 1.0.0
     */
    private function handle_service_form_submission() {
        if ( ! wp_verify_nonce( $_POST['schedspot_service_nonce'], 'schedspot_service_form' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $action = sanitize_text_field( $_POST['schedspot_service_action'] );

        $service_data = array(
            'name'        => sanitize_text_field( $_POST['service_name'] ),
            'description' => sanitize_textarea_field( $_POST['service_description'] ),
            'category'    => sanitize_text_field( $_POST['service_category'] ),
            'duration'    => absint( $_POST['service_duration'] ),
            'price_type'  => sanitize_text_field( $_POST['service_price_type'] ),
            'base_price'  => floatval( $_POST['service_base_price'] ),
            'is_active'   => isset( $_POST['service_is_active'] ) ? 1 : 0,
        );

        if ( $action === 'add' ) {
            $result = SchedSpot_Service::create_service( $service_data );

            if ( is_wp_error( $result ) ) {
                add_action( 'admin_notices', function() use ( $result ) {
                    echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
                } );
            } else {
                add_action( 'admin_notices', function() {
                    echo '<div class="notice notice-success"><p>' . __( 'Service created successfully.', 'schedspot' ) . '</p></div>';
                } );
                wp_redirect( admin_url( 'admin.php?page=schedspot-services' ) );
                exit;
            }
        } elseif ( $action === 'edit' ) {
            $service_id = absint( $_POST['service_id'] );
            $service = new SchedSpot_Service( $service_id );

            if ( $service->id ) {
                $result = $service->update( $service_data );

                if ( $result ) {
                    add_action( 'admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __( 'Service updated successfully.', 'schedspot' ) . '</p></div>';
                    } );
                } else {
                    add_action( 'admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __( 'Failed to update service.', 'schedspot' ) . '</p></div>';
                    } );
                }
            }
        } elseif ( $action === 'assign_worker' ) {
            if ( ! wp_verify_nonce( $_POST['schedspot_assign_worker_nonce'], 'schedspot_assign_worker' ) ) {
                wp_die( __( 'Security check failed.', 'schedspot' ) );
            }

            $service_id = absint( $_POST['service_id'] );
            $worker_id = absint( $_POST['worker_id'] );
            $custom_price = ! empty( $_POST['custom_price'] ) ? floatval( $_POST['custom_price'] ) : null;

            $service = new SchedSpot_Service( $service_id );
            if ( $service->id ) {
                $result = $service->assign_worker( $worker_id, $custom_price );

                if ( $result ) {
                    add_action( 'admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __( 'Worker assigned successfully.', 'schedspot' ) . '</p></div>';
                    } );
                } else {
                    add_action( 'admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __( 'Failed to assign worker.', 'schedspot' ) . '</p></div>';
                    } );
                }
            }
        }
    }

    /**
     * Handle delete service.
     *
     * @since 1.0.0
     * @param int $service_id Service ID.
     */
    private function handle_delete_service( $service_id ) {
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'delete_service_' . $service_id ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $service = new SchedSpot_Service( $service_id );

        if ( $service->id ) {
            $result = $service->delete();

            if ( is_wp_error( $result ) ) {
                add_action( 'admin_notices', function() use ( $result ) {
                    echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
                } );
            } else {
                add_action( 'admin_notices', function() {
                    echo '<div class="notice notice-success"><p>' . __( 'Service deleted successfully.', 'schedspot' ) . '</p></div>';
                } );
            }
        }

        wp_redirect( admin_url( 'admin.php?page=schedspot-services' ) );
        exit;
    }

    /**
     * Render add worker form.
     *
     * @since 1.0.0
     */
    private function render_add_worker_form() {
        $users = get_users( array(
            'meta_query' => array(
                array(
                    'key'     => 'wp_capabilities',
                    'value'   => 'schedspot_worker',
                    'compare' => 'NOT LIKE'
                )
            )
        ) );

        ?>
        <div class="wrap">
            <h1><?php _e( 'Add New Worker', 'schedspot' ); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field( 'schedspot_worker_form', 'schedspot_worker_nonce' ); ?>
                <input type="hidden" name="schedspot_worker_action" value="add">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="user_id"><?php _e( 'Select User', 'schedspot' ); ?> <span class="description">(required)</span></label></th>
                        <td>
                            <select id="user_id" name="user_id" required>
                                <option value=""><?php _e( 'Select a user', 'schedspot' ); ?></option>
                                <?php foreach ( $users as $user ) : ?>
                                    <option value="<?php echo esc_attr( $user->ID ); ?>">
                                        <?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Select an existing user to convert to a worker, or create a new user first.', 'schedspot' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bio"><?php _e( 'Bio', 'schedspot' ); ?></label></th>
                        <td><textarea id="bio" name="bio" rows="4" cols="50" placeholder="<?php _e( 'Tell us about your experience and skills...', 'schedspot' ); ?>"></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hourly_rate"><?php _e( 'Hourly Rate ($)', 'schedspot' ); ?></label></th>
                        <td><input type="number" id="hourly_rate" name="hourly_rate" min="0" step="0.01" placeholder="25.00"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="phone"><?php _e( 'Phone Number', 'schedspot' ); ?></label></th>
                        <td><input type="tel" id="phone" name="phone" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="experience_years"><?php _e( 'Years of Experience', 'schedspot' ); ?></label></th>
                        <td><input type="number" id="experience_years" name="experience_years" min="0" max="50"></td>
                    </tr>
                </table>

                <?php submit_button( __( 'Add Worker', 'schedspot' ) ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render edit worker form.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID.
     */
    private function render_edit_worker_form( $worker_id ) {
        $worker = new SchedSpot_Worker( $worker_id );

        if ( ! $worker->id ) {
            wp_die( __( 'Worker not found.', 'schedspot' ) );
        }

        ?>
        <div class="wrap">
            <h1><?php printf( __( 'Edit Worker: %s', 'schedspot' ), esc_html( $worker->user->display_name ) ); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field( 'schedspot_worker_form', 'schedspot_worker_nonce' ); ?>
                <input type="hidden" name="schedspot_worker_action" value="edit">
                <input type="hidden" name="worker_id" value="<?php echo esc_attr( $worker->id ); ?>">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="bio"><?php _e( 'Bio', 'schedspot' ); ?></label></th>
                        <td><textarea id="bio" name="bio" rows="4" cols="50"><?php echo esc_textarea( $worker->profile['bio'] ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="skills"><?php _e( 'Skills', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="text" id="skills" name="skills" class="regular-text" value="<?php echo esc_attr( is_array( $worker->profile['skills'] ) ? implode( ', ', $worker->profile['skills'] ) : $worker->profile['skills'] ); ?>">
                            <p class="description"><?php _e( 'Separate skills with commas.', 'schedspot' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hourly_rate"><?php _e( 'Hourly Rate ($)', 'schedspot' ); ?></label></th>
                        <td><input type="number" id="hourly_rate" name="hourly_rate" min="0" step="0.01" value="<?php echo esc_attr( $worker->profile['hourly_rate'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="phone"><?php _e( 'Phone Number', 'schedspot' ); ?></label></th>
                        <td><input type="tel" id="phone" name="phone" class="regular-text" value="<?php echo esc_attr( $worker->profile['phone'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="address"><?php _e( 'Address', 'schedspot' ); ?></label></th>
                        <td><textarea id="address" name="address" rows="3" cols="50"><?php echo esc_textarea( $worker->profile['address'] ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="experience_years"><?php _e( 'Years of Experience', 'schedspot' ); ?></label></th>
                        <td><input type="number" id="experience_years" name="experience_years" min="0" max="50" value="<?php echo esc_attr( $worker->profile['experience_years'] ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="certifications"><?php _e( 'Certifications', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="text" id="certifications" name="certifications" class="regular-text" value="<?php echo esc_attr( is_array( $worker->profile['certifications'] ) ? implode( ', ', $worker->profile['certifications'] ) : $worker->profile['certifications'] ); ?>">
                            <p class="description"><?php _e( 'Separate certifications with commas.', 'schedspot' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="languages"><?php _e( 'Languages', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="text" id="languages" name="languages" class="regular-text" value="<?php echo esc_attr( is_array( $worker->profile['languages'] ) ? implode( ', ', $worker->profile['languages'] ) : $worker->profile['languages'] ); ?>">
                            <p class="description"><?php _e( 'Separate languages with commas.', 'schedspot' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_areas"><?php _e( 'Service Areas', 'schedspot' ); ?></label></th>
                        <td>
                            <input type="text" id="service_areas" name="service_areas" class="regular-text" value="<?php echo esc_attr( is_array( $worker->profile['service_areas'] ) ? implode( ', ', $worker->profile['service_areas'] ) : $worker->profile['service_areas'] ); ?>">
                            <p class="description"><?php _e( 'Areas where this worker provides services. Separate with commas.', 'schedspot' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="availability_note"><?php _e( 'Availability Note', 'schedspot' ); ?></label></th>
                        <td><textarea id="availability_note" name="availability_note" rows="3" cols="50"><?php echo esc_textarea( $worker->profile['availability_note'] ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="is_available"><?php _e( 'Status', 'schedspot' ); ?></label></th>
                        <td>
                            <label>
                                <input type="checkbox" id="is_available" name="is_available" value="1" <?php checked( $worker->profile['is_available'] ); ?>>
                                <?php _e( 'Available for new bookings', 'schedspot' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Update Worker', 'schedspot' ) ); ?>
            </form>

            <h2><?php _e( 'Service Assignments', 'schedspot' ); ?></h2>
            <?php $this->render_worker_service_assignments( $worker ); ?>

            <h2><?php _e( 'Worker Statistics', 'schedspot' ); ?></h2>
            <?php $this->render_worker_statistics( $worker ); ?>
        </div>
        <?php
    }

    /**
     * Render worker profile view.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID.
     */
    private function render_worker_profile( $worker_id ) {
        $worker = new SchedSpot_Worker( $worker_id );

        if ( ! $worker->id ) {
            wp_die( __( 'Worker not found.', 'schedspot' ) );
        }

        $stats = $worker->get_statistics();
        $services = $worker->get_services();

        ?>
        <div class="wrap">
            <h1><?php printf( __( 'Worker Profile: %s', 'schedspot' ), esc_html( $worker->user->display_name ) ); ?></h1>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <div class="schedspot-widget">
                    <h3><?php _e( 'Profile Information', 'schedspot' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php _e( 'Name', 'schedspot' ); ?></th>
                            <td><?php echo esc_html( $worker->user->display_name ); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e( 'Email', 'schedspot' ); ?></th>
                            <td><?php echo esc_html( $worker->user->user_email ); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e( 'Phone', 'schedspot' ); ?></th>
                            <td><?php echo esc_html( $worker->profile['phone'] ); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e( 'Hourly Rate', 'schedspot' ); ?></th>
                            <td>$<?php echo esc_html( number_format( floatval( $worker->profile['hourly_rate'] ?? 0 ), 2 ) ); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e( 'Experience', 'schedspot' ); ?></th>
                            <td><?php printf( __( '%d years', 'schedspot' ), $worker->profile['experience_years'] ); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e( 'Profile Completion', 'schedspot' ); ?></th>
                            <td><?php echo esc_html( $stats['profile_completion'] ); ?>%</td>
                        </tr>
                    </table>
                </div>

                <div class="schedspot-widget">
                    <h3><?php _e( 'Statistics', 'schedspot' ); ?></h3>
                    <div class="schedspot-stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo esc_html( $stats['total_bookings'] ); ?></div>
                            <div class="stat-label"><?php _e( 'Total Bookings', 'schedspot' ); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">$<?php echo esc_html( number_format( floatval( $stats['total_earnings'] ?? 0 ), 2 ) ); ?></div>
                            <div class="stat-label"><?php _e( 'Total Earnings', 'schedspot' ); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo esc_html( floatval( $stats['completion_rate'] ?? 0 ) ); ?>%</div>
                            <div class="stat-label"><?php _e( 'Completion Rate', 'schedspot' ); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo esc_html( number_format( floatval( $stats['rating'] ?? 0 ), 1 ) ); ?></div>
                            <div class="stat-label"><?php _e( 'Rating', 'schedspot' ); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ( ! empty( $worker->profile['bio'] ) ) : ?>
                <div class="schedspot-widget" style="margin-top: 20px;">
                    <h3><?php _e( 'Bio', 'schedspot' ); ?></h3>
                    <p><?php echo esc_html( $worker->profile['bio'] ); ?></p>
                </div>
            <?php endif; ?>

            <div class="schedspot-widget" style="margin-top: 20px;">
                <h3><?php _e( 'Services', 'schedspot' ); ?></h3>
                <?php if ( ! empty( $services ) ) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e( 'Service', 'schedspot' ); ?></th>
                                <th><?php _e( 'Category', 'schedspot' ); ?></th>
                                <th><?php _e( 'Duration', 'schedspot' ); ?></th>
                                <th><?php _e( 'Price', 'schedspot' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $services as $service ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $service['name'] ); ?></td>
                                    <td><?php echo esc_html( $service['category'] ); ?></td>
                                    <td><?php printf( __( '%d minutes', 'schedspot' ), $service['duration'] ); ?></td>
                                    <td>$<?php echo esc_html( number_format( floatval( $service['custom_price'] ?? 0 ), 2 ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p><?php _e( 'No services assigned to this worker.', 'schedspot' ); ?></p>
                <?php endif; ?>
            </div>

            <div style="margin-top: 20px;">
                <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=edit&worker_id=' . $worker->id ); ?>" class="button button-primary"><?php _e( 'Edit Profile', 'schedspot' ); ?></a>
                <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=availability&worker_id=' . $worker->id ); ?>" class="button"><?php _e( 'Manage Availability', 'schedspot' ); ?></a>
                <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers' ); ?>" class="button"><?php _e( 'Back to Workers', 'schedspot' ); ?></a>
            </div>
        </div>
        <?php
    }

    /**
     * Render worker availability management.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID.
     */
    private function render_worker_availability( $worker_id ) {
        $worker = new SchedSpot_Worker( $worker_id );

        if ( ! $worker->id ) {
            wp_die( __( 'Worker not found.', 'schedspot' ) );
        }

        $availability = $worker->get_availability();
        $days = array(
            1 => __( 'Monday', 'schedspot' ),
            2 => __( 'Tuesday', 'schedspot' ),
            3 => __( 'Wednesday', 'schedspot' ),
            4 => __( 'Thursday', 'schedspot' ),
            5 => __( 'Friday', 'schedspot' ),
            6 => __( 'Saturday', 'schedspot' ),
            0 => __( 'Sunday', 'schedspot' ),
        );

        ?>
        <div class="wrap">
            <h1><?php printf( __( 'Availability: %s', 'schedspot' ), esc_html( $worker->user->display_name ) ); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field( 'schedspot_availability_form', 'schedspot_availability_nonce' ); ?>
                <input type="hidden" name="schedspot_worker_action" value="update_availability">
                <input type="hidden" name="worker_id" value="<?php echo esc_attr( $worker->id ); ?>">

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e( 'Day', 'schedspot' ); ?></th>
                            <th><?php _e( 'Available', 'schedspot' ); ?></th>
                            <th><?php _e( 'Start Time', 'schedspot' ); ?></th>
                            <th><?php _e( 'End Time', 'schedspot' ); ?></th>
                            <th><?php _e( 'Actions', 'schedspot' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="availability-schedule">
                        <?php foreach ( $days as $day_num => $day_name ) : ?>
                            <?php
                            $day_slots = array_filter( $availability, function( $slot ) use ( $day_num ) {
                                return $slot['day_of_week'] == $day_num;
                            } );

                            if ( empty( $day_slots ) ) {
                                $day_slots = array( array(
                                    'id' => 0,
                                    'day_of_week' => $day_num,
                                    'start_time' => '09:00:00',
                                    'end_time' => '17:00:00',
                                    'is_available' => false
                                ) );
                            }
                            ?>

                            <?php foreach ( $day_slots as $index => $slot ) : ?>
                                <tr data-day="<?php echo esc_attr( $day_num ); ?>">
                                    <td>
                                        <?php if ( $index === 0 ) echo esc_html( $day_name ); ?>
                                        <input type="hidden" name="availability[<?php echo esc_attr( $day_num ); ?>][<?php echo esc_attr( $index ); ?>][day_of_week]" value="<?php echo esc_attr( $day_num ); ?>">
                                    </td>
                                    <td>
                                        <input type="checkbox" name="availability[<?php echo esc_attr( $day_num ); ?>][<?php echo esc_attr( $index ); ?>][is_available]" value="1" <?php checked( $slot['is_available'] ); ?>>
                                    </td>
                                    <td>
                                        <input type="time" name="availability[<?php echo esc_attr( $day_num ); ?>][<?php echo esc_attr( $index ); ?>][start_time]" value="<?php echo esc_attr( substr( $slot['start_time'], 0, 5 ) ); ?>">
                                    </td>
                                    <td>
                                        <input type="time" name="availability[<?php echo esc_attr( $day_num ); ?>][<?php echo esc_attr( $index ); ?>][end_time]" value="<?php echo esc_attr( substr( $slot['end_time'], 0, 5 ) ); ?>">
                                    </td>
                                    <td>
                                        <?php if ( count( $day_slots ) > 1 ) : ?>
                                            <button type="button" class="button button-small remove-slot"><?php _e( 'Remove', 'schedspot' ); ?></button>
                                        <?php endif; ?>
                                        <?php if ( $index === count( $day_slots ) - 1 ) : ?>
                                            <button type="button" class="button button-small add-slot" data-day="<?php echo esc_attr( $day_num ); ?>"><?php _e( 'Add Slot', 'schedspot' ); ?></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="description"><?php _e( 'Set the worker\'s regular weekly availability. You can add multiple time slots per day.', 'schedspot' ); ?></p>

                <?php submit_button( __( 'Update Availability', 'schedspot' ) ); ?>
            </form>

            <div style="margin-top: 20px;">
                <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers&action=view&worker_id=' . $worker->id ); ?>" class="button"><?php _e( 'View Profile', 'schedspot' ); ?></a>
                <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers' ); ?>" class="button"><?php _e( 'Back to Workers', 'schedspot' ); ?></a>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Add new time slot
            $('.add-slot').on('click', function() {
                var day = $(this).data('day');
                var row = $(this).closest('tr');
                var index = $('tr[data-day="' + day + '"]').length;

                var newRow = row.clone();
                newRow.find('td:first').html('<input type="hidden" name="availability[' + day + '][' + index + '][day_of_week]" value="' + day + '">');
                newRow.find('input[type="checkbox"]').attr('name', 'availability[' + day + '][' + index + '][is_available]').prop('checked', false);
                newRow.find('input[type="time"]:first').attr('name', 'availability[' + day + '][' + index + '][start_time]').val('09:00');
                newRow.find('input[type="time"]:last').attr('name', 'availability[' + day + '][' + index + '][end_time]').val('17:00');
                newRow.find('.add-slot').remove();

                row.after(newRow);
                row.find('.add-slot').remove();
                newRow.find('td:last').append('<button type="button" class="button button-small add-slot" data-day="' + day + '"><?php _e( 'Add Slot', 'schedspot' ); ?></button>');
            });

            // Remove time slot
            $(document).on('click', '.remove-slot', function() {
                $(this).closest('tr').remove();
            });
        });
        </script>
        <?php
    }

    /**
     * Render worker service assignments.
     *
     * @since 1.0.0
     * @param SchedSpot_Worker $worker Worker object.
     */
    private function render_worker_service_assignments( $worker ) {
        $assigned_services = $worker->get_services();
        $all_services = SchedSpot_Service::get_services();
        $available_services = array();

        // Filter out already assigned services
        foreach ( $all_services as $service ) {
            $is_assigned = false;
            foreach ( $assigned_services as $assigned ) {
                if ( $assigned['id'] == $service->id ) {
                    $is_assigned = true;
                    break;
                }
            }
            if ( ! $is_assigned ) {
                $available_services[] = $service;
            }
        }

        ?>
        <div class="schedspot-service-assignments">
            <h3><?php _e( 'Currently Assigned Services', 'schedspot' ); ?></h3>
            <?php if ( ! empty( $assigned_services ) ) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e( 'Service', 'schedspot' ); ?></th>
                            <th><?php _e( 'Category', 'schedspot' ); ?></th>
                            <th><?php _e( 'Base Price', 'schedspot' ); ?></th>
                            <th><?php _e( 'Custom Price', 'schedspot' ); ?></th>
                            <th><?php _e( 'Actions', 'schedspot' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $assigned_services as $service ) : ?>
                            <tr>
                                <td><?php echo esc_html( $service['name'] ); ?></td>
                                <td><?php echo esc_html( $service['category'] ); ?></td>
                                <td>$<?php echo esc_html( number_format( $service['base_price'], 2 ) ); ?></td>
                                <td>
                                    <form method="post" action="" style="display: inline;">
                                        <?php wp_nonce_field( 'schedspot_update_service_price', 'schedspot_service_price_nonce' ); ?>
                                        <input type="hidden" name="schedspot_worker_action" value="update_service_price">
                                        <input type="hidden" name="worker_id" value="<?php echo esc_attr( $worker->id ); ?>">
                                        <input type="hidden" name="service_id" value="<?php echo esc_attr( $service['id'] ); ?>">
                                        <input type="number" name="custom_price" value="<?php echo esc_attr( $service['custom_price'] ); ?>" step="0.01" min="0" style="width: 80px;">
                                        <button type="submit" class="button button-small"><?php _e( 'Update', 'schedspot' ); ?></button>
                                    </form>
                                </td>
                                <td>
                                    <form method="post" action="" style="display: inline;">
                                        <?php wp_nonce_field( 'schedspot_remove_service', 'schedspot_remove_service_nonce' ); ?>
                                        <input type="hidden" name="schedspot_worker_action" value="remove_service">
                                        <input type="hidden" name="worker_id" value="<?php echo esc_attr( $worker->id ); ?>">
                                        <input type="hidden" name="service_id" value="<?php echo esc_attr( $service['id'] ); ?>">
                                        <button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to remove this service assignment?', 'schedspot' ); ?>')"><?php _e( 'Remove', 'schedspot' ); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e( 'No services assigned to this worker.', 'schedspot' ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $available_services ) ) : ?>
                <h3><?php _e( 'Assign New Service', 'schedspot' ); ?></h3>
                <form method="post" action="">
                    <?php wp_nonce_field( 'schedspot_assign_service', 'schedspot_assign_service_nonce' ); ?>
                    <input type="hidden" name="schedspot_worker_action" value="assign_service">
                    <input type="hidden" name="worker_id" value="<?php echo esc_attr( $worker->id ); ?>">

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="service_id"><?php _e( 'Service', 'schedspot' ); ?></label></th>
                            <td>
                                <select id="service_id" name="service_id" required>
                                    <option value=""><?php _e( 'Select a service', 'schedspot' ); ?></option>
                                    <?php foreach ( $available_services as $service ) : ?>
                                        <option value="<?php echo esc_attr( $service->id ); ?>">
                                            <?php echo esc_html( $service->name . ' - $' . number_format( $service->base_price, 2 ) ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="custom_price"><?php _e( 'Custom Price', 'schedspot' ); ?></label></th>
                            <td>
                                <input type="number" id="custom_price" name="custom_price" step="0.01" min="0" placeholder="<?php esc_attr_e( 'Leave empty to use base price', 'schedspot' ); ?>">
                                <p class="description"><?php _e( 'Set a custom price for this worker, or leave empty to use the service base price.', 'schedspot' ); ?></p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button( __( 'Assign Service', 'schedspot' ), 'secondary' ); ?>
                </form>
            <?php else : ?>
                <p><em><?php _e( 'All available services are already assigned to this worker.', 'schedspot' ); ?></em></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render worker statistics.
     *
     * @since 1.0.0
     * @param SchedSpot_Worker $worker Worker object.
     */
    private function render_worker_statistics( $worker ) {
        $stats = $worker->get_statistics();

        ?>
        <div class="schedspot-stats-grid" style="grid-template-columns: repeat(4, 1fr);">
            <div class="stat-item">
                <div class="stat-number"><?php echo esc_html( $stats['total_bookings'] ); ?></div>
                <div class="stat-label"><?php _e( 'Total Bookings', 'schedspot' ); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-number">$<?php echo esc_html( number_format( floatval( $stats['total_earnings'] ?? 0 ), 2 ) ); ?></div>
                <div class="stat-label"><?php _e( 'Total Earnings', 'schedspot' ); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo esc_html( intval( $stats['month_bookings'] ?? 0 ) ); ?></div>
                <div class="stat-label"><?php _e( 'This Month', 'schedspot' ); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-number">$<?php echo esc_html( number_format( floatval( $stats['month_earnings'] ?? 0 ), 2 ) ); ?></div>
                <div class="stat-label"><?php _e( 'Month Earnings', 'schedspot' ); ?></div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle worker form submission.
     *
     * @since 1.0.0
     */
    private function handle_worker_form_submission() {
        $worker_nonce = $_POST['schedspot_worker_nonce'] ?? '';
        $availability_nonce = $_POST['schedspot_availability_nonce'] ?? '';

        if ( ! wp_verify_nonce( $worker_nonce, 'schedspot_worker_form' ) &&
             ! wp_verify_nonce( $availability_nonce, 'schedspot_availability_form' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $action = sanitize_text_field( $_POST['schedspot_worker_action'] );

        if ( $action === 'add' ) {
            $user_id = absint( $_POST['user_id'] );
            $profile_data = array(
                'bio'              => sanitize_textarea_field( $_POST['bio'] ),
                'hourly_rate'      => floatval( $_POST['hourly_rate'] ),
                'phone'            => sanitize_text_field( $_POST['phone'] ),
                'experience_years' => absint( $_POST['experience_years'] ),
            );

            $result = SchedSpot_Worker::create_worker( $user_id, $profile_data );

            if ( is_wp_error( $result ) ) {
                add_action( 'admin_notices', function() use ( $result ) {
                    echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
                } );
            } else {
                add_action( 'admin_notices', function() {
                    echo '<div class="notice notice-success"><p>' . __( 'Worker created successfully.', 'schedspot' ) . '</p></div>';
                } );
                wp_redirect( admin_url( 'admin.php?page=schedspot-workers' ) );
                exit;
            }
        } elseif ( $action === 'edit' ) {
            $worker_id = absint( $_POST['worker_id'] );
            $worker = new SchedSpot_Worker( $worker_id );

            if ( $worker->id ) {
                $profile_data = array(
                    'bio'               => sanitize_textarea_field( $_POST['bio'] ),
                    'skills'            => sanitize_text_field( $_POST['skills'] ),
                    'hourly_rate'       => floatval( $_POST['hourly_rate'] ),
                    'phone'             => sanitize_text_field( $_POST['phone'] ),
                    'address'           => sanitize_textarea_field( $_POST['address'] ),
                    'experience_years'  => absint( $_POST['experience_years'] ),
                    'certifications'    => sanitize_text_field( $_POST['certifications'] ),
                    'languages'         => sanitize_text_field( $_POST['languages'] ),
                    'service_areas'     => sanitize_text_field( $_POST['service_areas'] ),
                    'availability_note' => sanitize_textarea_field( $_POST['availability_note'] ),
                    'is_available'      => isset( $_POST['is_available'] ) ? 1 : 0,
                );

                $result = $worker->update_profile( $profile_data );

                if ( $result ) {
                    add_action( 'admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __( 'Worker updated successfully.', 'schedspot' ) . '</p></div>';
                    } );
                } else {
                    add_action( 'admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __( 'Failed to update worker.', 'schedspot' ) . '</p></div>';
                    } );
                }
            }
        } elseif ( $action === 'update_availability' ) {
            $worker_id = absint( $_POST['worker_id'] );
            $availability_data = $_POST['availability'];

            global $wpdb;

            // Clear existing availability
            $wpdb->delete(
                $wpdb->prefix . 'schedspot_worker_availability',
                array( 'worker_id' => $worker_id ),
                array( '%d' )
            );

            // Insert new availability
            foreach ( $availability_data as $day => $slots ) {
                foreach ( $slots as $slot ) {
                    if ( ! empty( $slot['is_available'] ) ) {
                        $wpdb->insert(
                            $wpdb->prefix . 'schedspot_worker_availability',
                            array(
                                'worker_id'    => $worker_id,
                                'day_of_week'  => absint( $slot['day_of_week'] ),
                                'start_time'   => sanitize_text_field( $slot['start_time'] ) . ':00',
                                'end_time'     => sanitize_text_field( $slot['end_time'] ) . ':00',
                                'is_available' => 1,
                            ),
                            array( '%d', '%d', '%s', '%s', '%d' )
                        );
                    }
                }
            }

            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . __( 'Availability updated successfully.', 'schedspot' ) . '</p></div>';
            } );
        } elseif ( $action === 'assign_service' ) {
            if ( ! wp_verify_nonce( $_POST['schedspot_assign_service_nonce'], 'schedspot_assign_service' ) ) {
                wp_die( __( 'Security check failed.', 'schedspot' ) );
            }

            $worker_id = absint( $_POST['worker_id'] );
            $service_id = absint( $_POST['service_id'] );
            $custom_price = ! empty( $_POST['custom_price'] ) ? floatval( $_POST['custom_price'] ) : null;

            $service = new SchedSpot_Service( $service_id );
            if ( $service->id ) {
                $result = $service->assign_worker( $worker_id, $custom_price );

                if ( $result ) {
                    add_action( 'admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __( 'Service assigned successfully.', 'schedspot' ) . '</p></div>';
                    } );
                } else {
                    add_action( 'admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __( 'Failed to assign service.', 'schedspot' ) . '</p></div>';
                    } );
                }
            }
        } elseif ( $action === 'remove_service' ) {
            if ( ! wp_verify_nonce( $_POST['schedspot_remove_service_nonce'], 'schedspot_remove_service' ) ) {
                wp_die( __( 'Security check failed.', 'schedspot' ) );
            }

            $worker_id = absint( $_POST['worker_id'] );
            $service_id = absint( $_POST['service_id'] );

            global $wpdb;
            $result = $wpdb->delete(
                $wpdb->prefix . 'schedspot_worker_services',
                array(
                    'worker_id' => $worker_id,
                    'service_id' => $service_id
                ),
                array( '%d', '%d' )
            );

            if ( $result !== false ) {
                add_action( 'admin_notices', function() {
                    echo '<div class="notice notice-success"><p>' . __( 'Service removed successfully.', 'schedspot' ) . '</p></div>';
                } );
            } else {
                add_action( 'admin_notices', function() {
                    echo '<div class="notice notice-error"><p>' . __( 'Failed to remove service.', 'schedspot' ) . '</p></div>';
                } );
            }
        } elseif ( $action === 'update_service_price' ) {
            if ( ! wp_verify_nonce( $_POST['schedspot_service_price_nonce'], 'schedspot_update_service_price' ) ) {
                wp_die( __( 'Security check failed.', 'schedspot' ) );
            }

            $worker_id = absint( $_POST['worker_id'] );
            $service_id = absint( $_POST['service_id'] );
            $custom_price = floatval( $_POST['custom_price'] );

            global $wpdb;
            $result = $wpdb->update(
                $wpdb->prefix . 'schedspot_worker_services',
                array( 'custom_price' => $custom_price ),
                array(
                    'worker_id' => $worker_id,
                    'service_id' => $service_id
                ),
                array( '%f' ),
                array( '%d', '%d' )
            );

            if ( $result !== false ) {
                add_action( 'admin_notices', function() {
                    echo '<div class="notice notice-success"><p>' . __( 'Service price updated successfully.', 'schedspot' ) . '</p></div>';
                } );
            } else {
                add_action( 'admin_notices', function() {
                    echo '<div class="notice notice-error"><p>' . __( 'Failed to update service price.', 'schedspot' ) . '</p></div>';
                } );
            }
        }
    }

    /**
     * Get admin styles.
     *
     * @since 0.1.0
     * @return string CSS styles.
     */
    private function get_admin_styles() {
        return '
        .schedspot-dashboard-widgets {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .schedspot-widget {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        .schedspot-widget h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #23282d;
        }
        .schedspot-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .schedspot-quick-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .schedspot-placeholder {
            text-align: center;
            padding: 40px;
            background: #f9f9f9;
            border-radius: 4px;
            margin-top: 20px;
        }
        ';
    }

    /**
     * Render Google Calendar settings.
     *
     * @since 1.0.0
     */
    private function render_calendar_settings() {
        $gcal = new SchedSpot_GCal();
        $is_enabled = get_option( 'schedspot_gcal_enabled', false );
        $is_connected = $gcal->is_connected();
        $client_id = get_option( 'schedspot_gcal_client_id', '' );
        $client_secret = get_option( 'schedspot_gcal_client_secret', '' );
        $calendar_id = get_option( 'schedspot_gcal_calendar_id', 'primary' );
        $calendars = get_option( 'schedspot_gcal_calendars', array() );

        // Handle form submission
        if ( isset( $_POST['schedspot_gcal_save'] ) && wp_verify_nonce( $_POST['schedspot_gcal_nonce'], 'schedspot_gcal_settings' ) ) {
            update_option( 'schedspot_gcal_enabled', isset( $_POST['schedspot_gcal_enabled'] ) );
            update_option( 'schedspot_gcal_client_id', sanitize_text_field( $_POST['schedspot_gcal_client_id'] ?? '' ) );
            update_option( 'schedspot_gcal_client_secret', sanitize_text_field( $_POST['schedspot_gcal_client_secret'] ?? '' ) );
            update_option( 'schedspot_gcal_calendar_id', sanitize_text_field( $_POST['schedspot_gcal_calendar_id'] ?? 'primary' ) );

            echo '<div class="notice notice-success"><p>' . __( 'Google Calendar settings saved.', 'schedspot' ) . '</p></div>';

            // Refresh values
            $is_enabled = get_option( 'schedspot_gcal_enabled', false );
            $client_id = get_option( 'schedspot_gcal_client_id', '' );
            $client_secret = get_option( 'schedspot_gcal_client_secret', '' );
            $calendar_id = get_option( 'schedspot_gcal_calendar_id', 'primary' );
        }

        // Display connection status messages
        if ( isset( $_GET['connected'] ) ) {
            echo '<div class="notice notice-success"><p>' . __( 'Google Calendar connected successfully!', 'schedspot' ) . '</p></div>';
        } elseif ( isset( $_GET['error'] ) ) {
            echo '<div class="notice notice-error"><p>' . __( 'Failed to connect to Google Calendar. Please check your credentials.', 'schedspot' ) . '</p></div>';
        }

        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'schedspot_gcal_settings', 'schedspot_gcal_nonce' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e( 'Enable Google Calendar Sync', 'schedspot' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="schedspot_gcal_enabled" value="1" <?php checked( $is_enabled ); ?> />
                            <?php _e( 'Enable two-way synchronization with Google Calendar', 'schedspot' ); ?>
                        </label>
                        <p class="description"><?php _e( 'When enabled, confirmed bookings will be automatically synced to Google Calendar.', 'schedspot' ); ?></p>
                    </td>
                </tr>

                <?php if ( $is_enabled ) : ?>
                <tr>
                    <th scope="row"><?php _e( 'Google API Client ID', 'schedspot' ); ?></th>
                    <td>
                        <input type="text" name="schedspot_gcal_client_id" value="<?php echo esc_attr( $client_id ); ?>" class="regular-text" />
                        <p class="description">
                            <?php printf(
                                __( 'Get your Client ID from the <a href="%s" target="_blank">Google Cloud Console</a>.', 'schedspot' ),
                                'https://console.cloud.google.com/apis/credentials'
                            ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e( 'Google API Client Secret', 'schedspot' ); ?></th>
                    <td>
                        <input type="password" name="schedspot_gcal_client_secret" value="<?php echo esc_attr( $client_secret ); ?>" class="regular-text" />
                        <p class="description"><?php _e( 'Enter your Client Secret from Google Cloud Console.', 'schedspot' ); ?></p>
                    </td>
                </tr>

                <?php if ( ! empty( $client_id ) && ! empty( $client_secret ) ) : ?>
                <tr>
                    <th scope="row"><?php _e( 'Connection Status', 'schedspot' ); ?></th>
                    <td>
                        <?php if ( $is_connected ) : ?>
                            <span style="color: green;">●</span> <?php _e( 'Connected to Google Calendar', 'schedspot' ); ?>
                            <br><br>
                            <button type="button" class="button" onclick="disconnectGoogleCalendar()"><?php _e( 'Disconnect', 'schedspot' ); ?></button>
                            <button type="button" class="button" onclick="syncAllBookings()"><?php _e( 'Sync All Bookings', 'schedspot' ); ?></button>
                        <?php else : ?>
                            <span style="color: red;">●</span> <?php _e( 'Not connected', 'schedspot' ); ?>
                            <br><br>
                            <a href="<?php echo esc_url( $gcal->get_auth_url() ); ?>" class="button button-primary"><?php _e( 'Connect to Google Calendar', 'schedspot' ); ?></a>
                        <?php endif; ?>
                    </td>
                </tr>

                <?php if ( $is_connected && ! empty( $calendars ) ) : ?>
                <tr>
                    <th scope="row"><?php _e( 'Target Calendar', 'schedspot' ); ?></th>
                    <td>
                        <select name="schedspot_gcal_calendar_id">
                            <option value="primary" <?php selected( $calendar_id, 'primary' ); ?>><?php _e( 'Primary Calendar', 'schedspot' ); ?></option>
                            <?php foreach ( $calendars as $calendar ) : ?>
                                <option value="<?php echo esc_attr( $calendar['id'] ); ?>" <?php selected( $calendar_id, $calendar['id'] ); ?>>
                                    <?php echo esc_html( $calendar['summary'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e( 'Select which calendar to sync bookings to.', 'schedspot' ); ?></p>
                    </td>
                </tr>
                <?php endif; ?>

                <?php endif; ?>
                <?php endif; ?>
            </table>

            <?php submit_button( __( 'Save Calendar Settings', 'schedspot' ), 'primary', 'schedspot_gcal_save' ); ?>
        </form>

        <script>
        function disconnectGoogleCalendar() {
            if (confirm('<?php _e( 'Are you sure you want to disconnect Google Calendar?', 'schedspot' ); ?>')) {
                jQuery.post(ajaxurl, {
                    action: 'schedspot_gcal_disconnect',
                    nonce: '<?php echo wp_create_nonce( 'schedspot_gcal_disconnect' ); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('<?php _e( 'Failed to disconnect. Please try again.', 'schedspot' ); ?>');
                    }
                });
            }
        }

        function syncAllBookings() {
            if (confirm('<?php _e( 'This will sync all confirmed bookings to Google Calendar. Continue?', 'schedspot' ); ?>')) {
                jQuery.post(ajaxurl, {
                    action: 'schedspot_gcal_sync_all',
                    nonce: '<?php echo wp_create_nonce( 'schedspot_gcal_sync_all' ); ?>'
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert(response.data.message || '<?php _e( 'Sync failed. Please try again.', 'schedspot' ); ?>');
                    }
                });
            }
        }
        </script>
        <?php
    }

    /**
     * Render SMS settings.
     *
     * @since 2.0.0
     */
    private function render_sms_settings() {
        $sms = new SchedSpot_SMS();
        $is_enabled = get_option( 'schedspot_sms_enabled', false );
        $provider = get_option( 'schedspot_sms_provider', 'twilio' );
        $twilio_sid = get_option( 'schedspot_twilio_account_sid', '' );
        $twilio_token = get_option( 'schedspot_twilio_auth_token', '' );
        $twilio_number = get_option( 'schedspot_twilio_from_number', '' );
        $country_code = get_option( 'schedspot_sms_default_country_code', '+1' );
        $login_required = get_option( 'schedspot_sms_login_required', false );
        $login_notifications = get_option( 'schedspot_sms_login_notifications', false );

        // Handle form submission
        if ( isset( $_POST['schedspot_sms_save'] ) && wp_verify_nonce( $_POST['schedspot_sms_nonce'], 'schedspot_sms_settings' ) ) {
            update_option( 'schedspot_sms_enabled', isset( $_POST['schedspot_sms_enabled'] ) );
            update_option( 'schedspot_sms_provider', sanitize_text_field( $_POST['schedspot_sms_provider'] ?? 'twilio' ) );
            update_option( 'schedspot_twilio_account_sid', sanitize_text_field( $_POST['schedspot_twilio_account_sid'] ?? '' ) );
            update_option( 'schedspot_twilio_auth_token', sanitize_text_field( $_POST['schedspot_twilio_auth_token'] ?? '' ) );
            update_option( 'schedspot_twilio_from_number', sanitize_text_field( $_POST['schedspot_twilio_from_number'] ?? '' ) );
            update_option( 'schedspot_sms_default_country_code', sanitize_text_field( $_POST['schedspot_sms_default_country_code'] ?? '+1' ) );
            update_option( 'schedspot_sms_login_required', isset( $_POST['schedspot_sms_login_required'] ) );
            update_option( 'schedspot_sms_login_notifications', isset( $_POST['schedspot_sms_login_notifications'] ) );

            echo '<div class="notice notice-success"><p>' . __( 'SMS settings saved.', 'schedspot' ) . '</p></div>';

            // Refresh values
            $is_enabled = get_option( 'schedspot_sms_enabled', false );
            $provider = get_option( 'schedspot_sms_provider', 'twilio' );
            $twilio_sid = get_option( 'schedspot_twilio_account_sid', '' );
            $twilio_token = get_option( 'schedspot_twilio_auth_token', '' );
            $twilio_number = get_option( 'schedspot_twilio_from_number', '' );
            $country_code = get_option( 'schedspot_sms_default_country_code', '+1' );
            $login_required = get_option( 'schedspot_sms_login_required', false );
            $login_notifications = get_option( 'schedspot_sms_login_notifications', false );
        }

        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'schedspot_sms_settings', 'schedspot_sms_nonce' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e( 'Enable SMS Integration', 'schedspot' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="schedspot_sms_enabled" value="1" <?php checked( $is_enabled ); ?> />
                            <?php _e( 'Enable SMS notifications and authentication', 'schedspot' ); ?>
                        </label>
                        <p class="description"><?php _e( 'When enabled, SMS notifications will be sent for booking updates and users can authenticate via SMS.', 'schedspot' ); ?></p>
                    </td>
                </tr>

                <?php if ( $is_enabled ) : ?>
                <tr>
                    <th scope="row"><?php _e( 'SMS Provider', 'schedspot' ); ?></th>
                    <td>
                        <select name="schedspot_sms_provider">
                            <option value="twilio" <?php selected( $provider, 'twilio' ); ?>><?php _e( 'Twilio', 'schedspot' ); ?></option>
                        </select>
                        <p class="description"><?php _e( 'Choose your SMS service provider.', 'schedspot' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e( 'Default Country Code', 'schedspot' ); ?></th>
                    <td>
                        <input type="text" name="schedspot_sms_default_country_code" value="<?php echo esc_attr( $country_code ); ?>" class="small-text" />
                        <p class="description"><?php _e( 'Default country code for phone numbers (e.g., +1 for US/Canada).', 'schedspot' ); ?></p>
                    </td>
                </tr>

                <?php if ( $provider === 'twilio' ) : ?>
                <tr>
                    <th scope="row"><?php _e( 'Twilio Account SID', 'schedspot' ); ?></th>
                    <td>
                        <input type="text" name="schedspot_twilio_account_sid" value="<?php echo esc_attr( $twilio_sid ); ?>" class="regular-text" />
                        <p class="description">
                            <?php printf(
                                __( 'Get your Account SID from the <a href="%s" target="_blank">Twilio Console</a>.', 'schedspot' ),
                                'https://console.twilio.com/'
                            ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e( 'Twilio Auth Token', 'schedspot' ); ?></th>
                    <td>
                        <input type="password" name="schedspot_twilio_auth_token" value="<?php echo esc_attr( $twilio_token ); ?>" class="regular-text" />
                        <p class="description"><?php _e( 'Enter your Auth Token from Twilio Console.', 'schedspot' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e( 'Twilio Phone Number', 'schedspot' ); ?></th>
                    <td>
                        <input type="text" name="schedspot_twilio_from_number" value="<?php echo esc_attr( $twilio_number ); ?>" class="regular-text" />
                        <p class="description"><?php _e( 'Your Twilio phone number (e.g., +1234567890).', 'schedspot' ); ?></p>
                    </td>
                </tr>
                <?php endif; ?>

                <tr>
                    <th scope="row"><?php _e( 'SMS Authentication', 'schedspot' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="schedspot_sms_login_required" value="1" <?php checked( $login_required ); ?> />
                            <?php _e( 'Require SMS verification for login', 'schedspot' ); ?>
                        </label>
                        <p class="description"><?php _e( 'Users will need to verify their phone number via SMS when logging in.', 'schedspot' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e( 'Login Notifications', 'schedspot' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="schedspot_sms_login_notifications" value="1" <?php checked( $login_notifications ); ?> />
                            <?php _e( 'Send SMS notification on login', 'schedspot' ); ?>
                        </label>
                        <p class="description"><?php _e( 'Users will receive an SMS notification when they log in.', 'schedspot' ); ?></p>
                    </td>
                </tr>

                <?php if ( $is_enabled && ! empty( $twilio_sid ) && ! empty( $twilio_token ) && ! empty( $twilio_number ) ) : ?>
                <tr>
                    <th scope="row"><?php _e( 'Test SMS', 'schedspot' ); ?></th>
                    <td>
                        <input type="text" id="test_phone" placeholder="+1234567890" class="regular-text" />
                        <button type="button" class="button" onclick="sendTestSMS()"><?php _e( 'Send Test SMS', 'schedspot' ); ?></button>
                        <p class="description"><?php _e( 'Send a test SMS to verify your configuration.', 'schedspot' ); ?></p>
                        <div id="test_result"></div>
                    </td>
                </tr>
                <?php endif; ?>

                <?php endif; ?>
            </table>

            <?php submit_button( __( 'Save SMS Settings', 'schedspot' ), 'primary', 'schedspot_sms_save' ); ?>
        </form>

        <script>
        function sendTestSMS() {
            var phone = document.getElementById('test_phone').value;
            if (!phone) {
                alert('<?php _e( 'Please enter a phone number.', 'schedspot' ); ?>');
                return;
            }

            jQuery.post(ajaxurl, {
                action: 'schedspot_test_sms',
                phone: phone,
                nonce: '<?php echo wp_create_nonce( 'schedspot_test_sms' ); ?>'
            }, function(response) {
                var result = document.getElementById('test_result');
                if (response.success) {
                    result.innerHTML = '<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>';
                } else {
                    result.innerHTML = '<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>';
                }
            });
        }
        </script>
        <?php
    }

    /**
     * Geolocation section callback.
     *
     * @since 2.0.0
     */
    public function geolocation_section_callback() {
        echo '<p>' . __( 'Configure geolocation and geofencing settings for location-based services.', 'schedspot' ) . '</p>';
    }

    /**
     * Enable geofencing field callback.
     *
     * @since 2.0.0
     */
    public function enable_geofencing_field_callback() {
        $value = get_option( 'schedspot_enable_geofencing', false );
        echo '<input type="checkbox" name="schedspot_enable_geofencing" value="1"' . checked( $value, 1, false ) . ' />';
        echo '<label>' . __( 'Enable location-based service restrictions', 'schedspot' ) . '</label>';
        echo '<p class="description">' . __( 'When enabled, workers can define service areas and bookings will be restricted by location.', 'schedspot' ) . '</p>';
    }

    /**
     * Google Maps API key field callback.
     *
     * @since 2.0.0
     */
    public function google_maps_api_key_field_callback() {
        $value = get_option( 'schedspot_google_maps_api_key', '' );
        echo '<input type="text" name="schedspot_google_maps_api_key" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . sprintf(
            __( 'Required for geocoding and maps. Get your API key from <a href="%s" target="_blank">Google Cloud Console</a>.', 'schedspot' ),
            'https://console.cloud.google.com/apis/credentials'
        ) . '</p>';
    }

    /**
     * Default service radius field callback.
     *
     * @since 2.0.0
     */
    public function default_service_radius_field_callback() {
        $value = get_option( 'schedspot_default_service_radius', 25.0 );
        echo '<input type="number" name="schedspot_default_service_radius" value="' . esc_attr( $value ) . '" min="1" max="500" step="0.1" />';
        echo '<p class="description">' . __( 'Default service radius in kilometers when workers don\'t specify custom service areas.', 'schedspot' ) . '</p>';
    }

    /**
     * Distance unit field callback.
     *
     * @since 2.0.0
     */
    public function distance_unit_field_callback() {
        $value = get_option( 'schedspot_distance_unit', 'km' );
        echo '<select name="schedspot_distance_unit">';
        echo '<option value="km"' . selected( $value, 'km', false ) . '>' . __( 'Kilometers', 'schedspot' ) . '</option>';
        echo '<option value="miles"' . selected( $value, 'miles', false ) . '>' . __( 'Miles', 'schedspot' ) . '</option>';
        echo '</select>';
        echo '<p class="description">' . __( 'Unit for displaying distances to users.', 'schedspot' ) . '</p>';
    }

    /**
     * Render geolocation settings page.
     *
     * @since 2.0.0
     */
    private function render_geolocation_settings() {
        $is_enabled = get_option( 'schedspot_enable_geofencing', false );
        $api_key = get_option( 'schedspot_google_maps_api_key', '' );

        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'schedspot_geolocation_settings' );
            do_settings_sections( 'schedspot_geolocation_settings' );
            ?>

            <table class="form-table">
                <?php if ( $is_enabled && empty( $api_key ) ) : ?>
                <tr>
                    <td colspan="2">
                        <div class="notice notice-warning inline">
                            <p><?php _e( 'Geofencing is enabled but Google Maps API key is missing. Please add your API key below.', 'schedspot' ); ?></p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>

                <?php if ( $is_enabled && ! empty( $api_key ) ) : ?>
                <tr>
                    <th scope="row"><?php _e( 'Test Geocoding', 'schedspot' ); ?></th>
                    <td>
                        <input type="text" id="test_address" placeholder="123 Main St, City, State" class="regular-text" />
                        <button type="button" class="button" onclick="testGeocoding()"><?php _e( 'Test Address', 'schedspot' ); ?></button>
                        <p class="description"><?php _e( 'Test geocoding functionality with a sample address.', 'schedspot' ); ?></p>
                        <div id="geocoding_result"></div>
                    </td>
                </tr>
                <?php endif; ?>
            </table>

            <?php submit_button( __( 'Save Geolocation Settings', 'schedspot' ), 'primary', 'schedspot_geolocation_save' ); ?>
        </form>

        <?php if ( $is_enabled && ! empty( $api_key ) ) : ?>
        <script>
        function testGeocoding() {
            var address = document.getElementById('test_address').value;
            if (!address) {
                alert('<?php _e( 'Please enter an address.', 'schedspot' ); ?>');
                return;
            }

            jQuery.post(ajaxurl, {
                action: 'schedspot_geocode_address',
                address: address,
                nonce: '<?php echo wp_create_nonce( 'schedspot_geolocation_nonce' ); ?>'
            }, function(response) {
                var result = document.getElementById('geocoding_result');
                if (response.success) {
                    result.innerHTML = '<div class="notice notice-success inline"><p>' +
                        '<?php _e( 'Success! Coordinates:', 'schedspot' ); ?> ' +
                        response.data.lat + ', ' + response.data.lng +
                        '<br><?php _e( 'Formatted address:', 'schedspot' ); ?> ' + response.data.formatted_address +
                        '</p></div>';
                } else {
                    result.innerHTML = '<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>';
                }
            });
        }
        </script>
        <?php endif; ?>
        <?php
    }

    // Section callbacks
    public function messaging_section_callback() {
        echo '<p>' . __( 'Configure messaging system settings for client-worker communication.', 'schedspot' ) . '</p>';
    }

    public function email_section_callback() {
        echo '<p>' . __( 'Configure email notification settings and templates.', 'schedspot' ) . '</p>';
    }

    public function advanced_section_callback() {
        echo '<p>' . __( 'Advanced system settings for debugging, caching, and data management.', 'schedspot' ) . '</p>';
    }

    // Messaging settings field callbacks
    public function enable_messaging_field_callback() {
        $value = get_option( 'schedspot_enable_messaging', true );
        echo '<input type="checkbox" name="schedspot_enable_messaging" value="1"' . checked( $value, 1, false ) . ' />';
        echo '<label>' . __( 'Enable messaging between clients and workers', 'schedspot' ) . '</label>';
    }

    public function allow_file_attachments_field_callback() {
        $value = get_option( 'schedspot_allow_file_attachments', true );
        echo '<input type="checkbox" name="schedspot_allow_file_attachments" value="1"' . checked( $value, 1, false ) . ' />';
        echo '<label>' . __( 'Allow file attachments in messages', 'schedspot' ) . '</label>';
    }

    public function max_file_size_field_callback() {
        $value = get_option( 'schedspot_max_file_size', 5 );
        echo '<input type="number" name="schedspot_max_file_size" value="' . esc_attr( $value ) . '" min="1" max="50" />';
        echo '<p class="description">' . __( 'Maximum file size in megabytes.', 'schedspot' ) . '</p>';
    }

    public function allowed_file_types_field_callback() {
        $value = get_option( 'schedspot_allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx' );
        echo '<input type="text" name="schedspot_allowed_file_types" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . __( 'Comma-separated list of allowed file extensions.', 'schedspot' ) . '</p>';
    }

    public function message_retention_days_field_callback() {
        $value = get_option( 'schedspot_message_retention_days', 365 );
        echo '<input type="number" name="schedspot_message_retention_days" value="' . esc_attr( $value ) . '" min="30" max="3650" />';
        echo '<p class="description">' . __( 'Number of days to keep messages before automatic deletion.', 'schedspot' ) . '</p>';
    }

    // Email settings field callbacks
    public function email_notifications_enabled_field_callback() {
        $value = get_option( 'schedspot_email_notifications_enabled', true );
        echo '<input type="checkbox" name="schedspot_email_notifications_enabled" value="1"' . checked( $value, 1, false ) . ' />';
        echo '<label>' . __( 'Enable email notifications for bookings and messages', 'schedspot' ) . '</label>';
    }

    public function admin_email_field_callback() {
        $value = get_option( 'schedspot_admin_email', get_option( 'admin_email' ) );
        echo '<input type="email" name="schedspot_admin_email" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . __( 'Email address for admin notifications.', 'schedspot' ) . '</p>';
    }

    public function email_from_name_field_callback() {
        $value = get_option( 'schedspot_email_from_name', get_bloginfo( 'name' ) );
        echo '<input type="text" name="schedspot_email_from_name" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . __( 'Name to appear in the "From" field of emails.', 'schedspot' ) . '</p>';
    }

    public function email_from_address_field_callback() {
        $value = get_option( 'schedspot_email_from_address', get_option( 'admin_email' ) );
        echo '<input type="email" name="schedspot_email_from_address" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . __( 'Email address to appear in the "From" field of emails.', 'schedspot' ) . '</p>';
    }

    // Advanced settings field callbacks
    public function enable_debug_mode_field_callback() {
        $value = get_option( 'schedspot_enable_debug_mode', false );
        echo '<input type="checkbox" name="schedspot_enable_debug_mode" value="1"' . checked( $value, 1, false ) . ' />';
        echo '<label>' . __( 'Enable debug mode for troubleshooting', 'schedspot' ) . '</label>';
        echo '<p class="description">' . __( 'Enables detailed logging and error reporting.', 'schedspot' ) . '</p>';
    }

    public function cache_duration_field_callback() {
        $value = get_option( 'schedspot_cache_duration', 60 );
        echo '<input type="number" name="schedspot_cache_duration" value="' . esc_attr( $value ) . '" min="5" max="1440" />';
        echo '<p class="description">' . __( 'Cache duration in minutes for API responses and data.', 'schedspot' ) . '</p>';
    }

    public function api_rate_limit_field_callback() {
        $value = get_option( 'schedspot_api_rate_limit', 1000 );
        echo '<input type="number" name="schedspot_api_rate_limit" value="' . esc_attr( $value ) . '" min="100" max="10000" />';
        echo '<p class="description">' . __( 'Maximum API requests per hour per user.', 'schedspot' ) . '</p>';
    }

    public function data_retention_days_field_callback() {
        $value = get_option( 'schedspot_data_retention_days', 1095 );
        echo '<input type="number" name="schedspot_data_retention_days" value="' . esc_attr( $value ) . '" min="90" max="3650" />';
        echo '<p class="description">' . __( 'Number of days to keep booking and user data before cleanup.', 'schedspot' ) . '</p>';
    }

    /**
     * Handle worker deletion with data cleanup.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID.
     */
    private function handle_delete_worker( $worker_id ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to delete workers.', 'schedspot' ) );
        }

        // Verify nonce
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'delete_worker_' . $worker_id ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $worker = get_userdata( $worker_id );
        if ( ! $worker || ! in_array( 'schedspot_worker', $worker->roles ) ) {
            echo '<div class="notice notice-error"><p>' . __( 'Worker not found.', 'schedspot' ) . '</p></div>';
            $this->render_workers_list();
            return;
        }

        // Check for active bookings
        global $wpdb;
        $active_bookings = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings
             WHERE worker_id = %d AND status IN ('pending', 'confirmed')",
            $worker_id
        ) );

        if ( $active_bookings > 0 ) {
            echo '<div class="notice notice-error"><p>' .
                 sprintf( __( 'Cannot delete worker. They have %d active booking(s). Please complete or cancel these bookings first.', 'schedspot' ), $active_bookings ) .
                 '</p></div>';
            $this->render_workers_list();
            return;
        }

        // Perform cleanup
        $this->cleanup_worker_data( $worker_id );

        // Delete the user
        if ( wp_delete_user( $worker_id ) ) {
            echo '<div class="notice notice-success"><p>' .
                 sprintf( __( 'Worker "%s" has been successfully deleted along with all associated data.', 'schedspot' ), $worker->display_name ) .
                 '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __( 'Error deleting worker.', 'schedspot' ) . '</p></div>';
        }

        $this->render_workers_list();
    }

    /**
     * Cleanup worker data before deletion.
     *
     * @since 1.0.0
     * @param int $worker_id Worker ID.
     */
    private function cleanup_worker_data( $worker_id ) {
        global $wpdb;

        // Delete worker services
        $wpdb->delete(
            $wpdb->prefix . 'schedspot_worker_services',
            array( 'worker_id' => $worker_id ),
            array( '%d' )
        );

        // Delete worker availability
        $wpdb->delete(
            $wpdb->prefix . 'schedspot_worker_availability',
            array( 'worker_id' => $worker_id ),
            array( '%d' )
        );

        // Delete service areas
        $wpdb->delete(
            $wpdb->prefix . 'schedspot_service_areas',
            array( 'worker_id' => $worker_id ),
            array( '%d' )
        );

        // Delete messages (sent and received)
        $wpdb->delete(
            $wpdb->prefix . 'schedspot_messages',
            array( 'sender_id' => $worker_id ),
            array( '%d' )
        );
        $wpdb->delete(
            $wpdb->prefix . 'schedspot_messages',
            array( 'receiver_id' => $worker_id ),
            array( '%d' )
        );

        // Update completed bookings to remove worker reference but keep for records
        $wpdb->update(
            $wpdb->prefix . 'schedspot_bookings',
            array( 'worker_notes' => 'Worker account deleted' ),
            array( 'worker_id' => $worker_id, 'status' => 'completed' ),
            array( '%s' ),
            array( '%d', '%s' )
        );

        // Delete user meta
        delete_user_meta( $worker_id, 'schedspot_worker_profile' );
        delete_user_meta( $worker_id, 'schedspot_payment_settings' );
        delete_user_meta( $worker_id, 'schedspot_worker_settings' );
    }
}
