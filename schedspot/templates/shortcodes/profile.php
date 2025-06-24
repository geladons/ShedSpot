<?php
/**
 * Profile Shortcode Template
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="schedspot-profile-container schedspot-modern-container">
    <!-- Navigation Bar -->
    <div class="schedspot-navigation">
        <div class="schedspot-nav-links">
            <a href="<?php echo esc_url( home_url( '/?schedspot_action=booking_form' ) ); ?>" class="schedspot-nav-link">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e( 'Book a Service', 'schedspot' ); ?>
            </a>
            <a href="<?php echo esc_url( home_url( '/?schedspot_action=dashboard' ) ); ?>" class="schedspot-nav-link">
                <span class="dashicons dashicons-list-view"></span>
                <?php _e( 'My Bookings', 'schedspot' ); ?>
            </a>
            <a href="<?php echo esc_url( home_url( '/?schedspot_action=messages' ) ); ?>" class="schedspot-nav-link">
                <span class="dashicons dashicons-email-alt"></span>
                <?php _e( 'Messages', 'schedspot' ); ?>
            </a>
            <a href="<?php echo esc_url( home_url( '/?schedspot_action=profile' ) ); ?>" class="schedspot-nav-link active">
                <span class="dashicons dashicons-admin-users"></span>
                <?php _e( 'Profile', 'schedspot' ); ?>
            </a>
        </div>
    </div>

    <div class="schedspot-profile-content">
        <div class="schedspot-profile-header">
            <h2><?php _e( 'Profile & Settings', 'schedspot' ); ?></h2>
            <p class="user-role"><?php echo esc_html( ucfirst( str_replace( 'schedspot_', '', $user_role ) ) ); ?></p>
        </div>

        <div class="schedspot-profile-tabs">
            <nav class="schedspot-tab-nav">
                <?php foreach ( $available_tabs as $tab_key => $tab_label ) : ?>
                    <button type="button"
                            class="tab-button <?php echo $current_tab === $tab_key ? 'active' : ''; ?>"
                            data-tab="<?php echo esc_attr( $tab_key ); ?>">
                        <?php echo esc_html( $tab_label ); ?>
                    </button>
                <?php endforeach; ?>
            </nav>

            <div class="tab-content">
                <?php if ( $current_tab === 'general' ) : ?>
                    <div class="schedspot-card">
                        <div class="schedspot-card-header">
                            <h3><?php _e( 'General Information', 'schedspot' ); ?></h3>
                        </div>
                        <div class="schedspot-card-body">
                            <form method="post" enctype="multipart/form-data" class="schedspot-form">
                                <?php wp_nonce_field( 'update_general_profile', '_wpnonce' ); ?>
                                <input type="hidden" name="action" value="update_general">

                                <div class="schedspot-form-grid">
                                    <div class="schedspot-form-group">
                                        <label for="first_name"><?php _e( 'First Name', 'schedspot' ); ?> <span class="required">*</span></label>
                                        <input type="text" id="first_name" name="first_name"
                                               value="<?php echo esc_attr( $profile_data['general']['first_name'] ); ?>" required>
                                    </div>

                                    <div class="schedspot-form-group">
                                        <label for="last_name"><?php _e( 'Last Name', 'schedspot' ); ?> <span class="required">*</span></label>
                                        <input type="text" id="last_name" name="last_name"
                                               value="<?php echo esc_attr( $profile_data['general']['last_name'] ); ?>" required>
                                    </div>

                                    <div class="schedspot-form-group">
                                        <label for="email"><?php _e( 'Email Address', 'schedspot' ); ?> <span class="required">*</span></label>
                                        <input type="email" id="email" name="email"
                                               value="<?php echo esc_attr( $profile_data['general']['email'] ); ?>" required>
                                    </div>

                                    <div class="schedspot-form-group">
                                        <label for="phone"><?php _e( 'Phone Number', 'schedspot' ); ?></label>
                                        <input type="tel" id="phone" name="phone"
                                               value="<?php echo esc_attr( $profile_data['general']['phone'] ); ?>">
                                    </div>
                                </div>

                                <div class="schedspot-form-group">
                                    <label for="bio"><?php _e( 'Bio', 'schedspot' ); ?></label>
                                    <textarea id="bio" name="bio" rows="4" class="schedspot-textarea"><?php echo esc_textarea( $profile_data['general']['bio'] ); ?></textarea>
                                </div>

                                <div class="schedspot-form-group">
                                    <label><?php _e( 'Profile Picture', 'schedspot' ); ?></label>
                                    <div class="schedspot-avatar-upload">
                                        <div class="current-avatar">
                                            <?php echo get_avatar( $current_user->ID, 80 ); ?>
                                        </div>
                                        <div class="upload-controls">
                                            <input type="file" id="avatar" name="avatar" accept="image/*" class="schedspot-file-input">
                                            <label for="avatar" class="schedspot-btn schedspot-btn-secondary">
                                                <?php _e( 'Choose File', 'schedspot' ); ?>
                                            </label>
                                            <small class="form-help"><?php _e( 'Maximum file size: 2MB. Supported formats: JPG, PNG, GIF', 'schedspot' ); ?></small>
                                        </div>
                                    </div>
                                </div>

                                <div class="schedspot-form-actions">
                                    <button type="submit" class="schedspot-btn schedspot-btn-primary">
                                        <span class="dashicons dashicons-update"></span>
                                        <?php _e( 'Update Profile', 'schedspot' ); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php elseif ( $current_tab === 'notifications' ) : ?>
                    <div class="schedspot-card">
                        <div class="schedspot-card-header">
                            <h3><?php _e( 'Notification Preferences', 'schedspot' ); ?></h3>
                        </div>
                        <div class="schedspot-card-body">
                            <form method="post" class="schedspot-form">
                                <?php wp_nonce_field( 'update_notifications', '_wpnonce' ); ?>
                                <input type="hidden" name="action" value="update_notifications">

                                <div class="schedspot-notification-settings">
                                    <div class="schedspot-privacy-setting">
                                        <div class="setting-info">
                                            <h4><?php _e( 'Email Notifications', 'schedspot' ); ?></h4>
                                            <p><?php _e( 'Receive email notifications for booking updates and important messages.', 'schedspot' ); ?></p>
                                        </div>
                                        <label class="schedspot-toggle">
                                            <input type="checkbox" name="email_notifications" value="1"
                                                   <?php checked( $profile_data['notifications']['email_notifications'] ); ?>>
                                            <span class="schedspot-toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="schedspot-privacy-setting">
                                        <div class="setting-info">
                                            <h4><?php _e( 'SMS Notifications', 'schedspot' ); ?></h4>
                                            <p><?php _e( 'Receive SMS notifications for urgent updates and reminders.', 'schedspot' ); ?></p>
                                        </div>
                                        <label class="schedspot-toggle">
                                            <input type="checkbox" name="sms_notifications" value="1"
                                                   <?php checked( $profile_data['notifications']['sms_notifications'] ); ?>>
                                            <span class="schedspot-toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="schedspot-privacy-setting">
                                        <div class="setting-info">
                                            <h4><?php _e( 'Booking Reminders', 'schedspot' ); ?></h4>
                                            <p><?php _e( 'Receive reminders before your scheduled appointments.', 'schedspot' ); ?></p>
                                        </div>
                                        <label class="schedspot-toggle">
                                            <input type="checkbox" name="booking_reminders" value="1"
                                                   <?php checked( $profile_data['notifications']['booking_reminders'] ); ?>>
                                            <span class="schedspot-toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="schedspot-privacy-setting">
                                        <div class="setting-info">
                                            <h4><?php _e( 'Marketing Emails', 'schedspot' ); ?></h4>
                                            <p><?php _e( 'Receive promotional emails and service updates.', 'schedspot' ); ?></p>
                                        </div>
                                        <label class="schedspot-toggle">
                                            <input type="checkbox" name="marketing_emails" value="1"
                                                   <?php checked( $profile_data['notifications']['marketing_emails'] ); ?>>
                                            <span class="schedspot-toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="schedspot-form-actions">
                                    <button type="submit" class="schedspot-btn schedspot-btn-primary">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php _e( 'Save Preferences', 'schedspot' ); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php elseif ( $current_tab === 'privacy' ) : ?>
                    <div class="schedspot-card">
                        <div class="schedspot-card-header">
                            <h3><?php _e( 'Privacy Settings', 'schedspot' ); ?></h3>
                        </div>
                        <div class="schedspot-card-body">
                            <form method="post" class="schedspot-form">
                                <?php wp_nonce_field( 'update_privacy', '_wpnonce' ); ?>
                                <input type="hidden" name="action" value="update_privacy">

                                <div class="schedspot-form-group">
                                    <label for="profile_visibility"><?php _e( 'Profile Visibility', 'schedspot' ); ?></label>
                                    <select id="profile_visibility" name="profile_visibility" class="schedspot-select">
                                        <option value="public" <?php selected( $profile_data['privacy']['profile_visibility'], 'public' ); ?>>
                                            <?php _e( 'Public - Visible to everyone', 'schedspot' ); ?>
                                        </option>
                                        <option value="registered" <?php selected( $profile_data['privacy']['profile_visibility'], 'registered' ); ?>>
                                            <?php _e( 'Registered Users Only', 'schedspot' ); ?>
                                        </option>
                                        <option value="private" <?php selected( $profile_data['privacy']['profile_visibility'], 'private' ); ?>>
                                            <?php _e( 'Private - Hidden from search', 'schedspot' ); ?>
                                        </option>
                                    </select>
                                </div>

                                <div class="schedspot-privacy-settings">
                                    <div class="schedspot-privacy-setting">
                                        <div class="setting-info">
                                            <h4><?php _e( 'Show Contact Information', 'schedspot' ); ?></h4>
                                            <p><?php _e( 'Allow others to see your contact details in your profile.', 'schedspot' ); ?></p>
                                        </div>
                                        <label class="schedspot-toggle">
                                            <input type="checkbox" name="show_contact_info" value="1"
                                                   <?php checked( $profile_data['privacy']['show_contact_info'] ); ?>>
                                            <span class="schedspot-toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="schedspot-privacy-setting">
                                        <div class="setting-info">
                                            <h4><?php _e( 'Allow Direct Booking', 'schedspot' ); ?></h4>
                                            <p><?php _e( 'Allow clients to book services directly without approval.', 'schedspot' ); ?></p>
                                        </div>
                                        <label class="schedspot-toggle">
                                            <input type="checkbox" name="allow_direct_booking" value="1"
                                                   <?php checked( $profile_data['privacy']['allow_direct_booking'] ); ?>>
                                            <span class="schedspot-toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="schedspot-form-actions">
                                    <button type="submit" class="schedspot-btn schedspot-btn-primary">
                                        <span class="dashicons dashicons-privacy"></span>
                                        <?php _e( 'Update Privacy Settings', 'schedspot' ); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching functionality
    $('.tab-button').on('click', function() {
        var targetTab = $(this).data('tab');
        var currentUrl = new URL(window.location);
        currentUrl.searchParams.set('tab', targetTab);
        window.location.href = currentUrl.toString();
    });

    // File input styling
    $('.schedspot-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        var label = $(this).next('label');
        if (fileName) {
            label.text(fileName);
        } else {
            label.text('<?php _e( 'Choose File', 'schedspot' ); ?>');
        }
    });

    // Form validation
    $('.schedspot-form').on('submit', function(e) {
        var form = $(this);
        var requiredFields = form.find('[required]');
        var isValid = true;

        requiredFields.each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('error');
                isValid = false;
            } else {
                $(this).removeClass('error');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('<?php _e( 'Please fill in all required fields.', 'schedspot' ); ?>');
        }
    });

    // Show success/error messages
    <?php if ( isset( $_GET['updated'] ) ) : ?>
        var message = '';
        switch ('<?php echo esc_js( $_GET['updated'] ); ?>') {
            case 'general':
                message = '<?php _e( 'Profile updated successfully!', 'schedspot' ); ?>';
                break;
            case 'notifications':
                message = '<?php _e( 'Notification preferences saved!', 'schedspot' ); ?>';
                break;
            case 'privacy':
                message = '<?php _e( 'Privacy settings updated!', 'schedspot' ); ?>';
                break;
            default:
                message = '<?php _e( 'Settings updated successfully!', 'schedspot' ); ?>';
        }

        if (message) {
            $('<div class="schedspot-notice schedspot-notice-success">' + message + '</div>')
                .prependTo('.schedspot-profile-content')
                .delay(5000)
                .fadeOut();
        }
    <?php endif; ?>
});
</script>
