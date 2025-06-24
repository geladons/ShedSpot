/**
 * SchedSpot Profile Management JavaScript
 *
 * @package SchedSpot
 * @version 1.0.0
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initProfileTabs();
        initAvatarUpload();
        initSkillsManagement();
        initAvailabilityScheduler();
        initFormValidation();
        initNotificationSettings();
    });

    /**
     * Initialize profile tabs
     */
    function initProfileTabs() {
        $('.tab-button').on('click', function() {
            const tabId = $(this).data('tab');
            
            // Update active tab button
            $('.tab-button').removeClass('active');
            $(this).addClass('active');
            
            // Show corresponding tab content
            $('.tab-content').removeClass('active');
            $('#' + tabId).addClass('active');
            
            // Update URL hash
            window.location.hash = tabId;
        });

        // Activate tab from URL hash on page load
        const hash = window.location.hash.substring(1);
        if (hash) {
            const $tabButton = $(`.tab-button[data-tab="${hash}"]`);
            if ($tabButton.length) {
                $tabButton.click();
            }
        }
    }

    /**
     * Initialize avatar upload
     */
    function initAvatarUpload() {
        $('#avatar-upload').on('change', function() {
            const file = this.files[0];
            if (file) {
                if (validateImageFile(file)) {
                    previewAvatar(file);
                } else {
                    this.value = '';
                }
            }
        });
    }

    /**
     * Initialize skills management
     */
    function initSkillsManagement() {
        // Add skill
        $('#add-skill-btn').on('click', function() {
            const skillInput = $('#new-skill');
            const skill = skillInput.val().trim();
            
            if (skill && !isSkillExists(skill)) {
                addSkillTag(skill);
                skillInput.val('');
            }
        });

        // Add skill on Enter key
        $('#new-skill').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#add-skill-btn').click();
            }
        });

        // Remove skill
        $(document).on('click', '.schedspot-skill-tag .remove', function() {
            $(this).closest('.schedspot-skill-tag').remove();
            updateSkillsInput();
        });
    }

    /**
     * Initialize availability scheduler
     */
    function initAvailabilityScheduler() {
        // Toggle time slot availability
        $(document).on('click', '.schedspot-time-slot', function() {
            $(this).toggleClass('available');
            updateAvailabilityData();
        });

        // Bulk select for days
        $('.schedspot-day-header').on('click', function() {
            const dayColumn = $(this).closest('.schedspot-day-column');
            const timeSlots = dayColumn.find('.schedspot-time-slot');
            const allAvailable = timeSlots.length === timeSlots.filter('.available').length;
            
            if (allAvailable) {
                timeSlots.removeClass('available');
            } else {
                timeSlots.addClass('available');
            }
            
            updateAvailabilityData();
        });
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        // Profile form submission
        $('#schedspot-profile-form').on('submit', function(e) {
            if (!validateProfileForm()) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const $submitBtn = $(this).find('[type="submit"]');
            $submitBtn.prop('disabled', true);
            $submitBtn.text(schedspot_frontend.strings.saving);
        });

        // Real-time validation
        $('#profile-email').on('blur', function() {
            const email = $(this).val();
            if (email && !isValidEmail(email)) {
                showFieldError($(this), schedspot_frontend.strings.invalid_email);
            } else {
                clearFieldError($(this));
            }
        });

        $('#profile-phone').on('blur', function() {
            const phone = $(this).val();
            if (phone && phone.length < 10) {
                showFieldError($(this), schedspot_frontend.strings.invalid_phone);
            } else {
                clearFieldError($(this));
            }
        });
    }

    /**
     * Initialize notification settings
     */
    function initNotificationSettings() {
        // Toggle switches
        $('.schedspot-toggle input').on('change', function() {
            const setting = $(this).attr('name');
            const value = $(this).is(':checked');
            
            // Save setting immediately
            saveNotificationSetting(setting, value);
        });
    }

    /**
     * Validate image file
     */
    function validateImageFile(file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

        if (file.size > maxSize) {
            showNotification(schedspot_frontend.strings.image_too_large, 'error');
            return false;
        }

        if (!allowedTypes.includes(file.type)) {
            showNotification(schedspot_frontend.strings.invalid_image_type, 'error');
            return false;
        }

        return true;
    }

    /**
     * Preview avatar
     */
    function previewAvatar(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('.schedspot-avatar-preview img').attr('src', e.target.result);
            $('.schedspot-avatar-preview .placeholder').hide();
        };
        reader.readAsDataURL(file);
    }

    /**
     * Check if skill already exists
     */
    function isSkillExists(skill) {
        let exists = false;
        $('.schedspot-skill-tag').each(function() {
            if ($(this).text().trim().replace('×', '').trim().toLowerCase() === skill.toLowerCase()) {
                exists = true;
                return false;
            }
        });
        return exists;
    }

    /**
     * Add skill tag
     */
    function addSkillTag(skill) {
        const skillTag = $(`
            <div class="schedspot-skill-tag">
                ${skill}
                <span class="remove">&times;</span>
            </div>
        `);
        
        $('.schedspot-skills-list').append(skillTag);
        updateSkillsInput();
    }

    /**
     * Update skills hidden input
     */
    function updateSkillsInput() {
        const skills = [];
        $('.schedspot-skill-tag').each(function() {
            const skill = $(this).text().trim().replace('×', '').trim();
            if (skill) {
                skills.push(skill);
            }
        });
        
        $('#skills-input').val(skills.join(','));
    }

    /**
     * Update availability data
     */
    function updateAvailabilityData() {
        const availability = {};
        
        $('.schedspot-day-column').each(function() {
            const day = $(this).data('day');
            const availableSlots = [];
            
            $(this).find('.schedspot-time-slot.available').each(function() {
                availableSlots.push($(this).data('time'));
            });
            
            availability[day] = availableSlots;
        });
        
        $('#availability-data').val(JSON.stringify(availability));
    }

    /**
     * Validate profile form
     */
    function validateProfileForm() {
        let isValid = true;
        
        // Clear previous errors
        $('.error-message').remove();
        $('.schedspot-form-group').removeClass('error');

        // Validate required fields
        $('[required]').each(function() {
            if (!$(this).val().trim()) {
                isValid = false;
                showFieldError($(this), schedspot_frontend.strings.field_required);
            }
        });

        // Validate email
        const email = $('#profile-email').val();
        if (email && !isValidEmail(email)) {
            isValid = false;
            showFieldError($('#profile-email'), schedspot_frontend.strings.invalid_email);
        }

        // Validate phone
        const phone = $('#profile-phone').val();
        if (phone && phone.length < 10) {
            isValid = false;
            showFieldError($('#profile-phone'), schedspot_frontend.strings.invalid_phone);
        }

        return isValid;
    }

    /**
     * Show field error
     */
    function showFieldError($field, message) {
        const $group = $field.closest('.schedspot-form-group');
        $group.addClass('error');
        
        if (!$group.find('.error-message').length) {
            $group.append(`<div class="error-message">${message}</div>`);
        }
    }

    /**
     * Clear field error
     */
    function clearFieldError($field) {
        const $group = $field.closest('.schedspot-form-group');
        $group.removeClass('error');
        $group.find('.error-message').remove();
    }

    /**
     * Save notification setting
     */
    function saveNotificationSetting(setting, value) {
        $.post(schedspot_frontend.ajax_url, {
            action: 'schedspot_save_notification_setting',
            setting: setting,
            value: value ? 1 : 0,
            nonce: schedspot_frontend.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotification(schedspot_frontend.strings.setting_saved, 'success');
            } else {
                showNotification(response.data.message || schedspot_frontend.strings.error, 'error');
            }
        })
        .fail(function() {
            showNotification(schedspot_frontend.strings.error, 'error');
        });
    }

    /**
     * Validate email format
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Show notification
     */
    function showNotification(message, type) {
        // Remove existing notifications
        $('.schedspot-notification').remove();

        const notification = $(`<div class="schedspot-notification ${type}">${message}</div>`);
        $('body').append(notification);

        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Initialize geolocation settings
     */
    function initGeolocationSettings() {
        $('#get-current-location').on('click', function() {
            if (navigator.geolocation) {
                $(this).prop('disabled', true).text(schedspot_frontend.strings.getting_location);
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        $('#latitude').val(position.coords.latitude);
                        $('#longitude').val(position.coords.longitude);
                        
                        // Reverse geocode to get address
                        reverseGeocode(position.coords.latitude, position.coords.longitude);
                        
                        $('#get-current-location').prop('disabled', false).text(schedspot_frontend.strings.get_current_location);
                        showNotification(schedspot_frontend.strings.location_updated, 'success');
                    },
                    function(error) {
                        $('#get-current-location').prop('disabled', false).text(schedspot_frontend.strings.get_current_location);
                        showNotification(schedspot_frontend.strings.location_error, 'error');
                    }
                );
            } else {
                showNotification(schedspot_frontend.strings.geolocation_not_supported, 'error');
            }
        });
    }

    /**
     * Reverse geocode coordinates to address
     */
    function reverseGeocode(lat, lng) {
        // This would typically use Google Maps API or similar service
        // For now, just update the coordinates display
        $('#current-coordinates').text(`${lat.toFixed(6)}, ${lng.toFixed(6)}`);
    }

    // Initialize geolocation if on location tab
    if ($('#location-settings').length) {
        initGeolocationSettings();
    }

    // Export functions for global access
    window.SchedSpotProfile = {
        showNotification: showNotification,
        validateProfileForm: validateProfileForm,
        addSkillTag: addSkillTag,
        updateAvailabilityData: updateAvailabilityData
    };

})(jQuery);
