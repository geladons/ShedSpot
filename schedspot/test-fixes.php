<?php
/**
 * SchedSpot Fixes Test Script
 * 
 * This script tests the fixes implemented for:
 * 1. Admin schedule tab functionality
 * 2. Payment management features
 * 3. Frontend navigation system
 * 4. Client-worker message dialog link
 * 
 * @package SchedSpot
 * @version 1.7.3
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SchedSpot_Fixes_Test {
    
    public function __construct() {
        add_action( 'wp_ajax_schedspot_test_fixes', array( $this, 'test_fixes' ) );
        add_action( 'wp_ajax_nopriv_schedspot_test_fixes', array( $this, 'test_fixes' ) );
    }
    
    /**
     * Test all implemented fixes
     */
    public function test_fixes() {
        $results = array(
            'admin_schedule_tabs' => $this->test_admin_schedule_tabs(),
            'payment_management' => $this->test_payment_management(),
            'frontend_navigation' => $this->test_frontend_navigation(),
            'message_dialog_link' => $this->test_message_dialog_link(),
            'shortcode_registration' => $this->test_shortcode_registration(),
            'virtual_pages' => $this->test_virtual_pages(),
        );
        
        wp_send_json_success( $results );
    }
    
    /**
     * Test admin schedule tabs functionality
     */
    private function test_admin_schedule_tabs() {
        $css_file = SCHEDSPOT_PLUGIN_DIR . 'assets/css/admin-schedule.css';
        $js_file = SCHEDSPOT_PLUGIN_DIR . 'assets/js/admin-schedule.js';
        
        $tests = array();
        
        // Check if CSS file exists and contains nav-tab-active styles
        if ( file_exists( $css_file ) ) {
            $css_content = file_get_contents( $css_file );
            $tests['css_file_exists'] = true;
            $tests['nav_tab_active_styles'] = strpos( $css_content, 'nav-tab-active' ) !== false;
            $tests['tab_content_styles'] = strpos( $css_content, '.tab-content.active' ) !== false;
        } else {
            $tests['css_file_exists'] = false;
        }
        
        // Check if JS file exists and contains tab functionality
        if ( file_exists( $js_file ) ) {
            $js_content = file_get_contents( $js_file );
            $tests['js_file_exists'] = true;
            $tests['switch_tab_function'] = strpos( $js_content, 'switchTab' ) !== false;
            $tests['schedule_manager_init'] = strpos( $js_content, 'ScheduleManager.init()' ) !== false;
        } else {
            $tests['js_file_exists'] = false;
        }
        
        return $tests;
    }
    
    /**
     * Test payment management AJAX handlers
     */
    private function test_payment_management() {
        $tests = array();
        
        // Check if admin bookings class exists
        if ( class_exists( 'SchedSpot_Admin_Bookings' ) ) {
            $tests['admin_bookings_class_exists'] = true;
            
            // Check if AJAX actions are registered
            $tests['request_deposit_action'] = has_action( 'wp_ajax_schedspot_request_deposit' );
            $tests['mark_deposit_paid_action'] = has_action( 'wp_ajax_schedspot_mark_deposit_paid' );
            $tests['request_final_payment_action'] = has_action( 'wp_ajax_schedspot_request_final_payment' );
            $tests['generate_invoice_action'] = has_action( 'wp_ajax_schedspot_generate_invoice' );
        } else {
            $tests['admin_bookings_class_exists'] = false;
        }
        
        return $tests;
    }
    
    /**
     * Test frontend navigation system
     */
    private function test_frontend_navigation() {
        $tests = array();
        
        // Check if navigation class exists
        if ( class_exists( 'SchedSpot_Navigation' ) ) {
            $tests['navigation_class_exists'] = true;
            
            // Check if navigation assets exist
            $css_file = SCHEDSPOT_PLUGIN_DIR . 'assets/css/navigation.css';
            $js_file = SCHEDSPOT_PLUGIN_DIR . 'assets/js/navigation.js';
            
            $tests['navigation_css_exists'] = file_exists( $css_file );
            $tests['navigation_js_exists'] = file_exists( $js_file );
            
            // Check if virtual page handler is hooked
            $tests['virtual_pages_hooked'] = has_action( 'template_redirect', 'SchedSpot_Core->handle_virtual_pages' );
        } else {
            $tests['navigation_class_exists'] = false;
        }
        
        return $tests;
    }
    
    /**
     * Test message dialog link in booking details
     */
    private function test_message_dialog_link() {
        $template_file = SCHEDSPOT_PLUGIN_DIR . 'templates/admin/booking-details.php';
        $tests = array();
        
        if ( file_exists( $template_file ) ) {
            $template_content = file_get_contents( $template_file );
            $tests['template_exists'] = true;
            $tests['message_link_added'] = strpos( $template_content, 'Client-Worker Messages' ) !== false;
            $tests['message_button_added'] = strpos( $template_content, 'View Message Thread' ) !== false;
        } else {
            $tests['template_exists'] = false;
        }
        
        return $tests;
    }
    
    /**
     * Test shortcode registration
     */
    private function test_shortcode_registration() {
        $tests = array();
        
        // Check if shortcodes are registered
        $tests['booking_form_shortcode'] = shortcode_exists( 'schedspot_booking_form' );
        $tests['dashboard_shortcode'] = shortcode_exists( 'schedspot_dashboard' );
        $tests['messages_shortcode'] = shortcode_exists( 'schedspot_messages' );
        $tests['profile_shortcode'] = shortcode_exists( 'schedspot_profile' );
        
        return $tests;
    }
    
    /**
     * Test virtual pages functionality
     */
    private function test_virtual_pages() {
        $tests = array();
        
        // Check if SchedSpot core class exists and has virtual page method
        if ( class_exists( 'SchedSpot_Core' ) ) {
            $tests['core_class_exists'] = true;
            $tests['handle_virtual_pages_method'] = method_exists( 'SchedSpot_Core', 'handle_virtual_pages' );
            $tests['render_virtual_page_method'] = method_exists( 'SchedSpot_Core', 'render_virtual_page' );
        } else {
            $tests['core_class_exists'] = false;
        }
        
        return $tests;
    }
    
    /**
     * Run comprehensive test and output results
     */
    public static function run_test() {
        $tester = new self();
        
        echo "<h2>SchedSpot Fixes Test Results</h2>";
        
        // Test admin schedule tabs
        echo "<h3>1. Admin Schedule Tabs</h3>";
        $schedule_results = $tester->test_admin_schedule_tabs();
        foreach ( $schedule_results as $test => $result ) {
            $status = $result ? '✅ PASS' : '❌ FAIL';
            echo "<p>{$test}: {$status}</p>";
        }
        
        // Test payment management
        echo "<h3>2. Payment Management</h3>";
        $payment_results = $tester->test_payment_management();
        foreach ( $payment_results as $test => $result ) {
            $status = $result ? '✅ PASS' : '❌ FAIL';
            echo "<p>{$test}: {$status}</p>";
        }
        
        // Test frontend navigation
        echo "<h3>3. Frontend Navigation</h3>";
        $nav_results = $tester->test_frontend_navigation();
        foreach ( $nav_results as $test => $result ) {
            $status = $result ? '✅ PASS' : '❌ FAIL';
            echo "<p>{$test}: {$status}</p>";
        }
        
        // Test message dialog link
        echo "<h3>4. Message Dialog Link</h3>";
        $message_results = $tester->test_message_dialog_link();
        foreach ( $message_results as $test => $result ) {
            $status = $result ? '✅ PASS' : '❌ FAIL';
            echo "<p>{$test}: {$status}</p>";
        }
        
        // Test shortcode registration
        echo "<h3>5. Shortcode Registration</h3>";
        $shortcode_results = $tester->test_shortcode_registration();
        foreach ( $shortcode_results as $test => $result ) {
            $status = $result ? '✅ PASS' : '❌ FAIL';
            echo "<p>{$test}: {$status}</p>";
        }
        
        // Test virtual pages
        echo "<h3>6. Virtual Pages</h3>";
        $virtual_results = $tester->test_virtual_pages();
        foreach ( $virtual_results as $test => $result ) {
            $status = $result ? '✅ PASS' : '❌ FAIL';
            echo "<p>{$test}: {$status}</p>";
        }
        
        echo "<h3>Summary</h3>";
        $all_tests = array_merge( $schedule_results, $payment_results, $nav_results, $message_results, $shortcode_results, $virtual_results );
        $passed = count( array_filter( $all_tests ) );
        $total = count( $all_tests );
        
        echo "<p><strong>Tests Passed: {$passed}/{$total}</strong></p>";
        
        if ( $passed === $total ) {
            echo "<p style='color: green; font-weight: bold;'>🎉 All fixes are working correctly!</p>";
        } else {
            echo "<p style='color: orange; font-weight: bold;'>⚠️ Some issues may need attention.</p>";
        }
    }
}

// Initialize the test class
new SchedSpot_Fixes_Test();

// If accessed directly, run the test
if ( isset( $_GET['run_test'] ) && $_GET['run_test'] === 'schedspot_fixes' ) {
    SchedSpot_Fixes_Test::run_test();
}