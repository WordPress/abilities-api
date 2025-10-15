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
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Core class intended for WordPress core.
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
				'label'               => __( 'Get Blog Information' ),
				'description'         => __( 'Returns a single site information field from get_bloginfo().' ),
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'field' => array(
							'type'        => 'string',
							'enum'        => $fields,
							'description' => __( 'The site information field to retrieve.' ),
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
							'description' => __( 'The requested site information field.' ),
						),
						'value' => array(
							'type'        => 'string',
							'description' => __( 'The value returned by get_bloginfo().' ),
						),
					),
					'additionalProperties' => false,
				),
				'execute_callback'    => static function ( $input = array() ): array {
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
						'instructions' => __( 'Retrieves a single site property by passing an allowed field to get_bloginfo().' ),
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
				'label'               => __( 'Get Current User Information' ),
				'description'         => __( 'Returns basic information about the current authenticated user.' ),
				'output_schema'       => array(
					'type'                 => 'object',
					'required'             => array( 'id', 'display_name', 'locale' ),
					'properties'           => array(
						'id'            => array(
							'type'        => 'integer',
							'description' => __( 'The user ID.' ),
						),
						'display_name'  => array(
							'type'        => 'string',
							'description' => __( 'The display name of the user.' ),
						),
						'user_nicename' => array(
							'type'        => 'string',
							'description' => __( 'The URL-friendly name for the user.' ),
						),
						'user_login'    => array(
							'type'        => 'string',
							'description' => __( 'The login username for the user.' ),
						),
						'roles'         => array(
							'type'        => 'array',
							'description' => __( 'The roles assigned to the user.' ),
							'items'       => array(
								'type' => 'string',
							),
						),
						'locale'        => array(
							'type'        => 'string',
							'description' => __( 'The locale string for the user, such as en_US.' ),
						),
					),
					'additionalProperties' => false,
				),
				'execute_callback'    => static function (): array {
					$current_user = wp_get_current_user();

					return array(
						'id'            => $current_user->ID,
						'display_name'  => $current_user->display_name,
						'user_nicename' => $current_user->user_nicename,
						'user_login'    => $current_user->user_login,
						'roles'         => $current_user->roles,
						'locale'        => get_user_locale( $current_user ),
					);
				},
				'permission_callback' => static function (): bool {
					return is_user_logged_in();
				},
				'meta'                => array(
					'annotations'  => array(
						'instructions' => __( 'Retrieves information about the current authenticated user.' ),
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
				'label'               => __( 'Get Environment Type' ),
				'description'         => __( 'Returns the current WordPress environment type (e.g. production or staging).' ),
				'output_schema'       => array(
					'type'                 => 'object',
					'required'             => array( 'environment' ),
					'properties'           => array(
						'environment' => array(
							'type'        => 'string',
							'description' => __( 'The environment type returned by wp_get_environment_type().' ),
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
						'instructions' => __( 'Retrieves the current WordPress environment type.' ),
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
