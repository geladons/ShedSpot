<?php
/**
 * Customer Dashboard Template
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="schedspot-dashboard-container">
    <!-- Navigation Bar -->
    <div class="schedspot-navigation">
        <div class="schedspot-nav-links">
            <a href="<?php echo esc_url( home_url( '/?schedspot_action=booking_form' ) ); ?>" class="schedspot-nav-link">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e( 'Book a Service', 'schedspot' ); ?>
            </a>
            <a href="<?php echo esc_url( home_url( '/?schedspot_action=dashboard' ) ); ?>" class="schedspot-nav-link active">
                <span class="dashicons dashicons-list-view"></span>
                <?php _e( 'My Bookings', 'schedspot' ); ?>
            </a>
            <a href="<?php echo esc_url( home_url( '/?schedspot_action=messages' ) ); ?>" class="schedspot-nav-link">
                <span class="dashicons dashicons-email-alt"></span>
                <?php _e( 'Messages', 'schedspot' ); ?>
            </a>
            <a href="<?php echo esc_url( home_url( '/?schedspot_action=profile' ) ); ?>" class="schedspot-nav-link">
                <span class="dashicons dashicons-admin-users"></span>
                <?php _e( 'Profile', 'schedspot' ); ?>
            </a>
        </div>
    </div>

    <div class="schedspot-dashboard schedspot-customer-dashboard">
        <div class="schedspot-dashboard-header">
            <h2><?php _e( 'Customer Dashboard', 'schedspot' ); ?></h2>
            <p class="user-welcome"><?php printf( __( 'Welcome back, %s!', 'schedspot' ), esc_html( $current_user->display_name ) ); ?></p>
            <span class="user-role"><?php _e( 'Customer', 'schedspot' ); ?></span>
        </div>

    <div class="dashboard-navigation">
        <nav class="dashboard-nav">
            <a href="#bookings" class="nav-item active" data-tab="bookings">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e( 'My Bookings', 'schedspot' ); ?>
            </a>
            <a href="#messages" class="nav-item" data-tab="messages">
                <span class="dashicons dashicons-email"></span>
                <?php _e( 'Messages', 'schedspot' ); ?>
                <?php if ( $dashboard_data['stats']['unread_messages'] > 0 ) : ?>
                    <span class="notification-badge"><?php echo esc_html( $dashboard_data['stats']['unread_messages'] ); ?></span>
                <?php endif; ?>
            </a>
            <a href="#profile" class="nav-item" data-tab="profile">
                <span class="dashicons dashicons-admin-users"></span>
                <?php _e( 'Profile', 'schedspot' ); ?>
            </a>
        </nav>
    </div>

    <div class="dashboard-content">
        <!-- Bookings Tab -->
        <div id="bookings" class="dashboard-tab active">
            <!-- Quick Stats -->
            <div class="schedspot-stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="stat-number"><?php echo esc_html( $dashboard_data['stats']['total_bookings'] ); ?></div>
                    <div class="stat-label"><?php _e( 'Total Bookings', 'schedspot' ); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="stat-number"><?php echo esc_html( $dashboard_data['stats']['pending_bookings'] ); ?></div>
                    <div class="stat-label"><?php _e( 'Pending', 'schedspot' ); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-yes"></span>
                    </div>
                    <div class="stat-number"><?php echo esc_html( $dashboard_data['stats']['confirmed_bookings'] ); ?></div>
                    <div class="stat-label"><?php _e( 'Confirmed', 'schedspot' ); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="stat-number">$<?php echo number_format( $dashboard_data['stats']['total_spent'], 2 ); ?></div>
                    <div class="stat-label"><?php _e( 'Total Spent', 'schedspot' ); ?></div>
                </div>
            </div>

            <div class="bookings-section">
                <div class="section-header">
                    <h3><?php _e( 'Recent Bookings', 'schedspot' ); ?></h3>
                    <a href="<?php echo esc_url( add_query_arg( 'schedspot_action', 'booking_form' ) ); ?>" class="schedspot-btn schedspot-btn-primary">
                        <?php _e( 'Book New Service', 'schedspot' ); ?>
                    </a>
                </div>

                <?php if ( ! empty( $dashboard_data['recent_bookings'] ) ) : ?>
                    <div class="bookings-list">
                        <?php foreach ( $dashboard_data['recent_bookings'] as $booking ) : ?>
                            <div class="booking-card">
                                <div class="booking-header">
                                    <h4><?php echo esc_html( $booking->service_name ); ?></h4>
                                    <span class="booking-status status-<?php echo esc_attr( $booking->status ); ?>">
                                        <?php echo esc_html( ucfirst( $booking->status ) ); ?>
                                    </span>
                                </div>
                                <div class="booking-details">
                                    <div class="booking-meta">
                                        <span class="booking-date">
                                            <span class="dashicons dashicons-calendar-alt"></span>
                                            <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->booking_date ) ) ); ?>
                                        </span>
                                        <span class="booking-time">
                                            <span class="dashicons dashicons-clock"></span>
                                            <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking->start_time ) ) ); ?>
                                        </span>
                                        <?php if ( $booking->worker_name ) : ?>
                                            <span class="booking-worker">
                                                <span class="dashicons dashicons-admin-users"></span>
                                                <?php echo esc_html( $booking->worker_name ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="booking-price">
                                        <strong>$<?php echo esc_html( number_format( $booking->total_cost, 2 ) ); ?></strong>
                                    </div>
                                </div>
                                <div class="booking-actions">
                                    <?php if ( $booking->status === 'pending' ) : ?>
                                        <button class="schedspot-btn schedspot-btn-small" onclick="rescheduleBooking(<?php echo esc_attr( $booking->id ); ?>)">
                                            <?php _e( 'Reschedule', 'schedspot' ); ?>
                                        </button>
                                        <button class="schedspot-btn schedspot-btn-small schedspot-btn-danger" onclick="cancelBooking(<?php echo esc_attr( $booking->id ); ?>)">
                                            <?php _e( 'Cancel', 'schedspot' ); ?>
                                        </button>
                                    <?php endif; ?>
                                    <button class="schedspot-btn schedspot-btn-small" onclick="viewBookingDetails(<?php echo esc_attr( $booking->id ); ?>)">
                                        <?php _e( 'View Details', 'schedspot' ); ?>
                                    </button>
                                    <?php if ( $booking->worker_id ) : ?>
                                        <button class="schedspot-btn schedspot-btn-small" onclick="messageWorker(<?php echo esc_attr( $booking->worker_id ); ?>)">
                                            <?php _e( 'Message Worker', 'schedspot' ); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="no-bookings">
                        <div class="no-bookings-content">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <h3><?php _e( 'No Bookings Yet', 'schedspot' ); ?></h3>
                            <p><?php _e( 'You haven\'t made any bookings yet. Start by booking your first service!', 'schedspot' ); ?></p>
                            <a href="<?php echo esc_url( add_query_arg( 'schedspot_action', 'booking_form' ) ); ?>" class="schedspot-btn schedspot-btn-primary">
                                <?php _e( 'Book a Service', 'schedspot' ); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Messages Tab -->
        <div id="messages" class="dashboard-tab">
            <div class="messages-section">
                <div class="section-header">
                    <h3><?php _e( 'Recent Messages', 'schedspot' ); ?></h3>
                    <a href="<?php echo esc_url( add_query_arg( 'schedspot_action', 'messages' ) ); ?>" class="schedspot-btn schedspot-btn-primary">
                        <?php _e( 'View All Messages', 'schedspot' ); ?>
                    </a>
                </div>

                <?php if ( ! empty( $dashboard_data['recent_messages'] ) ) : ?>
                    <div class="messages-list">
                        <?php foreach ( $dashboard_data['recent_messages'] as $message ) : ?>
                            <div class="message-card">
                                <div class="message-header">
                                    <div class="message-sender">
                                        <?php echo get_avatar( $message->sender_id, 32 ); ?>
                                        <span class="sender-name"><?php echo esc_html( $message->sender_name ); ?></span>
                                    </div>
                                    <span class="message-time"><?php echo esc_html( human_time_diff( strtotime( $message->created_at ) ) ); ?> <?php _e( 'ago', 'schedspot' ); ?></span>
                                </div>
                                <div class="message-content">
                                    <p><?php echo esc_html( wp_trim_words( $message->content, 20 ) ); ?></p>
                                </div>
                                <div class="message-actions">
                                    <a href="<?php echo esc_url( add_query_arg( array( 'schedspot_action' => 'messages', 'conversation' => $message->sender_id ) ) ); ?>" class="schedspot-btn schedspot-btn-small">
                                        <?php _e( 'Reply', 'schedspot' ); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="no-messages">
                        <div class="no-messages-content">
                            <span class="dashicons dashicons-email"></span>
                            <h3><?php _e( 'No Messages', 'schedspot' ); ?></h3>
                            <p><?php _e( 'You don\'t have any messages yet.', 'schedspot' ); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Profile Tab -->
        <div id="profile" class="dashboard-tab">
            <div class="profile-section">
                <div class="section-header">
                    <h3><?php _e( 'Profile Information', 'schedspot' ); ?></h3>
                    <a href="<?php echo esc_url( add_query_arg( 'schedspot_action', 'profile' ) ); ?>" class="schedspot-btn schedspot-btn-primary">
                        <?php _e( 'Edit Profile', 'schedspot' ); ?>
                    </a>
                </div>

                <div class="profile-info">
                    <div class="profile-avatar">
                        <?php echo get_avatar( $current_user->ID, 80 ); ?>
                    </div>
                    <div class="profile-details">
                        <h4><?php echo esc_html( $current_user->display_name ); ?></h4>
                        <p class="profile-email"><?php echo esc_html( $current_user->user_email ); ?></p>
                        <?php if ( $dashboard_data['profile']['phone'] ) : ?>
                            <p class="profile-phone"><?php echo esc_html( $dashboard_data['profile']['phone'] ); ?></p>
                        <?php endif; ?>
                        <?php if ( $dashboard_data['profile']['bio'] ) : ?>
                            <p class="profile-bio"><?php echo esc_html( $dashboard_data['profile']['bio'] ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.dashboard-nav .nav-item').on('click', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');

        $('.dashboard-nav .nav-item').removeClass('active');
        $(this).addClass('active');

        $('.dashboard-tab').removeClass('active');
        $('#' + tab).addClass('active');
    });
});

// Booking actions
function rescheduleBooking(bookingId) {
    // Implementation for rescheduling
    alert('Reschedule booking #' + bookingId);
}

function cancelBooking(bookingId) {
    if (confirm(schedspot_frontend.strings.confirm_cancel)) {
        // Implementation for canceling
        alert('Cancel booking #' + bookingId);
    }
}

function viewBookingDetails(bookingId) {
    // Implementation for viewing details
    alert('View booking details #' + bookingId);
}

function messageWorker(workerId) {
    // Redirect to messages with worker conversation
    window.location.href = '<?php echo esc_url( add_query_arg( 'schedspot_action', 'messages' ) ); ?>&conversation=' + workerId;
}
</script>
