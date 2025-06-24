<?php
/**
 * Booking Wizard Template
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue wizard-specific assets
wp_enqueue_style( 'schedspot-booking-wizard', SCHEDSPOT_PLUGIN_URL . 'assets/css/booking-wizard.css', array(), SCHEDSPOT_VERSION );
wp_enqueue_script( 'schedspot-booking-wizard', SCHEDSPOT_PLUGIN_URL . 'assets/js/booking-wizard.js', array( 'jquery' ), SCHEDSPOT_VERSION, true );
?>

<div class="schedspot-booking-wizard">
    <!-- Progress Bar -->
    <div class="booking-progress">
        <div class="progress-header">
            <h1 class="progress-title"><?php _e( 'Book Your Service', 'schedspot' ); ?></h1>
            <div class="progress-step-info"><?php _e( 'Step 1 of 5', 'schedspot' ); ?></div>
        </div>
        <div class="progress-bar">
            <div class="progress-step active"></div>
            <div class="progress-step"></div>
            <div class="progress-step"></div>
            <div class="progress-step"></div>
            <div class="progress-step"></div>
        </div>
    </div>

    <!-- Step Content -->
    <div class="booking-steps">
        <!-- Step 1: Service Selection -->
        <div class="booking-step active" data-step="1">
            <div class="step-header">
                <h2 class="step-title"><?php _e( 'Choose Your Service', 'schedspot' ); ?></h2>
                <p class="step-subtitle"><?php _e( 'Select the service you would like to book', 'schedspot' ); ?></p>
            </div>
            <div class="step-content">
                <div class="services-grid">
                    <?php if ( ! empty( $services ) ) : ?>
                        <?php foreach ( $services as $service ) : ?>
                            <div class="service-card" data-service-id="<?php echo esc_attr( $service->id ); ?>" 
                                 data-price="<?php echo esc_attr( $service->base_price ); ?>" 
                                 data-duration="<?php echo esc_attr( $service->duration ); ?>">
                                <h3 class="service-name"><?php echo esc_html( $service->name ); ?></h3>
                                <p class="service-description"><?php echo esc_html( $service->description ); ?></p>
                                <div class="service-meta">
                                    <span class="service-duration"><?php echo esc_html( $service->duration ); ?> <?php _e( 'min', 'schedspot' ); ?></span>
                                    <span class="service-price">$<?php echo esc_html( number_format( $service->base_price, 2 ) ); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="no-services">
                            <p><?php _e( 'No services available at the moment.', 'schedspot' ); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Step 2: Worker Selection -->
        <div class="booking-step" data-step="2">
            <div class="step-header">
                <h2 class="step-title"><?php _e( 'Choose Your Provider', 'schedspot' ); ?></h2>
                <p class="step-subtitle"><?php _e( 'Select a service provider for your booking', 'schedspot' ); ?></p>
            </div>
            <div class="step-content">
                <div class="workers-grid">
                    <!-- Workers will be loaded dynamically -->
                </div>
            </div>
        </div>

        <!-- Step 3: Date & Time Selection -->
        <div class="booking-step" data-step="3">
            <div class="step-header">
                <h2 class="step-title"><?php _e( 'Pick Date & Time', 'schedspot' ); ?></h2>
                <p class="step-subtitle"><?php _e( 'Choose when you would like your service', 'schedspot' ); ?></p>
            </div>
            <div class="step-content">
                <div class="datetime-container">
                    <div class="date-picker-section">
                        <h3 class="section-title"><?php _e( 'Select Date', 'schedspot' ); ?></h3>
                        <input type="date" id="booking_date" class="date-picker" min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
                    </div>
                    <div class="time-picker-section">
                        <h3 class="section-title"><?php _e( 'Available Times', 'schedspot' ); ?></h3>
                        <div class="time-slots">
                            <!-- Time slots will be loaded dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4: Contact Information -->
        <div class="booking-step" data-step="4">
            <div class="step-header">
                <h2 class="step-title"><?php _e( 'Your Information', 'schedspot' ); ?></h2>
                <p class="step-subtitle"><?php _e( 'Please provide your contact details', 'schedspot' ); ?></p>
            </div>
            <div class="step-content">
                <div class="contact-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="client_name"><?php _e( 'Full Name', 'schedspot' ); ?> <span class="required">*</span></label>
                            <input type="text" id="client_name" name="name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="client_email"><?php _e( 'Email Address', 'schedspot' ); ?> <span class="required">*</span></label>
                            <input type="email" id="client_email" name="email" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="client_phone"><?php _e( 'Phone Number', 'schedspot' ); ?> <span class="required">*</span></label>
                            <input type="tel" id="client_phone" name="phone" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="client_address"><?php _e( 'Address', 'schedspot' ); ?></label>
                            <input type="text" id="client_address" name="address" class="form-input">
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label" for="client_notes"><?php _e( 'Additional Notes', 'schedspot' ); ?></label>
                        <textarea id="client_notes" name="notes" class="form-textarea" rows="4" placeholder="<?php _e( 'Any special requests or additional information...', 'schedspot' ); ?>"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 5: Confirmation -->
        <div class="booking-step" data-step="5">
            <div class="step-header">
                <h2 class="step-title"><?php _e( 'Confirm Your Booking', 'schedspot' ); ?></h2>
                <p class="step-subtitle"><?php _e( 'Please review your booking details', 'schedspot' ); ?></p>
            </div>
            <div class="step-content">
                <div class="booking-summary">
                    <!-- Summary will be populated dynamically -->
                </div>
                <div class="booking-terms">
                    <label class="terms-checkbox">
                        <input type="checkbox" id="accept_terms" required>
                        <span class="checkmark"></span>
                        <?php _e( 'I agree to the', 'schedspot' ); ?> 
                        <a href="#" target="_blank"><?php _e( 'Terms of Service', 'schedspot' ); ?></a> 
                        <?php _e( 'and', 'schedspot' ); ?> 
                        <a href="#" target="_blank"><?php _e( 'Privacy Policy', 'schedspot' ); ?></a>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="step-navigation">
        <button type="button" class="nav-btn nav-btn-secondary nav-btn-prev" disabled>
            ← <?php _e( 'Previous', 'schedspot' ); ?>
        </button>
        <button type="button" class="nav-btn nav-btn-primary nav-btn-next" disabled>
            <?php _e( 'Next', 'schedspot' ); ?> →
        </button>
        <button type="button" class="nav-btn nav-btn-primary submit-booking" style="display: none;">
            <?php _e( 'Confirm Booking', 'schedspot' ); ?>
        </button>
    </div>
</div>

<style>
/* Notification styles */
.booking-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 8px;
    padding: 16px 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    z-index: 10001;
    display: flex;
    align-items: center;
    gap: 12px;
    max-width: 400px;
    border-left: 4px solid #007cba;
}

.booking-notification.error {
    border-left-color: #d63638;
}

.booking-notification.success {
    border-left-color: #00a32a;
}

.notification-message {
    flex: 1;
    font-size: 14px;
    color: #333;
}

.notification-close {
    background: none;
    border: none;
    font-size: 18px;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-close:hover {
    color: #333;
}

/* Terms checkbox styling */
.terms-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
    font-size: 14px;
    line-height: 1.5;
    margin-top: 20px;
}

.terms-checkbox input[type="checkbox"] {
    margin: 0;
    width: 18px;
    height: 18px;
    accent-color: #667eea;
}

.terms-checkbox a {
    color: #667eea;
    text-decoration: none;
}

.terms-checkbox a:hover {
    text-decoration: underline;
}

/* Required field indicator */
.required {
    color: #d63638;
    font-weight: bold;
}

/* Loading state for buttons */
.loading {
    position: relative;
    color: transparent !important;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Error states */
.error {
    color: #d63638;
    text-align: center;
    padding: 20px;
    font-style: italic;
}

.no-services {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .booking-notification {
        right: 16px;
        left: 16px;
        max-width: none;
    }
}
</style>
