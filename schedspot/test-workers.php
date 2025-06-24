<?php
/**
 * Test script to check workers and install sample data if needed
 */

// WordPress environment
require_once '../../../wp-config.php';

// Check if workers exist
$workers = get_users(array('role' => 'schedspot_worker'));
echo "Found " . count($workers) . " workers with schedspot_worker role:\n";

foreach ($workers as $worker) {
    echo "- ID: {$worker->ID}, Email: {$worker->user_email}, Name: {$worker->display_name}\n";
}

// Check if services exist
global $wpdb;
$services = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}schedspot_services");
echo "\nFound " . count($services) . " services:\n";

foreach ($services as $service) {
    echo "- ID: {$service->id}, Name: {$service->name}\n";
}

// Check worker-service assignments
$assignments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}schedspot_worker_services");
echo "\nFound " . count($assignments) . " worker-service assignments:\n";

foreach ($assignments as $assignment) {
    echo "- Worker ID: {$assignment->worker_id}, Service ID: {$assignment->service_id}, Enabled: {$assignment->is_enabled}\n";
}

// If no workers found, install sample data
if (count($workers) === 0) {
    echo "\nNo workers found. Installing sample data...\n";
    
    // Include sample data class
    include_once 'includes/class-schedspot-sample-data.php';
    
    if (class_exists('SchedSpot_Sample_Data')) {
        SchedSpot_Sample_Data::install();
        echo "Sample data installation completed.\n";
        
        // Check again
        $workers = get_users(array('role' => 'schedspot_worker'));
        echo "After installation: Found " . count($workers) . " workers\n";
    } else {
        echo "SchedSpot_Sample_Data class not found.\n";
    }
}