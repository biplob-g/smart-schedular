/**
 * Smart Schedular Public JavaScript
 */
(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        // Cache DOM elements
        const $container = $('.smart-schedular-container');
        const $calendarGrid = $('.smart-schedular-calendar-grid');
        const $monthTitle = $('.smart-schedular-month-title');
        const $prevMonth = $('.smart-schedular-prev-month');
        const $nextMonth = $('.smart-schedular-next-month');
        const $timeSlots = $('.smart-schedular-time-slots');
        const $selectedDate = $('.smart-schedular-selected-date');
        const $timezoneSelect = $('.smart-schedular-timezone-select');
        const $bookingForm = $('.smart-schedular-booking-form');
        const $calendarView = $('.smart-schedular-calendar-view');
        const $continueBtn = $('.smart-schedular-continue-button');
        const $backBtn = $('.smart-schedular-back-button');
        const $bookingBtn = $('.smart-schedular-booking-button');
        const $loader = $('.smart-schedular-loader');
        const $message = $('.smart-schedular-message');
        
        // Get service ID from container
        const serviceId = $container.data('service-id');
        
        // Variables to track state
        let currentMonth = smart_schedular.current_month;
        let selectedDate = null;
        let selectedTime = null;
        let userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        
        // Initialize timezone selector
        if ($timezoneSelect.length) {
            $timezoneSelect.val(userTimezone);
            $timezoneSelect.on('change', function() {
                userTimezone = $(this).val();
                loadDates(currentMonth);
            });
        }
        
        // Initialize
        loadDates(currentMonth);
        
        // Event: Previous month button
        $prevMonth.on('click', function() {
            if ($(this).prop('disabled')) return;
            
            const [year, month] = currentMonth.split('-');
            const prevMonthDate = new Date(year, month - 1 - 1, 1);
            currentMonth = `${prevMonthDate.getFullYear()}-${String(prevMonthDate.getMonth() + 1).padStart(2, '0')}`;
            
            loadDates(currentMonth);
        });
        
        // Event: Next month button
        $nextMonth.on('click', function() {
            if ($(this).prop('disabled')) return;
            
            const [year, month] = currentMonth.split('-');
            const nextMonthDate = new Date(year, month - 1 + 1, 1);
            currentMonth = `${nextMonthDate.getFullYear()}-${String(nextMonthDate.getMonth() + 1).padStart(2, '0')}`;
            
            loadDates(currentMonth);
        });
        
        // Event: Date selection
        $(document).on('click', '.smart-schedular-day.available', function() {
            $('.smart-schedular-day').removeClass('selected');
            $(this).addClass('selected');
            
            selectedDate = $(this).data('date');
            loadTimeSlots(selectedDate);
        });
        
        // Event: Time slot selection
        $(document).on('click', '.smart-schedular-time-slot', function() {
            $('.smart-schedular-time-slot').removeClass('selected');
            $(this).addClass('selected');
            
            selectedTime = $(this).data('time');
            $continueBtn.prop('disabled', false);
        });
        
        // Event: Continue button
        $continueBtn.on('click', function() {
            if (!selectedDate || !selectedTime) return;
            
            // Format the selected date for display
            const dateObj = new Date(selectedDate + 'T' + selectedTime);
            const formattedDate = dateObj.toLocaleDateString(undefined, { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            const formattedTime = dateObj.toLocaleTimeString(undefined, { 
                hour: 'numeric', 
                minute: 'numeric',
                hour12: true
            });
            
            // Update hidden fields in the booking form
            $('#appointment_date').val(selectedDate);
            $('#appointment_time').val(selectedTime);
            $('#appointment_timezone').val(userTimezone);
            
            // Show booking form view
            $calendarView.hide();
            $bookingForm.addClass('active');
            
            // Update summary
            $('.smart-schedular-summary-date').text(formattedDate);
            $('.smart-schedular-summary-time').text(formattedTime);
            $('.smart-schedular-summary-timezone').text(userTimezone);
        });
        
        // Event: Back button
        $backBtn.on('click', function() {
            $bookingForm.removeClass('active');
            $calendarView.show();
        });
        
        // Event: Form submission
        $('.smart-schedular-booking-form form').on('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            const customerName = $('#customer_name').val();
            const customerEmail = $('#customer_email').val();
            
            if (!customerName || !customerEmail) {
                showMessage('Please fill in all required fields.', 'error');
                return;
            }
            
            // Prepare data
            const formData = {
                service_id: serviceId,
                customer_name: customerName,
                customer_email: customerEmail,
                customer_phone: $('#customer_phone').val(),
                appointment_date: selectedDate,
                appointment_time: selectedTime,
                appointment_timezone: userTimezone,
                nonce: smart_schedular.nonce
            };
            
            // Submit booking
            bookAppointment(formData);
        });
        
        /**
         * Load available dates for a given month
         */
        function loadDates(month) {
            showLoader();
            
            $.ajax({
                url: smart_schedular.ajax_url,
                type: 'POST',
                data: {
                    action: 'smart_schedular_get_available_dates',
                    service_id: serviceId,
                    month: month,
                    timezone: userTimezone,
                    nonce: smart_schedular.nonce
                },
                success: function(response) {
                    if (response.success) {
                        renderCalendar(response.data.dates, response.data.month);
                    } else {
                        showMessage('Error loading calendar: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showMessage('An error occurred while loading the calendar. Please try again.', 'error');
                },
                complete: function() {
                    hideLoader();
                }
            });
        }
        
        /**
         * Load available time slots for a given date
         */
        function loadTimeSlots(date) {
            $selectedDate.text(formatDate(date));
            $timeSlots.empty();
            showLoader();
            
            $.ajax({
                url: smart_schedular.ajax_url,
                type: 'POST',
                data: {
                    action: 'smart_schedular_get_available_times',
                    service_id: serviceId,
                    date: date,
                    timezone: userTimezone,
                    nonce: smart_schedular.nonce
                },
                success: function(response) {
                    if (response.success) {
                        renderTimeSlots(response.data.times);
                    } else {
                        showMessage('Error loading time slots: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showMessage('An error occurred while loading time slots. Please try again.', 'error');
                },
                complete: function() {
                    hideLoader();
                }
            });
        }
        
        /**
         * Book an appointment
         */
        function bookAppointment(formData) {
            showLoader();
            $bookingBtn.prop('disabled', true);
            
            $.ajax({
                url: smart_schedular.ajax_url,
                type: 'POST',
                data: {
                    action: 'smart_schedular_book_appointment',
                    ...formData
                },
                success: function(response) {
                    if (response.success) {
                        // Clear form
                        $('.smart-schedular-booking-form form')[0].reset();
                        
                        // Show success message
                        showMessage(smart_schedular.success_booking_text, 'success');
                        
                        // Hide form, show success view
                        $bookingForm.removeClass('active');
                        $('.smart-schedular-success-view').addClass('active');
                    } else {
                        showMessage(response.data, 'error');
                        $bookingBtn.prop('disabled', false);
                    }
                },
                error: function() {
                    showMessage(smart_schedular.error_booking_text, 'error');
                    $bookingBtn.prop('disabled', false);
                },
                complete: function() {
                    hideLoader();
                }
            });
        }
        
        /**
         * Render the calendar with available dates
         */
        function renderCalendar(dates, month) {
            // Update month title
            const [year, monthNum] = month.split('-');
            const monthName = new Date(year, monthNum - 1, 1).toLocaleString('default', { month: 'long' });
            $monthTitle.text(`${monthName} ${year}`);
            
            // Disable prev month button if current month is the current real month
            const currentRealMonth = smart_schedular.current_month;
            $prevMonth.prop('disabled', month <= currentRealMonth);
            
            // Clear calendar grid except for weekday headers
            $calendarGrid.find('.smart-schedular-day').remove();
            
            // Get the first day of the month (0 = Sunday, 6 = Saturday)
            const firstDay = new Date(year, monthNum - 1, 1).getDay();
            
            // Add empty cells for days before the first day of the month
            for (let i = 0; i < firstDay; i++) {
                $calendarGrid.append('<div class="smart-schedular-day empty"></div>');
            }
            
            // Add cells for each day
            dates.forEach(dateInfo => {
                const dayNum = parseInt(dateInfo.day, 10);
                const dateStr = dateInfo.date;
                const isToday = dateStr === smart_schedular.current_date;
                const isPast = dateInfo.past;
                
                let classes = 'smart-schedular-day';
                if (isToday) classes += ' today';
                if (isPast) classes += ' past';
                else if (dateInfo.available) classes += ' available';
                else classes += ' unavailable';
                
                const $day = $(`<div class="${classes}" data-date="${dateStr}">${dayNum}</div>`);
                $calendarGrid.append($day);
            });
            
            // Reset time slots and selected date
            $timeSlots.empty();
            $selectedDate.text('Select a date');
            selectedDate = null;
            selectedTime = null;
            $continueBtn.prop('disabled', true);
        }
        
        /**
         * Render time slots for the selected date
         */
        function renderTimeSlots(times) {
            if (!times || times.length === 0) {
                $timeSlots.html('<p>No available time slots for this date.</p>');
                return;
            }
            
            $timeSlots.empty();
            
            times.forEach(slot => {
                const $timeSlot = $(`
                    <div class="smart-schedular-time-slot" data-time="${slot.time}">
                        ${slot.formatted_time}
                    </div>
                `);
                
                $timeSlots.append($timeSlot);
            });
            
            // Reset time selection
            selectedTime = null;
            $continueBtn.prop('disabled', true);
        }
        
        /**
         * Format date for display
         */
        function formatDate(dateStr) {
            const date = new Date(dateStr + 'T00:00:00');
            return date.toLocaleDateString(undefined, { 
                weekday: 'long', 
                month: 'long', 
                day: 'numeric' 
            });
        }
        
        /**
         * Show loader
         */
        function showLoader() {
            $loader.addClass('active');
        }
        
        /**
         * Hide loader
         */
        function hideLoader() {
            $loader.removeClass('active');
        }
        
        /**
         * Show message
         */
        function showMessage(text, type = 'success') {
            $message.removeClass('smart-schedular-success smart-schedular-error')
                   .addClass('active ' + (type === 'success' ? 'smart-schedular-success' : 'smart-schedular-error'))
                   .text(text);
            
            // Hide message after 5 seconds
            setTimeout(function() {
                $message.removeClass('active');
            }, 5000);
        }
    });

})(jQuery); 