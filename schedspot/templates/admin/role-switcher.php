<?php
/**
 * Role Switcher Template - Enhanced Design
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
$current_role_mode = get_user_meta( $current_user->ID, 'schedspot_admin_role_mode', true );
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'Role Switcher', 'schedspot' ); ?></h1>
    <hr class="wp-header-end">
    
    <div class="schedspot-role-switcher-container">
        <div class="current-role-display">
            <h2><?php _e( 'Current Role Mode', 'schedspot' ); ?></h2>
            <div class="current-role-badge">
                <span class="role-icon">👤</span>
                <span class="role-name">
                    <?php
                    switch ( $current_role_mode ) {
                        case 'schedspot_worker':
                            _e( 'Worker', 'schedspot' );
                            break;
                        case 'schedspot_customer':
                            _e( 'Customer', 'schedspot' );
                            break;
                        default:
                            _e( 'Administrator', 'schedspot' );
                            break;
                    }
                    ?>
                </span>
            </div>
            <p class="role-description">
                <?php _e( 'You are currently viewing the plugin as this role. Switch roles to test different user experiences.', 'schedspot' ); ?>
            </p>
        </div>

        <div class="role-switcher-grid">
            <div class="role-option <?php echo ( ! $current_role_mode || $current_role_mode === 'administrator' ) ? 'active' : ''; ?>" data-role="administrator">
                <div class="role-header">
                    <div class="role-icon-large">🔧</div>
                    <h3><?php _e( 'Administrator', 'schedspot' ); ?></h3>
                </div>
                <div class="role-features">
                    <ul>
                        <li><?php _e( 'Full plugin management', 'schedspot' ); ?></li>
                        <li><?php _e( 'Settings & configuration', 'schedspot' ); ?></li>
                        <li><?php _e( 'User & role management', 'schedspot' ); ?></li>
                        <li><?php _e( 'Analytics & reporting', 'schedspot' ); ?></li>
                        <li><?php _e( 'System administration', 'schedspot' ); ?></li>
                    </ul>
                </div>
                <div class="role-actions">
                    <button class="role-switch-btn <?php echo ( ! $current_role_mode || $current_role_mode === 'administrator' ) ? 'current' : 'switch'; ?>" 
                            onclick="switchRole('administrator')" 
                            <?php disabled( ! $current_role_mode || $current_role_mode === 'administrator' ); ?>>
                        <?php echo ( ! $current_role_mode || $current_role_mode === 'administrator' ) ? __( 'Current Role', 'schedspot' ) : __( 'Switch to Admin', 'schedspot' ); ?>
                    </button>
                </div>
            </div>

            <div class="role-option <?php echo ( $current_role_mode === 'schedspot_worker' ) ? 'active' : ''; ?>" data-role="schedspot_worker">
                <div class="role-header">
                    <div class="role-icon-large">👷</div>
                    <h3><?php _e( 'Worker', 'schedspot' ); ?></h3>
                </div>
                <div class="role-features">
                    <ul>
                        <li><?php _e( 'Worker dashboard', 'schedspot' ); ?></li>
                        <li><?php _e( 'Booking management', 'schedspot' ); ?></li>
                        <li><?php _e( 'Availability scheduling', 'schedspot' ); ?></li>
                        <li><?php _e( 'Earnings tracking', 'schedspot' ); ?></li>
                        <li><?php _e( 'Client messaging', 'schedspot' ); ?></li>
                    </ul>
                </div>
                <div class="role-actions">
                    <button class="role-switch-btn <?php echo ( $current_role_mode === 'schedspot_worker' ) ? 'current' : 'switch'; ?>" 
                            onclick="switchRole('schedspot_worker')" 
                            <?php disabled( $current_role_mode === 'schedspot_worker' ); ?>>
                        <?php echo ( $current_role_mode === 'schedspot_worker' ) ? __( 'Current Role', 'schedspot' ) : __( 'Switch to Worker', 'schedspot' ); ?>
                    </button>
                </div>
            </div>

            <div class="role-option <?php echo ( $current_role_mode === 'schedspot_customer' ) ? 'active' : ''; ?>" data-role="schedspot_customer">
                <div class="role-header">
                    <div class="role-icon-large">👤</div>
                    <h3><?php _e( 'Customer', 'schedspot' ); ?></h3>
                </div>
                <div class="role-features">
                    <ul>
                        <li><?php _e( 'Service booking', 'schedspot' ); ?></li>
                        <li><?php _e( 'Booking history', 'schedspot' ); ?></li>
                        <li><?php _e( 'Worker communication', 'schedspot' ); ?></li>
                        <li><?php _e( 'Profile management', 'schedspot' ); ?></li>
                        <li><?php _e( 'Payment tracking', 'schedspot' ); ?></li>
                    </ul>
                </div>
                <div class="role-actions">
                    <button class="role-switch-btn <?php echo ( $current_role_mode === 'schedspot_customer' ) ? 'current' : 'switch'; ?>" 
                            onclick="switchRole('schedspot_customer')" 
                            <?php disabled( $current_role_mode === 'schedspot_customer' ); ?>>
                        <?php echo ( $current_role_mode === 'schedspot_customer' ) ? __( 'Current Role', 'schedspot' ) : __( 'Switch to Customer', 'schedspot' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="role-switcher-info">
            <div class="info-box">
                <h3><?php _e( 'How Role Switching Works', 'schedspot' ); ?></h3>
                <p><?php _e( 'Role switching allows administrators to experience the plugin from different user perspectives without changing their actual WordPress user role. This is perfect for testing functionality and understanding the user experience.', 'schedspot' ); ?></p>
                
                <div class="info-features">
                    <div class="feature-item">
                        <span class="feature-icon">🔒</span>
                        <div class="feature-text">
                            <strong><?php _e( 'Safe Testing', 'schedspot' ); ?></strong>
                            <p><?php _e( 'Your actual WordPress permissions remain unchanged.', 'schedspot' ); ?></p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">🔄</span>
                        <div class="feature-text">
                            <strong><?php _e( 'Easy Switching', 'schedspot' ); ?></strong>
                            <p><?php _e( 'Switch between roles instantly with one click.', 'schedspot' ); ?></p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">👥</span>
                        <div class="feature-text">
                            <strong><?php _e( 'User Experience', 'schedspot' ); ?></strong>
                            <p><?php _e( 'See exactly what your users see and experience.', 'schedspot' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchRole(role) {
    // Show loading state
    const button = event.target;
    const originalText = button.textContent;
    button.textContent = '<?php esc_js( _e( 'Switching...', 'schedspot' ) ); ?>';
    button.disabled = true;
    
    const data = new FormData();
    data.append('action', 'schedspot_switch_role');
    data.append('role', role);
    data.append('nonce', '<?php echo wp_create_nonce( 'schedspot_switch_role' ); ?>');
    
    fetch(ajaxurl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Show success message
            button.textContent = '<?php esc_js( _e( 'Success! Redirecting...', 'schedspot' ) ); ?>';
            
            // Redirect after a short delay
            setTimeout(() => {
                window.location.href = '<?php echo admin_url( 'admin.php?page=schedspot' ); ?>';
            }, 1000);
        } else {
            // Reset button and show error
            button.textContent = originalText;
            button.disabled = false;
            alert(result.data.message || '<?php esc_js( _e( 'Failed to switch role. Please try again.', 'schedspot' ) ); ?>');
        }
    })
    .catch(error => {
        // Reset button and show error
        button.textContent = originalText;
        button.disabled = false;
        alert('<?php esc_js( _e( 'Error switching role. Please check your connection and try again.', 'schedspot' ) ); ?>');
    });
}
</script>
