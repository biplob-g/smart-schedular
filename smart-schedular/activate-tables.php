<?php
/**
 * Force Table Creation Script
 * 
 * This script will force recreate all database tables for the Smart Schedular plugin.
 */

// Load WordPress environment
require_once '../../../../wp-load.php';

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied');
}

// Table creation function
function create_smart_schedular_tables() {
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
    $result1 = dbDelta($sql_services);
    $result2 = dbDelta($sql);
    $result3 = dbDelta($sql_slots);
    $result4 = dbDelta($sql_blocked);
    
    // Insert default service
    $default_service = array(
        'name' => 'Software Demo',
        'duration' => 30,
        'description' => 'Our team will facilitate a demo targeted to your needs. Learn more about how our product can work for you and your team!',
        'color' => '#1a73e8',
        'font_family' => 'system-ui, -apple-system, sans-serif',
        'available_days' => '1,2,3,4,5',
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
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
    
    return array(
        'services' => $result1,
        'appointments' => $result2,
        'slots' => $result3,
        'blocked_dates' => $result4,
        'default_service_id' => $service_id
    );
}

// Execute the table creation
$result = create_smart_schedular_tables();

// Display results
echo '<html><head><title>Smart Schedular Table Activation</title>';
echo '<style>
    body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
    h1 { color: #2271b1; }
    .success { color: green; }
    .error { color: red; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    table, th, td { border: 1px solid #ddd; }
    th, td { padding: 12px; text-align: left; }
    th { background-color: #f2f2f2; }
    .button { display: inline-block; background-color: #2271b1; color: white; padding: 10px 15px; 
              text-decoration: none; border-radius: 3px; margin-top: 20px; }
</style>';
echo '</head><body>';

echo '<h1>Smart Schedular Database Tables Created</h1>';
echo '<p class="success">✓ All tables have been recreated successfully!</p>';

echo '<h2>Default Service</h2>';
echo '<p>A default service has been created with ID: <strong>' . $result['default_service_id'] . '</strong></p>';

echo '<h2>Table Creation Results</h2>';
echo '<table>';
echo '<tr><th>Table</th><th>Result</th></tr>';
echo '<tr><td>Services</td><td>' . (is_array($result['services']) ? implode(', ', $result['services']) : 'Created') . '</td></tr>';
echo '<tr><td>Appointments</td><td>' . (is_array($result['appointments']) ? implode(', ', $result['appointments']) : 'Created') . '</td></tr>';
echo '<tr><td>Time Slots</td><td>' . (is_array($result['slots']) ? implode(', ', $result['slots']) : 'Created') . '</td></tr>';
echo '<tr><td>Blocked Dates</td><td>' . (is_array($result['blocked_dates']) ? implode(', ', $result['blocked_dates']) : 'Created') . '</td></tr>';
echo '</table>';

echo '<a href="db-check.php" class="button">View Table Details</a>';
echo '<a href="admin.php?page=smart-schedular-services" class="button" style="margin-left: 10px;">Go to Services</a>';

echo '</body></html>';
?> 