<?php
/**
 * Plugin Name: SchedSpot
 * Plugin URI: https://schedspot.com
 * Description: A comprehensive WordPress service booking and marketplace plugin combining appointment scheduling with a multi-vendor marketplace.
 * Version: 1.0.0
 * Author: SchedSpot Team
 * Author URI: https://schedspot.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: schedspot
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'SCHEDSPOT_VERSION', '1.0.0' );
define( 'SCHEDSPOT_PLUGIN_FILE', __FILE__ );
define( 'SCHEDSPOT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCHEDSPOT_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
define( 'SCHEDSPOT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main SchedSpot Plugin Class
 *
 * @class SchedSpot_Core
 * @version 0.1.0
 */
final class SchedSpot_Core {

    /**
     * The single instance of the class.
     *
     * @var SchedSpot_Core
     * @since 0.1.0
     */
    protected static $_instance = null;

    /**
     * Main SchedSpot Instance.
     *
     * Ensures only one instance of SchedSpot is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return SchedSpot_Core - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * SchedSpot Constructor.
     *
     * @since 0.1.0
     */
    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Define SchedSpot Constants.
     *
     * @since 0.1.0
     */
    private function define_constants() {
        $this->define( 'SCHEDSPOT_ABSPATH', dirname( SCHEDSPOT_PLUGIN_FILE ) . '/' );
        $this->define( 'SCHEDSPOT_INCLUDES_DIR', SCHEDSPOT_ABSPATH . 'includes/' );
        $this->define( 'SCHEDSPOT_ADMIN_DIR', SCHEDSPOT_ABSPATH . 'admin/' );
        $this->define( 'SCHEDSPOT_PUBLIC_DIR', SCHEDSPOT_ABSPATH . 'public/' );
        $this->define( 'SCHEDSPOT_TEMPLATES_DIR', SCHEDSPOT_ABSPATH . 'templates/' );
    }

    /**
     * Define constant if not already set.
     *
     * @since 0.1.0
     * @param string $name Constant name.
     * @param string|bool $value Constant value.
     */
    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     *
     * @since 0.1.0
     */
    public function includes() {
        // Core includes
        include_once SCHEDSPOT_INCLUDES_DIR . 'class-schedspot-install.php';
        include_once SCHEDSPOT_INCLUDES_DIR . 'models/class-schedspot-booking.php';
        include_once SCHEDSPOT_INCLUDES_DIR . 'models/class-schedspot-service.php';
        include_once SCHEDSPOT_INCLUDES_DIR . 'models/class-schedspot-worker.php';
        include_once SCHEDSPOT_INCLUDES_DIR . 'shortcodes/class-schedspot-shortcodes.php';
        include_once SCHEDSPOT_INCLUDES_DIR . 'api/class-schedspot-api.php';
        include_once SCHEDSPOT_INCLUDES_DIR . 'integrations/class-schedspot-woocommerce.php';
        include_once SCHEDSPOT_INCLUDES_DIR . 'integrations/class-schedspot-gcalendar.php';
        include_once SCHEDSPOT_INCLUDES_DIR . 'integrations/class-schedspot-sms.php';
        include_once SCHEDSPOT_INCLUDES_DIR . 'integrations/class-schedspot-geolocation.php';

        // Messaging classes
        include_once SCHEDSPOT_INCLUDES_DIR . 'messaging/class-schedspot-message.php';
        include_once SCHEDSPOT_INCLUDES_DIR . 'messaging/class-schedspot-messaging.php';

        // Admin includes
        if ( is_admin() ) {
            include_once SCHEDSPOT_ADMIN_DIR . 'class-schedspot-admin.php';
        }

        // Public includes
        if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
            include_once SCHEDSPOT_PUBLIC_DIR . 'class-schedspot-public.php';
        }
    }

    /**
     * Hook into actions and filters.
     *
     * @since 0.1.0
     */
    private function init_hooks() {
        register_activation_hook( SCHEDSPOT_PLUGIN_FILE, array( 'SchedSpot_Install', 'install' ) );
        register_deactivation_hook( SCHEDSPOT_PLUGIN_FILE, array( 'SchedSpot_Install', 'deactivate' ) );

        add_action( 'init', array( $this, 'init' ), 0 );
        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
    }

    /**
     * Init SchedSpot when WordPress Initialises.
     *
     * @since 0.1.0
     */
    public function init() {
        // Before init action
        do_action( 'before_schedspot_init' );

        // Set up localisation
        $this->load_plugin_textdomain();

        // Initialize components
        $this->init_components();

        // Init action
        do_action( 'schedspot_init' );
    }

    /**
     * Initialize plugin components.
     *
     * @since 0.1.0
     */
    private function init_components() {
        // Initialize API
        new SchedSpot_API();

        // Initialize WooCommerce integration
        new SchedSpot_WooCommerce();

        // Initialize Google Calendar integration
        new SchedSpot_GCal();

        // Initialize SMS integration
        new SchedSpot_SMS();

        // Initialize geolocation integration
        new SchedSpot_Geolocation();

        // Initialize messaging system
        new SchedSpot_Messaging();

        // Initialize shortcodes
        new SchedSpot_Shortcodes();

        // Initialize admin
        if ( is_admin() ) {
            new SchedSpot_Admin();
        }

        // Initialize public
        if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
            new SchedSpot_Public();
        }
    }

    /**
     * Load Localisation files.
     *
     * Note: the first-loaded translation file overrides any following ones if the same translation is present.
     *
     * Locales found in:
     *      - WP_LANG_DIR/schedspot/schedspot-LOCALE.mo
     *      - WP_LANG_DIR/plugins/schedspot-LOCALE.mo
     *
     * @since 0.1.0
     */
    public function load_plugin_textdomain() {
        $locale = determine_locale();
        $locale = apply_filters( 'plugin_locale', $locale, 'schedspot' );

        unload_textdomain( 'schedspot' );
        load_textdomain( 'schedspot', WP_LANG_DIR . '/schedspot/schedspot-' . $locale . '.mo' );
        load_plugin_textdomain( 'schedspot', false, plugin_basename( dirname( SCHEDSPOT_PLUGIN_FILE ) ) . '/languages' );
    }

    /**
     * Get the plugin url.
     *
     * @since 0.1.0
     * @return string
     */
    public function plugin_url() {
        return untrailingslashit( plugins_url( '/', SCHEDSPOT_PLUGIN_FILE ) );
    }

    /**
     * Get the plugin path.
     *
     * @since 0.1.0
     * @return string
     */
    public function plugin_path() {
        return untrailingslashit( plugin_dir_path( SCHEDSPOT_PLUGIN_FILE ) );
    }

    /**
     * Get Ajax URL.
     *
     * @since 0.1.0
     * @return string
     */
    public function ajax_url() {
        return admin_url( 'admin-ajax.php', 'relative' );
    }

    /**
     * Get effective user role considering admin role switching.
     *
     * @since 1.0.0
     * @param int $user_id User ID (optional, defaults to current user).
     * @return string Effective user role.
     */
    public function get_effective_user_role( $user_id = 0 ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return '';
        }

        // Check if current user is admin and has role switching enabled
        if ( current_user_can( 'manage_options' ) ) {
            $admin_role_mode = get_user_meta( get_current_user_id(), 'schedspot_admin_role_mode', true );
            $impersonate_user = get_user_meta( get_current_user_id(), 'schedspot_admin_impersonate_user', true );

            // If admin is impersonating another user, get that user's role
            if ( $impersonate_user && $impersonate_user != get_current_user_id() ) {
                $impersonate_user_obj = get_userdata( $impersonate_user );
                if ( $impersonate_user_obj ) {
                    return $this->get_user_primary_schedspot_role( $impersonate_user_obj );
                }
            }

            // If admin has switched role mode, return that role
            if ( $admin_role_mode && $admin_role_mode !== 'administrator' ) {
                return $admin_role_mode;
            }
        }

        // Return user's actual primary SchedSpot role
        return $this->get_user_primary_schedspot_role( $user );
    }

    /**
     * Get user's primary SchedSpot role.
     *
     * @since 1.0.0
     * @param WP_User $user User object.
     * @return string Primary SchedSpot role.
     */
    private function get_user_primary_schedspot_role( $user ) {
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
     * Check if current user has effective capability considering role switching.
     *
     * @since 1.0.0
     * @param string $capability Capability to check.
     * @return bool Whether user has the capability.
     */
    public function current_user_can_effective( $capability ) {
        // If admin is in role switching mode, check capabilities for that role
        if ( current_user_can( 'manage_options' ) ) {
            $effective_role = $this->get_effective_user_role();

            if ( $effective_role === 'schedspot_worker' ) {
                $worker_caps = array(
                    'schedspot_manage_bookings' => true,
                    'schedspot_view_own_bookings' => true,
                    'schedspot_send_messages' => true,
                    'schedspot_read_messages' => true,
                    'schedspot_manage_availability' => true,
                    'schedspot_manage_profile' => true,
                );
                return isset( $worker_caps[ $capability ] ) ? $worker_caps[ $capability ] : false;
            } elseif ( $effective_role === 'schedspot_customer' ) {
                $customer_caps = array(
                    'schedspot_create_booking' => true,
                    'schedspot_view_own_bookings' => true,
                    'schedspot_send_messages' => true,
                    'schedspot_read_messages' => true,
                );
                return isset( $customer_caps[ $capability ] ) ? $customer_caps[ $capability ] : false;
            }
        }

        // Default to WordPress capability check
        return current_user_can( $capability );
    }
}

/**
 * Main instance of SchedSpot.
 *
 * Returns the main instance of SchedSpot to prevent the need to use globals.
 *
 * @since 0.1.0
 * @return SchedSpot_Core
 */
function SchedSpot() {
    return SchedSpot_Core::instance();
}

// Global for backwards compatibility.
$GLOBALS['schedspot'] = SchedSpot();
