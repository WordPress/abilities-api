<?php
/**
 * Core Abilities registration.
 *
 * @package WordPress
 * @subpackage Abilities_API
 * @since 0.3.0
 */

declare( strict_types = 1 );

/**
 * Registers the default core abilities that ship with the Abilities API.
 *
 * @since 0.3.0
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Core class intended for WordPress core.
class WP_Core_Abilities {
	/**
	 * Registers the core abilities categories.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function register_category(): void {
		// Site-related capabilities
		wp_register_ability_category(
			'site',
			array(
				'label'       => __( 'Site' ),
				'description' => __( 'Abilities that retrieve or modify site information and settings.' ),
			)
		);

		// User-related capabilities
		wp_register_ability_category(
			'user',
			array(
				'label'       => __( 'User' ),
				'description' => __( 'Abilities that retrieve or modify user information and settings.' ),
			)
		);
	}

	/**
	 * Registers the default core abilities.
	 *
 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function register(): void {
		self::register_get_site_info();
		self::register_get_current_user_info();
		self::register_get_environment_info();
	}

	/**
	 * Registers the `wp/get-site-info` ability.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	protected static function register_get_site_info(): void {
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
			'wp/get-site-info',
			array(
				'label'               => __( 'Get Site Information' ),
				'description'         => __( 'Returns a single site information field from get_bloginfo().' ),
				'category'            => 'site',
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
				'permission_callback' => static function ( $input = array() ): bool {
					// Site information can expose sensitive details; require admin capability.
					return current_user_can( 'manage_options' );
				},
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
	 * Registers the `wp/get-current-user-info` ability.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	protected static function register_get_current_user_info(): void {
		wp_register_ability(
			'wp/get-current-user-info',
			array(
				'label'               => __( 'Get Current User Information' ),
				'description'         => __( 'Returns basic information about the current authenticated user.' ),
				'category'            => 'user',
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
	 * Registers the `wp/get-environment-info` ability.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	protected static function register_get_environment_info(): void {
		wp_register_ability(
			'wp/get-environment-info',
			array(
				'label'               => __( 'Get Environment Info' ),
				'description'         => __( 'Returns basic information about the WordPress runtime environment.' ),
				'category'            => 'site',
				'output_schema'       => array(
					'type'                 => 'object',
					'required'             => array( 'environment', 'php_version', 'mysql_version', 'wp_version', 'database_type' ),
					'properties'           => array(
						'environment'   => array(
							'type'        => 'string',
							'description' => __( 'The environment type returned by wp_get_environment_type().' ),
						),
						'php_version'   => array(
							'type'        => 'string',
							'description' => __( 'The PHP version.' ),
						),
						'mysql_version' => array(
							'type'        => 'string',
							'description' => __( 'The database server version (MySQL or MariaDB).' ),
						),
						'wp_version'    => array(
							'type'        => 'string',
							'description' => __( 'The WordPress version.' ),
						),
						'database_type' => array(
							'type'        => 'string',
							'description' => __( 'The database server type (e.g., mysql or mariadb).' ),
						),
					),
					'additionalProperties' => false,
				),
				'execute_callback'    => static function (): array {
					global $wpdb;

					$env          = wp_get_environment_type();
					$php_version  = phpversion();
					$db_version   = '';
					if ( isset( $wpdb ) && is_object( $wpdb ) && method_exists( $wpdb, 'db_version' ) ) {
						$db_version = (string) $wpdb->db_version();
					}
					$wp_version   = (string) get_bloginfo( 'version' );

					$type = 'mysql';
					if ( stripos( $db_version, 'mariadb' ) !== false ) {
						$type = 'mariadb';
					}

					return array(
						'environment'   => (string) $env,
						'php_version'   => (string) $php_version,
						'mysql_version' => (string) $db_version,
						'wp_version'    => (string) $wp_version,
						'database_type' => (string) $type,
					);
				},
				'permission_callback' => static function (): bool {
					// Environment information is restricted to administrators.
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'annotations'  => array(
						'instructions' => __( 'Retrieves environment information such as environment type, PHP, database, and WordPress versions.' ),
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
