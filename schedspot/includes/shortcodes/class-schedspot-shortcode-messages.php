<?php
/**
 * Messages Shortcode Class
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Shortcode_Messages Class.
 *
 * Handles the messaging interface shortcode functionality.
 *
 * @class SchedSpot_Shortcode_Messages
 * @version 1.0.0
 */
class SchedSpot_Shortcode_Messages {

    /**
     * Render messages shortcode.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Rendered output.
     */
    public static function render( $atts ) {
        $instance = new self();
        return $instance->render_messages( $atts );
    }

    /**
     * Render the messages interface.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Rendered output.
     */
    public function render_messages( $atts ) {
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            return $this->render_login_required();
        }

        // Parse attributes
        $atts = shortcode_atts( array(
            'user_id' => '',
        ), $atts, 'schedspot_messages' );

        $current_user = wp_get_current_user();
        
        // Get conversations for current user
        $conversations = $this->get_user_conversations( $current_user->ID );
        
        // Get target conversation if user_id is specified
        $target_conversation = null;
        if ( $atts['user_id'] ) {
            $target_conversation = $this->get_conversation_with_user( $current_user->ID, intval( $atts['user_id'] ) );
        }

        // Start output buffering
        ob_start();
        
        // Load template
        include SCHEDSPOT_PLUGIN_DIR . 'templates/shortcodes/messages.php';
        
        return ob_get_clean();
    }

    /**
     * Get conversations for user.
     *
     * @since 1.0.0
     * @param int $user_id User ID.
     * @return array Conversations.
     */
    private function get_user_conversations( $user_id ) {
        global $wpdb;

        // Get all unique conversation partners
        $conversation_partners = $wpdb->get_results( $wpdb->prepare(
            "SELECT DISTINCT
                CASE
                    WHEN sender_id = %d THEN receiver_id
                    ELSE sender_id
                END as partner_id
             FROM {$wpdb->prefix}schedspot_messages
             WHERE sender_id = %d OR receiver_id = %d",
            $user_id, $user_id, $user_id
        ) );

        $conversations = array();

        foreach ( $conversation_partners as $partner ) {
            $partner_user = get_user_by( 'ID', $partner->partner_id );
            
            if ( ! $partner_user ) {
                continue;
            }

            // Get last message
            $last_message = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}schedspot_messages 
                 WHERE (sender_id = %d AND recipient_id = %d) OR (sender_id = %d AND recipient_id = %d)
                 ORDER BY created_at DESC 
                 LIMIT 1",
                $user_id, $partner->partner_id, $partner->partner_id, $user_id
            ) );

            // Get unread count
            $unread_count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_messages 
                 WHERE sender_id = %d AND recipient_id = %d AND is_read = 0",
                $partner->partner_id, $user_id
            ) );

            $conversations[] = array(
                'user_id' => $partner->partner_id,
                'name' => $partner_user->display_name,
                'avatar' => get_avatar_url( $partner->partner_id ),
                'last_message' => $last_message ? $this->truncate_message( $last_message->content ) : '',
                'last_message_time' => $last_message ? $last_message->created_at : '',
                'time_ago' => $last_message ? human_time_diff( strtotime( $last_message->created_at ) ) . ' ago' : '',
                'unread_count' => intval( $unread_count ),
            );
        }

        // Sort by last message time
        usort( $conversations, function( $a, $b ) {
            return strtotime( $b['last_message_time'] ) - strtotime( $a['last_message_time'] );
        } );

        return $conversations;
    }

    /**
     * Get conversation with specific user.
     *
     * @since 1.0.0
     * @param int $user_id        Current user ID.
     * @param int $conversation_id Conversation partner ID.
     * @return array|null Conversation data.
     */
    private function get_conversation_with_user( $user_id, $conversation_id ) {
        global $wpdb;

        $partner_user = get_user_by( 'ID', $conversation_id );
        
        if ( ! $partner_user ) {
            return null;
        }

        // Get messages
        $messages = $wpdb->get_results( $wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name
             FROM {$wpdb->prefix}schedspot_messages m
             LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
             WHERE (m.sender_id = %d AND m.receiver_id = %d) OR (m.sender_id = %d AND m.receiver_id = %d)
             ORDER BY m.created_at ASC",
            $user_id, $conversation_id, $conversation_id, $user_id
        ) );

        // Mark messages as read
        $wpdb->update(
            $wpdb->prefix . 'schedspot_messages',
            array( 'read_at' => current_time( 'mysql' ) ),
            array( 'sender_id' => $conversation_id, 'receiver_id' => $user_id, 'read_at' => null ),
            array( '%s' ),
            array( '%d', '%d', '%s' )
        );

        // Format messages
        $formatted_messages = array();
        foreach ( $messages as $message ) {
            $formatted_messages[] = array(
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender_name,
                'sender_avatar' => get_avatar_url( $message->sender_id ),
                'content' => $message->content,
                'attachment_url' => $message->attachment_url,
                'attachment_name' => $message->attachment_name,
                'created_at' => $message->created_at,
                'time_ago' => human_time_diff( strtotime( $message->created_at ) ) . ' ago',
                'is_own' => $message->sender_id == $user_id,
            );
        }

        return array(
            'user' => array(
                'id' => $partner_user->ID,
                'name' => $partner_user->display_name,
                'avatar' => get_avatar_url( $partner_user->ID ),
            ),
            'messages' => $formatted_messages,
        );
    }

    /**
     * Send message.
     *
     * @since 1.0.0
     * @param int    $sender_id    Sender ID.
     * @param int    $recipient_id Recipient ID.
     * @param string $content      Message content.
     * @param string $attachment   Attachment URL.
     * @return int|false Message ID or false on failure.
     */
    public function send_message( $sender_id, $recipient_id, $content, $attachment = '' ) {
        global $wpdb;

        $message_data = array(
            'sender_id' => $sender_id,
            'receiver_id' => $recipient_id,
            'content' => sanitize_textarea_field( $content ),
            'attachment_data' => $attachment ? json_encode( array( 'url' => esc_url_raw( $attachment ), 'name' => basename( $attachment ) ) ) : '',
            'status' => 'sent',
            'created_at' => current_time( 'mysql' ),
        );

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'schedspot_messages',
            $message_data,
            array( '%d', '%d', '%s', '%s', '%s', '%d', '%s' )
        );

        if ( $inserted ) {
            // Send notification
            $this->send_message_notification( $recipient_id, $sender_id, $content );
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Handle file upload.
     *
     * @since 1.0.0
     * @param array $file File data from $_FILES.
     * @return string|false Upload URL or false on failure.
     */
    public function handle_file_upload( $file ) {
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        $upload_overrides = array(
            'test_form' => false,
            'unique_filename_callback' => array( $this, 'generate_unique_filename' ),
        );

        $uploaded_file = wp_handle_upload( $file, $upload_overrides );

        if ( $uploaded_file && ! isset( $uploaded_file['error'] ) ) {
            return $uploaded_file['url'];
        }

        return false;
    }

    /**
     * Generate unique filename for uploads.
     *
     * @since 1.0.0
     * @param string $dir      Upload directory.
     * @param string $name     Original filename.
     * @param string $ext      File extension.
     * @return string Unique filename.
     */
    public function generate_unique_filename( $dir, $name, $ext ) {
        $prefix = 'schedspot-message-' . time() . '-' . wp_generate_password( 8, false );
        return $prefix . $ext;
    }

    /**
     * Send message notification.
     *
     * @since 1.0.0
     * @param int    $recipient_id Recipient ID.
     * @param int    $sender_id    Sender ID.
     * @param string $content      Message content.
     */
    private function send_message_notification( $recipient_id, $sender_id, $content ) {
        $recipient = get_user_by( 'ID', $recipient_id );
        $sender = get_user_by( 'ID', $sender_id );

        if ( ! $recipient || ! $sender ) {
            return;
        }

        // Check if user wants email notifications
        $email_notifications = get_user_meta( $recipient_id, 'schedspot_email_notifications', true );
        
        if ( $email_notifications !== '0' ) {
            $subject = sprintf( __( 'New message from %s', 'schedspot' ), $sender->display_name );
            $message = sprintf(
                __( 'You have received a new message from %s:

"%s"

Reply to this message by visiting your dashboard.', 'schedspot' ),
                $sender->display_name,
                wp_trim_words( $content, 20 )
            );

            wp_mail( $recipient->user_email, $subject, $message );
        }
    }

    /**
     * Truncate message for preview.
     *
     * @since 1.0.0
     * @param string $message Message content.
     * @param int    $length  Maximum length.
     * @return string Truncated message.
     */
    private function truncate_message( $message, $length = 50 ) {
        if ( strlen( $message ) <= $length ) {
            return $message;
        }

        return substr( $message, 0, $length ) . '...';
    }

    /**
     * Render login required message.
     *
     * @since 1.0.0
     * @return string Login required HTML.
     */
    private function render_login_required() {
        ob_start();
        ?>
        <div class="schedspot-login-required">
            <h3><?php _e( 'Login Required', 'schedspot' ); ?></h3>
            <p><?php _e( 'Please log in to access your messages.', 'schedspot' ); ?></p>
            <a href="<?php echo wp_login_url( get_permalink() ); ?>" class="schedspot-btn schedspot-btn-primary">
                <?php _e( 'Login', 'schedspot' ); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get message statistics for user.
     *
     * @since 1.0.0
     * @param int $user_id User ID.
     * @return array Message statistics.
     */
    public function get_message_stats( $user_id ) {
        global $wpdb;

        return array(
            'total_conversations' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(DISTINCT
                    CASE
                        WHEN sender_id = %d THEN receiver_id
                        ELSE sender_id
                    END)
                 FROM {$wpdb->prefix}schedspot_messages
                 WHERE sender_id = %d OR receiver_id = %d",
                $user_id, $user_id, $user_id
            ) ),
            'unread_messages' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_messages
                 WHERE receiver_id = %d AND read_at IS NULL",
                $user_id
            ) ),
            'total_sent' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_messages
                 WHERE sender_id = %d",
                $user_id
            ) ),
            'total_received' => $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}schedspot_messages
                 WHERE receiver_id = %d",
                $user_id
            ) ),
        );
    }

    /**
     * Delete conversation.
     *
     * @since 1.0.0
     * @param int $user_id    Current user ID.
     * @param int $partner_id Conversation partner ID.
     * @return bool Success status.
     */
    public function delete_conversation( $user_id, $partner_id ) {
        global $wpdb;

        // Only delete messages where current user is sender or recipient
        $deleted = $wpdb->delete(
            $wpdb->prefix . 'schedspot_messages',
            array(
                'sender_id' => $user_id,
                'recipient_id' => $partner_id,
            ),
            array( '%d', '%d' )
        );

        $deleted += $wpdb->delete(
            $wpdb->prefix . 'schedspot_messages',
            array(
                'sender_id' => $partner_id,
                'recipient_id' => $user_id,
            ),
            array( '%d', '%d' )
        );

        return $deleted > 0;
    }

    /**
     * Search messages.
     *
     * @since 1.0.0
     * @param int    $user_id User ID.
     * @param string $query   Search query.
     * @return array Search results.
     */
    public function search_messages( $user_id, $query ) {
        global $wpdb;

        $search_term = '%' . $wpdb->esc_like( $query ) . '%';

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name
             FROM {$wpdb->prefix}schedspot_messages m
             LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
             WHERE (m.sender_id = %d OR m.receiver_id = %d)
             AND m.content LIKE %s
             ORDER BY m.created_at DESC
             LIMIT 50",
            $user_id, $user_id, $search_term
        ) );
    }
}
