<?php
/**
 * Abilities API
 *
 * Defines WP_Ability class.
 *
 * @package WordPress
 * @subpackage Abilities API
 * @since 0.1.0
 */

declare( strict_types = 1 );

/**
 * Encapsulates the properties and methods related to a specific ability in the registry.
 *
 * @since 0.1.0
 *
 * @see WP_Abilities_Registry
 */
class WP_Ability implements \JsonSerializable {

	/**
	 * The default value for the `show_in_rest` meta.
	 *
	 * @since n.e.x.t
	 * @var bool
	 */
	protected const DEFAULT_SHOW_IN_REST = false;

	/**
	 * The default ability annotations.
	 * They are not guaranteed to provide a faithful description of ability behavior.
	 *
	 * @since n.e.x.t
	 * @var array<string,(bool|string)>
	 */
	protected static $default_annotations = array(
		// Instructions on how to use the ability.
		'instructions' => '',
		// If true, the ability does not modify its environment.
		'readonly'     => false,
		/*
		 * If true, the ability may perform destructive updates to its environment.
		 * If false, the ability performs only additive updates.
		 */
		'destructive'  => true,
		/*
		 * If true, calling the ability repeatedly with the same arguments will have no additional effect
		 * on its environment.
		 */
		'idempotent'   => false,
	);

	/**
	 * The name of the ability, with its namespace.
	 * Example: `my-plugin/my-ability`.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $name;

	/**
	 * The human-readable ability label.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $label;

	/**
	 * The detailed ability description.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $description;

	/**
	 * The optional ability input schema.
	 *
	 * @since 0.1.0
	 * @var array<string,mixed>
	 */
	protected $input_schema = array();

	/**
	 * The optional ability output schema.
	 *
	 * @since 0.1.0
	 * @var array<string,mixed>
	 */
	protected $output_schema = array();

	/**
	 * The ability execute callback.
	 *
	 * @since 0.1.0
	 * @var callable( mixed $input= ): (mixed|\WP_Error)
	 */
	protected $execute_callback;

	/**
	 * The optional ability permission callback.
	 *
	 * @since 0.1.0
	 * @var callable( mixed $input= ): (bool|\WP_Error)
	 */
	protected $permission_callback;

	/**
	 * The optional ability metadata.
	 *
	 * @since 0.1.0
	 * @var array<string,mixed>
	 */
	protected $meta;

	/**
	 * Constructor.
	 *
	 * Do not use this constructor directly. Instead, use the `wp_register_ability()` function.
	 *
	 * @access private
	 *
	 * @since 0.1.0
	 *
	 * @see wp_register_ability()
	 *
	 * @param string              $name The name of the ability, with its namespace.
	 * @param array<string,mixed> $args An associative array of arguments for the ability. This should include
	 *                                  `label`, `description`, `input_schema`, `output_schema`, `execute_callback`,
	 *                                  `permission_callback`, and `meta`.
	 */
	public function __construct( string $name, array $args ) {
		$this->name = $name;

		$properties = $this->prepare_properties( $args );

		foreach ( $properties as $property_name => $property_value ) {
			if ( ! property_exists( $this, $property_name ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: %s: Property name. */
						esc_html__( 'Property "%1$s" is not a valid property for ability "%2$s". Please check the %3$s class for allowed properties.' ),
						'<code>' . esc_html( $property_name ) . '</code>',
						'<code>' . esc_html( $this->name ) . '</code>',
						'<code>' . esc_html( self::class ) . '</code>'
					),
					'0.1.0'
				);
				continue;
			}

			$this->$property_name = $property_value;
		}
	}

	/**
	 * Prepares and validates the properties used to instantiate the ability.
	 *
	 * Errors are thrown as exceptions instead of \WP_Errors to allow for simpler handling and overloading. They are then
	 * caught and converted to a WP_Error when by WP_Abilities_Registry::register().
	 *
	 * @since 0.2.0
	 *
	 * @see WP_Abilities_Registry::register()
	 *
	 * @param array<string,mixed> $args An associative array of arguments used to instantiate the class.
	 * @return array<string,mixed> The validated and prepared properties.
	 * @throws \InvalidArgumentException if an argument is invalid.
	 *
	 * @phpstan-return array{
	 *   label: string,
	 *   description: string,
	 *   execute_callback: callable( mixed $input= ): (mixed|\WP_Error),
	 *   permission_callback: callable( mixed $input= ): (bool|\WP_Error),
	 *   input_schema?: array<string,mixed>,
	 *   output_schema?: array<string,mixed>,
	 *   meta?: array{
	 *     annotations?: array<string,(bool|string)>,
	 *     show_in_rest?: bool,
	 *     ...<string, mixed>
	 *   },
	 *   ...<string, mixed>,
	 * } $args
	 */
	protected function prepare_properties( array $args ): array {
		// Required args must be present and of the correct type.
		if ( empty( $args['label'] ) || ! is_string( $args['label'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'The ability properties must contain a `label` string.' )
			);
		}

		if ( empty( $args['description'] ) || ! is_string( $args['description'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'The ability properties must contain a `description` string.' )
			);
		}

		if ( empty( $args['execute_callback'] ) || ! is_callable( $args['execute_callback'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'The ability properties must contain a valid `execute_callback` function.' )
			);
		}

		if ( empty( $args['permission_callback'] ) || ! is_callable( $args['permission_callback'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'The ability properties must provide a valid `permission_callback` function.' )
			);
		}

		// Optional args only need to be of the correct type if they are present.
		if ( isset( $args['input_schema'] ) && ! is_array( $args['input_schema'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'The ability properties should provide a valid `input_schema` definition.' )
			);
		}

		if ( isset( $args['output_schema'] ) && ! is_array( $args['output_schema'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'The ability properties should provide a valid `output_schema` definition.' )
			);
		}

		if ( isset( $args['meta'] ) && ! is_array( $args['meta'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'The ability properties should provide a valid `meta` array.' )
			);
		}

		if ( isset( $args['meta']['annotations'] ) && ! is_array( $args['meta']['annotations'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'The ability meta should provide a valid `annotations` array.' )
			);
		}

		if ( isset( $args['meta']['show_in_rest'] ) && ! is_bool( $args['meta']['show_in_rest'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'The ability meta should provide a valid `show_in_rest` boolean.' )
			);
		}

		// Set defaults for optional meta.
		$args['meta']                = wp_parse_args(
			$args['meta'] ?? array(),
			array(
				'annotations'  => static::$default_annotations,
				'show_in_rest' => self::DEFAULT_SHOW_IN_REST,
			)
		);
		$args['meta']['annotations'] = wp_parse_args(
			$args['meta']['annotations'],
			static::$default_annotations
		);

		return $args;
	}

	/**
	 * Retrieves the name of the ability, with its namespace.
	 * Example: `my-plugin/my-ability`.
	 *
	 * @since 0.1.0
	 *
	 * @return string The ability name, with its namespace.
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Retrieves the human-readable label for the ability.
	 *
	 * @since 0.1.0
	 *
	 * @return string The human-readable ability label.
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Retrieves the detailed description for the ability.
	 *
	 * @since 0.1.0
	 *
	 * @return string The detailed description for the ability.
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Retrieves the input schema for the ability.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string,mixed> The input schema for the ability.
	 */
	public function get_input_schema(): array {
		return $this->input_schema;
	}

	/**
	 * Retrieves the output schema for the ability.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string,mixed> The output schema for the ability.
	 */
	public function get_output_schema(): array {
		return $this->output_schema;
	}

	/**
	 * Retrieves the metadata for the ability.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string,mixed> The metadata for the ability.
	 */
	public function get_meta(): array {
		return $this->meta;
	}

	/**
	 * Retrieves a specific metadata item for the ability.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $key           The metadata key to retrieve.
	 * @param mixed  $default_value Optional. The default value to return if the metadata item is not found. Default `null`.
	 * @return mixed The value of the metadata item, or the default value if not found.
	 */
	public function get_meta_item( string $key, $default_value = null ) {
		return array_key_exists( $key, $this->meta ) ? $this->meta[ $key ] : $default_value;
	}

	/**
	 * Converts the ability to an array representation.
	 *
	 * Returns a complete array representation of the ability including name, label,
	 * description, schemas, and metadata. Callbacks are excluded as they are not serializable.
	 *
	 * @since n.e.x.t
	 *
	 * @return array<string,mixed> {
	 *     The ability as an associative array.
	 *
	 *     @type string $name          The ability name with namespace.
	 *     @type string $label         The human-readable label.
	 *     @type string $description   The detailed description.
	 *     @type array  $input_schema  The input validation schema.
	 *     @type array  $output_schema The output validation schema.
	 *     @type array  $meta {
	 *         Metadata for the ability.
	 *
	 *         @type array $annotations {
	 *             Behavior annotations.
	 *
	 *             @type string $instructions Usage instructions.
	 *             @type bool   $readonly     Whether the ability is read-only.
	 *             @type bool   $destructive  Whether the ability is destructive.
	 *             @type bool   $idempotent   Whether the ability is idempotent.
	 *         }
	 *         @type bool $show_in_rest Whether the ability is exposed in REST API.
	 *     }
	 * }
	 */
	public function to_array(): array {
		$array = array(
			'name'          => $this->get_name(),
			'label'         => $this->get_label(),
			'description'   => $this->get_description(),
			'input_schema'  => $this->get_input_schema(),
			'output_schema' => $this->get_output_schema(),
			'meta'          => $this->get_meta(),
		);

		/**
		 * Filters the array representation of an ability.
		 *
		 * @since n.e.x.t
		 *
		 * @param array<string,mixed> $array   The ability as an associative array.
		 * @param \WP_Ability          $ability The ability instance.
		 */
		return apply_filters( "wp_ability_{$this->get_name()}_to_array", $array, $this );
	}

	/**
	 * Serializes the ability to a value that can be serialized natively by json_encode().
	 *
	 * Implements the JsonSerializable interface to allow the ability to be passed
	 * directly to json_encode() without manually calling to_array().
	 *
	 * @since n.e.x.t
	 *
	 * @return array<string,mixed> The ability as an associative array.
	 */
	public function jsonSerialize(): array {
		return $this->to_array();
	}

	/**
	 * Converts the ability to a JSON Schema representation.
	 *
	 * Generates a JSON Schema Draft 7 compliant schema describing the ability's
	 * structure, including input/output schemas and metadata.
	 *
	 * @since n.e.x.t
	 *
	 * @return array<string,mixed> A JSON Schema representation of the ability.
	 */
	public function to_json_schema(): array {
		$input_schema  = $this->get_input_schema();
		$output_schema = $this->get_output_schema();

		$schema = array(
			'$schema'     => 'http://json-schema.org/draft-07/schema#',
			'type'        => 'object',
			'title'       => $this->get_label(),
			'description' => $this->get_description(),
			'properties'  => array(
				'name' => array(
					'type'  => 'string',
					'const' => $this->get_name(),
				),
				'meta' => array(
					'type'       => 'object',
					'properties' => array(
						'annotations'  => array(
							'type'       => 'object',
							'properties' => array(
								'instructions' => array( 'type' => 'string' ),
								'readonly'     => array( 'type' => 'boolean' ),
								'destructive'  => array( 'type' => 'boolean' ),
								'idempotent'   => array( 'type' => 'boolean' ),
							),
						),
						'show_in_rest' => array(
							'type' => 'boolean',
						),
					),
				),
			),
			'required'    => array( 'name', 'meta' ),
		);

		if ( ! empty( $input_schema ) ) {
			$schema['properties']['input_schema'] = $input_schema;
			$schema['required'][]                 = 'input_schema';
		}

		if ( ! empty( $output_schema ) ) {
			$schema['properties']['output_schema'] = $output_schema;
			$schema['required'][]                  = 'output_schema';
		}

		/**
		 * Filters the JSON Schema representation of an ability.
		 *
		 * @since n.e.x.t
		 *
		 * @param array<string,mixed> $schema  The JSON Schema representation.
		 * @param \WP_Ability          $ability The ability instance.
		 */
		return apply_filters( "wp_ability_{$this->get_name()}_to_json_schema", $schema, $this );
	}

	/**
	 * Validates input data against the input schema.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $input Optional. The input data to validate. Default `null`.
	 * @return true|\WP_Error Returns true if valid or the WP_Error object if validation fails.
	 */
	protected function validate_input( $input = null ) {
		$input_schema = $this->get_input_schema();
		if ( empty( $input_schema ) ) {
			if ( null === $input ) {
				return true;
			}

			return new \WP_Error(
				'ability_missing_input_schema',
				sprintf(
					/* translators: %s ability name. */
					__( 'Ability "%s" does not define an input schema required to validate the provided input.' ),
					$this->name
				)
			);
		}

		$valid_input = rest_validate_value_from_schema( $input, $input_schema, 'input' );
		if ( is_wp_error( $valid_input ) ) {
			return new \WP_Error(
				'ability_invalid_input',
				sprintf(
					/* translators: %1$s ability name, %2$s error message. */
					__( 'Ability "%1$s" has invalid input. Reason: %2$s' ),
					$this->name,
					$valid_input->get_error_message()
				)
			);
		}

		return true;
	}

	/**
	 * Invokes a callable, ensuring the input is passed through only if the input schema is defined.
	 *
	 * @since n.e.x.t
	 *
	 * @param callable $callback The callable to invoke.
	 * @param mixed    $input    Optional. The input data for the ability. Default `null`.
	 * @return mixed The result of the callable execution.
	 */
	protected function invoke_callback( callable $callback, $input = null ) {
		$args = array();
		if ( ! empty( $this->get_input_schema() ) ) {
			$args[] = $input;
		}

		return $callback( ...$args );
	}

	/**
	 * Checks whether the ability has the necessary permissions.
	 *
	 * The input is validated against the input schema before it is passed to to permission callback.
	 *
	 * @since 0.2.0
	 *
	 * @param mixed $input Optional. The input data for permission checking. Default `null`.
	 * @return bool|\WP_Error Whether the ability has the necessary permission.
	 */
	public function check_permissions( $input = null ) {
		$is_valid = $this->validate_input( $input );
		if ( is_wp_error( $is_valid ) ) {
			return $is_valid;
		}

		return $this->invoke_callback( $this->permission_callback, $input );
	}

	/**
	 * Checks whether the ability has the necessary permissions (deprecated).
	 *
	 * The input is validated against the input schema before it is passed to to permission callback.
	 *
	 * @deprecated 0.2.0 Use check_permissions() instead.
	 * @see WP_Ability::check_permissions()
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $input Optional. The input data for permission checking. Default `null`.
	 * @return bool|\WP_Error Whether the ability has the necessary permission.
	 */
	public function has_permission( $input = null ) {
		_deprecated_function( __METHOD__, '0.2.0', 'WP_Ability::check_permissions()' );
		return $this->check_permissions( $input );
	}

	/**
	 * Executes the ability callback.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $input Optional. The input data for the ability. Default `null`.
	 * @return mixed|\WP_Error The result of the ability execution, or WP_Error on failure.
	 */
	protected function do_execute( $input = null ) {
		if ( ! is_callable( $this->execute_callback ) ) {
			return new \WP_Error(
				'ability_invalid_execute_callback',
				/* translators: %s ability name. */
				sprintf( __( 'Ability "%s" does not have a valid execute callback.' ), $this->name )
			);
		}

		return $this->invoke_callback( $this->execute_callback, $input );
	}

	/**
	 * Validates output data against the output schema.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $output The output data to validate.
	 * @return true|\WP_Error Returns true if valid, or a WP_Error object if validation fails.
	 */
	protected function validate_output( $output ) {
		$output_schema = $this->get_output_schema();
		if ( empty( $output_schema ) ) {
			return true;
		}

		$valid_output = rest_validate_value_from_schema( $output, $output_schema, 'output' );
		if ( is_wp_error( $valid_output ) ) {
			return new \WP_Error(
				'ability_invalid_output',
				sprintf(
					/* translators: %1$s ability name, %2$s error message. */
					__( 'Ability "%1$s" has invalid output. Reason: %2$s' ),
					$this->name,
					$valid_output->get_error_message()
				)
			);
		}

		return true;
	}

	/**
	 * Executes the ability after input validation and running a permission check.
	 * Before returning the return value, it also validates the output.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $input Optional. The input data for the ability. Default `null`.
	 * @return mixed|\WP_Error The result of the ability execution, or WP_Error on failure.
	 */
	public function execute( $input = null ) {
		$has_permissions = $this->check_permissions( $input );
		if ( true !== $has_permissions ) {
			if ( is_wp_error( $has_permissions ) ) {
				if ( 'ability_invalid_input' === $has_permissions->get_error_code() ) {
					return $has_permissions;
				}
				// Don't leak the permission check error to someone without the correct perms.
				_doing_it_wrong(
					__METHOD__,
					esc_html( $has_permissions->get_error_message() ),
					'0.1.0'
				);
			}

			return new \WP_Error(
				'ability_invalid_permissions',
				/* translators: %s ability name. */
				sprintf( __( 'Ability "%s" does not have necessary permission.' ), $this->name )
			);
		}

		/**
		 * Fires before an ability gets executed.
		 *
		 * @since 0.2.0
		 *
		 * @param string $ability_name The name of the ability.
		 * @param mixed  $input        The input data for the ability.
		 */
		do_action( 'before_execute_ability', $this->name, $input );

		$result = $this->do_execute( $input );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$is_valid = $this->validate_output( $result );
		if ( is_wp_error( $is_valid ) ) {
			return $is_valid;
		}

		/**
		 * Fires immediately after an ability finished executing.
		 *
		 * @since 0.2.0
		 *
		 * @param string $ability_name The name of the ability.
		 * @param mixed  $input        The input data for the ability.
		 * @param mixed  $result       The result of the ability execution.
		 */
		do_action( 'after_execute_ability', $this->name, $input, $result );

		return $result;
	}

	/**
	 * Wakeup magic method.
	 *
	 * @since 0.1.0
	 */
	public function __wakeup(): void {
		throw new \LogicException( self::class . ' should never be unserialized.' );
	}
}
