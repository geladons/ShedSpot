<?php
/**
 * General Dashboard Template
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="schedspot-dashboard schedspot-general-dashboard">
    <div class="schedspot-dashboard-header">
        <h2><?php _e( 'Dashboard', 'schedspot' ); ?></h2>
        <p class="user-welcome"><?php printf( __( 'Welcome, %s!', 'schedspot' ), esc_html( $current_user->display_name ) ); ?></p>
    </div>

    <div class="dashboard-navigation">
        <nav class="dashboard-nav">
            <a href="#overview" class="nav-item active" data-tab="overview">
                <span class="dashicons dashicons-dashboard"></span>
                <?php _e( 'Overview', 'schedspot' ); ?>
            </a>
            <a href="#bookings" class="nav-item" data-tab="bookings">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e( 'Bookings', 'schedspot' ); ?>
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
        <!-- Overview Tab -->
        <div id="overview" class="dashboard-tab active">
            <div class="dashboard-welcome">
                <div class="welcome-card">
                    <h3><?php _e( 'Welcome to SchedSpot!', 'schedspot' ); ?></h3>
                    <p><?php _e( 'Get started by exploring our services or managing your account.', 'schedspot' ); ?></p>
                    <div class="welcome-actions">
                        <a href="<?php echo esc_url( add_query_arg( 'schedspot_action', 'booking_form' ) ); ?>" class="schedspot-btn schedspot-btn-primary">
                            <?php _e( 'Book a Service', 'schedspot' ); ?>
                        </a>
                        <a href="<?php echo esc_url( add_query_arg( 'schedspot_action', 'profile' ) ); ?>" class="schedspot-btn schedspot-btn-secondary">
                            <?php _e( 'Complete Profile', 'schedspot' ); ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo esc_html( $dashboard_data['stats']['total_bookings'] ?? 0 ); ?></h3>
                        <p><?php _e( 'Total Bookings', 'schedspot' ); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-email"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo esc_html( $dashboard_data['stats']['total_messages'] ?? 0 ); ?></h3>
                        <p><?php _e( 'Messages', 'schedspot' ); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo esc_html( ucfirst( str_replace( 'schedspot_', '', $user_role ) ) ); ?></h3>
                        <p><?php _e( 'Account Type', 'schedspot' ); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-calendar"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $current_user->user_registered ) ) ); ?></h3>
                        <p><?php _e( 'Member Since', 'schedspot' ); ?></p>
                    </div>
                </div>
            </div>

            <div class="quick-actions">
                <h3><?php _e( 'Quick Actions', 'schedspot' ); ?></h3>
                <div class="actions-grid">
                    <a href="<?php echo esc_url( add_query_arg( 'schedspot_action', 'booking_form' ) ); ?>" class="action-card">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <h4><?php _e( 'Book Service', 'schedspot' ); ?></h4>
                        <p><?php _e( 'Schedule a new service booking', 'schedspot' ); ?></p>
                    </a>
                    <a href="<?php echo esc_url( add_query_arg( 'schedspot_action', 'messages' ) ); ?>" class="action-card">
                        <span class="dashicons dashicons-email-alt"></span>
                        <h4><?php _e( 'Messages', 'schedspot' ); ?></h4>
                        <p><?php _e( 'View and send messages', 'schedspot' ); ?></p>
                    </a>
                    <a href="<?php echo esc_url( add_query_arg( 'schedspot_action', 'profile' ) ); ?>" class="action-card">
                        <span class="dashicons dashicons-admin-users"></span>
                        <h4><?php _e( 'Profile', 'schedspot' ); ?></h4>
                        <p><?php _e( 'Update your profile information', 'schedspot' ); ?></p>
                    </a>
                    <?php if ( current_user_can( 'manage_options' ) ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=schedspot' ) ); ?>" class="action-card">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <h4><?php _e( 'Admin Panel', 'schedspot' ); ?></h4>
                            <p><?php _e( 'Manage SchedSpot settings', 'schedspot' ); ?></p>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bookings Tab -->
        <div id="bookings" class="dashboard-tab">
            <div class="bookings-section">
                <div class="section-header">
                    <h3><?php _e( 'My Bookings', 'schedspot' ); ?></h3>
                    <a href="<?php echo esc_url( add_query_arg( 'schedspot_action', 'booking_form' ) ); ?>" class="schedspot-btn schedspot-btn-primary">
                        <?php _e( 'New Booking', 'schedspot' ); ?>
                    </a>
                </div>

                <?php if ( ! empty( $dashboard_data['recent_bookings'] ) ) : ?>
                    <div class="bookings-list">
                        <?php foreach ( $dashboard_data['recent_bookings'] as $booking ) : ?>
                            <div class="booking-card">
                                <div class="booking-header">
                                    <h4><?php echo esc_html( $booking->service_name ?? __( 'Service', 'schedspot' ) ); ?></h4>
                                    <span class="booking-status status-<?php echo esc_attr( $booking->status ?? 'pending' ); ?>">
                                        <?php echo esc_html( ucfirst( $booking->status ?? 'pending' ) ); ?>
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
                                    </div>
                                    <div class="booking-price">
                                        <strong>$<?php echo esc_html( number_format( $booking->total_cost ?? 0, 2 ) ); ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="no-bookings">
                        <div class="no-bookings-content">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <h3><?php _e( 'No Bookings Yet', 'schedspot' ); ?></h3>
                            <p><?php _e( 'You haven\'t made any bookings yet.', 'schedspot' ); ?></p>
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
                                        <span class="sender-name"><?php echo esc_html( $message->sender_name ?? __( 'Unknown', 'schedspot' ) ); ?></span>
                                    </div>
                                    <span class="message-time"><?php echo esc_html( human_time_diff( strtotime( $message->created_at ) ) ); ?> <?php _e( 'ago', 'schedspot' ); ?></span>
                                </div>
                                <div class="message-content">
                                    <p><?php echo esc_html( wp_trim_words( $message->content, 20 ) ); ?></p>
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
                        <p class="profile-role"><?php echo esc_html( ucfirst( str_replace( 'schedspot_', '', $user_role ) ) ); ?></p>
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
</script>
