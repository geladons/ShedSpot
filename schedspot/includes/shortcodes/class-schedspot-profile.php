<?php
/**
 * SchedSpot Profile Shortcode
 *
 * Handles the profile management shortcode functionality
 *
 * @package SchedSpot
 * @version 1.6.1
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Profile Class.
 *
 * @class SchedSpot_Profile
 * @version 1.6.1
 */
class SchedSpot_Profile {

    /**
     * Constructor.
     *
     * @since 1.6.1
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize profile functionality.
     *
     * @since 1.6.1
     */
    public function init() {
        add_shortcode( 'schedspot_profile', array( $this, 'render_profile' ) );
        add_action( 'wp_ajax_schedspot_update_profile', array( $this, 'update_profile' ) );
        add_action( 'wp_ajax_schedspot_upload_avatar', array( $this, 'upload_avatar' ) );
        add_action( 'wp_ajax_schedspot_update_worker_profile', array( $this, 'update_worker_profile' ) );
        add_action( 'wp_ajax_schedspot_export_user_data', array( $this, 'export_user_data' ) );
        add_action( 'wp_ajax_schedspot_delete_user_account', array( $this, 'delete_user_account' ) );
    }

    /**
     * Render profile shortcode.
     *
     * @since 1.6.1
     * @param array $atts Shortcode attributes.
     * @return string Profile management HTML.
     */
    public function render_profile( $atts ) {
        if ( ! is_user_logged_in() ) {
            return $this->render_login_prompt();
        }

        $current_user = wp_get_current_user();
        $user_role = $this->get_user_primary_role( $current_user );

        $atts = shortcode_atts( array(
            'show_tabs' => 'true',
            'default_tab' => 'general',
            'show_worker_fields' => 'auto', // auto, true, false
        ), $atts );

        // Auto-detect worker fields based on user role
        if ( $atts['show_worker_fields'] === 'auto' ) {
            $atts['show_worker_fields'] = ( $user_role === 'schedspot_worker' ) ? 'true' : 'false';
        }

        ob_start();
        ?>
        <div class="schedspot-profile-content">
            <div class="profile-header">
                <h2><?php _e( 'Profile Settings', 'schedspot' ); ?></h2>
                <p><?php _e( 'Manage your account information and preferences.', 'schedspot' ); ?></p>
            </div>

            <?php if ( $atts['show_tabs'] === 'true' ) : ?>
                <div class="profile-tabs">
                    <button class="tab-button active" data-tab="general">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php _e( 'General', 'schedspot' ); ?>
                    </button>
                    
                    <?php if ( $atts['show_worker_fields'] === 'true' ) : ?>
                        <button class="tab-button" data-tab="worker-profile">
                            <span class="dashicons dashicons-businessman"></span>
                            <?php _e( 'Worker Profile', 'schedspot' ); ?>
                        </button>
                    <?php endif; ?>
                    
                    <button class="tab-button" data-tab="notifications">
                        <span class="dashicons dashicons-bell"></span>
                        <?php _e( 'Notifications', 'schedspot' ); ?>
                    </button>
                    
                    <button class="tab-button" data-tab="privacy">
                        <span class="dashicons dashicons-privacy"></span>
                        <?php _e( 'Privacy', 'schedspot' ); ?>
                    </button>
                    
                    <button class="tab-button" data-tab="account">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e( 'Account', 'schedspot' ); ?>
                    </button>
                </div>
            <?php endif; ?>

            <!-- General Tab -->
            <div id="general" class="tab-content active">
                <?php $this->render_general_profile_form( $current_user ); ?>
            </div>

            <?php if ( $atts['show_worker_fields'] === 'true' ) : ?>
                <!-- Worker Profile Tab -->
                <div id="worker-profile" class="tab-content">
                    <?php $this->render_worker_profile_form( $current_user ); ?>
                </div>
            <?php endif; ?>

            <!-- Notifications Tab -->
            <div id="notifications" class="tab-content">
                <?php $this->render_notifications_form( $current_user ); ?>
            </div>

            <!-- Privacy Tab -->
            <div id="privacy" class="tab-content">
                <?php $this->render_privacy_form( $current_user ); ?>
            </div>

            <!-- Account Tab -->
            <div id="account" class="tab-content">
                <?php $this->render_account_form( $current_user ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render login prompt for non-logged-in users.
     *
     * @since 1.6.1
     * @return string Login prompt HTML.
     */
    private function render_login_prompt() {
        ob_start();
        ?>
        <div class="schedspot-login-prompt">
            <div class="login-prompt-content">
                <span class="dashicons dashicons-admin-users"></span>
                <h3><?php _e( 'Please Log In', 'schedspot' ); ?></h3>
                <p><?php _e( 'You need to be logged in to access your profile settings.', 'schedspot' ); ?></p>
                <div class="login-prompt-actions">
                    <a href="<?php echo wp_login_url( get_permalink() ); ?>" class="schedspot-btn schedspot-btn-primary">
                        <?php _e( 'Log In', 'schedspot' ); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render general profile form.
     *
     * @since 1.6.1
     * @param WP_User $user Current user object.
     */
    private function render_general_profile_form( $user ) {
        ?>
        <form class="profile-form general-profile-form" method="post">
            <?php wp_nonce_field( 'schedspot_update_profile', 'profile_nonce' ); ?>
            
            <div class="form-section">
                <h3><?php _e( 'Basic Information', 'schedspot' ); ?></h3>
                
                <div class="avatar-upload-section">
                    <div class="current-avatar">
                        <?php echo get_avatar( $user->ID, 100 ); ?>
                    </div>
                    <button type="button" class="avatar-upload-btn">
                        <span class="dashicons dashicons-camera"></span>
                        <?php _e( 'Change Avatar', 'schedspot' ); ?>
                    </button>
                    <input type="file" id="avatar-file-input" accept="image/*" style="display: none;">
                </div>

                <div class="form-row">
                    <label for="display_name"><?php _e( 'Display Name', 'schedspot' ); ?> *</label>
                    <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" required>
                </div>

                <div class="form-row">
                    <label for="user_email"><?php _e( 'Email Address', 'schedspot' ); ?> *</label>
                    <input type="email" id="user_email" name="user_email" value="<?php echo esc_attr( $user->user_email ); ?>" required>
                </div>

                <div class="form-row">
                    <label for="user_phone"><?php _e( 'Phone Number', 'schedspot' ); ?></label>
                    <input type="tel" id="user_phone" name="user_phone" value="<?php echo esc_attr( get_user_meta( $user->ID, 'schedspot_user_phone', true ) ); ?>">
                </div>

                <div class="form-row">
                    <label for="user_address"><?php _e( 'Address', 'schedspot' ); ?></label>
                    <textarea id="user_address" name="user_address" rows="3"><?php echo esc_textarea( get_user_meta( $user->ID, 'schedspot_user_address', true ) ); ?></textarea>
                </div>

                <div class="form-row">
                    <label for="user_bio"><?php _e( 'Bio', 'schedspot' ); ?></label>
                    <textarea id="user_bio" name="user_bio" rows="4" placeholder="<?php _e( 'Tell us about yourself...', 'schedspot' ); ?>"><?php echo esc_textarea( get_user_meta( $user->ID, 'schedspot_user_bio', true ) ); ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span class="dashicons dashicons-yes"></span>
                    <?php _e( 'Save Changes', 'schedspot' ); ?>
                </button>
            </div>
        </form>
        <?php
    }

    /**
     * Render worker profile form.
     *
     * @since 1.6.1
     * @param WP_User $user Current user object.
     */
    private function render_worker_profile_form( $user ) {
        $worker_skills = get_user_meta( $user->ID, 'schedspot_worker_skills', true ) ?: '';
        $worker_hourly_rate = get_user_meta( $user->ID, 'schedspot_worker_hourly_rate', true ) ?: '';
        $worker_experience = get_user_meta( $user->ID, 'schedspot_worker_experience', true ) ?: '';
        $worker_certifications = get_user_meta( $user->ID, 'schedspot_worker_certifications', true ) ?: '';
        ?>
        <form class="profile-form worker-profile-form" method="post">
            <?php wp_nonce_field( 'schedspot_update_worker_profile', 'worker_profile_nonce' ); ?>
            
            <div class="form-section">
                <h3><?php _e( 'Professional Information', 'schedspot' ); ?></h3>
                
                <div class="form-row">
                    <label for="worker_hourly_rate"><?php _e( 'Hourly Rate ($)', 'schedspot' ); ?></label>
                    <input type="number" id="worker_hourly_rate" name="worker_hourly_rate" min="0" step="0.01" value="<?php echo esc_attr( $worker_hourly_rate ); ?>">
                    <div class="description"><?php _e( 'Your standard hourly rate for services.', 'schedspot' ); ?></div>
                </div>

                <div class="form-row">
                    <label for="worker_experience"><?php _e( 'Years of Experience', 'schedspot' ); ?></label>
                    <select id="worker_experience" name="worker_experience">
                        <option value=""><?php _e( 'Select experience level', 'schedspot' ); ?></option>
                        <option value="0-1" <?php selected( $worker_experience, '0-1' ); ?>><?php _e( 'Less than 1 year', 'schedspot' ); ?></option>
                        <option value="1-3" <?php selected( $worker_experience, '1-3' ); ?>><?php _e( '1-3 years', 'schedspot' ); ?></option>
                        <option value="3-5" <?php selected( $worker_experience, '3-5' ); ?>><?php _e( '3-5 years', 'schedspot' ); ?></option>
                        <option value="5-10" <?php selected( $worker_experience, '5-10' ); ?>><?php _e( '5-10 years', 'schedspot' ); ?></option>
                        <option value="10+" <?php selected( $worker_experience, '10+' ); ?>><?php _e( '10+ years', 'schedspot' ); ?></option>
                    </select>
                </div>

                <div class="form-row">
                    <label for="worker_skills"><?php _e( 'Skills', 'schedspot' ); ?></label>
                    <input type="text" id="worker_skills" name="worker_skills" value="<?php echo esc_attr( $worker_skills ); ?>" placeholder="<?php _e( 'e.g., Cleaning, Plumbing, Electrical', 'schedspot' ); ?>">
                    <div class="description"><?php _e( 'Separate multiple skills with commas.', 'schedspot' ); ?></div>
                    
                    <div class="skills-container">
                        <?php if ( $worker_skills ) : ?>
                            <?php foreach ( explode( ',', $worker_skills ) as $skill ) : ?>
                                <div class="skill-tag selected">
                                    <?php echo esc_html( trim( $skill ) ); ?>
                                    <button type="button" class="remove-skill">&times;</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="add-skill-input">
                        <input type="text" placeholder="<?php _e( 'Add a skill...', 'schedspot' ); ?>">
                        <button type="button" class="add-skill-btn"><?php _e( 'Add', 'schedspot' ); ?></button>
                    </div>
                </div>

                <div class="form-row">
                    <label for="worker_certifications"><?php _e( 'Certifications', 'schedspot' ); ?></label>
                    <textarea id="worker_certifications" name="worker_certifications" rows="3" placeholder="<?php _e( 'List any relevant certifications or licenses...', 'schedspot' ); ?>"><?php echo esc_textarea( $worker_certifications ); ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span class="dashicons dashicons-yes"></span>
                    <?php _e( 'Save Worker Profile', 'schedspot' ); ?>
                </button>
            </div>
        </form>
        <?php
    }

    /**
     * Render notifications form.
     *
     * @since 1.6.1
     * @param WP_User $user Current user object.
     */
    private function render_notifications_form( $user ) {
        $email_notifications = get_user_meta( $user->ID, 'schedspot_email_notifications', true ) !== 'no';
        $sms_notifications = get_user_meta( $user->ID, 'schedspot_sms_notifications', true ) === 'yes';
        $booking_reminders = get_user_meta( $user->ID, 'schedspot_booking_reminders', true ) !== 'no';
        ?>
        <form class="profile-form notification-settings-form" method="post">
            <?php wp_nonce_field( 'schedspot_update_notifications', 'notifications_nonce' ); ?>
            
            <div class="form-section">
                <h3><?php _e( 'Notification Preferences', 'schedspot' ); ?></h3>
                
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="email_notifications" name="email_notifications" value="yes" <?php checked( $email_notifications ); ?>>
                        <label for="email_notifications"><?php _e( 'Email Notifications', 'schedspot' ); ?></label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input type="checkbox" id="sms_notifications" name="sms_notifications" value="yes" <?php checked( $sms_notifications ); ?>>
                        <label for="sms_notifications"><?php _e( 'SMS Notifications', 'schedspot' ); ?></label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input type="checkbox" id="booking_reminders" name="booking_reminders" value="yes" <?php checked( $booking_reminders ); ?>>
                        <label for="booking_reminders"><?php _e( 'Booking Reminders', 'schedspot' ); ?></label>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span class="dashicons dashicons-yes"></span>
                    <?php _e( 'Save Notification Settings', 'schedspot' ); ?>
                </button>
            </div>
        </form>
        <?php
    }

    /**
     * Render privacy form.
     *
     * @since 1.6.1
     * @param WP_User $user Current user object.
     */
    private function render_privacy_form( $user ) {
        ?>
        <form class="profile-form privacy-settings-form" method="post">
            <?php wp_nonce_field( 'schedspot_update_privacy', 'privacy_nonce' ); ?>
            
            <div class="form-section">
                <h3><?php _e( 'Privacy Settings', 'schedspot' ); ?></h3>
                
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="profile_public" name="profile_public" value="yes">
                        <label for="profile_public"><?php _e( 'Make my profile public', 'schedspot' ); ?></label>
                    </div>
                    
                    <div class="checkbox-item">
                        <input type="checkbox" id="show_contact_info" name="show_contact_info" value="yes">
                        <label for="show_contact_info"><?php _e( 'Show contact information to clients', 'schedspot' ); ?></label>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span class="dashicons dashicons-yes"></span>
                    <?php _e( 'Save Privacy Settings', 'schedspot' ); ?>
                </button>
            </div>
        </form>
        <?php
    }

    /**
     * Render account management form.
     *
     * @since 1.6.1
     * @param WP_User $user Current user object.
     */
    private function render_account_form( $user ) {
        ?>
        <div class="form-section">
            <h3><?php _e( 'Password Change', 'schedspot' ); ?></h3>
            
            <form class="profile-form password-change-form" method="post">
                <?php wp_nonce_field( 'schedspot_change_password', 'password_nonce' ); ?>
                
                <div class="form-row">
                    <label for="current_password"><?php _e( 'Current Password', 'schedspot' ); ?> *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-row">
                    <label for="new_password"><?php _e( 'New Password', 'schedspot' ); ?> *</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>

                <div class="form-row">
                    <label for="confirm_password"><?php _e( 'Confirm New Password', 'schedspot' ); ?> *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span class="dashicons dashicons-lock"></span>
                        <?php _e( 'Change Password', 'schedspot' ); ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="danger-zone">
            <h3><?php _e( 'Danger Zone', 'schedspot' ); ?></h3>
            <p><?php _e( 'These actions cannot be undone. Please proceed with caution.', 'schedspot' ); ?></p>
            
            <div class="danger-actions">
                <button type="button" class="btn btn-secondary export-data-btn">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e( 'Export My Data', 'schedspot' ); ?>
                </button>
                
                <button type="button" class="btn btn-danger delete-account-btn">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e( 'Delete Account', 'schedspot' ); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Update user profile via AJAX.
     *
     * @since 1.6.1
     */
    public function update_profile() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'schedspot' ) ) );
        }

        if ( ! wp_verify_nonce( $_POST['profile_nonce'], 'schedspot_update_profile' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'schedspot' ) ) );
        }

        $user_id = get_current_user_id();
        $user_data = array(
            'ID' => $user_id,
            'display_name' => sanitize_text_field( $_POST['display_name'] ),
            'user_email' => sanitize_email( $_POST['user_email'] ),
        );

        $result = wp_update_user( $user_data );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        // Update meta fields
        update_user_meta( $user_id, 'schedspot_user_phone', sanitize_text_field( $_POST['user_phone'] ) );
        update_user_meta( $user_id, 'schedspot_user_address', sanitize_textarea_field( $_POST['user_address'] ) );
        update_user_meta( $user_id, 'schedspot_user_bio', sanitize_textarea_field( $_POST['user_bio'] ) );

        wp_send_json_success( array( 'message' => __( 'Profile updated successfully.', 'schedspot' ) ) );
    }

    /**
     * Upload avatar via AJAX.
     *
     * @since 1.6.1
     */
    public function upload_avatar() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'schedspot' ) ) );
        }

        if ( empty( $_FILES['avatar'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'schedspot' ) ) );
        }

        $upload = wp_handle_upload( $_FILES['avatar'], array( 'test_form' => false ) );

        if ( isset( $upload['error'] ) ) {
            wp_send_json_error( array( 'message' => $upload['error'] ) );
        }

        $user_id = get_current_user_id();
        update_user_meta( $user_id, 'schedspot_avatar_url', $upload['url'] );

        wp_send_json_success( array(
            'message' => __( 'Avatar updated successfully.', 'schedspot' ),
            'avatar_url' => $upload['url']
        ) );
    }

    /**
     * Get user's primary role.
     *
     * @since 1.6.1
     * @param WP_User $user User object.
     * @return string Primary role.
     */
    private function get_user_primary_role( $user ) {
        if ( empty( $user->roles ) ) {
            return 'subscriber';
        }
        
        if ( in_array( 'schedspot_worker', $user->roles ) ) {
            return 'schedspot_worker';
        }
        
        return $user->roles[0];
    }

    // Placeholder methods for additional functionality
    public function update_worker_profile() {
        // Worker profile update logic
        wp_send_json_success( array( 'message' => __( 'Worker profile updated.', 'schedspot' ) ) );
    }

    public function export_user_data() {
        // Data export logic
        wp_send_json_success( array( 'message' => __( 'Data export requested.', 'schedspot' ) ) );
    }

    public function delete_user_account() {
        // Account deletion logic
        wp_send_json_success( array( 'message' => __( 'Account deletion initiated.', 'schedspot' ) ) );
    }
}
