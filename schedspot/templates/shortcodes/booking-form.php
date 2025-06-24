<?php
/**
 * Booking Form Template
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="schedspot-form-container">
    <?php if ( isset( $error_message ) ) : ?>
        <div class="schedspot-notice error">
            <?php echo wp_kses_post( $error_message ); ?>
        </div>
    <?php endif; ?>

    <form id="schedspot-booking-form" method="post" class="schedspot-booking-form">
        <?php wp_nonce_field( 'schedspot_booking_form', 'schedspot_booking_nonce' ); ?>

        <!-- Service Selection -->
        <div class="schedspot-form-section">
            <h3><?php _e( 'Select Service', 'schedspot' ); ?></h3>
            
            <div class="schedspot-form-row">
                <label for="schedspot_service_id"><?php _e( 'Service *', 'schedspot' ); ?></label>
                <select id="schedspot_service_id" name="schedspot_service_id" required>
                    <option value=""><?php _e( 'Choose a service...', 'schedspot' ); ?></option>
                    <?php foreach ( $services as $service ) : ?>
                        <option value="<?php echo esc_attr( $service->id ); ?>"
                                <?php selected( $selected_service ? $selected_service->id : '', $service->id ); ?>
                                data-price="<?php echo esc_attr( $service->base_price ); ?>"
                                data-duration="<?php echo esc_attr( $service->duration ); ?>">
                            <?php echo esc_html( $service->name ); ?>
                            <?php if ( $service->base_price > 0 ) : ?>
                                - $<?php echo number_format( $service->base_price, 2 ); ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ( $selected_service ) : ?>
                <div class="service-details">
                    <h4><?php echo esc_html( $selected_service->name ); ?></h4>
                    <p><?php echo esc_html( $selected_service->description ); ?></p>
                    <?php if ( $selected_service->duration > 0 ) : ?>
                        <p><strong><?php _e( 'Duration:', 'schedspot' ); ?></strong> <?php echo esc_html( $selected_service->duration ); ?> <?php _e( 'minutes', 'schedspot' ); ?></p>
                    <?php endif; ?>
                    <?php if ( $selected_service->base_price > 0 ) : ?>
                        <p><strong><?php _e( 'Price:', 'schedspot' ); ?></strong> $<?php echo number_format( $selected_service->base_price, 2 ); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Worker Selection -->
        <?php if ( $atts['show_workers'] === 'true' && ! empty( $workers ) ) : ?>
            <div class="schedspot-form-section">
                <h3><?php _e( 'Choose Worker', 'schedspot' ); ?></h3>
                
                <div class="worker-selection-mode">
                    <label>
                        <input type="radio" name="worker_selection_mode" value="auto" checked>
                        <?php _e( 'Automatically assign best available worker', 'schedspot' ); ?>
                    </label>
                    <label>
                        <input type="radio" name="worker_selection_mode" value="manual">
                        <?php _e( 'I want to choose a specific worker', 'schedspot' ); ?>
                    </label>
                </div>

                <div id="manual-worker-selection" style="display: none;">
                    <input type="hidden" id="schedspot_worker_id" name="schedspot_worker_id" value="<?php echo esc_attr( $selected_worker ? $selected_worker->ID : '' ); ?>">
                    
                    <div class="schedspot-workers-grid">
                        <?php foreach ( $workers as $worker ) : ?>
                            <div class="worker-card available" data-worker-id="<?php echo esc_attr( $worker->ID ); ?>">
                                <div class="worker-avatar">
                                    <img src="<?php echo esc_url( $worker->avatar ); ?>" alt="<?php echo esc_attr( $worker->display_name ); ?>">
                                </div>
                                
                                <div class="worker-info">
                                    <h4><?php echo esc_html( $worker->display_name ); ?></h4>
                                    
                                    <div class="worker-rating">
                                        <span class="stars">★★★★★</span>
                                        <span class="rating-text"><?php echo number_format( $worker->rating, 1 ); ?></span>
                                    </div>
                                    
                                    <?php if ( ! empty( $worker->skills ) ) : ?>
                                        <div class="worker-specialties">
                                            <?php foreach ( array_slice( $worker->skills, 0, 3 ) as $skill ) : ?>
                                                <span class="specialty-tag"><?php echo esc_html( $skill ); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ( $worker->hourly_rate > 0 ) : ?>
                                        <div class="worker-price">
                                            $<?php echo number_format( $worker->hourly_rate, 2 ); ?>/hr
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="worker-availability">
                                        <?php _e( 'Available', 'schedspot' ); ?>
                                    </div>
                                </div>
                                
                                <div class="worker-actions">
                                    <button type="button" class="schedspot-btn schedspot-btn-primary select-worker-btn" data-worker-id="<?php echo esc_attr( $worker->ID ); ?>">
                                        <?php _e( 'Select', 'schedspot' ); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Date and Time Selection -->
        <div class="schedspot-form-section">
            <h3><?php _e( 'Select Date & Time', 'schedspot' ); ?></h3>
            
            <div class="schedspot-form-row">
                <label for="schedspot_booking_date"><?php _e( 'Preferred Date *', 'schedspot' ); ?></label>
                <input type="date" id="schedspot_booking_date" name="schedspot_booking_date" required min="<?php echo date( 'Y-m-d' ); ?>">
            </div>
            
            <div class="schedspot-form-row">
                <label for="schedspot_booking_time"><?php _e( 'Preferred Time *', 'schedspot' ); ?></label>
                <select id="schedspot_booking_time" name="schedspot_booking_time" required>
                    <option value=""><?php _e( 'Choose time...', 'schedspot' ); ?></option>
                    <?php
                    for ( $hour = 8; $hour <= 18; $hour++ ) {
                        for ( $minute = 0; $minute < 60; $minute += 30 ) {
                            $time = sprintf( '%02d:%02d', $hour, $minute );
                            $display_time = date( 'g:i A', strtotime( $time ) );
                            echo '<option value="' . esc_attr( $time ) . '">' . esc_html( $display_time ) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Client Information -->
        <div class="schedspot-form-section">
            <h3><?php _e( 'Your Information', 'schedspot' ); ?></h3>
            
            <div class="schedspot-form-row">
                <label for="schedspot_client_name"><?php _e( 'Full Name *', 'schedspot' ); ?></label>
                <input type="text" id="schedspot_client_name" name="schedspot_client_name" required>
            </div>
            
            <div class="schedspot-form-row">
                <label for="schedspot_client_email"><?php _e( 'Email Address *', 'schedspot' ); ?></label>
                <input type="email" id="schedspot_client_email" name="schedspot_client_email" required>
            </div>
            
            <div class="schedspot-form-row">
                <label for="schedspot_client_phone"><?php _e( 'Phone Number *', 'schedspot' ); ?></label>
                <input type="tel" id="schedspot_client_phone" name="schedspot_client_phone" required>
            </div>
            
            <div class="schedspot-form-row">
                <label for="schedspot_client_address"><?php _e( 'Address', 'schedspot' ); ?></label>
                <input type="text" id="schedspot_client_address" name="schedspot_client_address" placeholder="<?php esc_attr_e( 'Street address, city, state, zip', 'schedspot' ); ?>">
            </div>
        </div>

        <!-- Additional Information -->
        <div class="schedspot-form-section">
            <h3><?php _e( 'Additional Information', 'schedspot' ); ?></h3>
            
            <div class="schedspot-form-row">
                <label for="schedspot_notes"><?php _e( 'Special Requests or Notes', 'schedspot' ); ?></label>
                <textarea id="schedspot_notes" name="schedspot_notes" rows="4" placeholder="<?php esc_attr_e( 'Any special requirements, questions, or additional information...', 'schedspot' ); ?>"></textarea>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="schedspot-form-section">
            <div class="schedspot-form-row">
                <button type="submit" name="schedspot_submit_booking" class="schedspot-btn schedspot-btn-primary schedspot-btn-large">
                    <?php _e( 'Submit Booking Request', 'schedspot' ); ?>
                </button>
            </div>
            
            <p class="form-disclaimer">
                <?php _e( 'By submitting this form, you agree to our terms of service. Your booking request will be reviewed and you will receive a confirmation email shortly.', 'schedspot' ); ?>
            </p>
        </div>
    </form>
</div>

<style>
.schedspot-form-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.schedspot-form-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.schedspot-form-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.schedspot-form-row {
    margin-bottom: 20px;
}

.schedspot-form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.schedspot-form-row input,
.schedspot-form-row select,
.schedspot-form-row textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.schedspot-form-row input:focus,
.schedspot-form-row select:focus,
.schedspot-form-row textarea:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.2);
}

.schedspot-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.schedspot-btn-primary {
    background: #0073aa;
    color: #fff;
}

.schedspot-btn-primary:hover {
    background: #005a87;
    transform: translateY(-1px);
}

.schedspot-btn-large {
    padding: 15px 30px;
    font-size: 16px;
    width: 100%;
}

.service-details {
    margin-top: 15px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.form-disclaimer {
    font-size: 12px;
    color: #666;
    margin-top: 15px;
    text-align: center;
}

@media (max-width: 768px) {
    .schedspot-form-container {
        padding: 15px;
    }
    
    .schedspot-form-section {
        padding: 15px;
    }
}
</style>
