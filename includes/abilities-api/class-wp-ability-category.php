<?php
/**
 * Abilities API
 *
 * Defines WP_Ability_Category class.
 *
 * @package WordPress
 * @subpackage Abilities API
 * @since 0.3.0
 */

declare( strict_types = 1 );

/**
 * Encapsulates the properties and methods related to a specific ability category.
 *
 * @since 0.3.0
 *
 * @see WP_Abilities_Category_Registry
 */
class WP_Ability_Category {

	/**
	 * The unique slug for the category.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	protected $slug;

	/**
	 * The human-readable category label.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	protected $label;

	/**
	 * The detailed category description.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	protected $description;

	/**
	 * Constructor.
	 *
	 * Do not use this constructor directly. Instead, use the `wp_register_ability_category()` function.
	 *
	 * @access private
	 *
	 * @since 0.3.0
	 *
	 * @see wp_register_ability_category()
	 *
	 * @param string              $slug The unique slug for the category.
	 * @param array<string,mixed> $args An associative array of arguments for the category.
	 */
	public function __construct( string $slug, array $args ) {
		if ( empty( $slug ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'The category slug cannot be empty.' )
			);
		}

		$this->slug = sanitize_title( $slug );

		$properties = $this->prepare_properties( $args );

		$this->label       = $properties['label'];
		$this->description = $properties['description'];
	}

	/**
	 * Prepares and validates the properties used to instantiate the category.
	 *
	 * @since 0.3.0
	 *
	 * @param array<string,mixed> $args An associative array of arguments used to instantiate the class.
	 * @return array<string,mixed> The validated and prepared properties.
	 * @throws \InvalidArgumentException if an argument is invalid.
	 *
	 * @phpstan-return array{
	 *   label: string,
	 *   description: string,
	 *   ...<string, mixed>,
	 * }
	 */
	protected function prepare_properties( array $args ): array {
		// Required args must be present and of the correct type.
		if ( empty( $args['label'] ) || ! is_string( $args['label'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'The category properties must contain a `label` string.' )
			);
		}

		if ( empty( $args['description'] ) || ! is_string( $args['description'] ) ) {
			throw new \InvalidArgumentException(
				esc_html__( 'The category properties must contain a `description` string.' )
			);
		}

		return array(
			'label'       => $args['label'],
			'description' => $args['description'],
		);
	}

	/**
	 * Retrieves the slug of the category.
	 *
	 * @since 0.3.0
	 *
	 * @return string The category slug.
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Retrieves the human-readable label for the category.
	 *
	 * @since 0.3.0
	 *
	 * @return string The human-readable category label.
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Retrieves the detailed description for the category.
	 *
	 * @since 0.3.0
	 *
	 * @return string The detailed description for the category.
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Wakeup magic method.
	 *
	 * @since 0.3.0
	 */
	public function __wakeup(): void {
		throw new \LogicException( self::class . ' should never be unserialized.' );
	}
}
