<?php declare( strict_types=1 );

/**
 * Tests for the REST controller for the Abilities categories endpoint.
 *
 * @covers WP_REST_Abilities_Categories_Controller
 * @group abilities-api
 * @group rest-api
 */
class Tests_REST_API_WpRestAbilityCategoriesController extends WP_UnitTestCase {

	/**
	 * REST Server instance.
	 *
	 * @var \WP_REST_Server
	 */
	protected $server;

	/**
	 * Test admin user ID.
	 *
	 * @var int
	 */
	protected static $admin_user_id;

	/**
	 * Test subscriber user ID.
	 *
	 * @var int
	 */
	protected static $subscriber_user_id;

	/**
	 * Set up before class.
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		self::$admin_user_id = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);

		self::$subscriber_user_id = self::factory()->user->create(
			array(
				'role' => 'subscriber',
			)
		);
	}

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );

		add_action(
			'abilities_api_categories_init',
			array( $this, 'register_test_categories' )
		);
		do_action( 'abilities_api_categories_init' );

		wp_set_current_user( self::$admin_user_id );
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down(): void {
		foreach ( array( 'data-retrieval', 'data-modification', 'communication', 'test-category-1', 'test-category-2', 'test-category-3' ) as $slug ) {
			if ( ! WP_Abilities_Category_Registry::get_instance()->is_registered( $slug ) ) {
				continue;
			}

			wp_unregister_ability_category( $slug );
		}

		for ( $i = 4; $i <= 60; $i++ ) {
			$slug = "test-category-{$i}";
			if ( WP_Abilities_Category_Registry::get_instance()->is_registered( $slug ) ) {
				wp_unregister_ability_category( $slug );
			}
		}

		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tear_down();
	}

	/**
	 * Register test categories for testing.
	 */
	public function register_test_categories(): void {
		wp_register_ability_category(
			'data-retrieval',
			array(
				'label'       => 'Data Retrieval',
				'description' => 'Abilities that retrieve and return data from the WordPress site.',
			)
		);

		wp_register_ability_category(
			'data-modification',
			array(
				'label'       => 'Data Modification',
				'description' => 'Abilities that modify data on the WordPress site.',
			)
		);

		wp_register_ability_category(
			'communication',
			array(
				'label'       => 'Communication',
				'description' => 'Abilities that send messages or notifications.',
				'meta'        => array(
					'priority' => 'high',
				),
			)
		);

		// Register multiple categories for pagination testing
		for ( $i = 1; $i <= 60; $i++ ) {
			wp_register_ability_category(
				"test-category-{$i}",
				array(
					'label'       => "Test Category {$i}",
					'description' => "Test category number {$i}",
				)
			);
		}
	}

	/**
	 * Test listing all categories.
	 */
	public function test_get_items(): void {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertNotEmpty( $data );

		$this->assertCount( 50, $data, 'First page should return exactly 50 items (default per_page)' );

		$category_slugs = wp_list_pluck( $data, 'slug' );
		$this->assertContains( 'data-retrieval', $category_slugs );
		$this->assertContains( 'data-modification', $category_slugs );
		$this->assertContains( 'communication', $category_slugs );
	}

	/**
	 * Test getting a specific category.
	 */
	public function test_get_item(): void {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories/data-retrieval' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'data-retrieval', $data['slug'] );
		$this->assertEquals( 'Data Retrieval', $data['label'] );
		$this->assertEquals( 'Abilities that retrieve and return data from the WordPress site.', $data['description'] );
		$this->assertArrayHasKey( 'meta', $data );
	}

	/**
	 * Test getting a category with meta.
	 */
	public function test_get_item_with_meta(): void {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories/communication' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'communication', $data['slug'] );
		$this->assertArrayHasKey( 'meta', $data );
		$this->assertIsArray( $data['meta'] );
		$this->assertEquals( 'high', $data['meta']['priority'] );
	}

	/**
	 * Test getting a non-existent category returns 404.
	 *
	 * @expectedIncorrectUsage WP_Abilities_Category_Registry::get_registered
	 */
	public function test_get_item_not_found(): void {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories/non-existent' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'rest_category_not_found', $data['code'] );
	}

	/**
	 * Test permission check for listing categories requires admin.
	 */
	public function test_get_items_permission_denied_for_non_admin(): void {
		// Test with subscriber user
		wp_set_current_user( self::$subscriber_user_id );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test permission check for listing categories requires authentication.
	 */
	public function test_get_items_permission_denied_for_unauthenticated(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test permission check for single category.
	 */
	public function test_get_item_permission_denied(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories/data-retrieval' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test pagination headers.
	 */
	public function test_pagination_headers(): void {
		$request = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories' );
		$request->set_param( 'per_page', 10 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'X-WP-Total', $headers );
		$this->assertArrayHasKey( 'X-WP-TotalPages', $headers );

		$total_categories = count( wp_get_ability_categories() );
		$this->assertEquals( $total_categories, (int) $headers['X-WP-Total'] );
		$this->assertEquals( ceil( $total_categories / 10 ), (int) $headers['X-WP-TotalPages'] );
	}

	/**
	 * Test HEAD method returns empty body with proper headers.
	 */
	public function test_head_request(): void {
		$request  = new WP_REST_Request( 'HEAD', '/wp/v2/abilities/categories' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertEmpty( $data );

		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'X-WP-Total', $headers );
		$this->assertArrayHasKey( 'X-WP-TotalPages', $headers );
	}

	/**
	 * Test pagination links.
	 */
	public function test_pagination_links(): void {
		$request = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories' );
		$request->set_param( 'per_page', 10 );
		$request->set_param( 'page', 1 );
		$response = $this->server->dispatch( $request );

		$headers     = $response->get_headers();
		$link_header = $headers['Link'] ?? '';

		$this->assertStringContainsString( 'rel="next"', $link_header );
		$this->assertStringNotContainsString( 'rel="prev"', $link_header );

		$request->set_param( 'page', 3 );
		$response = $this->server->dispatch( $request );

		$headers     = $response->get_headers();
		$link_header = $headers['Link'] ?? '';

		$this->assertStringContainsString( 'rel="next"', $link_header );
		$this->assertStringContainsString( 'rel="prev"', $link_header );

		$total_categories = count( wp_get_ability_categories() );
		$last_page        = ceil( $total_categories / 10 );
		$request->set_param( 'page', $last_page );
		$response = $this->server->dispatch( $request );

		$headers     = $response->get_headers();
		$link_header = $headers['Link'] ?? '';

		$this->assertStringNotContainsString( 'rel="next"', $link_header );
		$this->assertStringContainsString( 'rel="prev"', $link_header );
	}

	/**
	 * Test collection parameters.
	 */
	public function test_collection_params(): void {
		$request = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories' );
		$request->set_param( 'per_page', 5 );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertCount( 5, $data );
		$request->set_param( 'page', 2 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertCount( 5, $data );

		$page1_request = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories' );
		$page1_request->set_param( 'per_page', 5 );
		$page1_request->set_param( 'page', 1 );
		$page1_response = $this->server->dispatch( $page1_request );
		$page1_slugs    = wp_list_pluck( $page1_response->get_data(), 'slug' );
		$page2_slugs    = wp_list_pluck( $data, 'slug' );

		$this->assertNotEquals( $page1_slugs, $page2_slugs );
	}

	/**
	 * Test response links for individual categories.
	 */
	public function test_category_response_links(): void {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories/data-retrieval' );
		$response = $this->server->dispatch( $request );

		$links = $response->get_links();
		$this->assertArrayHasKey( 'self', $links );
		$this->assertArrayHasKey( 'collection', $links );
		$this->assertArrayHasKey( 'abilities', $links );

		$self_link = $links['self'][0]['href'];
		$this->assertStringContainsString( '/wp/v2/abilities/categories/data-retrieval', $self_link );

		$collection_link = $links['collection'][0]['href'];
		$this->assertStringContainsString( '/wp/v2/abilities/categories', $collection_link );

		$abilities_link = $links['abilities'][0]['href'];
		$this->assertStringContainsString( '/wp/v2/abilities?category=data-retrieval', $abilities_link );
	}

	/**
	 * Test context parameter.
	 */
	public function test_context_parameter(): void {
		$request = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories/data-retrieval' );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'description', $data );

		$request->set_param( 'context', 'embed' );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'slug', $data );
		$this->assertArrayHasKey( 'label', $data );
	}

	/**
	 * Test schema retrieval.
	 */
	public function test_get_schema(): void {
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/abilities/categories' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'schema', $data );
		$schema = $data['schema'];

		$this->assertEquals( 'ability-category', $schema['title'] );
		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'properties', $schema );

		$properties = $schema['properties'];

		$this->assertCount( 4, $properties, 'Schema should have exactly 4 properties.' );

		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'label', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'meta', $properties );

		$slug_property = $properties['slug'];
		$this->assertEquals( 'string', $slug_property['type'] );
		$this->assertTrue( $slug_property['readonly'] );

		$this->assertArrayHasKey( 'required', $schema );
		$this->assertContains( 'slug', $schema['required'] );
		$this->assertContains( 'label', $schema['required'] );
		$this->assertContains( 'description', $schema['required'] );
		$this->assertContains( 'meta', $schema['required'] );
	}

	/**
	 * Test category slug with valid format.
	 */
	public function test_category_slug_with_valid_format(): void {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories/data-retrieval' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Data provider for invalid category slugs.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function invalid_category_slugs_provider(): array {
		return array(
			'Uppercase'         => array( 'Data-Retrieval' ),
			'@ symbol'          => array( 'data@retrieval' ),
			'space'             => array( 'data retrieval' ),
			'dot'               => array( 'data.retrieval' ),
			'underscore'        => array( 'data_retrieval' ),
			'URL encoded space' => array( 'data%20retrieval' ),
		);
	}

	/**
	 * Test category slugs with invalid format.
	 *
	 * @dataProvider invalid_category_slugs_provider
	 * @param string $slug Invalid category slug to test.
	 */
	public function test_category_slug_with_invalid_format( string $slug ): void {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories/' . $slug );
		$response = $this->server->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 400, 404 ) );
	}

	/**
	 * Test category slug with forward slash (matched by abilities route).
	 *
	 * @expectedIncorrectUsage WP_Abilities_Registry::get_registered
	 */
	public function test_category_slug_with_forward_slash(): void {
		// Forward slashes cause the URL to be matched by the abilities route instead
		$request  = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories/data/retrieval' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Data provider for invalid pagination parameters.
	 *
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public function invalid_pagination_params_provider(): array {
		return array(
			'Zero page'            => array( array( 'page' => 0 ) ),
			'Negative page'        => array( array( 'page' => -1 ) ),
			'Non-numeric page'     => array( array( 'page' => 'abc' ) ),
			'Zero per page'        => array( array( 'per_page' => 0 ) ),
			'Negative per page'    => array( array( 'per_page' => -10 ) ),
			'Exceeds maximum'      => array( array( 'per_page' => 1000 ) ),
			'Non-numeric per page' => array( array( 'per_page' => 'all' ) ),
		);
	}

	/**
	 * Test pagination parameters with invalid values.
	 *
	 * @dataProvider invalid_pagination_params_provider
	 * @param array<string, mixed> $params Invalid pagination parameters.
	 */
	public function test_invalid_pagination_parameters( array $params ): void {
		$request = new WP_REST_Request( 'GET', '/wp/v2/abilities/categories' );
		$request->set_query_params( $params );

		$response = $this->server->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 200, 400 ) );

		if ( $response->get_status() !== 200 ) {
			return;
		}

		$data = $response->get_data();
		$this->assertIsArray( $data );
	}
}
