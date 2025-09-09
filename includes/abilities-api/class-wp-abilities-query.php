<?php
/**
 * Abilities API
 *
 * Defines WP_Abilities_Query class.
 *
 * @package WordPress
 * @subpackage Abilities_API
 * @since 0.2.0
 */

declare( strict_types = 1 );

/**
 * Queries and filters registered abilities.
 *
 * @since 0.2.0
 */
class WP_Abilities_Query {

	/**
	 * Query parameters.
	 *
	 * @since 0.2.0
	 * @var array<string,mixed>
	 */
	private $query_vars = array();

	/**
	 * Constructor.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string,mixed> $args Optional. Query arguments.
	 */
	public function __construct( array $args = array() ) {
		$this->query_vars = $this->parse_query_args( $args );
	}

	/**
	 * Parses and sanitizes query arguments.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string,mixed> $args Query arguments.
	 * @return array<string,mixed> Parsed query arguments.
	 */
	private function parse_query_args( array $args ): array {
		$defaults = array(
			'namespace'         => '',
			'search'            => '',
			'meta_query'        => array(),
			'has_input_schema'  => null,
			'has_output_schema' => null,
		);

		$args = wp_parse_args( $args, $defaults );

		// Sanitize string arguments.
		$args['namespace'] = sanitize_text_field( $args['namespace'] );
		$args['search']    = sanitize_text_field( $args['search'] );

		// Ensure meta_query is an array.
		if ( ! is_array( $args['meta_query'] ) ) {
			$args['meta_query'] = array();
		}

		// Sanitize boolean arguments.
		if ( null !== $args['has_input_schema'] ) {
			$args['has_input_schema'] = (bool) $args['has_input_schema'];
		}
		if ( null !== $args['has_output_schema'] ) {
			$args['has_output_schema'] = (bool) $args['has_output_schema'];
		}

		return $args;
	}

	/**
	 * Retrieves filtered abilities.
	 *
	 * @since 0.2.0
	 *
	 * @return \WP_Ability[] Filtered array of abilities.
	 */
	public function get_abilities(): array {
		$all_abilities = WP_Abilities_Registry::get_instance()->get_all_registered();
		$filtered      = array();

		foreach ( $all_abilities as $name => $ability ) {
			if ( ! $this->matches_filters( $ability ) ) {
				continue;
			}

			$filtered[ $name ] = $ability;
		}

		return $filtered;
	}

	/**
	 * Checks if an ability matches the current filters.
	 *
	 * @since 0.2.0
	 *
	 * @param \WP_Ability $ability Ability to check.
	 * @return bool True if the ability matches all filters, false otherwise.
	 */
	private function matches_filters( WP_Ability $ability ): bool {
		// Filter by namespace.
		if ( ! empty( $this->query_vars['namespace'] ) ) {
			$namespace = $this->query_vars['namespace'];
			// Remove trailing slash or wildcard for consistency.
			$namespace = rtrim( $namespace, '/*' );

			if ( 0 !== strpos( $ability->get_name(), $namespace . '/' ) ) {
				return false;
			}
		}

		// Filter by search term in label or description.
		if ( ! empty( $this->query_vars['search'] ) ) {
			$search_term = strtolower( $this->query_vars['search'] );
			$label       = strtolower( $ability->get_label() );
			$description = strtolower( $ability->get_description() );

			if ( false === strpos( $label, $search_term ) && false === strpos( $description, $search_term ) ) {
				return false;
			}
		}

		// Filter by meta fields.
		if ( ! empty( $this->query_vars['meta_query'] ) ) {
			if ( ! $this->matches_meta_query( $ability, $this->query_vars['meta_query'] ) ) {
				return false;
			}
		}

		// Filter by input schema presence.
		if ( null !== $this->query_vars['has_input_schema'] ) {
			$has_input = ! empty( $ability->get_input_schema() );
			if ( $has_input !== $this->query_vars['has_input_schema'] ) {
				return false;
			}
		}

		// Filter by output schema presence.
		if ( null !== $this->query_vars['has_output_schema'] ) {
			$has_output = ! empty( $ability->get_output_schema() );
			if ( $has_output !== $this->query_vars['has_output_schema'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if an ability matches meta query conditions.
	 *
	 * @since 0.2.0
	 *
	 * @param \WP_Ability        $ability    Ability to check.
	 * @param array<string,mixed> $meta_query Meta query conditions.
	 * @return bool True if the ability matches meta conditions, false otherwise.
	 */
	private function matches_meta_query( WP_Ability $ability, array $meta_query ): bool {
		$meta = $ability->get_meta();

		foreach ( $meta_query as $query ) {
			if ( ! is_array( $query ) ) {
				continue;
			}

			$key     = $query['key'] ?? '';
			$value   = $query['value'] ?? '';
			$compare = $query['compare'] ?? '=';

			if ( empty( $key ) ) {
				continue;
			}

			$meta_value = $meta[ $key ] ?? null;

			switch ( $compare ) {
				case '=':
				case '==':
					if ( $meta_value !== $value ) {
						return false;
					}
					break;
				case '!=':
					if ( $meta_value === $value ) {
						return false;
					}
					break;
				case 'EXISTS':
					if ( ! isset( $meta[ $key ] ) ) {
						return false;
					}
					break;
				case 'NOT EXISTS':
					if ( isset( $meta[ $key ] ) ) {
						return false;
					}
					break;
				case 'IN':
					if ( ! is_array( $value ) || ! in_array( $meta_value, $value, true ) ) {
						return false;
					}
					break;
				case 'NOT IN':
					if ( is_array( $value ) && in_array( $meta_value, $value, true ) ) {
						return false;
					}
					break;
				default:
					// For unsupported compare operators, skip the condition.
					continue 2;
			}
		}

		return true;
	}
}
