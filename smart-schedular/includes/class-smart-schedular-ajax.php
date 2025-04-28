<?php

class Smart_Schedular_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_check_service_availability', array($this, 'check_service_availability'));
        add_action('wp_ajax_nopriv_check_service_availability', array($this, 'check_service_availability'));
        
        add_action('wp_ajax_get_available_time_slots', array($this, 'get_available_time_slots'));
        add_action('wp_ajax_nopriv_get_available_time_slots', array($this, 'get_available_time_slots'));
    }
    
    /**
     * Check if a service is available on a given date
     */
    public function check_service_availability() {
        // Log request
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Check service availability request: ' . print_r($_POST, true));
        }
        
        // Verify nonce
        if (!check_ajax_referer('smart_schedular_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }
        
        global $wpdb;
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        
        if (empty($service_id) || empty($date)) {
            wp_send_json_error(array('message' => 'Missing required parameters'));
        }
        
        // Get service timezone
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT timezone FROM {$wpdb->prefix}smart_schedular_services WHERE id = %d",
            $service_id
        ));
        $timezone = $service ? $service->timezone : 'UTC';
        
        // Get the day of week
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
        
        // Default to available if not explicitly set to unavailable
        if ($is_available === '') {
            $is_available = '1';
        }
        
        // Get business hours
        $start_time = get_post_meta($service_id, '_' . $day_key . '_start', true);
        $end_time = get_post_meta($service_id, '_' . $day_key . '_end', true);
        
        // Set default times if none are set
        if (empty($start_time)) {
            $start_time = '09:00';
        }
        if (empty($end_time)) {
            $end_time = '17:00';
        }
        
        // Convert times to service timezone
        if ($is_available === '1' && !empty($start_time) && !empty($end_time)) {
            $start_time = Smart_Schedular_Timezone::convert_time($start_time, 'UTC', $timezone);
            $end_time = Smart_Schedular_Timezone::convert_time($end_time, 'UTC', $timezone);
        }
        
        $response = array(
            'available' => ($is_available === '1'),
            'date' => $date,
            'day' => $day_key,
            'business_hours' => array(
                'start' => $start_time,
                'end' => $end_time
            ),
            'timezone' => $timezone
        );
        
        // Log response
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Check service availability response: ' . print_r($response, true));
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * Get available time slots for a service on a given date
     */
    public function get_available_time_slots() {
        // Verify nonce
        if (!check_ajax_referer('smart_schedular_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }
        
        global $wpdb;
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        
        if (empty($service_id) || empty($date)) {
            wp_send_json_error(array('message' => 'Missing required parameters'));
        }
        
        // Get service timezone
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT timezone FROM {$wpdb->prefix}smart_schedular_services WHERE id = %d",
            $service_id
        ));
        $timezone = $service ? $service->timezone : 'UTC';
        
        // Get available time slots
        $time_slots = Smart_Schedular_Timezone::get_available_time_slots($service_id, $date, $timezone);
        
        wp_send_json_success(array(
            'time_slots' => $time_slots,
            'date' => $date,
            'timezone' => $timezone
        ));
    }
} 