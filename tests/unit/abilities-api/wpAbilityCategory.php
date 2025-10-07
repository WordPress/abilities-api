<?php declare( strict_types=1 );

/**
 * Tests for the ability category functionality.
 *
 * @covers WP_Ability_Category
 * @covers WP_Abilities_Category_Registry
 * @covers wp_register_ability_category
 * @covers wp_unregister_ability_category
 * @covers wp_get_ability_category
 * @covers wp_get_ability_categories
 *
 * @group abilities-api
 */
class Tests_Abilities_API_WpAbilityCategory extends WP_UnitTestCase {

	/**
	 * Category registry instance.
	 *
	 * @var WP_Abilities_Category_Registry
	 */
	private $registry;

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->registry = WP_Abilities_Category_Registry::get_instance();
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down(): void {
		// Clean up all test categories.
		$categories = $this->registry->get_all_registered();
		foreach ( $categories as $category ) {
			if ( str_starts_with( $category->get_slug(), 'test-' ) ) {
				$this->registry->unregister( $category->get_slug() );
			}
		}

		parent::tear_down();
	}

	/**
	 * Helper to register a category during the hook.
	 */
	private function register_category_during_hook( string $slug, array $args ): ?WP_Ability_Category {
		$result = null;
		add_action(
			'abilities_api_category_registry_init',
			function () use ( $slug, $args, &$result ) {
				$result = wp_register_ability_category( $slug, $args );
			}
		);
		do_action( 'abilities_api_category_registry_init' );
		return $result;
	}

	/**
	 * Test registering a valid category.
	 */
	public function test_register_valid_category(): void {
		$result = $this->register_category_during_hook(
			'test-math',
			array(
				'label'       => 'Math',
				'description' => 'Mathematical operations.',
			)
		);

		$this->assertInstanceOf( WP_Ability_Category::class, $result );
		$this->assertSame( 'test-math', $result->get_slug() );
		$this->assertSame( 'Math', $result->get_label() );
		$this->assertSame( 'Mathematical operations.', $result->get_description() );
	}

	/**
	 * Test registering category with invalid slug format.
	 *
	 * @expectedIncorrectUsage WP_Abilities_Category_Registry::register
	 */
	public function test_register_category_invalid_slug_format(): void {
		// Uppercase characters not allowed.
		$result = $this->register_category_during_hook(
			'Test-Math',
			array(
				'label'       => 'Math',
				'description' => 'Mathematical operations.',
			)
		);

		$this->assertNull( $result );
	}

	/**
	 * Test registering category with invalid slug - underscore.
	 *
	 * @expectedIncorrectUsage WP_Abilities_Category_Registry::register
	 */
	public function test_register_category_invalid_slug_underscore(): void {
		$result = $this->register_category_during_hook(
			'test_math',
			array(
				'label'       => 'Math',
				'description' => 'Mathematical operations.',
			)
		);

		$this->assertNull( $result );
	}

	/**
	 * Test registering category without label.
	 *
	 * @expectedIncorrectUsage WP_Abilities_Category_Registry::register
	 */
	public function test_register_category_missing_label(): void {
		$result = $this->register_category_during_hook(
			'test-math',
			array(
				'description' => 'Mathematical operations.',
			)
		);

		$this->assertNull( $result );
	}

	/**
	 * Test registering category without description.
	 *
	 * @expectedIncorrectUsage WP_Abilities_Category_Registry::register
	 */
	public function test_register_category_missing_description(): void {
		$result = $this->register_category_during_hook(
			'test-math',
			array(
				'label' => 'Math',
			)
		);

		$this->assertNull( $result );
	}

	/**
	 * Test registering category before abilities_api_category_registry_init hook.
	 *
	 * @expectedIncorrectUsage WP_Abilities_Category_Registry::register
	 */
	public function test_register_category_before_init_hook(): void {
		global $wp_actions;

		// Store original count.
		$original_count = isset( $wp_actions['abilities_api_category_registry_init'] ) ? $wp_actions['abilities_api_category_registry_init'] : 0;

		// Reset to simulate hook not fired.
		unset( $wp_actions['abilities_api_category_registry_init'] );

		$result = wp_register_ability_category(
			'test-math',
			array(
				'label'       => 'Math',
				'description' => 'Mathematical operations.',
			)
		);

		// Restore original count.
		if ( $original_count > 0 ) {
			$wp_actions['abilities_api_category_registry_init'] = $original_count;
		}

		$this->assertNull( $result );
	}

	/**
	 * Test registering duplicate category.
	 *
	 * @expectedIncorrectUsage WP_Abilities_Category_Registry::register
	 */
	public function test_register_duplicate_category(): void {
		$result = null;
		add_action(
			'abilities_api_category_registry_init',
			function () use ( &$result ) {
				wp_register_ability_category(
					'test-math',
					array(
						'label'       => 'Math',
						'description' => 'Mathematical operations.',
					)
				);

				$result = wp_register_ability_category(
					'test-math',
					array(
						'label'       => 'Math 2',
						'description' => 'Another math category.',
					)
				);
			}
		);
		do_action( 'abilities_api_category_registry_init' );

		$this->assertNull( $result );
	}

	/**
	 * Test unregistering existing category.
	 */
	public function test_unregister_existing_category(): void {
		$this->register_category_during_hook(
			'test-math',
			array(
				'label'       => 'Math',
				'description' => 'Mathematical operations.',
			)
		);

		$result = wp_unregister_ability_category( 'test-math' );

		$this->assertInstanceOf( WP_Ability_Category::class, $result );
		$this->assertFalse( $this->registry->is_registered( 'test-math' ) );
	}

	/**
	 * Test unregistering non-existent category.
	 *
	 * @expectedIncorrectUsage WP_Abilities_Category_Registry::unregister
	 */
	public function test_unregister_nonexistent_category(): void {
		$result = wp_unregister_ability_category( 'test-nonexistent' );

		$this->assertNull( $result );
	}

	/**
	 * Test retrieving existing category.
	 */
	public function test_get_existing_category(): void {
		$this->register_category_during_hook(
			'test-math',
			array(
				'label'       => 'Math',
				'description' => 'Mathematical operations.',
			)
		);

		$result = wp_get_ability_category( 'test-math' );

		$this->assertInstanceOf( WP_Ability_Category::class, $result );
		$this->assertSame( 'test-math', $result->get_slug() );
	}

	/**
	 * Test retrieving non-existent category.
	 *
	 * @expectedIncorrectUsage WP_Abilities_Category_Registry::get_registered
	 */
	public function test_get_nonexistent_category(): void {
		$result = wp_get_ability_category( 'test-nonexistent' );

		$this->assertNull( $result );
	}

	/**
	 * Test retrieving all registered categories.
	 */
	public function test_get_all_categories(): void {
		add_action(
			'abilities_api_category_registry_init',
			function () {
				wp_register_ability_category(
					'test-math',
					array(
						'label'       => 'Math',
						'description' => 'Mathematical operations.',
					)
				);

				wp_register_ability_category(
					'test-system',
					array(
						'label'       => 'System',
						'description' => 'System operations.',
					)
				);
			}
		);
		do_action( 'abilities_api_category_registry_init' );

		$categories = wp_get_ability_categories();

		$this->assertIsArray( $categories );
		$this->assertCount( 2, $categories );
		$this->assertArrayHasKey( 'test-math', $categories );
		$this->assertArrayHasKey( 'test-system', $categories );
	}

	/**
	 * Test category is_registered method.
	 */
	public function test_category_is_registered(): void {
		$this->assertFalse( $this->registry->is_registered( 'test-math' ) );

		$this->register_category_during_hook(
			'test-math',
			array(
				'label'       => 'Math',
				'description' => 'Mathematical operations.',
			)
		);

		$this->assertTrue( $this->registry->is_registered( 'test-math' ) );
	}

	/**
	 * Test ability can only be registered with existing category.
	 *
	 * @expectedIncorrectUsage WP_Abilities_Registry::register
	 */
	public function test_ability_requires_existing_category(): void {
		do_action( 'abilities_api_init' );

		// Try to register ability with non-existent category.
		$result = wp_register_ability(
			'test/calculator',
			array(
				'label'               => 'Calculator',
				'description'         => 'Performs calculations.',
				'category'            => 'test-nonexistent',
				'execute_callback'    => static function () {
					return 42;
				},
				'permission_callback' => '__return_true',
			)
		);

		$this->assertNull( $result );
	}

	/**
	 * Test ability can be registered with valid category.
	 */
	public function test_ability_with_valid_category(): void {
		add_action(
			'abilities_api_category_registry_init',
			function () {
				wp_register_ability_category(
					'test-math',
					array(
						'label'       => 'Math',
						'description' => 'Mathematical operations.',
					)
				);
			}
		);
		do_action( 'abilities_api_category_registry_init' );
		do_action( 'abilities_api_init' );

		$result = wp_register_ability(
			'test/calculator',
			array(
				'label'               => 'Calculator',
				'description'         => 'Performs calculations.',
				'category'            => 'test-math',
				'execute_callback'    => static function () {
					return 42;
				},
				'permission_callback' => '__return_true',
			)
		);

		$this->assertInstanceOf( WP_Ability::class, $result );
		$this->assertSame( 'test-math', $result->get_category() );

		// Cleanup.
		wp_unregister_ability( 'test/calculator' );
	}

	/**
	 * Test category registry singleton.
	 */
	public function test_category_registry_singleton(): void {
		$instance1 = WP_Abilities_Category_Registry::get_instance();
		$instance2 = WP_Abilities_Category_Registry::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test category with special characters in label and description.
	 */
	public function test_category_with_special_characters(): void {
		$result = $this->register_category_during_hook(
			'test-special',
			array(
				'label'       => 'Math & Science <tag>',
				'description' => 'Operations with "quotes" and \'apostrophes\'.',
			)
		);

		$this->assertInstanceOf( WP_Ability_Category::class, $result );
		$this->assertSame( 'Math & Science <tag>', $result->get_label() );
		$this->assertSame( 'Operations with "quotes" and \'apostrophes\'.', $result->get_description() );
	}

	/**
	 * Test category slug validation with valid formats.
	 */
	public function test_category_slug_valid_formats(): void {
		$valid_slugs = array(
			'test-simple',
			'test-multiple-words',
			'test-with-numbers-123',
			'test-a',
			'test-123',
		);

		add_action(
			'abilities_api_category_registry_init',
			function () use ( $valid_slugs ) {
				foreach ( $valid_slugs as $slug ) {
					$result = wp_register_ability_category(
						$slug,
						array(
							'label'       => 'Test',
							'description' => 'Test description.',
						)
					);

					$this->assertInstanceOf( WP_Ability_Category::class, $result, "Slug '{$slug}' should be valid" );
				}
			}
		);
		do_action( 'abilities_api_category_registry_init' );
	}

	/**
	 * Test category slug validation with invalid formats.
	 *
	 * @expectedIncorrectUsage WP_Abilities_Category_Registry::register
	 */
	public function test_category_slug_invalid_formats(): void {
		$invalid_slugs = array(
			'Test-Uppercase',
			'test_underscore',
			'test.dot',
			'test/slash',
			'test space',
			'-test-start-dash',
			'test-end-dash-',
			'test--double-dash',
		);

		add_action(
			'abilities_api_category_registry_init',
			function () use ( $invalid_slugs ) {
				foreach ( $invalid_slugs as $slug ) {
					$result = wp_register_ability_category(
						$slug,
						array(
							'label'       => 'Test',
							'description' => 'Test description.',
						)
					);

					$this->assertNull( $result, "Slug '{$slug}' should be invalid" );
				}
			}
		);
		do_action( 'abilities_api_category_registry_init' );
	}

	/**
	 * Test filtering abilities by category.
	 */
	public function test_filtering_abilities_by_category(): void {
		add_action(
			'abilities_api_category_registry_init',
			function () {
				// Register categories.
				wp_register_ability_category(
					'test-math',
					array(
						'label'       => 'Math',
						'description' => 'Mathematical operations.',
					)
				);

				wp_register_ability_category(
					'test-system',
					array(
						'label'       => 'System',
						'description' => 'System operations.',
					)
				);
			}
		);
		do_action( 'abilities_api_category_registry_init' );
		do_action( 'abilities_api_init' );

		// Register abilities.
		wp_register_ability(
			'test/add',
			array(
				'label'               => 'Add',
				'description'         => 'Adds numbers.',
				'category'            => 'test-math',
				'execute_callback'    => static function () {
					return 1;
				},
				'permission_callback' => '__return_true',
			)
		);

		wp_register_ability(
			'test/info',
			array(
				'label'               => 'Info',
				'description'         => 'Gets info.',
				'category'            => 'test-system',
				'execute_callback'    => static function () {
					return 2;
				},
				'permission_callback' => '__return_true',
			)
		);

		// Filter by category.
		$math_abilities   = WP_Abilities_Registry::get_instance()->get_abilities_by_category( 'test-math' );
		$system_abilities = WP_Abilities_Registry::get_instance()->get_abilities_by_category( 'test-system' );

		$this->assertCount( 1, $math_abilities );
		$this->assertArrayHasKey( 'test/add', $math_abilities );

		$this->assertCount( 1, $system_abilities );
		$this->assertArrayHasKey( 'test/info', $system_abilities );

		// Cleanup.
		wp_unregister_ability( 'test/add' );
		wp_unregister_ability( 'test/info' );
	}

	/**
	 * Test category registry initialization order.
	 */
	public function test_category_registry_initializes_before_abilities(): void {
		// Reset registries.
		$registry_reflection = new ReflectionClass( WP_Abilities_Registry::class );
		$instance_prop       = $registry_reflection->getProperty( 'instance' );
		$instance_prop->setAccessible( true );
		$instance_prop->setValue( null, null );

		$category_reflection = new ReflectionClass( WP_Abilities_Category_Registry::class );
		$category_prop       = $category_reflection->getProperty( 'instance' );
		$category_prop->setAccessible( true );
		$category_prop->setValue( null, null );

		// Get abilities registry (should initialize categories first).
		$abilities_registry = WP_Abilities_Registry::get_instance();

		// Verify category registry is initialized.
		$this->assertInstanceOf( WP_Abilities_Category_Registry::class, WP_Abilities_Category_Registry::get_instance() );
	}
}
