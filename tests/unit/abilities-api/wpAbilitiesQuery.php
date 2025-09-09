<?php
/**
 * Unit tests covering wp_get_abilities() function with filtering via WP_Abilities_Query.
 *
 * @package abilities-api
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests for the WP_Abilities_Query class.
 */
class WpAbilitiesQueryTest extends TestCase {

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Clear any existing abilities for a clean slate.
		$registry = WP_Abilities_Registry::get_instance();
		$reflection = new ReflectionClass( $registry );
		$property = $reflection->getProperty( 'registered_abilities' );
		$property->setAccessible( true );
		$property->setValue( $registry, array() );

		// Register test abilities.
		wp_register_ability(
			'test-plugin/ability-one',
			array(
				'label'        => 'Test Ability One',
				'description'  => 'First test ability for filtering',
				'input_schema' => array( 'type' => 'object' ),
				'meta'         => array( 'category' => 'test', 'level' => 1 ),
			)
		);

		wp_register_ability(
			'test-plugin/ability-two',
			array(
				'label'         => 'Test Ability Two',
				'description'   => 'Second test ability for filtering',
				'output_schema' => array( 'type' => 'string' ),
				'meta'          => array( 'category' => 'test', 'level' => 2 ),
			)
		);

		wp_register_ability(
			'other-plugin/ability-three',
			array(
				'label'         => 'Other Ability',
				'description'   => 'Ability from different namespace',
				'input_schema'  => array( 'type' => 'string' ),
				'output_schema' => array( 'type' => 'object' ),
				'meta'          => array( 'category' => 'other', 'level' => 1 ),
			)
		);

		wp_register_ability(
			'core/basic-ability',
			array(
				'label'       => 'Basic Core Ability',
				'description' => 'Simple ability with no schemas',
				'meta'        => array( 'category' => 'core' ),
			)
		);
	}

	/**
	 * Test backward compatibility - wp_get_abilities() without arguments should return all abilities.
	 */
	public function test_wp_get_abilities_backward_compatibility(): void {
		$abilities = wp_get_abilities();
		$this->assertCount( 4, $abilities );

		$names = array_keys( $abilities );
		$this->assertContains( 'test-plugin/ability-one', $names );
		$this->assertContains( 'test-plugin/ability-two', $names );
		$this->assertContains( 'other-plugin/ability-three', $names );
		$this->assertContains( 'core/basic-ability', $names );
	}

	/**
	 * Test filtering by namespace.
	 */
	public function test_filter_by_namespace(): void {
		// Filter by test-plugin namespace.
		$abilities = wp_get_abilities( array( 'namespace' => 'test-plugin' ) );
		$this->assertCount( 2, $abilities );
		$this->assertArrayHasKey( 'test-plugin/ability-one', $abilities );
		$this->assertArrayHasKey( 'test-plugin/ability-two', $abilities );

		// Filter by other-plugin namespace.
		$abilities = wp_get_abilities( array( 'namespace' => 'other-plugin' ) );
		$this->assertCount( 1, $abilities );
		$this->assertArrayHasKey( 'other-plugin/ability-three', $abilities );

		// Filter by core namespace.
		$abilities = wp_get_abilities( array( 'namespace' => 'core' ) );
		$this->assertCount( 1, $abilities );
		$this->assertArrayHasKey( 'core/basic-ability', $abilities );

		// Filter by non-existent namespace.
		$abilities = wp_get_abilities( array( 'namespace' => 'nonexistent' ) );
		$this->assertCount( 0, $abilities );
	}

	/**
	 * Test filtering by search term.
	 */
	public function test_filter_by_search(): void {
		// Search in labels.
		$abilities = wp_get_abilities( array( 'search' => 'Test' ) );
		$this->assertCount( 2, $abilities );
		$this->assertArrayHasKey( 'test-plugin/ability-one', $abilities );
		$this->assertArrayHasKey( 'test-plugin/ability-two', $abilities );

		// Search in descriptions.
		$abilities = wp_get_abilities( array( 'search' => 'filtering' ) );
		$this->assertCount( 2, $abilities );
		$this->assertArrayHasKey( 'test-plugin/ability-one', $abilities );
		$this->assertArrayHasKey( 'test-plugin/ability-two', $abilities );

		// Case insensitive search.
		$abilities = wp_get_abilities( array( 'search' => 'BASIC' ) );
		$this->assertCount( 1, $abilities );
		$this->assertArrayHasKey( 'core/basic-ability', $abilities );

		// Search that matches nothing.
		$abilities = wp_get_abilities( array( 'search' => 'nonexistent' ) );
		$this->assertCount( 0, $abilities );
	}

	/**
	 * Test filtering by input schema presence.
	 */
	public function test_filter_by_has_input_schema(): void {
		// Filter abilities that have input schema.
		$abilities = wp_get_abilities( array( 'has_input_schema' => true ) );
		$this->assertCount( 2, $abilities );
		$this->assertArrayHasKey( 'test-plugin/ability-one', $abilities );
		$this->assertArrayHasKey( 'other-plugin/ability-three', $abilities );

		// Filter abilities that don't have input schema.
		$abilities = wp_get_abilities( array( 'has_input_schema' => false ) );
		$this->assertCount( 2, $abilities );
		$this->assertArrayHasKey( 'test-plugin/ability-two', $abilities );
		$this->assertArrayHasKey( 'core/basic-ability', $abilities );
	}

	/**
	 * Test filtering by output schema presence.
	 */
	public function test_filter_by_has_output_schema(): void {
		// Filter abilities that have output schema.
		$abilities = wp_get_abilities( array( 'has_output_schema' => true ) );
		$this->assertCount( 2, $abilities );
		$this->assertArrayHasKey( 'test-plugin/ability-two', $abilities );
		$this->assertArrayHasKey( 'other-plugin/ability-three', $abilities );

		// Filter abilities that don't have output schema.
		$abilities = wp_get_abilities( array( 'has_output_schema' => false ) );
		$this->assertCount( 2, $abilities );
		$this->assertArrayHasKey( 'test-plugin/ability-one', $abilities );
		$this->assertArrayHasKey( 'core/basic-ability', $abilities );
	}

	/**
	 * Test filtering by meta query.
	 */
	public function test_filter_by_meta_query(): void {
		// Filter by category meta.
		$abilities = wp_get_abilities(
			array(
				'meta_query' => array(
					array(
						'key'   => 'category',
						'value' => 'test',
					),
				),
			)
		);
		$this->assertCount( 2, $abilities );
		$this->assertArrayHasKey( 'test-plugin/ability-one', $abilities );
		$this->assertArrayHasKey( 'test-plugin/ability-two', $abilities );

		// Filter by level meta.
		$abilities = wp_get_abilities(
			array(
				'meta_query' => array(
					array(
						'key'   => 'level',
						'value' => 1,
					),
				),
			)
		);
		$this->assertCount( 2, $abilities );
		$this->assertArrayHasKey( 'test-plugin/ability-one', $abilities );
		$this->assertArrayHasKey( 'other-plugin/ability-three', $abilities );

		// Test EXISTS compare.
		$abilities = wp_get_abilities(
			array(
				'meta_query' => array(
					array(
						'key'     => 'level',
						'compare' => 'EXISTS',
					),
				),
			)
		);
		$this->assertCount( 3, $abilities );
		$this->assertArrayNotHasKey( 'core/basic-ability', $abilities );

		// Test NOT EXISTS compare.
		$abilities = wp_get_abilities(
			array(
				'meta_query' => array(
					array(
						'key'     => 'level',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
		$this->assertCount( 1, $abilities );
		$this->assertArrayHasKey( 'core/basic-ability', $abilities );
	}

	/**
	 * Test combining multiple filters.
	 */
	public function test_combine_multiple_filters(): void {
		// Combine namespace and search filters.
		$abilities = wp_get_abilities(
			array(
				'namespace' => 'test-plugin',
				'search'    => 'First',
			)
		);
		$this->assertCount( 1, $abilities );
		$this->assertArrayHasKey( 'test-plugin/ability-one', $abilities );

		// Combine namespace and schema filters.
		$abilities = wp_get_abilities(
			array(
				'namespace'        => 'test-plugin',
				'has_input_schema' => true,
			)
		);
		$this->assertCount( 1, $abilities );
		$this->assertArrayHasKey( 'test-plugin/ability-one', $abilities );

		// Combine all filters - should return empty result.
		$abilities = wp_get_abilities(
			array(
				'namespace'         => 'test-plugin',
				'search'            => 'Other',
				'has_input_schema'  => true,
				'has_output_schema' => true,
			)
		);
		$this->assertCount( 0, $abilities );
	}

	/**
	 * Test WP_Abilities_Query class directly.
	 */
	public function test_wp_abilities_query_class(): void {
		$query = new WP_Abilities_Query( array( 'namespace' => 'core' ) );
		$abilities = $query->get_abilities();
		$this->assertCount( 1, $abilities );
		$this->assertArrayHasKey( 'core/basic-ability', $abilities );

		// Test empty query.
		$query = new WP_Abilities_Query();
		$abilities = $query->get_abilities();
		$this->assertCount( 4, $abilities );
	}
}