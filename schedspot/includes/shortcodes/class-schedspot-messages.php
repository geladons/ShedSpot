<?php
/**
 * SchedSpot Messages Shortcode
 *
 * Handles the messaging interface shortcode functionality
 *
 * @package SchedSpot
 * @version 1.6.1
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Messages Class.
 *
 * @class SchedSpot_Messages
 * @version 1.6.1
 */
class SchedSpot_Messages {

    /**
     * Constructor.
     *
     * @since 1.6.1
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize messages functionality.
     *
     * @since 1.6.1
     */
    public function init() {
        add_shortcode( 'schedspot_messages', array( $this, 'render_messages' ) );
        add_action( 'wp_ajax_schedspot_get_conversations', array( $this, 'get_conversations' ) );
        add_action( 'wp_ajax_schedspot_get_conversation_messages', array( $this, 'get_conversation_messages' ) );
        add_action( 'wp_ajax_schedspot_send_message', array( $this, 'send_message' ) );
        add_action( 'wp_ajax_schedspot_mark_messages_read', array( $this, 'mark_messages_read' ) );
    }

    /**
     * Render messages shortcode.
     *
     * @since 1.6.1
     * @param array $atts Shortcode attributes.
     * @return string Messages interface HTML.
     */
    public function render_messages( $atts ) {
        if ( ! is_user_logged_in() ) {
            return $this->render_login_prompt();
        }

        $atts = shortcode_atts( array(
            'conversation_id' => '',
            'show_conversation_list' => 'true',
            'height' => '600px',
        ), $atts );

        ob_start();
        ?>
        <div class="schedspot-messaging" style="height: <?php echo esc_attr( $atts['height'] ); ?>;">
            <?php if ( $atts['show_conversation_list'] === 'true' ) : ?>
                <div class="conversations-list">
                    <div class="conversations-header">
                        <h3><?php _e( 'Messages', 'schedspot' ); ?></h3>
                        <button class="new-conversation-btn" title="<?php _e( 'Start New Conversation', 'schedspot' ); ?>">
                            <span class="dashicons dashicons-plus"></span>
                        </button>
                    </div>
                    <div class="conversations-container">
                        <div class="conversations-loading">
                            <span class="dashicons dashicons-update spin"></span>
                            <?php _e( 'Loading conversations...', 'schedspot' ); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="chat-area <?php echo $atts['conversation_id'] ? 'has-conversation' : 'no-conversation'; ?>">
                <?php if ( $atts['conversation_id'] ) : ?>
                    <?php $this->render_conversation( $atts['conversation_id'] ); ?>
                <?php else : ?>
                    <div class="no-conversation-selected">
                        <div class="no-conversation-content">
                            <span class="dashicons dashicons-email-alt"></span>
                            <h3><?php _e( 'Select a Conversation', 'schedspot' ); ?></h3>
                            <p><?php _e( 'Choose a conversation from the list to start messaging.', 'schedspot' ); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- New Conversation Modal -->
        <div id="new-conversation-modal" class="schedspot-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><?php _e( 'Start New Conversation', 'schedspot' ); ?></h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="new-conversation-form">
                        <div class="form-row">
                            <label for="recipient-search"><?php _e( 'Send message to:', 'schedspot' ); ?></label>
                            <input type="text" id="recipient-search" placeholder="<?php _e( 'Search for users...', 'schedspot' ); ?>">
                            <div id="recipient-suggestions"></div>
                        </div>
                        <div class="form-row">
                            <label for="initial-message"><?php _e( 'Message:', 'schedspot' ); ?></label>
                            <textarea id="initial-message" rows="4" placeholder="<?php _e( 'Type your message...', 'schedspot' ); ?>" required></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="button cancel-btn"><?php _e( 'Cancel', 'schedspot' ); ?></button>
                            <button type="submit" class="button button-primary"><?php _e( 'Send Message', 'schedspot' ); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render login prompt for non-logged-in users.
     *
     * @since 1.6.1
     * @return string Login prompt HTML.
     */
    private function render_login_prompt() {
        ob_start();
        ?>
        <div class="schedspot-login-prompt">
            <div class="login-prompt-content">
                <span class="dashicons dashicons-email-alt"></span>
                <h3><?php _e( 'Please Log In', 'schedspot' ); ?></h3>
                <p><?php _e( 'You need to be logged in to access your messages.', 'schedspot' ); ?></p>
                <div class="login-prompt-actions">
                    <a href="<?php echo wp_login_url( get_permalink() ); ?>" class="schedspot-btn schedspot-btn-primary">
                        <?php _e( 'Log In', 'schedspot' ); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render specific conversation.
     *
     * @since 1.6.1
     * @param int $conversation_id Conversation ID.
     */
    private function render_conversation( $conversation_id ) {
        $conversation = $this->get_conversation_details( $conversation_id );
        
        if ( ! $conversation ) {
            echo '<div class="conversation-error">' . __( 'Conversation not found.', 'schedspot' ) . '</div>';
            return;
        }
        ?>
        <div class="chat-header">
            <div class="chat-user-info">
                <img src="<?php echo esc_url( $conversation['user_avatar'] ); ?>" alt="<?php echo esc_attr( $conversation['user_name'] ); ?>" class="chat-user-avatar">
                <div class="chat-user-details">
                    <div class="chat-user-name"><?php echo esc_html( $conversation['user_name'] ); ?></div>
                    <div class="chat-user-status"><?php echo esc_html( $conversation['user_status'] ); ?></div>
                </div>
            </div>
            <div class="chat-actions">
                <button class="chat-action-btn" title="<?php _e( 'Call', 'schedspot' ); ?>">
                    <span class="dashicons dashicons-phone"></span>
                </button>
                <button class="chat-action-btn" title="<?php _e( 'Video Call', 'schedspot' ); ?>">
                    <span class="dashicons dashicons-video-alt3"></span>
                </button>
                <button class="chat-action-btn" title="<?php _e( 'More Options', 'schedspot' ); ?>">
                    <span class="dashicons dashicons-ellipsis"></span>
                </button>
            </div>
        </div>

        <div class="messages-container" data-conversation-id="<?php echo esc_attr( $conversation_id ); ?>">
            <div class="messages-loading">
                <span class="dashicons dashicons-update spin"></span>
                <?php _e( 'Loading messages...', 'schedspot' ); ?>
            </div>
        </div>

        <div class="message-input-area">
            <div class="file-upload-area">
                <div class="file-upload-text">
                    <span class="dashicons dashicons-paperclip"></span>
                    <?php _e( 'Drop files here or click to upload', 'schedspot' ); ?>
                </div>
                <input type="file" class="file-input" multiple accept="image/*,.pdf,.doc,.docx,.txt">
            </div>
            
            <div class="message-input-container">
                <textarea class="message-input" placeholder="<?php _e( 'Type your message...', 'schedspot' ); ?>" rows="1"></textarea>
                <div class="message-actions">
                    <button class="message-action-btn attach-file-btn" title="<?php _e( 'Attach File', 'schedspot' ); ?>">
                        <span class="dashicons dashicons-paperclip"></span>
                    </button>
                    <button class="message-action-btn emoji-btn" title="<?php _e( 'Add Emoji', 'schedspot' ); ?>">
                        <span class="dashicons dashicons-smiley"></span>
                    </button>
                    <button class="message-action-btn send-message-btn" title="<?php _e( 'Send Message', 'schedspot' ); ?>">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get conversations via AJAX.
     *
     * @since 1.6.1
     */
    public function get_conversations() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'schedspot' ) ) );
        }

        $user_id = get_current_user_id();
        $conversations = $this->fetch_user_conversations( $user_id );

        wp_send_json_success( array(
            'conversations' => $conversations,
            'total' => count( $conversations )
        ) );
    }

    /**
     * Get conversation messages via AJAX.
     *
     * @since 1.6.1
     */
    public function get_conversation_messages() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'schedspot' ) ) );
        }

        $conversation_id = absint( $_GET['conversation_id'] );
        $page = absint( $_GET['page'] ) ?: 1;
        $per_page = 50;

        if ( ! $conversation_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid conversation ID.', 'schedspot' ) ) );
        }

        $messages = $this->fetch_conversation_messages( $conversation_id, $page, $per_page );
        $conversation = $this->get_conversation_details( $conversation_id );

        wp_send_json_success( array(
            'messages' => $messages,
            'conversation' => $conversation,
            'has_more' => count( $messages ) === $per_page
        ) );
    }

    /**
     * Send message via AJAX.
     *
     * @since 1.6.1
     */
    public function send_message() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'schedspot' ) ) );
        }

        $sender_id = get_current_user_id();
        $recipient_id = absint( $_POST['recipient_id'] );
        $content = sanitize_textarea_field( $_POST['content'] );
        $conversation_id = absint( $_POST['conversation_id'] );

        if ( ! $recipient_id || ! $content ) {
            wp_send_json_error( array( 'message' => __( 'Missing required fields.', 'schedspot' ) ) );
        }

        // Handle file attachments
        $attachments = array();
        if ( ! empty( $_FILES['attachments'] ) ) {
            $attachments = $this->handle_file_uploads( $_FILES['attachments'] );
        }

        $message_data = array(
            'sender_id' => $sender_id,
            'recipient_id' => $recipient_id,
            'conversation_id' => $conversation_id,
            'content' => $content,
            'attachments' => $attachments,
            'created_at' => current_time( 'mysql' ),
        );

        $message_id = $this->save_message( $message_data );

        if ( $message_id ) {
            // Send notification
            $this->send_message_notification( $message_id );

            wp_send_json_success( array(
                'message' => __( 'Message sent successfully.', 'schedspot' ),
                'message_id' => $message_id,
                'message_data' => $this->format_message_for_display( $message_data )
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to send message.', 'schedspot' ) ) );
        }
    }

    /**
     * Mark messages as read via AJAX.
     *
     * @since 1.6.1
     */
    public function mark_messages_read() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'schedspot' ) ) );
        }

        $conversation_id = absint( $_POST['conversation_id'] );
        $user_id = get_current_user_id();

        if ( ! $conversation_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid conversation ID.', 'schedspot' ) ) );
        }

        $marked = $this->mark_conversation_messages_read( $conversation_id, $user_id );

        if ( $marked ) {
            wp_send_json_success( array( 'message' => __( 'Messages marked as read.', 'schedspot' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to mark messages as read.', 'schedspot' ) ) );
        }
    }

    /**
     * Handle file uploads for message attachments.
     *
     * @since 1.6.1
     * @param array $files Uploaded files array.
     * @return array Processed attachments.
     */
    private function handle_file_uploads( $files ) {
        $attachments = array();
        $allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain' );
        $max_file_size = 5 * 1024 * 1024; // 5MB

        if ( ! is_array( $files['name'] ) ) {
            $files = array(
                'name' => array( $files['name'] ),
                'type' => array( $files['type'] ),
                'tmp_name' => array( $files['tmp_name'] ),
                'error' => array( $files['error'] ),
                'size' => array( $files['size'] ),
            );
        }

        for ( $i = 0; $i < count( $files['name'] ); $i++ ) {
            if ( $files['error'][ $i ] !== UPLOAD_ERR_OK ) {
                continue;
            }

            if ( ! in_array( $files['type'][ $i ], $allowed_types ) ) {
                continue;
            }

            if ( $files['size'][ $i ] > $max_file_size ) {
                continue;
            }

            $upload = wp_handle_upload( array(
                'name' => $files['name'][ $i ],
                'type' => $files['type'][ $i ],
                'tmp_name' => $files['tmp_name'][ $i ],
                'error' => $files['error'][ $i ],
                'size' => $files['size'][ $i ],
            ), array( 'test_form' => false ) );

            if ( ! isset( $upload['error'] ) ) {
                $attachments[] = array(
                    'name' => $files['name'][ $i ],
                    'url' => $upload['url'],
                    'type' => $files['type'][ $i ],
                    'size' => $files['size'][ $i ],
                );
            }
        }

        return $attachments;
    }

    /**
     * Fetch user conversations from database.
     *
     * @since 1.6.1
     * @param int $user_id User ID.
     * @return array Conversations array.
     */
    private function fetch_user_conversations( $user_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_conversations';

        $conversations = $wpdb->get_results( $wpdb->prepare(
            "SELECT c.*,
                    CASE
                        WHEN c.user1_id = %d THEN u2.display_name
                        ELSE u1.display_name
                    END as other_user_name,
                    CASE
                        WHEN c.user1_id = %d THEN c.user2_id
                        ELSE c.user1_id
                    END as other_user_id,
                    c.last_message_time,
                    c.last_message_content
             FROM {$table_name} c
             LEFT JOIN {$wpdb->users} u1 ON c.user1_id = u1.ID
             LEFT JOIN {$wpdb->users} u2 ON c.user2_id = u2.ID
             WHERE c.user1_id = %d OR c.user2_id = %d
             ORDER BY c.last_message_time DESC",
            $user_id, $user_id, $user_id, $user_id
        ) );

        $formatted_conversations = array();
        foreach ( $conversations as $conversation ) {
            $formatted_conversations[] = array(
                'id' => $conversation->id,
                'other_user_name' => $conversation->other_user_name,
                'other_user_id' => $conversation->other_user_id,
                'other_user_avatar' => get_avatar_url( $conversation->other_user_id ),
                'last_message' => $conversation->last_message_content,
                'last_message_time' => $conversation->last_message_time,
                'unread_count' => $this->get_unread_count( $conversation->id, $user_id ),
            );
        }

        return $formatted_conversations;
    }

    /**
     * Fetch conversation messages from database.
     *
     * @since 1.6.1
     * @param int $conversation_id Conversation ID.
     * @param int $page Page number.
     * @param int $per_page Messages per page.
     * @return array Messages array.
     */
    private function fetch_conversation_messages( $conversation_id, $page, $per_page ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_messages';
        $offset = ( $page - 1 ) * $per_page;

        $messages = $wpdb->get_results( $wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name
             FROM {$table_name} m
             LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID
             WHERE m.conversation_id = %d
             ORDER BY m.created_at DESC
             LIMIT %d OFFSET %d",
            $conversation_id, $per_page, $offset
        ) );

        $formatted_messages = array();
        foreach ( array_reverse( $messages ) as $message ) {
            $formatted_messages[] = array(
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender_name,
                'content' => $message->content,
                'attachments' => maybe_unserialize( $message->attachments ),
                'created_at' => $message->created_at,
                'is_read' => $message->is_read,
            );
        }

        return $formatted_messages;
    }

    /**
     * Get conversation details.
     *
     * @since 1.6.1
     * @param int $conversation_id Conversation ID.
     * @return array Conversation details.
     */
    private function get_conversation_details( $conversation_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_conversations';
        $current_user_id = get_current_user_id();

        $conversation = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $conversation_id
        ) );

        if ( ! $conversation ) {
            return false;
        }

        // Determine the other user
        $other_user_id = ( $conversation->user1_id == $current_user_id ) ? $conversation->user2_id : $conversation->user1_id;
        $other_user = get_user_by( 'ID', $other_user_id );

        if ( ! $other_user ) {
            return false;
        }

        return array(
            'user_name' => $other_user->display_name,
            'user_avatar' => get_avatar_url( $other_user_id ),
            'user_status' => $this->get_user_online_status( $other_user_id ),
            'conversation_id' => $conversation_id,
        );
    }

    /**
     * Save message to database.
     *
     * @since 1.6.1
     * @param array $message_data Message data.
     * @return int|false Message ID or false on failure.
     */
    private function save_message( $message_data ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_messages';

        // Create or get conversation
        $conversation_id = $this->get_or_create_conversation( $message_data['sender_id'], $message_data['recipient_id'] );

        if ( ! $conversation_id ) {
            return false;
        }

        $result = $wpdb->insert(
            $table_name,
            array(
                'conversation_id' => $conversation_id,
                'sender_id' => $message_data['sender_id'],
                'content' => $message_data['content'],
                'attachments' => maybe_serialize( $message_data['attachments'] ),
                'created_at' => current_time( 'mysql' ),
                'is_read' => 0,
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%d' )
        );

        if ( $result ) {
            $message_id = $wpdb->insert_id;

            // Update conversation last message
            $this->update_conversation_last_message( $conversation_id, $message_data['content'] );

            return $message_id;
        }

        return false;
    }

    /**
     * Send message notification to recipient.
     *
     * @since 1.6.1
     * @param int $message_id Message ID.
     * @return bool Success status.
     */
    private function send_message_notification( $message_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_messages';

        $message = $wpdb->get_row( $wpdb->prepare(
            "SELECT m.*, c.user1_id, c.user2_id
             FROM {$table_name} m
             LEFT JOIN {$wpdb->prefix}schedspot_conversations c ON m.conversation_id = c.id
             WHERE m.id = %d",
            $message_id
        ) );

        if ( ! $message ) {
            return false;
        }

        // Determine recipient
        $recipient_id = ( $message->user1_id == $message->sender_id ) ? $message->user2_id : $message->user1_id;
        $recipient = get_user_by( 'ID', $recipient_id );
        $sender = get_user_by( 'ID', $message->sender_id );

        if ( ! $recipient || ! $sender ) {
            return false;
        }

        // Send email notification if enabled
        if ( get_user_meta( $recipient_id, 'schedspot_email_notifications', true ) !== 'no' ) {
            $subject = sprintf( __( 'New message from %s', 'schedspot' ), $sender->display_name );
            $message_content = sprintf(
                __( 'You have received a new message from %s: %s', 'schedspot' ),
                $sender->display_name,
                wp_trim_words( $message->content, 20 )
            );

            wp_mail( $recipient->user_email, $subject, $message_content );
        }

        return true;
    }

    /**
     * Format message for display.
     *
     * @since 1.6.1
     * @param array $message_data Message data.
     * @return array Formatted message data.
     */
    private function format_message_for_display( $message_data ) {
        return array(
            'id' => $message_data['id'] ?? 0,
            'sender_id' => $message_data['sender_id'],
            'content' => wp_kses_post( $message_data['content'] ),
            'attachments' => $message_data['attachments'] ?? array(),
            'created_at' => $message_data['created_at'],
            'time_ago' => human_time_diff( strtotime( $message_data['created_at'] ) ),
        );
    }

    /**
     * Mark conversation messages as read.
     *
     * @since 1.6.1
     * @param int $conversation_id Conversation ID.
     * @param int $user_id User ID.
     * @return bool Success status.
     */
    private function mark_conversation_messages_read( $conversation_id, $user_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_messages';

        $result = $wpdb->update(
            $table_name,
            array( 'is_read' => 1 ),
            array(
                'conversation_id' => $conversation_id,
                'sender_id' => array( 'NOT IN' => array( $user_id ) ),
                'is_read' => 0,
            ),
            array( '%d' ),
            array( '%d', '%s', '%d' )
        );

        return $result !== false;
    }

    /**
     * Get or create conversation between two users.
     *
     * @since 1.6.1
     * @param int $user1_id First user ID.
     * @param int $user2_id Second user ID.
     * @return int|false Conversation ID or false on failure.
     */
    private function get_or_create_conversation( $user1_id, $user2_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_conversations';

        // Check if conversation already exists
        $conversation_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table_name}
             WHERE (user1_id = %d AND user2_id = %d)
             OR (user1_id = %d AND user2_id = %d)",
            $user1_id, $user2_id, $user2_id, $user1_id
        ) );

        if ( $conversation_id ) {
            return $conversation_id;
        }

        // Create new conversation
        $result = $wpdb->insert(
            $table_name,
            array(
                'user1_id' => $user1_id,
                'user2_id' => $user2_id,
                'created_at' => current_time( 'mysql' ),
                'last_message_time' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%s' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update conversation last message.
     *
     * @since 1.6.1
     * @param int $conversation_id Conversation ID.
     * @param string $message_content Message content.
     */
    private function update_conversation_last_message( $conversation_id, $message_content ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_conversations';

        $wpdb->update(
            $table_name,
            array(
                'last_message_content' => wp_trim_words( $message_content, 10 ),
                'last_message_time' => current_time( 'mysql' ),
            ),
            array( 'id' => $conversation_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }

    /**
     * Get unread message count for conversation.
     *
     * @since 1.6.1
     * @param int $conversation_id Conversation ID.
     * @param int $user_id User ID.
     * @return int Unread count.
     */
    private function get_unread_count( $conversation_id, $user_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'schedspot_messages';

        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name}
             WHERE conversation_id = %d AND sender_id != %d AND is_read = 0",
            $conversation_id, $user_id
        ) );

        return $count ? intval( $count ) : 0;
    }

    /**
     * Get user online status.
     *
     * @since 1.6.1
     * @param int $user_id User ID.
     * @return string Online status.
     */
    private function get_user_online_status( $user_id ) {
        $last_activity = get_user_meta( $user_id, 'schedspot_last_activity', true );

        if ( ! $last_activity ) {
            return __( 'Offline', 'schedspot' );
        }

        $time_diff = time() - strtotime( $last_activity );

        if ( $time_diff < 300 ) { // 5 minutes
            return __( 'Online', 'schedspot' );
        } elseif ( $time_diff < 3600 ) { // 1 hour
            return __( 'Away', 'schedspot' );
        } else {
            return __( 'Offline', 'schedspot' );
        }
    }
}
