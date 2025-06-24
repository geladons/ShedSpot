<?php
/**
 * Admin Dashboard Template
 *
 * @package SchedSpot
 * @version 1.7.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
?>

<div class="schedspot-dashboard schedspot-admin-dashboard">
    <!-- Admin Header -->
    <div class="dashboard-header">
        <div class="user-info">
            <div class="user-avatar">
                <?php echo get_avatar( $current_user->ID, 60 ); ?>
            </div>
            <div class="user-details">
                <h2><?php _e( 'Admin Dashboard', 'schedspot' ); ?></h2>
                <p class="user-welcome"><?php printf( __( 'Welcome back, %s!', 'schedspot' ), esc_html( $current_user->display_name ) ); ?></p>
                <span class="user-role"><?php _e( 'Administrator', 'schedspot' ); ?></span>
            </div>
        </div>
        
        <!-- Role Switcher -->
        <div class="admin-role-switcher">
            <label for="admin-role-mode"><?php _e( 'View as:', 'schedspot' ); ?></label>
            <select id="admin-role-mode" onchange="switchAdminRole(this.value)">
                <option value="administrator" <?php selected( $dashboard_data['role'], 'administrator' ); ?>><?php _e( 'Administrator', 'schedspot' ); ?></option>
                <option value="schedspot_worker" <?php selected( $dashboard_data['role'], 'schedspot_worker' ); ?>><?php _e( 'Worker View', 'schedspot' ); ?></option>
                <option value="schedspot_customer" <?php selected( $dashboard_data['role'], 'schedspot_customer' ); ?>><?php _e( 'Customer View', 'schedspot' ); ?></option>
            </select>
        </div>
    </div>

    <!-- Admin Navigation -->
    <div class="dashboard-navigation">
        <nav class="dashboard-nav">
            <a href="#overview" class="nav-item active" data-tab="overview">
                <span class="dashicons dashicons-dashboard"></span>
                <?php _e( 'Overview', 'schedspot' ); ?>
            </a>
            <a href="#bookings" class="nav-item" data-tab="bookings">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e( 'All Bookings', 'schedspot' ); ?>
                <?php if ( $dashboard_data['stats']['pending_bookings'] > 0 ) : ?>
                    <span class="notification-badge"><?php echo esc_html( $dashboard_data['stats']['pending_bookings'] ); ?></span>
                <?php endif; ?>
            </a>
            <a href="#workers" class="nav-item" data-tab="workers">
                <span class="dashicons dashicons-admin-users"></span>
                <?php _e( 'Workers', 'schedspot' ); ?>
            </a>
            <a href="#services" class="nav-item" data-tab="services">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php _e( 'Services', 'schedspot' ); ?>
            </a>
            <a href="#messages" class="nav-item" data-tab="messages">
                <span class="dashicons dashicons-email"></span>
                <?php _e( 'Messages', 'schedspot' ); ?>
                <?php if ( $dashboard_data['stats']['unread_messages'] > 0 ) : ?>
                    <span class="notification-badge"><?php echo esc_html( $dashboard_data['stats']['unread_messages'] ); ?></span>
                <?php endif; ?>
            </a>
            <a href="#settings" class="nav-item" data-tab="settings">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e( 'Quick Settings', 'schedspot' ); ?>
            </a>
        </nav>
    </div>

    <div class="dashboard-content">
        <!-- Overview Tab -->
        <div id="overview" class="dashboard-tab active">
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
                        <span class="dashicons dashicons-admin-users"></span>
                    </div>
                    <div class="stat-number"><?php echo esc_html( $dashboard_data['stats']['total_workers'] ); ?></div>
                    <div class="stat-label"><?php _e( 'Active Workers', 'schedspot' ); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="stat-number">$<?php echo number_format( $dashboard_data['stats']['total_revenue'], 2 ); ?></div>
                    <div class="stat-label"><?php _e( 'Total Revenue', 'schedspot' ); ?></div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="admin-recent-activity">
                <div class="section-header">
                    <h3><?php _e( 'Recent Activity', 'schedspot' ); ?></h3>
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-bookings' ); ?>" class="schedspot-btn schedspot-btn-primary" target="_blank">
                        <?php _e( 'Manage All', 'schedspot' ); ?>
                    </a>
                </div>

                <?php if ( ! empty( $dashboard_data['recent_bookings'] ) ) : ?>
                    <div class="recent-bookings-list">
                        <?php foreach ( array_slice( $dashboard_data['recent_bookings'], 0, 5 ) as $booking ) : ?>
                            <div class="booking-card admin-booking-card">
                                <div class="booking-header">
                                    <h4><?php echo esc_html( $booking->service_name ?? __( 'Unknown Service', 'schedspot' ) ); ?></h4>
                                    <span class="booking-status status-<?php echo esc_attr( $booking->status ); ?>">
                                        <?php echo esc_html( ucfirst( $booking->status ) ); ?>
                                    </span>
                                </div>
                                <div class="booking-details">
                                    <div class="booking-meta">
                                        <span class="booking-client">
                                            <span class="dashicons dashicons-admin-users"></span>
                                            <?php echo esc_html( $booking->client_name ); ?>
                                        </span>
                                        <span class="booking-worker">
                                            <span class="dashicons dashicons-businessman"></span>
                                            <?php echo esc_html( $booking->worker_name ?? __( 'Unassigned', 'schedspot' ) ); ?>
                                        </span>
                                        <span class="booking-date">
                                            <span class="dashicons dashicons-calendar-alt"></span>
                                            <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking->booking_date ) ) ); ?>
                                        </span>
                                        <span class="booking-price">
                                            <span class="dashicons dashicons-money-alt"></span>
                                            $<?php echo esc_html( number_format( $booking->total_cost, 2 ) ); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="booking-actions">
                                    <button class="schedspot-btn schedspot-btn-small" onclick="viewBookingDetails(<?php echo esc_attr( $booking->id ); ?>)">
                                        <?php _e( 'View Details', 'schedspot' ); ?>
                                    </button>
                                    <button class="schedspot-btn schedspot-btn-small" onclick="viewClientWorkerMessages(<?php echo esc_attr( $booking->id ); ?>)">
                                        <?php _e( 'View Messages', 'schedspot' ); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="no-bookings">
                        <div class="no-bookings-content">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <h3><?php _e( 'No Recent Bookings', 'schedspot' ); ?></h3>
                            <p><?php _e( 'No bookings have been made yet.', 'schedspot' ); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="admin-quick-actions">
                <h3><?php _e( 'Quick Actions', 'schedspot' ); ?></h3>
                <div class="quick-actions-grid">
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-bookings' ); ?>" class="quick-action-card" target="_blank">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span><?php _e( 'Manage Bookings', 'schedspot' ); ?></span>
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-workers' ); ?>" class="quick-action-card" target="_blank">
                        <span class="dashicons dashicons-admin-users"></span>
                        <span><?php _e( 'Manage Workers', 'schedspot' ); ?></span>
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-services' ); ?>" class="quick-action-card" target="_blank">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <span><?php _e( 'Manage Services', 'schedspot' ); ?></span>
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=schedspot-settings' ); ?>" class="quick-action-card" target="_blank">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <span><?php _e( 'Plugin Settings', 'schedspot' ); ?></span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Other tabs content will be loaded via AJAX -->
        <div id="bookings" class="dashboard-tab">
            <div class="loading-placeholder">
                <p><?php _e( 'Loading bookings...', 'schedspot' ); ?></p>
            </div>
        </div>

        <div id="workers" class="dashboard-tab">
            <div class="loading-placeholder">
                <p><?php _e( 'Loading workers...', 'schedspot' ); ?></p>
            </div>
        </div>

        <div id="services" class="dashboard-tab">
            <div class="loading-placeholder">
                <p><?php _e( 'Loading services...', 'schedspot' ); ?></p>
            </div>
        </div>

        <div id="messages" class="dashboard-tab">
            <div class="loading-placeholder">
                <p><?php _e( 'Loading messages...', 'schedspot' ); ?></p>
            </div>
        </div>

        <div id="settings" class="dashboard-tab">
            <div class="loading-placeholder">
                <p><?php _e( 'Loading settings...', 'schedspot' ); ?></p>
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
        
        // Load tab content via AJAX if needed
        if (tab !== 'overview' && $('#' + tab + ' .loading-placeholder').length > 0) {
            loadTabContent(tab);
        }
    });
});

// Admin role switching
function switchAdminRole(role) {
    var url = new URL(window.location);
    url.searchParams.set('admin_role_mode', role);
    window.location.href = url.toString();
}

// Admin-specific functions
function viewBookingDetails(bookingId) {
    // Open booking details in admin panel
    window.open('<?php echo admin_url( "admin.php?page=schedspot-bookings&action=view&booking_id=" ); ?>' + bookingId, '_blank');
}

function viewClientWorkerMessages(bookingId) {
    // Implementation for viewing client-worker message dialog
    alert('View messages for booking #' + bookingId + ' - Feature to be implemented');
}

function loadTabContent(tab) {
    // AJAX loading for tab content
    jQuery.ajax({
        url: schedspot_frontend.ajax_url,
        type: 'POST',
        data: {
            action: 'schedspot_load_admin_tab_content',
            tab: tab,
            nonce: schedspot_frontend.nonce
        },
        success: function(response) {
            if (response.success) {
                jQuery('#' + tab).html(response.data);
            } else {
                jQuery('#' + tab).html('<p>Error loading content.</p>');
            }
        },
        error: function() {
            jQuery('#' + tab).html('<p>Error loading content.</p>');
        }
    });
}
</script>