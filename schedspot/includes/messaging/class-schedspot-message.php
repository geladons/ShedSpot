<?php
/**
 * SchedSpot Message Model Class
 *
 * @package SchedSpot
 * @version 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Message Class.
 *
 * @class SchedSpot_Message
 * @version 2.0.0
 */
class SchedSpot_Message {

    /**
     * Message ID.
     *
     * @var int
     */
    public $id = 0;

    /**
     * Sender user ID.
     *
     * @var int
     */
    public $sender_id = 0;

    /**
     * Receiver user ID.
     *
     * @var int
     */
    public $receiver_id = 0;

    /**
     * Related booking ID (optional).
     *
     * @var int
     */
    public $booking_id = 0;

    /**
     * Message content.
     *
     * @var string
     */
    public $content = '';

    /**
     * Message type (text, file, system).
     *
     * @var string
     */
    public $message_type = 'text';

    /**
     * Attachment data (JSON).
     *
     * @var string
     */
    public $attachment_data = '';

    /**
     * Message status (sent, delivered, read).
     *
     * @var string
     */
    public $status = 'sent';

    /**
     * Read timestamp.
     *
     * @var string
     */
    public $read_at = null;

    /**
     * Created timestamp.
     *
     * @var string
     */
    public $created_at = '';

    /**
     * Updated timestamp.
     *
     * @var string
     */
    public $updated_at = '';

    /**
     * Constructor.
     *
     * @since 2.0.0
     * @param int $message_id Message ID.
     */
    public function __construct( $message_id = 0 ) {
        if ( $message_id > 0 ) {
            $this->load_message( $message_id );
        }
    }

    /**
     * Load message data from database.
     *
     * @since 2.0.0
     * @param int $message_id Message ID.
     * @return bool True on success, false on failure.
     */
    private function load_message( $message_id ) {
        global $wpdb;

        $message = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}schedspot_messages WHERE id = %d",
            $message_id
        ) );

        if ( ! $message ) {
            return false;
        }

        $this->id = absint( $message->id );
        $this->sender_id = absint( $message->sender_id );
        $this->receiver_id = absint( $message->receiver_id );
        $this->booking_id = absint( $message->booking_id );
        $this->content = $message->content;
        $this->message_type = $message->message_type;
        $this->attachment_data = $message->attachment_data;
        $this->status = $message->status;
        $this->read_at = $message->read_at;
        $this->created_at = $message->created_at;
        $this->updated_at = $message->updated_at;

        return true;
    }

    /**
     * Save message to database.
     *
     * @since 2.0.0
     * @return int|WP_Error Message ID on success, WP_Error on failure.
     */
    public function save() {
        global $wpdb;

        $data = array(
            'sender_id'       => $this->sender_id,
            'receiver_id'     => $this->receiver_id,
            'booking_id'      => $this->booking_id,
            'content'         => $this->content,
            'message_type'    => $this->message_type,
            'attachment_data' => $this->attachment_data,
            'status'          => $this->status,
            'read_at'         => $this->read_at,
            'updated_at'      => current_time( 'mysql' ),
        );

        $format = array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' );

        if ( $this->id > 0 ) {
            // Update existing message
            $result = $wpdb->update(
                $wpdb->prefix . 'schedspot_messages',
                $data,
                array( 'id' => $this->id ),
                $format,
                array( '%d' )
            );

            if ( $result === false ) {
                return new WP_Error( 'message_update_failed', __( 'Failed to update message.', 'schedspot' ) );
            }

            return $this->id;
        } else {
            // Insert new message
            $data['created_at'] = current_time( 'mysql' );
            $format[] = '%s';

            $result = $wpdb->insert(
                $wpdb->prefix . 'schedspot_messages',
                $data,
                $format
            );

            if ( $result === false ) {
                return new WP_Error( 'message_insert_failed', __( 'Failed to create message.', 'schedspot' ) );
            }

            $this->id = $wpdb->insert_id;
            $this->created_at = $data['created_at'];

            return $this->id;
        }
    }

    /**
     * Delete message from database.
     *
     * @since 2.0.0
     * @return bool True on success, false on failure.
     */
    public function delete() {
        global $wpdb;

        if ( $this->id <= 0 ) {
            return false;
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'schedspot_messages',
            array( 'id' => $this->id ),
            array( '%d' )
        );

        if ( $result !== false ) {
            // Clean up attachments if any
            if ( ! empty( $this->attachment_data ) ) {
                $this->delete_attachments();
            }

            // Reset object properties
            $this->id = 0;
            return true;
        }

        return false;
    }

    /**
     * Mark message as read.
     *
     * @since 2.0.0
     * @return bool True on success, false on failure.
     */
    public function mark_as_read() {
        if ( $this->status === 'read' ) {
            return true; // Already read
        }

        $this->status = 'read';
        $this->read_at = current_time( 'mysql' );

        $result = $this->save();

        if ( ! is_wp_error( $result ) ) {
            // Fire action hook
            do_action( 'schedspot_message_read', $this->id, $this->sender_id, $this->receiver_id );
            return true;
        }

        return false;
    }

    /**
     * Get message attachments.
     *
     * @since 2.0.0
     * @return array Array of attachment data.
     */
    public function get_attachments() {
        if ( empty( $this->attachment_data ) ) {
            return array();
        }

        $attachments = json_decode( $this->attachment_data, true );
        return is_array( $attachments ) ? $attachments : array();
    }

    /**
     * Add attachment to message.
     *
     * @since 2.0.0
     * @param array $attachment_info Attachment information.
     * @return bool True on success, false on failure.
     */
    public function add_attachment( $attachment_info ) {
        $attachments = $this->get_attachments();
        $attachments[] = $attachment_info;
        
        $this->attachment_data = wp_json_encode( $attachments );
        
        return ! is_wp_error( $this->save() );
    }

    /**
     * Delete message attachments.
     *
     * @since 2.0.0
     * @return bool True on success, false on failure.
     */
    private function delete_attachments() {
        $attachments = $this->get_attachments();
        
        foreach ( $attachments as $attachment ) {
            if ( isset( $attachment['file_path'] ) && file_exists( $attachment['file_path'] ) ) {
                wp_delete_file( $attachment['file_path'] );
            }
        }

        return true;
    }

    /**
     * Get formatted message data for API/frontend.
     *
     * @since 2.0.0
     * @return array Formatted message data.
     */
    public function get_formatted_data() {
        $sender = get_userdata( $this->sender_id );
        $receiver = get_userdata( $this->receiver_id );

        return array(
            'id'              => $this->id,
            'sender_id'       => $this->sender_id,
            'receiver_id'     => $this->receiver_id,
            'booking_id'      => $this->booking_id,
            'content'         => $this->content,
            'message_type'    => $this->message_type,
            'attachments'     => $this->get_attachments(),
            'status'          => $this->status,
            'read_at'         => $this->read_at,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            'sender_name'     => $sender ? $sender->display_name : __( 'Unknown User', 'schedspot' ),
            'sender_avatar'   => get_avatar_url( $this->sender_id, array( 'size' => 32 ) ),
            'receiver_name'   => $receiver ? $receiver->display_name : __( 'Unknown User', 'schedspot' ),
            'receiver_avatar' => get_avatar_url( $this->receiver_id, array( 'size' => 32 ) ),
            'time_ago'        => human_time_diff( strtotime( $this->created_at ), current_time( 'timestamp' ) ),
        );
    }

    /**
     * Create a new message.
     *
     * @since 2.0.0
     * @param array $message_data Message data.
     * @return int|WP_Error Message ID on success, WP_Error on failure.
     */
    public static function create_message( $message_data ) {
        // Validate required fields
        $required_fields = array( 'sender_id', 'receiver_id', 'content' );
        foreach ( $required_fields as $field ) {
            if ( empty( $message_data[ $field ] ) ) {
                return new WP_Error( 'missing_field', sprintf( __( 'Missing required field: %s', 'schedspot' ), $field ) );
            }
        }

        // Validate users exist
        if ( ! get_userdata( $message_data['sender_id'] ) ) {
            return new WP_Error( 'invalid_sender', __( 'Invalid sender user ID.', 'schedspot' ) );
        }

        if ( ! get_userdata( $message_data['receiver_id'] ) ) {
            return new WP_Error( 'invalid_receiver', __( 'Invalid receiver user ID.', 'schedspot' ) );
        }

        // Create message object
        $message = new self();
        $message->sender_id = absint( $message_data['sender_id'] );
        $message->receiver_id = absint( $message_data['receiver_id'] );
        $message->booking_id = isset( $message_data['booking_id'] ) ? absint( $message_data['booking_id'] ) : 0;
        $message->content = sanitize_textarea_field( $message_data['content'] );
        $message->message_type = isset( $message_data['message_type'] ) ? sanitize_text_field( $message_data['message_type'] ) : 'text';
        $message->attachment_data = isset( $message_data['attachment_data'] ) ? wp_json_encode( $message_data['attachment_data'] ) : '';
        $message->status = 'sent';

        // Save message
        $result = $message->save();

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Fire action hook
        do_action( 'schedspot_message_sent', $message->id, $message->sender_id, $message->receiver_id, $message->booking_id );

        return $message->id;
    }

    /**
     * Get messages between two users.
     *
     * @since 2.0.0
     * @param int   $user1_id First user ID.
     * @param int   $user2_id Second user ID.
     * @param array $args Query arguments.
     * @return array Array of message objects.
     */
    public static function get_conversation( $user1_id, $user2_id, $args = array() ) {
        global $wpdb;

        $defaults = array(
            'limit'      => 50,
            'offset'     => 0,
            'order'      => 'ASC',
            'booking_id' => 0,
        );

        $args = wp_parse_args( $args, $defaults );

        $where_clauses = array(
            '((sender_id = %d AND receiver_id = %d) OR (sender_id = %d AND receiver_id = %d))'
        );
        $params = array( $user1_id, $user2_id, $user2_id, $user1_id );

        if ( $args['booking_id'] > 0 ) {
            $where_clauses[] = 'booking_id = %d';
            $params[] = $args['booking_id'];
        }

        $where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
        $order_sql = sprintf( 'ORDER BY created_at %s', $args['order'] === 'DESC' ? 'DESC' : 'ASC' );
        $limit_sql = sprintf( 'LIMIT %d OFFSET %d', absint( $args['limit'] ), absint( $args['offset'] ) );

        $sql = "SELECT * FROM {$wpdb->prefix}schedspot_messages {$where_sql} {$order_sql} {$limit_sql}";
        $sql = $wpdb->prepare( $sql, $params );

        $results = $wpdb->get_results( $sql );
        $messages = array();

        foreach ( $results as $row ) {
            $message = new self();
            $message->id = absint( $row->id );
            $message->sender_id = absint( $row->sender_id );
            $message->receiver_id = absint( $row->receiver_id );
            $message->booking_id = absint( $row->booking_id );
            $message->content = $row->content;
            $message->message_type = $row->message_type;
            $message->attachment_data = $row->attachment_data;
            $message->status = $row->status;
            $message->read_at = $row->read_at;
            $message->created_at = $row->created_at;
            $message->updated_at = $row->updated_at;

            $messages[] = $message;
        }

        return $messages;
    }

    /**
     * Get user conversations.
     *
     * @since 2.0.0
     * @param int   $user_id User ID.
     * @param array $args Query arguments.
     * @return array Array of conversation data.
     */
    public static function get_user_conversations( $user_id, $args = array() ) {
        global $wpdb;

        $defaults = array(
            'limit'  => 20,
            'offset' => 0,
        );

        $args = wp_parse_args( $args, $defaults );

        // Get latest message for each conversation
        $sql = "
            SELECT
                CASE
                    WHEN sender_id = %d THEN receiver_id
                    ELSE sender_id
                END as other_user_id,
                MAX(created_at) as last_message_time,
                COUNT(*) as message_count,
                SUM(CASE WHEN receiver_id = %d AND status != 'read' THEN 1 ELSE 0 END) as unread_count
            FROM {$wpdb->prefix}schedspot_messages
            WHERE sender_id = %d OR receiver_id = %d
            GROUP BY other_user_id
            ORDER BY last_message_time DESC
            LIMIT %d OFFSET %d
        ";

        $results = $wpdb->get_results( $wpdb->prepare(
            $sql,
            $user_id,
            $user_id,
            $user_id,
            $user_id,
            absint( $args['limit'] ),
            absint( $args['offset'] )
        ) );

        $conversations = array();

        foreach ( $results as $row ) {
            $other_user = get_userdata( $row->other_user_id );
            if ( ! $other_user ) {
                continue;
            }

            // Get the latest message
            $latest_message = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}schedspot_messages
                 WHERE (sender_id = %d AND receiver_id = %d) OR (sender_id = %d AND receiver_id = %d)
                 ORDER BY created_at DESC LIMIT 1",
                $user_id,
                $row->other_user_id,
                $row->other_user_id,
                $user_id
            ) );

            $conversations[] = array(
                'other_user_id'     => absint( $row->other_user_id ),
                'other_user_name'   => $other_user->display_name,
                'other_user_avatar' => get_avatar_url( $row->other_user_id, array( 'size' => 48 ) ),
                'last_message_time' => $row->last_message_time,
                'last_message'      => $latest_message ? $latest_message->content : '',
                'message_count'     => absint( $row->message_count ),
                'unread_count'      => absint( $row->unread_count ),
                'time_ago'          => human_time_diff( strtotime( $row->last_message_time ), current_time( 'timestamp' ) ),
            );
        }

        return $conversations;
    }
}
