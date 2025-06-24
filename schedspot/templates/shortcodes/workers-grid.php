<?php
/**
 * Workers Grid Template
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="schedspot-workers-grid">
    <div class="workers-grid-header">
        <h2><?php _e( 'Available Workers', 'schedspot' ); ?></h2>
        <?php if ( ! empty( $atts['service_id'] ) ) : ?>
            <p class="service-filter-notice">
                <?php _e( 'Showing workers for selected service', 'schedspot' ); ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if ( ! empty( $workers ) ) : ?>
        <div class="workers-grid" data-columns="<?php echo esc_attr( min( 4, count( $workers ) ) ); ?>">
            <?php foreach ( $workers as $worker ) : ?>
                <div class="worker-card" data-worker-id="<?php echo esc_attr( $worker['ID'] ); ?>">
                    <div class="worker-avatar">
                        <img src="<?php echo esc_url( $worker['avatar'] ); ?>" alt="<?php echo esc_attr( $worker['display_name'] ); ?>">
                        <div class="worker-status available">
                            <span class="status-indicator"></span>
                            <?php _e( 'Available', 'schedspot' ); ?>
                        </div>
                    </div>

                    <div class="worker-info">
                        <h3 class="worker-name"><?php echo esc_html( $worker['display_name'] ); ?></h3>
                        
                        <?php if ( ! empty( $worker['profile']['bio'] ) ) : ?>
                            <p class="worker-bio"><?php echo esc_html( wp_trim_words( $worker['profile']['bio'], 15 ) ); ?></p>
                        <?php endif; ?>

                        <div class="worker-meta">
                            <?php if ( $worker['hourly_rate'] > 0 ) : ?>
                                <div class="worker-rate">
                                    <span class="dashicons dashicons-money-alt"></span>
                                    <span class="rate-amount">$<?php echo esc_html( number_format( $worker['hourly_rate'], 2 ) ); ?>/hr</span>
                                </div>
                            <?php endif; ?>

                            <div class="worker-rating">
                                <span class="dashicons dashicons-star-filled"></span>
                                <span class="rating-value"><?php echo esc_html( number_format( $worker['rating'], 1 ) ); ?></span>
                                <span class="rating-count">(<?php echo esc_html( rand( 5, 50 ) ); ?>)</span>
                            </div>

                            <?php if ( ! empty( $worker['profile']['experience'] ) ) : ?>
                                <div class="worker-experience">
                                    <span class="dashicons dashicons-awards"></span>
                                    <span><?php printf( _n( '%d year exp.', '%d years exp.', $worker['profile']['experience'], 'schedspot' ), $worker['profile']['experience'] ); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ( ! empty( $worker['skills'] ) ) : ?>
                            <div class="worker-skills">
                                <?php 
                                $skills = is_array( $worker['skills'] ) ? $worker['skills'] : explode( ',', $worker['skills'] );
                                $displayed_skills = array_slice( $skills, 0, 3 );
                                ?>
                                <?php foreach ( $displayed_skills as $skill ) : ?>
                                    <span class="skill-tag"><?php echo esc_html( trim( $skill ) ); ?></span>
                                <?php endforeach; ?>
                                <?php if ( count( $skills ) > 3 ) : ?>
                                    <span class="skill-tag more">+<?php echo esc_html( count( $skills ) - 3 ); ?> <?php _e( 'more', 'schedspot' ); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="worker-actions">
                        <button class="schedspot-btn schedspot-btn-primary worker-book-btn" 
                                data-worker-id="<?php echo esc_attr( $worker['ID'] ); ?>"
                                onclick="bookWithWorker(<?php echo esc_attr( $worker['ID'] ); ?>)">
                            <?php _e( 'Book Now', 'schedspot' ); ?>
                        </button>
                        <button class="schedspot-btn schedspot-btn-secondary worker-profile-btn" 
                                data-worker-id="<?php echo esc_attr( $worker['ID'] ); ?>"
                                onclick="viewWorkerProfile(<?php echo esc_attr( $worker['ID'] ); ?>)">
                            <?php _e( 'View Profile', 'schedspot' ); ?>
                        </button>
                        <?php if ( is_user_logged_in() ) : ?>
                            <button class="schedspot-btn schedspot-btn-small worker-message-btn" 
                                    data-worker-id="<?php echo esc_attr( $worker['ID'] ); ?>"
                                    onclick="messageWorker(<?php echo esc_attr( $worker['ID'] ); ?>)">
                                <span class="dashicons dashicons-email"></span>
                                <?php _e( 'Message', 'schedspot' ); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ( count( $workers ) >= intval( $atts['limit'] ) ) : ?>
            <div class="workers-grid-footer">
                <button class="schedspot-btn schedspot-btn-secondary load-more-workers" 
                        data-offset="<?php echo esc_attr( $atts['limit'] ); ?>"
                        data-service-id="<?php echo esc_attr( $atts['service_id'] ); ?>">
                    <?php _e( 'Load More Workers', 'schedspot' ); ?>
                </button>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <div class="no-workers">
            <div class="no-workers-content">
                <span class="dashicons dashicons-admin-users"></span>
                <h3><?php _e( 'No Workers Available', 'schedspot' ); ?></h3>
                <?php if ( ! empty( $atts['service_id'] ) ) : ?>
                    <p><?php _e( 'No workers are currently available for this service. Please try again later or select a different service.', 'schedspot' ); ?></p>
                <?php else : ?>
                    <p><?php _e( 'No workers are currently available. Please check back later.', 'schedspot' ); ?></p>
                <?php endif; ?>
                <div class="no-workers-actions">
                    <a href="<?php echo esc_url( add_query_arg( 'schedspot_action', 'booking_form' ) ); ?>" class="schedspot-btn schedspot-btn-primary">
                        <?php _e( 'Browse All Services', 'schedspot' ); ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function bookWithWorker(workerId) {
    // Redirect to booking form with pre-selected worker
    var bookingUrl = '<?php echo esc_url( add_query_arg( 'schedspot_action', 'booking_form' ) ); ?>';
    bookingUrl += '&worker_id=' + workerId;
    
    <?php if ( ! empty( $atts['service_id'] ) ) : ?>
        bookingUrl += '&service_id=<?php echo esc_js( $atts['service_id'] ); ?>';
    <?php endif; ?>
    
    window.location.href = bookingUrl;
}

function viewWorkerProfile(workerId) {
    // Show worker profile modal or redirect to profile page
    alert('View worker profile #' + workerId + ' - Feature coming soon!');
}

function messageWorker(workerId) {
    // Redirect to messages with worker conversation
    var messagesUrl = '<?php echo esc_url( add_query_arg( 'schedspot_action', 'messages' ) ); ?>';
    messagesUrl += '&conversation=' + workerId;
    window.location.href = messagesUrl;
}

jQuery(document).ready(function($) {
    // Load more workers functionality
    $('.load-more-workers').on('click', function() {
        var button = $(this);
        var offset = parseInt(button.data('offset'));
        var serviceId = button.data('service-id');
        
        button.prop('disabled', true).text('<?php _e( 'Loading...', 'schedspot' ); ?>');
        
        $.ajax({
            url: schedspot_frontend.rest_url + 'workers',
            method: 'GET',
            data: {
                offset: offset,
                limit: <?php echo esc_js( $atts['limit'] ); ?>,
                service_id: serviceId
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (response && response.length > 0) {
                    // Add new workers to grid
                    var workersHtml = '';
                    $.each(response, function(index, worker) {
                        // Build worker card HTML (simplified version)
                        workersHtml += '<div class="worker-card" data-worker-id="' + worker.ID + '">';
                        workersHtml += '<div class="worker-info"><h3>' + worker.display_name + '</h3></div>';
                        workersHtml += '<div class="worker-actions">';
                        workersHtml += '<button class="schedspot-btn schedspot-btn-primary" onclick="bookWithWorker(' + worker.ID + ')"><?php _e( 'Book Now', 'schedspot' ); ?></button>';
                        workersHtml += '</div></div>';
                    });
                    
                    $('.workers-grid').append(workersHtml);
                    button.data('offset', offset + response.length);
                    
                    if (response.length < <?php echo esc_js( $atts['limit'] ); ?>) {
                        button.hide(); // No more workers to load
                    }
                } else {
                    button.hide(); // No more workers
                }
            },
            error: function() {
                alert('<?php _e( 'Error loading workers. Please try again.', 'schedspot' ); ?>');
            },
            complete: function() {
                button.prop('disabled', false).text('<?php _e( 'Load More Workers', 'schedspot' ); ?>');
            }
        });
    });
});
</script>
