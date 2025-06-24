<?php
/**
 * Admin Settings Template
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings_sections = $instance->get_settings_sections();
?>

<div class="wrap">
    <h1><?php _e( 'SchedSpot Settings', 'schedspot' ); ?></h1>

    <?php settings_errors( 'schedspot_settings' ); ?>

    <!-- Settings Navigation -->
    <nav class="nav-tab-wrapper">
        <?php foreach ( $settings_sections as $section_id => $section ) : ?>
            <a href="?page=schedspot-settings&tab=<?php echo esc_attr( $section_id ); ?>" 
               class="nav-tab <?php echo $current_tab === $section_id ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html( $section['title'] ); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Settings Form -->
    <form method="post" action="">
        <?php wp_nonce_field( 'schedspot_settings' ); ?>
        
        <div class="tab-content">
            <?php if ( isset( $settings_sections[ $current_tab ] ) ) : ?>
                <table class="form-table">
                    <?php foreach ( $settings_sections[ $current_tab ]['fields'] as $field_id => $field ) : ?>
                        <tr>
                            <th scope="row">
                                <label for="schedspot_<?php echo esc_attr( $field_id ); ?>">
                                    <?php echo esc_html( $field['title'] ); ?>
                                </label>
                            </th>
                            <td>
                                <?php
                                $option_name = 'schedspot_' . $field_id;
                                $value = get_option( $option_name, $field['default'] ?? '' );
                                
                                switch ( $field['type'] ) :
                                    case 'text':
                                    case 'email':
                                        ?>
                                        <input type="<?php echo esc_attr( $field['type'] ); ?>" 
                                               id="schedspot_<?php echo esc_attr( $field_id ); ?>" 
                                               name="<?php echo esc_attr( $option_name ); ?>" 
                                               value="<?php echo esc_attr( $value ); ?>" 
                                               class="regular-text">
                                        <?php
                                        break;
                                    
                                    case 'password':
                                        ?>
                                        <input type="password" 
                                               id="schedspot_<?php echo esc_attr( $field_id ); ?>" 
                                               name="<?php echo esc_attr( $option_name ); ?>" 
                                               value="<?php echo esc_attr( $value ); ?>" 
                                               class="regular-text">
                                        <?php
                                        break;
                                    
                                    case 'number':
                                        ?>
                                        <input type="number" 
                                               id="schedspot_<?php echo esc_attr( $field_id ); ?>" 
                                               name="<?php echo esc_attr( $option_name ); ?>" 
                                               value="<?php echo esc_attr( $value ); ?>" 
                                               class="small-text"
                                               <?php if ( isset( $field['min'] ) ) echo 'min="' . esc_attr( $field['min'] ) . '"'; ?>
                                               <?php if ( isset( $field['max'] ) ) echo 'max="' . esc_attr( $field['max'] ) . '"'; ?>>
                                        <?php
                                        break;
                                    
                                    case 'select':
                                        ?>
                                        <select id="schedspot_<?php echo esc_attr( $field_id ); ?>" 
                                                name="<?php echo esc_attr( $option_name ); ?>">
                                            <?php foreach ( $field['options'] as $option_value => $option_label ) : ?>
                                                <option value="<?php echo esc_attr( $option_value ); ?>" 
                                                        <?php selected( $value, $option_value ); ?>>
                                                    <?php echo esc_html( $option_label ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php
                                        break;
                                    
                                    case 'checkbox':
                                        ?>
                                        <label>
                                            <input type="checkbox" 
                                                   id="schedspot_<?php echo esc_attr( $field_id ); ?>" 
                                                   name="<?php echo esc_attr( $option_name ); ?>" 
                                                   value="1" 
                                                   <?php checked( $value, '1' ); ?>>
                                            <?php echo esc_html( $field['description'] ?? '' ); ?>
                                        </label>
                                        <?php
                                        break;
                                    
                                    case 'textarea':
                                        ?>
                                        <textarea id="schedspot_<?php echo esc_attr( $field_id ); ?>" 
                                                  name="<?php echo esc_attr( $option_name ); ?>" 
                                                  rows="4" 
                                                  class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
                                        <?php
                                        break;
                                endswitch;
                                ?>
                                
                                <?php if ( isset( $field['description'] ) && $field['type'] !== 'checkbox' ) : ?>
                                    <p class="description"><?php echo esc_html( $field['description'] ); ?></p>
                                <?php endif; ?>
                                
                                <!-- Special action buttons for certain fields -->
                                <?php if ( $field_id === 'twilio_phone_number' ) : ?>
                                    <p>
                                        <button type="button" class="button" onclick="testSMS()">
                                            <?php _e( 'Test SMS', 'schedspot' ); ?>
                                        </button>
                                        <input type="text" id="test_phone" placeholder="<?php esc_attr_e( 'Phone number to test', 'schedspot' ); ?>" class="regular-text">
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ( $field_id === 'google_maps_api_key' ) : ?>
                                    <p>
                                        <button type="button" class="button" onclick="testGeocoding()">
                                            <?php _e( 'Test Geocoding', 'schedspot' ); ?>
                                        </button>
                                        <input type="text" id="test_address" placeholder="<?php esc_attr_e( 'Address to test', 'schedspot' ); ?>" class="regular-text">
                                    </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <!-- Google Calendar Integration Section -->
        <?php if ( $current_tab === 'integrations' ) : ?>
            <div class="schedspot-gcal-integration">
                <h3><?php _e( 'Google Calendar Integration', 'schedspot' ); ?></h3>
                
                <?php $gcal_connected = get_option( 'schedspot_gcal_access_token' ); ?>
                
                <?php if ( $gcal_connected ) : ?>
                    <div class="gcal-status connected">
                        <p><strong><?php _e( 'Status:', 'schedspot' ); ?></strong> <span class="status-text"><?php _e( 'Connected', 'schedspot' ); ?></span></p>
                        <p>
                            <button type="button" class="button" onclick="syncAllBookings()">
                                <?php _e( 'Sync All Bookings', 'schedspot' ); ?>
                            </button>
                            <button type="button" class="button" onclick="disconnectGoogleCalendar()">
                                <?php _e( 'Disconnect', 'schedspot' ); ?>
                            </button>
                        </p>
                    </div>
                <?php else : ?>
                    <div class="gcal-status disconnected">
                        <p><strong><?php _e( 'Status:', 'schedspot' ); ?></strong> <span class="status-text"><?php _e( 'Not Connected', 'schedspot' ); ?></span></p>
                        <p>
                            <a href="<?php echo admin_url( 'admin.php?page=schedspot-settings&action=connect_gcal' ); ?>" class="button button-primary">
                                <?php _e( 'Connect Google Calendar', 'schedspot' ); ?>
                            </a>
                        </p>
                        <p class="description">
                            <?php _e( 'Connect your Google Calendar to automatically sync bookings and prevent double-booking.', 'schedspot' ); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <p class="submit">
            <input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'schedspot' ); ?>">
        </p>
    </form>

    <!-- System Information -->
    <?php if ( $current_tab === 'general' ) : ?>
        <div class="schedspot-system-info">
            <h3><?php _e( 'System Information', 'schedspot' ); ?></h3>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><strong><?php _e( 'Plugin Version', 'schedspot' ); ?></strong></td>
                        <td><?php echo esc_html( SCHEDSPOT_VERSION ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e( 'WordPress Version', 'schedspot' ); ?></strong></td>
                        <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e( 'PHP Version', 'schedspot' ); ?></strong></td>
                        <td><?php echo esc_html( PHP_VERSION ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e( 'Database Version', 'schedspot' ); ?></strong></td>
                        <td><?php echo esc_html( get_option( 'schedspot_db_version', 'Unknown' ) ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e( 'Total Bookings', 'schedspot' ); ?></strong></td>
                        <td>
                            <?php
                            global $wpdb;
                            $total_bookings = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings" );
                            echo esc_html( $total_bookings ?: '0' );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php _e( 'Active Workers', 'schedspot' ); ?></strong></td>
                        <td>
                            <?php
                            $active_workers = count( get_users( array(
                                'role' => 'schedspot_worker',
                                'meta_query' => array(
                                    array(
                                        'key' => 'schedspot_is_available',
                                        'value' => '1',
                                        'compare' => '='
                                    )
                                )
                            ) ) );
                            echo esc_html( $active_workers );
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
// Test SMS functionality
function testSMS() {
    const phone = document.getElementById('test_phone').value;
    if (!phone) {
        alert('<?php esc_js( _e( 'Please enter a phone number to test.', 'schedspot' ) ); ?>');
        return;
    }
    
    const data = new FormData();
    data.append('action', 'schedspot_test_sms');
    data.append('phone', phone);
    data.append('nonce', '<?php echo wp_create_nonce( 'schedspot_test_sms' ); ?>');
    
    fetch(ajaxurl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.data.message);
        } else {
            alert(result.data.message || '<?php esc_js( _e( 'Test failed.', 'schedspot' ) ); ?>');
        }
    })
    .catch(error => {
        alert('<?php esc_js( _e( 'Error testing SMS.', 'schedspot' ) ); ?>');
    });
}

// Test geocoding functionality
function testGeocoding() {
    const address = document.getElementById('test_address').value;
    if (!address) {
        alert('<?php esc_js( _e( 'Please enter an address to test.', 'schedspot' ) ); ?>');
        return;
    }
    
    const data = new FormData();
    data.append('action', 'schedspot_geocode_address');
    data.append('address', address);
    data.append('nonce', '<?php echo wp_create_nonce( 'schedspot_geolocation_nonce' ); ?>');
    
    fetch(ajaxurl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('<?php esc_js( _e( 'Geocoding successful!', 'schedspot' ) ); ?>\n' + 
                  '<?php esc_js( _e( 'Latitude:', 'schedspot' ) ); ?> ' + result.data.lat + '\n' +
                  '<?php esc_js( _e( 'Longitude:', 'schedspot' ) ); ?> ' + result.data.lng);
        } else {
            alert(result.data.message || '<?php esc_js( _e( 'Geocoding failed.', 'schedspot' ) ); ?>');
        }
    })
    .catch(error => {
        alert('<?php esc_js( _e( 'Error testing geocoding.', 'schedspot' ) ); ?>');
    });
}

// Disconnect Google Calendar
function disconnectGoogleCalendar() {
    if (!confirm('<?php esc_js( _e( 'Are you sure you want to disconnect Google Calendar?', 'schedspot' ) ); ?>')) {
        return;
    }
    
    const data = new FormData();
    data.append('action', 'schedspot_gcal_disconnect');
    data.append('nonce', '<?php echo wp_create_nonce( 'schedspot_gcal_disconnect' ); ?>');
    
    fetch(ajaxurl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            alert(result.data.message || '<?php esc_js( _e( 'Failed to disconnect.', 'schedspot' ) ); ?>');
        }
    });
}

// Sync all bookings to Google Calendar
function syncAllBookings() {
    const data = new FormData();
    data.append('action', 'schedspot_gcal_sync_all');
    data.append('nonce', '<?php echo wp_create_nonce( 'schedspot_gcal_sync_all' ); ?>');
    
    fetch(ajaxurl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(result.data.message);
        } else {
            alert(result.data.message || '<?php esc_js( _e( 'Sync failed.', 'schedspot' ) ); ?>');
        }
    });
}
</script>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.schedspot-gcal-integration {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.gcal-status.connected .status-text {
    color: #155724;
    font-weight: 600;
}

.gcal-status.disconnected .status-text {
    color: #721c24;
    font-weight: 600;
}

.schedspot-system-info {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.schedspot-system-info table {
    margin-top: 15px;
}

.schedspot-system-info td {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
}
</style>
