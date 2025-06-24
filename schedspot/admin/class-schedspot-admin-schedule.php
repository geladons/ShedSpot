<?php
/**
 * SchedSpot Admin Schedule Management
 *
 * @package SchedSpot
 * @version 1.7.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot Admin Schedule Management Class
 *
 * @class SchedSpot_Admin_Schedule
 * @version 1.7.0
 */
class SchedSpot_Admin_Schedule {

    /**
     * Constructor.
     *
     * @since 1.7.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize schedule management.
     *
     * @since 1.7.0
     */
    public function init() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // Create exceptions table on init
        add_action( 'admin_init', array( $this, 'create_exceptions_table' ) );

        // AJAX handlers
        add_action( 'wp_ajax_schedspot_save_worker_schedule', array( $this, 'handle_save_worker_schedule' ) );
        add_action( 'wp_ajax_schedspot_get_worker_schedule', array( $this, 'handle_get_worker_schedule' ) );
        add_action( 'wp_ajax_schedspot_bulk_schedule_update', array( $this, 'handle_bulk_schedule_update' ) );
        add_action( 'wp_ajax_schedspot_add_schedule_exception', array( $this, 'handle_add_schedule_exception' ) );
        add_action( 'wp_ajax_schedspot_remove_schedule_exception', array( $this, 'handle_remove_schedule_exception' ) );
    }

    /**
     * Add admin menu.
     *
     * @since 1.7.0
     */
    public function add_admin_menu() {
        add_submenu_page(
            'schedspot',
            __( 'Worker Schedules', 'schedspot' ),
            __( 'Schedules', 'schedspot' ),
            'manage_options',
            'schedspot-schedules',
            array( $this, 'render_schedule_page' )
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @since 1.7.0
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( 'schedspot_page_schedspot-schedules' !== $hook ) {
            return;
        }

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_style( 'jquery-ui-style', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css' );
        
        wp_enqueue_script(
            'schedspot-admin-schedule',
            SCHEDSPOT_PLUGIN_URL . 'assets/js/admin-schedule.js',
            array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable' ),
            SCHEDSPOT_VERSION,
            true
        );

        wp_enqueue_style(
            'schedspot-admin-schedule',
            SCHEDSPOT_PLUGIN_URL . 'assets/css/admin-schedule.css',
            array(),
            SCHEDSPOT_VERSION
        );

        wp_localize_script( 'schedspot-admin-schedule', 'schedspot_schedule', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'schedspot_schedule_action' ),
            'strings' => array(
                'save_success' => __( 'Schedule saved successfully!', 'schedspot' ),
                'save_error' => __( 'Error saving schedule. Please try again.', 'schedspot' ),
                'confirm_delete' => __( 'Are you sure you want to delete this schedule exception?', 'schedspot' ),
                'loading' => __( 'Loading...', 'schedspot' ),
            ),
        ) );
    }

    /**
     * Render schedule management page.
     *
     * @since 1.7.0
     */
    public function render_schedule_page() {
        $workers = $this->get_workers();
        $selected_worker = isset( $_GET['worker_id'] ) ? intval( $_GET['worker_id'] ) : 0;
        
        if ( $selected_worker && ! $this->worker_exists( $selected_worker ) ) {
            $selected_worker = 0;
        }

        ?>
        <div class="wrap schedspot-admin-schedule">
            <h1><?php _e( 'Worker Schedule Management', 'schedspot' ); ?></h1>
            
            <div class="schedspot-schedule-header">
                <div class="schedspot-worker-selector">
                    <label for="worker-select"><?php _e( 'Select Worker:', 'schedspot' ); ?></label>
                    <select id="worker-select" name="worker_id">
                        <option value=""><?php _e( 'Choose a worker...', 'schedspot' ); ?></option>
                        <?php foreach ( $workers as $worker ) : ?>
                            <option value="<?php echo esc_attr( $worker->ID ); ?>" 
                                    <?php selected( $selected_worker, $worker->ID ); ?>>
                                <?php echo esc_html( $worker->display_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="load-schedule" class="button button-secondary">
                        <?php _e( 'Load Schedule', 'schedspot' ); ?>
                    </button>
                </div>

                <div class="schedspot-schedule-actions">
                    <button type="button" id="bulk-update" class="button button-secondary">
                        <?php _e( 'Bulk Update', 'schedspot' ); ?>
                    </button>
                    <button type="button" id="save-schedule" class="button button-primary">
                        <?php _e( 'Save Schedule', 'schedspot' ); ?>
                    </button>
                </div>
            </div>

            <?php if ( $selected_worker ) : ?>
                <div id="schedule-container" data-worker-id="<?php echo esc_attr( $selected_worker ); ?>">
                    <?php $this->render_schedule_interface( $selected_worker ); ?>
                </div>
            <?php else : ?>
                <div id="schedule-container" style="display: none;">
                    <!-- Schedule interface will be loaded here -->
                </div>
                <div class="schedspot-no-worker">
                    <p><?php _e( 'Please select a worker to manage their schedule.', 'schedspot' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render schedule interface for a specific worker.
     *
     * @since 1.7.0
     * @param int $worker_id Worker ID.
     */
    private function render_schedule_interface( $worker_id ) {
        $schedule = $this->get_worker_schedule( $worker_id );
        $exceptions = $this->get_schedule_exceptions( $worker_id );
        
        ?>
        <div class="schedspot-schedule-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#weekly-schedule" class="nav-tab nav-tab-active"><?php _e( 'Weekly Schedule', 'schedspot' ); ?></a>
                <a href="#exceptions" class="nav-tab"><?php _e( 'Exceptions', 'schedspot' ); ?></a>
                <a href="#calendar-view" class="nav-tab"><?php _e( 'Calendar View', 'schedspot' ); ?></a>
            </nav>

            <div id="weekly-schedule" class="tab-content active">
                <?php $this->render_weekly_schedule( $schedule ); ?>
            </div>

            <div id="exceptions" class="tab-content">
                <?php $this->render_exceptions_interface( $worker_id, $exceptions ); ?>
            </div>

            <div id="calendar-view" class="tab-content">
                <?php $this->render_calendar_view( $worker_id ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render weekly schedule interface.
     *
     * @since 1.7.0
     * @param array $schedule Current schedule data.
     */
    private function render_weekly_schedule( $schedule ) {
        $days = array(
            'monday' => __( 'Monday', 'schedspot' ),
            'tuesday' => __( 'Tuesday', 'schedspot' ),
            'wednesday' => __( 'Wednesday', 'schedspot' ),
            'thursday' => __( 'Thursday', 'schedspot' ),
            'friday' => __( 'Friday', 'schedspot' ),
            'saturday' => __( 'Saturday', 'schedspot' ),
            'sunday' => __( 'Sunday', 'schedspot' ),
        );

        ?>
        <div class="schedspot-weekly-schedule">
            <div class="schedule-instructions">
                <p><?php _e( 'Set the worker\'s regular weekly availability. You can add multiple time slots for each day.', 'schedspot' ); ?></p>
            </div>

            <?php foreach ( $days as $day_key => $day_name ) : ?>
                <div class="day-schedule" data-day="<?php echo esc_attr( $day_key ); ?>">
                    <div class="day-header">
                        <h3><?php echo esc_html( $day_name ); ?></h3>
                        <label class="day-toggle">
                            <input type="checkbox" class="day-enabled" 
                                   <?php checked( ! empty( $schedule[ $day_key ] ) ); ?>>
                            <?php _e( 'Available', 'schedspot' ); ?>
                        </label>
                    </div>

                    <div class="time-slots" <?php echo empty( $schedule[ $day_key ] ) ? 'style="display: none;"' : ''; ?>>
                        <?php
                        $day_slots = isset( $schedule[ $day_key ] ) ? $schedule[ $day_key ] : array();
                        if ( empty( $day_slots ) ) {
                            $day_slots = array( array( 'start' => '09:00', 'end' => '17:00' ) );
                        }
                        
                        foreach ( $day_slots as $index => $slot ) :
                        ?>
                            <div class="time-slot">
                                <input type="time" class="start-time" 
                                       value="<?php echo esc_attr( $slot['start'] ); ?>" 
                                       name="schedule[<?php echo esc_attr( $day_key ); ?>][<?php echo esc_attr( $index ); ?>][start]">
                                <span class="time-separator"><?php _e( 'to', 'schedspot' ); ?></span>
                                <input type="time" class="end-time" 
                                       value="<?php echo esc_attr( $slot['end'] ); ?>" 
                                       name="schedule[<?php echo esc_attr( $day_key ); ?>][<?php echo esc_attr( $index ); ?>][end]">
                                <button type="button" class="remove-slot button-link-delete">
                                    <?php _e( 'Remove', 'schedspot' ); ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                        
                        <button type="button" class="add-slot button button-secondary">
                            <?php _e( 'Add Time Slot', 'schedspot' ); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render exceptions interface.
     *
     * @since 1.7.0
     * @param int $worker_id Worker ID.
     * @param array $exceptions Current exceptions.
     */
    private function render_exceptions_interface( $worker_id, $exceptions ) {
        ?>
        <div class="schedspot-exceptions">
            <div class="exceptions-header">
                <h3><?php _e( 'Schedule Exceptions', 'schedspot' ); ?></h3>
                <p><?php _e( 'Add one-time availability changes or time off.', 'schedspot' ); ?></p>
            </div>

            <div class="add-exception">
                <h4><?php _e( 'Add New Exception', 'schedspot' ); ?></h4>
                <div class="exception-form">
                    <div class="form-row">
                        <label for="exception-date"><?php _e( 'Date:', 'schedspot' ); ?></label>
                        <input type="date" id="exception-date" min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
                    </div>

                    <div class="form-row">
                        <label for="exception-type"><?php _e( 'Type:', 'schedspot' ); ?></label>
                        <select id="exception-type">
                            <option value="unavailable"><?php _e( 'Unavailable', 'schedspot' ); ?></option>
                            <option value="custom"><?php _e( 'Custom Hours', 'schedspot' ); ?></option>
                        </select>
                    </div>

                    <div class="custom-hours" style="display: none;">
                        <div class="form-row">
                            <label for="exception-start"><?php _e( 'Start Time:', 'schedspot' ); ?></label>
                            <input type="time" id="exception-start">
                        </div>
                        <div class="form-row">
                            <label for="exception-end"><?php _e( 'End Time:', 'schedspot' ); ?></label>
                            <input type="time" id="exception-end">
                        </div>
                    </div>

                    <div class="form-row">
                        <label for="exception-note"><?php _e( 'Note (optional):', 'schedspot' ); ?></label>
                        <input type="text" id="exception-note" placeholder="<?php _e( 'Vacation, sick day, etc.', 'schedspot' ); ?>">
                    </div>

                    <button type="button" id="add-exception" class="button button-primary">
                        <?php _e( 'Add Exception', 'schedspot' ); ?>
                    </button>
                </div>
            </div>

            <div class="exceptions-list">
                <h4><?php _e( 'Current Exceptions', 'schedspot' ); ?></h4>
                <?php if ( empty( $exceptions ) ) : ?>
                    <p class="no-exceptions"><?php _e( 'No schedule exceptions found.', 'schedspot' ); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e( 'Date', 'schedspot' ); ?></th>
                                <th><?php _e( 'Type', 'schedspot' ); ?></th>
                                <th><?php _e( 'Hours', 'schedspot' ); ?></th>
                                <th><?php _e( 'Note', 'schedspot' ); ?></th>
                                <th><?php _e( 'Actions', 'schedspot' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $exceptions as $exception ) : ?>
                                <tr data-exception-id="<?php echo esc_attr( $exception['id'] ); ?>">
                                    <td><?php echo esc_html( date( 'F j, Y', strtotime( $exception['date'] ) ) ); ?></td>
                                    <td><?php echo esc_html( ucfirst( $exception['type'] ) ); ?></td>
                                    <td>
                                        <?php if ( $exception['type'] === 'unavailable' ) : ?>
                                            <?php _e( 'Unavailable', 'schedspot' ); ?>
                                        <?php else : ?>
                                            <?php echo esc_html( $exception['start_time'] . ' - ' . $exception['end_time'] ); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html( $exception['note'] ); ?></td>
                                    <td>
                                        <button type="button" class="remove-exception button-link-delete"
                                                data-exception-id="<?php echo esc_attr( $exception['id'] ); ?>">
                                            <?php _e( 'Remove', 'schedspot' ); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render calendar view.
     *
     * @since 1.7.0
     * @param int $worker_id Worker ID.
     */
    private function render_calendar_view( $worker_id ) {
        ?>
        <div class="schedspot-calendar-view">
            <div class="calendar-header">
                <h3><?php _e( 'Calendar View', 'schedspot' ); ?></h3>
                <p><?php _e( 'Visual representation of the worker\'s schedule and bookings.', 'schedspot' ); ?></p>
            </div>

            <div class="calendar-controls">
                <button type="button" id="prev-month" class="button">&laquo; <?php _e( 'Previous', 'schedspot' ); ?></button>
                <span id="current-month"></span>
                <button type="button" id="next-month" class="button"><?php _e( 'Next', 'schedspot' ); ?> &raquo;</button>
            </div>

            <div id="schedule-calendar">
                <!-- Calendar will be rendered here via JavaScript -->
            </div>

            <div class="calendar-legend">
                <h4><?php _e( 'Legend', 'schedspot' ); ?></h4>
                <div class="legend-items">
                    <div class="legend-item">
                        <span class="legend-color available"></span>
                        <?php _e( 'Available', 'schedspot' ); ?>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color booked"></span>
                        <?php _e( 'Booked', 'schedspot' ); ?>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color unavailable"></span>
                        <?php _e( 'Unavailable', 'schedspot' ); ?>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color exception"></span>
                        <?php _e( 'Exception', 'schedspot' ); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get all workers.
     *
     * @since 1.7.0
     * @return array Workers list.
     */
    private function get_workers() {
        return get_users( array(
            'role' => 'schedspot_worker',
            'orderby' => 'display_name',
            'order' => 'ASC',
        ) );
    }

    /**
     * Check if worker exists.
     *
     * @since 1.7.0
     * @param int $worker_id Worker ID.
     * @return bool Whether worker exists.
     */
    private function worker_exists( $worker_id ) {
        $user = get_userdata( $worker_id );
        return $user && in_array( 'schedspot_worker', $user->roles );
    }

    /**
     * Get worker schedule.
     *
     * @since 1.7.0
     * @param int $worker_id Worker ID.
     * @return array Schedule data.
     */
    private function get_worker_schedule( $worker_id ) {
        $schedule = get_user_meta( $worker_id, 'schedspot_weekly_schedule', true );

        if ( ! is_array( $schedule ) ) {
            // Default schedule: Monday to Friday, 9 AM to 5 PM
            $schedule = array(
                'monday' => array( array( 'start' => '09:00', 'end' => '17:00' ) ),
                'tuesday' => array( array( 'start' => '09:00', 'end' => '17:00' ) ),
                'wednesday' => array( array( 'start' => '09:00', 'end' => '17:00' ) ),
                'thursday' => array( array( 'start' => '09:00', 'end' => '17:00' ) ),
                'friday' => array( array( 'start' => '09:00', 'end' => '17:00' ) ),
            );
        }

        return $schedule;
    }

    /**
     * Get schedule exceptions.
     *
     * @since 1.7.0
     * @param int $worker_id Worker ID.
     * @return array Exceptions data.
     */
    private function get_schedule_exceptions( $worker_id ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'schedspot_schedule_exceptions';

        // Check if table exists, if not return empty array
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
            return array();
        }

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE worker_id = %d AND date >= %s ORDER BY date ASC",
            $worker_id,
            date( 'Y-m-d' )
        ), ARRAY_A );
    }

    /**
     * Handle save worker schedule AJAX request.
     *
     * @since 1.7.0
     */
    public function handle_save_worker_schedule() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_schedule_action' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'schedspot' ) ) );
        }

        $worker_id = intval( $_POST['worker_id'] );
        $schedule = $_POST['schedule'];

        if ( ! $this->worker_exists( $worker_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid worker ID.', 'schedspot' ) ) );
        }

        // Validate and sanitize schedule data
        $clean_schedule = array();
        $days = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );

        foreach ( $days as $day ) {
            if ( isset( $schedule[ $day ] ) && is_array( $schedule[ $day ] ) ) {
                $clean_schedule[ $day ] = array();
                foreach ( $schedule[ $day ] as $slot ) {
                    if ( isset( $slot['start'] ) && isset( $slot['end'] ) ) {
                        $start_time = sanitize_text_field( $slot['start'] );
                        $end_time = sanitize_text_field( $slot['end'] );

                        // Validate time format
                        if ( preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time ) &&
                             preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time ) ) {
                            $clean_schedule[ $day ][] = array(
                                'start' => $start_time,
                                'end' => $end_time,
                            );
                        }
                    }
                }
            }
        }

        // Save schedule
        update_user_meta( $worker_id, 'schedspot_weekly_schedule', $clean_schedule );

        wp_send_json_success( array(
            'message' => __( 'Schedule saved successfully!', 'schedspot' ),
            'schedule' => $clean_schedule
        ) );
    }

    /**
     * Handle get worker schedule AJAX request.
     *
     * @since 1.7.0
     */
    public function handle_get_worker_schedule() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_schedule_action' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'schedspot' ) ) );
        }

        $worker_id = intval( $_POST['worker_id'] );

        if ( ! $this->worker_exists( $worker_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid worker ID.', 'schedspot' ) ) );
        }

        $schedule = $this->get_worker_schedule( $worker_id );
        $exceptions = $this->get_schedule_exceptions( $worker_id );

        wp_send_json_success( array(
            'schedule' => $schedule,
            'exceptions' => $exceptions
        ) );
    }

    /**
     * Handle bulk schedule update AJAX request.
     *
     * @since 1.7.0
     */
    public function handle_bulk_schedule_update() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_schedule_action' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'schedspot' ) ) );
        }

        $worker_ids = array_map( 'intval', $_POST['worker_ids'] );
        $schedule = $_POST['schedule'];

        $updated_count = 0;
        foreach ( $worker_ids as $worker_id ) {
            if ( $this->worker_exists( $worker_id ) ) {
                update_user_meta( $worker_id, 'schedspot_weekly_schedule', $schedule );
                $updated_count++;
            }
        }

        wp_send_json_success( array(
            'message' => sprintf( __( 'Schedule updated for %d workers.', 'schedspot' ), $updated_count )
        ) );
    }

    /**
     * Handle add schedule exception AJAX request.
     *
     * @since 1.7.0
     */
    public function handle_add_schedule_exception() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_schedule_action' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'schedspot' ) ) );
        }

        global $wpdb;

        $worker_id = intval( $_POST['worker_id'] );
        $date = sanitize_text_field( $_POST['date'] );
        $type = sanitize_text_field( $_POST['type'] );
        $start_time = sanitize_text_field( $_POST['start_time'] );
        $end_time = sanitize_text_field( $_POST['end_time'] );
        $note = sanitize_text_field( $_POST['note'] );

        if ( ! $this->worker_exists( $worker_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid worker ID.', 'schedspot' ) ) );
        }

        // Validate date
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid date format.', 'schedspot' ) ) );
        }

        $table_name = $wpdb->prefix . 'schedspot_schedule_exceptions';

        // Create table if it doesn't exist
        $this->create_exceptions_table();

        $result = $wpdb->insert(
            $table_name,
            array(
                'worker_id' => $worker_id,
                'date' => $date,
                'type' => $type,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'note' => $note,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( $result === false ) {
            wp_send_json_error( array( 'message' => __( 'Failed to add exception.', 'schedspot' ) ) );
        }

        wp_send_json_success( array(
            'message' => __( 'Exception added successfully!', 'schedspot' ),
            'exception_id' => $wpdb->insert_id
        ) );
    }

    /**
     * Handle remove schedule exception AJAX request.
     *
     * @since 1.7.0
     */
    public function handle_remove_schedule_exception() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'schedspot_schedule_action' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'schedspot' ) ) );
        }

        global $wpdb;

        $exception_id = intval( $_POST['exception_id'] );
        $table_name = $wpdb->prefix . 'schedspot_schedule_exceptions';

        $result = $wpdb->delete(
            $table_name,
            array( 'id' => $exception_id ),
            array( '%d' )
        );

        if ( $result === false ) {
            wp_send_json_error( array( 'message' => __( 'Failed to remove exception.', 'schedspot' ) ) );
        }

        wp_send_json_success( array(
            'message' => __( 'Exception removed successfully!', 'schedspot' )
        ) );
    }

    /**
     * Create schedule exceptions table.
     *
     * @since 1.7.0
     */
    public function create_exceptions_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'schedspot_schedule_exceptions';

        // Check if table already exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) == $table_name ) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            worker_id bigint(20) NOT NULL,
            date date NOT NULL,
            type varchar(20) NOT NULL DEFAULT 'unavailable',
            start_time time DEFAULT NULL,
            end_time time DEFAULT NULL,
            note text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY worker_id (worker_id),
            KEY date (date),
            UNIQUE KEY worker_date (worker_id, date)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        // Log table creation for debugging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'SchedSpot: Schedule exceptions table created or verified: ' . $table_name );
        }
    }
}
