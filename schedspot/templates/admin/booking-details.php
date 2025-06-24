<?php
/**
 * Admin Booking Details Template
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

$client_details = is_string( $booking->client_details ) ? json_decode( $booking->client_details, true ) : $booking->client_details;
if ( ! is_array( $client_details ) ) {
    $client_details = array();
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php printf( __( 'Booking Details #%d', 'schedspot' ), $booking->id ); ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=schedspot-bookings&action=edit&booking_id=' . $booking->id ) ); ?>" class="page-title-action">
            <?php _e( 'Edit Booking', 'schedspot' ); ?>
        </a>
    </h1>
    <hr class="wp-header-end">

    <div class="schedspot-booking-details">
        <div class="booking-details-grid">
            <!-- Booking Information -->
            <div class="booking-info-card">
                <h2><?php _e( 'Booking Information', 'schedspot' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e( 'Booking ID', 'schedspot' ); ?></th>
                        <td><strong>#<?php echo esc_html( $booking->id ); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Status', 'schedspot' ); ?></th>
                        <td>
                            <span class="booking-status status-<?php echo esc_attr( $booking->status ); ?>">
                                <?php echo esc_html( ucfirst( $booking->status ) ); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Service', 'schedspot' ); ?></th>
                        <td><?php echo $service ? esc_html( $service->name ) : __( 'No service specified', 'schedspot' ); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Date & Time', 'schedspot' ); ?></th>
                        <td>
                            <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->booking_date ) ) ); ?>
                            <?php _e( 'at', 'schedspot' ); ?>
                            <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking->start_time ) ) ); ?>
                            <?php if ( $booking->end_time ) : ?>
                                - <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking->end_time ) ) ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Duration', 'schedspot' ); ?></th>
                        <td><?php echo esc_html( $booking->duration ); ?> <?php _e( 'minutes', 'schedspot' ); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Total Cost', 'schedspot' ); ?></th>
                        <td><strong>$<?php echo esc_html( number_format( $booking->total_cost, 2 ) ); ?></strong></td>
                    </tr>
                    <?php if ( $booking->deposit_amount > 0 ) : ?>
                    <tr>
                        <th><?php _e( 'Deposit Amount', 'schedspot' ); ?></th>
                        <td>$<?php echo esc_html( number_format( $booking->deposit_amount, 2 ) ); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php _e( 'Created', 'schedspot' ); ?></th>
                        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $booking->created_at ) ) ); ?></td>
                    </tr>
                    <?php if ( $booking->updated_at && $booking->updated_at !== $booking->created_at ) : ?>
                    <tr>
                        <th><?php _e( 'Last Updated', 'schedspot' ); ?></th>
                        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $booking->updated_at ) ) ); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Client Information -->
            <div class="client-info-card">
                <h2><?php _e( 'Client Information', 'schedspot' ); ?></h2>
                <table class="form-table">
                    <?php if ( $client ) : ?>
                    <tr>
                        <th><?php _e( 'User Account', 'schedspot' ); ?></th>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $client->ID ) ); ?>">
                                <?php echo esc_html( $client->display_name ); ?> (<?php echo esc_html( $client->user_email ); ?>)
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php _e( 'Name', 'schedspot' ); ?></th>
                        <td><?php echo esc_html( $client_details['name'] ?? __( 'Not provided', 'schedspot' ) ); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Email', 'schedspot' ); ?></th>
                        <td>
                            <?php if ( ! empty( $client_details['email'] ) ) : ?>
                                <a href="mailto:<?php echo esc_attr( $client_details['email'] ); ?>">
                                    <?php echo esc_html( $client_details['email'] ); ?>
                                </a>
                            <?php else : ?>
                                <?php _e( 'Not provided', 'schedspot' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Phone', 'schedspot' ); ?></th>
                        <td>
                            <?php if ( ! empty( $client_details['phone'] ) ) : ?>
                                <a href="tel:<?php echo esc_attr( $client_details['phone'] ); ?>">
                                    <?php echo esc_html( $client_details['phone'] ); ?>
                                </a>
                            <?php else : ?>
                                <?php _e( 'Not provided', 'schedspot' ); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ( ! empty( $client_details['address'] ) ) : ?>
                    <tr>
                        <th><?php _e( 'Address', 'schedspot' ); ?></th>
                        <td><?php echo esc_html( $client_details['address'] ); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Worker Information -->
            <div class="worker-info-card">
                <h2><?php _e( 'Worker Information', 'schedspot' ); ?></h2>
                <table class="form-table">
                    <?php if ( $worker ) : ?>
                    <tr>
                        <th><?php _e( 'Worker', 'schedspot' ); ?></th>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $worker->ID ) ); ?>">
                                <?php echo esc_html( $worker->display_name ); ?> (<?php echo esc_html( $worker->user_email ); ?>)
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Worker Profile', 'schedspot' ); ?></th>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=schedspot-workers&action=edit&worker_id=' . $worker->ID ) ); ?>">
                                <?php _e( 'View Worker Profile', 'schedspot' ); ?>
                            </a>
                        </td>
                    </tr>
                    <?php else : ?>
                    <tr>
                        <th><?php _e( 'Worker', 'schedspot' ); ?></th>
                        <td><?php _e( 'No worker assigned', 'schedspot' ); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Notes -->
            <?php if ( $booking->notes ) : ?>
            <div class="booking-notes-card">
                <h2><?php _e( 'Notes', 'schedspot' ); ?></h2>
                <div class="booking-notes-content">
                    <?php echo wp_kses_post( wpautop( $booking->notes ) ); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Payment Management Section -->
        <div class="payment-management-section">
            <h2><?php _e( 'Payment Management', 'schedspot' ); ?></h2>

            <div class="payment-status-card">
                <?php
                $payment_status = get_post_meta( $booking->id, 'schedspot_payment_status', true ) ?: 'pending';
                $deposit_requested = get_post_meta( $booking->id, 'schedspot_deposit_requested', true );
                $deposit_paid = get_post_meta( $booking->id, 'schedspot_deposit_paid', true );
                ?>

                <div class="payment-info">
                    <div class="payment-row">
                        <span class="label"><?php _e( 'Payment Status:', 'schedspot' ); ?></span>
                        <span class="payment-status payment-<?php echo esc_attr( $payment_status ); ?>">
                            <?php echo esc_html( ucfirst( $payment_status ) ); ?>
                        </span>
                    </div>

                    <div class="payment-row">
                        <span class="label"><?php _e( 'Total Amount:', 'schedspot' ); ?></span>
                        <span class="amount">$<?php echo esc_html( number_format( $booking->total_cost, 2 ) ); ?></span>
                    </div>

                    <?php if ( $booking->deposit_amount > 0 ) : ?>
                    <div class="payment-row">
                        <span class="label"><?php _e( 'Deposit Amount:', 'schedspot' ); ?></span>
                        <span class="amount">$<?php echo esc_html( number_format( $booking->deposit_amount, 2 ) ); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="payment-actions">
                    <?php if ( ! $deposit_requested && $booking->status === 'confirmed' ) : ?>
                        <button type="button" class="button button-secondary" onclick="requestDeposit(<?php echo esc_attr( $booking->id ); ?>)">
                            <?php _e( 'Request Deposit', 'schedspot' ); ?>
                        </button>
                    <?php endif; ?>

                    <?php if ( $deposit_requested && ! $deposit_paid ) : ?>
                        <button type="button" class="button button-secondary" onclick="markDepositPaid(<?php echo esc_attr( $booking->id ); ?>)">
                            <?php _e( 'Mark Deposit as Paid', 'schedspot' ); ?>
                        </button>
                    <?php endif; ?>

                    <?php if ( $payment_status !== 'completed' && $booking->status === 'completed' ) : ?>
                        <button type="button" class="button button-primary" onclick="requestFinalPayment(<?php echo esc_attr( $booking->id ); ?>)">
                            <?php _e( 'Request Final Payment', 'schedspot' ); ?>
                        </button>
                    <?php endif; ?>

                    <button type="button" class="button" onclick="generateInvoice(<?php echo esc_attr( $booking->id ); ?>)">
                        <?php _e( 'Generate Invoice', 'schedspot' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="booking-actions">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=schedspot-bookings&action=edit&booking_id=' . $booking->id ) ); ?>" class="button button-primary">
                <?php _e( 'Edit Booking', 'schedspot' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=schedspot-bookings' ) ); ?>" class="button">
                <?php _e( 'Back to Bookings', 'schedspot' ); ?>
            </a>
            <?php if ( $booking->status === 'pending' ) : ?>
                <button type="button" class="button button-secondary" onclick="updateBookingStatus(<?php echo esc_attr( $booking->id ); ?>, 'confirmed')">
                    <?php _e( 'Confirm Booking', 'schedspot' ); ?>
                </button>
                <button type="button" class="button button-secondary" onclick="updateBookingStatus(<?php echo esc_attr( $booking->id ); ?>, 'cancelled')">
                    <?php _e( 'Cancel Booking', 'schedspot' ); ?>
                </button>
            <?php elseif ( $booking->status === 'confirmed' ) : ?>
                <button type="button" class="button button-secondary" onclick="updateBookingStatus(<?php echo esc_attr( $booking->id ); ?>, 'completed')">
                    <?php _e( 'Mark Completed', 'schedspot' ); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.schedspot-booking-details {
    max-width: 1200px;
}

.booking-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.booking-info-card,
.client-info-card,
.worker-info-card,
.booking-notes-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.booking-notes-card {
    grid-column: 1 / -1;
}

.booking-status {
    padding: 4px 8px;
    border-radius: 3px;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 11px;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #d4edda; color: #155724; }
.status-completed { background: #d1ecf1; color: #0c5460; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.booking-actions {
    padding: 20px 0;
    border-top: 1px solid #ccd0d4;
}

.booking-actions .button {
    margin-right: 10px;
}

/* Payment Management Styles */
.payment-management-section {
    margin: 30px 0;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.payment-status-card {
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 6px;
    padding: 20px;
}

.payment-info {
    margin-bottom: 20px;
}

.payment-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.payment-row:last-child {
    border-bottom: none;
}

.payment-row .label {
    font-weight: 600;
    color: #333;
}

.payment-row .amount {
    font-weight: 700;
    color: #0073aa;
    font-size: 16px;
}

.payment-status {
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.payment-pending { background: #fff3cd; color: #856404; }
.payment-partial { background: #d1ecf1; color: #0c5460; }
.payment-completed { background: #d4edda; color: #155724; }
.payment-failed { background: #f8d7da; color: #721c24; }

.payment-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.payment-actions .button {
    margin: 0;
}

@media (max-width: 768px) {
    .booking-details-grid {
        grid-template-columns: 1fr;
    }

    .payment-actions {
        flex-direction: column;
    }

    .payment-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}
</style>

<script>
function updateBookingStatus(bookingId, status) {
    if (confirm('<?php _e( 'Are you sure you want to update this booking status?', 'schedspot' ); ?>')) {
        // Implementation for status update via AJAX
        window.location.href = '<?php echo admin_url( 'admin.php?page=schedspot-bookings&action=update_status&booking_id=' ); ?>' + bookingId + '&status=' + status + '&_wpnonce=<?php echo wp_create_nonce( 'schedspot_update_booking' ); ?>';
    }
}

function requestDeposit(bookingId) {
    if (confirm('<?php _e( 'Request deposit payment from client?', 'schedspot' ); ?>')) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'schedspot_request_deposit',
                booking_id: bookingId,
                nonce: '<?php echo wp_create_nonce( 'schedspot_payment_action' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e( 'Deposit request sent successfully!', 'schedspot' ); ?>');
                    location.reload();
                } else {
                    alert('<?php _e( 'Error: ', 'schedspot' ); ?>' + response.data.message);
                }
            },
            error: function() {
                alert('<?php _e( 'An error occurred. Please try again.', 'schedspot' ); ?>');
            }
        });
    }
}

function markDepositPaid(bookingId) {
    if (confirm('<?php _e( 'Mark deposit as paid?', 'schedspot' ); ?>')) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'schedspot_mark_deposit_paid',
                booking_id: bookingId,
                nonce: '<?php echo wp_create_nonce( 'schedspot_payment_action' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e( 'Deposit marked as paid!', 'schedspot' ); ?>');
                    location.reload();
                } else {
                    alert('<?php _e( 'Error: ', 'schedspot' ); ?>' + response.data.message);
                }
            },
            error: function() {
                alert('<?php _e( 'An error occurred. Please try again.', 'schedspot' ); ?>');
            }
        });
    }
}

function requestFinalPayment(bookingId) {
    if (confirm('<?php _e( 'Request final payment from client?', 'schedspot' ); ?>')) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'schedspot_request_final_payment',
                booking_id: bookingId,
                nonce: '<?php echo wp_create_nonce( 'schedspot_payment_action' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e( 'Final payment request sent successfully!', 'schedspot' ); ?>');
                    location.reload();
                } else {
                    alert('<?php _e( 'Error: ', 'schedspot' ); ?>' + response.data.message);
                }
            },
            error: function() {
                alert('<?php _e( 'An error occurred. Please try again.', 'schedspot' ); ?>');
            }
        });
    }
}

function generateInvoice(bookingId) {
    window.open('<?php echo admin_url( 'admin.php?page=schedspot-bookings&action=generate_invoice&booking_id=' ); ?>' + bookingId + '&_wpnonce=<?php echo wp_create_nonce( 'schedspot_generate_invoice' ); ?>', '_blank');
}
</script>
