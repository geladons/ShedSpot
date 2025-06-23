/**
 * SchedSpot Profile Management JavaScript
 * 
 * @package SchedSpot
 * @version 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Initialize profile functionality
     */
    function initProfile() {
        // Tab switching functionality
        $('.tab-button').on('click', handleTabSwitch);
        
        // Form submissions
        $('.profile-form').on('submit', handleFormSubmission);
        
        // Skills management
        $('.add-skill-btn').on('click', handleAddSkill);
        $(document).on('click', '.remove-skill', handleRemoveSkill);
        $('.add-skill-input input').on('keypress', handleSkillInputKeypress);
        
        // Avatar upload
        $('.avatar-upload-btn').on('click', handleAvatarUpload);
        $('#avatar-file-input').on('change', handleAvatarFileSelect);
        
        // Data export/delete
        $('.export-data-btn').on('click', handleDataExport);
        $('.delete-account-btn').on('click', handleAccountDeletion);
        
        // Form validation
        $('.profile-form input, .profile-form select, .profile-form textarea').on('blur', validateField);
        
        // Auto-save for certain fields
        $('.auto-save').on('change', handleAutoSave);
        
        // Initialize skill tags
        initializeSkillTags();
    }

    /**
     * Handle tab switching
     */
    function handleTabSwitch() {
        const tabId = $(this).data('tab');
        
        // Update active tab
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Show target content
        $('.tab-content').removeClass('active');
        $(`#${tabId}`).addClass('active');
        
        // Load tab-specific data if needed
        loadTabData(tabId);
    }

    /**
     * Load tab-specific data
     */
    function loadTabData(tabId) {
        switch (tabId) {
            case 'worker-profile':
                loadWorkerProfileData();
                break;
            case 'notifications':
                loadNotificationSettings();
                break;
            case 'privacy':
                loadPrivacySettings();
                break;
        }
    }

    /**
     * Handle form submission
     */
    function handleFormSubmission(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('[type="submit"]');
        const formData = new FormData(this);
        
        // Validate form
        if (!validateForm($form)) {
            return false;
        }
        
        // Show loading state
        $submitBtn.prop('disabled', true);
        const originalText = $submitBtn.html();
        $submitBtn.html('<span class="dashicons dashicons-update spin"></span> Saving...');
        
        // Determine endpoint based on form
        let endpoint = 'users/profile';
        if ($form.hasClass('worker-profile-form')) {
            endpoint = 'workers/profile';
        } else if ($form.hasClass('notification-settings-form')) {
            endpoint = 'users/notifications';
        } else if ($form.hasClass('privacy-settings-form')) {
            endpoint = 'users/privacy';
        }
        
        $.ajax({
            url: schedspot_frontend.rest_url + endpoint,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Profile updated successfully!', 'success');
                    
                    // Update UI with new data if provided
                    if (response.data) {
                        updateProfileUI(response.data);
                    }
                } else {
                    showMessage(response.message || 'Failed to update profile', 'error');
                }
            },
            error: function(xhr) {
                let message = 'Error updating profile';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showMessage(message, 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false);
                $submitBtn.html(originalText);
            }
        });
    }

    /**
     * Handle add skill
     */
    function handleAddSkill() {
        const $input = $('.add-skill-input input');
        const skill = $input.val().trim();
        
        if (skill && !isSkillAlreadyAdded(skill)) {
            addSkillTag(skill);
            $input.val('');
        }
    }

    /**
     * Handle skill input keypress
     */
    function handleSkillInputKeypress(e) {
        if (e.which === 13) {
            e.preventDefault();
            handleAddSkill();
        }
    }

    /**
     * Handle remove skill
     */
    function handleRemoveSkill() {
        $(this).closest('.skill-tag').remove();
        updateSkillsInput();
    }

    /**
     * Add skill tag
     */
    function addSkillTag(skill) {
        const skillHtml = `
            <div class="skill-tag selected">
                ${escapeHtml(skill)}
                <button type="button" class="remove-skill">&times;</button>
            </div>
        `;
        
        $('.skills-container').append(skillHtml);
        updateSkillsInput();
    }

    /**
     * Check if skill is already added
     */
    function isSkillAlreadyAdded(skill) {
        let exists = false;
        $('.skill-tag').each(function() {
            if ($(this).text().trim().replace('×', '') === skill) {
                exists = true;
                return false;
            }
        });
        return exists;
    }

    /**
     * Update skills hidden input
     */
    function updateSkillsInput() {
        const skills = [];
        $('.skill-tag.selected').each(function() {
            const skill = $(this).text().trim().replace('×', '');
            if (skill) {
                skills.push(skill);
            }
        });
        
        $('input[name="skills"]').val(skills.join(','));
    }

    /**
     * Initialize skill tags
     */
    function initializeSkillTags() {
        // Make existing skill tags selectable
        $('.skill-tag').on('click', function() {
            $(this).toggleClass('selected');
            updateSkillsInput();
        });
        
        // Initialize skills input
        updateSkillsInput();
    }

    /**
     * Handle avatar upload
     */
    function handleAvatarUpload() {
        $('#avatar-file-input').click();
    }

    /**
     * Handle avatar file select
     */
    function handleAvatarFileSelect() {
        const file = this.files[0];
        if (!file) return;
        
        // Validate file type
        if (!file.type.startsWith('image/')) {
            showMessage('Please select an image file', 'error');
            return;
        }
        
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            showMessage('Image file must be less than 5MB', 'error');
            return;
        }
        
        // Preview image
        const reader = new FileReader();
        reader.onload = function(e) {
            $('.current-avatar').attr('src', e.target.result);
        };
        reader.readAsDataURL(file);
        
        // Upload immediately
        uploadAvatar(file);
    }

    /**
     * Upload avatar
     */
    function uploadAvatar(file) {
        const formData = new FormData();
        formData.append('avatar', file);
        
        $.ajax({
            url: schedspot_frontend.rest_url + 'users/avatar',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Avatar updated successfully!', 'success');
                    $('.current-avatar').attr('src', response.data.avatar_url);
                } else {
                    showMessage(response.message || 'Failed to upload avatar', 'error');
                }
            },
            error: function() {
                showMessage('Error uploading avatar', 'error');
            }
        });
    }

    /**
     * Handle data export
     */
    function handleDataExport() {
        if (!confirm(schedspot_frontend.strings.confirm_data_export)) {
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true);
        $btn.html('<span class="dashicons dashicons-update spin"></span> Requesting...');
        
        $.ajax({
            url: schedspot_frontend.rest_url + 'users/export-data',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Data export requested. You will receive an email with download instructions.', 'success');
                } else {
                    showMessage(response.message || 'Failed to request data export', 'error');
                }
            },
            error: function() {
                showMessage('Error requesting data export', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.html('<span class="dashicons dashicons-download"></span> Export My Data');
            }
        });
    }

    /**
     * Handle account deletion
     */
    function handleAccountDeletion() {
        if (!confirm(schedspot_frontend.strings.confirm_account_deletion)) {
            return;
        }
        
        // Double confirmation
        if (!confirm('This action cannot be undone. Are you absolutely sure?')) {
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true);
        $btn.html('<span class="dashicons dashicons-update spin"></span> Deleting...');
        
        $.ajax({
            url: schedspot_frontend.rest_url + 'users/delete-account',
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Account deletion initiated. You will be logged out shortly.', 'success');
                    setTimeout(function() {
                        window.location.href = '/';
                    }, 3000);
                } else {
                    showMessage(response.message || 'Failed to delete account', 'error');
                }
            },
            error: function() {
                showMessage('Error deleting account', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.html('<span class="dashicons dashicons-trash"></span> Delete Account');
            }
        });
    }

    /**
     * Validate form
     */
    function validateForm($form) {
        let isValid = true;
        
        $form.find('[required]').each(function() {
            if (!validateField.call(this)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    /**
     * Validate individual field
     */
    function validateField() {
        const $field = $(this);
        const value = $field.val().trim();
        const fieldType = $field.attr('type');
        const isRequired = $field.prop('required');
        
        // Remove previous error
        $field.removeClass('error');
        $field.siblings('.error-message').remove();
        
        // Check required
        if (isRequired && !value) {
            showFieldError($field, 'This field is required');
            return false;
        }
        
        // Type-specific validation
        if (value) {
            if (fieldType === 'email' && !isValidEmail(value)) {
                showFieldError($field, 'Please enter a valid email address');
                return false;
            }
            
            if (fieldType === 'tel' && value.length < 10) {
                showFieldError($field, 'Please enter a valid phone number');
                return false;
            }
            
            if (fieldType === 'url' && !isValidUrl(value)) {
                showFieldError($field, 'Please enter a valid URL');
                return false;
            }
        }
        
        return true;
    }

    /**
     * Show field error
     */
    function showFieldError($field, message) {
        $field.addClass('error');
        $field.after(`<div class="error-message">${message}</div>`);
    }

    /**
     * Handle auto-save
     */
    function handleAutoSave() {
        const $field = $(this);
        const fieldName = $field.attr('name');
        const value = $field.val();
        
        // Debounce auto-save
        clearTimeout($field.data('autoSaveTimeout'));
        $field.data('autoSaveTimeout', setTimeout(function() {
            autoSaveField(fieldName, value);
        }, 1000));
    }

    /**
     * Auto-save field
     */
    function autoSaveField(fieldName, value) {
        $.ajax({
            url: schedspot_frontend.rest_url + 'users/profile',
            method: 'POST',
            data: {
                [fieldName]: value
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', schedspot_frontend.nonce);
            },
            success: function(response) {
                if (response.success) {
                    // Show subtle success indicator
                    showAutoSaveSuccess(fieldName);
                }
            }
        });
    }

    /**
     * Show auto-save success
     */
    function showAutoSaveSuccess(fieldName) {
        const $field = $(`[name="${fieldName}"]`);
        $field.addClass('auto-saved');
        setTimeout(function() {
            $field.removeClass('auto-saved');
        }, 2000);
    }

    /**
     * Update profile UI
     */
    function updateProfileUI(data) {
        // Update any UI elements with new data
        if (data.avatar_url) {
            $('.current-avatar').attr('src', data.avatar_url);
        }
    }

    /**
     * Show message
     */
    function showMessage(message, type) {
        // Remove existing messages
        $('.profile-message').remove();
        
        const messageHtml = `
            <div class="profile-message ${type}">
                ${message}
            </div>
        `;
        
        $('.tab-content.active').prepend(messageHtml);
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(function() {
                $('.profile-message.success').fadeOut();
            }, 5000);
        }
    }

    /**
     * Validate email
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Validate URL
     */
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.schedspot-profile-content').length) {
            initProfile();
        }
    });

})(jQuery);
