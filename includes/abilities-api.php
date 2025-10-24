<?php
declare( strict_types = 1 );

/**
 * Queries registered abilities with flexible filtering options.
 * Supports filtering by namespace, meta, and arbitrary ability properties.
 *
 * @since 0.2.0
 *
 * @param array $args Array of query arguments. Supports property, meta, namespace, and special keys:
 *   - 'namespace' (string|array): Filter by ability namespace(s).
 *   - '?meta_key' (bool): Require presence of meta key.
 *   - '!property' (mixed): Negate match for property.
 *   - Other keys: Match ability property or meta value.
 * @return WP_Ability[] Array of matching abilities.
 */
function wp_query_abilities( array $args = array() ): array {
	$abilities = wp_get_abilities();
	if ( empty( $args ) ) {
		/**
		 * Filter the list of abilities returned by wp_query_abilities when no args are provided.
		 *
		 * @since 0.2.0
		 *
		 * @param WP_Ability[] $abilities Array of all abilities.
		 * @param array        $args      Query arguments (empty).
		 */
		return apply_filters( 'wp_query_abilities', $abilities, $args );
	}

	$filtered = array();
	foreach ( $abilities as $name => $ability ) {
		$pass = true;
		foreach ( $args as $key => $expected ) {
			$negate = false;
			$require_key = false;
			$property = $key;

			if ( is_string( $key ) ) {
				if ( $key[0] === '!' ) {
					$negate = true;
					$property = substr( $key, 1 );
				} elseif ( $key[0] === '?' ) {
					$require_key = true;
					$property = substr( $key, 1 );
				}
			}

			// Namespace filter (special case)
			if ( $property === 'namespace' ) {
				$ability_ns = explode( '/', $ability->get_name() )[0] ?? '';
				$expected_ns = (array) $expected;
				$match = in_array( $ability_ns, $expected_ns, true );
				$pass = $negate ? !$match : $match;
				if ( ! $pass ) break;
				continue;
			}

			// Check meta
			if ( $ability->get_meta() && array_key_exists( $property, $ability->get_meta() ) ) {
				$actual = $ability->get_meta()[ $property ];
			} elseif ( method_exists( $ability, 'get_' . $property ) ) {
				$actual = $ability->{'get_' . $property}();
			} else {
				$actual = null;
			}

			if ( $require_key ) {
				$has_key = $actual !== null;
				$pass = (bool) $has_key === (bool) $expected;
			} else {
				if ( is_array( $expected ) ) {
					$actArr = is_array( $actual ) ? $actual : ( null !== $actual ? [ $actual ] : [] );
					$pass = ! empty( array_intersect( $expected, $actArr ) );
				} else {
					$pass = ( $actual === $expected );
				}
			}
			if ( $negate ) {
				$pass = ! $pass;
			}
			if ( ! $pass ) break;
		}
		if ( $pass ) {
			$filtered[ $name ] = $ability;
		}
	}

	/**
	 * Filter the list of abilities returned by wp_query_abilities.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Ability[] $filtered Array of filtered abilities.
	 * @param array        $args     Query arguments.
	 */
	return apply_filters( 'wp_query_abilities', $filtered, $args );
}



/**
 * Registers a new ability using Abilities API.
 *
 * Note: Do not use before the {@see 'abilities_api_init'} hook.
 *
 * @since 0.1.0
 *
 * @see WP_Abilities_Registry::register()
 *
 * @param string              $name The name of the ability. The name must be a string containing a namespace
 *                                  prefix, i.e. `my-plugin/my-ability`. It can only contain lowercase
 *                                  alphanumeric characters, dashes and the forward slash.
 * @param array<string,mixed> $args An associative array of arguments for the ability. This should include
 *                                  `label`, `description`, `category`, `input_schema`, `output_schema`, `execute_callback`,
 *                                  `permission_callback`, `meta`, and `ability_class`.
 * @return ?\WP_Ability An instance of registered ability on success, null on failure.
 *
 * @phpstan-param array{
 *   label?: string,
 *   description?: string,
 *   category?: string,
 *   execute_callback?: callable( mixed $input= ): (mixed|\WP_Error),
 *   permission_callback?: callable( mixed $input= ): (bool|\WP_Error),
 *   input_schema?: array<string,mixed>,
 *   output_schema?: array<string,mixed>,
 *   meta?: array{
 *     annotations?: array<string,(bool|string)>,
 *     show_in_rest?: bool,
 *     ...<string,mixed>,
 *   },
 *   ability_class?: class-string<\WP_Ability>,
 *   ...<string, mixed>
 * } $args
 */
function wp_register_ability( string $name, array $args ): ?WP_Ability {
	if ( ! did_action( 'abilities_api_init' ) ) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: 1: abilities_api_init, 2: string value of the ability name. */
				esc_html__( 'Abilities must be registered on the %1$s action. The ability %2$s was not registered.' ),
				'<code>abilities_api_init</code>',
				'<code>' . esc_html( $name ) . '</code>'
			),
			'0.1.0'
		);
		return null;
	}

	return WP_Abilities_Registry::get_instance()->register( $name, $args );
}

/**
 * Unregisters an ability using Abilities API.
 *
 * @since 0.1.0
 *
 * @see WP_Abilities_Registry::unregister()
 *
 * @param string $name The name of the registered ability, with its namespace.
 * @return ?\WP_Ability The unregistered ability instance on success, null on failure.
 */
function wp_unregister_ability( string $name ): ?WP_Ability {
	return WP_Abilities_Registry::get_instance()->unregister( $name );
}

/**
 * Retrieves a registered ability using Abilities API.
 *
 * @since 0.1.0
 *
 * @see WP_Abilities_Registry::get_registered()
 *
 * @param string $name The name of the registered ability, with its namespace.
 * @return ?\WP_Ability The registered ability instance, or null if it is not registered.
 */
function wp_get_ability( string $name ): ?WP_Ability {
	return WP_Abilities_Registry::get_instance()->get_registered( $name );
}

/**
 * Retrieves all registered abilities using Abilities API.
 *
 * @since 0.1.0
 *
 * @see WP_Abilities_Registry::get_all_registered()
 *
 * @return \WP_Ability[] The array of registered abilities.
 */
function wp_get_abilities(): array {
	return WP_Abilities_Registry::get_instance()->get_all_registered();
}

/**
 * Registers a new ability category.
 *
 * @since 0.3.0
 *
 * @see WP_Abilities_Category_Registry::register()
 *
 * @param string              $slug The unique slug for the category. Must contain only lowercase
 *                                  alphanumeric characters and dashes.
 * @param array<string,mixed> $args An associative array of arguments for the category. This should
 *                                  include `label`, `description`, and optionally `meta`.
 * @return ?\WP_Ability_Category The registered category instance on success, null on failure.
 *
 * @phpstan-param array{
 *   label: string,
 *   description: string,
 *   meta?: array<string,mixed>,
 *   ...<string, mixed>
 * } $args
 */
function wp_register_ability_category( string $slug, array $args ): ?WP_Ability_Category {
	return WP_Abilities_Category_Registry::get_instance()->register( $slug, $args );
}

/**
 * Unregisters an ability category.
 *
 * @since 0.3.0
 *
 * @see WP_Abilities_Category_Registry::unregister()
 *
 * @param string $slug The slug of the registered category.
 * @return ?\WP_Ability_Category The unregistered category instance on success, null on failure.
 */
function wp_unregister_ability_category( string $slug ): ?WP_Ability_Category {
	return WP_Abilities_Category_Registry::get_instance()->unregister( $slug );
}

/**
 * Retrieves a registered ability category.
 *
 * @since 0.3.0
 *
 * @see WP_Abilities_Category_Registry::get_registered()
 *
 * @param string $slug The slug of the registered category.
 * @return ?\WP_Ability_Category The registered category instance, or null if it is not registered.
 */
function wp_get_ability_category( string $slug ): ?WP_Ability_Category {
	return WP_Abilities_Category_Registry::get_instance()->get_registered( $slug );
}

/**
 * Retrieves all registered ability categories.
 *
 * @since 0.3.0
 *
 * @see WP_Abilities_Category_Registry::get_all_registered()
 *
 * @return \WP_Ability_Category[] The array of registered categories.
 */
function wp_get_ability_categories(): array {
	return WP_Abilities_Category_Registry::get_instance()->get_all_registered();
}
