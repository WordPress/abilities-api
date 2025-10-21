<?php
/**
 * Abilities API
 *
 * Defines functions for managing abilities in WordPress.
 *
 * @package WordPress
 * @subpackage Abilities_API
 * @since 0.1.0
 */

declare( strict_types = 1 );

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
 * Retrieves a collection of registered abilities.
 *
 * Returns a WP_Abilities_Collection instance that provides a fluent, chainable
 * API for filtering, sorting, and manipulating abilities.
 *
 * @since 0.1.0
 * @since n.e.x.t Returns WP_Abilities_Collection instead of array.
 *
 * @see WP_Abilities_Collection
 *
 * @return \WP_Abilities_Collection Collection of WP_Ability instances.
 *
 * @example
 * // Get all abilities as collection
 * $abilities = wp_get_abilities();
 *
 * @example
 * // Filter by category
 * $math_abilities = wp_get_abilities()->where_category('math');
 *
 * @example
 * // Chain multiple filters
 * $abilities = wp_get_abilities()
 *     ->where_namespace(['WordPress', 'woocommerce'])
 *     ->where_meta(['show_in_rest' => true])
 *     ->search('product')
 *     ->sort_by_desc('name');
 *
 * @example
 * // Convert to array if needed
 * $abilities_array = wp_get_abilities()->to_array();
 */
function wp_get_abilities(): WP_Abilities_Collection {
	$registry = WP_Abilities_Registry::get_instance();
	return new WP_Abilities_Collection( $registry->get_all_registered() );
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
