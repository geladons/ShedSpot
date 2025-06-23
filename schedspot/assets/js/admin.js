/**
 * SchedSpot Admin JavaScript
 *
 * @package SchedSpot
 * @version 1.6.1
 */

(function($) {
    'use strict';

    /**
     * SchedSpot Admin object
     */
    var SchedSpotAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initComponents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Role switcher functionality
            $(document).on('click', '.schedspot-role-switch', this.handleRoleSwitch);
            
            // Form validation
            $(document).on('submit', '.schedspot-admin-form', this.validateForm);
            
            // Confirmation dialogs
            $(document).on('click', '.schedspot-confirm-action', this.confirmAction);
        },

        /**
         * Initialize components
         */
        initComponents: function() {
            // Initialize any admin components here
            this.initTables();
            this.initDatePickers();
        },

        /**
         * Initialize data tables
         */
        initTables: function() {
            if ($.fn.DataTable) {
                $('.schedspot-data-table').DataTable({
                    responsive: true,
                    pageLength: 25,
                    order: [[0, 'desc']]
                });
            }
        },

        /**
         * Initialize date pickers
         */
        initDatePickers: function() {
            if ($.fn.datepicker) {
                $('.schedspot-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }
        },

        /**
         * Handle role switch
         */
        handleRoleSwitch: function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var role = $this.data('role');
            
            if (!role) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'schedspot_switch_role',
                    role: role,
                    nonce: schedspot_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error switching role');
                    }
                },
                error: function() {
                    alert('Error switching role');
                }
            });
        },

        /**
         * Validate forms
         */
        validateForm: function(e) {
            var $form = $(this);
            var isValid = true;

            // Check required fields
            $form.find('[required]').each(function() {
                var $field = $(this);
                if (!$field.val().trim()) {
                    $field.addClass('error');
                    isValid = false;
                } else {
                    $field.removeClass('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        },

        /**
         * Confirm actions
         */
        confirmAction: function(e) {
            var message = $(this).data('confirm') || 'Are you sure?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        SchedSpotAdmin.init();
    });

})(jQuery);
