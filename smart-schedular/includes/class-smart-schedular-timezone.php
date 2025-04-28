<?php

class Smart_Schedular_Timezone {
    
    private static $timezone_offsets = array(
        'UTC' => 0,
        'EST' => -5,
        'PST' => -8,
        'IST' => 5.5
    );
    
    /**
     * Get available timezones
     * @return array
     */
    public static function get_available_timezones() {
        return array(
            'UTC' => 'UTC (GMT+0)',
            'EST' => 'EST (GMT-5)',
            'PST' => 'PST (GMT-8)',
            'IST' => 'IST (GMT+5:30)'
        );
    }
    
    /**
     * Convert time between timezones
     */
    public static function convert_time($time, $from_timezone, $to_timezone) {
        if (!isset(self::$timezone_offsets[$from_timezone]) || !isset(self::$timezone_offsets[$to_timezone])) {
            return $time;
        }
        
        // Convert time to minutes since midnight
        list($hours, $minutes) = explode(':', $time);
        $total_minutes = ($hours * 60) + $minutes;
        
        // Calculate offset difference in minutes
        $offset_diff = (self::$timezone_offsets[$to_timezone] - self::$timezone_offsets[$from_timezone]) * 60;
        
        // Apply offset
        $total_minutes += $offset_diff;
        
        // Handle day wrap
        while ($total_minutes < 0) {
            $total_minutes += 1440; // 24 hours * 60 minutes
        }
        while ($total_minutes >= 1440) {
            $total_minutes -= 1440;
        }
        
        // Convert back to HH:mm format
        $hours = floor($total_minutes / 60);
        $minutes = $total_minutes % 60;
        
        return sprintf('%02d:%02d', $hours, $minutes);
    }
    
    /**
     * Check if a given time is within business hours
     */
    public static function is_within_business_hours($service_id, $date, $time, $timezone = 'UTC') {
        $day_of_week = date('N', strtotime($date));
        $day_map = array(
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            7 => 'sunday'
        );
        
        $day_key = $day_map[$day_of_week];
        
        // Get service availability
        $is_available = get_post_meta($service_id, '_' . $day_key . '_available', true);
        if ($is_available !== '1') {
            return false;
        }
        
        // Get business hours for the day
        $start_time = get_post_meta($service_id, '_' . $day_key . '_start', true);
        $end_time = get_post_meta($service_id, '_' . $day_key . '_end', true);
        
        if (empty($start_time) || empty($end_time)) {
            return false;
        }
        
        // Convert the check time to service's timezone
        $check_time = self::convert_time($time, $timezone, 'UTC');
        $time_ts = strtotime("1970-01-01 $check_time");
        $start_ts = strtotime("1970-01-01 $start_time");
        $end_ts = strtotime("1970-01-01 $end_time");
        
        return ($time_ts >= $start_ts && $time_ts <= $end_ts);
    }
    
    /**
     * Get available time slots for a service on a given date
     */
    public static function get_available_time_slots($service_id, $date, $timezone = 'UTC') {
        global $wpdb;
        
        // Get service duration
        $table_name = $wpdb->prefix . 'smart_schedular_services';
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $service_id
        ));
        
        if (!$service) {
            return array();
        }
        
        $duration = intval($service->duration);
        $day_of_week = date('N', strtotime($date));
        $day_map = array(
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            7 => 'sunday'
        );
        
        $day_key = $day_map[$day_of_week];
        
        // Check if service is available on this day
        $is_available = get_post_meta($service_id, '_' . $day_key . '_available', true);
        if ($is_available !== '1') {
            return array();
        }
        
        // Get business hours
        $start_time = get_post_meta($service_id, '_' . $day_key . '_start', true);
        $end_time = get_post_meta($service_id, '_' . $day_key . '_end', true);
        
        if (empty($start_time) || empty($end_time)) {
            return array();
        }
        
        // Convert business hours to requested timezone
        $start_time = self::convert_time($start_time, 'UTC', $timezone);
        $end_time = self::convert_time($end_time, 'UTC', $timezone);
        
        // Generate time slots
        $time_slots = array();
        $current_time = strtotime($start_time);
        $end_timestamp = strtotime($end_time);
        
        while ($current_time <= $end_timestamp) {
            $slot_time = date('H:i', $current_time);
            
            // Check if slot is already booked
            $utc_slot_time = self::convert_time($slot_time, $timezone, 'UTC');
            $is_booked = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}smart_schedular_appointments 
                WHERE service_id = %d 
                AND appointment_date = %s 
                AND appointment_time = %s",
                $service_id,
                $date,
                $utc_slot_time
            ));
            
            if ($is_booked == 0) {
                $time_slots[] = $slot_time;
            }
            
            // Move to next slot based on service duration
            $current_time = strtotime("+{$duration} minutes", $current_time);
        }
        
        return $time_slots;
    }
} 