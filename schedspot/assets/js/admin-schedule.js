/**
 * SchedSpot Admin Schedule Management JavaScript
 *
 * @package SchedSpot
 * @version 1.7.0
 */

jQuery(document).ready(function($) {
    'use strict';

    // Initialize schedule management
    var ScheduleManager = {
        currentWorkerId: 0,
        currentMonth: new Date(),

        init: function() {
            this.bindEvents();
            this.initializeTabs();
            this.loadInitialData();
        },

        bindEvents: function() {
            // Worker selection
            $('#load-schedule').on('click', this.loadWorkerSchedule.bind(this));
            $('#worker-select').on('change', function() {
                var workerId = $(this).val();
                if (workerId) {
                    window.location.href = window.location.pathname + '?page=schedspot-schedules&worker_id=' + workerId;
                }
            });

            // Schedule management
            $('#save-schedule').on('click', this.saveSchedule.bind(this));
            $('#bulk-update').on('click', this.showBulkUpdateModal.bind(this));

            // Weekly schedule events
            $(document).on('change', '.day-enabled', this.toggleDayAvailability);
            $(document).on('click', '.add-slot', this.addTimeSlot);
            $(document).on('click', '.remove-slot', this.removeTimeSlot);

            // Exception events
            $('#exception-type').on('change', this.toggleCustomHours);
            $('#add-exception').on('click', this.addException.bind(this));
            $(document).on('click', '.remove-exception', this.removeException.bind(this));

            // Calendar navigation
            $('#prev-month').on('click', this.previousMonth.bind(this));
            $('#next-month').on('click', this.nextMonth.bind(this));

            // Tab switching
            $('.nav-tab').on('click', this.switchTab);
        },

        initializeTabs: function() {
            $('.nav-tab').first().addClass('nav-tab-active');
            $('.tab-content').first().addClass('active');
        },

        loadInitialData: function() {
            var workerId = $('#schedule-container').data('worker-id');
            if (workerId) {
                this.currentWorkerId = workerId;
                this.renderCalendar();
            }
        },

        loadWorkerSchedule: function() {
            var workerId = $('#worker-select').val();
            if (!workerId) {
                alert('Please select a worker first.');
                return;
            }

            this.showLoading();

            $.ajax({
                url: schedspot_schedule.ajax_url,
                type: 'POST',
                data: {
                    action: 'schedspot_get_worker_schedule',
                    worker_id: workerId,
                    nonce: schedspot_schedule.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.currentWorkerId = workerId;
                        this.renderScheduleInterface(response.data);
                        $('#schedule-container').show();
                        $('.schedspot-no-worker').hide();
                    } else {
                        this.showError(response.data.message);
                    }
                }.bind(this),
                error: function() {
                    this.showError('Failed to load schedule. Please try again.');
                }.bind(this),
                complete: function() {
                    this.hideLoading();
                }.bind(this)
            });
        },

        saveSchedule: function() {
            if (!this.currentWorkerId) {
                alert('No worker selected.');
                return;
            }

            var scheduleData = this.collectScheduleData();
            this.showLoading();

            $.ajax({
                url: schedspot_schedule.ajax_url,
                type: 'POST',
                data: {
                    action: 'schedspot_save_worker_schedule',
                    worker_id: this.currentWorkerId,
                    schedule: scheduleData,
                    nonce: schedspot_schedule.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.showSuccess(response.data.message);
                        this.renderCalendar(); // Refresh calendar view
                    } else {
                        this.showError(response.data.message);
                    }
                }.bind(this),
                error: function() {
                    this.showError('Failed to save schedule. Please try again.');
                }.bind(this),
                complete: function() {
                    this.hideLoading();
                }.bind(this)
            });
        },

        collectScheduleData: function() {
            var schedule = {};
            var days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

            days.forEach(function(day) {
                var dayContainer = $('.day-schedule[data-day="' + day + '"]');
                var isEnabled = dayContainer.find('.day-enabled').is(':checked');

                if (isEnabled) {
                    schedule[day] = [];
                    dayContainer.find('.time-slot').each(function() {
                        var startTime = $(this).find('.start-time').val();
                        var endTime = $(this).find('.end-time').val();
                        if (startTime && endTime) {
                            schedule[day].push({
                                start: startTime,
                                end: endTime
                            });
                        }
                    });
                }
            });

            return schedule;
        },

        toggleDayAvailability: function() {
            var dayContainer = $(this).closest('.day-schedule');
            var timeSlots = dayContainer.find('.time-slots');
            
            if ($(this).is(':checked')) {
                timeSlots.slideDown();
                // Add default time slot if none exist
                if (timeSlots.find('.time-slot').length === 0) {
                    ScheduleManager.addTimeSlotToDay(dayContainer);
                }
            } else {
                timeSlots.slideUp();
            }
        },

        addTimeSlot: function() {
            var dayContainer = $(this).closest('.day-schedule');
            ScheduleManager.addTimeSlotToDay(dayContainer);
        },

        addTimeSlotToDay: function(dayContainer) {
            var day = dayContainer.data('day');
            var slotIndex = dayContainer.find('.time-slot').length;
            
            var slotHtml = '<div class="time-slot">' +
                '<input type="time" class="start-time" value="09:00" name="schedule[' + day + '][' + slotIndex + '][start]">' +
                '<span class="time-separator">to</span>' +
                '<input type="time" class="end-time" value="17:00" name="schedule[' + day + '][' + slotIndex + '][end]">' +
                '<button type="button" class="remove-slot button-link-delete">Remove</button>' +
                '</div>';
            
            dayContainer.find('.add-slot').before(slotHtml);
        },

        removeTimeSlot: function() {
            var dayContainer = $(this).closest('.day-schedule');
            $(this).closest('.time-slot').remove();
            
            // If no slots remain, uncheck the day
            if (dayContainer.find('.time-slot').length === 0) {
                dayContainer.find('.day-enabled').prop('checked', false);
                dayContainer.find('.time-slots').slideUp();
            }
        },

        toggleCustomHours: function() {
            var customHours = $('.custom-hours');
            if ($(this).val() === 'custom') {
                customHours.slideDown();
            } else {
                customHours.slideUp();
            }
        },

        addException: function() {
            var date = $('#exception-date').val();
            var type = $('#exception-type').val();
            var startTime = $('#exception-start').val();
            var endTime = $('#exception-end').val();
            var note = $('#exception-note').val();

            if (!date) {
                alert('Please select a date.');
                return;
            }

            if (type === 'custom' && (!startTime || !endTime)) {
                alert('Please specify start and end times for custom hours.');
                return;
            }

            $.ajax({
                url: schedspot_schedule.ajax_url,
                type: 'POST',
                data: {
                    action: 'schedspot_add_schedule_exception',
                    worker_id: this.currentWorkerId,
                    date: date,
                    type: type,
                    start_time: startTime,
                    end_time: endTime,
                    note: note,
                    nonce: schedspot_schedule.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.showSuccess(response.data.message);
                        this.refreshExceptionsList();
                        this.clearExceptionForm();
                    } else {
                        this.showError(response.data.message);
                    }
                }.bind(this),
                error: function() {
                    this.showError('Failed to add exception. Please try again.');
                }.bind(this)
            });
        },

        removeException: function(e) {
            e.preventDefault();
            
            if (!confirm(schedspot_schedule.strings.confirm_delete)) {
                return;
            }

            var exceptionId = $(this).data('exception-id');

            $.ajax({
                url: schedspot_schedule.ajax_url,
                type: 'POST',
                data: {
                    action: 'schedspot_remove_schedule_exception',
                    exception_id: exceptionId,
                    nonce: schedspot_schedule.nonce
                },
                success: function(response) {
                    if (response.success) {
                        this.showSuccess(response.data.message);
                        $('[data-exception-id="' + exceptionId + '"]').fadeOut();
                    } else {
                        this.showError(response.data.message);
                    }
                }.bind(this),
                error: function() {
                    this.showError('Failed to remove exception. Please try again.');
                }.bind(this)
            });
        },

        clearExceptionForm: function() {
            $('#exception-date').val('');
            $('#exception-type').val('unavailable');
            $('#exception-start').val('');
            $('#exception-end').val('');
            $('#exception-note').val('');
            $('.custom-hours').hide();
        },

        refreshExceptionsList: function() {
            // Reload the current page to refresh the exceptions list
            window.location.reload();
        },

        switchTab: function(e) {
            e.preventDefault();
            
            var targetTab = $(this).attr('href').substring(1);
            
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.tab-content').removeClass('active');
            $('#' + targetTab).addClass('active');
            
            if (targetTab === 'calendar-view') {
                ScheduleManager.renderCalendar();
            }
        },

        previousMonth: function() {
            this.currentMonth.setMonth(this.currentMonth.getMonth() - 1);
            this.renderCalendar();
        },

        nextMonth: function() {
            this.currentMonth.setMonth(this.currentMonth.getMonth() + 1);
            this.renderCalendar();
        },

        renderCalendar: function() {
            if (!this.currentWorkerId) {
                $('#schedule-calendar').html('<p>Please select a worker to view their calendar.</p>');
                return;
            }

            var monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];

            var monthYear = monthNames[this.currentMonth.getMonth()] + ' ' + this.currentMonth.getFullYear();
            $('#current-month').text(monthYear);

            // Generate calendar HTML
            var calendarHtml = this.generateCalendarHTML();
            $('#schedule-calendar').html(calendarHtml);
        },

        generateCalendarHTML: function() {
            var year = this.currentMonth.getFullYear();
            var month = this.currentMonth.getMonth();

            // Get first day of month and number of days
            var firstDay = new Date(year, month, 1);
            var lastDay = new Date(year, month + 1, 0);
            var daysInMonth = lastDay.getDate();
            var startingDayOfWeek = firstDay.getDay();

            var html = '<table class="calendar-table">';
            html += '<thead><tr>';
            html += '<th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>';
            html += '</tr></thead><tbody>';

            var date = 1;

            // Generate calendar rows
            for (var week = 0; week < 6; week++) {
                html += '<tr>';

                for (var day = 0; day < 7; day++) {
                    if (week === 0 && day < startingDayOfWeek) {
                        html += '<td class="calendar-day empty"></td>';
                    } else if (date > daysInMonth) {
                        html += '<td class="calendar-day empty"></td>';
                    } else {
                        var cellDate = new Date(year, month, date);
                        var dateStr = cellDate.toISOString().split('T')[0];
                        var dayClass = this.getDayClass(cellDate);

                        html += '<td class="calendar-day ' + dayClass + '" data-date="' + dateStr + '">';
                        html += '<div class="day-number">' + date + '</div>';
                        html += '<div class="day-status">' + this.getDayStatus(cellDate) + '</div>';
                        html += '</td>';

                        date++;
                    }
                }

                html += '</tr>';

                if (date > daysInMonth) {
                    break;
                }
            }

            html += '</tbody></table>';
            return html;
        },

        getDayClass: function(date) {
            var today = new Date();
            var classes = [];

            if (date.toDateString() === today.toDateString()) {
                classes.push('today');
            }

            if (date < today) {
                classes.push('past');
            }

            // Check if worker is available on this day
            var dayName = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'][date.getDay()];
            var schedule = this.getWorkerScheduleForDay(dayName);

            if (schedule && schedule.length > 0) {
                classes.push('available');
            } else {
                classes.push('unavailable');
            }

            return classes.join(' ');
        },

        getDayStatus: function(date) {
            var dayName = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'][date.getDay()];
            var schedule = this.getWorkerScheduleForDay(dayName);

            if (schedule && schedule.length > 0) {
                return schedule[0].start + '-' + schedule[0].end;
            }

            return 'Unavailable';
        },

        getWorkerScheduleForDay: function(dayName) {
            // This would normally fetch from the current worker's schedule
            // For now, return a default schedule
            var defaultSchedule = {
                'monday': [{ start: '09:00', end: '17:00' }],
                'tuesday': [{ start: '09:00', end: '17:00' }],
                'wednesday': [{ start: '09:00', end: '17:00' }],
                'thursday': [{ start: '09:00', end: '17:00' }],
                'friday': [{ start: '09:00', end: '17:00' }]
            };

            return defaultSchedule[dayName] || [];
        },

        showLoading: function() {
            $('.schedspot-admin-schedule').addClass('schedspot-loading');
        },

        hideLoading: function() {
            $('.schedspot-admin-schedule').removeClass('schedspot-loading');
        },

        showSuccess: function(message) {
            this.showNotice(message, 'success');
        },

        showError: function(message) {
            this.showNotice(message, 'error');
        },

        showNotice: function(message, type) {
            var notice = $('<div class="schedspot-notice ' + type + '">' + message + '</div>');
            $('.schedspot-admin-schedule h1').after(notice);
            
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize the schedule manager
    ScheduleManager.init();
});
