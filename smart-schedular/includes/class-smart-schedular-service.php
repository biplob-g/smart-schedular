<?php

class Smart_Schedular_Service {

    public function save_service($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_schedular_services';
        
        $service_data = array(
            'name' => sanitize_text_field($data['service_name']),
            'duration' => intval($data['service_duration']),
            'price' => floatval($data['service_price']),
            'description' => isset($data['service_description']) ? wp_kses_post($data['service_description']) : '',
            'color' => isset($data['service_color']) ? sanitize_hex_color($data['service_color']) : '#3788d8',
            'timezone' => isset($data['service_timezone']) ? sanitize_text_field($data['service_timezone']) : 'UTC',
            'updated_at' => current_time('mysql')
        );
        
        if (isset($data['service_id']) && !empty($data['service_id'])) {
            // Update existing service
            $wpdb->update(
                $table_name,
                $service_data,
                array('id' => intval($data['service_id']))
            );
            $service_id = intval($data['service_id']);
        } else {
            // Insert new service
            $service_data['created_at'] = current_time('mysql');
            $wpdb->insert($table_name, $service_data);
            $service_id = $wpdb->insert_id;
        }
        
        // Save business hours with default 9 AM to 6 PM
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        $default_start = '09:00';
        $default_end   = '18:00';
        foreach ($days as $day) {
            $is_available = isset($data[$day . '_available']) ? '1' : '0';
            $start_time   = isset($data[$day . '_start']) ? sanitize_text_field($data[$day . '_start']) : $default_start;
            $end_time     = isset($data[$day . '_end'])   ? sanitize_text_field($data[$day . '_end'])   : $default_end;
            
            // Convert times to UTC for storage
            if ($service_data['timezone'] !== 'UTC') {
                $start_time = Smart_Schedular_Timezone::convert_time($start_time, $service_data['timezone'], 'UTC');
                $end_time = Smart_Schedular_Timezone::convert_time($end_time, $service_data['timezone'], 'UTC');
            }
            
            update_post_meta($service_id, '_' . $day . '_available', $is_available);
            update_post_meta($service_id, '_' . $day . '_start', $start_time);
            update_post_meta($service_id, '_' . $day . '_end', $end_time);
        }
        
        return $service_id;
    }

    public function get_service($service_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_schedular_services';
        
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $service_id
        ), ARRAY_A);
        
        if ($service) {
            // Add business hours to service data
            $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
            $service['business_hours'] = array();
            
            foreach ($days as $day) {
                $start_time = get_post_meta($service_id, '_' . $day . '_start', true);
                $end_time = get_post_meta($service_id, '_' . $day . '_end', true);
                
                // Convert times from UTC to service timezone
                if ($service['timezone'] !== 'UTC') {
                    $start_time = Smart_Schedular_Timezone::convert_time($start_time, 'UTC', $service['timezone']);
                    $end_time = Smart_Schedular_Timezone::convert_time($end_time, 'UTC', $service['timezone']);
                }
                
                $service['business_hours'][$day] = array(
                    'available' => get_post_meta($service_id, '_' . $day . '_available', true),
                    'start' => $start_time,
                    'end' => $end_time
                );
            }
        }
        
        return $service;
    }

    public function get_business_hours($service_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_schedular_services';
        
        // Get service timezone
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT timezone FROM $table_name WHERE id = %d",
            $service_id
        ));
        
        $timezone = $service ? $service->timezone : 'UTC';
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        $business_hours = array();
        
        foreach ($days as $day) {
            $start_time = get_post_meta($service_id, '_' . $day . '_start', true);
            $end_time = get_post_meta($service_id, '_' . $day . '_end', true);
            
            // Convert times from UTC to service timezone
            if ($timezone !== 'UTC') {
                $start_time = Smart_Schedular_Timezone::convert_time($start_time, 'UTC', $timezone);
                $end_time = Smart_Schedular_Timezone::convert_time($end_time, 'UTC', $timezone);
            }
            
            $business_hours[$day] = array(
                'available' => get_post_meta($service_id, '_' . $day . '_available', true),
                'start' => $start_time,
                'end' => $end_time,
                'timezone' => $timezone
            );
        }
        
        return $business_hours;
    }
} 