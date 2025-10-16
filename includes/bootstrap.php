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

// Ensure procedural functions are available.
// Classes are autoloaded via Composer's classmap.
if ( ! function_exists( 'wp_register_ability' ) ) {
	require_once __DIR__ . '/abilities-api.php';
}

// Initialize REST API routes when WordPress is available.
// Classes are autoloaded by Composer when the hooks fire.
if ( function_exists( 'add_action' ) ) {
	add_action( 'rest_api_init', array( 'WP_REST_Abilities_Init', 'register_routes' ) );
	add_action( 'init', array( 'WP_Abilities_Assets_Init', 'register_assets' ) );
	add_action( 'admin_enqueue_scripts', array( 'WP_Abilities_Assets_Init', 'admin_enqueue_scripts' ) );
}
