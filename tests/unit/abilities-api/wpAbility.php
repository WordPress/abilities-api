<?php declare( strict_types=1 );

/**
 * Tests for the abilities registry functionality.
 *
 * @covers WP_Ability
 *
 * @group abilities-api
 */
class Tests_Abilities_API_WpAbility extends WP_UnitTestCase {

	public static $test_ability_name       = 'test/calculator';
	public static $test_ability_properties = array();

	/**
	 * Set up each test method.
	 */
	public function set_up(): void {
		parent::set_up();

		// Register category during the hook.
		add_action(
			'abilities_api_categories_init',
			function () {
				if ( ! WP_Abilities_Category_Registry::get_instance()->is_registered( 'math' ) ) {
					wp_register_ability_category(
						'math',
						array(
							'label'       => 'Math',
							'description' => 'Mathematical operations and calculations.',
						)
					);
				}
			}
		);

		// Fire the hook to allow category registration.
		do_action( 'abilities_api_categories_init' );

		self::$test_ability_properties = array(
			'label'               => 'Calculator',
			'description'         => 'Calculates the result of math operations.',
			'category'            => 'math',
			'output_schema'       => array(
				'type'        => 'number',
				'description' => 'The result of performing a math operation.',
				'required'    => true,
			),
			'execute_callback'    => static function (): int {
				return 0;
			},
			'permission_callback' => static function (): bool {
				return true;
			},
			'meta'                => array(
				'annotations' => array(
					'readonly'    => true,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down(): void {
		// Clean up registered categories.
		$category_registry = WP_Abilities_Category_Registry::get_instance();
		if ( $category_registry->is_registered( 'math' ) ) {
			wp_unregister_ability_category( 'math' );
		}

		parent::tear_down();
	}

	/*
	 * Tests that getting non-existing metadata item returns default value.
	 */
	public function test_meta_get_non_existing_item_returns_default() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );

		$this->assertNull(
			$ability->get_meta_item( 'non_existing' ),
			'Non-existing metadata item should return null.'
		);
	}

	/**
	 * Tests that getting non-existing metadata item with custom default returns that default.
	 */
	public function test_meta_get_non_existing_item_with_custom_default() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );

		$this->assertSame(
			'default_value',
			$ability->get_meta_item( 'non_existing', 'default_value' ),
			'Non-existing metadata item should return custom default value.'
		);
	}

	/**
	 * Tests getting all annotations when selective overrides are applied.
	 */
	public function test_get_merged_annotations_from_meta() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );

		$this->assertEquals(
			array_merge(
				self::$test_ability_properties['meta']['annotations'],
				array(
					'instructions' => '',
					'idempotent'   => false,
				)
			),
			$ability->get_meta_item( 'annotations' )
		);
	}

	/**
	 * Tests getting default annotations when not provided.
	 */
	public function test_get_default_annotations_from_meta() {
		$args = self::$test_ability_properties;
		unset( $args['meta']['annotations'] );

		$ability = new WP_Ability( self::$test_ability_name, $args );

		$this->assertSame(
			array(
				'instructions' => '',
				'readonly'     => false,
				'destructive'  => true,
				'idempotent'   => false,
			),
			$ability->get_meta_item( 'annotations' )
		);
	}

	/**
	 * Tests getting all annotations when values overridden.
	 */
	public function test_get_overridden_annotations_from_meta() {
		$annotations = array(
			'instructions' => 'Enjoy responsibly.',
			'readonly'     => true,
			'destructive'  => false,
			'idempotent'   => false,
		);
		$args        = array_merge(
			self::$test_ability_properties,
			array(
				'meta' => array(
					'annotations' => $annotations,
				),
			)
		);

		$ability = new WP_Ability( self::$test_ability_name, $args );

		$this->assertSame( $annotations, $ability->get_meta_item( 'annotations' ) );
	}

	/**
	 * Tests that invalid `annotations` value throws an exception.
	 */
	public function test_annotations_from_meta_throws_exception() {
		$args = array_merge(
			self::$test_ability_properties,
			array(
				'meta' => array(
					'annotations' => 5,
				),
			)
		);

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'The ability meta should provide a valid `annotations` array.' );

		new WP_Ability( self::$test_ability_name, $args );
	}

	/**
	 * Tests that `show_in_rest` metadata defaults to false when not provided.
	 */
	public function test_meta_show_in_rest_defaults_to_false() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );

		$this->assertFalse(
			$ability->get_meta_item( 'show_in_rest' ),
			'`show_in_rest` metadata should default to false.'
		);
	}

	/**
	 * Tests that `show_in_rest` metadata can be set to true.
	 */
	public function test_meta_show_in_rest_can_be_set_to_true() {
		$args    = array_merge(
			self::$test_ability_properties,
			array(
				'meta' => array(
					'show_in_rest' => true,
				),
			)
		);
		$ability = new WP_Ability( self::$test_ability_name, $args );

		$this->assertTrue(
			$ability->get_meta_item( 'show_in_rest' ),
			'`show_in_rest` metadata should be true.'
		);
	}

	/**
	 * Tests that `show_in_rest` can be set to false.
	 */
	public function test_show_in_rest_can_be_set_to_false() {
		$args    = array_merge(
			self::$test_ability_properties,
			array(
				'meta' => array(
					'show_in_rest' => false,
				),
			)
		);
		$ability = new WP_Ability( self::$test_ability_name, $args );

		$this->assertFalse(
			$ability->get_meta_item( 'show_in_rest' ),
			'`show_in_rest` metadata should be false.'
		);
	}

	/**
	 * Tests that invalid `show_in_rest` value throws an exception.
	 */
	public function test_show_in_rest_throws_exception() {
		$args = array_merge(
			self::$test_ability_properties,
			array(
				'meta' => array(
					'show_in_rest' => 5,
				),
			)
		);

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'The ability meta should provide a valid `show_in_rest` boolean.' );

		new WP_Ability( self::$test_ability_name, $args );
	}

	/**
	 * Data provider for testing the execution of the ability.
	 */
	public function data_execute_input() {
		return array(
			'null input'    => array(
				array(
					'type'        => array( 'null', 'integer' ),
					'description' => 'The null or integer to convert to integer.',
					'required'    => true,
				),
				static function ( $input ): int {
					return null === $input ? 0 : (int) $input;
				},
				null,
				0,
			),
			'boolean input' => array(
				array(
					'type'        => 'boolean',
					'description' => 'The boolean to convert to integer.',
					'required'    => true,
				),
				static function ( bool $input ): int {
					return $input ? 1 : 0;
				},
				true,
				1,
			),
			'integer input' => array(
				array(
					'type'        => 'integer',
					'description' => 'The integer to add 5 to.',
					'required'    => true,
				),
				static function ( int $input ): int {
					return 5 + $input;
				},
				2,
				7,
			),
			'number input'  => array(
				array(
					'type'        => 'number',
					'description' => 'The floating number to round.',
					'required'    => true,
				),
				static function ( float $input ): int {
					return (int) round( $input );
				},
				2.7,
				3,
			),
			'string input'  => array(
				array(
					'type'        => 'string',
					'description' => 'The string to measure the length of.',
					'required'    => true,
				),
				static function ( string $input ): int {
					return strlen( $input );
				},
				'Hello world!',
				12,
			),
			'object input'  => array(
				array(
					'type'                 => 'object',
					'description'          => 'An object containing two numbers to add.',
					'properties'           => array(
						'a' => array(
							'type'        => 'integer',
							'description' => 'First number.',
							'required'    => true,
						),
						'b' => array(
							'type'        => 'integer',
							'description' => 'Second number.',
							'required'    => true,
						),
					),
					'additionalProperties' => false,
				),
				static function ( array $input ): int {
					return $input['a'] + $input['b'];
				},
				array(
					'a' => 2,
					'b' => 3,
				),
				5,
			),
			'array input'   => array(
				array(
					'type'        => 'array',
					'description' => 'An array containing two numbers to add.',
					'required'    => true,
					'minItems'    => 2,
					'maxItems'    => 2,
					'items'       => array(
						'type' => 'integer',
					),
				),
				static function ( array $input ): int {
					return $input[0] + $input[1];
				},
				array( 2, 3 ),
				5,
			),
		);
	}

	/**
	 * Tests the execution of the ability.
	 *
	 * @dataProvider data_execute_input
	 */
	public function test_execute_input( $input_schema, $execute_callback, $input, $result ) {
		$args = array_merge(
			self::$test_ability_properties,
			array(
				'input_schema'     => $input_schema,
				'execute_callback' => $execute_callback,
			)
		);

		$ability = new WP_Ability( self::$test_ability_name, $args );

		$this->assertSame( $result, $ability->execute( $input ) );
	}

	/**
	 * A static method to be used as a callback in tests.
	 *
	 * @param string $input An input string.
	 * @return int The length of the input string.
	 */
	public static function my_static_execute_callback( string $input ): int {
		return strlen( $input );
	}

	/**
	 * An instance method to be used as a callback in tests.
	 *
	 * @param string $input An input string.
	 * @return int The length of the input string.
	 */
	public function my_instance_execute_callback( string $input ): int {
		return strlen( $input );
	}

	/**
	 * Data provider for testing different types of execute callbacks.
	 */
	public function data_execute_callback() {
		return array(
			'function name string'       => array(
				'strlen',
			),
			'closure'                    => array(
				static function ( string $input ): int {
					return strlen( $input );
				},
			),
			'static class method string' => array(
				'Tests_Abilities_API_WpAbility::my_static_execute_callback',
			),
			'static class method array'  => array(
				array( 'Tests_Abilities_API_WpAbility', 'my_static_execute_callback' ),
			),
			'object method'              => array(
				array( $this, 'my_instance_execute_callback' ),
			),
		);
	}

	/**
	 * Tests the execution of the ability with different types of callbacks.
	 *
	 * @dataProvider data_execute_callback
	 */
	public function test_execute_with_different_callbacks( $execute_callback ) {
		$args = array_merge(
			self::$test_ability_properties,
			array(
				'input_schema'     => array(
					'type'        => 'string',
					'description' => 'Test input string.',
					'required'    => true,
				),
				'execute_callback' => $execute_callback,
			)
		);

		$ability = new WP_Ability( self::$test_ability_name, $args );

		$this->assertSame( 6, $ability->execute( 'hello!' ) );
	}

	/**
	 * Tests the execution of the ability with no input.
	 */
	public function test_execute_no_input() {
		$args = array_merge(
			self::$test_ability_properties,
			array(
				'execute_callback' => static function (): int {
					return 42;
				},
			)
		);

		$ability = new WP_Ability( self::$test_ability_name, $args );

		$this->assertSame( 42, $ability->execute() );
	}

	/**
	 * Tests that before_execute_ability action is fired with correct parameters.
	 */
	public function test_before_execute_ability_action() {
		$action_ability_name = null;
		$action_input        = null;

		$args = array_merge(
			self::$test_ability_properties,
			array(
				'input_schema'     => array(
					'type'        => 'integer',
					'description' => 'Test input parameter.',
					'required'    => true,
				),
				'execute_callback' => static function ( int $input ): int {
					return $input * 2;
				},
			)
		);

		$callback = static function ( $ability_name, $input ) use ( &$action_ability_name, &$action_input ) {
			$action_ability_name = $ability_name;
			$action_input        = $input;
		};

		add_action( 'before_execute_ability', $callback, 10, 2 );

		$ability = new WP_Ability( self::$test_ability_name, $args );
		$result  = $ability->execute( 5 );

		remove_action( 'before_execute_ability', $callback );

		$this->assertSame( self::$test_ability_name, $action_ability_name, 'Action should receive correct ability name' );
		$this->assertSame( 5, $action_input, 'Action should receive correct input' );
		$this->assertSame( 10, $result, 'Ability should execute correctly' );
	}

	/**
	 * Tests that before_execute_ability action is fired with null input when no input schema is defined.
	 */
	public function test_before_execute_ability_action_no_input() {
		$action_ability_name = null;
		$action_input        = null;

		$args = array_merge(
			self::$test_ability_properties,
			array(
				'execute_callback' => static function (): int {
					return 42;
				},
			)
		);

		$callback = static function ( $ability_name, $input ) use ( &$action_ability_name, &$action_input ) {
			$action_ability_name = $ability_name;
			$action_input        = $input;
		};

		add_action( 'before_execute_ability', $callback, 10, 2 );

		$ability = new WP_Ability( self::$test_ability_name, $args );
		$result  = $ability->execute();

		remove_action( 'before_execute_ability', $callback );

		$this->assertSame( self::$test_ability_name, $action_ability_name, 'Action should receive correct ability name' );
		$this->assertNull( $action_input, 'Action should receive null input when no input provided' );
		$this->assertSame( 42, $result, 'Ability should execute correctly' );
	}

	/**
	 * Tests that after_execute_ability action is fired with correct parameters.
	 */
	public function test_after_execute_ability_action() {
		$action_ability_name = null;
		$action_input        = null;
		$action_result       = null;

		$args = array_merge(
			self::$test_ability_properties,
			array(
				'input_schema'     => array(
					'type'        => 'integer',
					'description' => 'Test input parameter.',
					'required'    => true,
				),
				'execute_callback' => static function ( int $input ): int {
					return $input * 3;
				},
			)
		);

		$callback = static function ( $ability_name, $input, $result ) use ( &$action_ability_name, &$action_input, &$action_result ) {
			$action_ability_name = $ability_name;
			$action_input        = $input;
			$action_result       = $result;
		};

		add_action( 'after_execute_ability', $callback, 10, 3 );

		$ability = new WP_Ability( self::$test_ability_name, $args );
		$result  = $ability->execute( 7 );

		remove_action( 'after_execute_ability', $callback );

		$this->assertSame( self::$test_ability_name, $action_ability_name, 'Action should receive correct ability name' );
		$this->assertSame( 7, $action_input, 'Action should receive correct input' );
		$this->assertSame( 21, $action_result, 'Action should receive correct result' );
		$this->assertSame( 21, $result, 'Ability should execute correctly' );
	}

	/**
	 * Tests that after_execute_ability action is fired with null input when no input schema is defined.
	 */
	public function test_after_execute_ability_action_no_input() {
		$action_ability_name = null;
		$action_input        = null;
		$action_result       = null;

		$args = array_merge(
			self::$test_ability_properties,
			array(
				'output_schema'    => array(),
				'execute_callback' => static function (): string {
					return 'test-result';
				},
			)
		);

		$callback = static function ( $ability_name, $input, $result ) use ( &$action_ability_name, &$action_input, &$action_result ) {
			$action_ability_name = $ability_name;
			$action_input        = $input;
			$action_result       = $result;
		};

		add_action( 'after_execute_ability', $callback, 10, 3 );

		$ability = new WP_Ability( self::$test_ability_name, $args );
		$result  = $ability->execute();

		remove_action( 'after_execute_ability', $callback );

		$this->assertSame( self::$test_ability_name, $action_ability_name, 'Action should receive correct ability name' );
		$this->assertNull( $action_input, 'Action should receive null input when no input provided' );
		$this->assertSame( 'test-result', $action_result, 'Action should receive correct result' );
		$this->assertSame( 'test-result', $result, 'Ability should execute correctly' );
	}

	/**
	 * Tests that neither action is fired when execution fails due to permission issues.
	 */
	public function test_actions_not_fired_on_permission_failure() {
		$before_action_fired = false;
		$after_action_fired  = false;

		$args = array_merge(
			self::$test_ability_properties,
			array(
				'permission_callback' => static function (): bool {
					return false;
				},
			)
		);

		$before_callback = static function () use ( &$before_action_fired ) {
			$before_action_fired = true;
		};

		$after_callback = static function () use ( &$after_action_fired ) {
			$after_action_fired = true;
		};

		add_action( 'before_execute_ability', $before_callback );
		add_action( 'after_execute_ability', $after_callback );

		$ability = new WP_Ability( self::$test_ability_name, $args );
		$result  = $ability->execute();

		remove_action( 'before_execute_ability', $before_callback );
		remove_action( 'after_execute_ability', $after_callback );

		$this->assertFalse( $before_action_fired, 'before_execute_ability action should not be fired on permission failure' );
		$this->assertFalse( $after_action_fired, 'after_execute_ability action should not be fired on permission failure' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Should return WP_Error on permission failure' );
	}

	/**
	 * Tests that after_execute_ability action is not fired when execution callback returns WP_Error.
	 */
	public function test_after_action_not_fired_on_execution_error() {
		$before_action_fired = false;
		$after_action_fired  = false;

		$args = array_merge(
			self::$test_ability_properties,
			array(
				'execute_callback' => static function () {
					return new WP_Error( 'test_error', 'Test execution error' );
				},
			)
		);

		$before_callback = static function () use ( &$before_action_fired ) {
			$before_action_fired = true;
		};

		$after_callback = static function () use ( &$after_action_fired ) {
			$after_action_fired = true;
		};

		add_action( 'before_execute_ability', $before_callback );
		add_action( 'after_execute_ability', $after_callback );

		$ability = new WP_Ability( self::$test_ability_name, $args );
		$result  = $ability->execute();

		remove_action( 'before_execute_ability', $before_callback );
		remove_action( 'after_execute_ability', $after_callback );

		$this->assertTrue( $before_action_fired, 'before_execute_ability action should be fired even if execution fails' );
		$this->assertFalse( $after_action_fired, 'after_execute_ability action should not be fired when execution returns WP_Error' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Should return WP_Error from execution callback' );
	}

	/**
	 * Tests that after_execute_ability action is not fired when output validation fails.
	 */
	public function test_after_action_not_fired_on_output_validation_error() {
		$before_action_fired = false;
		$after_action_fired  = false;

		$args = array_merge(
			self::$test_ability_properties,
			array(
				'output_schema'    => array(
					'type'        => 'string',
					'description' => 'Expected string output.',
					'required'    => true,
				),
				'execute_callback' => static function (): int {
					return 42;
				},
			)
		);

		$before_callback = static function () use ( &$before_action_fired ) {
			$before_action_fired = true;
		};

		$after_callback = static function () use ( &$after_action_fired ) {
			$after_action_fired = true;
		};

		add_action( 'before_execute_ability', $before_callback );
		add_action( 'after_execute_ability', $after_callback );

		$ability = new WP_Ability( self::$test_ability_name, $args );
		$result  = $ability->execute();

		remove_action( 'before_execute_ability', $before_callback );
		remove_action( 'after_execute_ability', $after_callback );

		$this->assertTrue( $before_action_fired, 'before_execute_ability action should be fired even if output validation fails' );
		$this->assertFalse( $after_action_fired, 'after_execute_ability action should not be fired when output validation fails' );
		$this->assertInstanceOf( WP_Error::class, $result, 'Should return WP_Error for output validation failure' );
	}

	/**
	 * Tests that to_array() returns correct structure with all expected keys.
	 */
	public function test_to_array_returns_correct_structure() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );
		$array   = $ability->to_array();

		$this->assertIsArray( $array, 'to_array() should return an array' );
		$this->assertArrayHasKey( 'name', $array, 'Array should contain name key' );
		$this->assertArrayHasKey( 'label', $array, 'Array should contain label key' );
		$this->assertArrayHasKey( 'description', $array, 'Array should contain description key' );
		$this->assertArrayHasKey( 'input_schema', $array, 'Array should contain input_schema key' );
		$this->assertArrayHasKey( 'output_schema', $array, 'Array should contain output_schema key' );
		$this->assertArrayHasKey( 'meta', $array, 'Array should contain meta key' );

		$this->assertSame( self::$test_ability_name, $array['name'], 'Name should match' );
		$this->assertSame( 'Calculator', $array['label'], 'Label should match' );
		$this->assertSame( 'Calculates the result of math operations.', $array['description'], 'Description should match' );
	}

	/**
	 * Tests that to_array() does not include callbacks.
	 */
	public function test_to_array_excludes_callbacks() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );
		$array   = $ability->to_array();

		$this->assertArrayNotHasKey( 'execute_callback', $array, 'Array should not contain execute_callback' );
		$this->assertArrayNotHasKey( 'permission_callback', $array, 'Array should not contain permission_callback' );
	}

	/**
	 * Tests to_array() with both input and output schemas.
	 */
	public function test_to_array_with_full_schemas() {
		$args = array_merge(
			self::$test_ability_properties,
			array(
				'input_schema' => array(
					'type'        => 'integer',
					'description' => 'Test input.',
				),
			)
		);

		$ability = new WP_Ability( self::$test_ability_name, $args );
		$array   = $ability->to_array();

		$this->assertIsArray( $array['input_schema'], 'Input schema should be an array' );
		$this->assertSame( 'integer', $array['input_schema']['type'], 'Input schema type should match' );
		$this->assertIsArray( $array['output_schema'], 'Output schema should be an array' );
		$this->assertSame( 'number', $array['output_schema']['type'], 'Output schema type should match' );
	}

	/**
	 * Tests to_array() without input schema.
	 */
	public function test_to_array_without_input_schema() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );
		$array   = $ability->to_array();

		$this->assertArrayHasKey( 'input_schema', $array, 'input_schema key should exist' );
		$this->assertIsArray( $array['input_schema'], 'input_schema should be an array' );
		$this->assertEmpty( $array['input_schema'], 'input_schema should be empty' );
	}

	/**
	 * Tests to_array() meta structure.
	 */
	public function test_to_array_meta_structure() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );
		$array   = $ability->to_array();

		$this->assertIsArray( $array['meta'], 'Meta should be an array' );
		$this->assertArrayHasKey( 'annotations', $array['meta'], 'Meta should contain annotations' );
		$this->assertArrayHasKey( 'show_in_rest', $array['meta'], 'Meta should contain show_in_rest' );

		$this->assertIsArray( $array['meta']['annotations'], 'Annotations should be an array' );
		$this->assertArrayHasKey( 'readonly', $array['meta']['annotations'], 'Annotations should contain readonly' );
		$this->assertArrayHasKey( 'destructive', $array['meta']['annotations'], 'Annotations should contain destructive' );
		$this->assertArrayHasKey( 'idempotent', $array['meta']['annotations'], 'Annotations should contain idempotent' );
		$this->assertArrayHasKey( 'instructions', $array['meta']['annotations'], 'Annotations should contain instructions' );

		$this->assertTrue( $array['meta']['annotations']['readonly'], 'Readonly should be true' );
		$this->assertFalse( $array['meta']['annotations']['destructive'], 'Destructive should be false' );
		$this->assertFalse( $array['meta']['show_in_rest'], 'show_in_rest should default to false' );
	}

	/**
	 * Tests to_array() with custom meta properties.
	 */
	public function test_to_array_with_custom_meta() {
		$args = array_merge(
			self::$test_ability_properties,
			array(
				'meta' => array(
					'custom_property' => 'custom_value',
					'another_prop'    => 123,
				),
			)
		);

		$ability = new WP_Ability( self::$test_ability_name, $args );
		$array   = $ability->to_array();

		$this->assertArrayHasKey( 'custom_property', $array['meta'], 'Custom meta property should be included' );
		$this->assertSame( 'custom_value', $array['meta']['custom_property'], 'Custom meta value should match' );
		$this->assertArrayHasKey( 'another_prop', $array['meta'], 'Another custom meta property should be included' );
		$this->assertSame( 123, $array['meta']['another_prop'], 'Another custom meta value should match' );
	}

	/**
	 * Tests that to_json_schema() returns valid JSON Schema structure.
	 */
	public function test_to_json_schema_returns_valid_structure() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );
		$schema  = $ability->to_json_schema();

		$this->assertIsArray( $schema, 'to_json_schema() should return an array' );
		$this->assertArrayHasKey( '$schema', $schema, 'Schema should contain $schema key' );
		$this->assertSame( 'http://json-schema.org/draft-04/schema#', $schema['$schema'], 'Schema version should be Draft 4' );
		$this->assertArrayHasKey( 'type', $schema, 'Schema should contain type key' );
		$this->assertSame( 'object', $schema['type'], 'Schema type should be object' );
		$this->assertArrayHasKey( 'title', $schema, 'Schema should contain title key' );
		$this->assertSame( 'Calculator', $schema['title'], 'Schema title should match label' );
		$this->assertArrayHasKey( 'description', $schema, 'Schema should contain description key' );
		$this->assertSame( 'Calculates the result of math operations.', $schema['description'], 'Schema description should match' );
	}

	/**
	 * Tests to_json_schema() with both input and output schemas.
	 */
	public function test_to_json_schema_with_both_schemas() {
		$args = array_merge(
			self::$test_ability_properties,
			array(
				'input_schema' => array(
					'type'        => 'integer',
					'description' => 'Test input.',
				),
			)
		);

		$ability = new WP_Ability( self::$test_ability_name, $args );
		$schema  = $ability->to_json_schema();

		$this->assertArrayHasKey( 'properties', $schema, 'Schema should contain properties' );
		$this->assertArrayHasKey( 'input_schema', $schema['properties'], 'Properties should contain input_schema' );
		$this->assertArrayHasKey( 'output_schema', $schema['properties'], 'Properties should contain output_schema' );

		$this->assertSame( 'integer', $schema['properties']['input_schema']['type'], 'Input schema should be preserved' );
		$this->assertSame( 'number', $schema['properties']['output_schema']['type'], 'Output schema should be preserved' );

		$this->assertContains( 'input_schema', $schema['required'], 'input_schema should be in required array' );
		$this->assertContains( 'output_schema', $schema['required'], 'output_schema should be in required array' );
	}

	/**
	 * Tests to_json_schema() without input schema.
	 */
	public function test_to_json_schema_without_input_schema() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );
		$schema  = $ability->to_json_schema();

		$this->assertArrayNotHasKey( 'input_schema', $schema['properties'], 'Properties should not contain input_schema when not defined' );
		$this->assertNotContains( 'input_schema', $schema['required'], 'input_schema should not be in required array when not defined' );
		$this->assertArrayHasKey( 'output_schema', $schema['properties'], 'Properties should contain output_schema' );
		$this->assertContains( 'output_schema', $schema['required'], 'output_schema should be in required array' );
	}

	/**
	 * Tests to_json_schema() meta structure.
	 */
	public function test_to_json_schema_meta_structure() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );
		$schema  = $ability->to_json_schema();

		$this->assertArrayHasKey( 'meta', $schema['properties'], 'Properties should contain meta' );
		$this->assertSame( 'object', $schema['properties']['meta']['type'], 'Meta type should be object' );
		$this->assertArrayHasKey( 'properties', $schema['properties']['meta'], 'Meta should have properties' );
		$this->assertArrayHasKey( 'annotations', $schema['properties']['meta']['properties'], 'Meta properties should contain annotations' );
		$this->assertArrayHasKey( 'show_in_rest', $schema['properties']['meta']['properties'], 'Meta properties should contain show_in_rest' );

		$annotations = $schema['properties']['meta']['properties']['annotations'];
		$this->assertSame( 'object', $annotations['type'], 'Annotations type should be object' );
		$this->assertArrayHasKey( 'properties', $annotations, 'Annotations should have properties' );
		$this->assertArrayHasKey( 'readonly', $annotations['properties'], 'Annotations properties should contain readonly' );
		$this->assertArrayHasKey( 'destructive', $annotations['properties'], 'Annotations properties should contain destructive' );
		$this->assertArrayHasKey( 'idempotent', $annotations['properties'], 'Annotations properties should contain idempotent' );
		$this->assertArrayHasKey( 'instructions', $annotations['properties'], 'Annotations properties should contain instructions' );

		$this->assertSame( 'boolean', $schema['properties']['meta']['properties']['show_in_rest']['type'], 'show_in_rest type should be boolean' );
	}

	/**
	 * Tests to_json_schema() name property uses enum for constant value.
	 */
	public function test_to_json_schema_name_is_constant() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );
		$schema  = $ability->to_json_schema();

		$this->assertArrayHasKey( 'name', $schema['properties'], 'Properties should contain name' );
		$this->assertArrayHasKey( 'enum', $schema['properties']['name'], 'Name should have enum keyword' );
		$this->assertIsArray( $schema['properties']['name']['enum'], 'Name enum should be an array' );
		$this->assertCount( 1, $schema['properties']['name']['enum'], 'Name enum should have exactly one value' );
		$this->assertSame( self::$test_ability_name, $schema['properties']['name']['enum'][0], 'Name enum value should match ability name' );
		$this->assertContains( 'name', $schema['required'], 'name should be in required array' );
	}

	/**
	 * Tests that to_array() filter is applied.
	 */
	public function test_to_array_filter() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );

		$filter_callback = static function ( $array, $ability_instance ) {
			$array['custom_field'] = 'custom_value';
			return $array;
		};

		add_filter( 'wp_ability_test/calculator_to_array', $filter_callback, 10, 2 );

		$array = $ability->to_array();

		remove_filter( 'wp_ability_test/calculator_to_array', $filter_callback );

		$this->assertArrayHasKey( 'custom_field', $array, 'Filtered array should contain custom field' );
		$this->assertSame( 'custom_value', $array['custom_field'], 'Custom field value should match' );
	}

	/**
	 * Tests that to_json_schema() filter is applied.
	 */
	public function test_to_json_schema_filter() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );

		$filter_callback = static function ( $schema, $ability_instance ) {
			$schema['custom_property'] = 'custom_schema_value';
			return $schema;
		};

		add_filter( 'wp_ability_test/calculator_to_json_schema', $filter_callback, 10, 2 );

		$schema = $ability->to_json_schema();

		remove_filter( 'wp_ability_test/calculator_to_json_schema', $filter_callback );

		$this->assertArrayHasKey( 'custom_property', $schema, 'Filtered schema should contain custom property' );
		$this->assertSame( 'custom_schema_value', $schema['custom_property'], 'Custom property value should match' );
	}

	/**
	 * Tests that to_array() filter receives ability instance as second parameter.
	 */
	public function test_to_array_filter_receives_ability_instance() {
		$ability          = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );
		$received_ability = null;

		$filter_callback = static function ( $array, $ability_instance ) use ( &$received_ability ) {
			$received_ability = $ability_instance;
			return $array;
		};

		add_filter( 'wp_ability_test/calculator_to_array', $filter_callback, 10, 2 );

		$ability->to_array();

		remove_filter( 'wp_ability_test/calculator_to_array', $filter_callback );

		$this->assertInstanceOf( WP_Ability::class, $received_ability, 'Filter should receive WP_Ability instance' );
		$this->assertSame( self::$test_ability_name, $received_ability->get_name(), 'Received ability should match' );
	}

	/**
	 * Tests that to_json_schema() filter receives ability instance as second parameter.
	 */
	public function test_to_json_schema_filter_receives_ability_instance() {
		$ability          = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );
		$received_ability = null;

		$filter_callback = static function ( $schema, $ability_instance ) use ( &$received_ability ) {
			$received_ability = $ability_instance;
			return $schema;
		};

		add_filter( 'wp_ability_test/calculator_to_json_schema', $filter_callback, 10, 2 );

		$ability->to_json_schema();

		remove_filter( 'wp_ability_test/calculator_to_json_schema', $filter_callback );

		$this->assertInstanceOf( WP_Ability::class, $received_ability, 'Filter should receive WP_Ability instance' );
		$this->assertSame( self::$test_ability_name, $received_ability->get_name(), 'Received ability should match' );
	}

	/**
	 * Tests that WP_Ability implements JsonSerializable.
	 */
	public function test_implements_json_serializable() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );

		$this->assertInstanceOf( JsonSerializable::class, $ability, 'WP_Ability should implement JsonSerializable' );
	}

	/**
	 * Tests that json_encode() works with WP_Ability.
	 */
	public function test_json_encode() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );

		$json = json_encode( $ability );

		$this->assertIsString( $json, 'json_encode should return a string' );
		$this->assertNotFalse( $json, 'json_encode should not fail' );

		$decoded = json_decode( $json, true );

		$this->assertIsArray( $decoded, 'Decoded JSON should be an array' );
		$this->assertArrayHasKey( 'name', $decoded, 'Decoded array should contain name' );
		$this->assertSame( self::$test_ability_name, $decoded['name'], 'Name should match' );
		$this->assertArrayHasKey( 'label', $decoded, 'Decoded array should contain label' );
		$this->assertArrayHasKey( 'description', $decoded, 'Decoded array should contain description' );
		$this->assertArrayHasKey( 'meta', $decoded, 'Decoded array should contain meta' );
	}

	/**
	 * Tests that jsonSerialize() returns same data as to_array().
	 */
	public function test_json_serialize_matches_to_array() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );

		$array          = $ability->to_array();
		$json_serialize = $ability->jsonSerialize();

		$this->assertSame( $array, $json_serialize, 'jsonSerialize() should return the same data as to_array()' );
	}

	/**
	 * Tests that json_encode() applies to_array() filter.
	 */
	public function test_json_encode_applies_filter() {
		$ability = new WP_Ability( self::$test_ability_name, self::$test_ability_properties );

		$filter_callback = static function ( $array, $ability_instance ) {
			$array['filtered'] = true;
			return $array;
		};

		add_filter( 'wp_ability_test/calculator_to_array', $filter_callback, 10, 2 );

		$json = json_encode( $ability );
		$decoded = json_decode( $json, true );

		remove_filter( 'wp_ability_test/calculator_to_array', $filter_callback );

		$this->assertArrayHasKey( 'filtered', $decoded, 'json_encode should apply to_array filter' );
		$this->assertTrue( $decoded['filtered'], 'Filtered value should be present' );
	}
}
