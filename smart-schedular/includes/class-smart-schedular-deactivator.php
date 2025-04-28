<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Smart_Schedular
 * @subpackage Smart_Schedular/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Smart_Schedular
 * @subpackage Smart_Schedular/includes
 * @author     Smart Schedular
 */
class Smart_Schedular_Deactivator {

    /**
     * Plugin deactivation tasks.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // For now, we don't do anything on deactivation to preserve data
        // If later we want to clean up, this is the place
    }
} 