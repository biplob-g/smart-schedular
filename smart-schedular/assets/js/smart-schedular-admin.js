/**
 * Smart Schedular Admin JavaScript
 */
(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        // Initialize color picker
        if ($.fn.wpColorPicker) {
            $('.color-picker').wpColorPicker();
        }

        // Initialize datepickers
        if ($.fn.datepicker) {
            $('.datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
        }

        // Services page: add time slot
        $('.add-time-slot').on('click', function(e) {
            e.preventDefault();
            
            var template = $('#time-slot-template').html();
            $('.time-slots-container').append(template);
        });

        // Services page: remove time slot
        $(document).on('click', '.remove-time-slot', function(e) {
            e.preventDefault();
            $(this).closest('.time-slot').remove();
        });

        // Services page: handle service form submission
        $('#service-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            var serviceId = $('#service-id').val();
            
            $.ajax({
                url: smart_schedular.ajax_url,
                type: 'POST',
                data: {
                    action: 'smart_schedular_save_service',
                    nonce: smart_schedular.nonce,
                    ...formData
                },
                beforeSend: function() {
                    $('#save-service-btn').prop('disabled', true).text('Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        
                        if (!serviceId) {
                            // Redirect to edit the new service
                            window.location.href = 'admin.php?page=smart-schedular-services&edit=' + response.data.service_id;
                        }
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    $('#save-service-btn').prop('disabled', false).text('Save Service');
                }
            });
        });

        // Services page: handle time slots submission
        $('#time-slots-form').on('submit', function(e) {
            e.preventDefault();
            
            var slots = [];
            $('.time-slot').each(function() {
                var $this = $(this);
                slots.push({
                    day: $this.find('.time-slot-day').val(),
                    start: $this.find('.time-slot-start').val(),
                    end: $this.find('.time-slot-end').val()
                });
            });
            
            var serviceId = $('#service-id').val();
            
            $.ajax({
                url: smart_schedular.ajax_url,
                type: 'POST',
                data: {
                    action: 'smart_schedular_save_time_slots',
                    nonce: smart_schedular.nonce,
                    service_id: serviceId,
                    slots: slots
                },
                beforeSend: function() {
                    $('#save-time-slots-btn').prop('disabled', true).text('Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    $('#save-time-slots-btn').prop('disabled', false).text('Save Time Slots');
                }
            });
        });

        // Services page: block date
        $('#block-date-form').on('submit', function(e) {
            e.preventDefault();
            
            var date = $('#block-date').val();
            var serviceId = $('#service-id').val();
            
            if (!date) {
                alert('Please select a date to block.');
                return;
            }
            
            $.ajax({
                url: smart_schedular.ajax_url,
                type: 'POST',
                data: {
                    action: 'smart_schedular_block_date',
                    nonce: smart_schedular.nonce,
                    service_id: serviceId,
                    date: date
                },
                beforeSend: function() {
                    $('#block-date-btn').prop('disabled', true).text('Blocking...');
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#block-date').val('');
                        
                        // Refresh blocked dates list
                        loadBlockedDates(serviceId);
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    $('#block-date-btn').prop('disabled', false).text('Block Date');
                }
            });
        });

        // Services page: unblock date
        $(document).on('click', '.unblock-date', function(e) {
            e.preventDefault();
            
            var date = $(this).data('date');
            var serviceId = $('#service-id').val();
            
            if (confirm('Are you sure you want to unblock this date?')) {
                $.ajax({
                    url: smart_schedular.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'smart_schedular_unblock_date',
                        nonce: smart_schedular.nonce,
                        service_id: serviceId,
                        date: date
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            
                            // Refresh blocked dates list
                            loadBlockedDates(serviceId);
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            }
        });

        // Function to load blocked dates
        function loadBlockedDates(serviceId) {
            $.ajax({
                url: smart_schedular.ajax_url,
                type: 'GET',
                data: {
                    action: 'smart_schedular_get_blocked_dates',
                    nonce: smart_schedular.nonce,
                    service_id: serviceId
                },
                success: function(response) {
                    if (response.success) {
                        var $list = $('#blocked-dates-list');
                        $list.empty();
                        
                        if (response.data.dates.length === 0) {
                            $list.html('<p>No blocked dates found.</p>');
                            return;
                        }
                        
                        var html = '<ul>';
                        $.each(response.data.dates, function(index, item) {
                            html += '<li>' + item.date + ' <a href="#" class="unblock-date" data-date="' + item.date + '">Unblock</a></li>';
                        });
                        html += '</ul>';
                        
                        $list.html(html);
                    }
                }
            });
        }

        // Load blocked dates on page load if on the services edit page
        if ($('#blocked-dates-list').length && $('#service-id').length) {
            var serviceId = $('#service-id').val();
            if (serviceId) {
                loadBlockedDates(serviceId);
            }
        }

        // Appointments page: handle filters
        $('#appointment-filters-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var queryArgs = $form.serialize();
            
            window.location.href = 'admin.php?page=smart-schedular-appointments&' + queryArgs;
        });

        // Appointments page: reset filters
        $('#reset-filters').on('click', function(e) {
            e.preventDefault();
            window.location.href = 'admin.php?page=smart-schedular-appointments';
        });

        // Appointments page: approve appointment
        $('.approve-appointment').on('click', function() {
            var appointmentId = $(this).data('id');
            
            if (confirm('Are you sure you want to approve this appointment?')) {
                $.ajax({
                    url: smart_schedular.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'smart_schedular_approve_appointment',
                        appointment_id: appointmentId,
                        nonce: smart_schedular.nonce
                    },
                    beforeSend: function() {
                        $(this).prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Appointment approved successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    },
                    complete: function() {
                        $(this).prop('disabled', false);
                    }
                });
            }
        });

        // Appointments page: decline appointment
        $('.decline-appointment').on('click', function() {
            var appointmentId = $(this).data('id');
            
            if (confirm('Are you sure you want to decline this appointment?')) {
                $.ajax({
                    url: smart_schedular.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'smart_schedular_decline_appointment',
                        appointment_id: appointmentId,
                        nonce: smart_schedular.nonce
                    },
                    beforeSend: function() {
                        $(this).prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Appointment declined successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    },
                    complete: function() {
                        $(this).prop('disabled', false);
                    }
                });
            }
        });
    });

})(jQuery); 