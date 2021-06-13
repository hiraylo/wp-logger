<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Plugin_Name
 *
 * @wordpress-plugin
 * Plugin Name:       WordPress Plugin Boilerplate
 * Plugin URI:        http://example.com/plugin-name-uri/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Your Name or Your Company
 * Author URI:        http://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       plugin-name
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PLUGIN_NAME_VERSION', '1.0.0' );

//register_activation_hook( __FILE__, 'activate_plugin_name' );
//register_deactivation_hook( __FILE__, 'deactivate_plugin_name' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-plugin-name.php';




/**
 * This function runs when WordPress completes its upgrade process
 * It iterates through each plugin updated to see if ours is included
 * @param $upgrader_object Array
 * @param $options Array
 */
function wp_upe_upgrade_completed( $upgrader_object, $options ) {
    // The path to our plugin's main file
    $our_plugin = plugin_basename( __FILE__ );
    // If an update has taken place and the updated type is plugins and the plugins element exists
    if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
        // Iterate through the plugins being updated and check if ours is there
        foreach( $options['plugins'] as $plugin ) {
            if( $plugin == $our_plugin ) {
                // Set a transient to record that our plugin has just been updated
                set_transient( 'wp_upe_updated', 1 );
            }
        }
    }
}
add_action( 'upgrader_process_complete', 'wp_upe_upgrade_completed', 10, 2 );

/**
 * Show a notice to anyone who has just updated this plugin
 * This notice shouldn't display to anyone who has just installed the plugin for the first time
 */
function wp_upe_display_update_notice() {
    // Check the transient to see if we've just updated the plugin
    if( get_transient( 'wp_upe_updated' ) ) {
        echo '<div class="notice notice-success">' . __( 'Thanks for updating', 'wp-upe' ) . '</div>';
        delete_transient( 'wp_upe_updated' );
    }
}
add_action( 'admin_notices', 'wp_upe_display_update_notice' );

/**
 * Show a notice to anyone who has just installed the plugin for the first time
 * This notice shouldn't display to anyone who has just updated this plugin
 */
function wp_upe_display_install_notice() {
    // Check the transient to see if we've just activated the plugin
    if( get_transient( 'wp_upe_activated' ) ) {
        echo '<div class="notice notice-success">' . __( 'Thanks for installing', 'wp-upe' ) . '</div>';
        // Delete the transient so we don't keep displaying the activation message
        delete_transient( 'wp_upe_activated' );
    }
}
add_action( 'admin_notices', 'wp_upe_display_install_notice' );

/**
 * Run this on activation
 * Set a transient so that we know we've just activated the plugin
 */
function wp_upe_activate() {
    set_transient( 'wp_upe_activated', 1 );
}
register_activation_hook( __FILE__, 'wp_upe_activate' );


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_plugin_name() {

    $plugin = new Plugin_Name();
    $plugin->run();

}
run_plugin_name();