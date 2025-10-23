<?php
/**
 * REST API initialization for Abilities API.
 *
 * @package WordPress
 * @subpackage Abilities_API
 * @since 0.1.0
 */

declare( strict_types = 1 );

/**
 * Handles initialization of Abilities REST API endpoints.
 *
 * @since 0.1.0
 */
class WP_REST_Abilities_Init {

	/**
	 * Registers the REST API routes for abilities.
	 *
	 * @since 0.1.0
	 */
	public static function register_routes(): void {
		require_once __DIR__ . '/endpoints/class-wp-rest-abilities-run-controller.php';
		require_once __DIR__ . '/endpoints/class-wp-rest-abilities-list-controller.php';
		require_once __DIR__ . '/endpoints/class-wp-rest-abilities-categories-controller.php';

		$categories_controller = new WP_REST_Abilities_Categories_Controller();
		$categories_controller->register_routes();

		$run_controller = new WP_REST_Abilities_Run_Controller();
		$run_controller->register_routes();

		$list_controller = new WP_REST_Abilities_List_Controller();
		$list_controller->register_routes();
	}
}
