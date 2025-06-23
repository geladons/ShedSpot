<?php
/**
 * SchedSpot Messaging Controller Class
 *
 * @package SchedSpot
 * @version 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Messaging Class.
 *
 * @class SchedSpot_Messaging
 * @version 2.0.0
 */
class SchedSpot_Messaging {

    /**
     * Constructor.
     *
     * @since 2.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize messaging functionality.
     *
     * @since 2.0.0
     */
    public function init() {
        // Check if messaging is enabled
        if ( ! $this->is_enabled() ) {
            return;
        }

        // Initialize hooks
        add_action( 'wp_ajax_schedspot_send_message', array( $this, 'ajax_send_message' ) );
        add_action( 'wp_ajax_schedspot_get_messages', array( $this, 'ajax_get_messages' ) );
        add_action( 'wp_ajax_schedspot_get_conversations', array( $this, 'ajax_get_conversations' ) );
        add_action( 'wp_ajax_schedspot_mark_messages_read', array( $this, 'ajax_mark_messages_read' ) );
        add_action( 'wp_ajax_schedspot_upload_attachment', array( $this, 'ajax_upload_attachment' ) );

        // Message notification hooks
        add_action( 'schedspot_message_sent', array( $this, 'handle_message_notifications' ), 10, 4 );

        // Enqueue scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Add messaging capabilities to roles
        add_action( 'init', array( $this, 'add_messaging_capabilities' ) );

        // Add messaging to user profiles
        add_action( 'show_user_profile', array( $this, 'add_user_messaging_section' ) );
        add_action( 'edit_user_profile', array( $this, 'add_user_messaging_section' ) );
    }

    /**
     * Check if messaging is enabled.
     *
     * @since 2.0.0
     * @return bool True if enabled, false otherwise.
     */
    public function is_enabled() {
        return get_option( 'schedspot_enable_messaging', true );
    }

    /**
     * Add messaging capabilities to user roles.
     *
     * @since 2.0.0
     */
    public function add_messaging_capabilities() {
        $customer_role = get_role( 'schedspot_customer' );
        if ( $customer_role ) {
            $customer_role->add_cap( 'schedspot_send_messages' );
            $customer_role->add_cap( 'schedspot_read_messages' );
        }

        $worker_role = get_role( 'schedspot_worker' );
        if ( $worker_role ) {
            $worker_role->add_cap( 'schedspot_send_messages' );
            $worker_role->add_cap( 'schedspot_read_messages' );
        }

        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            $admin_role->add_cap( 'schedspot_send_messages' );
            $admin_role->add_cap( 'schedspot_read_messages' );
            $admin_role->add_cap( 'schedspot_moderate_messages' );
        }
    }

    /**
     * Send a message.
     *
     * @since 2.0.0
     * @param array $message_data Message data.
     * @return int|WP_Error Message ID on success, WP_Error on failure.
     */
    public function send_message( $message_data ) {
        // Validate permissions
        if ( ! current_user_can( 'schedspot_send_messages' ) ) {
            return new WP_Error( 'permission_denied', __( 'You do not have permission to send messages.', 'schedspot' ) );
        }

        // Validate that users can message each other
        $can_message = $this->can_users_message( $message_data['sender_id'], $message_data['receiver_id'] );
        if ( is_wp_error( $can_message ) ) {
            return $can_message;
        }

        // Create message using static method
        return SchedSpot_Message::create_message( $message_data );
    }

    /**
     * Check if two users can message each other.
     *
     * @since 2.0.0
     * @param int $user1_id First user ID.
     * @param int $user2_id Second user ID.
     * @return bool|WP_Error True if they can message, WP_Error otherwise.
     */
    public function can_users_message( $user1_id, $user2_id ) {
        // Admin can message anyone
        if ( current_user_can( 'schedspot_moderate_messages' ) ) {
            return true;
        }

        // Users can only message if they have a booking relationship
        if ( $this->users_have_booking_relationship( $user1_id, $user2_id ) ) {
            return true;
        }

        return new WP_Error( 'no_relationship', __( 'You can only message users you have bookings with.', 'schedspot' ) );
    }

    /**
     * Check if users have a booking relationship.
     *
     * @since 2.0.0
     * @param int $user1_id First user ID.
     * @param int $user2_id Second user ID.
     * @return bool True if they have a booking relationship.
     */
    private function users_have_booking_relationship( $user1_id, $user2_id ) {
        global $wpdb;

        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_bookings 
             WHERE (user_id = %d AND worker_id = %d) OR (user_id = %d AND worker_id = %d)",
            $user1_id,
            $user2_id,
            $user2_id,
            $user1_id
        ) );

        return $count > 0;
    }

    /**
     * Get conversation between two users.
     *
     * @since 2.0.0
     * @param int   $user1_id First user ID.
     * @param int   $user2_id Second user ID.
     * @param array $args Query arguments.
     * @return array Array of formatted message data.
     */
    public function get_conversation( $user1_id, $user2_id, $args = array() ) {
        // Validate permissions
        $current_user_id = get_current_user_id();
        if ( ! current_user_can( 'schedspot_moderate_messages' ) && 
             $current_user_id !== $user1_id && $current_user_id !== $user2_id ) {
            return new WP_Error( 'permission_denied', __( 'You do not have permission to view this conversation.', 'schedspot' ) );
        }

        $messages = SchedSpot_Message::get_conversation( $user1_id, $user2_id, $args );
        $formatted_messages = array();

        foreach ( $messages as $message ) {
            $formatted_messages[] = $message->get_formatted_data();
        }

        return $formatted_messages;
    }

    /**
     * Get user conversations.
     *
     * @since 2.0.0
     * @param int   $user_id User ID.
     * @param array $args Query arguments.
     * @return array Array of conversation data.
     */
    public function get_user_conversations( $user_id, $args = array() ) {
        // Validate permissions
        $current_user_id = get_current_user_id();
        if ( ! current_user_can( 'schedspot_moderate_messages' ) && $current_user_id !== $user_id ) {
            return new WP_Error( 'permission_denied', __( 'You do not have permission to view these conversations.', 'schedspot' ) );
        }

        return SchedSpot_Message::get_user_conversations( $user_id, $args );
    }

    /**
     * Mark messages as read.
     *
     * @since 2.0.0
     * @param array $message_ids Array of message IDs.
     * @return bool True on success, false on failure.
     */
    public function mark_messages_read( $message_ids ) {
        $current_user_id = get_current_user_id();
        $success_count = 0;

        foreach ( $message_ids as $message_id ) {
            $message = new SchedSpot_Message( $message_id );
            
            // Only allow marking own received messages as read
            if ( $message->receiver_id === $current_user_id || current_user_can( 'schedspot_moderate_messages' ) ) {
                if ( $message->mark_as_read() ) {
                    $success_count++;
                }
            }
        }

        return $success_count > 0;
    }

    /**
     * Handle file upload for message attachments.
     *
     * @since 2.0.0
     * @param array $file_data File upload data.
     * @return array|WP_Error Attachment data on success, WP_Error on failure.
     */
    public function handle_file_upload( $file_data ) {
        // Validate file type and size
        $allowed_types = array( 'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt' );
        $max_size = 5 * 1024 * 1024; // 5MB

        $file_extension = strtolower( pathinfo( $file_data['name'], PATHINFO_EXTENSION ) );
        
        if ( ! in_array( $file_extension, $allowed_types ) ) {
            return new WP_Error( 'invalid_file_type', __( 'File type not allowed.', 'schedspot' ) );
        }

        if ( $file_data['size'] > $max_size ) {
            return new WP_Error( 'file_too_large', __( 'File size exceeds 5MB limit.', 'schedspot' ) );
        }

        // Handle upload
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        $upload_overrides = array( 'test_form' => false );
        $uploaded_file = wp_handle_upload( $file_data, $upload_overrides );

        if ( isset( $uploaded_file['error'] ) ) {
            return new WP_Error( 'upload_failed', $uploaded_file['error'] );
        }

        return array(
            'file_name' => basename( $uploaded_file['file'] ),
            'file_path' => $uploaded_file['file'],
            'file_url'  => $uploaded_file['url'],
            'file_type' => $uploaded_file['type'],
            'file_size' => filesize( $uploaded_file['file'] ),
        );
    }

    /**
     * Handle message notifications.
     *
     * @since 2.0.0
     * @param int $message_id Message ID.
     * @param int $sender_id Sender user ID.
     * @param int $receiver_id Receiver user ID.
     * @param int $booking_id Booking ID (optional).
     */
    public function handle_message_notifications( $message_id, $sender_id, $receiver_id, $booking_id = 0 ) {
        $message = new SchedSpot_Message( $message_id );
        $sender = get_userdata( $sender_id );
        $receiver = get_userdata( $receiver_id );

        if ( ! $sender || ! $receiver ) {
            return;
        }

        // Send email notification
        $this->send_email_notification( $message, $sender, $receiver );

        // Send SMS notification if enabled
        if ( class_exists( 'SchedSpot_SMS' ) ) {
            $sms = new SchedSpot_SMS();
            if ( $sms->is_enabled() ) {
                $this->send_sms_notification( $message, $sender, $receiver );
            }
        }

        // Fire action hook for custom notifications
        do_action( 'schedspot_message_notification_sent', $message_id, $sender_id, $receiver_id, $booking_id );
    }

    /**
     * Send email notification for new message.
     *
     * @since 2.0.0
     * @param SchedSpot_Message $message Message object.
     * @param WP_User           $sender Sender user object.
     * @param WP_User           $receiver Receiver user object.
     */
    private function send_email_notification( $message, $sender, $receiver ) {
        if ( ! get_option( 'schedspot_email_message_notifications', true ) ) {
            return;
        }

        $subject = sprintf(
            __( 'New message from %s - %s', 'schedspot' ),
            $sender->display_name,
            get_bloginfo( 'name' )
        );

        $message_content = wp_trim_words( $message->content, 20 );
        $dashboard_url = home_url( '/dashboard/' ); // Adjust based on your setup

        $body = sprintf(
            __( "Hi %s,\n\nYou have received a new message from %s:\n\n\"%s\"\n\nTo view and reply to this message, please visit your dashboard:\n%s\n\nBest regards,\n%s Team", 'schedspot' ),
            $receiver->display_name,
            $sender->display_name,
            $message_content,
            $dashboard_url,
            get_bloginfo( 'name' )
        );

        wp_mail( $receiver->user_email, $subject, $body );
    }

    /**
     * Send SMS notification for new message.
     *
     * @since 2.0.0
     * @param SchedSpot_Message $message Message object.
     * @param WP_User           $sender Sender user object.
     * @param WP_User           $receiver Receiver user object.
     */
    private function send_sms_notification( $message, $sender, $receiver ) {
        if ( ! get_option( 'schedspot_sms_message_notifications', true ) ) {
            return;
        }

        $phone = get_user_meta( $receiver->ID, 'schedspot_phone', true );
        if ( ! $phone ) {
            return;
        }

        $sms_message = sprintf(
            __( 'New message from %s: "%s" - Reply at %s', 'schedspot' ),
            $sender->display_name,
            wp_trim_words( $message->content, 10 ),
            home_url( '/dashboard/' )
        );

        $sms = new SchedSpot_SMS();
        $sms->send_sms( $phone, $sms_message );
    }

    /**
     * AJAX handler for sending messages.
     *
     * @since 2.0.0
     */
    public function ajax_send_message() {
        check_ajax_referer( 'schedspot_messaging_nonce', 'nonce' );

        if ( ! current_user_can( 'schedspot_send_messages' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'schedspot' ) ) );
        }

        $message_data = array(
            'sender_id'   => get_current_user_id(),
            'receiver_id' => absint( $_POST['receiver_id'] ?? 0 ),
            'content'     => sanitize_textarea_field( $_POST['content'] ?? '' ),
            'booking_id'  => absint( $_POST['booking_id'] ?? 0 ),
        );

        // Handle file attachment if present
        if ( ! empty( $_FILES['attachment'] ) ) {
            $attachment = $this->handle_file_upload( $_FILES['attachment'] );
            if ( is_wp_error( $attachment ) ) {
                wp_send_json_error( array( 'message' => $attachment->get_error_message() ) );
            }
            $message_data['message_type'] = 'file';
            $message_data['attachment_data'] = array( $attachment );
        }

        $result = $this->send_message( $message_data );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        $message = new SchedSpot_Message( $result );
        wp_send_json_success( array(
            'message' => __( 'Message sent successfully.', 'schedspot' ),
            'data'    => $message->get_formatted_data(),
        ) );
    }

    /**
     * AJAX handler for getting messages.
     *
     * @since 2.0.0
     */
    public function ajax_get_messages() {
        check_ajax_referer( 'schedspot_messaging_nonce', 'nonce' );

        if ( ! current_user_can( 'schedspot_read_messages' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'schedspot' ) ) );
        }

        $user1_id = get_current_user_id();
        $user2_id = absint( $_POST['other_user_id'] ?? 0 );
        $booking_id = absint( $_POST['booking_id'] ?? 0 );

        $args = array(
            'limit'      => absint( $_POST['limit'] ?? 50 ),
            'offset'     => absint( $_POST['offset'] ?? 0 ),
            'order'      => sanitize_text_field( $_POST['order'] ?? 'ASC' ),
            'booking_id' => $booking_id,
        );

        $messages = $this->get_conversation( $user1_id, $user2_id, $args );

        if ( is_wp_error( $messages ) ) {
            wp_send_json_error( array( 'message' => $messages->get_error_message() ) );
        }

        wp_send_json_success( array( 'messages' => $messages ) );
    }

    /**
     * AJAX handler for getting conversations.
     *
     * @since 2.0.0
     */
    public function ajax_get_conversations() {
        check_ajax_referer( 'schedspot_messaging_nonce', 'nonce' );

        if ( ! current_user_can( 'schedspot_read_messages' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'schedspot' ) ) );
        }

        $user_id = get_current_user_id();
        $args = array(
            'limit'  => absint( $_POST['limit'] ?? 20 ),
            'offset' => absint( $_POST['offset'] ?? 0 ),
        );

        $conversations = $this->get_user_conversations( $user_id, $args );

        if ( is_wp_error( $conversations ) ) {
            wp_send_json_error( array( 'message' => $conversations->get_error_message() ) );
        }

        wp_send_json_success( array( 'conversations' => $conversations ) );
    }

    /**
     * AJAX handler for marking messages as read.
     *
     * @since 2.0.0
     */
    public function ajax_mark_messages_read() {
        check_ajax_referer( 'schedspot_messaging_nonce', 'nonce' );

        if ( ! current_user_can( 'schedspot_read_messages' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'schedspot' ) ) );
        }

        $message_ids = array_map( 'absint', $_POST['message_ids'] ?? array() );

        if ( empty( $message_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'No message IDs provided.', 'schedspot' ) ) );
        }

        $result = $this->mark_messages_read( $message_ids );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Messages marked as read.', 'schedspot' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to mark messages as read.', 'schedspot' ) ) );
        }
    }

    /**
     * AJAX handler for uploading attachments.
     *
     * @since 2.0.0
     */
    public function ajax_upload_attachment() {
        check_ajax_referer( 'schedspot_messaging_nonce', 'nonce' );

        if ( ! current_user_can( 'schedspot_send_messages' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'schedspot' ) ) );
        }

        if ( empty( $_FILES['file'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'schedspot' ) ) );
        }

        $attachment = $this->handle_file_upload( $_FILES['file'] );

        if ( is_wp_error( $attachment ) ) {
            wp_send_json_error( array( 'message' => $attachment->get_error_message() ) );
        }

        wp_send_json_success( array(
            'message'    => __( 'File uploaded successfully.', 'schedspot' ),
            'attachment' => $attachment,
        ) );
    }

    /**
     * Enqueue frontend scripts and styles.
     *
     * @since 2.0.0
     */
    public function enqueue_frontend_scripts() {
        if ( ! $this->is_enabled() || ! is_user_logged_in() ) {
            return;
        }

        // Only enqueue on pages with messaging functionality
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) ) {
            return;
        }

        $has_messaging = false;
        $shortcodes = array( 'schedspot_dashboard', 'schedspot_messages' );

        foreach ( $shortcodes as $shortcode ) {
            if ( has_shortcode( $post->post_content, $shortcode ) ) {
                $has_messaging = true;
                break;
            }
        }

        if ( ! $has_messaging ) {
            return;
        }

        // Enqueue messaging script
        wp_enqueue_script(
            'schedspot-messaging',
            SCHEDSPOT_PLUGIN_URL . 'public/js/messaging.js',
            array( 'jquery' ),
            SCHEDSPOT_VERSION,
            true
        );

        wp_localize_script( 'schedspot-messaging', 'schedspot_messaging', array(
            'ajax_url'     => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'schedspot_messaging_nonce' ),
            'current_user' => get_current_user_id(),
            'strings'      => array(
                'send_message'       => __( 'Send Message', 'schedspot' ),
                'type_message'       => __( 'Type your message...', 'schedspot' ),
                'attach_file'        => __( 'Attach File', 'schedspot' ),
                'sending'            => __( 'Sending...', 'schedspot' ),
                'message_sent'       => __( 'Message sent!', 'schedspot' ),
                'send_failed'        => __( 'Failed to send message.', 'schedspot' ),
                'loading_messages'   => __( 'Loading messages...', 'schedspot' ),
                'no_messages'        => __( 'No messages yet.', 'schedspot' ),
                'file_too_large'     => __( 'File size exceeds 5MB limit.', 'schedspot' ),
                'invalid_file_type'  => __( 'File type not allowed.', 'schedspot' ),
                'confirm_delete'     => __( 'Are you sure you want to delete this message?', 'schedspot' ),
            ),
        ) );

        // Enqueue messaging styles from CSS file
        wp_enqueue_style( 'schedspot-frontend-enhanced', SCHEDSPOT_PLUGIN_URL . 'assets/css/frontend-enhanced.css', array(), SCHEDSPOT_VERSION );
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @since 2.0.0
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( ! $this->is_enabled() ) {
            return;
        }

        // Only load on SchedSpot admin pages
        if ( strpos( $hook, 'schedspot' ) === false ) {
            return;
        }

        // Enqueue admin messaging script
        wp_enqueue_script(
            'schedspot-admin-messaging',
            SCHEDSPOT_PLUGIN_URL . 'admin/js/messaging.js',
            array( 'jquery' ),
            SCHEDSPOT_VERSION,
            true
        );

        wp_localize_script( 'schedspot-admin-messaging', 'schedspot_admin_messaging', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'schedspot_messaging_nonce' ),
            'strings'  => array(
                'moderate_messages' => __( 'Moderate Messages', 'schedspot' ),
                'view_conversation' => __( 'View Conversation', 'schedspot' ),
                'delete_message'    => __( 'Delete Message', 'schedspot' ),
                'confirm_delete'    => __( 'Are you sure you want to delete this message?', 'schedspot' ),
            ),
        ) );
    }

    /**
     * Add messaging section to user profiles.
     *
     * @since 2.0.0
     * @param WP_User $user User object.
     */
    public function add_user_messaging_section( $user ) {
        if ( ! current_user_can( 'schedspot_moderate_messages' ) ) {
            return;
        }

        $conversations = $this->get_user_conversations( $user->ID, array( 'limit' => 5 ) );

        ?>
        <h3><?php _e( 'SchedSpot Messages', 'schedspot' ); ?></h3>
        <table class="form-table">
            <tr>
                <th><label><?php _e( 'Recent Conversations', 'schedspot' ); ?></label></th>
                <td>
                    <?php if ( ! empty( $conversations ) && ! is_wp_error( $conversations ) ) : ?>
                        <ul>
                            <?php foreach ( $conversations as $conversation ) : ?>
                                <li>
                                    <strong><?php echo esc_html( $conversation['other_user_name'] ); ?></strong>
                                    - <?php echo esc_html( $conversation['time_ago'] ); ?> ago
                                    <?php if ( $conversation['unread_count'] > 0 ) : ?>
                                        <span class="unread-count">(<?php echo esc_html( $conversation['unread_count'] ); ?> unread)</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p><?php _e( 'No conversations found.', 'schedspot' ); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Get messaging styles.
     *
     * @since 2.0.0
     * @return string CSS styles.
     */
    private function get_messaging_styles() {
        return "
        .schedspot-messaging {
            max-width: 800px;
            margin: 20px auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .schedspot-conversations {
            width: 300px;
            float: left;
            border-right: 1px solid #ddd;
            height: 500px;
            overflow-y: auto;
            background: #f9f9f9;
        }

        .schedspot-conversation-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .schedspot-conversation-item:hover {
            background: #f0f0f0;
        }

        .schedspot-conversation-item.active {
            background: #0073aa;
            color: white;
        }

        .schedspot-conversation-item .user-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .schedspot-conversation-item .last-message {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .schedspot-conversation-item .time-ago {
            font-size: 11px;
            color: #999;
        }

        .schedspot-conversation-item .unread-count {
            background: #e74c3c;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            float: right;
        }

        .schedspot-chat-area {
            margin-left: 300px;
            height: 500px;
            display: flex;
            flex-direction: column;
        }

        .schedspot-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: white;
        }

        .schedspot-message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }

        .schedspot-message.own {
            flex-direction: row-reverse;
        }

        .schedspot-message .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin: 0 10px;
        }

        .schedspot-message .content {
            max-width: 70%;
            background: #f1f1f1;
            padding: 10px 15px;
            border-radius: 18px;
            position: relative;
        }

        .schedspot-message.own .content {
            background: #0073aa;
            color: white;
        }

        .schedspot-message .time {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
        }

        .schedspot-message-form {
            padding: 20px;
            border-top: 1px solid #ddd;
            background: #f9f9f9;
        }

        .schedspot-message-input {
            display: flex;
            gap: 10px;
        }

        .schedspot-message-input textarea {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 20px;
            resize: none;
            min-height: 40px;
        }

        .schedspot-message-input button {
            padding: 10px 20px;
            background: #0073aa;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
        }

        .schedspot-message-input button:hover {
            background: #005a87;
        }

        .schedspot-attachment {
            display: inline-block;
            padding: 5px 10px;
            background: #e1e1e1;
            border-radius: 10px;
            margin-top: 5px;
            text-decoration: none;
            color: #333;
        }

        .schedspot-attachment:hover {
            background: #d1d1d1;
        }

        @media (max-width: 768px) {
            .schedspot-messaging {
                margin: 10px;
            }

            .schedspot-conversations {
                width: 100%;
                float: none;
                height: 200px;
            }

            .schedspot-chat-area {
                margin-left: 0;
                height: 400px;
            }
        }
        ";
    }

    /**
     * Get unread message count for user.
     *
     * @since 2.0.0
     * @param int $user_id User ID.
     * @return int Unread message count.
     */
    public function get_unread_count( $user_id ) {
        global $wpdb;

        return absint( $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_messages
             WHERE receiver_id = %d AND status != 'read'",
            $user_id
        ) ) );
    }

    /**
     * Create system message.
     *
     * @since 2.0.0
     * @param int    $receiver_id Receiver user ID.
     * @param string $content Message content.
     * @param int    $booking_id Related booking ID.
     * @return int|WP_Error Message ID on success, WP_Error on failure.
     */
    public function create_system_message( $receiver_id, $content, $booking_id = 0 ) {
        $message_data = array(
            'sender_id'    => 0, // System messages have sender_id = 0
            'receiver_id'  => $receiver_id,
            'content'      => $content,
            'message_type' => 'system',
            'booking_id'   => $booking_id,
        );

        return SchedSpot_Message::create_message( $message_data );
    }
}
