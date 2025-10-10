<?php
/**
 * Core Abilities registration.
 *
 * @package WordPress
 * @subpackage Abilities_API
 * @since n.e.x.t
 */

declare( strict_types = 1 );

/**
 * Registers the default core abilities that ship with the Abilities API.
 *
 * @since n.e.x.t
 */
class WP_Core_Abilities {
	/**
	 * Registers the default core abilities.
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	public static function register(): void {
		self::register_get_bloginfo();
		self::register_get_current_user_info();
		self::register_get_environment_type();
		self::register_find_abilities();
	}

	/**
	 * Registers the `core/get-bloginfo` ability.
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	protected static function register_get_bloginfo(): void {
		$fields = array(
			'name',
			'description',
			'url',
			'wpurl',
			'admin_email',
			'charset',
			'language',
			'version',
		);

		wp_register_ability(
			'core/get-bloginfo',
			array(
				'label'               => __( 'Get Blog Information', 'abilities-api' ),
				'description'         => __( 'Returns a single site information field from get_bloginfo().', 'abilities-api' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'field' => array(
							'type'        => 'string',
							'enum'        => $fields,
							'description' => __( 'The site information field to retrieve.', 'abilities-api' ),
						),
					),
					'required'             => array( 'field' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'                 => 'object',
					'required'             => array( 'field', 'value' ),
					'properties'           => array(
						'field' => array(
							'type'        => 'string',
							'description' => __( 'The requested site information field.', 'abilities-api' ),
						),
						'value' => array(
							'type'        => 'string',
							'description' => __( 'The value returned by get_bloginfo().', 'abilities-api' ),
						),
					),
					'additionalProperties' => false,
				),
				'execute_callback'    => static function ( array $input ): array {
					$field = $input['field'];
					$value = get_bloginfo( $field );

					return array(
						'field' => $field,
						'value' => (string) $value,
					);
				},
				'permission_callback' => '__return_true',
				'meta'                => array(
					'annotations'  => array(
						'instructions' => __( 'Retrieves a single site property by passing an allowed field to get_bloginfo().', 'abilities-api' ),
						'readonly'     => true,
						'destructive'  => false,
						'idempotent'   => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Registers the `core/get-current-user-info` ability.
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	protected static function register_get_current_user_info(): void {
		wp_register_ability(
			'core/get-current-user-info',
			array(
				'label'               => __( 'Get Current User Information', 'abilities-api' ),
				'description'         => __( 'Returns basic information about the current authenticated user.', 'abilities-api' ),
				'output_schema'       => array(
					'type'                 => 'object',
					'required'             => array( 'id', 'display_name', 'locale' ),
					'properties'           => array(
						'id'           => array(
							'type'        => 'integer',
							'description' => __( 'The user ID.', 'abilities-api' ),
						),
						'display_name' => array(
							'type'        => 'string',
							'description' => __( 'The display name of the user.', 'abilities-api' ),
						),
						'user_nicename' => array(
							'type'        => 'string',
							'description' => __( 'The URL-friendly name for the user.', 'abilities-api' ),
						),
						'user_login'   => array(
							'type'        => 'string',
							'description' => __( 'The login username for the user.', 'abilities-api' ),
						),
						'roles'        => array(
							'type'        => 'array',
							'description' => __( 'The roles assigned to the user.', 'abilities-api' ),
							'items'       => array(
								'type' => 'string',
							),
						),
						'locale' => array(
							'type'        => 'string',
							'description' => __( 'The locale string for the user, such as en_US.', 'abilities-api' ),
						),
					),
					'additionalProperties' => false,
				),
				'execute_callback'    => static function (): array {
					$current_user = wp_get_current_user();

					return array(
						'id'           => $current_user->ID,
						'display_name' => $current_user->display_name,
						'user_nicename' => $current_user->user_nicename,
						'user_login'   => $current_user->user_login,
						'roles'        => $current_user->roles,
						'locale'       => get_user_locale( $current_user ),
					);
				},
				'permission_callback' => static function (): bool {
					return is_user_logged_in();
				},
				'meta'                => array(
					'annotations'  => array(
						'instructions' => __( 'Retrieves information about the current authenticated user.', 'abilities-api' ),
						'readonly'     => true,
						'destructive'  => false,
						'idempotent'   => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Registers the `core/get-environment-type` ability.
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	protected static function register_get_environment_type(): void {
		wp_register_ability(
			'core/get-environment-type',
			array(
				'label'               => __( 'Get Environment Type', 'abilities-api' ),
				'description'         => __( 'Returns the current WordPress environment type (e.g. production or staging).', 'abilities-api' ),
				'output_schema'       => array(
					'type'                 => 'object',
					'required'             => array( 'environment' ),
					'properties'           => array(
						'environment' => array(
							'type'        => 'string',
							'description' => __( 'The environment type returned by wp_get_environment_type().', 'abilities-api' ),
						),
					),
					'additionalProperties' => false,
				),
				'execute_callback'    => static function (): array {
					return array(
						'environment' => wp_get_environment_type(),
					);
				},
				'permission_callback' => '__return_true',
				'meta'                => array(
					'annotations'  => array(
						'instructions' => __( 'Retrieves the current WordPress environment type.', 'abilities-api' ),
						'readonly'     => true,
						'destructive'  => false,
						'idempotent'   => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Registers the `core/find-abilities` ability.
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	protected static function register_find_abilities(): void {
		wp_register_ability(
			'core/find-abilities',
			array(
				'label'               => __( 'Find Abilities', 'abilities-api' ),
				'description'         => __( 'Returns a list of abilities that are exposed through the registry.', 'abilities-api' ),
				'input_schema'        => array(
					'type'                 => array( 'object', 'null' ),
					'properties'           => array(
						'namespace' => array(
							'type'        => 'string',
							'description' => __( 'Optional namespace prefix to filter abilities (e.g. "core/").', 'abilities-api' ),
						),
						'show_in_rest' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether to limit results to abilities exposed in REST. Defaults to true.', 'abilities-api' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'abilities' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'        => array(
										'type'        => 'string',
										'description' => __( 'The ability name.', 'abilities-api' ),
									),
									'label'       => array(
										'type'        => 'string',
										'description' => __( 'The human readable label.', 'abilities-api' ),
									),
									'description' => array(
										'type'        => 'string',
										'description' => __( 'The detailed description.', 'abilities-api' ),
									),
									'meta'        => array(
										'type'        => 'object',
										'description' => __( 'Additional metadata associated with the ability.', 'abilities-api' ),
									),
									'annotations' => array(
										'type'        => 'object',
										'description' => __( 'Annotations describing ability behavior.', 'abilities-api' ),
									),
									'show_in_rest' => array(
										'type'        => 'boolean',
										'description' => __( 'Whether the ability is exposed in REST.', 'abilities-api' ),
									),
								),
								'required'   => array( 'name', 'label', 'description', 'meta', 'annotations', 'show_in_rest' ),
							),
						),
					),
					'additionalProperties' => false,
				),
				'execute_callback'    => static function ( array $input = array() ): array {
					$namespace     = $input['namespace'] ?? null;
					$filter_rest   = array_key_exists( 'show_in_rest', $input ) ? (bool) $input['show_in_rest'] : true;
					$abilities     = wp_get_abilities();
					$filtered_list = array();

					foreach ( $abilities as $ability ) {
						$show_in_rest = $ability->get_meta_item( 'show_in_rest', false );
						if ( $filter_rest && ! $show_in_rest ) {
							continue;
						}

						if ( $namespace && ! str_starts_with( $ability->get_name(), $namespace ) ) {
							continue;
						}

						$filtered_list[] = array(
							'name'         => $ability->get_name(),
							'label'        => $ability->get_label(),
							'description'  => $ability->get_description(),
							'meta'         => $ability->get_meta(),
							'annotations'  => $ability->get_meta_item( 'annotations', array() ),
							'show_in_rest' => $show_in_rest,
						);
					}

					/**
					 * Filters the abilities returned by the `core/find-abilities` ability.
					 *
					 * @since n.e.x.t
					 *
					 * @param array<string,mixed>[] $abilities An array of abilities exposed by the ability.
					 * @param array<string,mixed>   $input      The input arguments passed to the ability.
					 */
					$filtered_list = apply_filters( 'abilities_api_core_find_abilities_results', $filtered_list, $input );

					return array(
						'abilities' => array_values( $filtered_list ),
					);
				},
				'permission_callback' => static function (): bool {
					return current_user_can( 'read' );
				},
				'meta'                => array(
					'annotations'  => array(
						'instructions' => __( 'Lists abilities from the registry. Optional namespace filter is supported.', 'abilities-api' ),
						'readonly'     => true,
						'destructive'  => false,
						'idempotent'   => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}
}
