<?php

class Smart_Schedular_Appointment {

    public function save_appointment($data) {
        // Create appointment post
        $appointment_data = array(
            'post_title'    => sprintf(__('Appointment for %s', 'smart-schedular'), $data['customer_name']),
            'post_type'     => 'smart_schedular_appt',
            'post_status'   => 'publish'
        );
        
        if (isset($data['appointment_id']) && !empty($data['appointment_id'])) {
            $appointment_data['ID'] = intval($data['appointment_id']);
            $appointment_id = wp_update_post($appointment_data);
        } else {
            $appointment_id = wp_insert_post($appointment_data);
        }
        
        if (!is_wp_error($appointment_id)) {
            // Save appointment meta
            update_post_meta($appointment_id, '_service_id', intval($data['service_id']));
            update_post_meta($appointment_id, '_appointment_date', sanitize_text_field($data['appointment_date']));
            update_post_meta($appointment_id, '_appointment_time', sanitize_text_field($data['appointment_time']));
            update_post_meta($appointment_id, '_customer_name', sanitize_text_field($data['customer_name']));
            update_post_meta($appointment_id, '_customer_email', sanitize_email($data['customer_email']));
            update_post_meta($appointment_id, '_customer_phone', sanitize_text_field($data['customer_phone']));
            update_post_meta($appointment_id, '_appointment_status', 'pending');
            
            // Send notifications
            if (!isset($data['appointment_id']) || empty($data['appointment_id'])) {
                // Only send notifications for new appointments
                Smart_Schedular_Notifications::send_appointment_notifications($appointment_id);
            }
            
            return $appointment_id;
        }
        
        return false;
    }
} 