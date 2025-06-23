/**
 * SchedSpot Messaging Frontend JavaScript
 *
 * @package SchedSpot
 * @version 2.0.0
 */

(function($) {
    'use strict';

    var SchedSpotMessaging = {
        currentConversation: null,
        currentUserId: null,
        messageContainer: null,
        conversationContainer: null,
        messageForm: null,
        refreshInterval: null,

        /**
         * Initialize messaging functionality
         */
        init: function() {
            this.currentUserId = schedspot_messaging.current_user;
            this.bindElements();
            this.bindEvents();
            this.loadConversations();
            this.startAutoRefresh();
        },

        /**
         * Bind DOM elements
         */
        bindElements: function() {
            this.messageContainer = $('#schedspot-messages');
            this.conversationContainer = $('#schedspot-conversations');
            this.messageForm = $('#schedspot-message-form');
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            // Send message form
            $(document).on('submit', '#schedspot-message-form', (e) => {
                e.preventDefault();
                this.sendMessage();
            });

            // Conversation selection
            $(document).on('click', '.schedspot-conversation-item', (e) => {
                e.preventDefault();
                var userId = $(e.currentTarget).data('user-id');
                this.selectConversation(userId);
            });

            // File attachment
            $(document).on('change', '#schedspot-message-attachment', (e) => {
                this.handleFileSelection(e.target.files[0]);
            });

            // Message input auto-resize
            $(document).on('input', '#schedspot-message-content', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Mark messages as read when conversation is viewed
            $(document).on('focus', '#schedspot-message-content', () => {
                if (this.currentConversation) {
                    this.markMessagesAsRead();
                }
            });

            // Keyboard shortcuts
            $(document).on('keydown', '#schedspot-message-content', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        },

        /**
         * Load user conversations
         */
        loadConversations: function() {
            $.post(schedspot_messaging.ajax_url, {
                action: 'schedspot_get_conversations',
                nonce: schedspot_messaging.nonce
            }, (response) => {
                if (response.success) {
                    this.renderConversations(response.data.conversations);
                } else {
                    this.showError(response.data.message);
                }
            });
        },

        /**
         * Render conversations list
         */
        renderConversations: function(conversations) {
            if (!this.conversationContainer.length) return;

            this.conversationContainer.empty();

            if (conversations.length === 0) {
                this.conversationContainer.html('<p class="no-conversations">' + schedspot_messaging.strings.no_messages + '</p>');
                return;
            }

            conversations.forEach((conversation) => {
                var unreadBadge = conversation.unread_count > 0 ? 
                    `<span class="unread-count">${conversation.unread_count}</span>` : '';

                var html = `
                    <div class="schedspot-conversation-item" data-user-id="${conversation.other_user_id}">
                        <div class="user-avatar">
                            <img src="${conversation.other_user_avatar}" alt="${conversation.other_user_name}">
                        </div>
                        <div class="conversation-info">
                            <div class="user-name">${conversation.other_user_name} ${unreadBadge}</div>
                            <div class="last-message">${this.truncateText(conversation.last_message, 50)}</div>
                            <div class="time-ago">${conversation.time_ago} ago</div>
                        </div>
                    </div>
                `;

                this.conversationContainer.append(html);
            });
        },

        /**
         * Select a conversation
         */
        selectConversation: function(userId) {
            this.currentConversation = userId;
            
            // Update UI
            $('.schedspot-conversation-item').removeClass('active');
            $(`.schedspot-conversation-item[data-user-id="${userId}"]`).addClass('active');

            // Load messages
            this.loadMessages(userId);

            // Show message form
            this.showMessageForm(userId);
        },

        /**
         * Load messages for conversation
         */
        loadMessages: function(otherUserId) {
            if (!this.messageContainer.length) return;

            this.messageContainer.html('<div class="loading">' + schedspot_messaging.strings.loading_messages + '</div>');

            $.post(schedspot_messaging.ajax_url, {
                action: 'schedspot_get_messages',
                other_user_id: otherUserId,
                nonce: schedspot_messaging.nonce
            }, (response) => {
                if (response.success) {
                    this.renderMessages(response.data.messages);
                    this.scrollToBottom();
                } else {
                    this.showError(response.data.message);
                }
            });
        },

        /**
         * Render messages
         */
        renderMessages: function(messages) {
            if (!this.messageContainer.length) return;

            this.messageContainer.empty();

            if (messages.length === 0) {
                this.messageContainer.html('<p class="no-messages">' + schedspot_messaging.strings.no_messages + '</p>');
                return;
            }

            messages.forEach((message) => {
                var isOwn = message.sender_id == this.currentUserId;
                var messageClass = isOwn ? 'schedspot-message own' : 'schedspot-message';

                var attachmentHtml = '';
                if (message.attachments && message.attachments.length > 0) {
                    message.attachments.forEach((attachment) => {
                        attachmentHtml += `<div class="schedspot-attachment">
                            <a href="${attachment.file_url}" target="_blank">${attachment.file_name}</a>
                        </div>`;
                    });
                }

                var html = `
                    <div class="${messageClass}" data-message-id="${message.id}">
                        <div class="avatar">
                            <img src="${isOwn ? message.sender_avatar : message.receiver_avatar}" alt="">
                        </div>
                        <div class="content">
                            <div class="text">${this.formatMessageContent(message.content)}</div>
                            ${attachmentHtml}
                            <div class="time">${message.time_ago} ago</div>
                        </div>
                    </div>
                `;

                this.messageContainer.append(html);
            });
        },

        /**
         * Show message form
         */
        showMessageForm: function(receiverId) {
            if (!this.messageForm.length) return;

            this.messageForm.find('#schedspot-receiver-id').val(receiverId);
            this.messageForm.show();
        },

        /**
         * Send message
         */
        sendMessage: function() {
            var content = $('#schedspot-message-content').val().trim();
            var receiverId = $('#schedspot-receiver-id').val();
            var attachment = $('#schedspot-message-attachment')[0].files[0];

            if (!content && !attachment) {
                return;
            }

            var formData = new FormData();
            formData.append('action', 'schedspot_send_message');
            formData.append('receiver_id', receiverId);
            formData.append('content', content);
            formData.append('nonce', schedspot_messaging.nonce);

            if (attachment) {
                formData.append('attachment', attachment);
            }

            // Disable form
            this.messageForm.find('button').prop('disabled', true).text(schedspot_messaging.strings.sending);

            $.ajax({
                url: schedspot_messaging.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        // Clear form
                        $('#schedspot-message-content').val('');
                        $('#schedspot-message-attachment').val('');
                        
                        // Reload messages
                        this.loadMessages(receiverId);
                        
                        // Update conversations
                        this.loadConversations();
                        
                        this.showSuccess(schedspot_messaging.strings.message_sent);
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: () => {
                    this.showError(schedspot_messaging.strings.send_failed);
                },
                complete: () => {
                    // Re-enable form
                    this.messageForm.find('button').prop('disabled', false).text(schedspot_messaging.strings.send_message);
                }
            });
        },

        /**
         * Handle file selection
         */
        handleFileSelection: function(file) {
            if (!file) return;

            // Validate file size (5MB limit)
            if (file.size > 5 * 1024 * 1024) {
                this.showError(schedspot_messaging.strings.file_too_large);
                $('#schedspot-message-attachment').val('');
                return;
            }

            // Validate file type
            var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
            if (!allowedTypes.includes(file.type)) {
                this.showError(schedspot_messaging.strings.invalid_file_type);
                $('#schedspot-message-attachment').val('');
                return;
            }

            // Show file name
            $('.attachment-preview').remove();
            this.messageForm.append(`<div class="attachment-preview">Selected: ${file.name}</div>`);
        },

        /**
         * Mark messages as read
         */
        markMessagesAsRead: function() {
            if (!this.currentConversation) return;

            var unreadMessages = [];
            $('.schedspot-message:not(.own)').each(function() {
                unreadMessages.push($(this).data('message-id'));
            });

            if (unreadMessages.length === 0) return;

            $.post(schedspot_messaging.ajax_url, {
                action: 'schedspot_mark_messages_read',
                message_ids: unreadMessages,
                nonce: schedspot_messaging.nonce
            });
        },

        /**
         * Start auto-refresh for new messages
         */
        startAutoRefresh: function() {
            this.refreshInterval = setInterval(() => {
                if (this.currentConversation) {
                    this.loadMessages(this.currentConversation);
                }
                this.loadConversations();
            }, 30000); // Refresh every 30 seconds
        },

        /**
         * Utility functions
         */
        scrollToBottom: function() {
            if (this.messageContainer.length) {
                this.messageContainer.scrollTop(this.messageContainer[0].scrollHeight);
            }
        },

        truncateText: function(text, length) {
            return text.length > length ? text.substring(0, length) + '...' : text;
        },

        formatMessageContent: function(content) {
            return content.replace(/\n/g, '<br>');
        },

        showError: function(message) {
            // You can customize this to show errors in your preferred way
            alert(message);
        },

        showSuccess: function(message) {
            // You can customize this to show success messages in your preferred way
            console.log(message);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if (typeof schedspot_messaging !== 'undefined') {
            SchedSpotMessaging.init();
        }
    });

    // Make available globally
    window.SchedSpotMessaging = SchedSpotMessaging;

})(jQuery);
