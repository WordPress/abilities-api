<?php
/**
 * Bootstraps the Abilities API classes and global functions.
 *
 * Can also be used by libraries to include the Abilities API classes without loading the plugin itself.
 *
 * This file will not be needed when the Abilities API is merged in Core.
 *
 * @package abilities-api
 * @since 0.1.0
 */

declare( strict_types = 1 );

/**
 * Plugin version.
 *
 * Useful to confirm you're using the latest version of the Abilities API.
 */
if ( ! defined( 'WP_ABILITIES_API_VERSION' ) ) {
	define( 'WP_ABILITIES_API_VERSION', '0.1.0' );
}

// Load core classes if they are not already defined.
if ( ! class_exists( 'WP_Ability' ) ) {
	require_once __DIR__ . '/abilities-api/class-wp-ability.php';
}

if ( ! class_exists( 'WP_Abilities_Registry' ) ) {
	require_once __DIR__ . '/abilities-api/class-wp-abilities-registry.php';
}

// Ensure procedural functions are available.
if ( ! function_exists( 'wp_register_ability' ) ) {
	require_once __DIR__ . '/abilities-api.php';
}

// Load REST API init class for plugin bootstrap.
if ( ! class_exists( 'WP_REST_Abilities_Init' ) ) {
	require_once __DIR__ . '/rest-api/class-wp-rest-abilities-init.php';
}
