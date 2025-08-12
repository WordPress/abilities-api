<?php declare( strict_types = 1 );

/**
 * REST API: WP_REST_Abilities_Init class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 0.1.0
 */

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
	public static function register_routes() {
		require_once __DIR__ . '/class-wp-rest-abilities-run-controller.php';
		require_once __DIR__ . '/class-wp-rest-abilities-list-controller.php';

		$run_controller = new WP_REST_Abilities_Run_Controller();
		$run_controller->register_routes();

		$list_controller = new WP_REST_Abilities_List_Controller();
		$list_controller->register_routes();
	}
}

add_action( 'rest_api_init', array( 'WP_REST_Abilities_Init', 'register_routes' ), 120 );
