<?php declare( strict_types=1 );

/**
 * @covers WP_REST_Abilities_Run_Controller
 * @group abilities-api
 * @group rest-api
 */
class WPRESTAbilitiesRunControllerTest extends WP_UnitTestCase {

	/**
	 * REST Server instance.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Test user ID with permissions.
	 *
	 * @var int
	 */
	protected static $user_id;

	/**
	 * Test user ID without permissions.
	 *
	 * @var int
	 */
	protected static $no_permission_user_id;

	/**
	 * Set up before class.
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		self::$user_id = self::factory()->user->create(
			array(
				'role' => 'editor',
			)
		);

		self::$no_permission_user_id = self::factory()->user->create(
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
		$this->server = $wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );

		do_action( 'abilities_api_init' );

		$this->register_test_abilities();

		// Set default user for tests
		wp_set_current_user( self::$user_id );
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down(): void {
		foreach ( wp_get_abilities() as $ability ) {
			if ( str_starts_with( $ability->get_name(), 'test/' ) ) {
				wp_unregister_ability( $ability->get_name() );
			}
		}

		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tear_down();
	}

	/**
	 * Register test abilities for testing.
	 */
	private function register_test_abilities(): void {
		// Tool ability (POST only)
		wp_register_ability(
			'test/calculator',
			array(
				'label'               => 'Calculator',
				'description'         => 'Performs calculations',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'a' => array(
							'type'        => 'number',
							'description' => 'First number',
						),
						'b' => array(
							'type'        => 'number',
							'description' => 'Second number',
						),
					),
					'required'             => array( 'a', 'b' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type' => 'number',
				),
				'execute_callback'    => function ( array $input ) {
					return $input['a'] + $input['b'];
				},
				'permission_callback' => function ( array $input ) {
					return current_user_can( 'edit_posts' );
				},
				'meta'                => array(
					'type' => 'tool',
				),
			)
		);

		// Resource ability (GET only)
		wp_register_ability(
			'test/user-info',
			array(
				'label'               => 'User Info',
				'description'         => 'Gets user information',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'user_id' => array(
							'type'    => 'integer',
							'default' => 0,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'    => array( 'type' => 'integer' ),
						'login' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => function ( array $input ) {
					$user_id = $input['user_id'] ?? get_current_user_id();
					$user    = get_user_by( 'id', $user_id );
					if ( ! $user ) {
						return new WP_Error( 'user_not_found', 'User not found' );
					}
					return array(
						'id'    => $user->ID,
						'login' => $user->user_login,
					);
				},
				'permission_callback' => function ( array $input ) {
					return is_user_logged_in();
				},
				'meta'                => array(
					'type' => 'resource',
				),
			)
		);

		// Ability with contextual permissions
		wp_register_ability(
			'test/restricted',
			array(
				'label'               => 'Restricted Action',
				'description'         => 'Requires specific input for permission',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'secret' => array( 'type' => 'string' ),
						'data'   => array( 'type' => 'string' ),
					),
					'required'   => array( 'secret', 'data' ),
				),
				'output_schema'       => array(
					'type' => 'string',
				),
				'execute_callback'    => function ( array $input ) {
					return 'Success: ' . $input['data'];
				},
				'permission_callback' => function ( array $input ) {
					// Only allow if secret matches
					return isset( $input['secret'] ) && 'valid_secret' === $input['secret'];
				},
				'meta'                => array(
					'type' => 'tool',
				),
			)
		);

		// Ability that returns null
		wp_register_ability(
			'test/null-return',
			array(
				'label'               => 'Null Return',
				'description'         => 'Returns null',
				'execute_callback'    => function () {
					return null;
				},
				'permission_callback' => '__return_true',
				'meta'                => array(
					'type' => 'tool',
				),
			)
		);

		// Ability that returns WP_Error
		wp_register_ability(
			'test/error-return',
			array(
				'label'               => 'Error Return',
				'description'         => 'Returns error',
				'execute_callback'    => function () {
					return new WP_Error( 'test_error', 'This is a test error' );
				},
				'permission_callback' => '__return_true',
				'meta'                => array(
					'type' => 'tool',
				),
			)
		);

		// Ability with invalid output
		wp_register_ability(
			'test/invalid-output',
			array(
				'label'               => 'Invalid Output',
				'description'         => 'Returns invalid output',
				'output_schema'       => array(
					'type' => 'number',
				),
				'execute_callback'    => function () {
					return 'not a number'; // Invalid - schema expects number
				},
				'permission_callback' => '__return_true',
				'meta'                => array(
					'type' => 'tool',
				),
			)
		);

		// Resource ability for query params testing
		wp_register_ability(
			'test/query-params',
			array(
				'label'               => 'Query Params Test',
				'description'         => 'Tests query parameter handling',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'param1' => array( 'type' => 'string' ),
						'param2' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => function ( array $input ) {
					return $input;
				},
				'permission_callback' => '__return_true',
				'meta'                => array(
					'type' => 'resource',
				),
			)
		);
	}

	/**
	 * Test executing a tool ability with POST.
	 */
	public function test_execute_tool_ability_post(): void {
		$request = new WP_REST_Request( 'POST', '/wp/v2/abilities/test/calculator/run' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'a' => 5,
					'b' => 3,
				)
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 8, $response->get_data() );
	}

	/**
	 * Test executing a resource ability with GET.
	 */
	public function test_execute_resource_ability_get(): void {
		$request = new WP_REST_Request( 'GET', '/wp/v2/abilities/test/user-info/run' );
		$request->set_query_params(
			array(
				'user_id' => self::$user_id,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( self::$user_id, $data['id'] );
	}

	/**
	 * Test HTTP method validation for tool abilities.
	 */
	public function test_tool_ability_requires_post(): void {
		wp_register_ability(
			'test/open-tool',
			array(
				'label'               => 'Open Tool',
				'description'         => 'Tool with no permission requirements',
				'execute_callback'    => function () {
					return 'success';
				},
				'permission_callback' => '__return_true',
				'meta'                => array(
					'type' => 'tool',
				),
			)
		);

		$request  = new WP_REST_Request( 'GET', '/wp/v2/abilities/test/open-tool/run' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 405, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'rest_invalid_method', $data['code'] );
		$this->assertStringContainsString( 'Tool abilities require POST', $data['message'] );
	}

	/**
	 * Test HTTP method validation for resource abilities.
	 */
	public function test_resource_ability_requires_get(): void {
		// Try POST on a resource ability (should fail)
		$request = new WP_REST_Request( 'POST', '/wp/v2/abilities/test/user-info/run' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'user_id' => 1 ) ) );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 405, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'rest_invalid_method', $data['code'] );
		$this->assertStringContainsString( 'Resource abilities require GET', $data['message'] );
	}


	/**
	 * Test output validation against schema.
	 * Note: When output validation fails in WP_Ability::execute(), it returns null,
	 * which causes the REST controller to return 'rest_ability_execution_failed'.
	 *
	 * @expectedIncorrectUsage WP_Ability::validate_output
	 */
	public function test_output_validation(): void {
		$request = new WP_REST_Request( 'POST', '/wp/v2/abilities/test/invalid-output/run' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array() ) );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 500, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( 'rest_ability_execution_failed', $data['code'] );
		$this->assertStringContainsString( 'Ability execution failed', $data['message'] );
	}

	/**
	 * Test permission check for execution.
	 */
	public function test_execution_permission_denied(): void {
		wp_set_current_user( self::$no_permission_user_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/abilities/test/calculator/run' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'a' => 5,
					'b' => 3,
				)
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'rest_cannot_execute', $data['code'] );
	}

	/**
	 * Test contextual permission check.
	 */
	public function test_contextual_permission_check(): void {
		$request = new WP_REST_Request( 'POST', '/wp/v2/abilities/test/restricted/run' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'secret' => 'wrong_secret',
					'data'   => 'test data',
				)
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 403, $response->get_status() );

		$request->set_body(
			wp_json_encode(
				array(
					'secret' => 'valid_secret',
					'data'   => 'test data',
				)
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Success: test data', $response->get_data() );
	}

	/**
	 * Test handling of null return from ability.
	 */
	public function test_null_return_handling(): void {
		$request = new WP_REST_Request( 'POST', '/wp/v2/abilities/test/null-return/run' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array() ) );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 500, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'rest_ability_execution_failed', $data['code'] );
		$this->assertStringContainsString( 'Ability execution failed', $data['message'] );
	}

	/**
	 * Test handling of WP_Error return from ability.
	 */
	public function test_wp_error_return_handling(): void {
		$request = new WP_REST_Request( 'POST', '/wp/v2/abilities/test/error-return/run' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array() ) );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 500, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'rest_ability_execution_failed', $data['code'] );
		$this->assertEquals( 'This is a test error', $data['message'] );
	}

	/**
	 * Test non-existent ability returns 404.
	 *
	 * @expectedIncorrectUsage WP_Abilities_Registry::get_registered
	 */
	public function test_execute_non_existent_ability(): void {
		$request = new WP_REST_Request( 'POST', '/wp/v2/abilities/non/existent/run' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array() ) );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'rest_ability_not_found', $data['code'] );
	}

	/**
	 * Test schema retrieval for run endpoint.
	 */
	public function test_run_endpoint_schema(): void {
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/abilities/test/calculator/run' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'schema', $data );
		$schema = $data['schema'];

		$this->assertEquals( 'ability-execution', $schema['title'] );
		$this->assertEquals( 'object', $schema['type'] );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'result', $schema['properties'] );
	}

}
