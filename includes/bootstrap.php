<?php
/**
 * Bootstraps the Abilities API classes and global functions.
 *
 * This file is autoloaded by Composer when the package is installed via the
 * "files" autoload mechanism. It ensures the procedural functions defined in
 * `includes/abilities-api.php` are available without requiring namespaces.
 *
 * @package WordPress
 * @subpackage Abilities_API
 * @since 0.1.0
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	return; // Not in WordPress context
}

// Version of the plugin.
if ( ! defined( 'WP_ABILITIES_API_VERSION' ) ) {
	define( 'WP_ABILITIES_API_VERSION', '0.3.0' );
}

// Load core classes if they are not already defined (for non-Composer installs or direct includes).
if ( ! class_exists( 'WP_Ability' ) ) {
	require_once __DIR__ . '/abilities-api/class-wp-ability.php';
}
if ( ! class_exists( 'WP_Abilities_Registry' ) ) {
	require_once __DIR__ . '/abilities-api/class-wp-abilities-registry.php';
}
if ( ! class_exists( 'WP_Ability_Category' ) ) {
	require_once __DIR__ . '/abilities-api/class-wp-ability-category.php';
}
if ( ! class_exists( 'WP_Abilities_Category_Registry' ) ) {
	require_once __DIR__ . '/abilities-api/class-wp-abilities-category-registry.php';
}

// Ensure procedural functions are available, too.
if ( ! function_exists( 'wp_register_ability' ) ) {
	require_once __DIR__ . '/abilities-api.php';
}

// Load REST API init class for plugin bootstrap.
if ( ! class_exists( 'WP_REST_Abilities_Init' ) ) {
	require_once __DIR__ . '/rest-api/class-wp-rest-abilities-init.php';

	// Initialize REST API routes when WordPress is available.
	if ( function_exists( 'add_action' ) ) {
		add_action( 'rest_api_init', array( 'WP_REST_Abilities_Init', 'register_routes' ) );
	}
}

// Load assets init class for plugin bootstrap.
if ( ! class_exists( 'WP_Abilities_Assets_Init' ) ) {
	require_once __DIR__ . '/assets/class-wp-abilities-assets-init.php';

	// Initialize client assets when WordPress is available.
	if ( function_exists( 'add_action' ) ) {
		add_action( 'init', array( 'WP_Abilities_Assets_Init', 'register_assets' ) );
		add_action( 'admin_enqueue_scripts', array( 'WP_Abilities_Assets_Init', 'admin_enqueue_scripts' ) );
	}
}
