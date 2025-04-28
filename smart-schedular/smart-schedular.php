<?php
/**
 * Plugin Name: Smart Schedular
 * Plugin URI: https://example.com/smart-schedular
 * Description: A modern appointment booking system inspired by Calendly with Google integration
 * Version: 1.0.0
 * Author: Biplob Ghatak
 * Author URI: https://ghatakbits.in
 * Text Domain: smart-schedular
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('SMART_SCHEDULAR_VERSION', '1.0.0');
define('SMART_SCHEDULAR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SMART_SCHEDULAR_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_smart_schedular() {
    require_once SMART_SCHEDULAR_PLUGIN_DIR . 'includes/class-smart-schedular-activator.php';
    Smart_Schedular_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_smart_schedular() {
    require_once SMART_SCHEDULAR_PLUGIN_DIR . 'includes/class-smart-schedular-deactivator.php';
    Smart_Schedular_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_smart_schedular');
register_deactivation_hook(__FILE__, 'deactivate_smart_schedular');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-smart-schedular.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-smart-schedular-service.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-smart-schedular-appointment.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-smart-schedular-notifications.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-smart-schedular-timezone.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-smart-schedular-ajax.php';

/**
 * Begins execution of the plugin.
 */
function run_smart_schedular() {
    $plugin = new Smart_Schedular();
    $plugin->run();
    
    // Initialize AJAX handler
    new Smart_Schedular_Ajax();
}
run_smart_schedular();

// Manually update the database schema (used for development only - will be removed in production)
function smart_schedular_update_db_schema() {
    // Only run this on admin or AJAX requests to avoid performance impact on frontend
    if (!is_admin() && !wp_doing_ajax()) {
        return;
    }
    
    require_once plugin_dir_path(__FILE__) . 'includes/class-smart-schedular-activator.php';
    
    // Try to update tables with error handling
    try {
        Smart_Schedular_Activator::maybe_update_tables();
    } catch (Exception $e) {
        error_log('Smart Schedular DB Update Error: ' . $e->getMessage());
    }
}
// Use a higher priority to ensure plugin is fully loaded
add_action('init', 'smart_schedular_update_db_schema', 20); 