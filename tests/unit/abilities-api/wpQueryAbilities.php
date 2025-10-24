<?php declare( strict_types=1 );

/**
 * Tests for wp_query_abilities function.
 *
 * @covers wp_query_abilities
 *
 * @group abilities-api
 */
class Test_Abilities_API_WpQueryAbilities extends WP_UnitTestCase {
	public static $test_abilities = array();

	public function set_up(): void {
		parent::set_up();
		self::$test_abilities = array(
			'foo/one' => array(
				'label' => 'Foo One',
				'description' => 'Ability one',
				'input_schema' => array(),
				'output_schema' => array(),
				'execute_callback' => '__return_null',
				'permission_callback' => '__return_true',
				'meta' => array('type' => 'resource', 'tags' => array('a','b'), 'audience' => array('internal')),
			),
			'foo/two' => array(
				'label' => 'Foo Two',
				'description' => 'Ability two',
				'input_schema' => array(),
				'output_schema' => array(),
				'execute_callback' => '__return_null',
				'permission_callback' => '__return_true',
				'meta' => array('type' => 'tool', 'tags' => array('b','c')),
			),
			'bar/three' => array(
				'label' => 'Bar Three',
				'description' => 'Ability three',
				'input_schema' => array(),
				'output_schema' => array(),
				'execute_callback' => '__return_null',
				'permission_callback' => '__return_true',
				'meta' => array('type' => 'resource', 'audience' => array('external')),
			),
		);
		foreach ( self::$test_abilities as $name => $args ) {
			wp_register_ability( $name, $args );
		}
	}

	public function tear_down(): void {
		foreach ( array_keys( self::$test_abilities ) as $name ) {
			wp_unregister_ability( $name );
		}
		parent::tear_down();
	}

	public function test_query_by_namespace() {
		$result = wp_query_abilities( array( 'namespace' => 'foo' ) );
		$this->assertCount( 2, $result );
		foreach ( $result as $ability ) {
			$this->assertStringStartsWith( 'foo/', $ability->get_name() );
		}
	}

	public function test_query_by_meta_type() {
		$result = wp_query_abilities( array( 'type' => 'resource' ) );
		$this->assertCount( 2, $result );
	}

	public function test_query_by_meta_tags_includes() {
		$result = wp_query_abilities( array( 'tags' => array('b') ) );
		$this->assertCount( 2, $result );
	}

	public function test_query_by_audience_internal() {
		$result = wp_query_abilities( array( 'audience' => array('internal') ) );
		$this->assertCount( 1, $result );
		$ability = reset( $result );
		$this->assertEquals( 'foo/one', $ability->get_name() );
	}

	public function test_query_by_meta_key_presence() {
		$result = wp_query_abilities( array( '?tags' => true ) );
		$this->assertCount( 2, $result );
	}

	public function test_query_by_negation() {
		$result = wp_query_abilities( array( '!type' => 'tool' ) );
		$this->assertCount( 2, $result );
	}
}
