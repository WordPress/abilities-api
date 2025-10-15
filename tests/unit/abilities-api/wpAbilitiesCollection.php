<?php
/**
 * Tests for the WP_Abilities_Collection class.
 *
 * @covers WP_Abilities_Collection
 *
 * @group abilities-api
 * @group abilities-collection
 */

declare( strict_types = 1 );

/**
 * Test case for WP_Abilities_Collection.
 */
class Tests_Abilities_API_WpAbilitiesCollection extends WP_UnitTestCase {

	/**
	 * Registry instance.
	 *
	 * @var \WP_Abilities_Registry
	 */
	private $registry;

	/**
	 * Set up test environment.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->registry = WP_Abilities_Registry::get_instance();

		// Register test categories during the hook.
		add_action(
			'abilities_api_categories_init',
			array( $this, 'register_test_categories' )
		);
		do_action( 'abilities_api_categories_init' );

		// Fire the abilities init hook to allow registration.
		do_action( 'abilities_api_init' );

		// Register test abilities (after hook has been fired).
		$this->register_test_abilities();
	}

	/**
	 * Tear down test environment.
	 */
	public function tear_down(): void {
		$this->cleanup_abilities();
		$this->cleanup_categories();
		parent::tear_down();
	}

	/**
	 * Test collection creation and count.
	 *
	 * @covers WP_Abilities_Collection::__construct
	 * @covers WP_Abilities_Collection::count
	 */
	public function test_collection_creation_and_count(): void {
		$abilities  = $this->registry->get_all_registered();
		$collection = new WP_Abilities_Collection( $abilities );

		$this->assertInstanceOf( WP_Abilities_Collection::class, $collection );
		$this->assertSame( count( $abilities ), $collection->count() );
	}

	/**
	 * Test constructor with abilities array.
	 *
	 * @covers WP_Abilities_Collection::__construct
	 */
	public function test_constructor_with_abilities(): void {
		$abilities  = $this->registry->get_all_registered();
		$collection = new WP_Abilities_Collection( $abilities );

		$this->assertInstanceOf( WP_Abilities_Collection::class, $collection );
		$this->assertSame( count( $abilities ), $collection->count() );
	}

	/**
	 * Test filter by single category.
	 *
	 * @covers WP_Abilities_Collection::where_category
	 */
	public function test_filter_by_single_category(): void {
		$collection     = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$math_abilities = $collection->where_category( 'math' );

		$this->assertGreaterThan( 0, $math_abilities->count() );

		foreach ( $math_abilities as $ability ) {
			$this->assertSame( 'math', $ability->get_category() );
		}
	}

	/**
	 * Test filter by multiple categories.
	 *
	 * @covers WP_Abilities_Collection::where_category
	 */
	public function test_filter_by_multiple_categories(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$filtered   = $collection->where_category( array( 'math', 'data-retrieval' ) );

		foreach ( $filtered as $ability ) {
			$this->assertContains( $ability->get_category(), array( 'math', 'data-retrieval' ) );
		}
	}

	/**
	 * Test filter by namespace.
	 *
	 * @covers WP_Abilities_Collection::where_namespace
	 */
	public function test_filter_by_namespace(): void {
		$collection     = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$test_abilities = $collection->where_namespace( 'test' );

		foreach ( $test_abilities as $ability ) {
			$this->assertStringStartsWith( 'test/', $ability->get_name() );
		}
	}

	/**
	 * Test filter by meta.
	 *
	 * @covers WP_Abilities_Collection::where_meta
	 */
	public function test_filter_by_meta(): void {
		$collection     = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$rest_abilities = $collection->where_meta( array( 'show_in_rest' => true ) );

		foreach ( $rest_abilities as $ability ) {
			$meta = $ability->get_meta();
			$this->assertTrue( $meta['show_in_rest'] );
		}
	}

	/**
	 * Test filter by nested meta using dot notation.
	 *
	 * @covers WP_Abilities_Collection::where_meta
	 */
	public function test_filter_by_nested_meta(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$readonly   = $collection->where_meta( array( 'annotations.readonly' => true ) );

		foreach ( $readonly as $ability ) {
			$meta = $ability->get_meta();
			$this->assertTrue( $meta['annotations']['readonly'] );
		}
	}

	/**
	 * Test search abilities.
	 *
	 * @covers WP_Abilities_Collection::search
	 */
	public function test_search_abilities(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$results    = $collection->search( 'number' );

		$this->assertGreaterThan( 0, $results->count() );

		foreach ( $results as $ability ) {
			$found = false !== stripos( $ability->get_name(), 'number' )
				|| false !== stripos( $ability->get_label(), 'number' )
				|| false !== stripos( $ability->get_description(), 'number' );

			$this->assertTrue( $found, 'Search term not found in ability' );
		}
	}

	/**
	 * Test sort by property ascending.
	 *
	 * @covers WP_Abilities_Collection::sort_by
	 */
	public function test_sort_by_property(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$sorted     = $collection->sort_by( 'name' );

		$names    = array_map(
			static function ( $a ) {
				return $a->get_name();
			},
			$sorted->to_array()
		);
		$expected = $names;
		sort( $expected );

		$this->assertSame( $expected, $names );
	}

	/**
	 * Test sort by property descending.
	 *
	 * @covers WP_Abilities_Collection::sort_by_desc
	 */
	public function test_sort_by_property_descending(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$sorted     = $collection->sort_by_desc( 'name' );

		$names    = array_map(
			static function ( $a ) {
				return $a->get_name();
			},
			$sorted->to_array()
		);
		$expected = $names;
		rsort( $expected );

		$this->assertSame( $expected, $names );
	}

	/**
	 * Test method chaining.
	 *
	 * @covers WP_Abilities_Collection::filter
	 * @covers WP_Abilities_Collection::where_category
	 * @covers WP_Abilities_Collection::where_meta
	 * @covers WP_Abilities_Collection::sort_by
	 */
	public function test_method_chaining(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );

		$result = $collection
			->where_category( 'math' )
			->where_meta( array( 'show_in_rest' => true ) )
			->sort_by( 'name' );

		$this->assertInstanceOf( WP_Abilities_Collection::class, $result );
		$this->assertGreaterThan( 0, $result->count() );

		foreach ( $result as $ability ) {
			$this->assertSame( 'math', $ability->get_category() );
			$meta = $ability->get_meta();
			$this->assertTrue( $meta['show_in_rest'] );
		}
	}

	/**
	 * Test first method.
	 *
	 * @covers WP_Abilities_Collection::first
	 */
	public function test_first(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$first      = $collection->first();

		$this->assertInstanceOf( WP_Ability::class, $first );
	}

	/**
	 * Test last method.
	 *
	 * @covers WP_Abilities_Collection::last
	 */
	public function test_last(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$last       = $collection->last();

		$this->assertInstanceOf( WP_Ability::class, $last );
	}

	/**
	 * Test get by name.
	 *
	 * @covers WP_Abilities_Collection::get
	 */
	public function test_get_by_name(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$ability    = $collection->get( 'test/add-numbers' );

		$this->assertInstanceOf( WP_Ability::class, $ability );
		$this->assertSame( 'test/add-numbers', $ability->get_name() );
	}

	/**
	 * Test is_empty method.
	 *
	 * @covers WP_Abilities_Collection::is_empty
	 */
	public function test_is_empty(): void {
		$empty = new WP_Abilities_Collection( array() );
		$this->assertTrue( $empty->is_empty() );

		$not_empty = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$this->assertFalse( $not_empty->is_empty() );
	}

	/**
	 * Test is_not_empty method.
	 *
	 * @covers WP_Abilities_Collection::is_not_empty
	 */
	public function test_is_not_empty(): void {
		$empty = new WP_Abilities_Collection( array() );
		$this->assertFalse( $empty->is_not_empty() );

		$not_empty = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$this->assertTrue( $not_empty->is_not_empty() );
	}

	/**
	 * Test immutability - original collection unchanged after filtering.
	 *
	 * @covers WP_Abilities_Collection::where_category
	 */
	public function test_immutability(): void {
		$collection     = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$original_count = $collection->count();

		$filtered = $collection->where_category( 'math' );

		// Original unchanged.
		$this->assertSame( $original_count, $collection->count() );

		// Filtered is different.
		$this->assertNotEquals( $original_count, $filtered->count() );
	}

	/**
	 * Test iterator interface.
	 *
	 * @covers WP_Abilities_Collection::getIterator
	 */
	public function test_iterator_interface(): void {
		$abilities  = $this->registry->get_all_registered();
		$collection = new WP_Abilities_Collection( $abilities );

		$count = 0;
		foreach ( $collection as $ability ) {
			$this->assertInstanceOf( WP_Ability::class, $ability );
			++$count;
		}

		$this->assertSame( count( $abilities ), $count );
	}

	/**
	 * Test pluck method.
	 *
	 * @covers WP_Abilities_Collection::pluck
	 */
	public function test_pluck(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$names      = $collection->pluck( 'name' );

		$this->assertIsArray( $names );
		$this->assertGreaterThan( 0, count( $names ) );
		$this->assertContains( 'test/add-numbers', $names );
	}

	/**
	 * Test pluck with key.
	 *
	 * @covers WP_Abilities_Collection::pluck
	 */
	public function test_pluck_with_key(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$labels     = $collection->pluck( 'label', 'name' );

		$this->assertIsArray( $labels );
		$this->assertArrayHasKey( 'test/add-numbers', $labels );
		$this->assertSame( 'Add Numbers', $labels['test/add-numbers'] );
	}

	/**
	 * Test where with dot notation.
	 *
	 * @covers WP_Abilities_Collection::where
	 */
	public function test_where_with_dot_notation(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where( 'meta.show_in_rest', true );

		$this->assertGreaterThan( 0, $result->count() );

		foreach ( $result as $ability ) {
			$meta = $ability->get_meta();
			$this->assertTrue( $meta['show_in_rest'] );
		}
	}

	/**
	 * Test where with operator.
	 *
	 * @covers WP_Abilities_Collection::where
	 */
	public function test_where_with_operator(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where( 'category', '!==', 'math' );

		foreach ( $result as $ability ) {
			$this->assertNotSame( 'math', $ability->get_category() );
		}
	}

	/**
	 * Test where_in method.
	 *
	 * @covers WP_Abilities_Collection::where_in
	 */
	public function test_where_in(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where_in( 'category', array( 'math', 'data-retrieval' ) );

		foreach ( $result as $ability ) {
			$this->assertContains( $ability->get_category(), array( 'math', 'data-retrieval' ) );
		}
	}

	/**
	 * Test where_not_in method.
	 *
	 * @covers WP_Abilities_Collection::where_not_in
	 */
	public function test_where_not_in(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where_not_in( 'category', array( 'math', 'data-retrieval' ) );

		foreach ( $result as $ability ) {
			$this->assertNotContains( $ability->get_category(), array( 'math', 'data-retrieval' ) );
		}
	}

	/**
	 * Test reverse method.
	 *
	 * @covers WP_Abilities_Collection::reverse
	 */
	public function test_reverse(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$original   = $collection->to_array();
		$reversed   = $collection->reverse()->to_array();

		$this->assertSame( array_reverse( $original ), $reversed );
	}

	/**
	 * Test values method.
	 *
	 * @covers WP_Abilities_Collection::values
	 */
	public function test_values(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$values     = $collection->values();

		$this->assertInstanceOf( WP_Abilities_Collection::class, $values );
		$this->assertSame( $collection->count(), $values->count() );
	}

	/**
	 * Test keys method.
	 *
	 * @covers WP_Abilities_Collection::keys
	 */
	public function test_keys(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$keys       = $collection->keys();

		$this->assertIsArray( $keys );
		$this->assertContains( 'test/add-numbers', $keys );
	}

	/**
	 * Test all method.
	 *
	 * @covers WP_Abilities_Collection::all
	 */
	public function test_all(): void {
		$abilities  = $this->registry->get_all_registered();
		$collection = new WP_Abilities_Collection( $abilities );

		$this->assertSame( $abilities, $collection->all() );
	}

	/**
	 * Test first with callback.
	 *
	 * @covers WP_Abilities_Collection::first
	 */
	public function test_first_with_callback(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );

		$first_math = $collection->first(
			static function ( $ability ) {
				return 'math' === $ability->get_category();
			}
		);

		$this->assertInstanceOf( WP_Ability::class, $first_math );
		$this->assertSame( 'math', $first_math->get_category() );
	}

	/**
	 * Test first with callback returning default.
	 *
	 * @covers WP_Abilities_Collection::first
	 */
	public function test_first_with_callback_default(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );

		$result = $collection->first(
			static function ( $ability ) {
				return 'nonexistent-category' === $ability->get_category();
			},
			'default-value'
		);

		$this->assertSame( 'default-value', $result );
	}

	/**
	 * Test first on empty collection returns default.
	 *
	 * @covers WP_Abilities_Collection::first
	 */
	public function test_first_empty_collection_default(): void {
		$collection = new WP_Abilities_Collection( array() );

		$result = $collection->first( null, 'empty-default' );

		$this->assertSame( 'empty-default', $result );
	}

	/**
	 * Test first on empty collection returns null by default.
	 *
	 * @covers WP_Abilities_Collection::first
	 */
	public function test_first_empty_collection_null(): void {
		$collection = new WP_Abilities_Collection( array() );

		$result = $collection->first();

		$this->assertNull( $result );
	}

	/**
	 * Test last with callback.
	 *
	 * @covers WP_Abilities_Collection::last
	 */
	public function test_last_with_callback(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );

		$last_math = $collection->last(
			static function ( $ability ) {
				return 'math' === $ability->get_category();
			}
		);

		$this->assertInstanceOf( WP_Ability::class, $last_math );
		$this->assertSame( 'math', $last_math->get_category() );
	}

	/**
	 * Test last with callback returning default.
	 *
	 * @covers WP_Abilities_Collection::last
	 */
	public function test_last_with_callback_default(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );

		$result = $collection->last(
			static function ( $ability ) {
				return 'nonexistent-category' === $ability->get_category();
			},
			'default-value'
		);

		$this->assertSame( 'default-value', $result );
	}

	/**
	 * Test last on empty collection returns default.
	 *
	 * @covers WP_Abilities_Collection::last
	 */
	public function test_last_empty_collection_default(): void {
		$collection = new WP_Abilities_Collection( array() );

		$result = $collection->last( null, 'empty-default' );

		$this->assertSame( 'empty-default', $result );
	}

	/**
	 * Test last on empty collection returns null by default.
	 *
	 * @covers WP_Abilities_Collection::last
	 */
	public function test_last_empty_collection_null(): void {
		$collection = new WP_Abilities_Collection( array() );

		$result = $collection->last();

		$this->assertNull( $result );
	}

	/**
	 * Test get with default value when ability not found.
	 *
	 * @covers WP_Abilities_Collection::get
	 */
	public function test_get_with_default(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );

		$result = $collection->get( 'nonexistent/ability', 'not-found' );

		$this->assertSame( 'not-found', $result );
	}

	/**
	 * Test get returns null when ability not found and no default.
	 *
	 * @covers WP_Abilities_Collection::get
	 */
	public function test_get_returns_null_when_not_found(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );

		$result = $collection->get( 'nonexistent/ability' );

		$this->assertNull( $result );
	}

	/**
	 * Test sort_by with custom callback.
	 *
	 * @covers WP_Abilities_Collection::sort_by
	 */
	public function test_sort_by_custom_callback(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );

		// Sort by name length.
		$sorted = $collection->sort_by(
			static function ( $a, $b ) {
				return strlen( $a->get_name() ) <=> strlen( $b->get_name() );
			}
		);

		$names = array_map(
			static function ( $a ) {
				return $a->get_name();
			},
			$sorted->to_array()
		);

		// Verify ascending order by length.
		$names_count = count( $names );
		for ( $i = 1; $i < $names_count; $i++ ) {
			$this->assertLessThanOrEqual(
				strlen( $names[ $i ] ),
				strlen( $names[ $i - 1 ] ),
				'Names should be sorted by length'
			);
		}
	}

	/**
	 * Test sort_by with custom callback descending.
	 *
	 * @covers WP_Abilities_Collection::sort_by
	 */
	public function test_sort_by_custom_callback_descending(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );

		// Sort by name length descending.
		$sorted = $collection->sort_by(
			static function ( $a, $b ) {
				return strlen( $a->get_name() ) <=> strlen( $b->get_name() );
			},
			true
		);

		$names = array_map(
			static function ( $a ) {
				return $a->get_name();
			},
			$sorted->to_array()
		);

		// Verify descending order by length.
		$names_count = count( $names );
		for ( $i = 1; $i < $names_count; $i++ ) {
			$this->assertGreaterThanOrEqual(
				strlen( $names[ $i ] ),
				strlen( $names[ $i - 1 ] ),
				'Names should be sorted by length descending'
			);
		}
	}

	/**
	 * Test where with greater than operator.
	 *
	 * @covers WP_Abilities_Collection::where
	 */
	public function test_where_with_greater_than(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where( 'meta.priority', '>', 5 );

		// Verify that filtering works correctly.
		$this->assertInstanceOf( WP_Abilities_Collection::class, $result );

		foreach ( $result as $ability ) {
			$meta = $ability->get_meta();
			if ( ! isset( $meta['priority'] ) ) {
				continue;
			}

			$this->assertGreaterThan( 5, $meta['priority'] );
		}

		// Also test that we found the expected high priority ability.
		$high_priority = $result->get( 'test/priority-high' );
		$this->assertInstanceOf( WP_Ability::class, $high_priority );
		$meta = $high_priority->get_meta();
		$this->assertSame( 10, $meta['priority'] );
	}

	/**
	 * Test where with less than operator.
	 *
	 * @covers WP_Abilities_Collection::where
	 */
	public function test_where_with_less_than(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where( 'meta.priority', '<', 5 );

		// Verify that filtering works correctly.
		$this->assertInstanceOf( WP_Abilities_Collection::class, $result );

		foreach ( $result as $ability ) {
			$meta = $ability->get_meta();
			if ( ! isset( $meta['priority'] ) ) {
				continue;
			}

			$this->assertLessThan( 5, $meta['priority'] );
		}

		// Also test that we found the expected low priority ability.
		$low_priority = $result->get( 'test/priority-low' );
		$this->assertInstanceOf( WP_Ability::class, $low_priority );
		$meta = $low_priority->get_meta();
		$this->assertSame( 3, $meta['priority'] );
	}

	/**
	 * Test where with greater than or equal operator.
	 *
	 * @covers WP_Abilities_Collection::where
	 */
	public function test_where_with_greater_than_or_equal(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where( 'meta.priority', '>=', 10 );

		// Verify that filtering works correctly.
		$this->assertInstanceOf( WP_Abilities_Collection::class, $result );

		foreach ( $result as $ability ) {
			$meta = $ability->get_meta();
			if ( ! isset( $meta['priority'] ) ) {
				continue;
			}

			$this->assertGreaterThanOrEqual( 10, $meta['priority'] );
		}

		// Also test that we found the expected high priority ability.
		$high_priority = $result->get( 'test/priority-high' );
		$this->assertInstanceOf( WP_Ability::class, $high_priority );
		$meta = $high_priority->get_meta();
		$this->assertSame( 10, $meta['priority'] );
	}

	/**
	 * Test where with less than or equal operator.
	 *
	 * @covers WP_Abilities_Collection::where
	 */
	public function test_where_with_less_than_or_equal(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where( 'meta.priority', '<=', 3 );

		// Verify that filtering works correctly.
		$this->assertInstanceOf( WP_Abilities_Collection::class, $result );

		foreach ( $result as $ability ) {
			$meta = $ability->get_meta();
			if ( ! isset( $meta['priority'] ) ) {
				continue;
			}

			$this->assertLessThanOrEqual( 3, $meta['priority'] );
		}

		// Also test that we found the expected low priority ability.
		$low_priority = $result->get( 'test/priority-low' );
		$this->assertInstanceOf( WP_Ability::class, $low_priority );
		$meta = $low_priority->get_meta();
		$this->assertSame( 3, $meta['priority'] );
	}

	/**
	 * Test where with loose equality operator.
	 *
	 * @covers WP_Abilities_Collection::where
	 */
	public function test_where_with_loose_equality(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where( 'meta.count', '==', 10 );

		$this->assertInstanceOf( WP_Abilities_Collection::class, $result );
		$this->assertGreaterThan( 0, $result->count() );

		// Verify the string-number ability was found with loose equality.
		$string_number = $result->get( 'test/string-number' );
		$this->assertInstanceOf( WP_Ability::class, $string_number );
		$meta = $string_number->get_meta();
		$this->assertSame( '10', $meta['count'] );
	}

	/**
	 * Test where with loose inequality operator.
	 *
	 * @covers WP_Abilities_Collection::where
	 */
	public function test_where_with_loose_inequality(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where( 'category', '!=', 'math' );

		foreach ( $result as $ability ) {
			$this->assertNotEquals( 'math', $ability->get_category() );
		}
	}

	/**
	 * Test where with alternative inequality operator.
	 *
	 * @covers WP_Abilities_Collection::where
	 */
	public function test_where_with_alternative_inequality(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where( 'category', '<>', 'math' );

		foreach ( $result as $ability ) {
			$this->assertNotEquals( 'math', $ability->get_category() );
		}
	}

	/**
	 * Test where_namespace with array of namespaces.
	 *
	 * @covers WP_Abilities_Collection::where_namespace
	 */
	public function test_where_namespace_with_array(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where_namespace( array( 'test', 'wordpress' ) );

		foreach ( $result as $ability ) {
			$name_parts = explode( '/', $ability->get_name() );
			$namespace  = $name_parts[0] ?? '';

			$this->assertContains( $namespace, array( 'test', 'wordpress' ) );
		}
	}

	/**
	 * Test pluck on empty collection.
	 *
	 * @covers WP_Abilities_Collection::pluck
	 */
	public function test_pluck_empty_collection(): void {
		$collection = new WP_Abilities_Collection( array() );
		$names      = $collection->pluck( 'name' );

		$this->assertIsArray( $names );
		$this->assertEmpty( $names );
	}

	/**
	 * Test pluck with dot notation for nested properties.
	 *
	 * @covers WP_Abilities_Collection::pluck
	 */
	public function test_pluck_with_dot_notation(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$show_in_rest_values = $collection->pluck( 'meta.show_in_rest' );

		$this->assertIsArray( $show_in_rest_values );
		$this->assertGreaterThan( 0, count( $show_in_rest_values ) );
		$this->assertContains( true, $show_in_rest_values );
		$this->assertContains( false, $show_in_rest_values );
	}

	/**
	 * Test pluck with dot notation for deeply nested properties.
	 *
	 * @covers WP_Abilities_Collection::pluck
	 */
	public function test_pluck_with_deep_dot_notation(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$readonly_values = $collection->pluck( 'meta.annotations.readonly' );

		$this->assertIsArray( $readonly_values );
		$this->assertGreaterThan( 0, count( $readonly_values ) );
		$this->assertContains( true, $readonly_values );
	}

	/**
	 * Test pluck with dot notation and key parameter.
	 *
	 * @covers WP_Abilities_Collection::pluck
	 */
	public function test_pluck_with_dot_notation_and_key(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result = $collection->pluck( 'meta.show_in_rest', 'name' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'test/add-numbers', $result );
		$this->assertTrue( $result['test/add-numbers'] );
		$this->assertArrayHasKey( 'test/multiply-numbers', $result );
		$this->assertFalse( $result['test/multiply-numbers'] );
	}

	/**
	 * Test pluck with both parameters using dot notation.
	 *
	 * @covers WP_Abilities_Collection::pluck
	 */
	public function test_pluck_with_both_dot_notation(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result = $collection->pluck( 'meta.annotations.readonly', 'category' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'math', $result );
		// Since multiple abilities have the same category, the last one wins.
		// We're mainly testing that dot notation works for both parameters.
		$this->assertIsBool( $result['math'] );
	}

	/**
	 * Test pluck with dot notation for nonexistent property.
	 *
	 * @covers WP_Abilities_Collection::pluck
	 */
	public function test_pluck_with_nonexistent_nested_property(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result = $collection->pluck( 'meta.nonexistent.property' );

		$this->assertIsArray( $result );
		// All values should be null since the property doesn't exist.
		foreach ( $result as $value ) {
			$this->assertNull( $value );
		}
	}

	/**
	 * Test filter method directly.
	 *
	 * @covers WP_Abilities_Collection::filter
	 */
	public function test_filter_method_directly(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );

		$filtered = $collection->filter(
			static function ( $ability ) {
				return strlen( $ability->get_name() ) > 15;
			}
		);

		$this->assertInstanceOf( WP_Abilities_Collection::class, $filtered );

		foreach ( $filtered as $ability ) {
			$this->assertGreaterThan( 15, strlen( $ability->get_name() ) );
		}
	}

	/**
	 * Test where with nonexistent nested meta key.
	 *
	 * @covers WP_Abilities_Collection::where
	 */
	public function test_where_with_nonexistent_nested_key(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where( 'meta.nonexistent.deeply.nested', true );

		$this->assertInstanceOf( WP_Abilities_Collection::class, $result );
		$this->assertSame( 0, $result->count() );
	}

	/**
	 * Test where_meta with nonexistent nested key.
	 *
	 * @covers WP_Abilities_Collection::where_meta
	 */
	public function test_where_meta_with_nonexistent_key(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where_meta( array( 'nonexistent.key' => 'value' ) );

		$this->assertInstanceOf( WP_Abilities_Collection::class, $result );
		$this->assertSame( 0, $result->count() );
	}

	/**
	 * Test to_array method.
	 *
	 * @covers WP_Abilities_Collection::to_array
	 */
	public function test_to_array(): void {
		$abilities  = $this->registry->get_all_registered();
		$collection = new WP_Abilities_Collection( $abilities );

		$array = $collection->to_array();

		$this->assertIsArray( $array );
		$this->assertSame( $abilities, $array );
	}

	/**
	 * Test search with case insensitivity.
	 *
	 * @covers WP_Abilities_Collection::search
	 */
	public function test_search_case_insensitive(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$lower      = $collection->search( 'email' );
		$upper      = $collection->search( 'EMAIL' );
		$mixed      = $collection->search( 'EmAiL' );

		$this->assertSame( $lower->count(), $upper->count() );
		$this->assertSame( $lower->count(), $mixed->count() );
	}

	/**
	 * Test search returns empty collection when no matches.
	 *
	 * @covers WP_Abilities_Collection::search
	 */
	public function test_search_no_matches(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->search( 'xyznonexistentxyz' );

		$this->assertInstanceOf( WP_Abilities_Collection::class, $result );
		$this->assertSame( 0, $result->count() );
	}

	/**
	 * Test where_in with dot notation.
	 *
	 * @covers WP_Abilities_Collection::where_in
	 */
	public function test_where_in_with_dot_notation(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where_in( 'meta.show_in_rest', array( true, false ) );

		$this->assertGreaterThan( 0, $result->count() );
	}

	/**
	 * Test where_not_in with dot notation.
	 *
	 * @covers WP_Abilities_Collection::where_not_in
	 */
	public function test_where_not_in_with_dot_notation(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );
		$result     = $collection->where_not_in( 'meta.show_in_rest', array( false ) );

		foreach ( $result as $ability ) {
			$meta = $ability->get_meta();
			if ( ! isset( $meta['show_in_rest'] ) ) {
				continue;
			}

			$this->assertTrue( $meta['show_in_rest'] );
		}
	}

	/**
	 * Test complex chaining with all methods.
	 *
	 * @covers WP_Abilities_Collection::where_category
	 * @covers WP_Abilities_Collection::where_meta
	 * @covers WP_Abilities_Collection::search
	 * @covers WP_Abilities_Collection::sort_by
	 * @covers WP_Abilities_Collection::first
	 */
	public function test_complex_chaining(): void {
		$collection = new WP_Abilities_Collection( $this->registry->get_all_registered() );

		$result = $collection
			->where_meta( array( 'show_in_rest' => true ) )
			->where_meta( array( 'annotations.readonly' => true ) )
			->sort_by( 'name' )
			->first();

		$this->assertInstanceOf( WP_Ability::class, $result );

		$meta = $result->get_meta();
		$this->assertTrue( $meta['show_in_rest'] );
		$this->assertTrue( $meta['annotations']['readonly'] );
	}

	/**
	 * Test empty collection with all methods.
	 *
	 * @covers WP_Abilities_Collection::where_category
	 * @covers WP_Abilities_Collection::sort_by
	 * @covers WP_Abilities_Collection::reverse
	 * @covers WP_Abilities_Collection::values
	 */
	public function test_empty_collection_methods(): void {
		$collection = new WP_Abilities_Collection( array() );

		// All methods should return empty collections or appropriate defaults.
		$this->assertSame( 0, $collection->where_category( 'math' )->count() );
		$this->assertSame( 0, $collection->sort_by( 'name' )->count() );
		$this->assertSame( 0, $collection->reverse()->count() );
		$this->assertSame( 0, $collection->values()->count() );
		$this->assertEmpty( $collection->keys() );
	}

	/**
	 * Register test categories.
	 */
	public function register_test_categories(): void {
		$categories = array( 'math', 'data-retrieval', 'communication', 'ecommerce' );

		foreach ( $categories as $slug ) {
			wp_register_ability_category(
				$slug,
				array(
					'label'       => ucfirst( $slug ),
					'description' => ucfirst( $slug ) . ' category.',
				)
			);
		}
	}

	/**
	 * Register test abilities.
	 */
	private function register_test_abilities(): void {
		// Math abilities.
		wp_register_ability(
			'test/add-numbers',
			array(
				'label'               => 'Add Numbers',
				'description'         => 'Adds two numbers together.',
				'category'            => 'math',
				'execute_callback'    => static function ( $input ) {
					return $input['a'] + $input['b'];
				},
				'permission_callback' => '__return_true',
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array( 'readonly' => true ),
				),
			)
		);

		wp_register_ability(
			'test/multiply-numbers',
			array(
				'label'               => 'Multiply Numbers',
				'description'         => 'Multiplies two numbers.',
				'category'            => 'math',
				'execute_callback'    => static function ( $input ) {
					return $input['a'] * $input['b'];
				},
				'permission_callback' => '__return_true',
				'meta'                => array(
					'show_in_rest' => false,
					'annotations'  => array( 'readonly' => true ),
				),
			)
		);

		// Data retrieval.
		wp_register_ability(
			'wordpress/get-posts',
			array(
				'label'               => 'Get Posts',
				'description'         => 'Retrieves WordPress posts.',
				'category'            => 'data-retrieval',
				'execute_callback'    => '__return_empty_array',
				'permission_callback' => '__return_true',
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array( 'readonly' => true ),
				),
			)
		);

		// Communication.
		wp_register_ability(
			'wordpress/send-email',
			array(
				'label'               => 'Send Email',
				'description'         => 'Sends an email.',
				'category'            => 'communication',
				'execute_callback'    => '__return_true',
				'permission_callback' => '__return_true',
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
					),
				),
			)
		);

		// Priority abilities for comparison operator tests.
		wp_register_ability(
			'test/priority-high',
			array(
				'label'               => 'High Priority',
				'description'         => 'High priority task.',
				'category'            => 'math',
				'execute_callback'    => '__return_true',
				'permission_callback' => '__return_true',
				'meta'                => array( 'priority' => 10 ),
			)
		);

		wp_register_ability(
			'test/priority-low',
			array(
				'label'               => 'Low Priority',
				'description'         => 'Low priority task.',
				'category'            => 'math',
				'execute_callback'    => '__return_true',
				'permission_callback' => '__return_true',
				'meta'                => array( 'priority' => 3 ),
			)
		);

		// String number ability for loose equality test.
		wp_register_ability(
			'test/string-number',
			array(
				'label'               => 'String Number',
				'description'         => 'Has string number.',
				'category'            => 'math',
				'execute_callback'    => '__return_true',
				'permission_callback' => '__return_true',
				'meta'                => array( 'count' => '10' ),
			)
		);
	}

	/**
	 * Clean up registered abilities.
	 */
	private function cleanup_abilities(): void {
		foreach ( $this->registry->get_all_registered() as $ability ) {
			$this->registry->unregister( $ability->get_name() );
		}
	}

	/**
	 * Clean up registered categories.
	 */
	private function cleanup_categories(): void {
		$categories = array( 'math', 'data-retrieval', 'communication', 'ecommerce' );
		foreach ( $categories as $slug ) {
			wp_unregister_ability_category( $slug );
		}
	}
}
