<?php
/**
 * Worker Dashboard Template
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

    <?php if ( isset( $_GET['updated'] ) ) : ?>
        <div class="schedspot-notice success">
            <?php _e( 'Settings updated successfully!', 'schedspot' ); ?>
        </div>
    <?php endif; ?>

    <!-- Dashboard Header -->
    <div class="schedspot-dashboard-header">
        <h2><?php printf( __( 'Welcome back, %s!', 'schedspot' ), esc_html( $dashboard_data['user']->display_name ) ); ?></h2>
        <p class="user-welcome"><?php _e( 'Manage your bookings, availability, and earnings from your worker dashboard.', 'schedspot' ); ?></p>
        <span class="user-role"><?php _e( 'Worker', 'schedspot' ); ?></span>
    </div>
        
        <div class="availability-toggle">
            <form method="post" class="availability-form">
                <?php wp_nonce_field( 'update_availability' ); ?>
                <input type="hidden" name="action" value="update_availability">
                <input type="hidden" name="is_available" value="<?php echo $dashboard_data['is_available'] ? '0' : '1'; ?>">
                
                <div class="availability-status <?php echo $dashboard_data['is_available'] ? 'available' : 'unavailable'; ?>" id="availability-status">
                    <span class="status-indicator"></span>
                    <span class="status-text" id="availability-text">
                        <?php echo $dashboard_data['is_available'] ? __( 'Available', 'schedspot' ) : __( 'Unavailable', 'schedspot' ); ?>
                    </span>
                </div>
                
                <button type="button" class="schedspot-btn schedspot-btn-secondary availability-toggle" onclick="toggleAvailability()">
                    <?php echo $dashboard_data['is_available'] ? __( 'Go Unavailable', 'schedspot' ) : __( 'Go Available', 'schedspot' ); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Availability Toggle -->
    <div class="availability-toggle">
        <h3><?php _e( 'Availability Status', 'schedspot' ); ?></h3>
        <div class="availability-status <?php echo $dashboard_data['worker_profile']['is_available'] ? 'available' : 'unavailable'; ?>">
            <span class="dashicons <?php echo $dashboard_data['worker_profile']['is_available'] ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>"></span>
            <?php echo $dashboard_data['worker_profile']['is_available'] ? __( 'Available for Bookings', 'schedspot' ) : __( 'Currently Unavailable', 'schedspot' ); ?>
        </div>
        <button type="button" class="availability-toggle-btn" data-worker-id="<?php echo esc_attr( $dashboard_data['user']->ID ); ?>">
            <?php echo $dashboard_data['worker_profile']['is_available'] ? __( 'Set Unavailable', 'schedspot' ) : __( 'Set Available', 'schedspot' ); ?>
        </button>
    </div>

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
            <div class="stat-number">$<?php echo number_format( $dashboard_data['stats']['this_month_earnings'], 2 ); ?></div>
            <div class="stat-label"><?php _e( 'This Month', 'schedspot' ); ?></div>
        </div>
    </div>

    <!-- Dashboard Navigation -->
    <div class="schedspot-dashboard-nav">
        <button class="nav-tab active" data-tab="upcoming"><?php _e( 'Upcoming Bookings', 'schedspot' ); ?></button>
        <button class="nav-tab" data-tab="all-bookings"><?php _e( 'All Bookings', 'schedspot' ); ?></button>
        <button class="nav-tab" data-tab="earnings"><?php _e( 'Earnings', 'schedspot' ); ?></button>
        <button class="nav-tab" data-tab="messages"><?php _e( 'Messages', 'schedspot' ); ?></button>
    </div>

    <!-- Dashboard Content -->
    <div class="schedspot-dashboard-content">
        
        <!-- Upcoming Bookings Tab -->
        <div class="tab-content active" id="upcoming">
            <h3><?php _e( 'Upcoming Bookings', 'schedspot' ); ?></h3>
            
            <?php if ( ! empty( $dashboard_data['upcoming_bookings'] ) ) : ?>
                <div class="bookings-list">
                    <?php foreach ( $dashboard_data['upcoming_bookings'] as $booking ) : ?>
                        <?php
                        // Handle client_details safely
                        $client_details = array();
                        if ( isset( $booking->client_details ) ) {
                            $client_details = is_string( $booking->client_details ) ? json_decode( $booking->client_details, true ) : $booking->client_details;
                        }
                        if ( ! is_array( $client_details ) ) {
                            $client_details = array(
                                'name'  => $booking->client_name ?? __( 'Unknown Client', 'schedspot' ),
                                'email' => $booking->client_email ?? '',
                                'phone' => $booking->client_phone ?? '',
                            );
                        }

                        // Handle service_details safely
                        $service_details = array();
                        if ( isset( $booking->service_details ) ) {
                            $service_details = is_string( $booking->service_details ) ? json_decode( $booking->service_details, true ) : $booking->service_details;
                        }
                        if ( ! is_array( $service_details ) ) {
                            $service_details = array(
                                'name' => __( 'Unknown Service', 'schedspot' ),
                                'description' => '',
                                'duration' => 60,
                            );
                        }
                        ?>
                        <div class="booking-card">
                            <div class="booking-header">
                                <h4><?php echo esc_html( $service_details['name'] ); ?></h4>
                                <span class="booking-status status-<?php echo esc_attr( $booking->status ); ?>">
                                    <?php echo esc_html( SchedSpot_Shortcode_Dashboard::format_booking_status( $booking->status ) ); ?>
                                </span>
                            </div>
                            
                            <div class="booking-details">
                                <div class="booking-info">
                                    <p><strong><?php _e( 'Client:', 'schedspot' ); ?></strong> <?php echo esc_html( $client_details['name'] ); ?></p>
                                    <p><strong><?php _e( 'Date:', 'schedspot' ); ?></strong> <?php echo esc_html( date( 'F j, Y', strtotime( $booking->booking_date ) ) ); ?></p>
                                    <p><strong><?php _e( 'Time:', 'schedspot' ); ?></strong> <?php echo esc_html( date( 'g:i A', strtotime( $booking->booking_time ) ) ); ?></p>
                                    <p><strong><?php _e( 'Price:', 'schedspot' ); ?></strong> $<?php echo number_format( $booking->total_price, 2 ); ?></p>
                                </div>
                                
                                <div class="booking-actions">
                                    <?php $actions = SchedSpot_Shortcode_Dashboard::get_booking_actions( $booking, 'schedspot_worker' ); ?>
                                    <?php foreach ( $actions as $action ) : ?>
                                        <button type="button" 
                                                class="schedspot-btn <?php echo esc_attr( $action['class'] ); ?>" 
                                                onclick="<?php echo esc_attr( $action['action'] ); ?>Booking(<?php echo esc_attr( $booking->id ); ?>)">
                                            <?php echo esc_html( $action['label'] ); ?>
                                        </button>
                                    <?php endforeach; ?>
                                    
                                    <button type="button" class="schedspot-btn schedspot-btn-secondary" onclick="messageWorker(<?php echo esc_attr( $client_details['id'] ?? 0 ); ?>, <?php echo esc_attr( $booking->id ); ?>)">
                                        <?php _e( 'Message Client', 'schedspot' ); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="empty-state">
                    <p><?php _e( 'No upcoming bookings found.', 'schedspot' ); ?></p>
                    <a href="<?php echo home_url( '/?schedspot_action=profile' ); ?>" class="schedspot-btn schedspot-btn-primary">
                        <?php _e( 'Update Your Availability', 'schedspot' ); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- All Bookings Tab -->
        <div class="tab-content" id="all-bookings">
            <h3><?php _e( 'All Bookings', 'schedspot' ); ?></h3>
            
            <div class="bookings-filters">
                <select class="status-filter" data-filter="status">
                    <option value="all"><?php _e( 'All Statuses', 'schedspot' ); ?></option>
                    <option value="pending"><?php _e( 'Pending', 'schedspot' ); ?></option>
                    <option value="confirmed"><?php _e( 'Confirmed', 'schedspot' ); ?></option>
                    <option value="completed"><?php _e( 'Completed', 'schedspot' ); ?></option>
                    <option value="cancelled"><?php _e( 'Cancelled', 'schedspot' ); ?></option>
                </select>
            </div>
            
            <?php if ( ! empty( $dashboard_data['bookings'] ) ) : ?>
                <div class="bookings-table">
                    <table class="schedspot-table">
                        <thead>
                            <tr>
                                <th><?php _e( 'Service', 'schedspot' ); ?></th>
                                <th><?php _e( 'Client', 'schedspot' ); ?></th>
                                <th><?php _e( 'Date', 'schedspot' ); ?></th>
                                <th><?php _e( 'Status', 'schedspot' ); ?></th>
                                <th><?php _e( 'Price', 'schedspot' ); ?></th>
                                <th><?php _e( 'Actions', 'schedspot' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $dashboard_data['bookings'] as $booking ) : ?>
                                <?php
                                // Handle client_details safely
                                $client_details = array();
                                if ( isset( $booking->client_details ) ) {
                                    if ( is_string( $booking->client_details ) ) {
                                        $client_details = json_decode( $booking->client_details, true );
                                    } elseif ( is_array( $booking->client_details ) ) {
                                        $client_details = $booking->client_details;
                                    }
                                }

                                // Fallback to individual client fields if client_details is not available
                                if ( empty( $client_details ) ) {
                                    $client_details = array(
                                        'name'    => $booking->client_name ?? __( 'Unknown Client', 'schedspot' ),
                                        'email'   => $booking->client_email ?? '',
                                        'phone'   => $booking->client_phone ?? '',
                                        'address' => $booking->client_address ?? '',
                                    );
                                }

                                // Get service name from booking object or database
                                $service_name = '';
                                if ( isset( $booking->service_name ) ) {
                                    $service_name = $booking->service_name;
                                } elseif ( $booking->service_id ) {
                                    global $wpdb;
                                    $service = $wpdb->get_row( $wpdb->prepare(
                                        "SELECT name FROM {$wpdb->prefix}schedspot_services WHERE id = %d",
                                        $booking->service_id
                                    ) );
                                    $service_name = $service ? $service->name : __( 'Unknown Service', 'schedspot' );
                                } else {
                                    $service_name = __( 'No Service', 'schedspot' );
                                }
                                ?>
                                <tr class="booking-row" data-status="<?php echo esc_attr( $booking->status ); ?>">
                                    <td><?php echo esc_html( $service_name ); ?></td>
                                    <td><?php echo esc_html( $client_details['name'] ?? __( 'Unknown Client', 'schedspot' ) ); ?></td>
                                    <td><?php echo esc_html( date( 'M j, Y g:i A', strtotime( $booking->booking_date . ' ' . $booking->start_time ) ) ); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr( $booking->status ); ?>">
                                            <?php echo esc_html( ucfirst( $booking->status ) ); ?>
                                        </span>
                                    </td>
                                    <td>$<?php echo number_format( $booking->total_cost ?? 0, 2 ); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="schedspot-btn schedspot-btn-small" onclick="viewBookingDetails(<?php echo esc_attr( $booking->id ); ?>)">
                                                <?php _e( 'View', 'schedspot' ); ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="empty-state">
                    <p><?php _e( 'No bookings found.', 'schedspot' ); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Earnings Tab -->
        <div class="tab-content" id="earnings">
            <h3><?php _e( 'Earnings Overview', 'schedspot' ); ?></h3>
            
            <div class="earnings-summary">
                <div class="earnings-card">
                    <h4><?php _e( 'Total Earnings', 'schedspot' ); ?></h4>
                    <div class="earnings-amount">$<?php echo number_format( $dashboard_data['stats']['total_earnings'], 2 ); ?></div>
                </div>
                
                <div class="earnings-card">
                    <h4><?php _e( 'This Month', 'schedspot' ); ?></h4>
                    <div class="earnings-amount">$<?php echo number_format( $dashboard_data['stats']['this_month_earnings'], 2 ); ?></div>
                </div>
                
                <div class="earnings-card">
                    <h4><?php _e( 'Completed Jobs', 'schedspot' ); ?></h4>
                    <div class="earnings-amount"><?php echo esc_html( $dashboard_data['stats']['completed_bookings'] ); ?></div>
                </div>
            </div>
            
            <div class="earnings-actions">
                <button type="button" class="schedspot-btn schedspot-btn-primary" onclick="requestPayout()">
                    <?php _e( 'Request Payout', 'schedspot' ); ?>
                </button>
                <button type="button" class="schedspot-btn schedspot-btn-secondary" onclick="downloadEarningsReport()">
                    <?php _e( 'Download Report', 'schedspot' ); ?>
                </button>
            </div>
        </div>

        <!-- Messages Tab -->
        <div class="tab-content" id="messages">
            <h3><?php _e( 'Recent Messages', 'schedspot' ); ?></h3>
            
            <?php if ( ! empty( $dashboard_data['recent_messages'] ) ) : ?>
                <div class="messages-preview">
                    <?php foreach ( $dashboard_data['recent_messages'] as $message ) : ?>
                        <div class="message-preview">
                            <div class="message-sender"><?php echo esc_html( $message['sender_name'] ); ?></div>
                            <div class="message-content"><?php echo esc_html( wp_trim_words( $message['content'], 15 ) ); ?></div>
                            <div class="message-time"><?php echo esc_html( $message['time_ago'] ); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="empty-state">
                    <p><?php _e( 'No recent messages.', 'schedspot' ); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="messages-actions">
                <a href="<?php echo home_url( '/?schedspot_action=messages' ); ?>" class="schedspot-btn schedspot-btn-primary">
                    <?php _e( 'View All Messages', 'schedspot' ); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="schedspot-quick-actions">
        <h3><?php _e( 'Quick Actions', 'schedspot' ); ?></h3>
        <div class="quick-actions-grid">
            <a href="<?php echo home_url( '/?schedspot_action=profile&tab=availability' ); ?>" class="quick-action-card">
                <div class="action-icon">📅</div>
                <div class="action-title"><?php _e( 'Update Availability', 'schedspot' ); ?></div>
            </a>
            
            <a href="<?php echo home_url( '/?schedspot_action=profile&tab=professional' ); ?>" class="quick-action-card">
                <div class="action-icon">👤</div>
                <div class="action-title"><?php _e( 'Edit Profile', 'schedspot' ); ?></div>
            </a>
            
            <a href="<?php echo home_url( '/?schedspot_action=messages' ); ?>" class="quick-action-card">
                <div class="action-icon">💬</div>
                <div class="action-title"><?php _e( 'Messages', 'schedspot' ); ?></div>
            </a>
            
            <a href="<?php echo home_url( '/?schedspot_action=profile&tab=services' ); ?>" class="quick-action-card">
                <div class="action-icon">🛠️</div>
                <div class="action-title"><?php _e( 'Manage Services', 'schedspot' ); ?></div>
            </a>
        </div>
    </div>
</div>

<script>
// Tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.nav-tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Status filter functionality
    const statusFilter = document.querySelector('.status-filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const filterValue = this.value;
            const bookingRows = document.querySelectorAll('.booking-row');
            
            bookingRows.forEach(row => {
                if (filterValue === 'all' || row.getAttribute('data-status') === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>
