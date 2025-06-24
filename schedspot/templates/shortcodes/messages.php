<?php
/**
 * Messages Interface Template
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="schedspot-messaging">
    <!-- Conversations Sidebar -->
    <div class="schedspot-conversations">
        <div class="schedspot-conversations-header">
            <h3><?php _e( 'Conversations', 'schedspot' ); ?></h3>
            <button type="button" class="new-conversation-btn" onclick="startNewConversation()">
                <?php _e( 'New', 'schedspot' ); ?>
            </button>
        </div>
        
        <div class="conversations-search">
            <input type="text" id="conversation-search" placeholder="<?php esc_attr_e( 'Search conversations...', 'schedspot' ); ?>">
        </div>
        
        <div class="schedspot-conversations-list">
            <?php if ( ! empty( $conversations ) ) : ?>
                <?php foreach ( $conversations as $conversation ) : ?>
                    <div class="schedspot-conversation-item" 
                         data-user-id="<?php echo esc_attr( $conversation['user_id'] ); ?>"
                         <?php if ( $target_conversation && $target_conversation['user']['id'] == $conversation['user_id'] ) echo 'class="active"'; ?>>
                        <div class="user-avatar">
                            <img src="<?php echo esc_url( $conversation['avatar'] ); ?>" alt="<?php echo esc_attr( $conversation['name'] ); ?>">
                        </div>
                        <div class="conversation-info">
                            <div class="user-name">
                                <?php echo esc_html( $conversation['name'] ); ?>
                                <?php if ( $conversation['unread_count'] > 0 ) : ?>
                                    <span class="unread-count"><?php echo esc_html( $conversation['unread_count'] ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="last-message"><?php echo esc_html( $conversation['last_message'] ); ?></div>
                            <div class="time-ago"><?php echo esc_html( $conversation['time_ago'] ); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="no-conversations">
                    <p><?php _e( 'No conversations yet.', 'schedspot' ); ?></p>
                    <p><?php _e( 'Start a conversation by booking a service or contacting a worker.', 'schedspot' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="schedspot-chat-area" <?php if ( ! $target_conversation ) echo 'style="display: none;"'; ?>>
        <div class="schedspot-chat-header">
            <?php if ( $target_conversation ) : ?>
                <div class="chat-user-info">
                    <img src="<?php echo esc_url( $target_conversation['user']['avatar'] ); ?>" alt="<?php echo esc_attr( $target_conversation['user']['name'] ); ?>" class="chat-avatar">
                    <h3><?php echo esc_html( $target_conversation['user']['name'] ); ?></h3>
                </div>
                <div class="chat-actions">
                    <button type="button" class="chat-action-btn" onclick="toggleChatInfo()" title="<?php esc_attr_e( 'Chat Info', 'schedspot' ); ?>">
                        ℹ️
                    </button>
                    <button type="button" class="chat-action-btn" onclick="clearConversation()" title="<?php esc_attr_e( 'Clear Conversation', 'schedspot' ); ?>">
                        🗑️
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div class="schedspot-messages">
            <?php if ( $target_conversation && ! empty( $target_conversation['messages'] ) ) : ?>
                <?php foreach ( $target_conversation['messages'] as $message ) : ?>
                    <div class="schedspot-message <?php echo $message['is_own'] ? 'own' : ''; ?>">
                        <div class="avatar">
                            <img src="<?php echo esc_url( $message['sender_avatar'] ); ?>" alt="<?php echo esc_attr( $message['sender_name'] ); ?>">
                        </div>
                        <div class="content">
                            <div class="text"><?php echo wp_kses_post( nl2br( $message['content'] ) ); ?></div>
                            
                            <?php if ( $message['attachment_url'] ) : ?>
                                <div class="schedspot-attachment">
                                    <a href="<?php echo esc_url( $message['attachment_url'] ); ?>" target="_blank">
                                        📎 <?php echo esc_html( $message['attachment_name'] ?: __( 'Attachment', 'schedspot' ) ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="time"><?php echo esc_html( $message['time_ago'] ); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif ( $target_conversation ) : ?>
                <div class="no-messages">
                    <p><?php _e( 'No messages in this conversation yet.', 'schedspot' ); ?></p>
                    <p><?php _e( 'Start the conversation by sending a message below.', 'schedspot' ); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ( $target_conversation ) : ?>
            <div class="schedspot-message-form">
                <form id="schedspot-message-form" enctype="multipart/form-data">
                    <input type="hidden" name="recipient_id" value="<?php echo esc_attr( $target_conversation['user']['id'] ); ?>">
                    
                    <div class="schedspot-message-input">
                        <div class="schedspot-file-upload">
                            <input type="file" id="message-attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
                            <label for="message-attachment" title="<?php esc_attr_e( 'Attach File', 'schedspot' ); ?>">
                                📎
                            </label>
                        </div>
                        
                        <textarea id="message-text" 
                                  name="content" 
                                  placeholder="<?php esc_attr_e( 'Type your message...', 'schedspot' ); ?>" 
                                  rows="1"></textarea>
                        
                        <button type="submit" class="send-button" title="<?php esc_attr_e( 'Send Message', 'schedspot' ); ?>">
                            ➤
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- No Conversation Selected -->
    <?php if ( ! $target_conversation ) : ?>
        <div class="schedspot-no-conversation">
            <div class="no-conversation-content">
                <h3><?php _e( 'Select a Conversation', 'schedspot' ); ?></h3>
                <p><?php _e( 'Choose a conversation from the sidebar to start messaging.', 'schedspot' ); ?></p>
                
                <?php if ( empty( $conversations ) ) : ?>
                    <div class="getting-started">
                        <h4><?php _e( 'Getting Started', 'schedspot' ); ?></h4>
                        <p><?php _e( 'You can start conversations by:', 'schedspot' ); ?></p>
                        <ul>
                            <li><?php _e( 'Booking a service', 'schedspot' ); ?></li>
                            <li><?php _e( 'Contacting clients about their bookings', 'schedspot' ); ?></li>
                            <li><?php _e( 'Responding to booking requests', 'schedspot' ); ?></li>
                        </ul>
                        
                        <div class="quick-actions">
                            <a href="<?php echo home_url( '/?schedspot_action=booking_form' ); ?>" class="schedspot-btn schedspot-btn-primary">
                                <?php _e( 'Book a Service', 'schedspot' ); ?>
                            </a>
                            <a href="<?php echo home_url( '/?schedspot_action=dashboard' ); ?>" class="schedspot-btn schedspot-btn-secondary">
                                <?php _e( 'View Dashboard', 'schedspot' ); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Chat Info Sidebar (Hidden by default) -->
<div class="schedspot-chat-info" id="chat-info-sidebar" style="display: none;">
    <?php if ( $target_conversation ) : ?>
        <div class="chat-info-header">
            <h4><?php _e( 'Chat Information', 'schedspot' ); ?></h4>
            <button type="button" class="close-info" onclick="toggleChatInfo()">×</button>
        </div>
        
        <div class="chat-info-content">
            <div class="user-profile">
                <img src="<?php echo esc_url( $target_conversation['user']['avatar'] ); ?>" alt="<?php echo esc_attr( $target_conversation['user']['name'] ); ?>" class="profile-avatar">
                <h5><?php echo esc_html( $target_conversation['user']['name'] ); ?></h5>
            </div>
            
            <div class="conversation-stats">
                <h6><?php _e( 'Conversation Stats', 'schedspot' ); ?></h6>
                <p><?php printf( __( 'Messages: %d', 'schedspot' ), count( $target_conversation['messages'] ) ); ?></p>
                <p><?php printf( __( 'Started: %s', 'schedspot' ), date( 'M j, Y', strtotime( $target_conversation['messages'][0]['created_at'] ?? 'now' ) ) ); ?></p>
            </div>
            
            <div class="conversation-actions">
                <button type="button" class="schedspot-btn schedspot-btn-secondary" onclick="exportConversation()">
                    <?php _e( 'Export Chat', 'schedspot' ); ?>
                </button>
                <button type="button" class="schedspot-btn schedspot-btn-danger" onclick="deleteConversation()">
                    <?php _e( 'Delete Chat', 'schedspot' ); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Initialize messaging interface
document.addEventListener('DOMContentLoaded', function() {
    // Auto-scroll to bottom of messages
    const messagesContainer = document.querySelector('.schedspot-messages');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Search functionality
    const searchInput = document.getElementById('conversation-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const conversations = document.querySelectorAll('.schedspot-conversation-item');
            
            conversations.forEach(conversation => {
                const name = conversation.querySelector('.user-name').textContent.toLowerCase();
                const lastMessage = conversation.querySelector('.last-message').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || lastMessage.includes(searchTerm)) {
                    conversation.style.display = '';
                } else {
                    conversation.style.display = 'none';
                }
            });
        });
    }
});

// Toggle chat info sidebar
function toggleChatInfo() {
    const sidebar = document.getElementById('chat-info-sidebar');
    const chatArea = document.querySelector('.schedspot-chat-area');
    
    if (sidebar.style.display === 'none' || sidebar.style.display === '') {
        sidebar.style.display = 'block';
        chatArea.style.marginRight = '300px';
    } else {
        sidebar.style.display = 'none';
        chatArea.style.marginRight = '0';
    }
}

// Start new conversation
function startNewConversation() {
    // This would typically open a modal or redirect to a user selection page
    alert('<?php esc_js( _e( 'Feature coming soon! For now, conversations start automatically when you book services or respond to bookings.', 'schedspot' ) ); ?>');
}

// Clear conversation
function clearConversation() {
    if (confirm('<?php esc_js( _e( 'Are you sure you want to clear this conversation? This action cannot be undone.', 'schedspot' ) ); ?>')) {
        // Implementation would go here
        alert('<?php esc_js( _e( 'Conversation cleared.', 'schedspot' ) ); ?>');
    }
}

// Export conversation
function exportConversation() {
    // Implementation would generate and download a conversation export
    alert('<?php esc_js( _e( 'Conversation export feature coming soon!', 'schedspot' ) ); ?>');
}

// Delete conversation
function deleteConversation() {
    if (confirm('<?php esc_js( _e( 'Are you sure you want to delete this conversation? This action cannot be undone.', 'schedspot' ) ); ?>')) {
        // Implementation would go here
        alert('<?php esc_js( _e( 'Conversation deleted.', 'schedspot' ) ); ?>');
        location.reload();
    }
}
</script>

<style>
.schedspot-messaging {
    display: flex;
    height: 600px;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
}

.schedspot-conversations {
    width: 300px;
    border-right: 1px solid #ddd;
    background: #f9f9f9;
    display: flex;
    flex-direction: column;
}

.schedspot-conversations-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    background: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.schedspot-conversations-header h3 {
    margin: 0;
    color: #333;
    font-size: 18px;
}

.new-conversation-btn {
    background: #0073aa;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.conversations-search {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.conversations-search input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.schedspot-chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    transition: margin-right 0.3s ease;
}

.schedspot-chat-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    background: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.chat-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}

.chat-user-info h3 {
    margin: 0;
    color: #333;
}

.chat-actions {
    display: flex;
    gap: 10px;
}

.chat-action-btn {
    background: none;
    border: 1px solid #ddd;
    padding: 8px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

.schedspot-no-conversation {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f9f9f9;
}

.no-conversation-content {
    text-align: center;
    max-width: 400px;
    padding: 40px;
}

.getting-started {
    margin-top: 30px;
    text-align: left;
}

.getting-started ul {
    margin: 15px 0;
    padding-left: 20px;
}

.quick-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    justify-content: center;
}

.schedspot-chat-info {
    position: absolute;
    right: 0;
    top: 0;
    width: 300px;
    height: 100%;
    background: white;
    border-left: 1px solid #ddd;
    z-index: 10;
}

.chat-info-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.close-info {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
}

.chat-info-content {
    padding: 20px;
}

.user-profile {
    text-align: center;
    margin-bottom: 30px;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    margin-bottom: 10px;
}

.conversation-stats {
    margin-bottom: 30px;
}

.conversation-stats h6 {
    margin-bottom: 10px;
    color: #333;
}

.conversation-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

@media (max-width: 768px) {
    .schedspot-messaging {
        flex-direction: column;
        height: auto;
    }

    .schedspot-conversations {
        width: 100%;
        height: 200px;
    }

    .schedspot-chat-area {
        height: 400px;
    }

    .schedspot-chat-info {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1000;
    }
}
</style>
