<?php
/**
 * SchedSpot Service List Shortcode
 *
 * Handles the service listing shortcode functionality
 *
 * @package SchedSpot
 * @version 1.6.1
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Service_List Class.
 *
 * @class SchedSpot_Service_List
 * @version 1.6.1
 */
class SchedSpot_Service_List {

    /**
     * Constructor.
     *
     * @since 1.6.1
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize service list functionality.
     *
     * @since 1.6.1
     */
    public function init() {
        add_shortcode( 'schedspot_service_list', array( $this, 'render_service_list' ) );
        add_action( 'wp_ajax_schedspot_filter_services', array( $this, 'filter_services' ) );
        add_action( 'wp_ajax_nopriv_schedspot_filter_services', array( $this, 'filter_services' ) );
        add_action( 'wp_ajax_schedspot_get_service_details', array( $this, 'get_service_details' ) );
        add_action( 'wp_ajax_nopriv_schedspot_get_service_details', array( $this, 'get_service_details' ) );
    }

    /**
     * Render service list shortcode.
     *
     * @since 1.6.1
     * @param array $atts Shortcode attributes.
     * @return string Service list HTML.
     */
    public function render_service_list( $atts ) {
        $atts = shortcode_atts( array(
            'layout' => 'grid', // grid, list, carousel
            'columns' => '3',
            'show_filters' => 'true',
            'show_search' => 'true',
            'show_sorting' => 'true',
            'category' => '',
            'limit' => '12',
            'show_book_button' => 'true',
            'show_price' => 'true',
            'show_duration' => 'true',
            'show_rating' => 'true',
        ), $atts );

        ob_start();
        ?>
        <div class="schedspot-service-list" data-layout="<?php echo esc_attr( $atts['layout'] ); ?>" data-columns="<?php echo esc_attr( $atts['columns'] ); ?>">
            
            <?php if ( $atts['show_search'] === 'true' || $atts['show_filters'] === 'true' || $atts['show_sorting'] === 'true' ) : ?>
                <div class="service-list-controls">
                    <?php if ( $atts['show_search'] === 'true' ) : ?>
                        <div class="service-search">
                            <input type="text" id="service-search" placeholder="<?php _e( 'Search services...', 'schedspot' ); ?>">
                            <button type="button" class="search-btn">
                                <span class="dashicons dashicons-search"></span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ( $atts['show_filters'] === 'true' ) : ?>
                        <div class="service-filters">
                            <select id="category-filter">
                                <option value=""><?php _e( 'All Categories', 'schedspot' ); ?></option>
                                <?php
                                $categories = $this->get_service_categories();
                                foreach ( $categories as $category ) {
                                    $selected = ( $atts['category'] === $category ) ? 'selected' : '';
                                    echo '<option value="' . esc_attr( $category ) . '" ' . $selected . '>' . esc_html( $category ) . '</option>';
                                }
                                ?>
                            </select>

                            <select id="price-filter">
                                <option value=""><?php _e( 'Any Price', 'schedspot' ); ?></option>
                                <option value="0-50"><?php _e( '$0 - $50', 'schedspot' ); ?></option>
                                <option value="50-100"><?php _e( '$50 - $100', 'schedspot' ); ?></option>
                                <option value="100-200"><?php _e( '$100 - $200', 'schedspot' ); ?></option>
                                <option value="200+"><?php _e( '$200+', 'schedspot' ); ?></option>
                            </select>

                            <select id="rating-filter">
                                <option value=""><?php _e( 'Any Rating', 'schedspot' ); ?></option>
                                <option value="4+"><?php _e( '4+ Stars', 'schedspot' ); ?></option>
                                <option value="3+"><?php _e( '3+ Stars', 'schedspot' ); ?></option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if ( $atts['show_sorting'] === 'true' ) : ?>
                        <div class="service-sorting">
                            <select id="sort-services">
                                <option value="name"><?php _e( 'Sort by Name', 'schedspot' ); ?></option>
                                <option value="price-low"><?php _e( 'Price: Low to High', 'schedspot' ); ?></option>
                                <option value="price-high"><?php _e( 'Price: High to Low', 'schedspot' ); ?></option>
                                <option value="rating"><?php _e( 'Highest Rated', 'schedspot' ); ?></option>
                                <option value="popular"><?php _e( 'Most Popular', 'schedspot' ); ?></option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="layout-toggle">
                        <button class="layout-btn <?php echo $atts['layout'] === 'grid' ? 'active' : ''; ?>" data-layout="grid">
                            <span class="dashicons dashicons-grid-view"></span>
                        </button>
                        <button class="layout-btn <?php echo $atts['layout'] === 'list' ? 'active' : ''; ?>" data-layout="list">
                            <span class="dashicons dashicons-list-view"></span>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="service-list-container">
                <div class="services-loading" style="display: none;">
                    <span class="dashicons dashicons-update spin"></span>
                    <?php _e( 'Loading services...', 'schedspot' ); ?>
                </div>

                <div class="services-grid" data-layout="<?php echo esc_attr( $atts['layout'] ); ?>">
                    <?php echo $this->render_services( $atts ); ?>
                </div>

                <div class="no-services" style="display: none;">
                    <div class="no-services-content">
                        <span class="dashicons dashicons-info"></span>
                        <h3><?php _e( 'No Services Found', 'schedspot' ); ?></h3>
                        <p><?php _e( 'Try adjusting your search criteria or filters.', 'schedspot' ); ?></p>
                    </div>
                </div>
            </div>

            <?php if ( $atts['layout'] === 'carousel' ) : ?>
                <div class="carousel-controls">
                    <button class="carousel-btn prev-btn">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </button>
                    <button class="carousel-btn next-btn">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="service-list-pagination">
                <!-- Pagination will be loaded via AJAX -->
            </div>
        </div>

        <!-- Service Details Modal -->
        <div id="service-details-modal" class="schedspot-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><?php _e( 'Service Details', 'schedspot' ); ?></h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="service-details-loading">
                        <span class="dashicons dashicons-update spin"></span>
                        <?php _e( 'Loading service details...', 'schedspot' ); ?>
                    </div>
                    <div class="service-details-content"></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render services based on attributes.
     *
     * @since 1.6.1
     * @param array $atts Shortcode attributes.
     * @return string Services HTML.
     */
    private function render_services( $atts ) {
        $services = $this->get_services( $atts );
        
        if ( empty( $services ) ) {
            return '<div class="no-services-message">' . __( 'No services available at the moment.', 'schedspot' ) . '</div>';
        }

        $html = '';
        foreach ( $services as $service ) {
            $html .= $this->render_service_card( $service, $atts );
        }

        return $html;
    }

    /**
     * Render individual service card.
     *
     * @since 1.6.1
     * @param object $service Service object.
     * @param array $atts Shortcode attributes.
     * @return string Service card HTML.
     */
    private function render_service_card( $service, $atts ) {
        $service_image = $this->get_service_image( $service->id );
        $service_rating = $this->get_service_rating( $service->id );
        $worker_count = $this->get_service_worker_count( $service->id );
        
        ob_start();
        ?>
        <div class="service-card" data-service-id="<?php echo esc_attr( $service->id ); ?>" data-category="<?php echo esc_attr( $service->category ); ?>" data-price="<?php echo esc_attr( $service->price ); ?>">
            <div class="service-image">
                <img src="<?php echo esc_url( $service_image ); ?>" alt="<?php echo esc_attr( $service->name ); ?>">
                <div class="service-overlay">
                    <button class="service-details-btn" data-service-id="<?php echo esc_attr( $service->id ); ?>">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e( 'View Details', 'schedspot' ); ?>
                    </button>
                </div>
            </div>

            <div class="service-content">
                <div class="service-header">
                    <h3 class="service-title"><?php echo esc_html( $service->name ); ?></h3>
                    <?php if ( $service->category ) : ?>
                        <span class="service-category"><?php echo esc_html( $service->category ); ?></span>
                    <?php endif; ?>
                </div>

                <div class="service-description">
                    <?php echo wp_trim_words( $service->description, 20, '...' ); ?>
                </div>

                <div class="service-meta">
                    <?php if ( $atts['show_price'] === 'true' ) : ?>
                        <div class="service-price">
                            <span class="price-label"><?php _e( 'Starting at', 'schedspot' ); ?></span>
                            <span class="price-amount">$<?php echo esc_html( number_format( $service->price, 2 ) ); ?></span>
                            <?php if ( $service->price_type === 'hourly' ) : ?>
                                <span class="price-unit">/hr</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $atts['show_duration'] === 'true' && $service->duration ) : ?>
                        <div class="service-duration">
                            <span class="dashicons dashicons-clock"></span>
                            <?php echo esc_html( $service->duration ); ?> <?php _e( 'min', 'schedspot' ); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $atts['show_rating'] === 'true' ) : ?>
                        <div class="service-rating">
                            <div class="rating-stars">
                                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                    <span class="star <?php echo $i <= $service_rating ? 'filled' : 'empty'; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-text">(<?php echo esc_html( number_format( $service_rating, 1 ) ); ?>)</span>
                        </div>
                    <?php endif; ?>

                    <div class="service-workers">
                        <span class="dashicons dashicons-groups"></span>
                        <?php printf( _n( '%d worker available', '%d workers available', $worker_count, 'schedspot' ), $worker_count ); ?>
                    </div>
                </div>

                <?php if ( $atts['show_book_button'] === 'true' ) : ?>
                    <div class="service-actions">
                        <button class="book-service-btn schedspot-btn schedspot-btn-primary" data-service-id="<?php echo esc_attr( $service->id ); ?>">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php _e( 'Book Now', 'schedspot' ); ?>
                        </button>
                        
                        <button class="service-details-btn schedspot-btn schedspot-btn-secondary" data-service-id="<?php echo esc_attr( $service->id ); ?>">
                            <span class="dashicons dashicons-info"></span>
                            <?php _e( 'Details', 'schedspot' ); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Filter services via AJAX.
     *
     * @since 1.6.1
     */
    public function filter_services() {
        $filters = array(
            'search' => sanitize_text_field( $_POST['search'] ?? '' ),
            'category' => sanitize_text_field( $_POST['category'] ?? '' ),
            'price_range' => sanitize_text_field( $_POST['price_range'] ?? '' ),
            'rating' => sanitize_text_field( $_POST['rating'] ?? '' ),
            'sort' => sanitize_text_field( $_POST['sort'] ?? 'name' ),
            'page' => absint( $_POST['page'] ?? 1 ),
            'per_page' => absint( $_POST['per_page'] ?? 12 ),
        );

        $services = $this->get_filtered_services( $filters );
        $total_services = $this->get_filtered_services_count( $filters );

        $html = '';
        foreach ( $services as $service ) {
            $html .= $this->render_service_card( $service, array(
                'show_price' => 'true',
                'show_duration' => 'true',
                'show_rating' => 'true',
                'show_book_button' => 'true',
            ) );
        }

        wp_send_json_success( array(
            'services_html' => $html,
            'total_services' => $total_services,
            'current_page' => $filters['page'],
            'total_pages' => ceil( $total_services / $filters['per_page'] ),
        ) );
    }

    /**
     * Get service details via AJAX.
     *
     * @since 1.6.1
     */
    public function get_service_details() {
        $service_id = absint( $_GET['service_id'] );
        
        if ( ! $service_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid service ID.', 'schedspot' ) ) );
        }

        $service = new SchedSpot_Service( $service_id );
        
        if ( ! $service->id ) {
            wp_send_json_error( array( 'message' => __( 'Service not found.', 'schedspot' ) ) );
        }

        $service_details = array(
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'category' => $service->category,
            'price' => $service->price,
            'price_type' => $service->price_type,
            'duration' => $service->duration,
            'requirements' => $service->requirements,
            'image' => $this->get_service_image( $service->id ),
            'rating' => $this->get_service_rating( $service->id ),
            'worker_count' => $this->get_service_worker_count( $service->id ),
            'workers' => $this->get_service_workers( $service->id ),
        );

        wp_send_json_success( $service_details );
    }

    /**
     * Get services based on filters.
     *
     * @since 1.6.1
     * @param array $atts Shortcode attributes or filters.
     * @return array Services array.
     */
    private function get_services( $atts ) {
        $services = SchedSpot_Service::get_all_services();

        // Apply category filter if specified
        if ( ! empty( $atts['category'] ) ) {
            $services = array_filter( $services, function( $service ) use ( $atts ) {
                return $service->category === $atts['category'];
            } );
        }

        // Apply limit if specified
        if ( ! empty( $atts['limit'] ) ) {
            $services = array_slice( $services, 0, intval( $atts['limit'] ) );
        }

        return $services;
    }

    /**
     * Get filtered services.
     *
     * @since 1.6.1
     * @param array $filters Filter parameters.
     * @return array Filtered services.
     */
    private function get_filtered_services( $filters ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_services';

        $where_conditions = array( "status = 'active'" );
        $where_values = array();

        // Search filter
        if ( ! empty( $filters['search'] ) ) {
            $where_conditions[] = "(name LIKE %s OR description LIKE %s)";
            $search_term = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        // Category filter
        if ( ! empty( $filters['category'] ) ) {
            $where_conditions[] = "category = %s";
            $where_values[] = $filters['category'];
        }

        // Price range filter
        if ( ! empty( $filters['price_range'] ) ) {
            switch ( $filters['price_range'] ) {
                case '0-50':
                    $where_conditions[] = "price BETWEEN 0 AND 50";
                    break;
                case '50-100':
                    $where_conditions[] = "price BETWEEN 50 AND 100";
                    break;
                case '100-200':
                    $where_conditions[] = "price BETWEEN 100 AND 200";
                    break;
                case '200+':
                    $where_conditions[] = "price > 200";
                    break;
            }
        }

        // Rating filter
        if ( ! empty( $filters['rating'] ) ) {
            $min_rating = floatval( str_replace( '+', '', $filters['rating'] ) );
            $where_conditions[] = "average_rating >= %f";
            $where_values[] = $min_rating;
        }

        // Build query
        $where_clause = implode( ' AND ', $where_conditions );
        $order_clause = $this->get_order_clause( $filters['sort'] ?? 'name' );
        $limit_clause = sprintf( "LIMIT %d OFFSET %d",
            $filters['per_page'],
            ( $filters['page'] - 1 ) * $filters['per_page']
        );

        $query = "SELECT * FROM {$table_name} WHERE {$where_clause} {$order_clause} {$limit_clause}";

        if ( ! empty( $where_values ) ) {
            $query = $wpdb->prepare( $query, $where_values );
        }

        return $wpdb->get_results( $query );
    }

    /**
     * Get filtered services count.
     *
     * @since 1.6.1
     * @param array $filters Filter parameters.
     * @return int Services count.
     */
    private function get_filtered_services_count( $filters ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_services';

        $where_conditions = array( "status = 'active'" );
        $where_values = array();

        // Apply same filters as get_filtered_services but for count
        if ( ! empty( $filters['search'] ) ) {
            $where_conditions[] = "(name LIKE %s OR description LIKE %s)";
            $search_term = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        if ( ! empty( $filters['category'] ) ) {
            $where_conditions[] = "category = %s";
            $where_values[] = $filters['category'];
        }

        if ( ! empty( $filters['price_range'] ) ) {
            switch ( $filters['price_range'] ) {
                case '0-50':
                    $where_conditions[] = "price BETWEEN 0 AND 50";
                    break;
                case '50-100':
                    $where_conditions[] = "price BETWEEN 50 AND 100";
                    break;
                case '100-200':
                    $where_conditions[] = "price BETWEEN 100 AND 200";
                    break;
                case '200+':
                    $where_conditions[] = "price > 200";
                    break;
            }
        }

        if ( ! empty( $filters['rating'] ) ) {
            $min_rating = floatval( str_replace( '+', '', $filters['rating'] ) );
            $where_conditions[] = "average_rating >= %f";
            $where_values[] = $min_rating;
        }

        $where_clause = implode( ' AND ', $where_conditions );
        $query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";

        if ( ! empty( $where_values ) ) {
            $query = $wpdb->prepare( $query, $where_values );
        }

        return intval( $wpdb->get_var( $query ) );
    }

    /**
     * Get service categories.
     *
     * @since 1.6.1
     * @return array Categories array.
     */
    private function get_service_categories() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_services';

        $categories = $wpdb->get_col(
            "SELECT DISTINCT category FROM {$table_name}
             WHERE status = 'active' AND category IS NOT NULL AND category != ''
             ORDER BY category ASC"
        );

        return $categories ?: array();
    }

    /**
     * Get order clause for SQL query.
     *
     * @since 1.6.1
     * @param string $sort Sort parameter.
     * @return string Order clause.
     */
    private function get_order_clause( $sort ) {
        switch ( $sort ) {
            case 'price-low':
                return 'ORDER BY price ASC';
            case 'price-high':
                return 'ORDER BY price DESC';
            case 'rating':
                return 'ORDER BY average_rating DESC';
            case 'popular':
                return 'ORDER BY booking_count DESC';
            case 'name':
            default:
                return 'ORDER BY name ASC';
        }
    }

    /**
     * Get service image URL.
     *
     * @since 1.6.1
     * @param int $service_id Service ID.
     * @return string Image URL.
     */
    private function get_service_image( $service_id ) {
        $image_url = get_post_meta( $service_id, 'schedspot_service_image', true );

        if ( $image_url ) {
            return $image_url;
        }

        // Return placeholder image
        return SCHEDSPOT_PLUGIN_URL . 'assets/images/service-placeholder.jpg';
    }

    /**
     * Get service average rating.
     *
     * @since 1.6.1
     * @param int $service_id Service ID.
     * @return float Average rating.
     */
    private function get_service_rating( $service_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_bookings';

        $rating = $wpdb->get_var( $wpdb->prepare(
            "SELECT AVG(rating) FROM {$table_name}
             WHERE service_id = %d AND rating > 0",
            $service_id
        ) );

        return $rating ? floatval( $rating ) : 0.0;
    }

    /**
     * Get service worker count.
     *
     * @since 1.6.1
     * @param int $service_id Service ID.
     * @return int Worker count.
     */
    private function get_service_worker_count( $service_id ) {
        global $wpdb;

        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta}
             WHERE meta_key = 'schedspot_worker_services'
             AND meta_value LIKE %s",
            '%' . $wpdb->esc_like( serialize( strval( $service_id ) ) ) . '%'
        ) );

        return $count ? intval( $count ) : 0;
    }

    /**
     * Get service workers.
     *
     * @since 1.6.1
     * @param int $service_id Service ID.
     * @return array Workers array.
     */
    private function get_service_workers( $service_id ) {
        global $wpdb;

        $user_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$wpdb->usermeta}
             WHERE meta_key = 'schedspot_worker_services'
             AND meta_value LIKE %s",
            '%' . $wpdb->esc_like( serialize( strval( $service_id ) ) ) . '%'
        ) );

        $workers = array();
        foreach ( $user_ids as $user_id ) {
            $user = get_user_by( 'ID', $user_id );
            if ( $user && in_array( 'schedspot_worker', $user->roles ) ) {
                $workers[] = array(
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'avatar' => get_avatar_url( $user->ID ),
                    'rating' => get_user_meta( $user->ID, 'schedspot_worker_rating', true ) ?: 0,
                    'hourly_rate' => get_user_meta( $user->ID, 'schedspot_worker_hourly_rate', true ) ?: 0,
                );
            }
        }

        return $workers;
    }
}
