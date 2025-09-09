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

		self::$test_ability_properties = array(
			'label'               => 'Calculator',
			'description'         => 'Calculates the result of math operations.',
			'output_schema'       => array(
				'type'        => 'number',
				'description' => 'The result of performing a math operation.',
				'required'    => true,
			),
			'permission_callback' => static function (): bool {
				return true;
			},
			'meta'                => array(
				'category' => 'math',
			),
		);
	}

	/**
	 * Data provider for testing the execution of the ability.
	 */
	public function data_execute_input() {
		return array(
			'null input'     => array(
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
			'boolean input'  => array(
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
			'integer input'  => array(
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
			'number input'   => array(
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
			'string input'   => array(
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
			'array input'    => array(
				array(
					'type'                 => 'object',
					'properties'           => array(
						'a' => array(
							'type'        => 'number',
							'description' => 'First number.',
							'required'    => true,
						),
						'b' => array(
							'type'        => 'number',
							'description' => 'Second number.',
							'required'    => true,
						),
					),
					'additionalProperties' => false,
			    ),
				static function ( array $input ): int {
					return $input['a'] + $input['b'];
				},
				array( 'a' => 2, 'b' => 3 ),
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
}
