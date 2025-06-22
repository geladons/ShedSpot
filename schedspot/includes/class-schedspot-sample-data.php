<?php
/**
 * Sample Data Class
 *
 * @package SchedSpot
 * @version 0.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Sample_Data Class.
 *
 * @class SchedSpot_Sample_Data
 * @version 0.1.0
 */
class SchedSpot_Sample_Data {

    /**
     * Install sample data.
     *
     * @since 0.1.0
     */
    public static function install() {
        self::create_sample_services();
        self::create_sample_users();
    }

    /**
     * Create sample services.
     *
     * @since 0.1.0
     */
    private static function create_sample_services() {
        global $wpdb;

        $services = array(
            array(
                'name'        => 'House Cleaning',
                'description' => 'Professional house cleaning service including all rooms, kitchen, and bathrooms.',
                'duration'    => 120,
                'price_type'  => 'hourly',
                'base_price'  => 25.00,
                'category'    => 'Cleaning',
            ),
            array(
                'name'        => 'Lawn Mowing',
                'description' => 'Complete lawn mowing and basic yard maintenance.',
                'duration'    => 60,
                'price_type'  => 'fixed',
                'base_price'  => 40.00,
                'category'    => 'Landscaping',
            ),
            array(
                'name'        => 'Handyman Services',
                'description' => 'General handyman work including minor repairs and installations.',
                'duration'    => 90,
                'price_type'  => 'hourly',
                'base_price'  => 35.00,
                'category'    => 'Maintenance',
            ),
            array(
                'name'        => 'Pet Sitting',
                'description' => 'Professional pet sitting in your home while you\'re away.',
                'duration'    => 240,
                'price_type'  => 'hourly',
                'base_price'  => 15.00,
                'category'    => 'Pet Care',
            ),
            array(
                'name'        => 'Personal Training',
                'description' => 'One-on-one personal training session at your location.',
                'duration'    => 60,
                'price_type'  => 'fixed',
                'base_price'  => 75.00,
                'category'    => 'Fitness',
            ),
        );

        foreach ( $services as $service ) {
            // Check if service already exists
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}schedspot_services WHERE name = %s",
                $service['name']
            ) );

            if ( ! $existing ) {
                $wpdb->insert(
                    $wpdb->prefix . 'schedspot_services',
                    $service,
                    array( '%s', '%s', '%d', '%s', '%f', '%s' )
                );
            }
        }
    }

    /**
     * Create sample users.
     *
     * @since 0.1.0
     */
    private static function create_sample_users() {
        // Create sample customer
        $customer_id = self::create_user_if_not_exists(
            'customer@schedspot.test',
            'schedspot_customer',
            'John Customer',
            'customer123'
        );

        // Create sample workers
        $worker1_id = self::create_user_if_not_exists(
            'worker1@schedspot.test',
            'schedspot_worker',
            'Jane Worker',
            'worker123'
        );

        $worker2_id = self::create_user_if_not_exists(
            'worker2@schedspot.test',
            'schedspot_worker',
            'Mike Handyman',
            'worker123'
        );

        // Assign services to workers
        if ( $worker1_id && $worker2_id ) {
            self::assign_services_to_workers( $worker1_id, $worker2_id );
        }
    }

    /**
     * Create user if not exists.
     *
     * @since 0.1.0
     * @param string $email User email.
     * @param string $role User role.
     * @param string $display_name Display name.
     * @param string $password Password.
     * @return int|false User ID on success, false on failure.
     */
    private static function create_user_if_not_exists( $email, $role, $display_name, $password ) {
        // Check if user already exists
        $existing_user = get_user_by( 'email', $email );
        if ( $existing_user ) {
            return $existing_user->ID;
        }

        $user_id = wp_create_user( $email, $password, $email );
        if ( is_wp_error( $user_id ) ) {
            return false;
        }

        // Update user data
        wp_update_user( array(
            'ID'           => $user_id,
            'display_name' => $display_name,
            'first_name'   => explode( ' ', $display_name )[0],
            'last_name'    => explode( ' ', $display_name )[1] ?? '',
        ) );

        // Set user role
        $user = new WP_User( $user_id );
        $user->set_role( $role );

        return $user_id;
    }

    /**
     * Assign services to workers.
     *
     * @since 0.1.0
     * @param int $worker1_id First worker ID.
     * @param int $worker2_id Second worker ID.
     */
    private static function assign_services_to_workers( $worker1_id, $worker2_id ) {
        global $wpdb;

        // Get service IDs
        $services = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}schedspot_services" );

        foreach ( $services as $service ) {
            // Assign different services to different workers
            $worker_id = null;
            $custom_price = null;

            switch ( $service->name ) {
                case 'House Cleaning':
                case 'Pet Sitting':
                    $worker_id = $worker1_id;
                    $custom_price = 28.00; // Custom rate for worker 1
                    break;
                case 'Lawn Mowing':
                case 'Handyman Services':
                case 'Personal Training':
                    $worker_id = $worker2_id;
                    $custom_price = 40.00; // Custom rate for worker 2
                    break;
            }

            if ( $worker_id ) {
                // Check if assignment already exists
                $existing = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}schedspot_worker_services 
                     WHERE worker_id = %d AND service_id = %d",
                    $worker_id,
                    $service->id
                ) );

                if ( ! $existing ) {
                    $wpdb->insert(
                        $wpdb->prefix . 'schedspot_worker_services',
                        array(
                            'worker_id'    => $worker_id,
                            'service_id'   => $service->id,
                            'custom_price' => $custom_price,
                            'is_enabled'   => 1,
                        ),
                        array( '%d', '%d', '%f', '%d' )
                    );
                }
            }
        }

        // Add sample availability for workers
        self::add_worker_availability( $worker1_id );
        self::add_worker_availability( $worker2_id );
    }

    /**
     * Add worker availability.
     *
     * @since 0.1.0
     * @param int $worker_id Worker ID.
     */
    private static function add_worker_availability( $worker_id ) {
        global $wpdb;

        // Standard business hours: Monday-Friday 9 AM to 5 PM
        $availability = array(
            array( 'day' => 1, 'start' => '09:00:00', 'end' => '17:00:00' ), // Monday
            array( 'day' => 2, 'start' => '09:00:00', 'end' => '17:00:00' ), // Tuesday
            array( 'day' => 3, 'start' => '09:00:00', 'end' => '17:00:00' ), // Wednesday
            array( 'day' => 4, 'start' => '09:00:00', 'end' => '17:00:00' ), // Thursday
            array( 'day' => 5, 'start' => '09:00:00', 'end' => '17:00:00' ), // Friday
            array( 'day' => 6, 'start' => '10:00:00', 'end' => '14:00:00' ), // Saturday (half day)
        );

        foreach ( $availability as $slot ) {
            // Check if availability already exists
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}schedspot_worker_availability 
                 WHERE worker_id = %d AND day_of_week = %d",
                $worker_id,
                $slot['day']
            ) );

            if ( ! $existing ) {
                $wpdb->insert(
                    $wpdb->prefix . 'schedspot_worker_availability',
                    array(
                        'worker_id'    => $worker_id,
                        'day_of_week'  => $slot['day'],
                        'start_time'   => $slot['start'],
                        'end_time'     => $slot['end'],
                        'is_available' => 1,
                    ),
                    array( '%d', '%d', '%s', '%s', '%d' )
                );
            }
        }
    }

    /**
     * Remove sample data.
     *
     * @since 0.1.0
     */
    public static function remove() {
        global $wpdb;

        // Remove sample users
        $sample_emails = array(
            'customer@schedspot.test',
            'worker1@schedspot.test',
            'worker2@schedspot.test',
        );

        foreach ( $sample_emails as $email ) {
            $user = get_user_by( 'email', $email );
            if ( $user ) {
                wp_delete_user( $user->ID );
            }
        }

        // Remove sample services (optional - you might want to keep these)
        $sample_services = array(
            'House Cleaning',
            'Lawn Mowing',
            'Handyman Services',
            'Pet Sitting',
            'Personal Training',
        );

        foreach ( $sample_services as $service_name ) {
            $wpdb->delete(
                $wpdb->prefix . 'schedspot_services',
                array( 'name' => $service_name ),
                array( '%s' )
            );
        }

        // Clean up related data
        $wpdb->query( "DELETE FROM {$wpdb->prefix}schedspot_worker_services WHERE worker_id NOT IN (SELECT ID FROM {$wpdb->users})" );
        $wpdb->query( "DELETE FROM {$wpdb->prefix}schedspot_worker_availability WHERE worker_id NOT IN (SELECT ID FROM {$wpdb->users})" );
    }
}
