<?php

/**
 * The booking functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Smart_Schedular
 * @subpackage Smart_Schedular/includes
 */

/**
 * The booking functionality of the plugin.
 *
 * Defines the plugin name, version, and handles booking functionality
 *
 * @package    Smart_Schedular
 * @subpackage Smart_Schedular/includes
 * @author     Smart Schedular
 */
class Smart_Schedular_Booking {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The email handler.
     *
     * @since    1.0.0
     * @access   private
     * @var      Smart_Schedular_Emails    $emails    The email handler.
     */
    private $emails;

    /**
     * The Google API handler.
     *
     * @since    1.0.0
     * @access   private
     * @var      Smart_Schedular_Google_API    $google_api    The Google API handler.
     */
    private $google_api;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        $this->emails = new Smart_Schedular_Emails($plugin_name, $version);
        $this->google_api = new Smart_Schedular_Google_API($plugin_name, $version);
    }
    
    /**
     * Get all services
     *
     * @since    1.0.0
     * @return   array    Array of services.
     */
    public function get_services() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'smart_schedular_services';
        
        $services = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY id ASC",
            ARRAY_A
        );
        
        // Define default service structure
        $default_service = array(
            'id' => 0,
            'name' => '',
            'duration' => 30,
            'description' => '',
            'logo_url' => '',
            'color' => '#3788d8',
            'font_family' => 'system-ui, -apple-system, sans-serif',
            'available_days' => '1,2,3,4,5',
            'created_at' => '',
            'updated_at' => ''
        );
        
        // Ensure all services have the required keys
        foreach ($services as &$service) {
            $service = array_merge($default_service, $service);
        }
        
        return $services;
    }
    
    /**
     * Get a specific service by ID
     *
     * @since    1.0.0
     * @param    int      $service_id    The service ID.
     * @return   array    Service details.
     */
    public function get_service($service_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'smart_schedular_services';
        
        $service = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $service_id),
            ARRAY_A
        );
        
        // If no service found, return empty array
        if (!$service) {
            return array();
        }
        
        // Ensure all required keys exist with default values
        $default_service = array(
            'id' => 0,
            'name' => '',
            'duration' => 30,
            'description' => '',
            'logo_url' => '',
            'color' => '#3788d8',
            'font_family' => 'system-ui, -apple-system, sans-serif',
            'available_days' => '1,2,3,4,5',
            'created_at' => '',
            'updated_at' => ''
        );
        
        // Merge with defaults to ensure all keys exist
        return array_merge($default_service, $service);
    }
    
    /**
     * Get available time slots for a service on a specific day
     *
     * @since    1.0.0
     * @param    int      $service_id    The service ID.
     * @param    string   $date          The date (YYYY-MM-DD).
     * @param    string   $timezone      The timezone.
     * @return   array    Available time slots.
     */
    public function get_available_time_slots($service_id, $date, $timezone) {
        global $wpdb;
        
        // Get service details
        $service = $this->get_service($service_id);
        if (!$service) {
            return array();
        }
        
        // Get the day of week (1-7, 1 = Monday, 7 = Sunday)
        $day_of_week = date('N', strtotime($date));
        
        // Check if the selected day is available for this service
        $available_days = explode(',', $service['available_days']);
        if (!in_array($day_of_week, $available_days)) {
            return array();
        }
        
        // Check if the date is blocked
        $blocked_table = $wpdb->prefix . 'smart_schedular_blocked_dates';
        $blocked = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $blocked_table WHERE service_id = %d AND blocked_date = %s",
            $service_id,
            $date
        ));
        
        if ($blocked > 0) {
            return array();
        }
        
        // Get time slots for this service on this day of week
        $slots_table = $wpdb->prefix . 'smart_schedular_time_slots';
        $slots = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $slots_table WHERE service_id = %d AND day_of_week = %d",
            $service_id,
            $day_of_week
        ), ARRAY_A);
        
        if (empty($slots)) {
            return array();
        }
        
        // Get existing appointments for this date to avoid double booking
        $appointments_table = $wpdb->prefix . 'smart_schedular_appointments';
        $existing_appointments = $wpdb->get_results($wpdb->prepare(
            "SELECT appointment_time FROM $appointments_table 
            WHERE service_id = %d AND appointment_date = %s AND status != 'declined'",
            $service_id,
            $date
        ), ARRAY_A);
        
        $booked_times = array();
        foreach ($existing_appointments as $appointment) {
            $booked_times[] = $appointment['appointment_time'];
        }
        
        // Generate time slots based on the service duration
        $available_slots = array();
        $duration_minutes = (int) $service['duration'];
        
        foreach ($slots as $slot) {
            $start_time = strtotime($slot['start_time']);
            $end_time = strtotime($slot['end_time']);
            
            // Loop through each time slot increment
            for ($time = $start_time; $time <= $end_time - ($duration_minutes * 60); $time += 30 * 60) {
                $slot_time = date('H:i:s', $time);
                
                // Check if this time is already booked
                if (!in_array($slot_time, $booked_times)) {
                    $formatted_time = date('g:ia', $time); // Format as 9:00am
                    $available_slots[] = array(
                        'time' => $slot_time,
                        'formatted_time' => $formatted_time
                    );
                }
            }
        }
        
        return $available_slots;
    }
    
    /**
     * Get available dates for a service
     *
     * @since    1.0.0
     * @param    int      $service_id    The service ID.
     * @param    string   $month         The month (YYYY-MM).
     * @param    string   $timezone      The timezone.
     * @return   array    Available dates.
     */
    public function get_available_dates($service_id, $month, $timezone) {
        global $wpdb;
        
        // Get service details
        $service = $this->get_service($service_id);
        if (!$service) {
            return array();
        }
        
        // Get the start and end date of the month
        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        // Get available days for this service
        $available_days = explode(',', $service['available_days']);
        
        // Get blocked dates
        $blocked_table = $wpdb->prefix . 'smart_schedular_blocked_dates';
        $blocked_dates = $wpdb->get_col($wpdb->prepare(
            "SELECT blocked_date FROM $blocked_table WHERE service_id = %d AND blocked_date BETWEEN %s AND %s",
            $service_id,
            $start_date,
            $end_date
        ));
        
        // Generate calendar data
        $calendar = array();
        $current_date = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);
        $today = new DateTime(current_time('Y-m-d'));
        
        while ($current_date <= $end_date_obj) {
            $date_str = $current_date->format('Y-m-d');
            $day_of_week = $current_date->format('N'); // 1-7, 1 = Monday, 7 = Sunday
            
            $date_info = array(
                'date' => $date_str,
                'day' => $current_date->format('j'),
                'available' => false,
                'past' => ($current_date < $today)
            );
            
            // Check if this day of week is available and not blocked
            if (in_array($day_of_week, $available_days) && !in_array($date_str, $blocked_dates) && $current_date >= $today) {
                // Check if there are available time slots on this date
                $available_slots = $this->get_available_time_slots($service_id, $date_str, $timezone);
                $date_info['available'] = !empty($available_slots);
            }
            
            $calendar[] = $date_info;
            $current_date->modify('+1 day');
        }
        
        return $calendar;
    }
    
    /**
     * Book an appointment
     *
     * @since    1.0.0
     * @param    array    $appointment_data    The appointment data.
     * @return   array    The result of booking.
     */
    public function book_appointment($appointment_data) {
        global $wpdb;
        
        // Validate the appointment data
        if (empty($appointment_data['service_id']) || 
            empty($appointment_data['customer_name']) || 
            empty($appointment_data['customer_email']) || 
            empty($appointment_data['appointment_date']) || 
            empty($appointment_data['appointment_time']) || 
            empty($appointment_data['appointment_timezone'])) {
            
            return array(
                'success' => false,
                'message' => 'Missing required fields'
            );
        }
        
        // Get service details
        $service = $this->get_service($appointment_data['service_id']);
        if (!$service) {
            return array(
                'success' => false,
                'message' => 'Invalid service'
            );
        }
        
        // Check if the time slot is still available
        $available_slots = $this->get_available_time_slots(
            $appointment_data['service_id'],
            $appointment_data['appointment_date'],
            $appointment_data['appointment_timezone']
        );
        
        $is_available = false;
        foreach ($available_slots as $slot) {
            if ($slot['time'] == $appointment_data['appointment_time']) {
                $is_available = true;
                break;
            }
        }
        
        if (!$is_available) {
            return array(
                'success' => false,
                'message' => 'The selected time slot is no longer available'
            );
        }
        
        // Prepare appointment data for insertion
        $data = array(
            'service_id' => absint($appointment_data['service_id']),
            'customer_name' => sanitize_text_field($appointment_data['customer_name']),
            'customer_email' => sanitize_email($appointment_data['customer_email']),
            'customer_phone' => isset($appointment_data['customer_phone']) ? sanitize_text_field($appointment_data['customer_phone']) : '',
            'appointment_date' => $appointment_data['appointment_date'],
            'appointment_time' => $appointment_data['appointment_time'],
            'appointment_timezone' => sanitize_text_field($appointment_data['appointment_timezone']),
            'duration' => absint($service['duration']),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );
        
        // Insert the appointment
        $table_name = $wpdb->prefix . 'smart_schedular_appointments';
        $result = $wpdb->insert($table_name, $data);
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => 'Failed to book the appointment'
            );
        }
        
        $appointment_id = $wpdb->insert_id;
        $data['id'] = $appointment_id;
        
        // Send emails
        $this->emails->send_customer_confirmation_email($data);
        $this->emails->send_admin_notification_email($data);
        
        return array(
            'success' => true,
            'message' => 'Appointment booked successfully',
            'appointment_id' => $appointment_id
        );
    }
    
    /**
     * Approve an appointment
     *
     * @since    1.0.0
     * @param    int      $appointment_id    The appointment ID.
     * @return   array    The result of the approval.
     */
    public function approve_appointment($appointment_id) {
        global $wpdb;
        
        // Get the appointment
        $table_name = $wpdb->prefix . 'smart_schedular_appointments';
        $appointment = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $appointment_id),
            ARRAY_A
        );
        
        if (!$appointment) {
            return array(
                'success' => false,
                'message' => 'Appointment not found'
            );
        }
        
        // Update the appointment with Google Calendar details
        $updated_appointment = $this->google_api->create_calendar_event_with_meet($appointment);
        
        // Send approval email
        $this->emails->send_customer_approval_email($updated_appointment);
        
        return array(
            'success' => true,
            'message' => 'Appointment approved successfully',
            'appointment' => $updated_appointment
        );
    }
    
    /**
     * Decline an appointment
     *
     * @since    1.0.0
     * @param    int      $appointment_id    The appointment ID.
     * @return   array    The result of declining.
     */
    public function decline_appointment($appointment_id) {
        global $wpdb;
        
        // Get the appointment
        $table_name = $wpdb->prefix . 'smart_schedular_appointments';
        $appointment = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $appointment_id),
            ARRAY_A
        );
        
        if (!$appointment) {
            return array(
                'success' => false,
                'message' => 'Appointment not found'
            );
        }
        
        // Update the appointment status
        $wpdb->update(
            $table_name,
            array('status' => 'declined'),
            array('id' => $appointment_id)
        );
        
        $appointment['status'] = 'declined';
        
        // Send decline email
        $this->emails->send_customer_decline_email($appointment);
        
        return array(
            'success' => true,
            'message' => 'Appointment declined successfully',
            'appointment' => $appointment
        );
    }
    
    /**
     * Get all appointments
     *
     * @since    1.0.0
     * @param    array    $filters    Optional. Filters for appointments.
     * @return   array    Array of appointments.
     */
    public function get_appointments($filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'smart_schedular_appointments';
        $services_table = $wpdb->prefix . 'smart_schedular_services';
        
        $where = array();
        $where_values = array();
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $where[] = 'a.status = %s';
            $where_values[] = $filters['status'];
        }
        
        if (isset($filters['service_id']) && !empty($filters['service_id'])) {
            $where[] = 'a.service_id = %d';
            $where_values[] = $filters['service_id'];
        }
        
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $where[] = 'a.appointment_date >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $where[] = 'a.appointment_date <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        $where_clause = '';
        if (!empty($where)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where);
        }
        
        $query = "SELECT a.*, s.name as service_name 
                FROM $table_name a 
                LEFT JOIN $services_table s ON a.service_id = s.id 
                $where_clause 
                ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $appointments = $wpdb->get_results($query, ARRAY_A);
        
        return $appointments;
    }
} 