/**
 * SchedSpot Navigation JavaScript
 * Modern, responsive navigation functionality
 */

(function($) {
    'use strict';

    class SchedSpotNavigation {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.setupBodyPadding();
            this.handleClickOutside();
            this.updateUnreadCount();
        }

        bindEvents() {
            // Toggle menu
            $(document).on('click', '#schedspot-nav-toggle', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleMenu();
            });

            // Close menu when clicking navigation links
            $(document).on('click', '.nav-section-items a', () => {
                this.closeMenu();
            });

            // Handle responsive behavior
            $(window).on('resize', () => {
                this.handleResize();
            });

            // Update unread messages count periodically
            if (schedspot_nav.is_logged_in) {
                setInterval(() => {
                    this.updateUnreadCount();
                }, 30000); // Update every 30 seconds
            }
        }

        toggleMenu() {
            const toggle = $('#schedspot-nav-toggle');
            const dropdown = $('#schedspot-nav-dropdown');

            if (toggle.hasClass('active')) {
                this.closeMenu();
            } else {
                this.openMenu();
            }
        }

        openMenu() {
            const toggle = $('#schedspot-nav-toggle');
            const dropdown = $('#schedspot-nav-dropdown');

            toggle.addClass('active');
            dropdown.addClass('active');

            // Add body class for styling
            $('body').addClass('schedspot-nav-open');

            // Focus management for accessibility
            dropdown.find('a').first().focus();

            // Trigger custom event
            $(document).trigger('schedspot:navigation:opened');
        }

        closeMenu() {
            const toggle = $('#schedspot-nav-toggle');
            const dropdown = $('#schedspot-nav-dropdown');

            toggle.removeClass('active');
            dropdown.removeClass('active');

            // Remove body class
            $('body').removeClass('schedspot-nav-open');

            // Trigger custom event
            $(document).trigger('schedspot:navigation:closed');
        }

        handleClickOutside() {
            $(document).on('click', (e) => {
                const nav = $('.schedspot-navigation');
                const toggle = $('#schedspot-nav-toggle');

                if (!nav.is(e.target) && nav.has(e.target).length === 0) {
                    if (toggle.hasClass('active')) {
                        this.closeMenu();
                    }
                }
            });
        }

        handleResize() {
            // Close menu on resize to prevent layout issues
            if ($('#schedspot-nav-toggle').hasClass('active')) {
                this.closeMenu();
            }
        }

        setupBodyPadding() {
            // Add padding to body to account for fixed navigation
            if ($('.schedspot-navigation').length > 0) {
                $('body').addClass('schedspot-nav-active');
            }
        }

        updateUnreadCount() {
            if (!schedspot_nav.is_logged_in) {
                return;
            }

            $.ajax({
                url: schedspot_nav.ajax_url,
                type: 'POST',
                data: {
                    action: 'schedspot_get_unread_count',
                    nonce: schedspot_nav.nonce
                },
                success: (response) => {
                    if (response.success && response.data.count !== undefined) {
                        this.updateUnreadBadge(response.data.count);
                    }
                },
                error: (xhr, status, error) => {
                    console.log('Error updating unread count:', error);
                }
            });
        }

        updateUnreadBadge(count) {
            const messageLink = $('.nav-section-items a[href*="messages"]');
            let badge = messageLink.find('.nav-badge');

            if (count > 0) {
                if (badge.length === 0) {
                    badge = $('<span class="nav-badge"></span>');
                    messageLink.append(badge);
                }
                badge.text(count).show();
            } else {
                badge.hide();
            }
        }

        // Public methods for external use
        static getInstance() {
            if (!window.schedspotNavigation) {
                window.schedspotNavigation = new SchedSpotNavigation();
            }
            return window.schedspotNavigation;
        }

        // Keyboard navigation support
        handleKeyboardNavigation() {
            $(document).on('keydown', (e) => {
                const dropdown = $('#schedspot-nav-dropdown');
                
                if (!dropdown.hasClass('active')) {
                    return;
                }

                switch (e.key) {
                    case 'Escape':
                        e.preventDefault();
                        this.closeMenu();
                        $('#schedspot-nav-toggle').focus();
                        break;
                    
                    case 'ArrowDown':
                        e.preventDefault();
                        this.focusNextItem();
                        break;
                    
                    case 'ArrowUp':
                        e.preventDefault();
                        this.focusPreviousItem();
                        break;
                    
                    case 'Tab':
                        // Allow natural tab behavior within dropdown
                        const focusableElements = dropdown.find('a, button, input, select, textarea');
                        const firstElement = focusableElements.first();
                        const lastElement = focusableElements.last();
                        
                        if (e.shiftKey && $(document.activeElement).is(firstElement)) {
                            e.preventDefault();
                            lastElement.focus();
                        } else if (!e.shiftKey && $(document.activeElement).is(lastElement)) {
                            e.preventDefault();
                            firstElement.focus();
                        }
                        break;
                }
            });
        }

        focusNextItem() {
            const focusableElements = $('#schedspot-nav-dropdown').find('a');
            const currentIndex = focusableElements.index($(document.activeElement));
            const nextIndex = (currentIndex + 1) % focusableElements.length;
            focusableElements.eq(nextIndex).focus();
        }

        focusPreviousItem() {
            const focusableElements = $('#schedspot-nav-dropdown').find('a');
            const currentIndex = focusableElements.index($(document.activeElement));
            const prevIndex = currentIndex <= 0 ? focusableElements.length - 1 : currentIndex - 1;
            focusableElements.eq(prevIndex).focus();
        }

        // Animation helpers
        animateIn(element, callback) {
            element.css({
                opacity: 0,
                transform: 'translateY(-10px)'
            }).animate({
                opacity: 1
            }, 300, () => {
                element.css('transform', 'translateY(0)');
                if (callback) callback();
            });
        }

        animateOut(element, callback) {
            element.animate({
                opacity: 0
            }, 200, () => {
                element.css('transform', 'translateY(-10px)');
                if (callback) callback();
            });
        }

        // Utility methods
        showNotification(message, type = 'info') {
            const notification = $(`
                <div class="schedspot-nav-notification ${type}">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">&times;</button>
                </div>
            `);

            $('body').append(notification);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                notification.fadeOut(() => {
                    notification.remove();
                });
            }, 5000);

            // Manual close
            notification.find('.notification-close').on('click', () => {
                notification.fadeOut(() => {
                    notification.remove();
                });
            });
        }

        // Check if user needs to login for certain actions
        requireLogin(callback) {
            if (!schedspot_nav.is_logged_in) {
                this.showNotification(schedspot_nav.strings.login_required, 'warning');
                return false;
            }
            
            if (callback) {
                callback();
            }
            return true;
        }
    }

    // Initialize navigation when DOM is ready
    $(document).ready(() => {
        const navigation = SchedSpotNavigation.getInstance();
        navigation.handleKeyboardNavigation();
        
        // Make navigation globally accessible
        window.SchedSpotNavigation = SchedSpotNavigation;
    });

    // Handle AJAX for unread count
    if (typeof schedspot_nav !== 'undefined' && schedspot_nav.is_logged_in) {
        // Add AJAX handler for unread count
        $(document).ajaxComplete((event, xhr, settings) => {
            // Update unread count after certain AJAX actions
            if (settings.data && settings.data.includes('schedspot_send_message')) {
                setTimeout(() => {
                    SchedSpotNavigation.getInstance().updateUnreadCount();
                }, 1000);
            }
        });
    }

})(jQuery);

// CSS for notifications
const notificationCSS = `
<style>
.schedspot-nav-notification {
    position: fixed;
    top: 80px;
    right: 20px;
    background: white;
    border-radius: 8px;
    padding: 16px 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    z-index: 10000;
    display: flex;
    align-items: center;
    gap: 12px;
    max-width: 300px;
    border-left: 4px solid #007cba;
}

.schedspot-nav-notification.warning {
    border-left-color: #f0ad4e;
}

.schedspot-nav-notification.error {
    border-left-color: #d9534f;
}

.schedspot-nav-notification.success {
    border-left-color: #5cb85c;
}

.notification-message {
    flex: 1;
    font-size: 14px;
    color: #333;
}

.notification-close {
    background: none;
    border: none;
    font-size: 18px;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-close:hover {
    color: #333;
}

@media (max-width: 768px) {
    .schedspot-nav-notification {
        right: 16px;
        left: 16px;
        max-width: none;
    }
}
</style>
`;

// Inject notification CSS
if (!document.getElementById('schedspot-nav-notification-css')) {
    const style = document.createElement('div');
    style.id = 'schedspot-nav-notification-css';
    style.innerHTML = notificationCSS;
    document.head.appendChild(style);
}
