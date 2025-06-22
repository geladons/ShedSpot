<?php
/**
 * WooCommerce Integration Class
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_WooCommerce Class.
 *
 * @class SchedSpot_WooCommerce
 * @version 1.0.0
 */
class SchedSpot_WooCommerce {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize WooCommerce integration.
     *
     * @since 1.0.0
     */
    public function init() {
        // Check if WooCommerce is active
        if ( ! $this->is_woocommerce_active() ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
            return;
        }

        // Initialize hooks
        add_action( 'init', array( $this, 'init_hooks' ) );
        add_action( 'schedspot_booking_created', array( $this, 'create_order_for_booking' ), 10, 2 );
        add_action( 'woocommerce_order_status_completed', array( $this, 'handle_payment_completed' ) );
        add_action( 'woocommerce_order_status_cancelled', array( $this, 'handle_payment_cancelled' ) );
        add_action( 'woocommerce_order_status_refunded', array( $this, 'handle_payment_refunded' ) );
        
        // Add custom order meta
        add_action( 'woocommerce_checkout_create_order', array( $this, 'add_booking_meta_to_order' ), 10, 2 );
        
        // Add booking details to order emails
        add_action( 'woocommerce_email_order_details', array( $this, 'add_booking_details_to_email' ), 10, 4 );
    }

    /**
     * Initialize hooks after WooCommerce is loaded.
     *
     * @since 1.0.0
     */
    public function init_hooks() {
        // Create virtual products for services
        add_action( 'schedspot_service_created', array( $this, 'create_product_for_service' ), 10, 2 );
        add_action( 'schedspot_service_updated', array( $this, 'update_product_for_service' ), 10, 2 );
        add_action( 'schedspot_service_deleted', array( $this, 'delete_product_for_service' ) );
    }

    /**
     * Check if WooCommerce is active.
     *
     * @since 1.0.0
     * @return bool True if WooCommerce is active, false otherwise.
     */
    private function is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }

    /**
     * Display WooCommerce missing notice.
     *
     * @since 1.0.0
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e( 'SchedSpot requires WooCommerce to be installed and activated for payment processing.', 'schedspot' ); ?></p>
        </div>
        <?php
    }

    /**
     * Create WooCommerce product for service.
     *
     * @since 1.0.0
     * @param int   $service_id Service ID.
     * @param array $service_data Service data.
     */
    public function create_product_for_service( $service_id, $service_data ) {
        $service = new SchedSpot_Service( $service_id );
        
        if ( ! $service->id ) {
            return;
        }

        // Check if product already exists
        $existing_product_id = get_post_meta( $service_id, 'schedspot_wc_product_id', true );
        if ( $existing_product_id && get_post( $existing_product_id ) ) {
            return;
        }

        // Create virtual product
        $product = new WC_Product_Simple();
        $product->set_name( $service->name );
        $product->set_description( $service->description );
        $product->set_short_description( $service->description );
        $product->set_regular_price( $service->base_price );
        $product->set_virtual( true );
        $product->set_downloadable( false );
        $product->set_catalog_visibility( 'hidden' ); // Hide from catalog
        $product->set_status( $service->is_active ? 'publish' : 'draft' );
        
        // Set categories
        if ( ! empty( $service->category ) ) {
            $category_id = $this->get_or_create_product_category( $service->category );
            if ( $category_id ) {
                $product->set_category_ids( array( $category_id ) );
            }
        }

        // Add custom meta
        $product->add_meta_data( 'schedspot_service_id', $service_id );
        $product->add_meta_data( 'schedspot_duration', $service->duration );
        $product->add_meta_data( 'schedspot_price_type', $service->price_type );

        $product_id = $product->save();

        if ( $product_id ) {
            // Store product ID in service meta
            update_post_meta( $service_id, 'schedspot_wc_product_id', $product_id );
            
            // Store service ID in product meta for reverse lookup
            update_post_meta( $product_id, '_schedspot_service_id', $service_id );
        }
    }

    /**
     * Update WooCommerce product for service.
     *
     * @since 1.0.0
     * @param int   $service_id Service ID.
     * @param array $service_data Service data.
     */
    public function update_product_for_service( $service_id, $service_data ) {
        $product_id = get_post_meta( $service_id, 'schedspot_wc_product_id', true );
        
        if ( ! $product_id ) {
            // Create product if it doesn't exist
            $this->create_product_for_service( $service_id, $service_data );
            return;
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return;
        }

        $service = new SchedSpot_Service( $service_id );
        
        // Update product details
        $product->set_name( $service->name );
        $product->set_description( $service->description );
        $product->set_short_description( $service->description );
        $product->set_regular_price( $service->base_price );
        $product->set_status( $service->is_active ? 'publish' : 'draft' );

        // Update meta
        $product->update_meta_data( 'schedspot_duration', $service->duration );
        $product->update_meta_data( 'schedspot_price_type', $service->price_type );

        $product->save();
    }

    /**
     * Delete WooCommerce product for service.
     *
     * @since 1.0.0
     * @param int $service_id Service ID.
     */
    public function delete_product_for_service( $service_id ) {
        $product_id = get_post_meta( $service_id, 'schedspot_wc_product_id', true );
        
        if ( $product_id ) {
            wp_delete_post( $product_id, true );
            delete_post_meta( $service_id, 'schedspot_wc_product_id' );
        }
    }

    /**
     * Get or create product category.
     *
     * @since 1.0.0
     * @param string $category_name Category name.
     * @return int|false Category ID or false on failure.
     */
    private function get_or_create_product_category( $category_name ) {
        $term = get_term_by( 'name', $category_name, 'product_cat' );
        
        if ( $term ) {
            return $term->term_id;
        }

        // Create new category
        $result = wp_insert_term( $category_name, 'product_cat' );
        
        if ( is_wp_error( $result ) ) {
            return false;
        }

        return $result['term_id'];
    }

    /**
     * Create WooCommerce order for booking.
     *
     * @since 1.0.0
     * @param int   $booking_id Booking ID.
     * @param array $booking_data Booking data.
     */
    public function create_order_for_booking( $booking_id, $booking_data ) {
        $booking = new SchedSpot_Booking( $booking_id );
        
        if ( ! $booking->id ) {
            return;
        }

        // Check if order already exists
        $existing_order_id = get_post_meta( $booking_id, 'schedspot_wc_order_id', true );
        if ( $existing_order_id && get_post( $existing_order_id ) ) {
            return;
        }

        // Get or create customer
        $customer_id = $this->get_or_create_customer( $booking );

        // Create order
        $order = wc_create_order( array( 'customer_id' => $customer_id ) );
        
        if ( is_wp_error( $order ) ) {
            return;
        }

        // Get service product
        $product_id = null;
        if ( $booking->service_id ) {
            $product_id = get_post_meta( $booking->service_id, 'schedspot_wc_product_id', true );
        }

        // Calculate pricing
        $pricing = $this->calculate_booking_pricing( $booking );

        if ( $product_id && get_post( $product_id ) ) {
            // Add service product to order
            $product = wc_get_product( $product_id );
            $order->add_product( $product, 1, array(
                'subtotal' => $pricing['service_cost'],
                'total'    => $pricing['service_cost'],
            ) );
        } else {
            // Add generic service item
            $item = new WC_Order_Item_Product();
            $item->set_name( __( 'Service Booking', 'schedspot' ) );
            $item->set_quantity( 1 );
            $item->set_subtotal( $pricing['service_cost'] );
            $item->set_total( $pricing['service_cost'] );
            $order->add_item( $item );
        }

        // Add system fee if applicable
        if ( $pricing['system_fee'] > 0 ) {
            $fee = new WC_Order_Item_Fee();
            $fee->set_name( __( 'Service Fee', 'schedspot' ) );
            $fee->set_amount( $pricing['system_fee'] );
            $fee->set_total( $pricing['system_fee'] );
            $order->add_item( $fee );
        }

        // Set billing address
        $order->set_billing_first_name( $booking->client_details['name'] );
        $order->set_billing_email( $booking->client_details['email'] );
        $order->set_billing_phone( $booking->client_details['phone'] );
        
        if ( ! empty( $booking->client_details['address'] ) ) {
            $address_parts = explode( ',', $booking->client_details['address'] );
            $order->set_billing_address_1( trim( $address_parts[0] ) );
            if ( isset( $address_parts[1] ) ) {
                $order->set_billing_city( trim( $address_parts[1] ) );
            }
        }

        // Add booking meta to order
        $order->add_meta_data( 'schedspot_booking_id', $booking_id );
        $order->add_meta_data( 'schedspot_worker_id', $booking->worker_id );
        $order->add_meta_data( 'schedspot_booking_date', $booking->booking_date );
        $order->add_meta_data( 'schedspot_start_time', $booking->start_time );
        $order->add_meta_data( 'schedspot_duration', $booking->duration );
        $order->add_meta_data( 'schedspot_commission_amount', $pricing['commission'] );

        // Calculate totals
        $order->calculate_totals();

        // Set order status
        $order->set_status( 'pending' );

        // Save order
        $order->save();

        // Store order ID in booking
        update_post_meta( $booking_id, 'schedspot_wc_order_id', $order->get_id() );

        // Update booking with pricing
        $booking->update( array(
            'total_cost'        => $pricing['total'],
            'deposit_amount'    => $pricing['deposit'],
            'commission_amount' => $pricing['commission'],
        ) );

        // Fire action hook
        do_action( 'schedspot_order_created_for_booking', $order->get_id(), $booking_id );
    }

    /**
     * Calculate booking pricing including fees and commission.
     *
     * @since 1.0.0
     * @param SchedSpot_Booking $booking Booking object.
     * @return array Pricing breakdown.
     */
    public function calculate_booking_pricing( $booking ) {
        $service_cost = 0;
        $system_fee_per_hour = floatval( get_option( 'schedspot_system_fee_per_hour', 0 ) );
        $commission_rate = floatval( get_option( 'schedspot_commission_rate', 10 ) );

        // Get worker's custom price or service base price
        if ( $booking->service_id ) {
            global $wpdb;
            $custom_price = $wpdb->get_var( $wpdb->prepare(
                "SELECT custom_price FROM {$wpdb->prefix}schedspot_worker_services 
                 WHERE worker_id = %d AND service_id = %d",
                $booking->worker_id,
                $booking->service_id
            ) );

            if ( $custom_price ) {
                $hourly_rate = floatval( $custom_price );
            } else {
                $service = new SchedSpot_Service( $booking->service_id );
                $hourly_rate = $service->base_price;
            }
        } else {
            // Use worker's default hourly rate
            $worker = new SchedSpot_Worker( $booking->worker_id );
            $hourly_rate = $worker->profile['hourly_rate'];
        }

        // Calculate service cost based on duration
        $hours = $booking->duration / 60;
        $service_cost = $hourly_rate * $hours;

        // Calculate system fee
        $system_fee = $system_fee_per_hour * $hours;

        // Calculate commission
        $commission = ( $service_cost * $commission_rate ) / 100;

        // Calculate total
        $total = $service_cost + $system_fee;

        // Calculate deposit (30% of total by default)
        $deposit_rate = floatval( get_option( 'schedspot_deposit_rate', 30 ) );
        $deposit = ( $total * $deposit_rate ) / 100;

        return array(
            'service_cost' => $service_cost,
            'system_fee'   => $system_fee,
            'commission'   => $commission,
            'total'        => $total,
            'deposit'      => $deposit,
            'worker_earnings' => $service_cost - $commission,
        );
    }

    /**
     * Get or create WooCommerce customer.
     *
     * @since 1.0.0
     * @param SchedSpot_Booking $booking Booking object.
     * @return int Customer ID.
     */
    private function get_or_create_customer( $booking ) {
        // Check if user exists
        if ( $booking->user_id > 0 ) {
            return $booking->user_id;
        }

        // Check if customer exists by email
        $customer = get_user_by( 'email', $booking->client_details['email'] );
        
        if ( $customer ) {
            return $customer->ID;
        }

        // Create new customer
        $customer_data = array(
            'user_login' => $booking->client_details['email'],
            'user_email' => $booking->client_details['email'],
            'display_name' => $booking->client_details['name'],
            'first_name' => $booking->client_details['name'],
            'role' => 'customer',
        );

        $customer_id = wp_insert_user( $customer_data );

        if ( ! is_wp_error( $customer_id ) ) {
            // Add customer role
            $user = new WP_User( $customer_id );
            $user->add_role( 'schedspot_customer' );
            
            return $customer_id;
        }

        return 0;
    }

    /**
     * Handle payment completed.
     *
     * @since 1.0.0
     * @param int $order_id Order ID.
     */
    public function handle_payment_completed( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $booking_id = $order->get_meta( 'schedspot_booking_id' );

        if ( ! $booking_id ) {
            return;
        }

        $booking = new SchedSpot_Booking( $booking_id );

        if ( ! $booking->id ) {
            return;
        }

        // Update booking status
        $booking->update( array( 'status' => 'confirmed' ) );

        // Record payment
        $this->record_payment( $booking_id, $order_id, 'completed' );

        // Send confirmation emails
        $this->send_booking_confirmation_emails( $booking );

        // Fire action hook
        do_action( 'schedspot_payment_completed', $booking_id, $order_id );
    }

    /**
     * Handle payment cancelled.
     *
     * @since 1.0.0
     * @param int $order_id Order ID.
     */
    public function handle_payment_cancelled( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $booking_id = $order->get_meta( 'schedspot_booking_id' );

        if ( ! $booking_id ) {
            return;
        }

        $booking = new SchedSpot_Booking( $booking_id );

        if ( ! $booking->id ) {
            return;
        }

        // Update booking status
        $booking->update( array( 'status' => 'cancelled' ) );

        // Record payment
        $this->record_payment( $booking_id, $order_id, 'cancelled' );

        // Fire action hook
        do_action( 'schedspot_payment_cancelled', $booking_id, $order_id );
    }

    /**
     * Handle payment refunded.
     *
     * @since 1.0.0
     * @param int $order_id Order ID.
     */
    public function handle_payment_refunded( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $booking_id = $order->get_meta( 'schedspot_booking_id' );

        if ( ! $booking_id ) {
            return;
        }

        $booking = new SchedSpot_Booking( $booking_id );

        if ( ! $booking->id ) {
            return;
        }

        // Update booking status
        $booking->update( array( 'status' => 'refunded' ) );

        // Record payment
        $this->record_payment( $booking_id, $order_id, 'refunded' );

        // Fire action hook
        do_action( 'schedspot_payment_refunded', $booking_id, $order_id );
    }

    /**
     * Record payment transaction.
     *
     * @since 1.0.0
     * @param int    $booking_id Booking ID.
     * @param int    $order_id Order ID.
     * @param string $status Payment status.
     */
    private function record_payment( $booking_id, $order_id, $status ) {
        global $wpdb;

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $payment_data = array(
            'booking_id'       => $booking_id,
            'order_id'         => $order_id,
            'amount'           => $order->get_total(),
            'payment_method'   => $order->get_payment_method(),
            'transaction_id'   => $order->get_transaction_id(),
            'status'           => $status,
            'payment_date'     => current_time( 'mysql' ),
        );

        // Check if payment record already exists
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}schedspot_payments WHERE booking_id = %d AND order_id = %d",
            $booking_id,
            $order_id
        ) );

        if ( $existing ) {
            // Update existing record
            $wpdb->update(
                $wpdb->prefix . 'schedspot_payments',
                $payment_data,
                array( 'id' => $existing ),
                array( '%d', '%d', '%f', '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );
        } else {
            // Insert new record
            $wpdb->insert(
                $wpdb->prefix . 'schedspot_payments',
                $payment_data,
                array( '%d', '%d', '%f', '%s', '%s', '%s', '%s' )
            );
        }
    }

    /**
     * Send booking confirmation emails.
     *
     * @since 1.0.0
     * @param SchedSpot_Booking $booking Booking object.
     */
    private function send_booking_confirmation_emails( $booking ) {
        // Send email to customer
        $this->send_customer_confirmation_email( $booking );

        // Send email to worker
        $this->send_worker_notification_email( $booking );
    }

    /**
     * Send customer confirmation email.
     *
     * @since 1.0.0
     * @param SchedSpot_Booking $booking Booking object.
     */
    private function send_customer_confirmation_email( $booking ) {
        $to = $booking->client_details['email'];
        $subject = sprintf( __( 'Booking Confirmation - %s', 'schedspot' ), get_bloginfo( 'name' ) );

        $worker = get_userdata( $booking->worker_id );
        $service = new SchedSpot_Service( $booking->service_id );

        $message = sprintf(
            __( 'Dear %s,

Your booking has been confirmed!

Booking Details:
- Service: %s
- Worker: %s
- Date: %s
- Time: %s
- Duration: %d minutes
- Total Cost: $%.2f

We will send you a reminder 24 hours before your appointment.

Thank you for choosing %s!', 'schedspot' ),
            $booking->client_details['name'],
            $service->name,
            $worker->display_name,
            date( 'F j, Y', strtotime( $booking->booking_date ) ),
            date( 'g:i A', strtotime( $booking->start_time ) ),
            $booking->duration,
            $booking->total_cost,
            get_bloginfo( 'name' )
        );

        wp_mail( $to, $subject, $message );
    }

    /**
     * Send worker notification email.
     *
     * @since 1.0.0
     * @param SchedSpot_Booking $booking Booking object.
     */
    private function send_worker_notification_email( $booking ) {
        $worker = get_userdata( $booking->worker_id );

        if ( ! $worker ) {
            return;
        }

        $to = $worker->user_email;
        $subject = sprintf( __( 'New Booking Assignment - %s', 'schedspot' ), get_bloginfo( 'name' ) );

        $service = new SchedSpot_Service( $booking->service_id );
        $earnings = $booking->total_cost - $booking->commission_amount;

        $message = sprintf(
            __( 'Hello %s,

You have a new booking assignment!

Booking Details:
- Client: %s
- Service: %s
- Date: %s
- Time: %s
- Duration: %d minutes
- Your Earnings: $%.2f

Client Contact:
- Email: %s
- Phone: %s

Please log in to your dashboard to view more details.

Best regards,
%s Team', 'schedspot' ),
            $worker->display_name,
            $booking->client_details['name'],
            $service->name,
            date( 'F j, Y', strtotime( $booking->booking_date ) ),
            date( 'g:i A', strtotime( $booking->start_time ) ),
            $booking->duration,
            $earnings,
            $booking->client_details['email'],
            $booking->client_details['phone'],
            get_bloginfo( 'name' )
        );

        wp_mail( $to, $subject, $message );
    }

    /**
     * Add booking meta to order.
     *
     * @since 1.0.0
     * @param WC_Order $order Order object.
     * @param array    $data Checkout data.
     */
    public function add_booking_meta_to_order( $order, $data ) {
        // This will be called during checkout
        // Additional booking meta can be added here if needed
    }

    /**
     * Add booking details to order emails.
     *
     * @since 1.0.0
     * @param WC_Order $order Order object.
     * @param bool     $sent_to_admin Whether sent to admin.
     * @param bool     $plain_text Whether plain text email.
     * @param WC_Email $email Email object.
     */
    public function add_booking_details_to_email( $order, $sent_to_admin, $plain_text, $email ) {
        $booking_id = $order->get_meta( 'schedspot_booking_id' );

        if ( ! $booking_id ) {
            return;
        }

        $booking = new SchedSpot_Booking( $booking_id );

        if ( ! $booking->id ) {
            return;
        }

        $worker = get_userdata( $booking->worker_id );
        $service = new SchedSpot_Service( $booking->service_id );

        if ( $plain_text ) {
            echo "\n" . __( 'BOOKING DETAILS', 'schedspot' ) . "\n";
            echo str_repeat( '-', 20 ) . "\n";
            echo sprintf( __( 'Service: %s', 'schedspot' ), $service->name ) . "\n";
            echo sprintf( __( 'Worker: %s', 'schedspot' ), $worker->display_name ) . "\n";
            echo sprintf( __( 'Date: %s', 'schedspot' ), date( 'F j, Y', strtotime( $booking->booking_date ) ) ) . "\n";
            echo sprintf( __( 'Time: %s', 'schedspot' ), date( 'g:i A', strtotime( $booking->start_time ) ) ) . "\n";
            echo sprintf( __( 'Duration: %d minutes', 'schedspot' ), $booking->duration ) . "\n";
        } else {
            ?>
            <h2><?php _e( 'Booking Details', 'schedspot' ); ?></h2>
            <table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
                <tbody>
                    <tr>
                        <th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Service', 'schedspot' ); ?></th>
                        <td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( $service->name ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Worker', 'schedspot' ); ?></th>
                        <td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( $worker->display_name ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Date', 'schedspot' ); ?></th>
                        <td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( date( 'F j, Y', strtotime( $booking->booking_date ) ) ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Time', 'schedspot' ); ?></th>
                        <td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( date( 'g:i A', strtotime( $booking->start_time ) ) ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Duration', 'schedspot' ); ?></th>
                        <td style="text-align:left; border: 1px solid #eee;"><?php printf( __( '%d minutes', 'schedspot' ), $booking->duration ); ?></td>
                    </tr>
                </tbody>
            </table>
            <?php
        }
    }

    /**
     * Get booking payment status.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     * @return string Payment status.
     */
    public function get_booking_payment_status( $booking_id ) {
        global $wpdb;

        $payment = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}schedspot_payments WHERE booking_id = %d ORDER BY payment_date DESC LIMIT 1",
            $booking_id
        ) );

        return $payment ? $payment->status : 'pending';
    }

    /**
     * Get worker earnings for period.
     *
     * @since 1.0.0
     * @param int    $worker_id Worker ID.
     * @param string $start_date Start date (Y-m-d format).
     * @param string $end_date End date (Y-m-d format).
     * @return array Earnings data.
     */
    public function get_worker_earnings( $worker_id, $start_date = null, $end_date = null ) {
        global $wpdb;

        $where_clauses = array( 'b.worker_id = %d', 'p.status = "completed"' );
        $params = array( $worker_id );

        if ( $start_date ) {
            $where_clauses[] = 'b.booking_date >= %s';
            $params[] = $start_date;
        }

        if ( $end_date ) {
            $where_clauses[] = 'b.booking_date <= %s';
            $params[] = $end_date;
        }

        $where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );

        $sql = "SELECT
                    COUNT(*) as total_bookings,
                    SUM(b.total_cost - b.commission_amount) as total_earnings,
                    SUM(b.commission_amount) as total_commission,
                    AVG(b.total_cost - b.commission_amount) as avg_earnings
                FROM {$wpdb->prefix}schedspot_bookings b
                JOIN {$wpdb->prefix}schedspot_payments p ON b.id = p.booking_id
                {$where_sql}";

        $result = $wpdb->get_row( $wpdb->prepare( $sql, $params ) );

        return array(
            'total_bookings'   => absint( $result->total_bookings ),
            'total_earnings'   => floatval( $result->total_earnings ),
            'total_commission' => floatval( $result->total_commission ),
            'avg_earnings'     => floatval( $result->avg_earnings ),
        );
    }

    /**
     * Create payment URL for booking.
     *
     * @since 1.0.0
     * @param int $booking_id Booking ID.
     * @return string Payment URL.
     */
    public function get_booking_payment_url( $booking_id ) {
        $order_id = get_post_meta( $booking_id, 'schedspot_wc_order_id', true );

        if ( ! $order_id ) {
            return '';
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return '';
        }

        return $order->get_checkout_payment_url();
    }
}
