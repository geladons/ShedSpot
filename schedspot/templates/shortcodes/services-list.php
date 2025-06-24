<?php
/**
 * Services List Shortcode Template
 *
 * @package SchedSpot
 * @version 1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$columns = intval( $atts['columns'] );
$columns = max( 1, min( 4, $columns ) ); // Ensure columns is between 1 and 4
?>

<div class="schedspot-services-list" data-columns="<?php echo esc_attr( $columns ); ?>">
    <?php if ( ! empty( $services ) ) : ?>
        <div class="services-grid columns-<?php echo esc_attr( $columns ); ?>">
            <?php foreach ( $services as $service ) : ?>
                <div class="service-card" data-service-id="<?php echo esc_attr( $service->id ); ?>">
                    <div class="service-header">
                        <h3 class="service-name"><?php echo esc_html( $service->name ); ?></h3>
                        <div class="service-category">
                            <span class="category-badge"><?php echo esc_html( $service->category ); ?></span>
                        </div>
                    </div>
                    
                    <div class="service-content">
                        <div class="service-description">
                            <p><?php echo esc_html( $service->description ); ?></p>
                        </div>
                        
                        <div class="service-details">
                            <div class="service-duration">
                                <span class="detail-label"><?php _e( 'Duration:', 'schedspot' ); ?></span>
                                <span class="detail-value">
                                    <?php
                                    $hours = floor( $service->duration / 60 );
                                    $minutes = $service->duration % 60;
                                    if ( $hours > 0 ) {
                                        echo sprintf( _n( '%d hour', '%d hours', $hours, 'schedspot' ), $hours );
                                        if ( $minutes > 0 ) {
                                            echo ' ' . sprintf( _n( '%d minute', '%d minutes', $minutes, 'schedspot' ), $minutes );
                                        }
                                    } else {
                                        echo sprintf( _n( '%d minute', '%d minutes', $minutes, 'schedspot' ), $minutes );
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <div class="service-price">
                                <span class="detail-label"><?php _e( 'Price:', 'schedspot' ); ?></span>
                                <span class="detail-value price-value">
                                    <?php
                                    $price = number_format( $service->base_price, 2 );
                                    if ( $service->price_type === 'hourly' ) {
                                        echo '$' . $price . '/' . __( 'hour', 'schedspot' );
                                    } else {
                                        echo '$' . $price;
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="service-actions">
                        <a href="<?php echo esc_url( add_query_arg( array( 'schedspot_action' => 'booking_form', 'service_id' => $service->id ), home_url( '/' ) ) ); ?>" 
                           class="schedspot-btn schedspot-btn-primary book-service-btn">
                            <?php _e( 'Book Now', 'schedspot' ); ?>
                        </a>
                        
                        <button type="button" class="schedspot-btn schedspot-btn-secondary view-workers-btn" 
                                data-service-id="<?php echo esc_attr( $service->id ); ?>">
                            <?php _e( 'View Workers', 'schedspot' ); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Workers Modal -->
        <div id="workers-modal" class="schedspot-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><?php _e( 'Available Workers', 'schedspot' ); ?></h3>
                    <button type="button" class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="workers-list" class="workers-grid">
                        <!-- Workers will be loaded here via AJAX -->
                    </div>
                </div>
            </div>
        </div>
        
    <?php else : ?>
        <div class="no-services-message">
            <div class="message-content">
                <h3><?php _e( 'No Services Available', 'schedspot' ); ?></h3>
                <p><?php _e( 'There are currently no services available. Please check back later.', 'schedspot' ); ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.schedspot-services-list {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.services-grid {
    display: grid;
    gap: 20px;
    margin-bottom: 30px;
}

.services-grid.columns-1 { grid-template-columns: 1fr; }
.services-grid.columns-2 { grid-template-columns: repeat(2, 1fr); }
.services-grid.columns-3 { grid-template-columns: repeat(3, 1fr); }
.services-grid.columns-4 { grid-template-columns: repeat(4, 1fr); }

@media (max-width: 768px) {
    .services-grid.columns-3,
    .services-grid.columns-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .services-grid {
        grid-template-columns: 1fr;
    }
}

.service-card {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.service-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    border-color: #007cba;
}

.service-header {
    margin-bottom: 15px;
}

.service-name {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 600;
    color: #23282d;
}

.category-badge {
    display: inline-block;
    background: #f0f6fc;
    color: #0073aa;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.service-content {
    margin-bottom: 20px;
}

.service-description p {
    margin: 0 0 15px 0;
    color: #555;
    line-height: 1.5;
}

.service-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.service-duration,
.service-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.detail-label {
    font-weight: 500;
    color: #666;
}

.detail-value {
    font-weight: 600;
    color: #23282d;
}

.price-value {
    color: #007cba;
    font-size: 16px;
}

.service-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.schedspot-btn {
    flex: 1;
    padding: 12px 16px;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
}

.schedspot-btn-primary {
    background: #007cba;
    color: #fff;
}

.schedspot-btn-primary:hover {
    background: #005a87;
    color: #fff;
}

.schedspot-btn-secondary {
    background: #f6f7f7;
    color: #555;
    border: 1px solid #ddd;
}

.schedspot-btn-secondary:hover {
    background: #e9ecef;
    color: #333;
}

.no-services-message {
    text-align: center;
    padding: 60px 20px;
    background: #f9f9f9;
    border-radius: 12px;
}

.message-content h3 {
    margin: 0 0 10px 0;
    color: #666;
}

.message-content p {
    margin: 0;
    color: #888;
}

/* Modal Styles */
.schedspot-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #fff;
    border-radius: 12px;
    max-width: 800px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e1e5e9;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.modal-body {
    padding: 20px;
}

.workers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // View workers button click
    $('.view-workers-btn').on('click', function() {
        var serviceId = $(this).data('service-id');
        loadWorkersForService(serviceId);
    });
    
    // Close modal
    $('.modal-close, .schedspot-modal').on('click', function(e) {
        if (e.target === this) {
            $('#workers-modal').hide();
        }
    });
    
    function loadWorkersForService(serviceId) {
        $('#workers-list').html('<div class="loading">Loading workers...</div>');
        $('#workers-modal').show();
        
        $.ajax({
            url: schedspot_frontend.rest_url + 'workers',
            method: 'GET',
            data: { service_id: serviceId },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(workers) {
                displayWorkers(workers);
            },
            error: function() {
                $('#workers-list').html('<div class="error">Error loading workers.</div>');
            }
        });
    }
    
    function displayWorkers(workers) {
        var html = '';
        
        if (workers.length === 0) {
            html = '<div class="no-workers">No workers available for this service.</div>';
        } else {
            workers.forEach(function(worker) {
                html += '<div class="worker-card">';
                html += '<div class="worker-avatar">';
                html += '<img src="' + (worker.avatar || 'https://via.placeholder.com/60') + '" alt="' + worker.name + '">';
                html += '</div>';
                html += '<div class="worker-info">';
                html += '<h4>' + worker.name + '</h4>';
                html += '<div class="worker-rating">★★★★★ (' + (worker.rating || '5.0') + ')</div>';
                html += '<div class="worker-rate">$' + (worker.hourly_rate || '50') + '/hr</div>';
                if (worker.bio) {
                    html += '<p class="worker-bio">' + worker.bio + '</p>';
                }
                html += '</div>';
                html += '<div class="worker-actions">';
                html += '<a href="' + schedspot_frontend.dashboard_url + '?schedspot_action=booking_form&worker_id=' + worker.id + '" class="schedspot-btn schedspot-btn-primary">Book Now</a>';
                html += '</div>';
                html += '</div>';
            });
        }
        
        $('#workers-list').html(html);
    }
});
</script>
