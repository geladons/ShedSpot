<?php
/**
 * SchedSpot Admin Bookings Management
 *
 * Handles all booking-related admin functionality
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Admin_Bookings Class.
 *
 * @class SchedSpot_Admin_Bookings
 * @version 1.0.0
 */
class SchedSpot_Admin_Bookings {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize bookings admin functionality.
     *
     * @since 1.0.0
     */
    public function init() {
        // No hooks needed here - this class is called by SchedSpot_Admin
    }

    /**
     * Bookings page callback.
     *
     * @since 1.0.0
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
     * Render bookings list.
     *
     * @since 1.0.0
     */
    private function render_bookings_list() {
        $bookings = SchedSpot_Booking::get_bookings( array( 'limit' => 50 ) );
        ?>
        <div class="wrap">
            <h1><?php _e( 'Bookings', 'schedspot' ); ?>
                <a href="<?php echo admin_url( 'admin.php?page=schedspot-bookings&action=add' ); ?>" class="page-title-action"><?php _e( 'Add New', 'schedspot' ); ?></a>
            </h1>

            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1"><?php _e( 'Bulk Actions', 'schedspot' ); ?></option>
                        <option value="delete"><?php _e( 'Delete', 'schedspot' ); ?></option>
                        <option value="approve"><?php _e( 'Approve', 'schedspot' ); ?></option>
                        <option value="cancel"><?php _e( 'Cancel', 'schedspot' ); ?></option>
                    </select>
                    <input type="submit" id="doaction" class="button action" value="<?php _e( 'Apply', 'schedspot' ); ?>">
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th scope="col" class="manage-column column-id"><?php _e( 'ID', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-client"><?php _e( 'Client', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-worker"><?php _e( 'Worker', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-service"><?php _e( 'Service', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-date"><?php _e( 'Date & Time', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-status"><?php _e( 'Status', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-cost"><?php _e( 'Cost', 'schedspot' ); ?></th>
                        <th scope="col" class="manage-column column-actions"><?php _e( 'Actions', 'schedspot' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $bookings ) ) : ?>
                        <tr>
                            <td colspan="9" class="no-items"><?php _e( 'No bookings found.', 'schedspot' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $bookings as $booking ) : ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="booking[]" value="<?php echo esc_attr( $booking->id ); ?>">
                                </th>
                                <td class="column-id">
                                    <strong><?php echo esc_html( $booking->id ); ?></strong>
                                </td>
                                <td class="column-client">
                                    <?php echo esc_html( $booking->client_name ?: $booking->get_client_name() ); ?>
                                    <br><small><?php echo esc_html( $booking->client_email ); ?></small>
                                </td>
                                <td class="column-worker">
                                    <?php echo esc_html( $booking->get_worker_name() ); ?>
                                </td>
                                <td class="column-service">
                                    <?php echo esc_html( $booking->get_service_name() ); ?>
                                </td>
                                <td class="column-date">
                                    <?php echo esc_html( date( 'M j, Y', strtotime( $booking->booking_date ) ) ); ?>
                                    <br><small><?php echo esc_html( $booking->start_time . ' - ' . $booking->end_time ); ?></small>
                                </td>
                                <td class="column-status">
                                    <span class="status-badge status-<?php echo esc_attr( $booking->status ); ?>">
                                        <?php echo esc_html( ucfirst( $booking->status ) ); ?>
                                    </span>
                                </td>
                                <td class="column-cost">
                                    <?php echo esc_html( '$' . number_format( $booking->total_cost, 2 ) ); ?>
                                </td>
                                <td class="column-actions">
                                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-bookings&action=view&booking_id=' . $booking->id ); ?>" class="button button-small"><?php _e( 'View', 'schedspot' ); ?></a>
                                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-bookings&action=edit&booking_id=' . $booking->id ); ?>" class="button button-small"><?php _e( 'Edit', 'schedspot' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-in-progress { background: #cce5ff; color: #004085; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        </style>
        <?php
    }

    /**
     * Render booking details.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     */
    private function render_booking_details( $booking_id ) {
        $booking = new SchedSpot_Booking( $booking_id );
        
        if ( ! $booking->id ) {
            wp_die( __( 'Booking not found.', 'schedspot' ) );
        }
        ?>
        <div class="wrap">
            <h1><?php printf( __( 'Booking #%d Details', 'schedspot' ), $booking->id ); ?>
                <a href="<?php echo admin_url( 'admin.php?page=schedspot-bookings&action=edit&booking_id=' . $booking->id ); ?>" class="page-title-action"><?php _e( 'Edit', 'schedspot' ); ?></a>
            </h1>

            <div class="booking-details-container">
                <div class="booking-info-grid">
                    <div class="booking-section">
                        <h3><?php _e( 'Booking Information', 'schedspot' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><?php _e( 'Booking ID', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $booking->id ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Status', 'schedspot' ); ?></th>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr( $booking->status ); ?>">
                                        <?php echo esc_html( ucfirst( $booking->status ) ); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Service', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $booking->get_service_name() ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Worker', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $booking->get_worker_name() ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Date & Time', 'schedspot' ); ?></th>
                                <td>
                                    <?php echo esc_html( date( 'F j, Y', strtotime( $booking->booking_date ) ) ); ?>
                                    <br><?php echo esc_html( $booking->start_time . ' - ' . $booking->end_time ); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Duration', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $booking->duration . ' minutes' ); ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="booking-section">
                        <h3><?php _e( 'Client Information', 'schedspot' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><?php _e( 'Name', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $booking->client_name ?: $booking->get_client_name() ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Email', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $booking->client_email ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Phone', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $booking->client_phone ?: __( 'Not provided', 'schedspot' ) ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Address', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( $booking->client_address ?: __( 'Not provided', 'schedspot' ) ); ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="booking-section">
                        <h3><?php _e( 'Payment Information', 'schedspot' ); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><?php _e( 'Total Cost', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( '$' . number_format( $booking->total_cost, 2 ) ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Deposit', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( '$' . number_format( $booking->deposit_amount, 2 ) ); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Commission', 'schedspot' ); ?></th>
                                <td><?php echo esc_html( '$' . number_format( $booking->commission_amount, 2 ) ); ?></td>
                            </tr>
                        </table>
                    </div>

                    <?php if ( $booking->notes ) : ?>
                    <div class="booking-section">
                        <h3><?php _e( 'Notes', 'schedspot' ); ?></h3>
                        <div class="booking-notes">
                            <?php echo wp_kses_post( wpautop( $booking->notes ) ); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="booking-actions">
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-bookings' ); ?>" class="button"><?php _e( 'Back to Bookings', 'schedspot' ); ?></a>
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-bookings&action=edit&booking_id=' . $booking->id ); ?>" class="button button-primary"><?php _e( 'Edit Booking', 'schedspot' ); ?></a>
                </div>
            </div>
        </div>

        <style>
        .booking-details-container {
            max-width: 1200px;
        }
        .booking-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .booking-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        .booking-section h3 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .booking-notes {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #0073aa;
        }
        .booking-actions {
            padding: 20px 0;
            border-top: 1px solid #ccd0d4;
        }
        .booking-actions .button {
            margin-right: 10px;
        }
        </style>
        <?php
    }

    /**
     * Handle booking form submission.
     *
     * @since 1.0.0
     */
    private function handle_booking_form_submission() {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'schedspot_booking_action' ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $action = sanitize_text_field( $_POST['schedspot_booking_action'] );
        $booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;

        switch ( $action ) {
            case 'update':
                $this->update_booking( $booking_id );
                break;
            case 'delete':
                $this->delete_booking( $booking_id );
                break;
        }
    }

    /**
     * Update booking.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     */
    private function update_booking( $booking_id ) {
        $booking = new SchedSpot_Booking( $booking_id );
        
        if ( ! $booking->id ) {
            wp_die( __( 'Booking not found.', 'schedspot' ) );
        }

        // Update booking data
        $update_data = array(
            'worker_id' => absint( $_POST['worker_id'] ),
            'service_id' => absint( $_POST['service_id'] ),
            'booking_date' => sanitize_text_field( $_POST['booking_date'] ),
            'start_time' => sanitize_text_field( $_POST['start_time'] ),
            'end_time' => sanitize_text_field( $_POST['end_time'] ),
            'duration' => absint( $_POST['duration'] ),
            'status' => sanitize_text_field( $_POST['status'] ),
            'total_cost' => floatval( $_POST['total_cost'] ),
            'deposit_amount' => floatval( $_POST['deposit_amount'] ),
            'commission_amount' => floatval( $_POST['commission_amount'] ),
            'client_name' => sanitize_text_field( $_POST['client_name'] ),
            'client_email' => sanitize_email( $_POST['client_email'] ),
            'client_phone' => sanitize_text_field( $_POST['client_phone'] ),
            'client_address' => sanitize_textarea_field( $_POST['client_address'] ),
            'notes' => sanitize_textarea_field( $_POST['notes'] ),
        );

        if ( $booking->update( $update_data ) ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-bookings&action=view&booking_id=' . $booking_id . '&updated=1' ) );
            exit;
        } else {
            wp_redirect( admin_url( 'admin.php?page=schedspot-bookings&action=edit&booking_id=' . $booking_id . '&error=1' ) );
            exit;
        }
    }

    /**
     * Delete booking.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     */
    private function delete_booking( $booking_id ) {
        $booking = new SchedSpot_Booking( $booking_id );
        
        if ( ! $booking->id ) {
            wp_die( __( 'Booking not found.', 'schedspot' ) );
        }

        if ( $booking->delete() ) {
            wp_redirect( admin_url( 'admin.php?page=schedspot-bookings&deleted=1' ) );
            exit;
        } else {
            wp_redirect( admin_url( 'admin.php?page=schedspot-bookings&error=1' ) );
            exit;
        }
    }

    /**
     * Handle delete booking action.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     */
    private function handle_delete_booking( $booking_id ) {
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'delete_booking_' . $booking_id ) ) {
            wp_die( __( 'Security check failed.', 'schedspot' ) );
        }

        $this->delete_booking( $booking_id );
    }
}
