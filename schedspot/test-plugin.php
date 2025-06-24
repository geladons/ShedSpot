<?php
/**
 * SchedSpot Plugin Test
 * Simple test to check if the plugin loads without errors
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Test plugin loading
echo "<h1>SchedSpot Plugin Test</h1>";

// Check if main class exists
if ( class_exists( 'SchedSpot_Core' ) ) {
    echo "<p style='color: green;'>✅ SchedSpot_Core class loaded successfully</p>";
} else {
    echo "<p style='color: red;'>❌ SchedSpot_Core class not found</p>";
}

// Check if main function exists
if ( function_exists( 'SchedSpot' ) ) {
    echo "<p style='color: green;'>✅ SchedSpot() function available</p>";
    
    // Try to get instance
    try {
        $instance = SchedSpot();
        if ( $instance ) {
            echo "<p style='color: green;'>✅ SchedSpot instance created successfully</p>";
        }
    } catch ( Exception $e ) {
        echo "<p style='color: red;'>❌ Error creating SchedSpot instance: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ SchedSpot() function not found</p>";
}

// Check if constants are defined
$constants = array(
    'SCHEDSPOT_VERSION',
    'SCHEDSPOT_PLUGIN_FILE',
    'SCHEDSPOT_PLUGIN_URL',
    'SCHEDSPOT_PLUGIN_DIR',
    'SCHEDSPOT_ABSPATH',
    'SCHEDSPOT_INCLUDES_DIR',
    'SCHEDSPOT_ADMIN_DIR',
    'SCHEDSPOT_PUBLIC_DIR',
);

echo "<h2>Constants Check</h2>";
foreach ( $constants as $constant ) {
    if ( defined( $constant ) ) {
        echo "<p style='color: green;'>✅ {$constant}: " . constant( $constant ) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ {$constant} not defined</p>";
    }
}

// Check if key classes exist
$classes = array(
    'SchedSpot_API',
    'SchedSpot_Shortcodes_Core',
    'SchedSpot_Navigation',
    'SchedSpot_Admin_Core',
    'SchedSpot_Public',
    'SchedSpot_Booking',
    'SchedSpot_Install',
);

echo "<h2>Classes Check</h2>";
foreach ( $classes as $class ) {
    if ( class_exists( $class ) ) {
        echo "<p style='color: green;'>✅ {$class} class loaded</p>";
    } else {
        echo "<p style='color: red;'>❌ {$class} class not found</p>";
    }
}

// Check if shortcodes are registered
$shortcodes = array(
    'schedspot_booking_form',
    'schedspot_dashboard',
    'schedspot_messages',
    'schedspot_profile',
    'schedspot_navigation',
);

echo "<h2>Shortcodes Check</h2>";
foreach ( $shortcodes as $shortcode ) {
    if ( shortcode_exists( $shortcode ) ) {
        echo "<p style='color: green;'>✅ [{$shortcode}] shortcode registered</p>";
    } else {
        echo "<p style='color: red;'>❌ [{$shortcode}] shortcode not found</p>";
    }
}

// Check database tables
global $wpdb;
$tables = array(
    $wpdb->prefix . 'schedspot_bookings',
    $wpdb->prefix . 'schedspot_services',
    $wpdb->prefix . 'schedspot_messages',
);

echo "<h2>Database Tables Check</h2>";
foreach ( $tables as $table ) {
    $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table;
    if ( $table_exists ) {
        echo "<p style='color: green;'>✅ {$table} table exists</p>";
    } else {
        echo "<p style='color: red;'>❌ {$table} table not found</p>";
    }
}

echo "<h2>Test Complete</h2>";
echo "<p>Check the debug log for any errors during plugin loading.</p>";
?>
