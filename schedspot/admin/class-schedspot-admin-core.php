<?php
/**
 * Admin Core Class
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Admin_Core Class.
 *
 * Handles core admin functionality including menu registration,
 * asset enqueuing, and basic admin initialization.
 *
 * @class SchedSpot_Admin_Core
 * @version 1.0.0
 */
class SchedSpot_Admin_Core {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize admin functionality.
     *
     * @since 1.0.0
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
     * @since 1.0.0
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
            array( 'SchedSpot_Admin_Bookings', 'bookings_page' )
        );

        // Services submenu
        add_submenu_page(
            'schedspot',
            __( 'Services', 'schedspot' ),
            __( 'Services', 'schedspot' ),
            'manage_options',
            'schedspot-services',
            array( 'SchedSpot_Admin_Services', 'services_page' )
        );

        // Workers submenu
        add_submenu_page(
            'schedspot',
            __( 'Workers', 'schedspot' ),
            __( 'Workers', 'schedspot' ),
            'manage_options',
            'schedspot-workers',
            array( 'SchedSpot_Admin_Workers', 'workers_page' )
        );

        // Settings submenu
        add_submenu_page(
            'schedspot',
            __( 'Settings', 'schedspot' ),
            __( 'Settings', 'schedspot' ),
            'manage_options',
            'schedspot-settings',
            array( 'SchedSpot_Admin_Settings', 'settings_page' )
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
     * @since 1.0.0
     */
    public function admin_init() {
        // Settings registration is handled by SchedSpot_Admin_Settings
        // This method is kept for backward compatibility and future core admin init tasks
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook.
     */
    public function admin_scripts( $hook ) {
        // Only load on SchedSpot admin pages
        if ( strpos( $hook, 'schedspot' ) === false ) {
            return;
        }

        // Enqueue admin CSS
        wp_enqueue_style( 
            'schedspot-admin-dashboard', 
            SCHEDSPOT_PLUGIN_URL . 'assets/css/admin-dashboard.css', 
            array(), 
            SCHEDSPOT_VERSION 
        );

        // Enqueue admin JavaScript
        wp_enqueue_script( 
            'schedspot-admin-dashboard', 
            SCHEDSPOT_PLUGIN_URL . 'assets/js/admin-dashboard.js', 
            array( 'jquery' ), 
            SCHEDSPOT_VERSION, 
            true 
        );

        // Localize script with admin data
        wp_localize_script( 'schedspot-admin-dashboard', 'schedspot_admin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'schedspot_switch_role' ), // Main nonce for role switching
            'nonces' => array(
                'role_switch' => wp_create_nonce( 'schedspot_switch_role' ),
                'booking_status' => wp_create_nonce( 'schedspot_booking_status' ),
                'gcal_disconnect' => wp_create_nonce( 'schedspot_gcal_disconnect' ),
                'gcal_sync_all' => wp_create_nonce( 'schedspot_gcal_sync_all' ),
                'test_sms' => wp_create_nonce( 'schedspot_test_sms' ),
                'geolocation' => wp_create_nonce( 'schedspot_geolocation_nonce' ),
                'refresh_stats' => wp_create_nonce( 'schedspot_refresh_stats' ),
                'refresh_bookings' => wp_create_nonce( 'schedspot_refresh_bookings' ),
            ),
            'strings' => array(
                'confirm_status_update' => __( 'Are you sure you want to update this booking status?', 'schedspot' ),
                'error_updating_status' => __( 'Error updating booking status.', 'schedspot' ),
                'confirm_gcal_disconnect' => __( 'Are you sure you want to disconnect Google Calendar?', 'schedspot' ),
                'error_gcal_disconnect' => __( 'Failed to disconnect. Please try again.', 'schedspot' ),
                'confirm_gcal_sync' => __( 'This will sync all confirmed bookings to Google Calendar. Continue?', 'schedspot' ),
                'error_gcal_sync' => __( 'Sync failed. Please try again.', 'schedspot' ),
                'enter_phone_number' => __( 'Please enter a phone number.', 'schedspot' ),
                'enter_address' => __( 'Please enter an address.', 'schedspot' ),
                'geocoding_success' => __( 'Success! Coordinates:', 'schedspot' ),
                'formatted_address' => __( 'Formatted address:', 'schedspot' ),
                'confirm_delete_worker' => __( 'Are you sure you want to delete this worker? This action cannot be undone.', 'schedspot' ),
                'confirm_delete_service' => __( 'Are you sure you want to delete this service? This action cannot be undone.', 'schedspot' ),
                'confirm_delete_booking' => __( 'Are you sure you want to delete this booking? This action cannot be undone.', 'schedspot' ),
                'service_name_required' => __( 'Service name is required.', 'schedspot' ),
                'invalid_price' => __( 'Please enter a valid price.', 'schedspot' ),
                'select_user' => __( 'Please select a user.', 'schedspot' ),
                'invalid_hourly_rate' => __( 'Please enter a valid hourly rate.', 'schedspot' ),
                'remove' => __( 'Remove', 'schedspot' ),
            ),
        ) );
    }

    /**
     * Add plugin action links.
     *
     * @since 1.0.0
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
     * @since 1.0.0
     */
    public function dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'SchedSpot Dashboard', 'schedspot' ); ?></h1>

            <!-- Debug Information Panel -->
            <div class="schedspot-debug-panel">
                <div class="debug-panel-header" onclick="toggleDebugPanel()">
                    <h3>
                        <span class="dashicons dashicons-info"></span>
                        <?php _e( 'System Debug Information', 'schedspot' ); ?>
                        <span class="debug-toggle-icon">▼</span>
                    </h3>
                </div>
                <div class="debug-panel-content" id="schedspot-debug-content" style="display: none;">
                    <?php $this->render_debug_information_panel(); ?>
                </div>
            </div>

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

        <script>
        function toggleDebugPanel() {
            var content = document.getElementById('schedspot-debug-content');
            var icon = document.querySelector('.debug-toggle-icon');
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.textContent = '▲';
            } else {
                content.style.display = 'none';
                icon.textContent = '▼';
            }
        }
        </script>
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
        
        // Load template
        include SCHEDSPOT_PLUGIN_DIR . 'templates/admin/role-switcher.php';
    }

    /**
     * Render recent bookings widget.
     *
     * @since 1.0.0
     */
    private function render_recent_bookings_widget() {
        $bookings = SchedSpot_Booking::get_bookings( array( 'limit' => 5 ) );
        
        if ( ! empty( $bookings ) ) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . __( 'Client', 'schedspot' ) . '</th><th>' . __( 'Date', 'schedspot' ) . '</th><th>' . __( 'Status', 'schedspot' ) . '</th></tr></thead>';
            echo '<tbody>';
            
            foreach ( $bookings as $booking ) {
                $client_details = is_string( $booking->client_details ) ? json_decode( $booking->client_details, true ) : $booking->client_details;
                if ( ! is_array( $client_details ) ) {
                    $client_details = array();
                }

                echo '<tr>';
                echo '<td>' . esc_html( $client_details['name'] ?? __( 'Unknown Client', 'schedspot' ) ) . '</td>';
                echo '<td>' . esc_html( date( 'M j, Y', strtotime( $booking->booking_date ) ) ) . '</td>';
                echo '<td><span class="status-badge status-' . esc_attr( $booking->status ) . '">' . esc_html( ucfirst( $booking->status ) ) . '</span></td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . __( 'No recent bookings found.', 'schedspot' ) . '</p>';
        }
    }

    /**
     * Render quick stats widget.
     *
     * @since 1.0.0
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
                <div class="stat-label"><?php _e( 'Active Workers', 'schedspot' ); ?></div>
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
     * @since 1.0.0
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
     * Render debug information panel.
     *
     * @since 1.7.0
     */
    private function render_debug_information_panel() {
        global $wpdb;

        // Get plugin version
        $plugin_data = get_plugin_data( SCHEDSPOT_PLUGIN_DIR . 'schedspot.php' );
        $plugin_version = $plugin_data['Version'] ?? '1.6.3';

        // Check database tables
        $tables = array(
            'schedspot_bookings',
            'schedspot_services',
            'schedspot_worker_services',
            'schedspot_worker_availability',
            'schedspot_messages',
            'schedspot_service_areas'
        );

        $table_status = array();
        foreach ( $tables as $table ) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name;
            $count = $exists ? $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ) : 0;
            $table_status[$table] = array( 'exists' => $exists, 'count' => $count );
        }

        // Get user roles count
        $worker_count = count( get_users( array( 'role' => 'schedspot_worker' ) ) );
        $customer_count = count( get_users( array( 'role' => 'schedspot_customer' ) ) );

        // Check REST API endpoints
        $rest_base = rest_url( 'schedspot/v1/' );
        $endpoints = array( 'bookings', 'services', 'workers', 'messages' );

        // Check asset files
        $assets = array(
            'CSS' => array(
                'frontend-enhanced.css',
                'booking-form.css',
                'dashboard.css',
                'navigation.css'
            ),
            'JS' => array(
                'frontend.js',
                'booking-form.js',
                'booking-wizard.js',
                'navigation.js'
            )
        );

        $asset_status = array();
        foreach ( $assets as $type => $files ) {
            foreach ( $files as $file ) {
                $path = SCHEDSPOT_PLUGIN_DIR . 'assets/' . strtolower( $type ) . '/' . $file;
                $asset_status[$type][$file] = file_exists( $path );
            }
        }

        // Get recent errors from debug log
        $recent_errors = $this->get_recent_debug_errors();

        ?>
        <div class="debug-info-grid">
            <!-- Plugin Information -->
            <div class="debug-section">
                <h4><span class="dashicons dashicons-admin-plugins"></span> Plugin Information</h4>
                <table class="debug-table">
                    <tr><td><strong>Version:</strong></td><td><?php echo esc_html( $plugin_version ); ?></td></tr>
                    <tr><td><strong>Plugin Path:</strong></td><td><?php echo esc_html( SCHEDSPOT_PLUGIN_DIR ); ?></td></tr>
                    <tr><td><strong>Plugin URL:</strong></td><td><?php echo esc_html( SCHEDSPOT_PLUGIN_URL ); ?></td></tr>
                    <tr><td><strong>WordPress Version:</strong></td><td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td></tr>
                    <tr><td><strong>PHP Version:</strong></td><td><?php echo esc_html( PHP_VERSION ); ?></td></tr>
                </table>
            </div>

            <!-- Database Status -->
            <div class="debug-section">
                <h4><span class="dashicons dashicons-database"></span> Database Tables</h4>
                <table class="debug-table">
                    <?php foreach ( $table_status as $table => $status ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( $table ); ?>:</strong></td>
                        <td>
                            <?php if ( $status['exists'] ) : ?>
                                <span class="debug-status-good">✓ Exists (<?php echo esc_html( $status['count'] ); ?> records)</span>
                            <?php else : ?>
                                <span class="debug-status-error">✗ Missing</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- User Roles -->
            <div class="debug-section">
                <h4><span class="dashicons dashicons-groups"></span> User Roles</h4>
                <table class="debug-table">
                    <tr><td><strong>Workers:</strong></td><td><?php echo esc_html( $worker_count ); ?> users</td></tr>
                    <tr><td><strong>Customers:</strong></td><td><?php echo esc_html( $customer_count ); ?> users</td></tr>
                    <tr><td><strong>Worker Role Exists:</strong></td><td><?php echo get_role( 'schedspot_worker' ) ? '<span class="debug-status-good">✓ Yes</span>' : '<span class="debug-status-error">✗ No</span>'; ?></td></tr>
                    <tr><td><strong>Customer Role Exists:</strong></td><td><?php echo get_role( 'schedspot_customer' ) ? '<span class="debug-status-good">✓ Yes</span>' : '<span class="debug-status-error">✗ No</span>'; ?></td></tr>
                </table>
            </div>

            <!-- REST API Status -->
            <div class="debug-section">
                <h4><span class="dashicons dashicons-rest-api"></span> REST API</h4>
                <table class="debug-table">
                    <tr><td><strong>Base URL:</strong></td><td><?php echo esc_html( $rest_base ); ?></td></tr>
                    <?php foreach ( $endpoints as $endpoint ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( $endpoint ); ?>:</strong></td>
                        <td><span class="debug-status-good">✓ Registered</span></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Asset Status -->
            <div class="debug-section">
                <h4><span class="dashicons dashicons-media-code"></span> Asset Files</h4>
                <?php foreach ( $asset_status as $type => $files ) : ?>
                <h5><?php echo esc_html( $type ); ?> Files:</h5>
                <table class="debug-table">
                    <?php foreach ( $files as $file => $exists ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( $file ); ?>:</strong></td>
                        <td>
                            <?php if ( $exists ) : ?>
                                <span class="debug-status-good">✓ Exists</span>
                            <?php else : ?>
                                <span class="debug-status-error">✗ Missing</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endforeach; ?>
            </div>

            <!-- Recent Errors -->
            <div class="debug-section">
                <h4><span class="dashicons dashicons-warning"></span> Recent Debug Log</h4>
                <?php if ( ! empty( $recent_errors ) ) : ?>
                <div class="debug-errors">
                    <?php foreach ( $recent_errors as $error ) : ?>
                    <div class="debug-error-item">
                        <small><?php echo esc_html( $error['time'] ); ?></small><br>
                        <code><?php echo esc_html( $error['message'] ); ?></code>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <p><span class="debug-status-good">✓ No recent SchedSpot errors found</span></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="debug-actions">
            <a href="<?php echo SCHEDSPOT_PLUGIN_URL . 'debug-test.php'; ?>" target="_blank" class="button">
                <?php _e( 'Run Full Debug Test', 'schedspot' ); ?>
            </a>
            <button type="button" class="button" onclick="location.reload()">
                <?php _e( 'Refresh Debug Info', 'schedspot' ); ?>
            </button>
        </div>
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
            wp_die( __( 'Invalid role specified.', 'schedspot' ) );
        }

        // Store the role mode for the current admin user
        update_user_meta( get_current_user_id(), 'schedspot_admin_role_mode', $target_role );

        // If switching to worker/customer mode and a test user is specified, store that too
        if ( $test_user_id > 0 && in_array( $target_role, array( 'schedspot_worker', 'schedspot_customer' ) ) ) {
            update_user_meta( get_current_user_id(), 'schedspot_test_user_id', $test_user_id );
        }

        // Redirect to prevent form resubmission
        wp_redirect( admin_url( 'admin.php?page=schedspot-role-switcher&switched=1' ) );
        exit;
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
                return $target_role;
            }
        }

        return get_user_meta( get_current_user_id(), 'schedspot_admin_role_mode', true ) ?: 'administrator';
    }

    /**
     * Get recent debug errors from log file.
     *
     * @since 1.7.0
     * @return array Recent errors.
     */
    private function get_recent_debug_errors() {
        $debug_log = WP_CONTENT_DIR . '/debug.log';
        $errors = array();

        if ( ! file_exists( $debug_log ) ) {
            return $errors;
        }

        $lines = file( $debug_log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        if ( ! $lines ) {
            return $errors;
        }

        // Get last 50 lines and filter for SchedSpot errors
        $recent_lines = array_slice( $lines, -50 );

        foreach ( $recent_lines as $line ) {
            if ( strpos( $line, 'SchedSpot' ) !== false &&
                 ( strpos( $line, 'Error' ) !== false ||
                   strpos( $line, 'Warning' ) !== false ||
                   strpos( $line, 'Fatal' ) !== false ) ) {

                // Extract timestamp and message
                preg_match( '/\[(.*?)\](.*)/', $line, $matches );
                if ( count( $matches ) >= 3 ) {
                    $errors[] = array(
                        'time' => $matches[1],
                        'message' => trim( $matches[2] )
                    );
                }
            }
        }

        return array_slice( $errors, -10 ); // Return last 10 errors
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
     * Add admin bar role switcher.
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
        ) );

        // Add quick switch options
        $roles = array( 'administrator', 'schedspot_worker', 'schedspot_customer' );
        foreach ( $roles as $role ) {
            if ( $role !== $current_role ) {
                $wp_admin_bar->add_node( array(
                    'parent' => 'schedspot-role-switcher',
                    'id'     => 'schedspot-switch-' . $role,
                    'title'  => sprintf( __( 'Switch to %s', 'schedspot' ), $this->get_role_display_name( $role ) ),
                    'href'   => wp_nonce_url(
                        admin_url( 'admin.php?page=schedspot-role-switcher&quick_switch=' . $role ),
                        'schedspot_quick_switch'
                    ),
                ) );
            }
        }
    }

    /**
     * Handle AJAX role switch.
     *
     * @since 1.0.0
     */
    public function handle_role_switch() {
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'schedspot' ) ) );
        }

        // Verify nonce - check both possible nonce names for compatibility
        $nonce_valid = false;
        if ( isset( $_POST['nonce'] ) ) {
            $nonce_valid = wp_verify_nonce( $_POST['nonce'], 'schedspot_switch_role' ) ||
                          wp_verify_nonce( $_POST['nonce'], 'schedspot_role_switch' );
        }

        if ( ! $nonce_valid ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        $target_role = sanitize_text_field( $_POST['role'] );

        // Validate target role
        $allowed_roles = array( 'administrator', 'schedspot_worker', 'schedspot_customer' );
        if ( ! in_array( $target_role, $allowed_roles ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid role selected.', 'schedspot' ) ) );
        }

        // Update user meta
        update_user_meta( get_current_user_id(), 'schedspot_admin_role_mode', $target_role );

        wp_send_json_success( array(
            'message' => sprintf( __( 'Switched to %s mode', 'schedspot' ), $this->get_role_display_name( $target_role ) ),
            'role' => $target_role,
        ) );
    }
}
