<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Smart_Schedular
 * @subpackage Smart_Schedular/frontend
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Smart_Schedular
 * @subpackage Smart_Schedular/frontend
 * @author     Smart Schedular
 */
class Smart_Schedular_Public {

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
     * The booking handler.
     *
     * @since    1.0.0
     * @access   private
     * @var      Smart_Schedular_Booking    $booking    The booking handler.
     */
    private $booking;

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
        
        // Initialize booking handler
        require_once SMART_SCHEDULAR_PLUGIN_DIR . 'includes/class-smart-schedular-booking.php';
        $this->booking = new Smart_Schedular_Booking($plugin_name, $version);
        
        // Register AJAX actions
        $this->register_ajax_actions();
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, SMART_SCHEDULAR_PLUGIN_URL . 'assets/css/smart-schedular-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // jQuery and jQuery UI
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-datepicker');
        
        // Moment.js for date/time handling
        wp_enqueue_script('moment', 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js', array(), '2.29.1', true);
        
        // FullCalendar
        wp_enqueue_script('fullcalendar', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js', array('jquery', 'moment'), '3.10.2', true);
        
        // Plugin main JS
        wp_enqueue_script($this->plugin_name, SMART_SCHEDULAR_PLUGIN_URL . 'public/js/smart-schedular-public.js', array('jquery', 'moment', 'fullcalendar'), $this->version, true);
        
        // Localize script
        $this->localize_scripts();
    }
    
    /**
     * Register shortcodes for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('smart_schedular', array($this, 'booking_form_shortcode'));
    }
    
    /**
     * Booking form shortcode callback.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            Shortcode output.
     */
    public function booking_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'service_id' => 0,
        ), $atts, 'smart_schedular');
        
        $service_id = intval($atts['service_id']);
        
        if (empty($service_id)) {
            return '<p class="smart-schedular-error">' . __('Please specify a service ID.', 'smart-schedular') . '</p>';
        }
        
        // Get service details
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_schedular_services';
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $service_id
        ));
        
        if (!$service) {
            return '<p class="smart-schedular-error">' . __('Service not found.', 'smart-schedular') . '</p>';
        }
        
        // Enqueue required scripts and styles
        wp_enqueue_style('fullcalendar', 'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css');
        
        // Create form output
        ob_start();
        ?>
        <div class="smart-schedular-booking-wrapper" id="smart-schedular-booking-wrapper">
            <input type="hidden" id="service_id" name="service_id" value="<?php echo esc_attr($service_id); ?>">
            
            <!-- Service info section -->
            <div class="service-info-section">
                <div class="service-avatar">
                    <?php if (!empty($service->logo_url)): ?>
                        <img src="<?php echo esc_url($service->logo_url); ?>" alt="<?php echo esc_attr($service->name); ?>">
                    <?php else: ?>
                        <img src="<?php echo SMART_SCHEDULAR_PLUGIN_URL; ?>assets/images/avatar-placeholder.png" alt="Service Avatar">
                    <?php endif; ?>
                </div>
                <div class="service-details">
                    <div class="service-provider">Joy</div>
                    <h2 class="service-title">Demo Call</h2>
                    <div class="service-duration">
                        <span class="icon">⏱</span> <?php echo esc_html($service->duration); ?> <?php _e('min', 'smart-schedular'); ?>
                    </div>
                    <div class="service-description">A member of our team will walk you through the platform and demonstrate how our solution can help!</div>
                </div>
            </div>
            
            <div class="calendar-header" style="padding: 20px 25px 10px 25px; border-bottom: 0;">
                <?php _e('Select a Date & Time', 'smart-schedular'); ?>
                <div class="calendar-date" id="selected-date"></div>
            </div>
            
            <!-- Main booking section -->
            <div class="booking-section">
                <!-- Calendar section -->
                <div class="calendar-section">
                    <div id="smart-schedular-calendar"></div>
                </div>
                
                <!-- Time slots section -->
                <div class="time-slots-section" id="smart-schedular-time-slots">
                    <!-- Time slots will be populated here -->
                    <div style="text-align: center; color: #5f6368; padding: 20px 0;">
                        <p><?php _e('Select a date to view available time slots', 'smart-schedular'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Timezone section -->
            <div class="timezone-section">
                <span class="timezone-icon">🌐</span> Eastern Time - US & Canada (GMT-5)
            </div>
            
            <!-- Booking form -->
            <div id="smart-schedular-booking-form" style="display: none;">
                <h3><?php _e('Complete Your Booking', 'smart-schedular'); ?></h3>
                
                <form id="appointment-form">
                    <input type="hidden" id="appointment_date" name="appointment_date">
                    <input type="hidden" id="appointment_time" name="appointment_time">
                    
                    <div class="form-group">
                        <label for="customer_name"><?php _e('Your Name', 'smart-schedular'); ?> <span class="required">*</span></label>
                        <input type="text" id="customer_name" name="customer_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_email"><?php _e('Email Address', 'smart-schedular'); ?> <span class="required">*</span></label>
                        <input type="email" id="customer_email" name="customer_email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_phone"><?php _e('Phone Number', 'smart-schedular'); ?></label>
                        <input type="tel" id="customer_phone" name="customer_phone" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_message"><?php _e('Message (Optional)', 'smart-schedular'); ?></label>
                        <textarea id="customer_message" name="customer_message" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><?php _e('Book Appointment', 'smart-schedular'); ?></button>
                </form>
                
                <div id="booking-confirmation" style="display: none;"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Adjust brightness of a color
     * 
     * @since 1.0.0
     * @param string $hex Hex color code
     * @param float $steps Steps to adjust (positive for lighter, negative for darker)
     * @return string Adjusted hex color
     */
    public function adjustBrightness($hex, $steps) {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Adjust brightness
        if ($steps > 0) {
            // Lighter
            $r = min(255, $r + (255 - $r) * $steps);
            $g = min(255, $g + (255 - $g) * $steps);
            $b = min(255, $b + (255 - $b) * $steps);
        } else {
            // Darker
            $steps = abs($steps);
            $r = max(0, $r * (1 - $steps));
            $g = max(0, $g * (1 - $steps));
            $b = max(0, $b * (1 - $steps));
        }
        
        // Convert back to hex
        $r = str_pad(dechex(round($r)), 2, '0', STR_PAD_LEFT);
        $g = str_pad(dechex(round($g)), 2, '0', STR_PAD_LEFT);
        $b = str_pad(dechex(round($b)), 2, '0', STR_PAD_LEFT);
        
        return '#' . $r . $g . $b;
    }
    
    /**
     * Ajax handler for getting available dates.
     *
     * @since    1.0.0
     */
    public function get_available_dates() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'smart_schedular_public_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $month = isset($_POST['month']) ? sanitize_text_field($_POST['month']) : current_time('Y-m');
        $timezone = isset($_POST['timezone']) ? sanitize_text_field($_POST['timezone']) : 'UTC';
        
        if (!$service_id) {
            wp_send_json_error('Invalid service ID');
            return;
        }
        
        $dates = $this->booking->get_available_dates($service_id, $month, $timezone);
        
        wp_send_json_success(array(
            'dates' => $dates,
            'month' => $month
        ));
    }
    
    /**
     * Ajax handler for getting available times.
     *
     * @since    1.0.0
     */
    public function get_available_times() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'smart_schedular_public_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $timezone = isset($_POST['timezone']) ? sanitize_text_field($_POST['timezone']) : 'UTC';
        
        if (!$service_id || !$date) {
            wp_send_json_error('Invalid service ID or date');
            return;
        }
        
        $times = $this->booking->get_available_time_slots($service_id, $date, $timezone);
        
        wp_send_json_success(array(
            'times' => $times,
            'date' => $date
        ));
    }
    
    /**
     * Ajax handler for booking appointments.
     *
     * @since    1.0.0
     */
    public function book_appointment() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'smart_schedular_nonce')) {
            wp_send_json_error('Invalid security token. Please refresh the page and try again.');
            return;
        }
        
        // Validate required fields
        $required_fields = array('service_id', 'appointment_date', 'appointment_time', 'customer_name', 'customer_email');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error('Please fill in all required fields.');
                return;
            }
        }
        
        // Validate email
        if (!is_email($_POST['customer_email'])) {
            wp_send_json_error('Please enter a valid email address.');
            return;
        }
        
        // Get service details
        global $wpdb;
        $service_id = intval($_POST['service_id']);
        $service_table = $wpdb->prefix . 'smart_schedular_services';
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $service_table WHERE id = %d",
            $service_id
        ));
        
        if (!$service) {
            wp_send_json_error('Selected service not found.');
            return;
        }
        
        // Format data to match database schema
        $appointment_data = array(
            'service_id' => $service_id,
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '',
            'customer_message' => isset($_POST['customer_message']) ? sanitize_textarea_field($_POST['customer_message']) : '',
            'appointment_date' => sanitize_text_field($_POST['appointment_date']),
            'appointment_time' => sanitize_text_field($_POST['appointment_time']),
            'appointment_timezone' => $service->timezone ? $service->timezone : 'UTC',
            'duration' => $service->duration ? intval($service->duration) : 30,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );
        
        // Debug information
        error_log('Attempting to insert appointment: ' . print_r($appointment_data, true));
        
        // Insert appointment
        $table_name = $wpdb->prefix . 'smart_schedular_appointments';
        $result = $wpdb->insert($table_name, $appointment_data);
        
        if (!$result) {
            $db_error = $wpdb->last_error;
            error_log('Database insert error: ' . $db_error);
            wp_send_json_error('Failed to save appointment. Database error: ' . $db_error);
            return;
        }
        
        $appointment_id = $wpdb->insert_id;
        
        // Format date and time for display
        $date_obj = DateTime::createFromFormat('Y-m-d', $appointment_data['appointment_date']);
        $formatted_date = $date_obj->format('l, F j, Y');
        
        $time_obj = DateTime::createFromFormat('H:i', $appointment_data['appointment_time']);
        $formatted_time = $time_obj->format('g:i A');
        
        // Send confirmation email to customer
        $to = $appointment_data['customer_email'];
        $subject = 'Your Appointment Confirmation - ' . $service->name;
        
        // Get timezone label
        $timezone = $service->timezone ?? 'UTC';
        $timezone_labels = array(
            'UTC' => 'UTC (GMT+0)',
            'EST' => 'Eastern Time - US & Canada (GMT-5)',
            'PST' => 'Pacific Time - US & Canada (GMT-8)',
            'IST' => 'India Standard Time (GMT+5:30)'
        );
        $timezone_label = isset($timezone_labels[$timezone]) ? $timezone_labels[$timezone] : $timezone;
        
        // Email content
        $message = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #3d9df6; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .appointment-details { background-color: #f5f5f5; padding: 15px; margin: 15px 0; }
                .footer { font-size: 12px; color: #666; padding-top: 20px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Appointment Confirmation</h2>
                </div>
                <div class="content">
                    <p>Dear ' . esc_html($appointment_data['customer_name']) . ',</p>
                    <p>Thank you for booking an appointment with us. Your appointment has been confirmed.</p>
                    
                    <div class="appointment-details">
                        <p><strong>Service:</strong> ' . esc_html($service->name) . '</p>
                        <p><strong>Date:</strong> ' . esc_html($formatted_date) . '</p>
                        <p><strong>Time:</strong> ' . esc_html($formatted_time) . ' (' . esc_html($timezone_label) . ')</p>
                        <p><strong>Duration:</strong> ' . esc_html($service->duration) . ' minutes</p>
                    </div>
                    
                    <p>If you need to reschedule or cancel this appointment, please contact us as soon as possible.</p>
                    <p>We look forward to meeting with you!</p>
                </div>
                <div class="footer">
                    <p>This is an automated email. Please do not reply to this message.</p>
                    <p>' . esc_html(get_bloginfo('name')) . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>'
        );
        
        // Send email
        $email_sent = wp_mail($to, $subject, $message, $headers);
        
        // Send notification to admin
        $admin_email = get_option('admin_email');
        $admin_subject = 'New Appointment Booking - ' . $service->name;
        
        $admin_message = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #3d9df6; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .appointment-details { background-color: #f5f5f5; padding: 15px; margin: 15px 0; }
                .customer-details { background-color: #f5f5f5; padding: 15px; margin: 15px 0; }
                .footer { font-size: 12px; color: #666; padding-top: 20px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>New Appointment Booking</h2>
                </div>
                <div class="content">
                    <p>A new appointment has been booked.</p>
                    
                    <div class="appointment-details">
                        <p><strong>Service:</strong> ' . esc_html($service->name) . '</p>
                        <p><strong>Date:</strong> ' . esc_html($formatted_date) . '</p>
                        <p><strong>Time:</strong> ' . esc_html($formatted_time) . ' (' . esc_html($timezone_label) . ')</p>
                        <p><strong>Duration:</strong> ' . esc_html($service->duration) . ' minutes</p>
                    </div>
                    
                    <div class="customer-details">
                        <p><strong>Customer Name:</strong> ' . esc_html($appointment_data['customer_name']) . '</p>
                        <p><strong>Email:</strong> ' . esc_html($appointment_data['customer_email']) . '</p>';
        
        if (!empty($appointment_data['customer_phone'])) {
            $admin_message .= '<p><strong>Phone:</strong> ' . esc_html($appointment_data['customer_phone']) . '</p>';
        }
        
        if (!empty($appointment_data['customer_message'])) {
            $admin_message .= '<p><strong>Message:</strong> ' . esc_html($appointment_data['customer_message']) . '</p>';
        }
        
        $admin_message .= '
                    </div>
                    
                    <p>You can manage this appointment from the admin dashboard.</p>
                </div>
                <div class="footer">
                    <p>This is an automated email. Please do not reply to this message.</p>
                    <p>' . esc_html(get_bloginfo('name')) . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        // Send admin notification
        $admin_email_sent = wp_mail($admin_email, $admin_subject, $admin_message, $headers);
        
        // Return success response
        wp_send_json_success(array(
            'appointment_id' => $appointment_id,
            'message' => 'Appointment booked successfully.',
            'email_sent' => $email_sent
        ));
    }

    /**
     * Add inline JavaScript to localize script variables
     */
    public function localize_scripts() {
        wp_localize_script($this->plugin_name, 'smart_schedular_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smart_schedular_nonce'),
            'current_date' => current_time('Y-m-d'),
            'current_month' => current_time('Y-m'),
            'loading_text' => __('Loading...', 'smart-schedular'),
            'success_booking_text' => __('Your appointment has been booked successfully! You will receive a confirmation email shortly.', 'smart-schedular'),
            'error_booking_text' => __('An error occurred while booking your appointment. Please try again.', 'smart-schedular')
        ));
    }

    /**
     * Register AJAX actions.
     *
     * @since    1.0.0
     */
    public function register_ajax_actions() {
        // Public AJAX actions
        add_action('wp_ajax_check_service_availability', array($this, 'check_service_availability'));
        add_action('wp_ajax_nopriv_check_service_availability', array($this, 'check_service_availability'));
        
        add_action('wp_ajax_get_available_time_slots', array($this, 'get_available_time_slots'));
        add_action('wp_ajax_nopriv_get_available_time_slots', array($this, 'get_available_time_slots'));
        
        add_action('wp_ajax_book_appointment', array($this, 'book_appointment'));
        add_action('wp_ajax_nopriv_book_appointment', array($this, 'book_appointment'));
    }

    /**
     * AJAX handler for checking service availability on a specific date.
     * 
     * @since    1.0.0
     */
    public function check_service_availability() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'smart_schedular_nonce')) {
            wp_send_json_error('Invalid security token.');
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        
        if (!$service_id || !$date) {
            wp_send_json_error('Missing required parameters.');
            return;
        }
        
        // For demo purposes, always return available
        wp_send_json_success(array(
            'available' => true,
            'service_id' => $service_id,
            'date' => $date
        ));
    }

    /**
     * AJAX handler for getting available time slots.
     * 
     * @since    1.0.0
     */
    public function get_available_time_slots() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'smart_schedular_nonce')) {
            wp_send_json_error('Invalid security token.');
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        
        if (!$service_id || !$date) {
            wp_send_json_error('Missing required parameters.');
            return;
        }
        
        // For demo purposes, return sample time slots
        $time_slots = array(
            '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
            '13:00', '13:30', '14:00', '14:30', '15:00', '15:30'
        );
        
        wp_send_json_success(array(
            'time_slots' => $time_slots,
            'service_id' => $service_id,
            'date' => $date,
            'timezone' => 'EST'
        ));
    }
} 