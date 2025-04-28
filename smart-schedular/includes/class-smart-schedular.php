<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    Smart_Schedular
 * @subpackage Smart_Schedular/includes
 */
class Smart_Schedular {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Smart_Schedular_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('SMART_SCHEDULAR_VERSION')) {
            $this->version = SMART_SCHEDULAR_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'smart-schedular';

        // Constants are defined here instead of in run() to ensure they're available during initialization
        if (!defined('SMART_SCHEDULAR_PLUGIN_DIR')) {
            define('SMART_SCHEDULAR_PLUGIN_DIR', plugin_dir_path(dirname(__FILE__)));
        }
        
        if (!defined('SMART_SCHEDULAR_PLUGIN_URL')) {
            define('SMART_SCHEDULAR_PLUGIN_URL', plugin_dir_url(dirname(__FILE__)));
        }

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Smart_Schedular_Loader. Orchestrates the hooks of the plugin.
     * - Smart_Schedular_Admin. Defines all hooks for the admin area.
     * - Smart_Schedular_Public. Defines all hooks for the public side of the site.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once SMART_SCHEDULAR_PLUGIN_DIR . 'includes/class-smart-schedular-loader.php';
        
        /**
         * The class responsible for handling email notifications.
         */
        require_once SMART_SCHEDULAR_PLUGIN_DIR . 'includes/class-smart-schedular-emails.php';
        
        /**
         * The class responsible for Google Calendar and Meet integration.
         */
        require_once SMART_SCHEDULAR_PLUGIN_DIR . 'includes/class-smart-schedular-google-api.php';
        
        /**
         * The class responsible for all booking functionality.
         */
        require_once SMART_SCHEDULAR_PLUGIN_DIR . 'includes/class-smart-schedular-booking.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once SMART_SCHEDULAR_PLUGIN_DIR . 'admin/class-smart-schedular-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once SMART_SCHEDULAR_PLUGIN_DIR . 'frontend/class-smart-schedular-public.php';

        $this->loader = new Smart_Schedular_Loader();
        
        // Check for database updates
        $this->check_for_updates();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Smart_Schedular_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Add menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // Register settings
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        
        // Ajax handlers for admin
        $this->loader->add_action('wp_ajax_smart_schedular_approve_appointment', $plugin_admin, 'approve_appointment');
        $this->loader->add_action('wp_ajax_smart_schedular_decline_appointment', $plugin_admin, 'decline_appointment');
        $this->loader->add_action('wp_ajax_smart_schedular_save_service', $plugin_admin, 'save_service');
        $this->loader->add_action('wp_ajax_smart_schedular_save_time_slots', $plugin_admin, 'save_time_slots');
        $this->loader->add_action('wp_ajax_smart_schedular_block_date', $plugin_admin, 'block_date');
        $this->loader->add_action('wp_ajax_smart_schedular_unblock_date', $plugin_admin, 'unblock_date');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Smart_Schedular_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Register shortcode
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        
        // Register AJAX actions
        $this->loader->add_action('init', $plugin_public, 'register_ajax_actions');
        
        // Ajax handlers for public
        $this->loader->add_action('wp_ajax_nopriv_smart_schedular_get_available_dates', $plugin_public, 'get_available_dates');
        $this->loader->add_action('wp_ajax_smart_schedular_get_available_dates', $plugin_public, 'get_available_dates');
        
        $this->loader->add_action('wp_ajax_nopriv_smart_schedular_get_available_times', $plugin_public, 'get_available_times');
        $this->loader->add_action('wp_ajax_smart_schedular_get_available_times', $plugin_public, 'get_available_times');
        
        $this->loader->add_action('wp_ajax_nopriv_smart_schedular_book_appointment', $plugin_public, 'book_appointment');
        $this->loader->add_action('wp_ajax_smart_schedular_book_appointment', $plugin_public, 'book_appointment');
    }

    /**
     * Define internationalization functionality for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        // Check if the i18n class file exists
        $i18n_file = SMART_SCHEDULAR_PLUGIN_DIR . 'includes/class-smart-schedular-i18n.php';
        
        if (file_exists($i18n_file)) {
            require_once $i18n_file;
            
            $plugin_i18n = new Smart_Schedular_i18n();
            $plugin_i18n->set_domain($this->get_plugin_name());
            
            $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
        }
        // If the file doesn't exist, internationalization is not implemented yet
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        // Dependencies and hooks are already loaded in constructor
        
        // Run the loader to execute all registered hooks
        $this->loader->run();
        
        // Initialize the public AJAX handlers directly
        $plugin_public = new Smart_Schedular_Public($this->get_plugin_name(), $this->get_version());
        $plugin_public->register_ajax_actions();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Smart_Schedular_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Check for database schema updates.
     *
     * @since    1.0.0
     */
    private function check_for_updates() {
        $current_version = get_option('smart_schedular_db_version', '1.0.0');
        
        if (version_compare($current_version, '1.0.1', '<')) {
            global $wpdb;
            
            // Add timezone column if it doesn't exist
            $table_name = $wpdb->prefix . 'smart_schedular_services';
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'timezone'");
            
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN timezone VARCHAR(10) DEFAULT 'UTC' AFTER color");
            }
            
            update_option('smart_schedular_db_version', '1.0.1');
        }
    }
} 