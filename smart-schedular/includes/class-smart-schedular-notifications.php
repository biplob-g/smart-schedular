<?php

class Smart_Schedular_Notifications {
    
    public static function send_appointment_notifications($appointment_id) {
        // Get appointment details
        $service_id = get_post_meta($appointment_id, '_service_id', true);
        $appointment_date = get_post_meta($appointment_id, '_appointment_date', true);
        $appointment_time = get_post_meta($appointment_id, '_appointment_time', true);
        $customer_name = get_post_meta($appointment_id, '_customer_name', true);
        $customer_email = get_post_meta($appointment_id, '_customer_email', true);
        $customer_phone = get_post_meta($appointment_id, '_customer_phone', true);
        
        // Get service details
        global $wpdb;
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}smart_schedular_services WHERE id = %d",
            $service_id
        ));
        
        // Get settings
        $options = get_option('smart_schedular_options', array());
        
        // Prepare placeholders
        $placeholders = array(
            '{service}' => $service ? $service->name : '',
            '{date}' => $appointment_date,
            '{time}' => $appointment_time,
            '{name}' => $customer_name,
            '{email}' => $customer_email,
            '{phone}' => $customer_phone
        );
        
        // Send admin notification
        $admin_email = isset($options['admin_email']) ? $options['admin_email'] : get_option('admin_email');
        $admin_subject = isset($options['notification_subject']) ? $options['notification_subject'] : 'New Appointment Booking';
        $admin_message = isset($options['notification_message']) ? $options['notification_message'] : '';
        
        $admin_message = str_replace(array_keys($placeholders), array_values($placeholders), $admin_message);
        wp_mail($admin_email, $admin_subject, $admin_message);
        
        // Send customer confirmation
        if (!empty($customer_email)) {
            $customer_subject = isset($options['confirmation_subject']) ? $options['confirmation_subject'] : 'Appointment Confirmation';
            $customer_message = isset($options['confirmation_message']) ? $options['confirmation_message'] : '';
            
            $customer_message = str_replace(array_keys($placeholders), array_values($placeholders), $customer_message);
            wp_mail($customer_email, $customer_subject, $customer_message);
        }
    }
    
    public static function format_date_time($date, $time) {
        $options = get_option('smart_schedular_options', array());
        $date_format = isset($options['date_format']) ? $options['date_format'] : get_option('date_format');
        $time_format = isset($options['time_format']) ? $options['time_format'] : get_option('time_format');
        
        $datetime = strtotime($date . ' ' . $time);
        return array(
            'formatted_date' => date_i18n($date_format, $datetime),
            'formatted_time' => date_i18n($time_format, $datetime)
        );
    }
} 