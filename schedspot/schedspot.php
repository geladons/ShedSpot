<?php
/**
 * Plugin Name: SchedSpot
 * Plugin URI: https://schedspot.com
 * Description: A comprehensive WordPress service booking and marketplace plugin combining appointment scheduling with a multi-vendor marketplace.
 * Version: 1.7.0
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
 * @version 1.7.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'SCHEDSPOT_VERSION', '1.7.0' );
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
        // Debug logging removed to prevent log spam

        // Core includes
        $core_files = array(
            'class-schedspot-install.php',
            'models/class-schedspot-booking.php',
            'models/class-schedspot-service.php',
            'models/class-schedspot-worker.php',
            'api/class-schedspot-api.php',
            'integrations/class-schedspot-woocommerce.php',
            'integrations/class-schedspot-gcalendar.php',
            'integrations/class-schedspot-sms.php',
            'integrations/class-schedspot-geolocation.php',
        );

        foreach ( $core_files as $file ) {
            $file_path = SCHEDSPOT_INCLUDES_DIR . $file;
            if ( file_exists( $file_path ) ) {
                include_once $file_path;
            } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'SchedSpot: Core file not found: ' . $file_path );
            }
        }

        // Messaging classes
        $messaging_files = array(
            'messaging/class-schedspot-message.php',
            'messaging/class-schedspot-messaging.php',
        );

        foreach ( $messaging_files as $file ) {
            $file_path = SCHEDSPOT_INCLUDES_DIR . $file;
            if ( file_exists( $file_path ) ) {
                include_once $file_path;
            } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'SchedSpot: Messaging file not found: ' . $file_path );
            }
        }

        // Shortcode classes (new modular structure)
        $shortcode_files = array(
            'shortcodes/class-schedspot-shortcodes-core.php',
            'shortcodes/class-schedspot-shortcode-booking-form.php',
            'shortcodes/class-schedspot-shortcode-dashboard.php',
            'shortcodes/class-schedspot-shortcode-messages.php',
            'shortcodes/class-schedspot-shortcode-profile.php',
        );

        foreach ( $shortcode_files as $file ) {
            $file_path = SCHEDSPOT_INCLUDES_DIR . $file;
            if ( file_exists( $file_path ) ) {
                include_once $file_path;
            } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'SchedSpot: Shortcode file not found: ' . $file_path );
            }
        }

        // Navigation system
        $navigation_file = SCHEDSPOT_INCLUDES_DIR . 'class-schedspot-navigation.php';
        if ( file_exists( $navigation_file ) ) {
            include_once $navigation_file;
        } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'SchedSpot: Navigation file not found: ' . $navigation_file );
        }

        // Admin includes (new modular structure)
        if ( is_admin() ) {
            $admin_files = array(
                'class-schedspot-admin-core.php',
                'class-schedspot-admin-bookings.php',
                'class-schedspot-admin-services.php',
                'class-schedspot-admin-workers.php',
                'class-schedspot-admin-settings.php',
                'class-schedspot-admin-analytics.php',
                'class-schedspot-admin-schedule.php',
            );

            foreach ( $admin_files as $file ) {
                $file_path = SCHEDSPOT_ADMIN_DIR . $file;
                if ( file_exists( $file_path ) ) {
                    include_once $file_path;
                } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'SchedSpot: Admin file not found: ' . $file_path );
                }
            }
        }

        // Public includes
        if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
            $public_file = SCHEDSPOT_PUBLIC_DIR . 'class-schedspot-public.php';
            if ( file_exists( $public_file ) ) {
                include_once $public_file;
            } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'SchedSpot: Public file not found: ' . $public_file );
            }
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
        add_action( 'template_redirect', array( $this, 'handle_virtual_pages' ) );
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
        if ( class_exists( 'SchedSpot_API' ) ) {
            new SchedSpot_API();
        }

        // Initialize WooCommerce integration
        if ( class_exists( 'SchedSpot_WooCommerce' ) ) {
            new SchedSpot_WooCommerce();
        }

        // Initialize Google Calendar integration
        if ( class_exists( 'SchedSpot_GCal' ) ) {
            new SchedSpot_GCal();
        }

        // Initialize SMS integration
        if ( class_exists( 'SchedSpot_SMS' ) ) {
            new SchedSpot_SMS();
        }

        // Initialize geolocation integration
        if ( class_exists( 'SchedSpot_Geolocation' ) ) {
            new SchedSpot_Geolocation();
        }

        // Initialize messaging system
        if ( class_exists( 'SchedSpot_Messaging' ) ) {
            new SchedSpot_Messaging();
        }

        // Initialize shortcodes (new modular structure)
        if ( class_exists( 'SchedSpot_Shortcodes_Core' ) ) {
            new SchedSpot_Shortcodes_Core();
        }

        // Initialize navigation system
        if ( class_exists( 'SchedSpot_Navigation' ) ) {
            new SchedSpot_Navigation();
        }

        // Initialize admin (new modular structure)
        if ( is_admin() && class_exists( 'SchedSpot_Admin_Core' ) ) {
            new SchedSpot_Admin_Core();
        }

        // Initialize admin schedule management
        if ( is_admin() && class_exists( 'SchedSpot_Admin_Schedule' ) ) {
            new SchedSpot_Admin_Schedule();
        }

        // Initialize public
        if ( ( ! is_admin() || defined( 'DOING_AJAX' ) ) && class_exists( 'SchedSpot_Public' ) ) {
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

    /**
     * Handle virtual pages for SchedSpot actions.
     *
     * @since 1.0.0
     */
    public function handle_virtual_pages() {
        if ( ! isset( $_GET['schedspot_action'] ) ) {
            return;
        }

        $action = sanitize_text_field( $_GET['schedspot_action'] );

        switch ( $action ) {
            case 'booking_form':
                $this->render_virtual_page( __( 'Book a Service', 'schedspot' ), '[schedspot_booking_form]' );
                break;
            case 'dashboard':
                $this->render_virtual_page( __( 'My Dashboard', 'schedspot' ), '[schedspot_dashboard]' );
                break;
            case 'messages':
                $this->render_virtual_page( __( 'Messages', 'schedspot' ), '[schedspot_messages]' );
                break;
            case 'profile':
                $this->render_virtual_page( __( 'Profile & Settings', 'schedspot' ), '[schedspot_profile]' );
                break;
        }
    }

    /**
     * Render a virtual page with shortcode content.
     *
     * @since 1.0.0
     * @param string $title Page title.
     * @param string $content Page content (shortcode).
     */
    private function render_virtual_page( $title, $content ) {
        // Set up global post object for virtual page
        global $wp_query, $post;

        $wp_query->is_page = true;
        $wp_query->is_singular = true;
        $wp_query->is_home = false;
        $wp_query->is_archive = false;
        $wp_query->is_category = false;

        // Create a fake post object
        $post = new stdClass();
        $post->ID = -1;
        $post->post_author = 1;
        $post->post_date = current_time( 'mysql' );
        $post->post_date_gmt = current_time( 'mysql', 1 );
        $post->post_content = $content;
        $post->post_title = $title;
        $post->post_excerpt = '';
        $post->post_status = 'publish';
        $post->comment_status = 'closed';
        $post->ping_status = 'closed';
        $post->post_password = '';
        $post->post_name = sanitize_title( $title );
        $post->to_ping = '';
        $post->pinged = '';
        $post->post_modified = current_time( 'mysql' );
        $post->post_modified_gmt = current_time( 'mysql', 1 );
        $post->post_content_filtered = '';
        $post->post_parent = 0;
        $post->guid = home_url( '?schedspot_action=' . $_GET['schedspot_action'] );
        $post->menu_order = 0;
        $post->post_type = 'page';
        $post->post_mime_type = '';
        $post->comment_count = 0;
        $post->filter = 'raw';

        // Set up query vars
        $wp_query->queried_object = $post;
        $wp_query->queried_object_id = $post->ID;
        $wp_query->post = $post;
        $wp_query->posts = array( $post );
        $wp_query->post_count = 1;
        $wp_query->found_posts = 1;
        $wp_query->max_num_pages = 1;
        $wp_query->is_404 = false;

        // Try to get page template, fallback to index.php if not found
        $template = get_page_template();
        if ( ! $template || ! file_exists( $template ) ) {
            $template = get_template_directory() . '/index.php';
            if ( ! file_exists( $template ) ) {
                // Last resort: create a basic template
                $this->render_basic_template( $title, $content );
                exit;
            }
        }

        // Load the page template
        include( $template );
        exit;
    }

    /**
     * Render a basic template for virtual pages when no theme template is available.
     *
     * @since 1.0.0
     * @param string $title Page title.
     * @param string $content Page content (shortcode).
     */
    private function render_basic_template( $title, $content ) {
        get_header();
        ?>
        <div id="primary" class="content-area">
            <main id="main" class="site-main">
                <article class="page">
                    <header class="entry-header">
                        <h1 class="entry-title"><?php echo esc_html( $title ); ?></h1>
                    </header>
                    <div class="entry-content">
                        <?php echo do_shortcode( $content ); ?>
                    </div>
                </article>
            </main>
        </div>
        <?php
        get_sidebar();
        get_footer();
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
