<?php
/**
 * Client script registration for the Abilities API.
 *
 * This file provides functions to register the Abilities API JavaScript client
 * for both plugin and Composer-based installations.
 *
 * @package WordPress
 * @subpackage Abilities_API
 * @since 0.1.0
 */

declare( strict_types = 1 );

/**
 * Registers the Abilities API JavaScript client.
 *
 * Auto-detects whether running as a plugin or Composer package and registers
 * the client script accordingly.
 *
 * @since 0.1.0
 *
 * @return bool True if the script was registered, false if the build doesn't exist.
 */
function wp_abilities_register_client_assets(): bool {
	if ( wp_script_is( 'wp-abilities', 'registered' ) ) {
		return true;
	}

	if ( defined( 'WP_ABILITIES_API_DIR' ) ) {
		// Running as a plugin
		$base_path = WP_ABILITIES_API_DIR;
		$base_url  = plugins_url( '', dirname( __DIR__, 2 ) . '/abilities-api.php' );
	} else {
		// Running as a Composer package
		$base_path = dirname( __DIR__, 2 );

		// For Composer, we need to determine the URL based on the installation location
		$plugin_dir = WP_PLUGIN_DIR;
		if ( strpos( $base_path, $plugin_dir ) === 0 ) {
			// Inside a plugin directory
			$relative_path = str_replace( $plugin_dir, '', $base_path );
			$base_url = plugins_url( $relative_path );
		} else {
			// Assume standard Composer vendor structure
			$base_url = plugins_url( 'vendor/wordpress/abilities-api', dirname( $base_path, 2 ) );
		}
	}

	$client_path = trailingslashit( $base_path ) . 'packages/client/build/';

	if ( ! file_exists( $client_path . 'index.js' ) ) {
		return false;
	}

	$asset = require_once $client_path . 'index.asset.php';

	$client_url = trailingslashit( $base_url ) . 'packages/client/build/index.js';

	wp_register_script(
		'wp-abilities',
		$client_url,
		$asset['dependencies'],
		$asset['version'],
		true
	);

	return true;
}
