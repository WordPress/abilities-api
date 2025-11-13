<?php
/**
 * Abilities API
 *
 * Defines WP_Ability_Category class.
 *
 * @package WordPress
 * @subpackage Abilities API
 * @since 6.9.0
 */

declare( strict_types = 1 );

/**
 * Encapsulates the properties and methods related to a specific ability category.
 *
 * @since 6.9.0
 *
 * @see WP_Ability_Categories_Registry
 */
final class WP_Ability_Category implements \JsonSerializable {

	/**
	 * The unique slug for the ability category.
	 *
	 * @since 6.9.0
	 * @var string
	 */
	protected $slug;

	/**
	 * The human-readable ability category label.
	 *
	 * @since 6.9.0
	 * @var string
	 */
	protected $label;

	/**
	 * The detailed ability category description.
	 *
	 * @since 6.9.0
	 * @var string
	 */
	protected $description;

	/**
	 * The optional ability category metadata.
	 *
	 * @since 6.9.0
	 * @var array<string, mixed>
	 */
	protected $meta = array();

	/**
	 * Constructor.
	 *
	 * Do not use this constructor directly. Instead, use the `wp_register_ability_category()` function.
	 *
	 * @access private
	 *
	 * @since 6.9.0
	 *
	 * @see wp_register_ability_category()
	 *
	 * @param string               $slug The unique slug for the ability category.
	 * @param array<string, mixed> $args {
	 *     An associative array of arguments for the ability category.
	 *
	 *     @type string               $label       The human-readable label for the ability category.
	 *     @type string               $description A description of the ability category.
	 *     @type array<string, mixed> $meta        Optional. Additional metadata for the ability category.
	 * }
	 */
	public function __construct( string $slug, array $args ) {
		if ( empty( $slug ) ) {
			throw new InvalidArgumentException(
				__( 'The ability category slug cannot be empty.' )
			);
		}

		$this->slug = $slug;

		$properties = $this->prepare_properties( $args );

		foreach ( $properties as $property_name => $property_value ) {
			if ( ! property_exists( $this, $property_name ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: %s: Property name. */
						__( 'Property "%1$s" is not a valid property for ability category "%2$s". Please check the %3$s class for allowed properties.' ),
						'<code>' . esc_html( $property_name ) . '</code>',
						'<code>' . esc_html( $this->slug ) . '</code>',
						'<code>' . __CLASS__ . '</code>'
					),
					'6.9.0'
				);
				continue;
			}

			$this->$property_name = $property_value;
		}
	}

	/**
	 * Prepares and validates the properties used to instantiate the ability category.
	 *
	 * @since 6.9.0
	 *
	 * @param array<string, mixed> $args $args {
	 *     An associative array of arguments used to instantiate the ability category class.
	 *
	 *     @type string               $label       The human-readable label for the ability category.
	 *     @type string               $description A description of the ability category.
	 *     @type array<string, mixed> $meta        Optional. Additional metadata for the ability category.
	 * }
	 * @return array<string, mixed> $args {
	 *     An associative array with validated and prepared ability category properties.
	 *
	 *     @type string               $label       The human-readable label for the ability category.
	 *     @type string               $description A description of the ability category.
	 *     @type array<string, mixed> $meta        Optional. Additional metadata for the ability category.
	 * }
	 * @throws InvalidArgumentException if an argument is invalid.
	 */
	protected function prepare_properties( array $args ): array {
		// Required args must be present and of the correct type.
		if ( empty( $args['label'] ) || ! is_string( $args['label'] ) ) {
			throw new InvalidArgumentException(
				__( 'The ability category properties must contain a `label` string.' )
			);
		}

		if ( empty( $args['description'] ) || ! is_string( $args['description'] ) ) {
			throw new InvalidArgumentException(
				__( 'The ability category properties must contain a `description` string.' )
			);
		}

		// Optional args only need to be of the correct type if they are present.
		if ( isset( $args['meta'] ) && ! is_array( $args['meta'] ) ) {
			throw new InvalidArgumentException(
				__( 'The ability category properties should provide a valid `meta` array.' )
			);
		}

		return $args;
	}

	/**
	 * Retrieves the slug of the ability category.
	 *
	 * @since 6.9.0
	 *
	 * @return string The ability category slug.
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Retrieves the human-readable label for the ability category.
	 *
	 * @since 6.9.0
	 *
	 * @return string The human-readable ability category label.
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Retrieves the detailed description for the ability category.
	 *
	 * @since 6.9.0
	 *
	 * @return string The detailed description for the ability category.
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Retrieves the metadata for the ability category.
	 *
	 * @since 6.9.0
	 *
	 * @return array<string,mixed> The metadata for the ability category.
	 */
	public function get_meta(): array {
		return $this->meta;
	}

	/**
	 * Converts the category to an array representation.
	 *
	 * Returns a complete array representation of the category including slug, label,
	 * description, and metadata.
	 *
	 * @since n.e.x.t
	 *
	 * @return array<string,mixed> {
	 *     The category as an associative array.
	 *
	 *     @type string $slug        The unique category slug.
	 *     @type string $label       The human-readable label.
	 *     @type string $description The detailed description.
	 *     @type array  $meta        Optional metadata for the category.
	 * }
	 */
	public function to_array(): array {
		$array = array(
			'slug'        => $this->get_slug(),
			'label'       => $this->get_label(),
			'description' => $this->get_description(),
			'meta'        => $this->get_meta(),
		);

		/**
		 * Filters the array representation of a category.
		 *
		 * @since n.e.x.t
		 *
		 * @param array<string,mixed>     $array    The category as an associative array.
		 * @param \WP_Ability_Category    $category The category instance.
		 */
		return apply_filters( "wp_ability_category_{$this->get_slug()}_to_array", $array, $this );
	}

	/**
	 * Serializes the category to a value that can be serialized natively by json_encode().
	 *
	 * Implements the JsonSerializable interface to allow the category to be passed
	 * directly to json_encode() without manually calling to_array().
	 *
	 * @since n.e.x.t
	 *
	 * @return array<string,mixed> The category as an associative array.
	 */
	public function jsonSerialize(): array {
		return $this->to_array();
	}

	/**
	 * Wakeup magic method.
	 *
	 * @since 6.9.0
	 * @throws LogicException If the ability category object is unserialized.
	 *                        This is a security hardening measure to prevent unserialization of the ability category.
	 */
	public function __wakeup(): void {
		throw new LogicException( __CLASS__ . ' should never be unserialized.' );
	}

	/**
	 * Sleep magic method.
	 *
	 * @since 6.9.0
	 * @throws LogicException If the ability category object is serialized.
	 *                        This is a security hardening measure to prevent serialization of the ability category.
	 */
	public function __sleep(): array {
		throw new LogicException( __CLASS__ . ' should never be serialized.' );
	}
}
