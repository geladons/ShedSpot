/**
 * SchedSpot Messaging System JavaScript
 *
 * @package SchedSpot
 * @version 1.0.0
 */

(function($) {
    'use strict';

    let currentConversationId = null;
    let messagePollingInterval = null;

    // Initialize when document is ready
    $(document).ready(function() {
        initMessaging();
        initConversationHandlers();
        initMessageForm();
        initFileUpload();
        
        // Auto-select conversation if user_id is specified in URL
        const urlParams = new URLSearchParams(window.location.search);
        const targetUserId = urlParams.get('user_id');
        if (targetUserId) {
            setTimeout(function() {
                selectConversation(parseInt(targetUserId));
            }, 1000);
        }
    });

    /**
     * Initialize messaging system
     */
    function initMessaging() {
        // Load conversations list
        loadConversations();

        // Start polling for new messages every 30 seconds
        messagePollingInterval = setInterval(function() {
            if (currentConversationId) {
                loadMessages(currentConversationId, false);
            }
            updateConversationsList();
        }, 30000);
    }

    /**
     * Initialize conversation handlers
     */
    function initConversationHandlers() {
        // Conversation item click
        $(document).on('click', '.schedspot-conversation-item', function() {
            const userId = $(this).data('user-id');
            selectConversation(userId);
        });

        // Mark conversation as read when selected
        $(document).on('click', '.schedspot-conversation-item', function() {
            markConversationAsRead($(this));
        });
    }

    /**
     * Initialize message form
     */
    function initMessageForm() {
        // Send message on form submit
        $('#schedspot-message-form').on('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });

        // Send message on Enter key (but not Shift+Enter)
        $('#message-text').on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Auto-resize textarea
        $('#message-text').on('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
    }

    /**
     * Initialize file upload
     */
    function initFileUpload() {
        $('#message-attachment').on('change', function() {
            const file = this.files[0];
            if (file) {
                // Validate file size and type
                if (validateFile(file)) {
                    showFilePreview(file);
                } else {
                    this.value = '';
                }
            }
        });
    }

    /**
     * Load conversations list
     */
    function loadConversations() {
        $.get(schedspot_frontend.rest_url + 'messages', {
            _wpnonce: schedspot_frontend.nonce
        })
        .done(function(data) {
            renderConversations(data);
        })
        .fail(function() {
            showNotification(schedspot_frontend.strings.error_loading_conversations, 'error');
        });
    }

    /**
     * Select conversation
     */
    function selectConversation(userId) {
        currentConversationId = userId;

        // Update UI
        $('.schedspot-conversation-item').removeClass('active');
        $(`.schedspot-conversation-item[data-user-id="${userId}"]`).addClass('active');

        // Load messages
        loadMessages(userId, true);

        // Show chat area
        $('.schedspot-chat-area').show();
        $('.schedspot-no-conversation').hide();
    }

    /**
     * Load messages for conversation
     */
    function loadMessages(userId, scrollToBottom = true) {
        $.get(schedspot_frontend.rest_url + 'conversations/' + userId, {
            _wpnonce: schedspot_frontend.nonce
        })
        .done(function(data) {
            renderMessages(data.messages, data.user);
            if (scrollToBottom) {
                scrollMessagesToBottom();
            }
        })
        .fail(function() {
            showNotification(schedspot_frontend.strings.error_loading_messages, 'error');
        });
    }

    /**
     * Send message
     */
    function sendMessage() {
        const messageText = $('#message-text').val().trim();
        const attachment = $('#message-attachment')[0].files[0];

        if (!messageText && !attachment) {
            return;
        }

        if (!currentConversationId) {
            showNotification(schedspot_frontend.strings.select_conversation, 'error');
            return;
        }

        // Disable send button
        const $sendBtn = $('.send-button');
        $sendBtn.prop('disabled', true);

        // Prepare form data
        const formData = new FormData();
        formData.append('recipient_id', currentConversationId);
        formData.append('content', messageText);
        if (attachment) {
            formData.append('attachment', attachment);
        }

        // Send message
        $.ajax({
            url: schedspot_frontend.rest_url + 'messages',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-WP-Nonce': schedspot_frontend.nonce
            }
        })
        .done(function(data) {
            // Clear form
            $('#message-text').val('');
            $('#message-attachment').val('');
            $('.file-preview').remove();

            // Reload messages
            loadMessages(currentConversationId, true);

            // Update conversations list
            updateConversationsList();
        })
        .fail(function(xhr) {
            const response = xhr.responseJSON;
            const message = response && response.message ? 
                response.message : 
                schedspot_frontend.strings.error_sending_message;
            showNotification(message, 'error');
        })
        .always(function() {
            // Re-enable send button
            $sendBtn.prop('disabled', false);
        });
    }

    /**
     * Render conversations list
     */
    function renderConversations(conversations) {
        const $list = $('.schedspot-conversations-list');
        $list.empty();

        if (!conversations || conversations.length === 0) {
            $list.append('<div class="no-conversations">' + schedspot_frontend.strings.no_conversations + '</div>');
            return;
        }

        conversations.forEach(function(conversation) {
            const unreadCount = conversation.unread_count > 0 ? 
                `<span class="unread-count">${conversation.unread_count}</span>` : '';

            const conversationHtml = `
                <div class="schedspot-conversation-item" data-user-id="${conversation.user_id}">
                    <div class="user-avatar">
                        <img src="${conversation.avatar}" alt="${conversation.name}">
                    </div>
                    <div class="conversation-info">
                        <div class="user-name">
                            ${conversation.name}
                            ${unreadCount}
                        </div>
                        <div class="last-message">${conversation.last_message || ''}</div>
                        <div class="time-ago">${conversation.time_ago || ''}</div>
                    </div>
                </div>
            `;
            $list.append(conversationHtml);
        });
    }

    /**
     * Render messages
     */
    function renderMessages(messages, user) {
        const $messages = $('.schedspot-messages');
        $messages.empty();

        // Update chat header
        $('.schedspot-chat-header h3').text(user.name);

        if (!messages || messages.length === 0) {
            $messages.append('<div class="no-messages">' + schedspot_frontend.strings.no_messages + '</div>');
            return;
        }

        messages.forEach(function(message) {
            const isOwn = message.sender_id == schedspot_frontend.current_user_id;
            const messageClass = isOwn ? 'schedspot-message own' : 'schedspot-message';

            let attachmentHtml = '';
            if (message.attachment_url) {
                attachmentHtml = `
                    <div class="schedspot-attachment">
                        <a href="${message.attachment_url}" target="_blank">
                            📎 ${message.attachment_name || 'Attachment'}
                        </a>
                    </div>
                `;
            }

            const messageHtml = `
                <div class="${messageClass}">
                    <div class="avatar">
                        <img src="${message.sender_avatar}" alt="${message.sender_name}">
                    </div>
                    <div class="content">
                        <div class="text">${message.content}</div>
                        ${attachmentHtml}
                        <div class="time">${message.time_ago}</div>
                    </div>
                </div>
            `;
            $messages.append(messageHtml);
        });
    }

    /**
     * Update conversations list (refresh unread counts)
     */
    function updateConversationsList() {
        $.get(schedspot_frontend.rest_url + 'messages', {
            _wpnonce: schedspot_frontend.nonce
        })
        .done(function(data) {
            // Update unread counts without full re-render
            data.forEach(function(conversation) {
                const $item = $(`.schedspot-conversation-item[data-user-id="${conversation.user_id}"]`);
                const $unreadCount = $item.find('.unread-count');
                
                if (conversation.unread_count > 0) {
                    if ($unreadCount.length) {
                        $unreadCount.text(conversation.unread_count);
                    } else {
                        $item.find('.user-name').append(`<span class="unread-count">${conversation.unread_count}</span>`);
                    }
                } else {
                    $unreadCount.remove();
                }
            });
        });
    }

    /**
     * Mark conversation as read
     */
    function markConversationAsRead($conversationItem) {
        $conversationItem.find('.unread-count').remove();
    }

    /**
     * Scroll messages to bottom
     */
    function scrollMessagesToBottom() {
        const $messages = $('.schedspot-messages');
        $messages.scrollTop($messages[0].scrollHeight);
    }

    /**
     * Validate file upload
     */
    function validateFile(file) {
        const maxSize = schedspot_frontend.max_file_size * 1024 * 1024; // Convert MB to bytes
        const allowedTypes = schedspot_frontend.allowed_file_types.split(',');

        if (file.size > maxSize) {
            showNotification(schedspot_frontend.strings.file_too_large, 'error');
            return false;
        }

        const fileExtension = file.name.split('.').pop().toLowerCase();
        if (!allowedTypes.includes(fileExtension)) {
            showNotification(schedspot_frontend.strings.file_type_not_allowed, 'error');
            return false;
        }

        return true;
    }

    /**
     * Show file preview
     */
    function showFilePreview(file) {
        $('.file-preview').remove();
        
        const preview = $(`
            <div class="file-preview">
                📎 ${file.name} (${formatFileSize(file.size)})
                <button type="button" class="remove-file">&times;</button>
            </div>
        `);
        
        $('#message-attachment').after(preview);
        
        preview.find('.remove-file').on('click', function() {
            $('#message-attachment').val('');
            preview.remove();
        });
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
     * Show notification
     */
    function showNotification(message, type) {
        const notification = $(`<div class="schedspot-notification ${type}">${message}</div>`);
        $('body').append(notification);

        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        if (messagePollingInterval) {
            clearInterval(messagePollingInterval);
        }
    });

    // Export functions for global access
    window.SchedSpotMessaging = {
        selectConversation: selectConversation,
        sendMessage: sendMessage,
        loadMessages: loadMessages,
        showNotification: showNotification
    };

})(jQuery);
