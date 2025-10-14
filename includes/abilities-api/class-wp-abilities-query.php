<?php
/**
 * Abilities API
 *
 * Defines WP_Abilities_Query class.
 *
 * @package WordPress
 * @subpackage Abilities API
 * @since n.e.x.t
 */

declare( strict_types=1 );

/**
 * Query class for filtering registered abilities.
 *
 * Provides a WordPress-standard query interface for filtering abilities by various criteria,
 * including category, namespace, search terms, meta properties, and annotations.
 *
 * @since n.e.x.t
 */
class WP_Abilities_Query {

	/**
	 * Constant representing no limit on results.
	 *
	 * @since n.e.x.t
	 * @var int
	 */
	private static $no_limit = -1;

	/**
	 * Valid orderby fields.
	 *
	 * @since n.e.x.t
	 * @var array<string>
	 */
	private static $valid_orderby_fields = array( 'name', 'label', 'category' );

	/**
	 * Valid order directions.
	 *
	 * @since n.e.x.t
	 * @var array<string>
	 */
	private static $valid_order_directions = array( 'ASC', 'DESC' );

	/**
	 * Query arguments after parsing.
	 *
	 * @since n.e.x.t
	 * @var array<string,mixed>
	 */
	protected $query_vars = array();

	/**
	 * The filtered abilities result.
	 *
	 * @since n.e.x.t
	 * @var \WP_Ability[]|null
	 */
	protected $abilities = null;

	/**
	 * Constructor.
	 *
	 * @since n.e.x.t
	 *
	 * @param array<string,mixed> $args Optional. Query arguments. Default empty array.
	 *
	 * @phpstan-param array{
	 *   category?: string|array<string>,
	 *   namespace?: string|array<string>,
	 *   search?: string,
	 *   meta?: array<string,mixed>,
	 *   orderby?: string,
	 *   order?: string,
	 *   limit?: int,
	 *   offset?: int,
	 *   ...<string, mixed>
	 * } $args
	 */
	public function __construct( array $args = array() ) {
		$this->parse_query( $args );
	}

	/**
	 * Parses and validates query arguments.
	 *
	 * @since n.e.x.t
	 *
	 * @param array<string,mixed> $args Query arguments.
	 *
	 */
	protected function parse_query( array $args ): void {
		$defaults = array(
			'category'  => '',
			'namespace' => '',
			'search'    => '',
			'meta'      => array(),
			'orderby'   => '',
			'order'     => 'ASC',
			'limit'     => self::$no_limit,
			'offset'    => 0,
		);

		$this->query_vars = wp_parse_args( $args, $defaults );

		$this->validate_meta_arg();
		$this->validate_orderby();
		$this->validate_order();
		$this->validate_pagination_args();
	}

	/**
	 * Validates the meta query argument.
	 *
	 * @since n.e.x.t
	 *
	 */
	protected function validate_meta_arg(): void {
		if ( is_array( $this->query_vars['meta'] ) ) {
			return;
		}
		$this->query_vars['meta'] = array();
	}

	/**
	 * Validates the orderby query argument.
	 *
	 * @since n.e.x.t
	 *
	 */
	protected function validate_orderby(): void {
		if ( empty( $this->query_vars['orderby'] ) ) {
			return;
		}

		if ( in_array( $this->query_vars['orderby'], self::$valid_orderby_fields, true ) ) {
			return;
		}
		$this->query_vars['orderby'] = '';
	}

	/**
	 * Validates the order query argument.
	 *
	 * @since n.e.x.t
	 *
	 */
	protected function validate_order(): void {
		$this->query_vars['order'] = strtoupper( $this->query_vars['order'] );
		if ( in_array( $this->query_vars['order'], self::$valid_order_directions, true ) ) {
			return;
		}
		$this->query_vars['order'] = 'ASC';
	}

	/**
	 * Validates the pagination query arguments (limit and offset).
	 *
	 * @since n.e.x.t
	 *
	 */
	protected function validate_pagination_args(): void {
		$this->query_vars['limit']  = (int) $this->query_vars['limit'];
		$this->query_vars['offset'] = (int) $this->query_vars['offset'];
	}

	/**
	 * Retrieves the filtered abilities based on query arguments.
	 *
	 * @return \WP_Ability[] Array of filtered abilities.
	 * @since n.e.x.t
	 *
	 */
	public function get_abilities(): array {
		if ( null !== $this->abilities ) {
			return $this->abilities;
		}

		$abilities = WP_Abilities_Registry::get_instance()->get_all_registered();

		$abilities = $this->apply_filters( $abilities );

		if ( empty( $abilities ) ) {
			$this->abilities = array();

			return $this->abilities;
		}

		$abilities = $this->apply_ordering( $abilities );

		$abilities = $this->apply_pagination( $abilities );

		$this->abilities = $abilities;

		return $this->abilities;
	}

	/**
	 * Applies all filters in a single pass for optimal performance.
	 *
	 * @param \WP_Ability[] $abilities Abilities to filter.
	 *
	 * @return \WP_Ability[] Filtered abilities.
	 * @since n.e.x.t
	 *
	 */
	protected function apply_filters( array $abilities ): array {
		$has_category  = ! empty( $this->query_vars['category'] );
		$has_namespace = ! empty( $this->query_vars['namespace'] );
		$has_search    = ! empty( $this->query_vars['search'] );
		$has_meta      = ! empty( $this->query_vars['meta'] ) && is_array( $this->query_vars['meta'] );

		if ( ! $has_category && ! $has_namespace && ! $has_search && ! $has_meta ) {
			return $abilities;
		}

		$filtered = array();

		foreach ( $abilities as $name => $ability ) {
			if ( $has_category && ! $this->filter_by_category( $ability ) ) {
				continue;
			}

			if ( $has_namespace && ! $this->filter_by_namespace( $ability ) ) {
				continue;
			}

			if ( $has_meta && ! $this->filter_by_meta( $ability ) ) {
				continue;
			}

			if ( $has_search && ! $this->filter_by_search( $ability ) ) {
				continue;
			}

			$filtered[ $name ] = $ability;
		}

		return $filtered;
	}

	/**
	 * Checks if an ability matches the category filter.
	 *
	 * @param \WP_Ability $ability The ability to check.
	 *
	 * @return bool True if ability matches category filter, false otherwise.
	 * @since n.e.x.t
	 *
	 */
	protected function filter_by_category( WP_Ability $ability ): bool {
		return $this->matches_filter( $ability->get_category(), $this->query_vars['category'] );
	}

	/**
	 * Checks if an ability matches the namespace filter.
	 *
	 * @param \WP_Ability $ability The ability to check.
	 *
	 * @return bool True if ability matches namespace filter, false otherwise.
	 * @since n.e.x.t
	 *
	 */
	protected function filter_by_namespace( WP_Ability $ability ): bool {
		$ability_namespace = self::get_ability_namespace( $ability->get_name() );

		if ( null === $ability_namespace ) {
			return false;
		}

		return $this->matches_filter( $ability_namespace, $this->query_vars['namespace'] );
	}

	/**
	 * Checks if an ability matches the meta filters.
	 *
	 * @param \WP_Ability $ability The ability to check.
	 *
	 * @return bool True if ability matches meta filters, false otherwise.
	 * @since n.e.x.t
	 *
	 */
	protected function filter_by_meta( WP_Ability $ability ): bool {
		$filters = $this->query_vars['meta'];

		if ( empty( $filters ) ) {
			return true;
		}

		$ability_meta = $ability->get_meta();

		[ $flat_filters, $nested_filters ] = $this->separate_meta_filters( $filters );

		return $this->check_flat_meta_filters( $ability_meta, $flat_filters )
			&& $this->check_nested_meta_filters( $ability_meta, $nested_filters );
	}

	/**
	 * Checks if an ability matches the search term.
	 *
	 * @param \WP_Ability $ability The ability to check.
	 *
	 * @return bool True if ability matches search term, false otherwise.
	 * @since n.e.x.t
	 *
	 */
	protected function filter_by_search( WP_Ability $ability ): bool {
		$search = $this->query_vars['search'];

		return stripos( $ability->get_name(), $search ) !== false
			|| stripos( $ability->get_label(), $search ) !== false
			|| stripos( $ability->get_description(), $search ) !== false;
	}

	/**
	 * Checks if a value matches the filter (either equals or in array).
	 *
	 * @param string               $value  The value to check.
	 * @param string|array<string> $filter The filter to match against.
	 *
	 * @return bool True if value matches the filter, false otherwise.
	 * @since n.e.x.t
	 *
	 */
	protected function matches_filter( string $value, $filter ): bool {
		if ( is_array( $filter ) ) {
			return in_array( $value, $filter, true );
		}

		return $value === $filter;
	}

	/**
	 * Extracts the namespace from an ability name.
	 *
	 * @param string $ability_name The ability name (e.g., 'namespace/ability-name').
	 *
	 * @return string|null The namespace part, or null if no slash found.
	 * @since n.e.x.t
	 *
	 */
	protected static function get_ability_namespace( string $ability_name ): ?string {
		$slash_pos = strpos( $ability_name, '/' );

		if ( false === $slash_pos ) {
			return null;
		}

		return substr( $ability_name, 0, $slash_pos );
	}

	/**
	 * Separates meta filters into flat and nested arrays.
	 *
	 * @param array<string,mixed> $filters The meta filters to separate.
	 *
	 * @return array{0: array<string,mixed>, 1: array<string,mixed>} Array containing flat filters and nested filters.
	 * @since n.e.x.t
	 *
	 */
	protected function separate_meta_filters( array $filters ): array {
		$flat_filters   = array();
		$nested_filters = array();

		foreach ( $filters as $key => $value ) {
			if ( is_array( $value ) ) {
				$nested_filters[ $key ] = $value;
			} else {
				$flat_filters[ $key ] = $value;
			}
		}

		return array( $flat_filters, $nested_filters );
	}

	/**
	 * Checks if ability meta matches flat filters.
	 *
	 * @param array<string,mixed> $ability_meta The ability's meta data.
	 * @param array<string,mixed> $flat_filters The flat filters to match.
	 *
	 * @return bool True if meta matches all flat filters, false otherwise.
	 * @since n.e.x.t
	 *
	 */
	protected function check_flat_meta_filters( array $ability_meta, array $flat_filters ): bool {
		if ( empty( $flat_filters ) ) {
			return true;
		}

		$flat_filtered = wp_list_filter( array( $ability_meta ), $flat_filters );

		return ! empty( $flat_filtered );
	}

	/**
	 * Checks if ability meta matches nested filters.
	 *
	 * @param array<string,mixed> $ability_meta   The ability's meta data.
	 * @param array<string,mixed> $nested_filters The nested filters to match.
	 *
	 * @return bool True if meta matches all nested filters, false otherwise.
	 * @since n.e.x.t
	 *
	 */
	protected function check_nested_meta_filters( array $ability_meta, array $nested_filters ): bool {
		if ( empty( $nested_filters ) ) {
			return true;
		}

		foreach ( $nested_filters as $key => $nested_filter ) {
			if ( ! isset( $ability_meta[ $key ] ) || ! is_array( $ability_meta[ $key ] ) ) {
				return false;
			}

			$nested_filtered = wp_list_filter( array( $ability_meta[ $key ] ), $nested_filter );
			if ( empty( $nested_filtered ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Applies ordering to abilities.
	 *
	 * @param \WP_Ability[] $abilities Abilities to order.
	 *
	 * @return \WP_Ability[] Ordered abilities.
	 * @since n.e.x.t
	 *
	 */
	protected function apply_ordering( array $abilities ): array {
		$orderby = $this->query_vars['orderby'];

		if ( empty( $orderby ) ) {
			return $abilities;
		}

		$order = $this->query_vars['order'];

		// Map orderby field to getter method name.
		$getter_map = array(
			'name'     => 'get_name',
			'label'    => 'get_label',
			'category' => 'get_category',
		);

		if ( ! isset( $getter_map[ $orderby ] ) ) {
			return $abilities;
		}

		$getter_method    = $getter_map[ $orderby ];
		$order_multiplier = 'DESC' === $order ? - 1 : 1;

		$abilities = array_values( $abilities );

		usort(
			$abilities,
			static function ( $a, $b ) use ( $getter_method, $order_multiplier ) {
				return strcasecmp( $a->$getter_method(), $b->$getter_method() ) * $order_multiplier;
			}
		);

		return $abilities;
	}

	/**
	 * Applies pagination to abilities.
	 *
	 * @param \WP_Ability[] $abilities Abilities to paginate.
	 *
	 * @return \WP_Ability[] Paginated abilities.
	 * @since n.e.x.t
	 *
	 */
	protected function apply_pagination( array $abilities ): array {
		$limit  = $this->query_vars['limit'];
		$offset = $this->query_vars['offset'];

		// No pagination if limit is -1.
		if ( self::$no_limit === $limit ) {
			// Apply offset only if specified.
			if ( $offset > 0 ) {
				return array_slice( $abilities, $offset );
			}

			return $abilities;
		}

		// Apply offset and limit.
		return array_slice( $abilities, $offset, $limit );
	}

	/**
	 * Gets the query variables.
	 *
	 * @param string $key Optional. Specific query var to retrieve. Default empty string.
	 *
	 * @return mixed Query var value if key provided, all query vars if no key.
	 * @since n.e.x.t
	 *
	 */
	public function get( string $key = '' ) {
		if ( ! empty( $key ) ) {
			return $this->query_vars[ $key ] ?? null;
		}

		return $this->query_vars;
	}
}
