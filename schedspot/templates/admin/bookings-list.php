<?php
/**
 * Admin Bookings List Template
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'Bookings', 'schedspot' ); ?></h1>
    <a href="<?php echo admin_url( 'admin.php?page=schedspot-bookings&action=add' ); ?>" class="page-title-action">
        <?php _e( 'Add New', 'schedspot' ); ?>
    </a>
    <hr class="wp-header-end">

    <?php settings_errors( 'schedspot_bookings' ); ?>

    <!-- Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="schedspot-bookings">
                
                <select name="status">
                    <option value="all"><?php _e( 'All Statuses', 'schedspot' ); ?></option>
                    <option value="pending" <?php selected( $_GET['status'] ?? '', 'pending' ); ?>><?php _e( 'Pending', 'schedspot' ); ?></option>
                    <option value="confirmed" <?php selected( $_GET['status'] ?? '', 'confirmed' ); ?>><?php _e( 'Confirmed', 'schedspot' ); ?></option>
                    <option value="completed" <?php selected( $_GET['status'] ?? '', 'completed' ); ?>><?php _e( 'Completed', 'schedspot' ); ?></option>
                    <option value="cancelled" <?php selected( $_GET['status'] ?? '', 'cancelled' ); ?>><?php _e( 'Cancelled', 'schedspot' ); ?></option>
                </select>

                <input type="date" name="date_from" value="<?php echo esc_attr( $_GET['date_from'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'From Date', 'schedspot' ); ?>">
                <input type="date" name="date_to" value="<?php echo esc_attr( $_GET['date_to'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'To Date', 'schedspot' ); ?>">

                <input type="search" name="s" value="<?php echo esc_attr( $_GET['s'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Search bookings...', 'schedspot' ); ?>">

                <input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'schedspot' ); ?>">
            </form>
        </div>
    </div>

    <!-- Bookings Table -->
    <form method="post">
        <?php wp_nonce_field( 'bulk-bookings' ); ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-1">
                    </td>
                    <th class="manage-column"><?php _e( 'ID', 'schedspot' ); ?></th>
                    <th class="manage-column"><?php _e( 'Client', 'schedspot' ); ?></th>
                    <th class="manage-column"><?php _e( 'Service', 'schedspot' ); ?></th>
                    <th class="manage-column"><?php _e( 'Worker', 'schedspot' ); ?></th>
                    <th class="manage-column"><?php _e( 'Date & Time', 'schedspot' ); ?></th>
                    <th class="manage-column"><?php _e( 'Status', 'schedspot' ); ?></th>
                    <th class="manage-column"><?php _e( 'Total', 'schedspot' ); ?></th>
                    <th class="manage-column"><?php _e( 'Actions', 'schedspot' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $bookings ) ) : ?>
                    <?php foreach ( $bookings as $booking ) : ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="booking[]" value="<?php echo esc_attr( $booking->id ); ?>">
                            </th>
                            <td><strong>#<?php echo esc_html( $booking->id ); ?></strong></td>
                            <td>
                                <strong><?php echo esc_html( $booking->client_name ); ?></strong><br>
                                <small><?php echo esc_html( $booking->client_email ); ?></small>
                            </td>
                            <td>
                                <?php
                                $service = get_post( $booking->service_id );
                                echo $service ? esc_html( $service->post_title ) : __( 'Unknown Service', 'schedspot' );
                                ?>
                            </td>
                            <td>
                                <?php
                                $worker = get_user_by( 'ID', $booking->worker_id );
                                echo $worker ? esc_html( $worker->display_name ) : __( 'Unassigned', 'schedspot' );
                                ?>
                            </td>
                            <td>
                                <?php echo esc_html( date( 'M j, Y', strtotime( $booking->booking_date ) ) ); ?><br>
                                <small><?php echo esc_html( date( 'g:i A', strtotime( $booking->start_time ) ) ); ?></small>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr( $booking->status ); ?>">
                                    <?php echo esc_html( ucfirst( $booking->status ) ); ?>
                                </span>
                            </td>
                            <td>
                                <strong>$<?php echo number_format( $booking->total_cost, 2 ); ?></strong>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=schedspot-bookings&action=view&booking_id=' . $booking->id ), 'booking_action_' . $booking->id ); ?>">
                                            <?php _e( 'View', 'schedspot' ); ?>
                                        </a> |
                                    </span>
                                    <span class="edit">
                                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=schedspot-bookings&action=edit&booking_id=' . $booking->id ), 'booking_action_' . $booking->id ); ?>">
                                            <?php _e( 'Edit', 'schedspot' ); ?>
                                        </a> |
                                    </span>
                                    <span class="delete">
                                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=schedspot-bookings&action=delete&booking_id=' . $booking->id ), 'booking_action_' . $booking->id ); ?>" 
                                           onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this booking?', 'schedspot' ); ?>')">
                                            <?php _e( 'Delete', 'schedspot' ); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="9" class="no-items"><?php _e( 'No bookings found.', 'schedspot' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Bulk Actions -->
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="action">
                    <option value="-1"><?php _e( 'Bulk Actions', 'schedspot' ); ?></option>
                    <option value="confirm"><?php _e( 'Confirm', 'schedspot' ); ?></option>
                    <option value="complete"><?php _e( 'Complete', 'schedspot' ); ?></option>
                    <option value="cancel"><?php _e( 'Cancel', 'schedspot' ); ?></option>
                    <option value="delete"><?php _e( 'Delete', 'schedspot' ); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'schedspot' ); ?>">
            </div>
        </div>
    </form>

    <!-- Statistics Summary -->
    <div class="schedspot-stats-summary">
        <h3><?php _e( 'Booking Statistics', 'schedspot' ); ?></h3>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-number"><?php echo esc_html( $total_bookings ); ?></span>
                <span class="stat-label"><?php _e( 'Total Bookings', 'schedspot' ); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number">
                    <?php 
                    $pending_count = count( array_filter( $bookings, function( $b ) { return $b->status === 'pending'; } ) );
                    echo esc_html( $pending_count );
                    ?>
                </span>
                <span class="stat-label"><?php _e( 'Pending', 'schedspot' ); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number">
                    <?php 
                    $confirmed_count = count( array_filter( $bookings, function( $b ) { return $b->status === 'confirmed'; } ) );
                    echo esc_html( $confirmed_count );
                    ?>
                </span>
                <span class="stat-label"><?php _e( 'Confirmed', 'schedspot' ); ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-number">
                    <?php 
                    $total_revenue = array_sum( array_map( function( $b ) { 
                        return in_array( $b->status, array( 'confirmed', 'completed' ) ) ? $b->total_cost : 0; 
                    }, $bookings ) );
                    echo '$' . number_format( $total_revenue, 2 );
                    ?>
                </span>
                <span class="stat-label"><?php _e( 'Total Revenue', 'schedspot' ); ?></span>
            </div>
        </div>
    </div>
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
.status-confirmed { background: #d1ecf1; color: #0c5460; }
.status-completed { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.schedspot-stats-summary {
    margin-top: 20px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: #0073aa;
}

.stat-label {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
}

.tablenav .actions {
    padding: 2px 0;
}

.tablenav .actions input,
.tablenav .actions select {
    margin-right: 6px;
}
</style>
