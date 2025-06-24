<?php
/**
 * SchedSpot Navigation System
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot Navigation Class
 *
 * @class SchedSpot_Navigation
 * @version 1.0.0
 */
class SchedSpot_Navigation {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize navigation functionality.
     *
     * @since 1.0.0
     */
    public function init() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_navigation_assets' ) );
        add_action( 'wp_footer', array( $this, 'render_navigation' ) );
        add_shortcode( 'schedspot_navigation', array( $this, 'navigation_shortcode' ) );
        add_action( 'wp_ajax_schedspot_get_unread_count', array( $this, 'ajax_get_unread_count' ) );
    }

    /**
     * Enqueue navigation assets.
     *
     * @since 1.0.0
     */
    public function enqueue_navigation_assets() {
        // Only load on pages with SchedSpot content
        if ( ! $this->should_load_navigation() ) {
            return;
        }

        wp_enqueue_style( 
            'schedspot-navigation', 
            SCHEDSPOT_PLUGIN_URL . 'assets/css/navigation.css', 
            array(), 
            SCHEDSPOT_VERSION 
        );

        wp_enqueue_script( 
            'schedspot-navigation', 
            SCHEDSPOT_PLUGIN_URL . 'assets/js/navigation.js', 
            array( 'jquery' ), 
            SCHEDSPOT_VERSION, 
            true 
        );

        wp_localize_script( 'schedspot-navigation', 'schedspot_nav', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'schedspot_navigation' ),
            'current_user_role' => SchedSpot()->get_effective_user_role(),
            'is_logged_in' => is_user_logged_in(),
            'strings' => array(
                'menu' => __( 'Menu', 'schedspot' ),
                'close' => __( 'Close', 'schedspot' ),
                'login_required' => __( 'Please log in to access this feature.', 'schedspot' ),
            ),
        ) );
    }

    /**
     * Check if navigation should be loaded.
     *
     * @since 1.0.0
     * @return bool Whether to load navigation.
     */
    private function should_load_navigation() {
        global $post;

        // Don't load on admin pages
        if ( is_admin() ) {
            return false;
        }

        // Don't load on login/register pages
        if ( is_page() && $post ) {
            $page_template = get_page_template_slug( $post->ID );
            if ( in_array( $page_template, array( 'page-login.php', 'page-register.php' ) ) ) {
                return false;
            }
        }

        // Load on SchedSpot virtual pages
        if ( isset( $_GET['schedspot_action'] ) ) {
            return true;
        }

        // Load on pages with any SchedSpot shortcodes
        if ( $post && $post->post_content ) {
            $schedspot_shortcodes = array(
                'schedspot_booking_form',
                'schedspot_dashboard',
                'schedspot_messages',
                'schedspot_profile',
                'schedspot_services',
                'schedspot_workers_grid',
                'schedspot_navigation'
            );

            foreach ( $schedspot_shortcodes as $shortcode ) {
                if ( has_shortcode( $post->post_content, $shortcode ) ) {
                    return true;
                }
            }
        }

        // Load on frontend for logged-in users (so they always have access to navigation)
        if ( is_user_logged_in() && ! is_admin() ) {
            return true;
        }

        // Load on home page and main pages for easy access
        if ( is_front_page() || is_home() ) {
            return true;
        }

        // Allow filtering for custom conditions
        return apply_filters( 'schedspot_should_load_navigation', false );
    }

    /**
     * Render navigation.
     *
     * @since 1.0.0
     */
    public function render_navigation() {
        if ( ! $this->should_load_navigation() ) {
            return;
        }

        $user_role = SchedSpot()->get_effective_user_role();
        $is_logged_in = is_user_logged_in();
        $current_user = wp_get_current_user();

        ?>
        <div id="schedspot-navigation" class="schedspot-navigation">
            <div class="schedspot-nav-container">
                <div class="schedspot-nav-brand">
                    <span class="nav-logo">📅</span>
                    <span class="nav-title">SchedSpot</span>
                </div>

                <div class="schedspot-nav-menu">
                    <button class="nav-menu-toggle" id="schedspot-nav-toggle">
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                        <span class="nav-menu-text"><?php _e( 'Menu', 'schedspot' ); ?></span>
                    </button>

                    <div class="nav-dropdown" id="schedspot-nav-dropdown">
                        <div class="nav-dropdown-header">
                            <?php if ( $is_logged_in ) : ?>
                                <div class="nav-user-info">
                                    <div class="nav-user-avatar">
                                        <?php echo get_avatar( $current_user->ID, 32 ); ?>
                                    </div>
                                    <div class="nav-user-details">
                                        <div class="nav-user-name"><?php echo esc_html( $current_user->display_name ); ?></div>
                                        <div class="nav-user-role"><?php echo esc_html( $this->get_role_display_name( $user_role ) ); ?></div>
                                    </div>
                                </div>
                            <?php else : ?>
                                <div class="nav-guest-info">
                                    <h4><?php _e( 'Welcome to SchedSpot', 'schedspot' ); ?></h4>
                                    <p><?php _e( 'Book services with ease', 'schedspot' ); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="nav-dropdown-content">
                            <?php $this->render_navigation_items( $user_role, $is_logged_in ); ?>
                        </div>

                        <div class="nav-dropdown-footer">
                            <?php if ( $is_logged_in ) : ?>
                                <a href="<?php echo wp_logout_url( home_url() ); ?>" class="nav-logout-btn">
                                    <span class="nav-icon">🚪</span>
                                    <?php _e( 'Logout', 'schedspot' ); ?>
                                </a>
                            <?php else : ?>
                                <div class="nav-auth-buttons">
                                    <a href="<?php echo wp_login_url( get_permalink() ); ?>" class="nav-login-btn">
                                        <?php _e( 'Login', 'schedspot' ); ?>
                                    </a>
                                    <a href="<?php echo wp_registration_url(); ?>" class="nav-register-btn">
                                        <?php _e( 'Register', 'schedspot' ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render navigation items based on user role.
     *
     * @since 1.0.0
     * @param string $user_role User role.
     * @param bool $is_logged_in Whether user is logged in.
     */
    private function render_navigation_items( $user_role, $is_logged_in ) {
        $items = $this->get_navigation_items( $user_role, $is_logged_in );

        foreach ( $items as $section => $section_items ) {
            if ( empty( $section_items ) ) {
                continue;
            }

            echo '<div class="nav-section">';
            echo '<h5 class="nav-section-title">' . esc_html( $section ) . '</h5>';
            echo '<ul class="nav-section-items">';

            foreach ( $section_items as $item ) {
                $class = isset( $item['class'] ) ? ' class="' . esc_attr( $item['class'] ) . '"' : '';
                $target = isset( $item['target'] ) ? ' target="' . esc_attr( $item['target'] ) . '"' : '';

                echo '<li>';
                echo '<a href="' . esc_url( $item['url'] ) . '"' . $class . $target . '>';
                echo '<span class="nav-icon">' . $item['icon'] . '</span>';
                echo '<span class="nav-text">' . esc_html( $item['label'] ) . '</span>';
                if ( isset( $item['badge'] ) && $item['badge'] > 0 ) {
                    echo '<span class="nav-badge">' . esc_html( $item['badge'] ) . '</span>';
                }
                echo '</a>';
                echo '</li>';
            }

            echo '</ul>';
            echo '</div>';
        }
    }

    /**
     * Get navigation items for user role.
     *
     * @since 1.0.0
     * @param string $user_role User role.
     * @param bool $is_logged_in Whether user is logged in.
     * @return array Navigation items.
     */
    private function get_navigation_items( $user_role, $is_logged_in ) {
        $items = array();

        // Common items for all users
        $items[ __( 'Services', 'schedspot' ) ] = array(
            array(
                'label' => __( 'Book a Service', 'schedspot' ),
                'url' => home_url( '/?schedspot_action=booking_form' ),
                'icon' => '📅',
            ),
        );

        if ( $is_logged_in ) {
            // Items for logged-in users
            $items[ __( 'My Account', 'schedspot' ) ] = array(
                array(
                    'label' => __( 'Dashboard', 'schedspot' ),
                    'url' => home_url( '/?schedspot_action=dashboard' ),
                    'icon' => '🏠',
                ),
                array(
                    'label' => __( 'My Bookings', 'schedspot' ),
                    'url' => home_url( '/?schedspot_action=dashboard&view=bookings' ),
                    'icon' => '📋',
                ),
                array(
                    'label' => __( 'Messages', 'schedspot' ),
                    'url' => home_url( '/?schedspot_action=messages' ),
                    'icon' => '💬',
                    'badge' => $this->get_unread_messages_count(),
                ),
                array(
                    'label' => __( 'Profile & Settings', 'schedspot' ),
                    'url' => home_url( '/?schedspot_action=profile' ),
                    'icon' => '⚙️',
                ),
            );

            // Worker-specific items
            if ( $user_role === 'schedspot_worker' ) {
                $items[ __( 'Worker Tools', 'schedspot' ) ] = array(
                    array(
                        'label' => __( 'Manage Availability', 'schedspot' ),
                        'url' => home_url( '/?schedspot_action=profile&tab=availability' ),
                        'icon' => '📅',
                    ),
                    array(
                        'label' => __( 'Earnings & Payments', 'schedspot' ),
                        'url' => home_url( '/?schedspot_action=dashboard&view=earnings' ),
                        'icon' => '💰',
                    ),
                    array(
                        'label' => __( 'Service Settings', 'schedspot' ),
                        'url' => home_url( '/?schedspot_action=profile&tab=services' ),
                        'icon' => '🛠️',
                    ),
                );
            }

            // Admin-specific items
            if ( current_user_can( 'manage_options' ) ) {
                $items[ __( 'Administration', 'schedspot' ) ] = array(
                    array(
                        'label' => __( 'Admin Panel', 'schedspot' ),
                        'url' => admin_url( 'admin.php?page=schedspot' ),
                        'icon' => '🔧',
                        'target' => '_blank',
                    ),
                    array(
                        'label' => __( 'Role Switcher', 'schedspot' ),
                        'url' => admin_url( 'admin.php?page=schedspot-role-switcher' ),
                        'icon' => '🔄',
                        'target' => '_blank',
                    ),
                );
            }
        }

        return apply_filters( 'schedspot_navigation_items', $items, $user_role, $is_logged_in );
    }

    /**
     * Get unread messages count.
     *
     * @since 1.0.0
     * @return int Unread messages count.
     */
    private function get_unread_messages_count() {
        if ( ! is_user_logged_in() ) {
            return 0;
        }

        global $wpdb;
        $user_id = get_current_user_id();

        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_messages 
             WHERE receiver_id = %d AND read_at IS NULL",
            $user_id
        ) );
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
            'administrator' => __( 'Administrator', 'schedspot' ),
            'schedspot_worker' => __( 'Service Provider', 'schedspot' ),
            'schedspot_customer' => __( 'Customer', 'schedspot' ),
        );

        return isset( $names[ $role ] ) ? $names[ $role ] : __( 'User', 'schedspot' );
    }

    /**
     * Navigation shortcode.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function navigation_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'style' => 'horizontal',
            'show_user_info' => 'true',
        ), $atts, 'schedspot_navigation' );

        ob_start();
        $this->render_navigation();
        return ob_get_clean();
    }

    /**
     * AJAX handler for getting unread messages count.
     *
     * @since 1.0.0
     */
    public function ajax_get_unread_count() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_navigation' ) ) {
            wp_die( 'Security check failed' );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'User not logged in' );
        }

        $count = $this->get_unread_messages_count();
        wp_send_json_success( array( 'count' => $count ) );
    }
}
