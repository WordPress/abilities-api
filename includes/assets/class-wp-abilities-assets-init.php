<?php
/**
 * Assets initialization for Abilities API.
 *
 * @package WordPress
 * @subpackage Abilities_API
 * @since 0.1.0
 */

declare( strict_types = 1 );

/**
 * Handles initialization of Abilities API client assets.
 *
 * @since 0.1.0
 */
class WP_Abilities_Assets_Init {

	/**
	 * Registers the Abilities API JavaScript client.
	 *
	 * Auto-detects whether running as a plugin or Composer package and registers
	 * the client script accordingly.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function register_assets(): void {
		if ( wp_script_is( 'wp-abilities', 'registered' ) ) {
			return;
		}

		$base_path = '';
		$base_url  = '';

		if ( defined( 'WP_ABILITIES_API_DIR' ) ) {
			// Running as a plugin
			$base_path = wp_normalize_path( (string) WP_ABILITIES_API_DIR );
			$base_url  = plugins_url( '', dirname( __DIR__, 2 ) . '/abilities-api.php' );
		} else {
			// Running as a Composer package
			$base_path = dirname( __DIR__, 2 );

			$base_path  = wp_normalize_path( (string) $base_path );
			$plugin_dir = wp_normalize_path( (string) WP_PLUGIN_DIR );

			// For Composer, we need to determine the URL based on the installation location
			if ( 0 === strpos( $base_path, $plugin_dir ) ) {
				// Inside a plugin directory
				$relative_path = str_replace( $plugin_dir, '', $base_path );
				$base_url      = plugins_url( $relative_path );
			} else {
				// Assume standard Composer vendor structure
				$base_url = plugins_url( 'vendor/wordpress/abilities-api', dirname( $base_path, 2 ) );
			}
		}

		/** @var string $base_path PHPStan type assertion - base_path is always set above */
		$client_path = trailingslashit( $base_path ) . 'packages/client/build/';

		if ( ! file_exists( $client_path . 'index.js' ) ) {
			return;
		}

		// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- asset file path can be variable based on plugin or Composer install
		$asset = require_once $client_path . 'index.asset.php';

		$client_url = trailingslashit( $base_url ) . 'packages/client/build/index.js';

		wp_register_script(
			'wp-abilities',
			$client_url,
			$asset['dependencies'],
			$asset['version'],
			true
		);
	}

	/**
	 * Auto-enqueue Abilities API client on admin pages.
	 *
	 * This is primarily for development and testing purposes.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function admin_enqueue_scripts(): void {
		if ( ! wp_script_is( 'wp-abilities', 'registered' ) ) {
			return;
		}

		wp_enqueue_script( 'wp-abilities' );
	}
}
