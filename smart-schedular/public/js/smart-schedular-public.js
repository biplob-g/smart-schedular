jQuery(document).ready(function($) {
    console.log('Smart Schedular initialized');
    
    // Initialize the calendar
    if ($('#smart-schedular-calendar').length) {
        console.log('Calendar found, initializing...');
        
        var calendar = $('#smart-schedular-calendar').fullCalendar({
            header: {
                left: 'prev',
                center: 'title',
                right: 'next'
            },
            defaultView: 'month',
            selectable: false,
            selectHelper: false,
            editable: false,
            eventLimit: true,
            showNonCurrentDates: true,
            fixedWeekCount: false,
            height: 'auto',
            contentHeight: 'auto',
            
            // Customize date cells
            dayRender: function(date, cell) {
                var today = moment();
                var serviceId = $('#service_id').val();
                
                // Only disable dates in the past
                if (date.isBefore(today, 'day')) {
                    cell.addClass('fc-disabled');
                    return;
                }

                // Set all future dates as available by default
                cell.addClass('fc-available');
                
                // Check if service is available on this day
                $.ajax({
                    url: smart_schedular_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'check_service_availability',
                        service_id: serviceId,
                        date: date.format('YYYY-MM-DD'),
                        nonce: smart_schedular_ajax.nonce
                    },
                    success: function(response) {
                        if (!response.success || !response.data.available) {
                            cell.removeClass('fc-available').addClass('fc-disabled');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error checking availability:', error);
                        cell.removeClass('fc-available').addClass('fc-disabled');
                    }
                });
            },
            
            // Handle date selection
            dayClick: function(date, jsEvent, view) {
                if ($(jsEvent.target).closest('.fc-day').hasClass('fc-disabled')) {
                    console.log('Day is disabled, ignoring click');
                    return false;
                }
                
                var serviceId = $('#service_id').val();
                
                // Remove previous selection
                $('.fc-day').removeClass('fc-selected');
                $(jsEvent.target).closest('.fc-day').addClass('fc-selected');
                
                // Store selected date
                $('#appointment_date').val(date.format('YYYY-MM-DD'));
                
                // Update the displayed selected date
                var formattedDate = date.format('dddd, MMMM D');
                $('#selected-date').text(formattedDate);
                
                // Clear previous time slot selection
                $('#appointment_time').val('');
                
                // Show loading indicator in time slots section
                $('#smart-schedular-time-slots').html('<div style="text-align: center; padding: 30px 0;"><span style="display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></span><p style="margin-top: 10px; color: #5f6368;">Loading available times...</p></div><style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>');
                
                // Check availability and show time slots
                $.ajax({
                    url: smart_schedular_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'get_available_time_slots',
                        service_id: serviceId,
                        date: date.format('YYYY-MM-DD'),
                        nonce: smart_schedular_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.time_slots.length > 0) {
                            showTimeSlots(response.data.time_slots, date.format('YYYY-MM-DD'));
                        } else {
                            $('#smart-schedular-time-slots').html('<div style="text-align: center; padding: 30px 0;"><p style="color: #5f6368; font-size: 14px;">No available time slots for this date.</p><p style="color: #5f6368; font-size: 14px; margin-top: 10px;">Please select another date.</p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error getting time slots:', error);
                        $('#smart-schedular-time-slots').html('<div style="text-align: center; padding: 30px 0;"><p style="color: #e74c3c; font-size: 14px;">Error loading time slots.</p><p style="color: #5f6368; font-size: 14px; margin-top: 10px;">Please try selecting another date.</p></div>');
                    }
                });
            }
        });
        
        // Auto-select today's date if available
        var today = moment();
        var todayCell = $('.fc-day[data-date="' + today.format('YYYY-MM-DD') + '"]');
        
        // Wait for calendar to fully initialize
        setTimeout(function() {
            if (todayCell.length && !todayCell.hasClass('fc-disabled')) {
                todayCell.trigger('click');
            }
        }, 1000);
    }
    
    // Function to display time slots
    function showTimeSlots(timeSlots, selectedDate) {
        var timeSlotsBtns = '';
        var confirmBtn = '<button class="confirm-btn" id="confirm-time-slot" style="display: none;">Confirm</button>';
        
        // Display time slots in columnar layout
        timeSlots.forEach(function(slot) {
            var displayTime = moment(slot, 'HH:mm').format('h:mm a');
            timeSlotsBtns += '<button class="time-slot-btn" data-time="' + slot + '">' + 
                displayTime +
                '</button>';
        });
        
        // Show time slots and confirm button
        $('#smart-schedular-time-slots').html(timeSlotsBtns + confirmBtn);
        
        // Handle time slot selection
        $('.time-slot-btn').click(function(e) {
            e.preventDefault();
            var selectedTime = $(this).data('time');
            $('#appointment_time').val(selectedTime);
            
            // Highlight selected time slot
            $('.time-slot-btn').removeClass('selected');
            $(this).addClass('selected');
            
            // Show confirm button
            $('#confirm-time-slot').show();
        });
        
        // Handle confirm button click
        $('#confirm-time-slot').click(function(e) {
            e.preventDefault();
            
            // Show the booking form
            $('#smart-schedular-booking-form').show();
            
            // Scroll to the form
            $('html, body').animate({
                scrollTop: $('#smart-schedular-booking-form').offset().top - 20
            }, 500);
        });
    }
    
    // Handle form submission
    $(document).on('submit', '#appointment-form', function(e) {
        e.preventDefault();
        console.log('Form submission initiated');
        
        // Validate form fields
        var requiredFields = ['customer_name', 'customer_email', 'appointment_date', 'appointment_time'];
        var isValid = true;
        
        console.log('Checking required fields');
        requiredFields.forEach(function(field) {
            if (!$('#' + field).val()) {
                isValid = false;
                $('#' + field).css('border-color', '#e74c3c');
                console.error('Missing required field:', field);
            } else {
                $('#' + field).css('border-color', '#e0e0e0');
            }
        });
        
        if (!isValid) {
            alert('Please fill in all required fields.');
            return false;
        }
        
        // Email validation
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test($('#customer_email').val())) {
            alert('Please enter a valid email address.');
            $('#customer_email').css('border-color', '#e74c3c');
            return false;
        }
        
        var formData = {
            action: 'book_appointment',
            nonce: smart_schedular_ajax.nonce,
            service_id: $('#service_id').val(),
            appointment_date: $('#appointment_date').val(),
            appointment_time: $('#appointment_time').val(),
            customer_name: $('#customer_name').val(),
            customer_email: $('#customer_email').val(),
            customer_phone: $('#customer_phone').val(),
            customer_message: $('#customer_message').val()
        };
        
        console.log('Form data to be submitted:', formData);
        console.log('AJAX URL:', smart_schedular_ajax.ajax_url);
        console.log('Nonce:', smart_schedular_ajax.nonce);
        
        // Show loading indicator
        var submitBtn = $(this).find('button[type="submit"]');
        var originalBtnText = submitBtn.text();
        submitBtn.prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: smart_schedular_ajax.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function(xhr) {
                console.log('AJAX request about to be sent');
            },
            success: function(response) {
                console.log('Form submission successful, response:', response);
                submitBtn.prop('disabled', false).text(originalBtnText);
                
                if (response.success) {
                    // Show thank you message
                    var thankYouHtml = '<div class="booking-success">' +
                        '<div class="success-icon">✓</div>' +
                        '<h3>Thank You!</h3>' +
                        '<p>Your appointment has been booked successfully.</p>' +
                        '<p>We have sent a confirmation email to <strong>' + $('#customer_email').val() + '</strong>.</p>' +
                        '<p>Date: <strong>' + moment($('#appointment_date').val()).format('dddd, MMMM D, YYYY') + '</strong></p>' +
                        '<p>Time: <strong>' + moment($('#appointment_time').val(), 'HH:mm').format('h:mm A') + '</strong></p>' +
                        '</div>';
                    
                    // Hide the form and show thank you message
                    $('#appointment-form').hide();
                    $('#booking-confirmation').html(thankYouHtml).show();
                    
                    // Scroll to thank you message
                    $('html, body').animate({
                        scrollTop: $('#booking-confirmation').offset().top - 100
                    }, 500);
                } else {
                    // Show error message
                    var errorMessage = response.data || 'There was an error booking your appointment. Please try again.';
                    console.error('Form submission error response:', response);
                    alert(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText,
                    readyState: xhr.readyState,
                    statusCode: xhr.status,
                    statusText: xhr.statusText
                });
                
                submitBtn.prop('disabled', false).text(originalBtnText);
                
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response && response.data) {
                        alert(response.data);
                    } else {
                        alert('There was an error processing your request. Please try again. (Error: ' + status + ', Code: ' + xhr.status + ')');
                    }
                } catch(e) {
                    console.error('Error parsing response:', e);
                    alert('There was an error processing your request. Please try again. (Error: ' + status + ', Code: ' + xhr.status + ')');
                }
            },
            complete: function(xhr, status) {
                console.log('AJAX request completed with status:', status);
            }
        });
    });
}); 