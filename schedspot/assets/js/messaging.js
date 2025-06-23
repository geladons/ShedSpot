/**
 * SchedSpot Messaging System JavaScript
 * 
 * @package SchedSpot
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Global messaging object
    window.SchedSpotMessaging = {
        currentConversation: null,
        conversations: [],
        messages: [],
        refreshInterval: null,
        isLoading: false
    };

    /**
     * Initialize messaging system
     */
    function initMessaging() {
        // Load conversations
        loadConversations();
        
        // Event handlers
        $(document).on('click', '.conversation-item', handleConversationClick);
        $(document).on('click', '.send-message-btn', handleSendMessage);
        $(document).on('keypress', '.message-input', handleMessageInputKeypress);
        $(document).on('click', '.attach-file-btn', handleFileAttach);
        $(document).on('change', '.file-input', handleFileSelect);
        
        // Auto-refresh messages
        SchedSpotMessaging.refreshInterval = setInterval(refreshCurrentConversation, 10000); // 10 seconds
        
        // Auto-resize message input
        $('.message-input').on('input', autoResizeMessageInput);
    }

    /**
     * Load conversations list
     */
    function loadConversations() {
        if (SchedSpotMessaging.isLoading) return;
        
        SchedSpotMessaging.isLoading = true;
        
        $.ajax({
            url: schedspot_frontend.rest_url + 'messages/conversations',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (response.success && response.data) {
                    SchedSpotMessaging.conversations = response.data;
                    renderConversationsList(response.data);
                } else {
                    showEmptyConversations();
                }
            },
            error: function() {
                showError('Failed to load conversations');
            },
            complete: function() {
                SchedSpotMessaging.isLoading = false;
            }
        });
    }

    /**
     * Render conversations list
     */
    function renderConversationsList(conversations) {
        const $list = $('.conversations-list');
        let html = '<div class="conversations-header">Messages</div>';
        
        if (conversations.length === 0) {
            html += '<div class="no-conversations">No conversations yet</div>';
        } else {
            conversations.forEach(function(conversation) {
                const isUnread = conversation.unread_count > 0;
                const timeAgo = formatTimeAgo(conversation.last_message_time);
                
                html += `
                    <div class="conversation-item" data-user-id="${conversation.user_id}">
                        <div class="conversation-name">${conversation.user_name}</div>
                        <div class="conversation-preview">${conversation.last_message || 'No messages yet'}</div>
                        <div class="conversation-time">${timeAgo}</div>
                        ${isUnread ? '<div class="unread-indicator"></div>' : ''}
                    </div>
                `;
            });
        }
        
        $list.html(html);
    }

    /**
     * Handle conversation click
     */
    function handleConversationClick() {
        const userId = $(this).data('user-id');
        selectConversation(userId);
    }

    /**
     * Select a conversation
     */
    function selectConversation(userId) {
        // Update UI
        $('.conversation-item').removeClass('active');
        $(`.conversation-item[data-user-id="${userId}"]`).addClass('active');
        
        // Load messages
        loadConversationMessages(userId);
        
        // Mark as read
        markConversationAsRead(userId);
        
        SchedSpotMessaging.currentConversation = userId;
    }

    /**
     * Load conversation messages
     */
    function loadConversationMessages(userId) {
        $('.chat-area').removeClass('no-conversation');
        $('.messages-container').html('<div class="loading-messages">Loading messages...</div>');
        
        $.ajax({
            url: schedspot_frontend.rest_url + 'messages/conversation/' + userId,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (response.success && response.data) {
                    SchedSpotMessaging.messages = response.data.messages;
                    renderMessages(response.data.messages);
                    updateChatHeader(response.data.user);
                } else {
                    showError('Failed to load messages');
                }
            },
            error: function() {
                showError('Failed to load messages');
            }
        });
    }

    /**
     * Render messages
     */
    function renderMessages(messages) {
        const $container = $('.messages-container');
        let html = '';
        
        if (messages.length === 0) {
            html = '<div class="no-messages">No messages yet. Start the conversation!</div>';
        } else {
            messages.forEach(function(message) {
                const isSent = message.is_sent;
                const timeFormatted = formatMessageTime(message.created_at);
                
                html += `
                    <div class="message ${isSent ? 'sent' : 'received'}">
                        <img src="${message.sender_avatar}" alt="${message.sender_name}" class="message-avatar">
                        <div class="message-content">
                            <div class="message-text">${escapeHtml(message.content)}</div>
                            ${message.attachment ? renderAttachment(message.attachment) : ''}
                            <div class="message-time">${timeFormatted}</div>
                            ${isSent ? `<div class="message-status">${message.status}</div>` : ''}
                        </div>
                    </div>
                `;
            });
        }
        
        $container.html(html);
        scrollToBottom();
    }

    /**
     * Render message attachment
     */
    function renderAttachment(attachment) {
        return `
            <div class="message-attachment">
                <span class="attachment-icon dashicons dashicons-paperclip"></span>
                <div class="attachment-info">
                    <div class="attachment-name">${attachment.name}</div>
                    <div class="attachment-size">${formatFileSize(attachment.size)}</div>
                </div>
            </div>
        `;
    }

    /**
     * Update chat header
     */
    function updateChatHeader(user) {
        $('.chat-user-name').text(user.name);
        $('.chat-user-avatar').attr('src', user.avatar);
        $('.chat-user-status').text(user.is_online ? 'Online' : 'Offline');
    }

    /**
     * Handle send message
     */
    function handleSendMessage() {
        const message = $('.message-input').val().trim();
        if (!message || !SchedSpotMessaging.currentConversation) return;
        
        sendMessage(message);
    }

    /**
     * Handle message input keypress
     */
    function handleMessageInputKeypress(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            handleSendMessage();
        }
    }

    /**
     * Send message
     */
    function sendMessage(content, attachment = null) {
        const $input = $('.message-input');
        const $sendBtn = $('.send-message-btn');
        
        // Disable input
        $input.prop('disabled', true);
        $sendBtn.prop('disabled', true);
        
        const formData = new FormData();
        formData.append('recipient_id', SchedSpotMessaging.currentConversation);
        formData.append('content', content);
        if (attachment) {
            formData.append('attachment', attachment);
        }
        
        $.ajax({
            url: schedspot_frontend.rest_url + 'messages/send',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (response.success) {
                    // Clear input
                    $input.val('').trigger('input');
                    
                    // Add message to UI immediately
                    addMessageToUI(response.data);
                    
                    // Refresh conversation list
                    loadConversations();
                } else {
                    showError(response.message || 'Failed to send message');
                }
            },
            error: function() {
                showError('Failed to send message');
            },
            complete: function() {
                $input.prop('disabled', false);
                $sendBtn.prop('disabled', false);
                $input.focus();
            }
        });
    }

    /**
     * Add message to UI
     */
    function addMessageToUI(message) {
        const timeFormatted = formatMessageTime(message.created_at);
        const messageHtml = `
            <div class="message sent">
                <img src="${message.sender_avatar}" alt="${message.sender_name}" class="message-avatar">
                <div class="message-content">
                    <div class="message-text">${escapeHtml(message.content)}</div>
                    <div class="message-time">${timeFormatted}</div>
                    <div class="message-status">Sent</div>
                </div>
            </div>
        `;
        
        $('.messages-container').append(messageHtml);
        scrollToBottom();
    }

    /**
     * Auto-resize message input
     */
    function autoResizeMessageInput() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    }

    /**
     * Refresh current conversation
     */
    function refreshCurrentConversation() {
        if (SchedSpotMessaging.currentConversation) {
            loadConversationMessages(SchedSpotMessaging.currentConversation);
        }
    }

    /**
     * Mark conversation as read
     */
    function markConversationAsRead(userId) {
        $.ajax({
            url: schedspot_frontend.rest_url + 'messages/mark-read/' + userId,
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (response.success) {
                    // Remove unread indicator
                    $(`.conversation-item[data-user-id="${userId}"] .unread-indicator`).remove();
                }
            }
        });
    }

    /**
     * Scroll to bottom of messages
     */
    function scrollToBottom() {
        const $container = $('.messages-container');
        $container.scrollTop($container[0].scrollHeight);
    }

    /**
     * Format time ago
     */
    function formatTimeAgo(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = now - time;
        const minutes = Math.floor(diff / 60000);
        
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return minutes + 'm ago';
        
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return hours + 'h ago';
        
        const days = Math.floor(hours / 24);
        if (days < 7) return days + 'd ago';
        
        return time.toLocaleDateString();
    }

    /**
     * Format message time
     */
    function formatMessageTime(timestamp) {
        const time = new Date(timestamp);
        return time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    /**
     * Format file size
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Show error message
     */
    function showError(message) {
        $('.messages-container').html(`<div class="error-message">${message}</div>`);
    }

    /**
     * Show empty conversations
     */
    function showEmptyConversations() {
        $('.conversations-list').html(`
            <div class="conversations-header">Messages</div>
            <div class="no-conversations">
                <div class="dashicons dashicons-email-alt"></div>
                <p>No conversations yet</p>
            </div>
        `);
    }

    // Public methods
    SchedSpotMessaging.selectConversation = selectConversation;
    SchedSpotMessaging.sendMessage = sendMessage;
    SchedSpotMessaging.refresh = loadConversations;

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.schedspot-messaging').length) {
            initMessaging();
        }
    });

    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        if (SchedSpotMessaging.refreshInterval) {
            clearInterval(SchedSpotMessaging.refreshInterval);
        }
    });

})(jQuery);
