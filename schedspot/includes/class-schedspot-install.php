<?php
/**
 * Installation related functions and actions.
 *
 * @package SchedSpot
 * @version 0.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Install Class.
 *
 * @class SchedSpot_Install
 * @version 0.1.0
 */
class SchedSpot_Install {

    /**
     * DB updates and callbacks that need to be run per version.
     *
     * @var array
     */
    private static $db_updates = array(
        '0.1.0' => array(
            'schedspot_update_010_db_version',
        ),
    );

    /**
     * Install SchedSpot.
     *
     * @since 0.1.0
     */
    public static function install() {
        if ( ! is_blog_installed() ) {
            return;
        }

        // Check if we are not already running this routine.
        if ( 'yes' === get_transient( 'schedspot_installing' ) ) {
            return;
        }

        // If we made it till here nothing is running yet, lets set the transient now.
        set_transient( 'schedspot_installing', 'yes', MINUTE_IN_SECONDS * 10 );

        self::create_options();
        self::create_tables();
        self::create_roles();
        self::create_pages();
        self::update_schedspot_version();

        // Install sample data if this is a fresh installation
        if ( get_option( 'schedspot_install_sample_data', 'yes' ) === 'yes' ) {
            include_once SCHEDSPOT_INCLUDES_DIR . 'class-schedspot-sample-data.php';
            SchedSpot_Sample_Data::install();
        }

        delete_transient( 'schedspot_installing' );

        do_action( 'schedspot_installed' );
    }

    /**
     * Deactivate SchedSpot.
     *
     * @since 0.1.0
     */
    public static function deactivate() {
        // Clear any cached data that has been removed.
        wp_cache_flush();

        do_action( 'schedspot_deactivated' );
    }

    /**
     * Create default options.
     *
     * @since 0.1.0
     */
    private static function create_options() {
        $default_options = array(
            'schedspot_version'                => SCHEDSPOT_VERSION,
            'schedspot_db_version'             => SCHEDSPOT_VERSION,
            'schedspot_default_timezone'       => 'UTC',
            'schedspot_date_format'            => 'Y-m-d',
            'schedspot_time_format'            => 'H:i',
            'schedspot_currency'               => 'USD',
            'schedspot_default_slot_length'    => 60, // minutes
            'schedspot_minimum_notice'         => 24, // hours
            'schedspot_system_fee_per_hour'    => 0,
            'schedspot_commission_rate'        => 10, // percentage
            'schedspot_enable_sms'             => 'no',
            'schedspot_enable_geofencing'      => 'no',
            'schedspot_auto_approve_bookings'  => 'no',
            'schedspot_install_sample_data'    => 'yes',
            'schedspot_enable_payments'        => 'yes',
            'schedspot_deposit_rate'           => 30,
            'schedspot_payment_required'       => 'deposit',
        );

        foreach ( $default_options as $option_name => $option_value ) {
            if ( false === get_option( $option_name, false ) ) {
                add_option( $option_name, $option_value );
            }
        }
    }

    /**
     * Set up the database tables which the plugin needs to function.
     *
     * @since 0.1.0
     */
    private static function create_tables() {
        global $wpdb;

        $wpdb->hide_errors();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $collate = '';
        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
        }

        $tables = "
CREATE TABLE {$wpdb->prefix}schedspot_bookings (
  id bigint(20) unsigned NOT NULL auto_increment,
  user_id bigint(20) unsigned NOT NULL,
  worker_id bigint(20) unsigned NOT NULL,
  service_id bigint(20) unsigned DEFAULT NULL,
  booking_date datetime NOT NULL,
  start_time time NOT NULL,
  end_time time NOT NULL,
  duration int(11) NOT NULL DEFAULT 60,
  status varchar(20) NOT NULL DEFAULT 'pending',
  total_cost decimal(10,2) NOT NULL DEFAULT 0.00,
  deposit_amount decimal(10,2) NOT NULL DEFAULT 0.00,
  commission_amount decimal(10,2) NOT NULL DEFAULT 0.00,
  client_name varchar(255) NOT NULL,
  client_email varchar(255) NOT NULL,
  client_phone varchar(20) DEFAULT NULL,
  client_address text DEFAULT NULL,
  client_lat decimal(10,8) DEFAULT NULL,
  client_lng decimal(11,8) DEFAULT NULL,
  notes text DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY user_id (user_id),
  KEY worker_id (worker_id),
  KEY service_id (service_id),
  KEY booking_date (booking_date),
  KEY status (status)
) $collate;

CREATE TABLE {$wpdb->prefix}schedspot_services (
  id bigint(20) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL,
  description text DEFAULT NULL,
  duration int(11) NOT NULL DEFAULT 60,
  price_type varchar(20) NOT NULL DEFAULT 'hourly',
  base_price decimal(10,2) NOT NULL DEFAULT 0.00,
  category varchar(100) DEFAULT NULL,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY is_active (is_active),
  KEY category (category)
) $collate;

CREATE TABLE {$wpdb->prefix}schedspot_worker_services (
  id bigint(20) unsigned NOT NULL auto_increment,
  worker_id bigint(20) unsigned NOT NULL,
  service_id bigint(20) unsigned NOT NULL,
  custom_price decimal(10,2) DEFAULT NULL,
  is_enabled tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY worker_service (worker_id, service_id),
  KEY worker_id (worker_id),
  KEY service_id (service_id)
) $collate;

CREATE TABLE {$wpdb->prefix}schedspot_worker_availability (
  id bigint(20) unsigned NOT NULL auto_increment,
  worker_id bigint(20) unsigned NOT NULL,
  day_of_week tinyint(1) NOT NULL,
  start_time time NOT NULL,
  end_time time NOT NULL,
  is_available tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY worker_id (worker_id),
  KEY day_of_week (day_of_week)
) $collate;

CREATE TABLE {$wpdb->prefix}schedspot_payments (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  booking_id bigint(20) unsigned NOT NULL,
  order_id bigint(20) unsigned NOT NULL,
  amount decimal(10,2) NOT NULL DEFAULT 0.00,
  payment_method varchar(50) DEFAULT '',
  transaction_id varchar(100) DEFAULT '',
  status varchar(20) DEFAULT 'pending',
  payment_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY booking_id (booking_id),
  KEY order_id (order_id),
  KEY status (status),
  KEY payment_date (payment_date)
) $collate;
        ";

        dbDelta( $tables );
    }

    /**
     * Create roles and capabilities.
     *
     * @since 0.1.0
     */
    private static function create_roles() {
        global $wp_roles;

        if ( ! class_exists( 'WP_Roles' ) ) {
            return;
        }

        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }

        // Customer role
        add_role(
            'schedspot_customer',
            __( 'SchedSpot Customer', 'schedspot' ),
            array(
                'read'                   => true,
                'schedspot_create_booking' => true,
                'schedspot_view_own_bookings' => true,
                'schedspot_cancel_own_booking' => true,
            )
        );

        // Worker role
        add_role(
            'schedspot_worker',
            __( 'SchedSpot Worker', 'schedspot' ),
            array(
                'read'                        => true,
                'schedspot_manage_bookings'   => true,
                'schedspot_view_own_bookings' => true,
                'schedspot_accept_booking'    => true,
                'schedspot_decline_booking'   => true,
                'schedspot_complete_booking'  => true,
                'schedspot_manage_availability' => true,
                'schedspot_manage_services'   => true,
            )
        );

        // Add capabilities to administrator
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            $admin_capabilities = array(
                'schedspot_manage_all_bookings',
                'schedspot_manage_workers',
                'schedspot_manage_customers',
                'schedspot_manage_services',
                'schedspot_view_analytics',
                'schedspot_manage_settings',
            );

            foreach ( $admin_capabilities as $cap ) {
                $admin_role->add_cap( $cap );
            }
        }
    }

    /**
     * Create default pages.
     *
     * @since 0.1.0
     */
    private static function create_pages() {
        $pages = array(
            'schedspot_booking_page' => array(
                'name'    => _x( 'book-service', 'Page slug', 'schedspot' ),
                'title'   => _x( 'Book a Service', 'Page title', 'schedspot' ),
                'content' => '[schedspot_booking_form]',
            ),
            'schedspot_services_page' => array(
                'name'    => _x( 'services', 'Page slug', 'schedspot' ),
                'title'   => _x( 'Our Services', 'Page title', 'schedspot' ),
                'content' => '[schedspot_service_list]',
            ),
            'schedspot_dashboard_page' => array(
                'name'    => _x( 'my-account', 'Page slug', 'schedspot' ),
                'title'   => _x( 'My Account', 'Page title', 'schedspot' ),
                'content' => '[schedspot_dashboard]',
            ),
        );

        foreach ( $pages as $key => $page ) {
            self::create_page( esc_sql( $page['name'] ), $key, $page['title'], $page['content'] );
        }
    }

    /**
     * Create a page and store the ID in an option.
     *
     * @since 0.1.0
     * @param mixed  $slug Slug for the new page.
     * @param string $option Option name to store the page's ID.
     * @param string $page_title (default: '') Title for the new page.
     * @param string $page_content (default: '') Content for the new page.
     * @param int    $post_parent (default: 0) Parent for the new page.
     * @return int Valid page ID.
     */
    private static function create_page( $slug, $option = '', $page_title = '', $page_content = '', $post_parent = 0 ) {
        global $wpdb;

        $option_value = get_option( $option );

        if ( $option_value > 0 ) {
            $page_object = get_post( $option_value );

            if ( $page_object && 'page' === $page_object->post_type && ! in_array( $page_object->post_status, array( 'pending', 'trash', 'future', 'auto-draft' ), true ) ) {
                // Valid page is already in place.
                return $page_object->ID;
            }
        }

        if ( strlen( $page_content ) > 0 ) {
            // Search for an existing page with the specified page content (typically a shortcode).
            $shortcode = str_replace( array( '<!-- wp:shortcode -->', '<!-- /wp:shortcode -->' ), '', $page_content );
            $valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", "%{$shortcode}%" ) );
        } else {
            // Search for an existing page with the specified page slug (typically the page name).
            $valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' )  AND post_name = %s LIMIT 1;", $slug ) );
        }

        $valid_page_found = apply_filters( 'schedspot_create_page_id', $valid_page_found, $slug, $page_content );

        if ( $valid_page_found ) {
            if ( $option ) {
                update_option( $option, $valid_page_found );
            }
            return $valid_page_found;
        }

        // Create the page.
        $page_data = array(
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_author'    => 1,
            'post_name'      => $slug,
            'post_title'     => $page_title,
            'post_content'   => $page_content,
            'post_parent'    => $post_parent,
            'comment_status' => 'closed',
        );
        $page_id   = wp_insert_post( $page_data );

        if ( $option ) {
            update_option( $option, $page_id );
        }

        return $page_id;
    }

    /**
     * Update SchedSpot version to current.
     *
     * @since 0.1.0
     */
    private static function update_schedspot_version() {
        update_option( 'schedspot_version', SCHEDSPOT_VERSION );
    }
}
