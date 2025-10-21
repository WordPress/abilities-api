<?php declare( strict_types=1 );

/**
 * Tests for the abilities query functionality.
 *
 * @covers WP_Abilities_Query
 *
 * @group abilities-api
 */
class Tests_Abilities_API_WpAbilitiesQuery extends WP_UnitTestCase {

	/**
	 * Abilities registry instance.
	 *
	 * @var \WP_Abilities_Registry
	 */
	private $registry = null;

	/**
	 * Set up each test method.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->registry = WP_Abilities_Registry::get_instance();

		// Register test categories.
		add_action(
			'abilities_api_categories_init',
			static function () {
				$categories = array( 'math', 'data-retrieval', 'communication', 'ecommerce' );
				foreach ( $categories as $category_slug ) {
					$registry = WP_Abilities_Category_Registry::get_instance();
					if ( $registry->is_registered( $category_slug ) ) {
						continue;
					}

					wp_register_ability_category(
						$category_slug,
						array(
							'label'       => ucfirst( $category_slug ),
							'description' => ucfirst( $category_slug ) . ' category.',
						)
					);
				}
			}
		);

		// Fire the hook to allow category registration.
		do_action( 'abilities_api_categories_init' );

		// Register test abilities with diverse properties.
		$this->register_test_abilities();
	}

	/**
	 * Tear down each test method.
	 */
	public function tear_down(): void {
		// Clean up registered abilities.
		$abilities = $this->registry->get_all_registered();
		foreach ( $abilities as $ability ) {
			$this->registry->unregister( $ability->get_name() );
		}

		// Clean up registered categories.
		$category_registry = WP_Abilities_Category_Registry::get_instance();
		$categories        = array( 'math', 'data-retrieval', 'communication', 'ecommerce' );
		foreach ( $categories as $category_slug ) {
			if ( ! $category_registry->is_registered( $category_slug ) ) {
				continue;
			}

			wp_unregister_ability_category( $category_slug );
		}

		$this->registry = null;

		parent::tear_down();
	}

	/**
	 * Registers test abilities with various properties for filtering tests.
	 */
	private function register_test_abilities(): void {
		// Math abilities - test namespace.
		$this->registry->register(
			'test/add-numbers',
			array(
				'label'               => 'Add Numbers',
				'description'         => 'Adds two numbers together.',
				'category'            => 'math',
				'execute_callback'    => static function ( array $input ): int {
					return $input['a'] + $input['b'];
				},
				'permission_callback' => '__return_true',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'a' => array( 'type' => 'number' ),
						'b' => array( 'type' => 'number' ),
					),
				),
				'output_schema'       => array( 'type' => 'number' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);

		$this->registry->register(
			'test/multiply-numbers',
			array(
				'label'               => 'Multiply Numbers',
				'description'         => 'Multiplies two numbers together.',
				'category'            => 'math',
				'execute_callback'    => static function ( array $input ): int {
					return $input['a'] * $input['b'];
				},
				'permission_callback' => '__return_true',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'a' => array( 'type' => 'number' ),
						'b' => array( 'type' => 'number' ),
					),
				),
				'output_schema'       => array( 'type' => 'number' ),
				'meta'                => array(
					'show_in_rest' => false,
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);

		// Data retrieval abilities - example namespace.
		$this->registry->register(
			'example/get-user-data',
			array(
				'label'               => 'Get User Data',
				'description'         => 'Retrieves user data from the database.',
				'category'            => 'data-retrieval',
				'execute_callback'    => static function () {
					return array( 'user' => 'John Doe' );
				},
				'permission_callback' => '__return_true',
				'input_schema'        => array( 'type' => 'object' ),
				'output_schema'       => array( 'type' => 'object' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'custom_key'   => 'custom_value',
				),
			)
		);

		// Communication abilities - demo namespace.
		$this->registry->register(
			'demo/send-email',
			array(
				'label'               => 'Send Email',
				'description'         => 'Sends an email to a recipient.',
				'category'            => 'communication',
				'execute_callback'    => static function (): bool {
					return true;
				},
				'permission_callback' => '__return_true',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'to'      => array( 'type' => 'string' ),
						'subject' => array( 'type' => 'string' ),
						'message' => array( 'type' => 'string' ),
					),
				),
				'output_schema'       => array( 'type' => 'boolean' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					),
				),
			)
		);

		// E-commerce abilities.
		$this->registry->register(
			'example/process-payment',
			array(
				'label'               => 'Process Payment',
				'description'         => 'Processes a payment transaction.',
				'category'            => 'ecommerce',
				'execute_callback'    => static function () {
					return array( 'success' => true );
				},
				'permission_callback' => '__return_true',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'amount' => array( 'type' => 'number' ),
					),
				),
				'output_schema'       => array( 'type' => 'object' ),
				'meta'                => array(
					'show_in_rest' => false,
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => true,
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Test basic query instantiation.
	 *
	 * @covers WP_Abilities_Query::__construct
	 */
	public function test_query_instantiation() {
		$query = new WP_Abilities_Query();
		$this->assertInstanceOf( WP_Abilities_Query::class, $query );
	}

	/**
	 * Test query with no arguments returns all abilities.
	 *
	 * @covers WP_Abilities_Query::get_abilities
	 */
	public function test_query_no_args_returns_all() {
		$query     = new WP_Abilities_Query();
		$abilities = $query->get_abilities();

		$this->assertCount( 5, $abilities );
	}

	/**
	 * Test wp_get_abilities() without arguments (backward compatibility).
	 *
	 * @covers wp_get_abilities
	 */
	public function test_wp_get_abilities_without_args() {
		$abilities = wp_get_abilities();

		$this->assertCount( 5, $abilities );
		$this->assertArrayHasKey( 'test/add-numbers', $abilities );
	}

	/**
	 * Test wp_get_abilities() with arguments uses query.
	 *
	 * @covers wp_get_abilities
	 */
	public function test_wp_get_abilities_with_args() {
		$abilities = wp_get_abilities( array( 'category' => 'math' ) );

		$this->assertCount( 2, $abilities );
	}

	/**
	 * Test filter by category.
	 *
	 * @covers WP_Abilities_Query::apply_filters
	 */
	public function test_filter_by_category() {
		$query     = new WP_Abilities_Query( array( 'category' => 'math' ) );
		$abilities = $query->get_abilities();

		$this->assertCount( 2, $abilities );
		foreach ( $abilities as $ability ) {
			$this->assertSame( 'math', $ability->get_category() );
		}
	}

	/**
	 * Test filter by multiple categories.
	 *
	 * @covers WP_Abilities_Query::apply_filters
	 */
	public function test_filter_by_multiple_categories() {
		$query     = new WP_Abilities_Query( array( 'category' => array( 'math', 'communication' ) ) );
		$abilities = $query->get_abilities();

		$this->assertCount( 3, $abilities );
		foreach ( $abilities as $ability ) {
			$this->assertContains( $ability->get_category(), array( 'math', 'communication' ) );
		}
	}

	/**
	 * Test filter by namespace.
	 *
	 * @covers WP_Abilities_Query::apply_filters
	 */
	public function test_filter_by_namespace() {
		$query     = new WP_Abilities_Query( array( 'namespace' => 'test' ) );
		$abilities = $query->get_abilities();

		$this->assertCount( 2, $abilities );
		foreach ( $abilities as $ability ) {
			$this->assertStringStartsWith( 'test/', $ability->get_name() );
		}
	}

	/**
	 * Test filter by multiple namespaces.
	 *
	 * @covers WP_Abilities_Query::apply_filters
	 */
	public function test_filter_by_multiple_namespaces() {
		$query     = new WP_Abilities_Query( array( 'namespace' => array( 'test', 'example' ) ) );
		$abilities = $query->get_abilities();

		$this->assertCount( 4, $abilities );
		foreach ( $abilities as $ability ) {
			$name      = $ability->get_name();
			$namespace = substr( $name, 0, strpos( $name, '/' ) );
			$this->assertContains( $namespace, array( 'test', 'example' ) );
		}
	}

	/**
	 * Test filter by search term in name.
	 *
	 * @covers WP_Abilities_Query::apply_filters
	 */
	public function test_filter_by_search_in_name() {
		$query     = new WP_Abilities_Query( array( 'search' => 'email' ) );
		$abilities = $query->get_abilities();

		$this->assertCount( 1, $abilities );
		$ability = reset( $abilities );
		$this->assertSame( 'demo/send-email', $ability->get_name() );
	}

	/**
	 * Test filter by search term in label.
	 *
	 * @covers WP_Abilities_Query::apply_filters
	 */
	public function test_filter_by_search_in_label() {
		$query     = new WP_Abilities_Query( array( 'search' => 'multiply' ) );
		$abilities = $query->get_abilities();

		$this->assertCount( 1, $abilities );
		$ability = reset( $abilities );
		$this->assertSame( 'test/multiply-numbers', $ability->get_name() );
	}

	/**
	 * Test filter by search term in description.
	 *
	 * @covers WP_Abilities_Query::apply_filters
	 */
	public function test_filter_by_search_in_description() {
		$query     = new WP_Abilities_Query( array( 'search' => 'payment' ) );
		$abilities = $query->get_abilities();

		$this->assertCount( 1, $abilities );
		$ability = reset( $abilities );
		$this->assertSame( 'example/process-payment', $ability->get_name() );
	}

	/**
	 * Test filter by show_in_rest using structured array.
	 *
	 * @covers WP_Abilities_Query::apply_filters
	 * @covers WP_Abilities_Query::matches_meta_filters
	 */
	public function test_filter_by_show_in_rest_structured() {
		$query = new WP_Abilities_Query(
			array(
				'meta' => array(
					'show_in_rest' => true,
				),
			)
		);

		$abilities = $query->get_abilities();

		$this->assertCount( 3, $abilities );
		foreach ( $abilities as $ability ) {
			$this->assertTrue( $ability->get_meta_item( 'show_in_rest' ) );
		}
	}


	/**
	 * Test filter by readonly annotation using structured array.
	 *
	 * @covers WP_Abilities_Query::apply_filters
	 * @covers WP_Abilities_Query::matches_meta_filters
	 */
	public function test_filter_by_readonly_structured() {
		$query = new WP_Abilities_Query(
			array(
				'meta' => array(
					'annotations' => array(
						'readonly' => true,
					),
				),
			)
		);

		$abilities = $query->get_abilities();

		$this->assertCount( 3, $abilities );
		foreach ( $abilities as $ability ) {
			$meta = $ability->get_meta();
			$this->assertTrue( $meta['annotations']['readonly'] );
		}
	}


	/**
	 * Test filter by destructive annotation.
	 *
	 * @covers WP_Abilities_Query::apply_filters
	 * @covers WP_Abilities_Query::matches_meta_filters
	 */
	public function test_filter_by_destructive() {
		$query = new WP_Abilities_Query(
			array(
				'meta' => array(
					'annotations' => array(
						'destructive' => true,
					),
				),
			)
		);

		$abilities = $query->get_abilities();

		$this->assertCount( 1, $abilities );
		$ability = reset( $abilities );
		$this->assertSame( 'example/process-payment', $ability->get_name() );
	}

	/**
	 * Test filter by idempotent annotation.
	 *
	 * @covers WP_Abilities_Query::apply_filters
	 * @covers WP_Abilities_Query::matches_meta_filters
	 */
	public function test_filter_by_idempotent() {
		$query = new WP_Abilities_Query(
			array(
				'meta' => array(
					'annotations' => array(
						'idempotent' => true,
					),
				),
			)
		);

		$abilities = $query->get_abilities();

		$this->assertCount( 3, $abilities );
	}

	/**
	 * Test filter by custom meta key.
	 *
	 * @covers WP_Abilities_Query::apply_filters
	 * @covers WP_Abilities_Query::matches_meta_filters
	 */
	public function test_filter_by_custom_meta() {
		$query = new WP_Abilities_Query(
			array(
				'meta' => array(
					'custom_key' => 'custom_value',
				),
			)
		);

		$abilities = $query->get_abilities();

		$this->assertCount( 1, $abilities );
		$ability = reset( $abilities );
		$this->assertSame( 'example/get-user-data', $ability->get_name() );
	}

	/**
	 * Test meta filters with AND logic (all conditions must match).
	 *
	 * @covers WP_Abilities_Query::apply_filters
	 * @covers WP_Abilities_Query::matches_meta
	 */
	public function test_meta_filters_and_logic() {
		$query = new WP_Abilities_Query(
			array(
				'meta' => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly' => true,
					),
				),
			)
		);

		$abilities = $query->get_abilities();

		$this->assertCount( 2, $abilities );
		foreach ( $abilities as $ability ) {
			$this->assertTrue( $ability->get_meta_item( 'show_in_rest' ) );
			$meta = $ability->get_meta();
			$this->assertTrue( $meta['annotations']['readonly'] );
		}
	}

	/**
	 * Test multiple filters combined.
	 *
	 * @covers WP_Abilities_Query::get_abilities
	 */
	public function test_multiple_filters_combined() {
		$query = new WP_Abilities_Query(
			array(
				'category' => 'math',
				'meta'     => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly' => true,
					),
				),
			)
		);

		$abilities = $query->get_abilities();

		$this->assertCount( 1, $abilities );
		$ability = reset( $abilities );
		$this->assertSame( 'test/add-numbers', $ability->get_name() );
	}

	/**
	 * Test order by name ascending (default).
	 *
	 * @covers WP_Abilities_Query::apply_ordering
	 */
	public function test_order_by_name_asc() {
		$query     = new WP_Abilities_Query( array( 'orderby' => 'name' ) );
		$abilities = $query->get_abilities();

		$names = array_map(
			static function ( $ability ) {
				return $ability->get_name();
			},
			$abilities
		);

		$expected = array(
			'demo/send-email',
			'example/get-user-data',
			'example/process-payment',
			'test/add-numbers',
			'test/multiply-numbers',
		);

		$this->assertSame( $expected, $names );
	}

	/**
	 * Test order by name descending.
	 *
	 * @covers WP_Abilities_Query::apply_ordering
	 */
	public function test_order_by_name_desc() {
		$query = new WP_Abilities_Query(
			array(
				'orderby' => 'name',
				'order'   => 'DESC',
			)
		);

		$abilities = $query->get_abilities();

		$names = array_map(
			static function ( $ability ) {
				return $ability->get_name();
			},
			$abilities
		);

		$expected = array(
			'test/multiply-numbers',
			'test/add-numbers',
			'example/process-payment',
			'example/get-user-data',
			'demo/send-email',
		);

		$this->assertSame( $expected, $names );
	}

	/**
	 * Test order by label.
	 *
	 * @covers WP_Abilities_Query::apply_ordering
	 */
	public function test_order_by_label() {
		$query = new WP_Abilities_Query(
			array(
				'orderby' => 'label',
				'order'   => 'ASC',
			)
		);

		$abilities = $query->get_abilities();

		$labels = array_map(
			static function ( $ability ) {
				return $ability->get_label();
			},
			$abilities
		);

		$expected = array(
			'Add Numbers',
			'Get User Data',
			'Multiply Numbers',
			'Process Payment',
			'Send Email',
		);

		$this->assertSame( $expected, $labels );
	}

	/**
	 * Test order by category.
	 *
	 * @covers WP_Abilities_Query::apply_ordering
	 */
	public function test_order_by_category() {
		$query = new WP_Abilities_Query(
			array(
				'orderby' => 'category',
				'order'   => 'ASC',
			)
		);

		$abilities = $query->get_abilities();

		$categories = array_map(
			static function ( $ability ) {
				return $ability->get_category();
			},
			$abilities
		);

		$expected = array(
			'communication',
			'data-retrieval',
			'ecommerce',
			'math',
			'math',
		);

		$this->assertSame( $expected, $categories );
	}

	/**
	 * Test pagination with limit.
	 *
	 * @covers WP_Abilities_Query::apply_pagination
	 */
	public function test_pagination_limit() {
		$query     = new WP_Abilities_Query( array( 'limit' => 2 ) );
		$abilities = $query->get_abilities();

		$this->assertCount( 2, $abilities );
	}

	/**
	 * Test pagination with offset.
	 *
	 * @covers WP_Abilities_Query::apply_pagination
	 */
	public function test_pagination_offset() {
		$query = new WP_Abilities_Query(
			array(
				'orderby' => 'name',
				'offset'  => 2,
			)
		);

		$abilities = $query->get_abilities();

		$this->assertCount( 3, $abilities );
		$first = reset( $abilities );
		$this->assertSame( 'example/process-payment', $first->get_name() );
	}

	/**
	 * Test pagination with both limit and offset.
	 *
	 * @covers WP_Abilities_Query::apply_pagination
	 */
	public function test_pagination_limit_and_offset() {
		$query = new WP_Abilities_Query(
			array(
				'orderby' => 'name',
				'limit'   => 2,
				'offset'  => 1,
			)
		);

		$abilities = $query->get_abilities();

		$this->assertCount( 2, $abilities );

		$names = array_map(
			static function ( $ability ) {
				return $ability->get_name();
			},
			$abilities
		);

		$expected = array(
			'example/get-user-data',
			'example/process-payment',
		);

		$this->assertSame( $expected, $names );
	}

	/**
	 * Test query returns empty array when no matches.
	 *
	 * @covers WP_Abilities_Query::get_abilities
	 */
	public function test_no_matches_returns_empty_array() {
		$query     = new WP_Abilities_Query( array( 'category' => 'nonexistent' ) );
		$abilities = $query->get_abilities();

		$this->assertIsArray( $abilities );
		$this->assertEmpty( $abilities );
	}

	/**
	 * Test query::get() method returns all query vars.
	 *
	 * @covers WP_Abilities_Query::get
	 */
	public function test_get_query_vars() {
		$query      = new WP_Abilities_Query( array( 'category' => 'math' ) );
		$query_vars = $query->get();

		$this->assertIsArray( $query_vars );
		$this->assertSame( 'math', $query_vars['category'] );
	}

	/**
	 * Test query::get() method with specific key.
	 *
	 * @covers WP_Abilities_Query::get
	 */
	public function test_get_specific_query_var() {
		$query    = new WP_Abilities_Query( array( 'category' => 'math' ) );
		$category = $query->get( 'category' );

		$this->assertSame( 'math', $category );
	}

	/**
	 * Test invalid orderby defaults to empty string (no ordering).
	 *
	 * @covers WP_Abilities_Query::parse_query
	 */
	public function test_invalid_orderby_defaults_to_empty() {
		$query   = new WP_Abilities_Query( array( 'orderby' => 'invalid' ) );
		$orderby = $query->get( 'orderby' );

		$this->assertSame( '', $orderby );
	}

	/**
	 * Test invalid order defaults to 'ASC'.
	 *
	 * @covers WP_Abilities_Query::parse_query
	 */
	public function test_invalid_order_defaults_to_asc() {
		$query = new WP_Abilities_Query( array( 'order' => 'invalid' ) );
		$order = $query->get( 'order' );

		$this->assertSame( 'ASC', $order );
	}


	/**
	 * Test query results are cached.
	 *
	 * @covers WP_Abilities_Query::get_abilities
	 */
	public function test_query_results_are_cached() {
		$query = new WP_Abilities_Query( array( 'category' => 'math' ) );

		$first_call  = $query->get_abilities();
		$second_call = $query->get_abilities();

		// Should return same instance (cached).
		$this->assertSame( $first_call, $second_call );
	}

	/**
	 * Test case-insensitive search.
	 *
	 * @covers WP_Abilities_Query::apply_filters
	 */
	public function test_case_insensitive_search() {
		$query     = new WP_Abilities_Query( array( 'search' => 'EMAIL' ) );
		$abilities = $query->get_abilities();

		$this->assertCount( 1, $abilities );
		$ability = reset( $abilities );
		$this->assertSame( 'demo/send-email', $ability->get_name() );
	}
}
