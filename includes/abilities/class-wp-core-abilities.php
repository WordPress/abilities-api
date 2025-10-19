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
final class WP_Core_Abilities {
	/**
	 * Category slugs for core abilities.
	 *
	 * @since 0.3.0
	 */
	public const CATEGORY_SITE = 'site';
	public const CATEGORY_USER = 'user';
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
			self::CATEGORY_SITE,
			array(
				'label'       => __( 'Site' ),
				'description' => __( 'Abilities that retrieve or modify site information and settings.' ),
			)
		);

		// User-related capabilities
		wp_register_ability_category(
			self::CATEGORY_USER,
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
		self::register_get_user_info();
		self::register_get_environment_info();
	}

	/**
	 * Registers the `core/get-site-info` ability.
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
			'core/get-site-info',
			array(
				'label'               => __( 'Get Site Information' ),
				'description'         => __( 'Returns site information configured in WordPress. By default returns all fields, or optionally a filtered subset.' ),
				'category'            => self::CATEGORY_SITE,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'fields' => array(
							'type'        => 'array',
							'items'       => array(
								'type' => 'string',
								'enum' => $fields,
							),
							'description' => __( 'Optional: Limit response to specific fields. If omitted, all fields are returned.' ),
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'                 => 'object',
					'properties'           => array(
						'name'        => array(
							'type'        => 'string',
							'description' => __( 'The site title.' ),
						),
						'description' => array(
							'type'        => 'string',
							'description' => __( 'The site tagline.' ),
						),
						'url'         => array(
							'type'        => 'string',
							'description' => __( 'The site home URL.' ),
						),
						'wpurl'       => array(
							'type'        => 'string',
							'description' => __( 'The WordPress installation URL.' ),
						),
						'admin_email' => array(
							'type'        => 'string',
							'description' => __( 'The site administrator email address.' ),
						),
						'charset'     => array(
							'type'        => 'string',
							'description' => __( 'The site character encoding.' ),
						),
						'language'    => array(
							'type'        => 'string',
							'description' => __( 'The site language locale code.' ),
						),
						'version'     => array(
							'type'        => 'string',
							'description' => __( 'The WordPress version.' ),
						),
					),
					'additionalProperties' => false,
				),
				'execute_callback'    => static function ( $input = array() ): array {
					$all_fields       = array( 'name', 'description', 'url', 'wpurl', 'admin_email', 'charset', 'language', 'version' );
					$requested_fields = ! empty( $input['fields'] ) ? $input['fields'] : $all_fields;

					$result = array();
					foreach ( $requested_fields as $field ) {
						$result[ $field ] = get_bloginfo( $field );
					}

					return $result;
				},
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Registers the `core/get-user-info` ability.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	protected static function register_get_user_info(): void {
		wp_register_ability(
			'core/get-user-info',
			array(
				'label'               => __( 'Get User Information' ),
				'description'         => __( 'Returns basic profile details for the current authenticated user to support personalization, auditing, and access-aware behavior.' ),
				'category'            => self::CATEGORY_USER,
				'output_schema'       => array(
					'type'                 => 'object',
					'required'             => array( 'id', 'display_name', 'user_nicename', 'user_login', 'roles', 'locale' ),
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
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => false,
				),
			)
		);
	}

	/**
	 * Registers the `core/get-environment-info` ability.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	protected static function register_get_environment_info(): void {
		wp_register_ability(
			'core/get-environment-info',
			array(
				'label'               => __( 'Get Environment Info' ),
				'description'         => __( 'Returns core details about the site\'s runtime context for diagnostics and compatibility (environment, PHP runtime, database server info, WordPress version).' ),
				'category'            => self::CATEGORY_SITE,
				'output_schema'       => array(
					'type'                 => 'object',
					'required'             => array( 'environment', 'php_version', 'db_server_info', 'wp_version' ),
					'properties'           => array(
						'environment'    => array(
							'type'        => 'string',
							'description' => __( 'The site\'s runtime environment classification (e.g., production, staging, development).' ),
							'examples'    => array( 'production', 'staging', 'development', 'local' ),
						),
						'php_version'    => array(
							'type'        => 'string',
							'description' => __( 'The PHP runtime version executing WordPress.' ),
						),
						'db_server_info' => array(
							'type'        => 'string',
							'description' => __( 'The database server vendor and version string reported by the driver.' ),
							'examples'    => array( '8.0.34', '10.11.6-MariaDB' ),
						),
						'wp_version'     => array(
							'type'        => 'string',
							'description' => __( 'The WordPress core version running on this site.' ),
						),
					),
					'additionalProperties' => false,
				),
				'execute_callback'    => static function (): array {
					global $wpdb;

					$env          = wp_get_environment_type();
					$php_version  = phpversion();
					$db_server_info  = '';
					if ( isset( $wpdb ) && is_object( $wpdb ) && method_exists( $wpdb, 'db_server_info' ) ) {
						$db_server_info = $wpdb->db_server_info() ?? '';
					}
					$wp_version   = get_bloginfo( 'version' );

					return array(
						'environment'    => $env,
						'php_version'    => $php_version,
						'db_server_info' => $db_server_info,
						'wp_version'     => $wp_version,
					);
				},
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}
}
