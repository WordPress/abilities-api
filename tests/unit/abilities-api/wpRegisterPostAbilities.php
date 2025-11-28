<?php

declare( strict_types=1 );

/**
 * Tests for the post CRUD abilities shipped with the Abilities API.
 *
 * @covers wp_register_post_ability_categories
 * @covers wp_register_post_abilities
 *
 * @group abilities-api
 */
class Tests_Abilities_API_WpRegisterPostAbilities extends WP_UnitTestCase {

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	private static $admin_id;

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	private static $editor_id;

	/**
	 * Subscriber user ID.
	 *
	 * @var int
	 */
	private static $subscriber_id;

	/**
	 * Test post ID.
	 *
	 * @var int
	 */
	private static $test_post_id;

	/**
	 * Set up before the class.
	 *
	 * @since 6.9.0
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		// Create test users.
		self::$admin_id      = self::factory()->user->create( array( 'role' => 'administrator' ) );
		self::$editor_id     = self::factory()->user->create( array( 'role' => 'editor' ) );
		self::$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		// Create a test post.
		self::$test_post_id = self::factory()->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content.',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
			)
		);

		// Ensure post abilities are registered for these tests.
		remove_action( 'wp_abilities_api_categories_init', '_unhook_core_ability_categories_registration', 1 );
		remove_action( 'wp_abilities_api_init', '_unhook_core_abilities_registration', 1 );

		add_action( 'wp_abilities_api_categories_init', 'wp_register_post_ability_categories' );
		add_action( 'wp_abilities_api_init', 'wp_register_post_abilities' );
		do_action( 'wp_abilities_api_categories_init' );
		do_action( 'wp_abilities_api_init' );
	}

	/**
	 * Tear down after the class.
	 *
	 * @since 6.9.0
	 */
	public static function tear_down_after_class(): void {
		// Re-add the unhook functions for subsequent tests.
		add_action( 'wp_abilities_api_categories_init', '_unhook_core_ability_categories_registration', 1 );
		add_action( 'wp_abilities_api_init', '_unhook_core_abilities_registration', 1 );

		// Remove the post abilities and their categories.
		$abilities_to_remove = array(
			'core/get-post',
			'core/list-posts',
			'core/create-post',
			'core/update-post',
			'core/delete-post',
		);
		foreach ( $abilities_to_remove as $ability_name ) {
			wp_unregister_ability( $ability_name );
		}
		wp_unregister_ability_category( 'content' );

		parent::tear_down_after_class();
	}

	/**
	 * Reset current user after each test.
	 */
	public function tear_down(): void {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	// =========================================================================
	// Category Tests
	// =========================================================================

	/**
	 * Tests that the content category is registered.
	 */
	public function test_content_category_is_registered(): void {
		$category = wp_get_ability_category( 'content' );

		$this->assertInstanceOf( WP_Ability_Category::class, $category );
		$this->assertSame( 'Content', $category->get_label() );
	}

	// =========================================================================
	// core/get-post Tests
	// =========================================================================

	/**
	 * Tests that the core/get-post ability is registered with the expected schema.
	 */
	public function test_core_get_post_ability_is_registered(): void {
		$ability = wp_get_ability( 'core/get-post' );

		$this->assertInstanceOf( WP_Ability::class, $ability );
		$this->assertTrue( $ability->get_meta_item( 'show_in_rest', false ) );

		$input_schema  = $ability->get_input_schema();
		$output_schema = $ability->get_output_schema();

		$this->assertSame( 'object', $input_schema['type'] );
		$this->assertArrayHasKey( 'id', $input_schema['properties'] );
		$this->assertContains( 'id', $input_schema['required'] );

		$this->assertArrayHasKey( 'id', $output_schema['properties'] );
		$this->assertArrayHasKey( 'title', $output_schema['properties'] );
		$this->assertArrayHasKey( 'content', $output_schema['properties'] );
	}

	/**
	 * Tests that core/get-post returns post data for an existing post.
	 */
	public function test_core_get_post_returns_post_data(): void {
		wp_set_current_user( self::$admin_id );

		$ability = wp_get_ability( 'core/get-post' );
		$result  = $ability->execute( array( 'id' => self::$test_post_id ) );

		$this->assertIsArray( $result );
		$this->assertSame( self::$test_post_id, $result['id'] );
		$this->assertSame( 'Test Post', $result['title'] );
		$this->assertSame( 'Test content.', $result['content'] );
		$this->assertSame( 'publish', $result['status'] );
		$this->assertSame( 'post', $result['post_type'] );
	}

	/**
	 * Tests that core/get-post returns an error for a non-existent post.
	 * Permission callback runs first and fails because post doesn't exist.
	 */
	public function test_core_get_post_returns_error_for_nonexistent(): void {
		wp_set_current_user( self::$admin_id );

		$ability = wp_get_ability( 'core/get-post' );
		$result  = $ability->execute( array( 'id' => 999999 ) );

		$this->assertWPError( $result );
		// Permission callback fails first because post doesn't exist.
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}

	/**
	 * Tests that core/get-post respects permissions.
	 */
	public function test_core_get_post_respects_permissions(): void {
		// Create a private post.
		$private_post_id = self::factory()->post->create(
			array(
				'post_status' => 'private',
				'post_author' => self::$admin_id,
			)
		);

		// Subscriber should not be able to read private post.
		wp_set_current_user( self::$subscriber_id );

		$ability = wp_get_ability( 'core/get-post' );
		$this->assertFalse( $ability->check_permissions( array( 'id' => $private_post_id ) ) );

		// Admin should be able to read private post.
		wp_set_current_user( self::$admin_id );
		$this->assertTrue( $ability->check_permissions( array( 'id' => $private_post_id ) ) );

		wp_delete_post( $private_post_id, true );
	}

	// =========================================================================
	// core/list-posts Tests
	// =========================================================================

	/**
	 * Tests that the core/list-posts ability is registered.
	 */
	public function test_core_list_posts_ability_is_registered(): void {
		$ability = wp_get_ability( 'core/list-posts' );

		$this->assertInstanceOf( WP_Ability::class, $ability );
		$this->assertTrue( $ability->get_meta_item( 'show_in_rest', false ) );

		$output_schema = $ability->get_output_schema();
		$this->assertArrayHasKey( 'posts', $output_schema['properties'] );
		$this->assertArrayHasKey( 'total', $output_schema['properties'] );
		$this->assertArrayHasKey( 'total_pages', $output_schema['properties'] );
	}

	/**
	 * Tests that core/list-posts returns paginated results.
	 */
	public function test_core_list_posts_returns_paginated_results(): void {
		wp_set_current_user( self::$admin_id );

		// Create additional posts.
		$post_ids = self::factory()->post->create_many( 5 );

		$ability = wp_get_ability( 'core/list-posts' );
		$result  = $ability->execute(
			array(
				'per_page' => 3,
				'page'     => 1,
			)
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'posts', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'total_pages', $result );
		$this->assertCount( 3, $result['posts'] );
		$this->assertGreaterThanOrEqual( 6, $result['total'] ); // At least 6 posts (1 from setup + 5 created).

		// Cleanup.
		foreach ( $post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Tests that core/list-posts filters by post type.
	 */
	public function test_core_list_posts_filters_by_post_type(): void {
		wp_set_current_user( self::$admin_id );

		// Create a page.
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$ability = wp_get_ability( 'core/list-posts' );

		// Query for pages only.
		$result = $ability->execute( array( 'post_type' => 'page' ) );

		$this->assertIsArray( $result );
		foreach ( $result['posts'] as $post ) {
			$this->assertSame( 'page', $post['post_type'] );
		}

		wp_delete_post( $page_id, true );
	}

	// =========================================================================
	// core/create-post Tests
	// =========================================================================

	/**
	 * Tests that the core/create-post ability is registered.
	 */
	public function test_core_create_post_ability_is_registered(): void {
		$ability = wp_get_ability( 'core/create-post' );

		$this->assertInstanceOf( WP_Ability::class, $ability );
		$this->assertTrue( $ability->get_meta_item( 'show_in_rest', false ) );

		$input_schema = $ability->get_input_schema();
		$this->assertContains( 'title', $input_schema['required'] );
	}

	/**
	 * Tests that core/create-post creates a draft by default.
	 */
	public function test_core_create_post_creates_draft(): void {
		wp_set_current_user( self::$editor_id );

		$ability = wp_get_ability( 'core/create-post' );
		$result  = $ability->execute(
			array(
				'title'   => 'New Test Post',
				'content' => 'New test content.',
			)
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'link', $result );

		$post = get_post( $result['id'] );
		$this->assertSame( 'New Test Post', $post->post_title );
		$this->assertSame( 'draft', $post->post_status );

		wp_delete_post( $result['id'], true );
	}

	/**
	 * Tests that core/create-post respects permissions.
	 */
	public function test_core_create_post_respects_permissions(): void {
		// Subscriber should not be able to create posts.
		wp_set_current_user( self::$subscriber_id );

		$ability = wp_get_ability( 'core/create-post' );
		$this->assertFalse( $ability->check_permissions( array( 'title' => 'Test' ) ) );

		// Editor should be able to create posts.
		wp_set_current_user( self::$editor_id );
		$this->assertTrue( $ability->check_permissions( array( 'title' => 'Test' ) ) );
	}

	// =========================================================================
	// core/update-post Tests
	// =========================================================================

	/**
	 * Tests that core/update-post updates fields.
	 */
	public function test_core_update_post_updates_fields(): void {
		wp_set_current_user( self::$admin_id );

		// Create a post to update.
		$post_id = self::factory()->post->create(
			array(
				'post_title'  => 'Original Title',
				'post_status' => 'draft',
			)
		);

		$ability = wp_get_ability( 'core/update-post' );
		$result  = $ability->execute(
			array(
				'id'    => $post_id,
				'title' => 'Updated Title',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( $post_id, $result['id'] );
		$this->assertArrayHasKey( 'modified', $result );

		$updated_post = get_post( $post_id );
		$this->assertSame( 'Updated Title', $updated_post->post_title );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Tests that core/update-post returns error for non-existent post.
	 * Permission callback runs first and fails because post doesn't exist.
	 */
	public function test_core_update_post_returns_error_for_nonexistent(): void {
		wp_set_current_user( self::$admin_id );

		$ability = wp_get_ability( 'core/update-post' );
		$result  = $ability->execute(
			array(
				'id'    => 999999,
				'title' => 'Updated',
			)
		);

		$this->assertWPError( $result );
		// Permission callback fails first because post doesn't exist.
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}

	// =========================================================================
	// core/delete-post Tests
	// =========================================================================

	/**
	 * Tests that core/delete-post moves post to trash by default.
	 */
	public function test_core_delete_post_moves_to_trash(): void {
		wp_set_current_user( self::$admin_id );

		$post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );

		$ability = wp_get_ability( 'core/delete-post' );
		$result  = $ability->execute( array( 'id' => $post_id ) );

		$this->assertIsArray( $result );
		$this->assertSame( $post_id, $result['id'] );
		$this->assertTrue( $result['deleted'] );
		$this->assertSame( 'publish', $result['previous_status'] );

		// Post should be in trash.
		$post = get_post( $post_id );
		$this->assertSame( 'trash', $post->post_status );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Tests that core/delete-post with force permanently deletes.
	 */
	public function test_core_delete_post_force_deletes_permanently(): void {
		wp_set_current_user( self::$admin_id );

		$post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );

		$ability = wp_get_ability( 'core/delete-post' );
		$result  = $ability->execute(
			array(
				'id'    => $post_id,
				'force' => true,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['deleted'] );

		// Post should be completely gone.
		$post = get_post( $post_id );
		$this->assertNull( $post );
	}
}
