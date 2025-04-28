<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Smart_Schedular
 * @subpackage Smart_Schedular/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Smart_Schedular
 * @subpackage Smart_Schedular/admin
 * @author     Smart Schedular
 */
class Smart_Schedular_Admin {

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
        
        // Initialize handlers
        require_once SMART_SCHEDULAR_PLUGIN_DIR . 'includes/class-smart-schedular-booking.php';
        require_once SMART_SCHEDULAR_PLUGIN_DIR . 'includes/class-smart-schedular-google-api.php';
        
        $this->booking = new Smart_Schedular_Booking($plugin_name, $version);
        $this->google_api = new Smart_Schedular_Google_API($plugin_name, $version);
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style('jquery-ui-datepicker', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        wp_enqueue_style($this->plugin_name, SMART_SCHEDULAR_PLUGIN_URL . 'assets/css/smart-schedular-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
        
        wp_enqueue_script($this->plugin_name, SMART_SCHEDULAR_PLUGIN_URL . 'assets/js/smart-schedular-admin.js', array('jquery', 'jquery-ui-datepicker', 'wp-color-picker'), $this->version, false);
        
        wp_localize_script($this->plugin_name, 'smart_schedular', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smart_schedular_admin_nonce'),
        ));
    }
    
    /**
     * Add menu items to the admin dashboard.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        // Main menu
        add_menu_page(
            'Smart Schedular',
            'Smart Schedular',
            'manage_options',
            'smart-schedular',
            array($this, 'display_plugin_admin_dashboard'),
            'dashicons-calendar-alt',
            30
        );
        
        // Appointments submenu
        add_submenu_page(
            'smart-schedular',
            'Appointments',
            'Appointments',
            'manage_options',
            'smart-schedular-appointments',
            array($this, 'display_appointments_page')
        );
        
        // Services submenu
        add_submenu_page(
            'smart-schedular',
            'Services',
            'Services',
            'manage_options',
            'smart-schedular-services',
            array($this, 'display_services_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'smart-schedular',
            'Settings',
            'Settings',
            'manage_options',
            'smart-schedular-settings',
            array($this, 'display_settings_page')
        );
    }
    
    /**
     * Register settings for the plugin.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Google Calendar API settings
        register_setting('smart_schedular_settings', 'smart_schedular_google_client_id');
        register_setting('smart_schedular_settings', 'smart_schedular_google_client_secret');
        register_setting('smart_schedular_settings', 'smart_schedular_google_refresh_token');
        
        // Email settings
        register_setting('smart_schedular_settings', 'smart_schedular_admin_email');
        register_setting('smart_schedular_settings', 'smart_schedular_email_from_name');
        register_setting('smart_schedular_settings', 'smart_schedular_email_from_email');
    }
    
    /**
     * Render the dashboard page.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_dashboard() {
        include_once('partials/smart-schedular-admin-dashboard.php');
    }
    
    /**
     * Render the appointments page.
     *
     * @since    1.0.0
     */
    public function display_appointments_page() {
        $appointments = $this->booking->get_appointments();
        include_once('partials/smart-schedular-admin-appointments.php');
    }
    
    /**
     * Render the services page.
     *
     * @since    1.0.0
     */
    public function display_services_page() {
        $services = $this->booking->get_services();
        include_once('partials/smart-schedular-admin-services.php');
    }
    
    /**
     * Render the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        include_once('partials/smart-schedular-admin-settings.php');
    }
    
    /**
     * Ajax handler for approving appointments.
     *
     * @since    1.0.0
     */
    public function approve_appointment() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'smart_schedular_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
        
        if (!$appointment_id) {
            wp_send_json_error('Invalid appointment ID');
            return;
        }
        
        $result = $this->booking->approve_appointment($appointment_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Ajax handler for declining appointments.
     *
     * @since    1.0.0
     */
    public function decline_appointment() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'smart_schedular_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
        
        if (!$appointment_id) {
            wp_send_json_error('Invalid appointment ID');
            return;
        }
        
        $result = $this->booking->decline_appointment($appointment_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Ajax handler for saving services.
     *
     * @since    1.0.0
     */
    public function save_service() {
        global $wpdb;
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'smart_schedular_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 30;
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $color = isset($_POST['color']) ? sanitize_hex_color($_POST['color']) : '#1a73e8';
        $logo_url = isset($_POST['logo_url']) ? esc_url_raw($_POST['logo_url']) : '';
        $font_family = isset($_POST['font_family']) ? sanitize_text_field($_POST['font_family']) : 'system-ui, -apple-system, sans-serif';
        $available_days = isset($_POST['available_days']) ? sanitize_text_field($_POST['available_days']) : '1,2,3,4,5';
        
        if (empty($name)) {
            wp_send_json_error('Service name is required');
            return;
        }
        
        $data = array(
            'name' => $name,
            'duration' => $duration,
            'description' => $description,
            'color' => $color,
            'logo_url' => $logo_url,
            'font_family' => $font_family,
            'available_days' => $available_days,
            'updated_at' => current_time('mysql')
        );
        
        $table_name = $wpdb->prefix . 'smart_schedular_services';
        
        if ($service_id > 0) {
            // Update existing service
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => $service_id)
            );
            
            $message = 'Service updated successfully';
        } else {
            // Create new service
            $data['created_at'] = current_time('mysql');
            
            $result = $wpdb->insert($table_name, $data);
            $service_id = $wpdb->insert_id;
            
            // Create default time slots for the service
            $slots_table = $wpdb->prefix . 'smart_schedular_time_slots';
            $days = explode(',', $available_days);
            
            foreach ($days as $day) {
                $wpdb->insert(
                    $slots_table,
                    array(
                        'service_id' => $service_id,
                        'day_of_week' => intval($day),
                        'start_time' => '08:00:00',
                        'end_time' => '17:00:00',
                        'created_at' => current_time('mysql')
                    )
                );
            }
            
            $message = 'Service created successfully';
        }
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => $message,
                'service_id' => $service_id
            ));
        } else {
            wp_send_json_error('Failed to save service');
        }
    }
    
    /**
     * Ajax handler for saving time slots.
     *
     * @since    1.0.0
     */
    public function save_time_slots() {
        global $wpdb;
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'smart_schedular_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $slots = isset($_POST['slots']) ? $_POST['slots'] : array();
        
        if (!$service_id) {
            wp_send_json_error('Invalid service ID');
            return;
        }
        
        $slots_table = $wpdb->prefix . 'smart_schedular_time_slots';
        
        // Delete existing slots for this service
        $wpdb->delete($slots_table, array('service_id' => $service_id));
        
        // Insert new slots
        foreach ($slots as $slot) {
            $day_of_week = isset($slot['day']) ? intval($slot['day']) : 0;
            $start_time = isset($slot['start']) ? sanitize_text_field($slot['start']) : '08:00:00';
            $end_time = isset($slot['end']) ? sanitize_text_field($slot['end']) : '17:00:00';
            
            if ($day_of_week >= 1 && $day_of_week <= 7) {
                $wpdb->insert(
                    $slots_table,
                    array(
                        'service_id' => $service_id,
                        'day_of_week' => $day_of_week,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'created_at' => current_time('mysql')
                    )
                );
            }
        }
        
        wp_send_json_success(array(
            'message' => 'Time slots saved successfully'
        ));
    }
    
    /**
     * Ajax handler for blocking dates.
     *
     * @since    1.0.0
     */
    public function block_date() {
        global $wpdb;
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'smart_schedular_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        
        if (!$service_id || !$date) {
            wp_send_json_error('Invalid service ID or date');
            return;
        }
        
        $table_name = $wpdb->prefix . 'smart_schedular_blocked_dates';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'service_id' => $service_id,
                'blocked_date' => $date,
                'created_at' => current_time('mysql')
            )
        );
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Date blocked successfully'
            ));
        } else {
            wp_send_json_error('Failed to block date');
        }
    }
    
    /**
     * Ajax handler for unblocking dates.
     *
     * @since    1.0.0
     */
    public function unblock_date() {
        global $wpdb;
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'smart_schedular_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        
        if (!$service_id || !$date) {
            wp_send_json_error('Invalid service ID or date');
            return;
        }
        
        $table_name = $wpdb->prefix . 'smart_schedular_blocked_dates';
        
        $result = $wpdb->delete(
            $table_name,
            array(
                'service_id' => $service_id,
                'blocked_date' => $date
            )
        );
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Date unblocked successfully'
            ));
        } else {
            wp_send_json_error('Failed to unblock date');
        }
    }
} 