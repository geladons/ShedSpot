<?php
/**
 * Geolocation and Geofencing Integration Class
 *
 * @package SchedSpot
 * @version 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Geolocation Class.
 *
 * @class SchedSpot_Geolocation
 * @version 2.0.0
 */
class SchedSpot_Geolocation {

    /**
     * Google Maps API key.
     *
     * @var string
     */
    private $google_api_key = '';

    /**
     * Default service radius in kilometers.
     *
     * @var float
     */
    private $default_radius = 25.0;

    /**
     * Constructor.
     *
     * @since 2.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize geolocation functionality.
     *
     * @since 2.0.0
     */
    public function init() {
        // Check if geofencing is enabled
        if ( ! $this->is_enabled() ) {
            return;
        }

        // Load configuration
        $this->load_config();

        // Initialize hooks
        add_action( 'schedspot_booking_validation', array( $this, 'validate_booking_location' ), 10, 2 );
        add_filter( 'schedspot_available_workers', array( $this, 'filter_workers_by_location' ), 10, 3 );
        add_action( 'wp_ajax_schedspot_geocode_address', array( $this, 'ajax_geocode_address' ) );
        add_action( 'wp_ajax_nopriv_schedspot_geocode_address', array( $this, 'ajax_geocode_address' ) );
        add_action( 'wp_ajax_schedspot_save_service_area', array( $this, 'ajax_save_service_area' ) );
        add_action( 'wp_ajax_schedspot_get_nearby_workers', array( $this, 'ajax_get_nearby_workers' ) );
        add_action( 'wp_ajax_nopriv_schedspot_get_nearby_workers', array( $this, 'ajax_get_nearby_workers' ) );

        // Enqueue scripts for frontend
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    /**
     * Check if geofencing is enabled.
     *
     * @since 2.0.0
     * @return bool True if enabled, false otherwise.
     */
    public function is_enabled() {
        return get_option( 'schedspot_enable_geofencing', false );
    }

    /**
     * Load geolocation configuration.
     *
     * @since 2.0.0
     */
    private function load_config() {
        $this->google_api_key = get_option( 'schedspot_google_maps_api_key', '' );
        $this->default_radius = floatval( get_option( 'schedspot_default_service_radius', 25.0 ) );
    }

    /**
     * Calculate distance between two points using Haversine formula.
     *
     * @since 2.0.0
     * @param float $lat1 Latitude of first point.
     * @param float $lng1 Longitude of first point.
     * @param float $lat2 Latitude of second point.
     * @param float $lng2 Longitude of second point.
     * @param string $unit Unit of measurement (km or miles).
     * @return float Distance between points.
     */
    public function calculate_distance( $lat1, $lng1, $lat2, $lng2, $unit = 'km' ) {
        if ( ( $lat1 == $lat2 ) && ( $lng1 == $lng2 ) ) {
            return 0;
        }

        $theta = $lng1 - $lng2;
        $dist = sin( deg2rad( $lat1 ) ) * sin( deg2rad( $lat2 ) ) + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * cos( deg2rad( $theta ) );
        $dist = acos( $dist );
        $dist = rad2deg( $dist );
        $miles = $dist * 60 * 1.1515;

        if ( $unit === 'km' ) {
            return $miles * 1.609344;
        } else {
            return $miles;
        }
    }

    /**
     * Check if a point is within a circular service area.
     *
     * @since 2.0.0
     * @param float $client_lat Client latitude.
     * @param float $client_lng Client longitude.
     * @param float $center_lat Service area center latitude.
     * @param float $center_lng Service area center longitude.
     * @param float $radius Service area radius in kilometers.
     * @return bool True if within area, false otherwise.
     */
    public function is_within_radius( $client_lat, $client_lng, $center_lat, $center_lng, $radius ) {
        $distance = $this->calculate_distance( $client_lat, $client_lng, $center_lat, $center_lng );
        return $distance <= $radius;
    }

    /**
     * Check if a point is within a polygon service area.
     *
     * @since 2.0.0
     * @param float $client_lat Client latitude.
     * @param float $client_lng Client longitude.
     * @param array $polygon Array of lat/lng points defining the polygon.
     * @return bool True if within polygon, false otherwise.
     */
    public function is_within_polygon( $client_lat, $client_lng, $polygon ) {
        if ( empty( $polygon ) || count( $polygon ) < 3 ) {
            return false;
        }

        $vertices_count = count( $polygon );
        $intersections = 0;

        for ( $i = 0; $i < $vertices_count; $i++ ) {
            $j = ( $i + 1 ) % $vertices_count;

            $vertex_i = $polygon[ $i ];
            $vertex_j = $polygon[ $j ];

            if ( ( $vertex_i['lat'] > $client_lat ) !== ( $vertex_j['lat'] > $client_lat ) &&
                 $client_lng < ( $vertex_j['lng'] - $vertex_i['lng'] ) * ( $client_lat - $vertex_i['lat'] ) / ( $vertex_j['lat'] - $vertex_i['lat'] ) + $vertex_i['lng'] ) {
                $intersections++;
            }
        }

        return ( $intersections % 2 ) === 1;
    }

    /**
     * Check if a worker serves a specific location.
     *
     * @since 2.0.0
     * @param int   $worker_id Worker ID.
     * @param float $client_lat Client latitude.
     * @param float $client_lng Client longitude.
     * @return bool True if worker serves the location, false otherwise.
     */
    public function worker_serves_location( $worker_id, $client_lat, $client_lng ) {
        if ( ! $this->is_enabled() ) {
            return true; // If geofencing is disabled, all workers serve all locations
        }

        $worker = new SchedSpot_Worker( $worker_id );
        $service_areas = $worker->profile['service_areas'];

        if ( empty( $service_areas ) ) {
            // If no service area is defined, use default radius from worker's address
            $worker_address = $worker->profile['address'];
            if ( empty( $worker_address ) ) {
                return true; // No restrictions if no address
            }

            $worker_coords = $this->geocode_address( $worker_address );
            if ( ! $worker_coords ) {
                return true; // Can't verify, allow booking
            }

            return $this->is_within_radius(
                $client_lat,
                $client_lng,
                $worker_coords['lat'],
                $worker_coords['lng'],
                $this->default_radius
            );
        }

        // Check each defined service area
        foreach ( $service_areas as $area ) {
            if ( $area['type'] === 'radius' ) {
                if ( $this->is_within_radius(
                    $client_lat,
                    $client_lng,
                    $area['center']['lat'],
                    $area['center']['lng'],
                    $area['radius']
                ) ) {
                    return true;
                }
            } elseif ( $area['type'] === 'polygon' ) {
                if ( $this->is_within_polygon( $client_lat, $client_lng, $area['coordinates'] ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Geocode an address to coordinates.
     *
     * @since 2.0.0
     * @param string $address Address to geocode.
     * @return array|false Array with lat/lng or false on failure.
     */
    public function geocode_address( $address ) {
        if ( empty( $address ) || empty( $this->google_api_key ) ) {
            return false;
        }

        // Check cache first
        $cache_key = 'schedspot_geocode_' . md5( $address );
        $cached_result = get_transient( $cache_key );
        
        if ( $cached_result !== false ) {
            return $cached_result;
        }

        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query( array(
            'address' => $address,
            'key'     => $this->google_api_key,
        ) );

        $response = wp_remote_get( $url, array( 'timeout' => 30 ) );

        if ( is_wp_error( $response ) ) {
            error_log( 'SchedSpot Geolocation: Geocoding request failed - ' . $response->get_error_message() );
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $data['status'] !== 'OK' || empty( $data['results'] ) ) {
            error_log( 'SchedSpot Geolocation: Geocoding failed - ' . $data['status'] );
            return false;
        }

        $location = $data['results'][0]['geometry']['location'];
        $result = array(
            'lat' => $location['lat'],
            'lng' => $location['lng'],
            'formatted_address' => $data['results'][0]['formatted_address'],
        );

        // Cache for 24 hours
        set_transient( $cache_key, $result, DAY_IN_SECONDS );

        return $result;
    }

    /**
     * Reverse geocode coordinates to address.
     *
     * @since 2.0.0
     * @param float $lat Latitude.
     * @param float $lng Longitude.
     * @return string|false Address or false on failure.
     */
    public function reverse_geocode( $lat, $lng ) {
        if ( empty( $this->google_api_key ) ) {
            return false;
        }

        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query( array(
            'latlng' => $lat . ',' . $lng,
            'key'    => $this->google_api_key,
        ) );

        $response = wp_remote_get( $url, array( 'timeout' => 30 ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $data['status'] !== 'OK' || empty( $data['results'] ) ) {
            return false;
        }

        return $data['results'][0]['formatted_address'];
    }

    /**
     * Validate booking location against worker service areas.
     *
     * @since 2.0.0
     * @param array $booking_data Booking data.
     * @param array $errors Current validation errors.
     * @return array Updated errors array.
     */
    public function validate_booking_location( $booking_data, $errors ) {
        if ( ! $this->is_enabled() ) {
            return $errors;
        }

        $client_lat = isset( $booking_data['client_lat'] ) ? floatval( $booking_data['client_lat'] ) : null;
        $client_lng = isset( $booking_data['client_lng'] ) ? floatval( $booking_data['client_lng'] ) : null;
        $worker_id = isset( $booking_data['worker_id'] ) ? absint( $booking_data['worker_id'] ) : 0;

        // If no coordinates provided, try to geocode the address
        if ( ( ! $client_lat || ! $client_lng ) && ! empty( $booking_data['client_address'] ) ) {
            $coords = $this->geocode_address( $booking_data['client_address'] );
            if ( $coords ) {
                $client_lat = $coords['lat'];
                $client_lng = $coords['lng'];
            }
        }

        if ( ! $client_lat || ! $client_lng ) {
            $errors[] = __( 'Unable to determine service location. Please provide a valid address.', 'schedspot' );
            return $errors;
        }

        if ( $worker_id && ! $this->worker_serves_location( $worker_id, $client_lat, $client_lng ) ) {
            $errors[] = __( 'The selected service provider does not serve your location.', 'schedspot' );
        }

        return $errors;
    }

    /**
     * Filter available workers by location.
     *
     * @since 2.0.0
     * @param array $workers Available workers.
     * @param float $client_lat Client latitude.
     * @param float $client_lng Client longitude.
     * @return array Filtered workers.
     */
    public function filter_workers_by_location( $workers, $client_lat, $client_lng ) {
        if ( ! $this->is_enabled() || ! $client_lat || ! $client_lng ) {
            return $workers;
        }

        $filtered_workers = array();

        foreach ( $workers as $worker ) {
            if ( $this->worker_serves_location( $worker->ID, $client_lat, $client_lng ) ) {
                // Add distance information
                $worker_coords = $this->get_worker_coordinates( $worker->ID );
                if ( $worker_coords ) {
                    $worker->distance = $this->calculate_distance(
                        $client_lat,
                        $client_lng,
                        $worker_coords['lat'],
                        $worker_coords['lng']
                    );
                }
                $filtered_workers[] = $worker;
            }
        }

        // Sort by distance
        usort( $filtered_workers, function( $a, $b ) {
            $distance_a = isset( $a->distance ) ? $a->distance : 999999;
            $distance_b = isset( $b->distance ) ? $b->distance : 999999;
            return $distance_a <=> $distance_b;
        } );

        return $filtered_workers;
    }

    /**
     * Get worker coordinates from their address or service area center.
     *
     * @since 2.0.0
     * @param int $worker_id Worker ID.
     * @return array|false Coordinates array or false.
     */
    public function get_worker_coordinates( $worker_id ) {
        $worker = new SchedSpot_Worker( $worker_id );
        $service_areas = $worker->profile['service_areas'];

        // Try to get coordinates from first service area
        if ( ! empty( $service_areas ) ) {
            foreach ( $service_areas as $area ) {
                if ( $area['type'] === 'radius' && isset( $area['center'] ) ) {
                    return $area['center'];
                } elseif ( $area['type'] === 'polygon' && ! empty( $area['coordinates'] ) ) {
                    // Return centroid of polygon
                    return $this->calculate_polygon_centroid( $area['coordinates'] );
                }
            }
        }

        // Fallback to geocoding worker's address
        $address = $worker->profile['address'];
        if ( $address ) {
            return $this->geocode_address( $address );
        }

        return false;
    }

    /**
     * Calculate centroid of a polygon.
     *
     * @since 2.0.0
     * @param array $coordinates Array of lat/lng points.
     * @return array Centroid coordinates.
     */
    private function calculate_polygon_centroid( $coordinates ) {
        $lat_sum = 0;
        $lng_sum = 0;
        $count = count( $coordinates );

        foreach ( $coordinates as $coord ) {
            $lat_sum += $coord['lat'];
            $lng_sum += $coord['lng'];
        }

        return array(
            'lat' => $lat_sum / $count,
            'lng' => $lng_sum / $count,
        );
    }

    /**
     * AJAX handler for geocoding addresses.
     *
     * @since 2.0.0
     */
    public function ajax_geocode_address() {
        check_ajax_referer( 'schedspot_geolocation_nonce', 'nonce' );

        $address = sanitize_text_field( $_POST['address'] ?? '' );

        if ( ! $address ) {
            wp_send_json_error( array( 'message' => __( 'Address is required.', 'schedspot' ) ) );
        }

        $result = $this->geocode_address( $address );

        if ( $result ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( array( 'message' => __( 'Unable to geocode address.', 'schedspot' ) ) );
        }
    }

    /**
     * AJAX handler for saving worker service areas.
     *
     * @since 2.0.0
     */
    public function ajax_save_service_area() {
        check_ajax_referer( 'schedspot_geolocation_nonce', 'nonce' );

        if ( ! current_user_can( 'schedspot_manage_availability' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'schedspot' ) ) );
        }

        $worker_id = get_current_user_id();
        $service_areas = json_decode( stripslashes( $_POST['service_areas'] ?? '[]' ), true );

        if ( ! is_array( $service_areas ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid service area data.', 'schedspot' ) ) );
        }

        // Validate service areas
        $validated_areas = array();
        foreach ( $service_areas as $area ) {
            if ( $area['type'] === 'radius' ) {
                if ( isset( $area['center']['lat'], $area['center']['lng'], $area['radius'] ) ) {
                    $validated_areas[] = array(
                        'type'   => 'radius',
                        'center' => array(
                            'lat' => floatval( $area['center']['lat'] ),
                            'lng' => floatval( $area['center']['lng'] ),
                        ),
                        'radius' => floatval( $area['radius'] ),
                        'name'   => sanitize_text_field( $area['name'] ?? '' ),
                    );
                }
            } elseif ( $area['type'] === 'polygon' ) {
                if ( isset( $area['coordinates'] ) && is_array( $area['coordinates'] ) ) {
                    $coordinates = array();
                    foreach ( $area['coordinates'] as $coord ) {
                        if ( isset( $coord['lat'], $coord['lng'] ) ) {
                            $coordinates[] = array(
                                'lat' => floatval( $coord['lat'] ),
                                'lng' => floatval( $coord['lng'] ),
                            );
                        }
                    }
                    if ( count( $coordinates ) >= 3 ) {
                        $validated_areas[] = array(
                            'type'        => 'polygon',
                            'coordinates' => $coordinates,
                            'name'        => sanitize_text_field( $area['name'] ?? '' ),
                        );
                    }
                }
            }
        }

        update_user_meta( $worker_id, 'schedspot_service_areas', $validated_areas );

        do_action( 'schedspot_worker_service_areas_updated', $worker_id, $validated_areas );

        wp_send_json_success( array( 'message' => __( 'Service areas saved successfully.', 'schedspot' ) ) );
    }

    /**
     * AJAX handler for getting nearby workers.
     *
     * @since 2.0.0
     */
    public function ajax_get_nearby_workers() {
        check_ajax_referer( 'schedspot_geolocation_nonce', 'nonce' );

        $lat = floatval( $_POST['lat'] ?? 0 );
        $lng = floatval( $_POST['lng'] ?? 0 );
        $service_id = absint( $_POST['service_id'] ?? 0 );
        $radius = floatval( $_POST['radius'] ?? $this->default_radius );

        if ( ! $lat || ! $lng ) {
            wp_send_json_error( array( 'message' => __( 'Location coordinates are required.', 'schedspot' ) ) );
        }

        $workers = $this->get_nearby_workers( $lat, $lng, $radius, $service_id );

        wp_send_json_success( array(
            'workers' => $workers,
            'count'   => count( $workers ),
        ) );
    }

    /**
     * Get workers near a specific location.
     *
     * @since 2.0.0
     * @param float $lat Client latitude.
     * @param float $lng Client longitude.
     * @param float $radius Search radius in kilometers.
     * @param int   $service_id Optional service ID filter.
     * @return array Array of nearby workers with distance.
     */
    public function get_nearby_workers( $lat, $lng, $radius = null, $service_id = null ) {
        if ( $radius === null ) {
            $radius = $this->default_radius;
        }

        // Get all workers
        $all_workers = get_users( array( 'role' => 'schedspot_worker' ) );
        $nearby_workers = array();

        foreach ( $all_workers as $user ) {
            $worker = new SchedSpot_Worker( $user->ID );

            // Check if worker provides the requested service
            if ( $service_id ) {
                $worker_services = $worker->get_services();
                $provides_service = false;
                foreach ( $worker_services as $service ) {
                    if ( $service['service_id'] == $service_id && $service['is_enabled'] ) {
                        $provides_service = true;
                        break;
                    }
                }
                if ( ! $provides_service ) {
                    continue;
                }
            }

            // Check if worker serves this location
            if ( $this->worker_serves_location( $user->ID, $lat, $lng ) ) {
                $worker_coords = $this->get_worker_coordinates( $user->ID );
                if ( $worker_coords ) {
                    $distance = $this->calculate_distance( $lat, $lng, $worker_coords['lat'], $worker_coords['lng'] );

                    if ( $distance <= $radius ) {
                        $nearby_workers[] = array(
                            'id'       => $user->ID,
                            'name'     => $user->display_name,
                            'distance' => round( $distance, 2 ),
                            'rating'   => $worker->profile['rating'],
                            'hourly_rate' => $worker->profile['hourly_rate'],
                            'avatar'   => get_avatar_url( $user->ID ),
                        );
                    }
                }
            }
        }

        // Sort by distance
        usort( $nearby_workers, function( $a, $b ) {
            return $a['distance'] <=> $b['distance'];
        } );

        return $nearby_workers;
    }

    /**
     * Enqueue frontend scripts and styles.
     *
     * @since 2.0.0
     */
    public function enqueue_frontend_scripts() {
        if ( ! $this->is_enabled() || empty( $this->google_api_key ) ) {
            return;
        }

        // Only enqueue on pages with SchedSpot shortcodes
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) ) {
            return;
        }

        $has_shortcode = false;
        $shortcodes = array( 'schedspot_booking_form', 'schedspot_service_list', 'schedspot_dashboard' );

        foreach ( $shortcodes as $shortcode ) {
            if ( has_shortcode( $post->post_content, $shortcode ) ) {
                $has_shortcode = true;
                break;
            }
        }

        if ( ! $has_shortcode ) {
            return;
        }

        // Enqueue Google Maps API
        wp_enqueue_script(
            'google-maps-api',
            'https://maps.googleapis.com/maps/api/js?key=' . $this->google_api_key . '&libraries=places,geometry',
            array(),
            null,
            true
        );

        // Enqueue geolocation script
        wp_enqueue_script(
            'schedspot-geolocation',
            SCHEDSPOT_PLUGIN_URL . 'public/js/geolocation.js',
            array( 'jquery', 'google-maps-api' ),
            SCHEDSPOT_VERSION,
            true
        );

        wp_localize_script( 'schedspot-geolocation', 'schedspot_geo', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'schedspot_geolocation_nonce' ),
            'strings'  => array(
                'location_required' => __( 'Location is required for this service.', 'schedspot' ),
                'geocoding_failed'  => __( 'Unable to find location. Please try a different address.', 'schedspot' ),
                'getting_location'  => __( 'Getting your location...', 'schedspot' ),
                'location_denied'   => __( 'Location access denied. Please enter your address manually.', 'schedspot' ),
            ),
        ) );
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @since 2.0.0
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( ! $this->is_enabled() || empty( $this->google_api_key ) ) {
            return;
        }

        // Only load on SchedSpot admin pages
        if ( strpos( $hook, 'schedspot' ) === false ) {
            return;
        }

        // Enqueue Google Maps API
        wp_enqueue_script(
            'google-maps-api',
            'https://maps.googleapis.com/maps/api/js?key=' . $this->google_api_key . '&libraries=places,geometry,drawing',
            array(),
            null,
            true
        );

        // Enqueue admin geolocation script
        wp_enqueue_script(
            'schedspot-admin-geolocation',
            SCHEDSPOT_PLUGIN_URL . 'admin/js/geolocation.js',
            array( 'jquery', 'google-maps-api' ),
            SCHEDSPOT_VERSION,
            true
        );

        wp_localize_script( 'schedspot-admin-geolocation', 'schedspot_admin_geo', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'schedspot_geolocation_nonce' ),
            'default_radius' => $this->default_radius,
            'strings'  => array(
                'draw_service_area' => __( 'Draw your service area on the map', 'schedspot' ),
                'save_area'         => __( 'Save Service Area', 'schedspot' ),
                'delete_area'       => __( 'Delete Area', 'schedspot' ),
                'area_saved'        => __( 'Service area saved successfully!', 'schedspot' ),
                'save_failed'       => __( 'Failed to save service area. Please try again.', 'schedspot' ),
            ),
        ) );
    }

    /**
     * Get formatted distance string.
     *
     * @since 2.0.0
     * @param float $distance Distance in kilometers.
     * @return string Formatted distance string.
     */
    public function format_distance( $distance ) {
        $unit = get_option( 'schedspot_distance_unit', 'km' );

        if ( $unit === 'miles' ) {
            $distance = $distance * 0.621371; // Convert km to miles
            if ( $distance < 1 ) {
                return sprintf( __( '%.1f miles', 'schedspot' ), $distance );
            } else {
                return sprintf( __( '%.0f miles', 'schedspot' ), $distance );
            }
        } else {
            if ( $distance < 1 ) {
                return sprintf( __( '%.1f km', 'schedspot' ), $distance );
            } else {
                return sprintf( __( '%.0f km', 'schedspot' ), $distance );
            }
        }
    }
}
