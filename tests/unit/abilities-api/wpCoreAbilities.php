<?php declare( strict_types=1 );

/**
 * Tests for the core abilities shipped with the Abilities API.
 *
 * @group abilities-api
 */
class Tests_Abilities_API_WpCoreAbilities extends WP_UnitTestCase {

	/**
	 * Sets up the test.
	 */
	public function set_up(): void {
		parent::set_up();

		// Unregister core abilities if they were already registered to avoid duplicate registration warnings.
		$registry = WP_Abilities_Registry::get_instance();
		if ( $registry->is_registered( 'core/get-bloginfo' ) ) {
			$registry->unregister( 'core/get-bloginfo' );
		}
		if ( $registry->is_registered( 'core/get-current-user-info' ) ) {
			$registry->unregister( 'core/get-current-user-info' );
		}
		if ( $registry->is_registered( 'core/get-environment-type' ) ) {
			$registry->unregister( 'core/get-environment-type' );
		}

		// Fire the init action if it hasn't been fired yet.
		if ( ! did_action( 'abilities_api_init' ) ) {
			do_action( 'abilities_api_init' );
		}

		// Register core abilities for testing.
		WP_Core_Abilities::register();
	}

	/**
	 * Tests that the `core/get-bloginfo` ability is registered with the expected schema.
	 */
	public function test_core_get_bloginfo_ability_is_registered(): void {
		$ability = wp_get_ability( 'core/get-bloginfo' );

		$this->assertInstanceOf( WP_Ability::class, $ability );
		$this->assertTrue( $ability->get_meta_item( 'show_in_rest', false ) );

		$input_schema = $ability->get_input_schema();
		$this->assertSame( array( 'field' ), $input_schema['required'] );
		$this->assertContains( 'name', $input_schema['properties']['field']['enum'] );
	}

	/**
	 * Tests executing the `core/get-bloginfo` ability.
	 */
	public function test_core_get_bloginfo_executes(): void {
		$ability = wp_get_ability( 'core/get-bloginfo' );

		$result = $ability->execute(
			array(
				'field' => 'name',
			)
		);

		$this->assertSame(
			array(
				'field' => 'name',
				'value' => get_bloginfo( 'name' ),
			),
			$result
		);
	}

	/**
	 * Tests that executing the current user info ability requires authentication.
	 */
	public function test_core_get_current_user_info_requires_authentication(): void {
		$ability = wp_get_ability( 'core/get-current-user-info' );

		$this->assertFalse( $ability->check_permissions() );

		$result = $ability->execute();
		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}

	/**
	 * Tests executing the current user info ability as an authenticated user.
	 */
	public function test_core_get_current_user_info_returns_user_data(): void {
		$user_id = self::factory()->user->create(
			array(
				'role'   => 'subscriber',
				'locale' => 'fr_FR',
			)
		);

		wp_set_current_user( $user_id );

		$ability = wp_get_ability( 'core/get-current-user-info' );

		$this->assertTrue( $ability->check_permissions() );

		$result = $ability->execute();
		$this->assertSame( $user_id, $result['id'] );
		$this->assertSame( 'fr_FR', $result['locale'] );
		$this->assertSame( 'subscriber', $result['roles'][0] );
		$this->assertSame( get_userdata( $user_id )->display_name, $result['display_name'] );

		wp_set_current_user( 0 );
	}

	/**
	 * Tests executing the environment type ability.
	 */
	public function test_core_get_environment_type_executes(): void {
		$ability      = wp_get_ability( 'core/get-environment-type' );
		$environment  = wp_get_environment_type();
		$ability_data = $ability->execute();

		$this->assertSame(
			array(
				'environment' => $environment,
			),
			$ability_data
		);
	}

}
