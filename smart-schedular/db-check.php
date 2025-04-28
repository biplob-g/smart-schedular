<?php
/**
 * Database Check Script
 * 
 * This script checks if the required database tables exist for the Smart Schedular plugin.
 */

// Load WordPress environment
require_once '../../../../wp-load.php';

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied');
}

global $wpdb;

// Tables to check
$tables = array(
    $wpdb->prefix . 'smart_schedular_services',
    $wpdb->prefix . 'smart_schedular_appointments',
    $wpdb->prefix . 'smart_schedular_time_slots',
    $wpdb->prefix . 'smart_schedular_blocked_dates'
);

echo '<h1>Smart Schedular Database Check</h1>';

// Check tables
foreach ($tables as $table) {
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    
    echo "<h2>Table: $table</h2>";
    
    if ($table_exists) {
        echo '<p style="color:green">✓ Table exists</p>';
        
        // Show table structure
        $table_structure = $wpdb->get_results("DESCRIBE $table");
        
        if ($table_structure) {
            echo '<h3>Table Structure:</h3>';
            echo '<table border="1" cellpadding="5" cellspacing="0">';
            echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
            
            foreach ($table_structure as $column) {
                echo '<tr>';
                echo '<td>' . esc_html($column->Field) . '</td>';
                echo '<td>' . esc_html($column->Type) . '</td>';
                echo '<td>' . esc_html($column->Null) . '</td>';
                echo '<td>' . esc_html($column->Key) . '</td>';
                echo '<td>' . (isset($column->Default) ? esc_html($column->Default) : 'NULL') . '</td>';
                echo '<td>' . esc_html($column->Extra) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            
            // Show count of rows
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            echo "<p>Rows in table: <strong>$count</strong></p>";
            
            // If it's the services table, show some data
            if ($table === $wpdb->prefix . 'smart_schedular_services' && $count > 0) {
                $services = $wpdb->get_results("SELECT * FROM $table LIMIT 5");
                
                echo '<h3>Sample Services:</h3>';
                echo '<table border="1" cellpadding="5" cellspacing="0">';
                echo '<tr><th>ID</th><th>Name</th><th>Duration</th><th>Color</th><th>Available Days</th></tr>';
                
                foreach ($services as $service) {
                    echo '<tr>';
                    echo '<td>' . esc_html($service->id) . '</td>';
                    echo '<td>' . esc_html($service->name) . '</td>';
                    echo '<td>' . esc_html($service->duration) . '</td>';
                    echo '<td>' . esc_html($service->color) . '</td>';
                    echo '<td>' . esc_html($service->available_days) . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            }
        } else {
            echo '<p style="color:red">Error retrieving table structure</p>';
        }
    } else {
        echo '<p style="color:red">✗ Table does not exist!</p>';
    }
    
    echo '<hr>';
}

// Add button to recreate tables
echo '<h2>Table Management</h2>';
echo '<p>Choose one of the following options to fix your database tables:</p>';

echo '<div style="display: flex; gap: 20px; margin-bottom: 30px;">';

// Option 1: Use plugin's built-in activation method
echo '<form method="post" style="margin-right: 20px;">';
echo '<input type="hidden" name="recreate_tables" value="1">';
echo '<button type="submit" style="padding: 10px 15px; background-color: #ffb900; color: #000; border: none; cursor: pointer; font-weight: bold;">Recreate Tables (Standard Method)</button>';
echo '<p style="font-size: 12px; color: #666; max-width: 250px;">Uses the plugin\'s built-in activator class. May not fix all issues.</p>';
echo '</form>';

// Option 2: Direct table creation method (recommended)
echo '<div>';
echo '<a href="activate-tables.php" style="display: inline-block; padding: 10px 15px; background-color: #d63638; color: white; text-decoration: none; font-weight: bold;">Force Recreate Tables (Recommended)</a>';
echo '<p style="font-size: 12px; color: #666; max-width: 250px;">Directly recreates all tables from scratch. Recommended for fixing database issues.</p>';
echo '</div>';

echo '</div>';

// Handle recreate tables request
if (isset($_POST['recreate_tables'])) {
    require_once 'includes/class-smart-schedular-activator.php';
    Smart_Schedular_Activator::activate();
    
    echo '<p style="color:green; font-weight: bold;">Tables have been recreated. Refresh the page to see the changes.</p>';
    echo '<p><a href="' . $_SERVER['PHP_SELF'] . '">Refresh Page</a></p>';
}
?> 