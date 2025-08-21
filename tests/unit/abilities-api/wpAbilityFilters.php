<?php declare( strict_types=1 );

/**
 * Tests for filter integration on WP_Ability.
 *
 * @covers WP_Ability
 *
 * @group abilities-api
 */
class Tests_Abilities_API_WpAbility_Filters extends WP_UnitTestCase {

	public static $ability_name       = 'test/filter-ability';
	public static $ability_properties = array();

	/**
	 * Set up each test method.
	 */
	public function set_up(): void {
		parent::set_up();

		self::$ability_properties = array(
			'label'               => 'Filter ability',
			'description'         => 'Ability used to test filters.',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'value' => array(
						'type' => 'string',
					),
				),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type' => 'string',
			),
			'execute_callback'    => static function ( array $input ) {
				return $input['value'] ?? '';
			},
			'permission_callback' => static function (): bool {
				return true;
			},
		);
	}

	/**
	 * Input schema filter should be applied and receive the ability name.
	 *
	 * @covers WP_Ability::get_input_schema
	 */
	public function test_input_schema_filter_applied(): void {
		$ability = new WP_Ability( self::$ability_name, self::$ability_properties );

		$filter_cb = static function ( $schema, $ability_name ) {
			if ( 'test/filter-ability' !== $ability_name ) {
				return $schema;
			}

			$schema['properties']['extra'] = array( 'type' => 'number' );
			return $schema;
		};

		add_filter( 'ability_input_schema', $filter_cb, 10, 2 );

		$filtered = $ability->get_input_schema();

		remove_filter( 'ability_input_schema', $filter_cb );

		$this->assertArrayHasKey( 'extra', $filtered['properties'] );
		$this->assertSame( array( 'type' => 'number' ), $filtered['properties']['extra'] );
	}

	/**
	 * Output schema getter should cast non-array filter returns to array.
	 *
	 * @covers WP_Ability::get_output_schema
	 */
	public function test_output_schema_filter_non_array_returns_empty(): void {
		$ability = new WP_Ability( self::$ability_name, self::$ability_properties );

		$filter_cb = static function ( $schema ) {
			return 'not an array';
		};

		add_filter( 'ability_output_schema', $filter_cb );

		$output = $ability->get_output_schema();

		remove_filter( 'ability_output_schema', $filter_cb );

		$this->assertIsArray( $output );
		$this->assertSame( array( 'not an array' ), $output );
	}

	/**
	 * The ability_permission_result filter can override permission to false.
	 *
	 * @covers WP_Ability::has_permission
	 */
	public function test_permission_filter_can_override_false(): void {
		$ability = new WP_Ability( self::$ability_name, self::$ability_properties );

		$filter_cb = static function ( $permission, $ability_name ) {
			if ( 'test/filter-ability' !== $ability_name ) {
				return $permission;
			}

			return false;
		};

		add_filter( 'ability_permission_result', $filter_cb, 10, 2 );

		$result = $ability->has_permission();

		remove_filter( 'ability_permission_result', $filter_cb );

		$this->assertFalse( $result );
	}

	/**
	 * The ability_permission_result filter can return a WP_Error and it should be propagated.
	 *
	 * @covers WP_Ability::has_permission
	 */
	public function test_permission_filter_can_return_wp_error(): void {
		$ability = new WP_Ability( self::$ability_name, self::$ability_properties );

		$filter_cb = static function ( $permission, $ability_name ) {
			if ( 'test/filter-ability' !== $ability_name ) {
				return $permission;
			}

			return new \WP_Error( 'test_error', 'Denied by filter' );
		};

		add_filter( 'ability_permission_result', $filter_cb, 10, 2 );

		$result = $ability->has_permission();

		remove_filter( 'ability_permission_result', $filter_cb );

		$this->assertTrue( is_wp_error( $result ) );
	}

	/**
	 * The ability_execute_result filter should be applied to the result returned by execute().
	 *
	 * @covers WP_Ability::execute
	 */
	public function test_execute_result_filter_can_modify_result(): void {
		$ability = new WP_Ability( self::$ability_name, self::$ability_properties );

		$filter_cb = static function ( $result, $ability_name ) {
			if ( 'test/filter-ability' !== $ability_name ) {
				return $result;
			}

			return 'modified-' . $result;
		};

		add_filter( 'ability_execute_result', $filter_cb, 10, 2 );

		$output = $ability->execute( array( 'value' => 'ok' ) );

		remove_filter( 'ability_execute_result', $filter_cb, 10 );

		$this->assertSame( 'modified-ok', $output );
	}

	/**
	 * The ability_execute_result filter can replace the execute() result with a WP_Error.
	 *
	 * @covers WP_Ability::execute
	 */
	public function test_execute_result_filter_can_return_wp_error(): void {
		$ability = new WP_Ability( self::$ability_name, self::$ability_properties );

		$filter_cb = static function ( $result, $ability_name ) {
			if ( 'test/filter-ability' !== $ability_name ) {
				return $result;
			}

			return new \WP_Error( 'filtered_error', 'Filtered out' );
		};

		add_filter( 'ability_execute_result', $filter_cb, 10, 2 );

		$output = $ability->execute( array( 'value' => 'ok' ) );

		remove_filter( 'ability_execute_result', $filter_cb, 10 );

		$this->assertTrue( is_wp_error( $output ) );
		$this->assertSame( 'filtered_error', $output->get_error_code() );
	}
}
