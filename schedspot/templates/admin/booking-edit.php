<?php
/**
 * Admin Booking Edit Template
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get booking data
$booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;
$booking = SchedSpot_Booking::get_booking_by_id( $booking_id );

if ( ! $booking ) {
    wp_die( __( 'Booking not found.', 'schedspot' ) );
}

// Get related data
$client = get_userdata( $booking->user_id );
$worker = get_userdata( $booking->worker_id );
$service = null;

if ( $booking->service_id ) {
    global $wpdb;
    $service = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}schedspot_services WHERE id = %d",
        $booking->service_id
    ) );
}

// Get all services and workers for dropdowns
$all_services = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}schedspot_services WHERE is_active = 1 ORDER BY name ASC" );
$all_workers = get_users( array( 'role' => 'schedspot_worker' ) );

$client_details = is_string( $booking->client_details ) ? json_decode( $booking->client_details, true ) : $booking->client_details;
if ( ! is_array( $client_details ) ) {
    $client_details = array();
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php printf( __( 'Edit Booking #%d', 'schedspot' ), $booking->id ); ?>
    </h1>
    <hr class="wp-header-end">

    <form method="post" action="" class="schedspot-booking-edit-form">
        <?php wp_nonce_field( 'schedspot_update_booking', 'schedspot_booking_nonce' ); ?>
        <input type="hidden" name="booking_id" value="<?php echo esc_attr( $booking->id ); ?>">
        <input type="hidden" name="update_booking" value="1">

        <div class="booking-edit-grid">
            <!-- Booking Details -->
            <div class="booking-details-section">
                <h2><?php _e( 'Booking Details', 'schedspot' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="booking_status"><?php _e( 'Status', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <select name="status" id="booking_status" class="regular-text">
                                <option value="pending" <?php selected( $booking->status, 'pending' ); ?>><?php _e( 'Pending', 'schedspot' ); ?></option>
                                <option value="confirmed" <?php selected( $booking->status, 'confirmed' ); ?>><?php _e( 'Confirmed', 'schedspot' ); ?></option>
                                <option value="completed" <?php selected( $booking->status, 'completed' ); ?>><?php _e( 'Completed', 'schedspot' ); ?></option>
                                <option value="cancelled" <?php selected( $booking->status, 'cancelled' ); ?>><?php _e( 'Cancelled', 'schedspot' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="service_id"><?php _e( 'Service', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <select name="service_id" id="service_id" class="regular-text">
                                <option value=""><?php _e( 'No service specified', 'schedspot' ); ?></option>
                                <?php foreach ( $all_services as $svc ) : ?>
                                    <option value="<?php echo esc_attr( $svc->id ); ?>" <?php selected( $booking->service_id, $svc->id ); ?>>
                                        <?php echo esc_html( $svc->name ); ?> - $<?php echo esc_html( number_format( $svc->base_price, 2 ) ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="worker_id"><?php _e( 'Worker', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <select name="worker_id" id="worker_id" class="regular-text">
                                <option value=""><?php _e( 'No worker assigned', 'schedspot' ); ?></option>
                                <?php foreach ( $all_workers as $wrk ) : ?>
                                    <option value="<?php echo esc_attr( $wrk->ID ); ?>" <?php selected( $booking->worker_id, $wrk->ID ); ?>>
                                        <?php echo esc_html( $wrk->display_name ); ?> (<?php echo esc_html( $wrk->user_email ); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="booking_date"><?php _e( 'Date', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <input type="date" name="booking_date" id="booking_date" value="<?php echo esc_attr( $booking->booking_date ); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="start_time"><?php _e( 'Start Time', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <input type="time" name="start_time" id="start_time" value="<?php echo esc_attr( $booking->start_time ); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="end_time"><?php _e( 'End Time', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <input type="time" name="end_time" id="end_time" value="<?php echo esc_attr( $booking->end_time ); ?>" class="regular-text">
                            <p class="description"><?php _e( 'Leave empty to calculate from duration', 'schedspot' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="duration"><?php _e( 'Duration (minutes)', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <input type="number" name="duration" id="duration" value="<?php echo esc_attr( $booking->duration ); ?>" class="regular-text" min="15" step="15">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="total_cost"><?php _e( 'Total Cost', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <input type="number" name="total_cost" id="total_cost" value="<?php echo esc_attr( $booking->total_cost ); ?>" class="regular-text" min="0" step="0.01">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="deposit_amount"><?php _e( 'Deposit Amount', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <input type="number" name="deposit_amount" id="deposit_amount" value="<?php echo esc_attr( $booking->deposit_amount ); ?>" class="regular-text" min="0" step="0.01">
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Client Information -->
            <div class="client-info-section">
                <h2><?php _e( 'Client Information', 'schedspot' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="client_name"><?php _e( 'Name', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <input type="text" name="client_details[name]" id="client_name" value="<?php echo esc_attr( $client_details['name'] ?? '' ); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="client_email"><?php _e( 'Email', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <input type="email" name="client_details[email]" id="client_email" value="<?php echo esc_attr( $client_details['email'] ?? '' ); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="client_phone"><?php _e( 'Phone', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <input type="tel" name="client_details[phone]" id="client_phone" value="<?php echo esc_attr( $client_details['phone'] ?? '' ); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="client_address"><?php _e( 'Address', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <textarea name="client_details[address]" id="client_address" class="large-text" rows="3"><?php echo esc_textarea( $client_details['address'] ?? '' ); ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Notes -->
            <div class="booking-notes-section">
                <h2><?php _e( 'Notes', 'schedspot' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="booking_notes"><?php _e( 'Booking Notes', 'schedspot' ); ?></label>
                        </th>
                        <td>
                            <textarea name="notes" id="booking_notes" class="large-text" rows="5"><?php echo esc_textarea( $booking->notes ); ?></textarea>
                            <p class="description"><?php _e( 'Internal notes about this booking', 'schedspot' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Submit Buttons -->
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Update Booking', 'schedspot' ); ?>">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=schedspot-bookings&action=view&booking_id=' . $booking->id ) ); ?>" class="button">
                <?php _e( 'View Details', 'schedspot' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=schedspot-bookings' ) ); ?>" class="button">
                <?php _e( 'Back to Bookings', 'schedspot' ); ?>
            </a>
        </p>
    </form>
</div>

<style>
.schedspot-booking-edit-form {
    max-width: 1200px;
}

.booking-edit-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 20px;
}

.booking-details-section,
.client-info-section,
.booking-notes-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.booking-notes-section {
    grid-column: 1 / -1;
}

.form-table th {
    width: 150px;
    padding: 15px 10px 15px 0;
}

.form-table td {
    padding: 15px 10px;
}

@media (max-width: 768px) {
    .booking-edit-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Auto-calculate end time when duration changes
    $('#duration').on('change', function() {
        var startTime = $('#start_time').val();
        var duration = parseInt($(this).val());
        
        if (startTime && duration) {
            var start = new Date('1970-01-01T' + startTime + ':00');
            var end = new Date(start.getTime() + duration * 60000);
            var endTime = end.toTimeString().slice(0, 5);
            $('#end_time').val(endTime);
        }
    });

    // Auto-calculate duration when end time changes
    $('#end_time').on('change', function() {
        var startTime = $('#start_time').val();
        var endTime = $(this).val();
        
        if (startTime && endTime) {
            var start = new Date('1970-01-01T' + startTime + ':00');
            var end = new Date('1970-01-01T' + endTime + ':00');
            var duration = (end - start) / 60000;
            
            if (duration > 0) {
                $('#duration').val(duration);
            }
        }
    });
});
</script>
