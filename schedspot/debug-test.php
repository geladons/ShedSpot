<?php
/**
 * SchedSpot Debug Test Page
 * 
 * This file helps debug common issues with the SchedSpot plugin.
 * Access via: /wp-content/plugins/schedspot/debug-test.php
 * 
 * @package SchedSpot
 * @version 1.0.0
 */

// Load WordPress
require_once('../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Administrator privileges required.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>SchedSpot Debug Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        .info { background: #d1ecf1; color: #0c5460; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>SchedSpot Debug Test</h1>
    
    <?php
    // Test 1: Plugin Activation
    echo '<div class="test-section">';
    echo '<h2>1. Plugin Activation Test</h2>';
    if (class_exists('SchedSpot_Core')) {
        echo '<div class="success">✓ SchedSpot plugin is loaded</div>';
    } else {
        echo '<div class="error">✗ SchedSpot plugin is not loaded</div>';
    }
    echo '</div>';

    // Test 2: Database Tables
    echo '<div class="test-section">';
    echo '<h2>2. Database Tables Test</h2>';
    global $wpdb;
    
    $tables = array(
        'schedspot_bookings',
        'schedspot_services', 
        'schedspot_worker_services',
        'schedspot_worker_availability'
    );
    
    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . $table;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            echo "<div class='success'>✓ Table $table exists ($count records)</div>";
        } else {
            echo "<div class='error'>✗ Table $table does not exist</div>";
        }
    }
    echo '</div>';

    // Test 3: User Roles
    echo '<div class="test-section">';
    echo '<h2>3. User Roles Test</h2>';
    $roles = array('schedspot_worker', 'schedspot_customer');
    foreach ($roles as $role) {
        if (get_role($role)) {
            $users = get_users(array('role' => $role));
            echo "<div class='success'>✓ Role $role exists (" . count($users) . " users)</div>";
        } else {
            echo "<div class='error'>✗ Role $role does not exist</div>";
        }
    }
    echo '</div>';

    // Test 4: REST API Endpoints
    echo '<div class="test-section">';
    echo '<h2>4. REST API Test</h2>';
    $rest_url = rest_url('schedspot/v1/');
    echo "<div class='info'>REST URL: $rest_url</div>";
    
    // Test workers endpoint
    $workers_url = rest_url('schedspot/v1/workers');
    $response = wp_remote_get($workers_url);
    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (is_array($data)) {
            echo "<div class='success'>✓ Workers API endpoint working (" . count($data) . " workers)</div>";
        } else {
            echo "<div class='warning'>⚠ Workers API endpoint returns: " . substr($body, 0, 100) . "...</div>";
        }
    } else {
        echo "<div class='error'>✗ Workers API endpoint error: " . $response->get_error_message() . "</div>";
    }
    echo '</div>';

    // Test 5: Services
    echo '<div class="test-section">';
    echo '<h2>5. Services Test</h2>';
    $services = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}schedspot_services LIMIT 5");
    if ($services) {
        echo "<div class='success'>✓ Services found (" . count($services) . ")</div>";
        echo "<pre>" . print_r($services, true) . "</pre>";
    } else {
        echo "<div class='warning'>⚠ No services found</div>";
    }
    echo '</div>';

    // Test 6: Workers
    echo '<div class="test-section">';
    echo '<h2>6. Workers Test</h2>';

    // Check if worker role exists
    $worker_role = get_role('schedspot_worker');
    if ($worker_role) {
        echo "<div class='success'>✓ Worker role exists</div>";
    } else {
        echo "<div class='error'>✗ Worker role does not exist</div>";
    }

    // Get all users with worker role
    $workers = get_users(array('role' => 'schedspot_worker', 'number' => 10));
    if ($workers) {
        echo "<div class='success'>✓ Workers found (" . count($workers) . ")</div>";
        foreach ($workers as $worker) {
            $profile = get_user_meta($worker->ID, 'schedspot_worker_profile', true);
            $available = get_user_meta($worker->ID, 'schedspot_is_available', true);
            echo "<div class='info'>Worker: {$worker->display_name} (ID: {$worker->ID}) - Available: " . ($available ? 'Yes' : 'No') . "</div>";
            if ($profile) {
                echo "<div class='info'>  Profile: " . (is_array($profile) ? 'Array with ' . count($profile) . ' fields' : 'String') . "</div>";
            }
        }
    } else {
        echo "<div class='warning'>⚠ No workers found</div>";

        // Check if there are any users at all
        $all_users = get_users(array('number' => 5));
        echo "<div class='info'>Total users in system: " . count($all_users) . "</div>";

        // Show available roles
        $roles = wp_roles()->get_names();
        echo "<div class='info'>Available roles: " . implode(', ', array_keys($roles)) . "</div>";
    }
    echo '</div>';

    // Test 7: Shortcodes
    echo '<div class="test-section">';
    echo '<h2>7. Shortcodes Test</h2>';
    $shortcodes = array('schedspot_booking_form', 'schedspot_dashboard', 'schedspot_services');
    foreach ($shortcodes as $shortcode) {
        if (shortcode_exists($shortcode)) {
            echo "<div class='success'>✓ Shortcode [$shortcode] is registered</div>";
        } else {
            echo "<div class='error'>✗ Shortcode [$shortcode] is not registered</div>";
        }
    }
    echo '</div>';

    // Test 8: Assets
    echo '<div class="test-section">';
    echo '<h2>8. Assets Test</h2>';
    $assets = array(
        'CSS' => array(
            'assets/css/frontend-enhanced.css',
            'assets/css/booking-form.css',
            'assets/css/dashboard.css'
        ),
        'JS' => array(
            'assets/js/frontend.js',
            'assets/js/booking-form.js',
            'assets/js/booking-wizard.js'
        )
    );
    
    foreach ($assets as $type => $files) {
        echo "<h3>$type Files:</h3>";
        foreach ($files as $file) {
            $path = SCHEDSPOT_PLUGIN_DIR . $file;
            if (file_exists($path)) {
                $size = filesize($path);
                echo "<div class='success'>✓ $file exists (" . number_format($size) . " bytes)</div>";
            } else {
                echo "<div class='error'>✗ $file does not exist</div>";
            }
        }
    }
    echo '</div>';

    // Test 9: WordPress Integration
    echo '<div class="test-section">';
    echo '<h2>9. WordPress Integration Test</h2>';
    
    // Check if hooks are registered
    $hooks = array(
        'init' => 'schedspot_init',
        'wp_enqueue_scripts' => 'schedspot_enqueue_scripts',
        'rest_api_init' => 'schedspot_rest_api_init'
    );
    
    foreach ($hooks as $hook => $action) {
        if (has_action($hook)) {
            echo "<div class='success'>✓ Hook $hook is registered</div>";
        } else {
            echo "<div class='warning'>⚠ Hook $hook may not be registered</div>";
        }
    }
    echo '</div>';

    // Test 10: Quick Fix Suggestions
    echo '<div class="test-section">';
    echo '<h2>10. Quick Fix Suggestions</h2>';
    
    // Check for common issues
    $suggestions = array();
    
    if (!get_users(array('role' => 'schedspot_worker'))) {
        $suggestions[] = "Create test workers: Go to Users > Add New, set role to 'SchedSpot Worker' OR go to SchedSpot > Workers and convert existing users";
    }
    
    if (!$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_services")) {
        $suggestions[] = "Create test services: Go to SchedSpot > Services > Add New";
    }
    
    if ($suggestions) {
        foreach ($suggestions as $suggestion) {
            echo "<div class='info'>💡 $suggestion</div>";
        }
    } else {
        echo "<div class='success'>✓ No obvious issues detected</div>";
    }
    echo '</div>';
    ?>
    
    <div class="test-section">
        <h2>Debug Complete</h2>
        <p>If you're still experiencing issues, check the WordPress debug log and browser console for additional error messages.</p>
        <p><strong>Next Steps:</strong></p>
        <ul>
            <li>Create test workers and services if they don't exist</li>
            <li>Test the booking form on a page with [schedspot_booking_form] shortcode</li>
            <li>Check browser console for JavaScript errors</li>
            <li>Verify REST API endpoints are accessible</li>
        </ul>
    </div>
</body>
</html>
