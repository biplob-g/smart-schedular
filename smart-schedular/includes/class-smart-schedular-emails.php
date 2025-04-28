<?php

/**
 * The email-specific functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Smart_Schedular
 * @subpackage Smart_Schedular/includes
 */

/**
 * The email-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for email notifications.
 *
 * @package    Smart_Schedular
 * @subpackage Smart_Schedular/includes
 * @author     Smart Schedular
 */
class Smart_Schedular_Emails {

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
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    /**
     * Send confirmation email to the customer when booking is made
     *
     * @since    1.0.0
     * @param    array    $appointment    The appointment data.
     * @return   bool     Whether the email was sent successfully.
     */
    public function send_customer_confirmation_email($appointment) {
        global $wpdb;
        
        // Get service details
        $service_table = $wpdb->prefix . 'smart_schedular_services';
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $service_table WHERE id = %d",
            $appointment['service_id']
        ));
        
        if (!$service) {
            return false;
        }
        
        $to = $appointment['customer_email'];
        $subject = 'Appointment Confirmation - ' . $service->name;
        
        // Format date and time
        $date = date_i18n(get_option('date_format'), strtotime($appointment['appointment_date']));
        $time = date_i18n(get_option('time_format'), strtotime($appointment['appointment_time']));
        
        $message = '<html><body>';
        $message .= '<h2>Your appointment is scheduled!</h2>';
        $message .= '<p>Thank you for booking an appointment with us. Here are the details:</p>';
        $message .= '<p><strong>Service:</strong> ' . esc_html($service->name) . '</p>';
        $message .= '<p><strong>Date:</strong> ' . esc_html($date) . '</p>';
        $message .= '<p><strong>Time:</strong> ' . esc_html($time) . ' (' . esc_html($appointment['appointment_timezone']) . ')</p>';
        $message .= '<p><strong>Duration:</strong> ' . esc_html($service->duration) . ' minutes</p>';
        $message .= '<p>Your appointment is now pending confirmation. You will receive another email once it has been confirmed.</p>';
        $message .= '<p>Thank you,<br>' . get_bloginfo('name') . '</p>';
        $message .= '</body></html>';
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send notification email to the admin when a new booking is made
     *
     * @since    1.0.0
     * @param    array    $appointment    The appointment data.
     * @return   bool     Whether the email was sent successfully.
     */
    public function send_admin_notification_email($appointment) {
        global $wpdb;
        
        // Get service details
        $service_table = $wpdb->prefix . 'smart_schedular_services';
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $service_table WHERE id = %d",
            $appointment['service_id']
        ));
        
        if (!$service) {
            return false;
        }
        
        $admin_email = get_option('admin_email');
        $subject = 'New Appointment Booking - ' . $service->name;
        
        // Format date and time
        $date = date_i18n(get_option('date_format'), strtotime($appointment['appointment_date']));
        $time = date_i18n(get_option('time_format'), strtotime($appointment['appointment_time']));
        
        $message = '<html><body>';
        $message .= '<h2>New Appointment Booking</h2>';
        $message .= '<p>A new appointment has been scheduled. Here are the details:</p>';
        $message .= '<p><strong>Service:</strong> ' . esc_html($service->name) . '</p>';
        $message .= '<p><strong>Customer:</strong> ' . esc_html($appointment['customer_name']) . '</p>';
        $message .= '<p><strong>Email:</strong> ' . esc_html($appointment['customer_email']) . '</p>';
        
        if (!empty($appointment['customer_phone'])) {
            $message .= '<p><strong>Phone:</strong> ' . esc_html($appointment['customer_phone']) . '</p>';
        }
        
        $message .= '<p><strong>Date:</strong> ' . esc_html($date) . '</p>';
        $message .= '<p><strong>Time:</strong> ' . esc_html($time) . ' (' . esc_html($appointment['appointment_timezone']) . ')</p>';
        $message .= '<p><strong>Duration:</strong> ' . esc_html($service->duration) . ' minutes</p>';
        
        // Add link to the admin page
        $admin_url = admin_url('admin.php?page=smart-schedular-appointments');
        $message .= '<p><a href="' . esc_url($admin_url) . '">Log in to approve or decline this appointment</a></p>';
        
        $message .= '</body></html>';
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Send approval email to the customer with Google Meet link
     *
     * @since    1.0.0
     * @param    array    $appointment    The appointment data with Meet link.
     * @return   bool     Whether the email was sent successfully.
     */
    public function send_customer_approval_email($appointment) {
        global $wpdb;
        
        // Get service details
        $service_table = $wpdb->prefix . 'smart_schedular_services';
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $service_table WHERE id = %d",
            $appointment['service_id']
        ));
        
        if (!$service) {
            return false;
        }
        
        $to = $appointment['customer_email'];
        $subject = 'Appointment Confirmed - ' . $service->name;
        
        // Format date and time
        $date = date_i18n(get_option('date_format'), strtotime($appointment['appointment_date']));
        $time = date_i18n(get_option('time_format'), strtotime($appointment['appointment_time']));
        
        $message = '<html><body>';
        $message .= '<h2>Your appointment has been confirmed!</h2>';
        $message .= '<p>Great news! Your appointment has been confirmed. Here are the details:</p>';
        $message .= '<p><strong>Service:</strong> ' . esc_html($service->name) . '</p>';
        $message .= '<p><strong>Date:</strong> ' . esc_html($date) . '</p>';
        $message .= '<p><strong>Time:</strong> ' . esc_html($time) . ' (' . esc_html($appointment['appointment_timezone']) . ')</p>';
        $message .= '<p><strong>Duration:</strong> ' . esc_html($service->duration) . ' minutes</p>';
        
        if (!empty($appointment['google_meet_link'])) {
            $message .= '<p><strong>Google Meet Link:</strong> <a href="' . esc_url($appointment['google_meet_link']) . '">' . esc_url($appointment['google_meet_link']) . '</a></p>';
            $message .= '<p>Please click on the Google Meet link at the scheduled time to join the meeting.</p>';
        }
        
        $message .= '<p>We look forward to meeting with you!</p>';
        $message .= '<p>Thank you,<br>' . get_bloginfo('name') . '</p>';
        $message .= '</body></html>';
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send decline email to the customer
     *
     * @since    1.0.0
     * @param    array    $appointment    The appointment data.
     * @return   bool     Whether the email was sent successfully.
     */
    public function send_customer_decline_email($appointment) {
        global $wpdb;
        
        // Get service details
        $service_table = $wpdb->prefix . 'smart_schedular_services';
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $service_table WHERE id = %d",
            $appointment['service_id']
        ));
        
        if (!$service) {
            return false;
        }
        
        $to = $appointment['customer_email'];
        $subject = 'Appointment Could Not Be Confirmed - ' . $service->name;
        
        // Format date and time
        $date = date_i18n(get_option('date_format'), strtotime($appointment['appointment_date']));
        $time = date_i18n(get_option('time_format'), strtotime($appointment['appointment_time']));
        
        $message = '<html><body>';
        $message .= '<h2>Appointment Update</h2>';
        $message .= '<p>We regret to inform you that we are unable to confirm your appointment for:</p>';
        $message .= '<p><strong>Service:</strong> ' . esc_html($service->name) . '</p>';
        $message .= '<p><strong>Date:</strong> ' . esc_html($date) . '</p>';
        $message .= '<p><strong>Time:</strong> ' . esc_html($time) . ' (' . esc_html($appointment['appointment_timezone']) . ')</p>';
        $message .= '<p>Please visit our booking page to select another date and time that works for you.</p>';
        $message .= '<p>We apologize for any inconvenience this may have caused.</p>';
        $message .= '<p>Thank you,<br>' . get_bloginfo('name') . '</p>';
        $message .= '</body></html>';
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($to, $subject, $message, $headers);
    }
} 