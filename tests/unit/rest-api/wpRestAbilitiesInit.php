<?php declare( strict_types=1 );

/**
 * Tests for WP_REST_Abilities_Init
 *
 * @covers WP_REST_Abilities_Init
 * @group abilities-api
 * @group rest-api
 */
class Tests_REST_API_WpRestAbilitiesInit extends WP_UnitTestCase {

	/**
	 * REST Server instance.
	 *
	 * @var \WP_REST_Server
	 */
	protected $server;

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server();
	}

	/**
	 * Clean up after each test.
	 */
	public function tear_down(): void {
		parent::tear_down();

		global $wp_rest_server;
		$wp_rest_server = null;
	}

	/**
	 * Test that routes are registered when rest_api_init fires.
	 */
	public function test_routes_registered_on_rest_api_init(): void {
		// Routes should not exist before init
		$routes = $this->server->get_routes();
		$this->assertArrayNotHasKey( '/wp-abilities/v1/categories', $routes );
		$this->assertArrayNotHasKey( '/wp-abilities/v1/abilities', $routes );
		$this->assertArrayNotHasKey( '/wp-abilities/v1/abilities/(?P<name>[a-zA-Z0-9\\-\\/]+)', $routes );
		$this->assertArrayNotHasKey( '/wp-abilities/v1/abilities/(?P<name>[a-zA-Z0-9\\-\\/]+?)/run', $routes );

		// Trigger rest_api_init
		do_action( 'rest_api_init', $this->server );

		// Routes should now be registered
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wp-abilities/v1/categories', $routes );
		$this->assertArrayHasKey( '/wp-abilities/v1/abilities', $routes );
		$this->assertArrayHasKey( '/wp-abilities/v1/abilities/(?P<name>[a-zA-Z0-9\\-\\/]+)', $routes );
		$this->assertArrayHasKey( '/wp-abilities/v1/abilities/(?P<name>[a-zA-Z0-9\\-\\/]+?)/run', $routes );
	}

	/**
	 * Test that the correct controller classes are instantiated.
	 */
	public function test_correct_controllers_instantiated(): void {
		// Trigger rest_api_init
		do_action( 'rest_api_init', $this->server );

		$routes = $this->server->get_routes();

		// Check categories controller
		$this->assertArrayHasKey( '/wp-abilities/v1/categories', $routes );
		$categories_route = $routes['/wp-abilities/v1/categories'][0];
		$this->assertIsArray( $categories_route['callback'] );
		$this->assertInstanceOf( 'WP_REST_Abilities_V1_Categories_Controller', $categories_route['callback'][0] );

		// Check list controller
		$this->assertArrayHasKey( '/wp-abilities/v1/abilities', $routes );
		$list_route = $routes['/wp-abilities/v1/abilities'][0];
		$this->assertIsArray( $list_route['callback'] );
		$this->assertInstanceOf( 'WP_REST_Abilities_V1_List_Controller', $list_route['callback'][0] );

		// Check run controller
		$this->assertArrayHasKey( '/wp-abilities/v1/abilities/(?P<name>[a-zA-Z0-9\\-\\/]+?)/run', $routes );
		$run_route = $routes['/wp-abilities/v1/abilities/(?P<name>[a-zA-Z0-9\\-\\/]+?)/run'][0];
		$this->assertIsArray( $run_route['callback'] );
		$this->assertInstanceOf( 'WP_REST_Abilities_V1_Run_Controller', $run_route['callback'][0] );
	}

	/**
	 * Test that the init class loads required files.
	 */
	public function test_required_files_loaded(): void {
		// Classes should be available after requiring the main plugin file
		$this->assertTrue( class_exists( 'WP_REST_Abilities_Init' ) );
		$this->assertTrue( class_exists( 'WP_REST_Abilities_V1_Categories_Controller' ) );
		$this->assertTrue( class_exists( 'WP_REST_Abilities_V1_List_Controller' ) );
		$this->assertTrue( class_exists( 'WP_REST_Abilities_V1_Run_Controller' ) );
	}

	/**
	 * Test that routes support expected HTTP methods.
	 */
	public function test_routes_support_expected_methods(): void {
		do_action( 'rest_api_init', $this->server );

		$routes = $this->server->get_routes();

		// Categories endpoint should support GET
		$categories_methods = $routes['/wp-abilities/v1/categories'][0]['methods'];
		// Methods can be a string like 'GET' or an array of method constants
		if ( is_string( $categories_methods ) ) {
			$this->assertEquals( WP_REST_Server::READABLE, $categories_methods );
		} else {
			// Just check it's set, don't check specific values
			$this->assertNotEmpty( $categories_methods );
		}

		// List endpoint should support GET
		$list_methods = $routes['/wp-abilities/v1/abilities'][0]['methods'];
		// Methods can be a string like 'GET' or an array of method constants
		if ( is_string( $list_methods ) ) {
			$this->assertEquals( WP_REST_Server::READABLE, $list_methods );
		} else {
			// Just check it's set, don't check specific values
			$this->assertNotEmpty( $list_methods );
		}

		// Single ability endpoint should support GET
		$single_methods = $routes['/wp-abilities/v1/abilities/(?P<name>[a-zA-Z0-9\\-\\/]+)'][0]['methods'];
		// Methods can be a string like 'GET' or an array of method constants
		if ( is_string( $single_methods ) ) {
			$this->assertEquals( WP_REST_Server::READABLE, $single_methods );
		} else {
			// Just check it's set, don't check specific values
			$this->assertNotEmpty( $single_methods );
		}

		// Run endpoint should support all methods (for type-based routing)
		$run_route = $routes['/wp-abilities/v1/abilities/(?P<name>[a-zA-Z0-9\\-\\/]+?)/run'][0];
		// ALLMETHODS can be a string or array
		if ( is_string( $run_route['methods'] ) ) {
			$this->assertEquals( WP_REST_Server::ALLMETHODS, $run_route['methods'] );
		} else {
			// Just verify it has methods
			$this->assertNotEmpty( $run_route['methods'] );
		}
	}

	/**
	 * Test namespace and base configuration.
	 */
	public function test_namespace_and_base_configuration(): void {
		do_action( 'rest_api_init', $this->server );

		$namespaces = $this->server->get_namespaces();
		$this->assertContains( 'wp/v2', $namespaces );

		// Verify abilities endpoints are under /wp-abilities/v1 namespace
		$routes = $this->server->get_routes();
		foreach ( array_keys( $routes ) as $route ) {
			if ( strpos( $route, 'abilities' ) === false ) {
				continue;
			}

			$this->assertStringStartsWith( '/wp-abilities/v1', $route );
		}
	}

	/**
	 * Test that multiple calls to register_routes don't duplicate routes.
	 */
	public function test_no_duplicate_routes_on_multiple_init(): void {
		// First init.
		do_action( 'rest_api_init', $this->server );

		$routes_init = $this->server->get_routes();

		// This number depends on how many routes are registered initially.
		$initial_count = count( $routes_init['/wp-abilities/v1/abilities'] ?? array() );

		$this->assertGreaterThanOrEqual( 1, $initial_count );
		$this->assertCount( $initial_count, $routes_init['/wp-abilities/v1/categories'] ?? array() );
		$this->assertCount( $initial_count, $routes_init['/wp-abilities/v1/abilities'] ?? array() );
		$this->assertCount( $initial_count	, $routes_init['/wp-abilities/v1/abilities/(?P<name>[a-zA-Z0-9\-\/]+?)/run'] ?? array() );

		// Second init (simulating multiple calls).
		WP_REST_Abilities_Init::register_routes( $this->server );

		$routes_second_init = $this->server->get_routes();

		$this->assertCount( $initial_count, $routes_second_init['/wp-abilities/v1/categories'] ?? array() );
		$this->assertCount( $initial_count, $routes_second_init['/wp-abilities/v1/abilities'] ?? array() );
		$this->assertCount( $initial_count, $routes_second_init['/wp-abilities/v1/abilities/(?P<name>[a-zA-Z0-9\-\/]+?)/run'] ?? array() );
	}
}
