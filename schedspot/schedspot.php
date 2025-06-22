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
