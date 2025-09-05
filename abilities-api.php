<?php
/**
 * Abilities API
 *
 * @package     abilities-api
 * @author      WordPress.org Contributors
 * @copyright   2025 Plugin Contributors
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Abilities API
 * Plugin URI:        https://github.com/WordPress/abilities-api
 * Description:       Provides a framework for registering and executing AI abilities in WordPress.
 * Requires at least: 6.8
 * Version:           0.2.0
 * Requires PHP:      7.2
 * Author:            WordPress.org Contributors
 * Author URI:        https://github.com/WordPress/abilities-api/graphs/contributors
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       abilities-api
 */

/**
 * Shortcut constant to the path of this file.
 */
define( 'WP_ABILITIES_API_DIR', plugin_dir_path( __FILE__ ) );


require_once WP_ABILITIES_API_DIR . 'includes/bootstrap.php';

// Register the Abilities API client script
add_action( 'init', 'wp_abilities_register_client_assets' );

// Auto-enqueue on admin pages for development and testing
add_action( 'admin_enqueue_scripts', 'wp_abilities_admin_enqueue_scripts' );

/**
 * Auto-enqueue Abilities API client on admin pages.
 *
 * This is primarily for development and testing purposes.
 *
 * @since 0.1.0
 * @return void
 */
function wp_abilities_admin_enqueue_scripts() {
	if ( wp_script_is( 'wp-abilities', 'registered' ) ) {
		wp_enqueue_script( 'wp-abilities' );
	}
}
