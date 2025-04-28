<?php

/**
 * Fired during plugin activation
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Smart_Schedular
 * @subpackage Smart_Schedular/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Smart_Schedular
 * @subpackage Smart_Schedular/includes
 * @author     Smart Schedular
 */
class Smart_Schedular_Activator {

    /**
     * Create the necessary tables on plugin activation.
     *
     * @since    1.0.0
     */
    public static function activate() {
        self::create_tables();
        self::maybe_update_tables();
    }
    
    /**
     * Create the custom database tables for appointment booking.
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // First, let's drop the existing tables if they exist
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}smart_schedular_time_slots");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}smart_schedular_blocked_dates");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}smart_schedular_appointments");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}smart_schedular_services");
        
        // Table for services
        $table_services = $wpdb->prefix . 'smart_schedular_services';
        
        $sql_services = "CREATE TABLE $table_services (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            duration int NOT NULL,
            description text DEFAULT '',
            logo_url varchar(255) DEFAULT '',
            color varchar(20) DEFAULT '#1a73e8',
            font_family varchar(50) DEFAULT 'system-ui, -apple-system, sans-serif',
            available_days varchar(20) DEFAULT '1,2,3,4,5', 
            timezone VARCHAR(10) DEFAULT 'UTC',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Table for appointments
        $table_name = $wpdb->prefix . 'smart_schedular_appointments';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            service_id mediumint(9) NOT NULL,
            customer_name varchar(100) NOT NULL,
            customer_email varchar(100) NOT NULL,
            customer_phone varchar(20) DEFAULT '',
            customer_message text DEFAULT '',
            appointment_date date NOT NULL,
            appointment_time time NOT NULL,
            appointment_timezone varchar(50) NOT NULL,
            duration int NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            google_event_id varchar(255) DEFAULT NULL,
            google_meet_link varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Table for available time slots
        $table_slots = $wpdb->prefix . 'smart_schedular_time_slots';
        
        $sql_slots = "CREATE TABLE $table_slots (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            service_id mediumint(9) NOT NULL,
            day_of_week tinyint(1) NOT NULL, 
            start_time time NOT NULL,
            end_time time NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Table for blocked dates
        $table_blocked = $wpdb->prefix . 'smart_schedular_blocked_dates';
        
        $sql_blocked = "CREATE TABLE $table_blocked (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            service_id mediumint(9) NOT NULL,
            blocked_date date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY service_date (service_id, blocked_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create tables (execute in the correct dependency order)
        dbDelta($sql_services);
        dbDelta($sql);
        dbDelta($sql_slots);
        dbDelta($sql_blocked);
        
        // Insert default service
        $default_service = array(
            'name' => 'Software Demo',
            'duration' => 30,
            'description' => 'Our team will facilitate a demo targeted to your needs. Learn more about how our product can work for you and your team!',
            'color' => '#1a73e8',
            'font_family' => 'system-ui, -apple-system, sans-serif',
            'available_days' => '1,2,3,4,5',
        );
        
        $wpdb->insert($table_services, $default_service);
        
        // Insert default time slots for the service
        $service_id = $wpdb->insert_id;
        $default_slots = array(
            array('service_id' => $service_id, 'day_of_week' => 1, 'start_time' => '08:00:00', 'end_time' => '17:00:00'),
            array('service_id' => $service_id, 'day_of_week' => 2, 'start_time' => '08:00:00', 'end_time' => '17:00:00'),
            array('service_id' => $service_id, 'day_of_week' => 3, 'start_time' => '08:00:00', 'end_time' => '17:00:00'),
            array('service_id' => $service_id, 'day_of_week' => 4, 'start_time' => '08:00:00', 'end_time' => '17:00:00'),
            array('service_id' => $service_id, 'day_of_week' => 5, 'start_time' => '08:00:00', 'end_time' => '17:00:00'),
        );
        
        foreach ($default_slots as $slot) {
            $wpdb->insert($table_slots, $slot);
        }
    }

    /**
     * Update existing tables if they need modification
     *
     * @since    1.0.0
     */
    public static function maybe_update_tables() {
        global $wpdb;
        
        // Check if the appointments table exists
        $table_name = $wpdb->prefix . 'smart_schedular_appointments';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        if (!$table_exists) {
            // Table doesn't exist yet, nothing to update
            return;
        }
        
        // Check for customer_message column in appointments table
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM `{$table_name}` LIKE 'customer_message'");
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `customer_message` text DEFAULT '' AFTER `customer_phone`");
        }
    }
} 