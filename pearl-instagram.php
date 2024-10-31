<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.pearlthemes.com
 * @since             1.0.0
 * @package           Pearl_Instagram
 *
 * @wordpress-plugin
 * Plugin Name:       Instagram Widget by PearlThemes
 * Plugin URI:        https://profiles.wordpress.org/pearlthemes
 * Description:       A light weight plugin to display recent Instagram photos with awesome customizability options.
 * Version:           1.1.0
 * Author:            PearlThemes
 * Author URI:        https://www.pearlthemes.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pearl-instagram
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pearl-instagram-activator.php
 */
function activate_pearl_instagram() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pearl-instagram-activator.php';
	Pearl_Instagram_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pearl-instagram-deactivator.php
 */
function deactivate_pearl_instagram() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pearl-instagram-deactivator.php';
	Pearl_Instagram_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_pearl_instagram' );
register_deactivation_hook( __FILE__, 'deactivate_pearl_instagram' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-pearl-instagram.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_pearl_instagram() {

	$plugin = new Pearl_Instagram();
	$plugin->run();

}
run_pearl_instagram();
