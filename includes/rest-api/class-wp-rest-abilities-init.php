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
	 *
	 * @param WP_REST_Server|null $rest_server Optional. The REST server to register routes with. Default null, which
	 *                                         will use the main REST server instance.
	 */
	public static function register_routes( $rest_server = null ): void {
		if ( ! $rest_server instanceof WP_REST_Server ) {
			$rest_server = rest_get_server();
		}

		$routes = $rest_server->get_routes();

		if ( ! isset( $routes['/wp-abilities/v1/categories'] ) ) {
			if ( ! class_exists( 'WP_REST_Abilities_V1_Categories_Controller' ) ) {
				require_once __DIR__ . '/endpoints/class-wp-rest-abilities-v1-categories-controller.php';
			}
			$categories_controller = new WP_REST_Abilities_V1_Categories_Controller();
			$categories_controller->register_routes();
		}

		if ( ! isset( $routes['/wp-abilities/v1/abilities/(?P<name>[a-zA-Z0-9\\-\\/]+?)/run'] ) ) {
			if ( ! class_exists( 'WP_REST_Abilities_V1_Run_Controller' ) ) {
				require_once __DIR__ . '/endpoints/class-wp-rest-abilities-v1-run-controller.php';
			}
			$run_controller = new WP_REST_Abilities_V1_Run_Controller();
			$run_controller->register_routes();
		}

		if ( isset( $routes['/wp-abilities/v1/abilities'] ) ) {
			return;
		}

		if ( ! class_exists( 'WP_REST_Abilities_V1_List_Controller' ) ) {
			require_once __DIR__ . '/endpoints/class-wp-rest-abilities-v1-list-controller.php';
		}
		$list_controller = new WP_REST_Abilities_V1_List_Controller();
		$list_controller->register_routes();
	}
}
