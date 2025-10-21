<?php
/**
 * Abilities API
 *
 * Defines WP_Abilities_Collection class.
 *
 * @package WordPress
 * @subpackage Abilities API
 * @since n.e.x.t
 */

declare(strict_types=1);

/**
 * A collection class for filtering and sorting abilities.
 *
 * Provides a fluent, chainable API for querying abilities in memory.
 *
 * @since n.e.x.t
 *
 * @implements \IteratorAggregate<int, \WP_Ability>
 */
class WP_Abilities_Collection implements IteratorAggregate, Countable {
	/**
	 * The abilities in this collection.
	 *
	 * @var array<\WP_Ability>
	 */
	private $abilities = array();

	/**
	 * @since n.e.x.t
	 *
	 * @param array<\WP_Ability> $abilities Array of WP_Ability objects.
	 */
	public function __construct( array $abilities = array() ) {
		$this->abilities = $abilities;
	}

	/**
	 * Get iterator for foreach loops (IteratorAggregate).
	 *
	 * @since n.e.x.t
	 *
	 * @return \ArrayIterator<int, \WP_Ability> Iterator over abilities.
	 */
	public function getIterator(): ArrayIterator {
		return new ArrayIterator( $this->abilities );
	}

	/**
	 * Count abilities (Countable).
	 *
	 * @since n.e.x.t
	 *
	 * @return int Number of abilities.
	 */
	public function count(): int {
		return count( $this->abilities );
	}

	/**
	 * Get underlying array of abilities.
	 *
	 * @since n.e.x.t
	 *
	 * @return array<\WP_Ability> Array of abilities.
	 */
	public function to_array(): array {
		return $this->abilities;
	}

	/**
	 * @since n.e.x.t
	 *
	 * @return array<\WP_Ability> Array of abilities.
	 */
	public function all(): array {
		return $this->to_array();
	}

	/**
	 * Re-index abilities with sequential keys.
	 *
	 * @since n.e.x.t
	 *
	 * @return self New collection with re-indexed abilities.
	 */
	public function values(): self {
		return new self( array_values( $this->abilities ) );
	}

	/**
	 * Get all ability names.
	 *
	 * @since n.e.x.t
	 *
	 * @return array<string> Array of ability names.
	 */
	public function keys(): array {
		return array_map(
			static function ( $ability ) {
				return $ability->get_name();
			},
			$this->abilities
		);
	}

	/**
	 * Filter abilities using a callback.
	 *
	 * @since n.e.x.t
	 *
	 * @param callable $callback Filter callback (receives WP_Ability, returns bool).
	 * @return self New filtered collection.
	 */
	public function filter( callable $callback ): self {
		return new self( array_filter( $this->abilities, $callback ) );
	}

	/**
	 * Extract a single property from all abilities.
	 *
	 * Supports dot notation for nested properties:
	 * - pluck('name') - Get all ability names
	 * - pluck('meta.show_in_rest') - Get nested meta property
	 * - pluck('label', 'name') - Get labels keyed by names
	 * - pluck('meta.priority', 'name') - Get nested values keyed by names
	 *
	 * @since n.e.x.t
	 *
	 * @param string      $value Property to extract (supports dot notation).
	 * @param string|null $key   Optional property to use as array keys (supports dot notation).
	 * @return array<int|string, mixed> Array of extracted values.
	 */
	public function pluck( string $value, ?string $key = null ): array {
		$result = array();

		foreach ( $this->abilities as $ability ) {
			$plucked_value = $this->data_get( $ability, $value );

			if ( null === $key ) {
				$result[] = $plucked_value;
			} else {
				$key_value            = $this->data_get( $ability, $key );
				$result[ $key_value ] = $plucked_value;
			}
		}

		return $result;
	}

	/**
	 * Get nested property value using dot notation.
	 *
	 * Handles both object methods (get_name, get_meta) and nested array access.
	 *
	 * @since n.e.x.t
	 *
	 * @param \WP_Ability $ability The ability object.
	 * @param string     $key     Dot-notated key (e.g., 'meta.annotations.readonly').
	 * @return mixed The property value or null if not found.
	 */
	private function data_get( WP_Ability $ability, string $key ) {
		// Split key into segments.
		$segments      = explode( '.', $key );
		$first_segment = array_shift( $segments );

		// Try to get value from ability getter method.
		$method = 'get_' . $first_segment;

		if ( ! method_exists( $ability, $method ) ) {
			return null;
		}

		$value = $ability->$method();

		// If no more segments, return value.
		if ( empty( $segments ) ) {
			return $value;
		}

		// Traverse nested array segments.
		return $this->array_get( $value, implode( '.', $segments ) );
	}

	/**
	 * Get array value using dot notation.
	 *
	 * @since n.e.x.t
	 *
	 * @param mixed  $target Array to search.
	 * @param string $key    Dot-notated key.
	 * @return mixed Value or null if not found.
	 */
	private function array_get( $target, string $key ) {
		if ( ! is_array( $target ) ) {
			return null;
		}

		// Check if key exists directly (no dot).
		if ( isset( $target[ $key ] ) ) {
			return $target[ $key ];
		}

		// Traverse nested keys.
		foreach ( explode( '.', $key ) as $segment ) {
			if ( ! is_array( $target ) || ! array_key_exists( $segment, $target ) ) {
				return null;
			}
			$target = $target[ $segment ];
		}

		return $target;
	}

	/**
	 * Compare two values using an operator.
	 *
	 * @since n.e.x.t
	 *
	 * @param mixed  $actual   The actual value.
	 * @param string $operator Comparison operator (=, ===, !=, !==, >, <, >=, <=).
	 * @param mixed  $expected The expected value.
	 * @return bool True if comparison passes.
	 */
	private function compare_values( $actual, string $operator, $expected ): bool {
		switch ( $operator ) {
			case '=':
			case '==':
				return $actual == $expected; // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual

			case '===':
				return $actual === $expected;

			case '!=':
			case '<>':
				return $actual != $expected; // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual

			case '!==':
				return $actual !== $expected;

			case '>':
				return $actual > $expected;

			case '>=':
				return $actual >= $expected;

			case '<':
				return $actual < $expected;

			case '<=':
				return $actual <= $expected;

			default:
				return $actual === $expected;
		}
	}

	/**
	 * Filter abilities by property value using dot notation.
	 *
	 * Supports:
	 * - Direct properties: where('category', 'math')
	 * - Nested properties: where('meta.show_in_rest', true)
	 * - Deep nesting: where('meta.annotations.readonly', true)
	 * - Comparison operators: where('meta.priority', '>', 5)
	 *
	 * @since n.e.x.t
	 *
	 * @param string $key      Property key (supports dot notation).
	 * @param mixed  $operator Comparison operator or value if 2 args.
	 * @param mixed  $value    Value to compare (optional).
	 * @return self New collection with filtered abilities.
	 */
	public function where( string $key, $operator = null, $value = null ): self {
		// Handle 2-argument version: where('key', 'value').
		if ( 2 === func_num_args() ) {
			$value    = $operator;
			$operator = '=';
		}

		return $this->filter(
			function ( $ability ) use ( $key, $operator, $value ) {
				$actual = $this->data_get( $ability, $key );
				return $this->compare_values( $actual, $operator, $value );
			}
		);
	}

	/**
	 * Filter items where key is in given values (supports dot notation).
	 *
	 * @since n.e.x.t
	 *
	 * @param string               $key    Property key (supports dot notation).
	 * @param array<int|string, mixed> $values Values to match.
	 * @return self New collection with filtered abilities.
	 */
	public function where_in( string $key, array $values ): self {
		return $this->filter(
			function ( $ability ) use ( $key, $values ) {
				$actual = $this->data_get( $ability, $key );
				return in_array( $actual, $values, true );
			}
		);
	}

	/**
	 * Filter items where key is NOT in given values (supports dot notation).
	 *
	 * @since n.e.x.t
	 *
	 * @param string               $key    Property key (supports dot notation).
	 * @param array<int|string, mixed> $values Values to exclude.
	 * @return self New collection with filtered abilities.
	 */
	public function where_not_in( string $key, array $values ): self {
		return $this->filter(
			function ( $ability ) use ( $key, $values ) {
				$actual = $this->data_get( $ability, $key );
				return ! in_array( $actual, $values, true );
			}
		);
	}

	/**
	 * Filter abilities by category.
	 *
	 * @since n.e.x.t
	 *
	 * @param string|array<string> $categories Single category or array of categories.
	 * @return self New collection with filtered abilities.
	 */
	public function where_category( $categories ): self {
		if ( is_array( $categories ) ) {
			return $this->where_in( 'category', $categories );
		}

		return $this->where( 'category', $categories );
	}

	/**
	 * Filter abilities by namespace.
	 *
	 * @since n.e.x.t
	 *
	 * @param string|array<string> $namespaces Single namespace or array of namespaces.
	 * @return self New collection with filtered abilities.
	 */
	public function where_namespace( $namespaces ): self {
		$namespaces = (array) $namespaces;

		return $this->filter(
			static function ( $ability ) use ( $namespaces ) {
				$name_parts = explode( '/', $ability->get_name() );
				$namespace  = $name_parts[0] ?? '';

				return in_array( $namespace, $namespaces, true );
			}
		);
	}

	/**
	 * Filter abilities by meta properties (supports dot notation).
	 *
	 * @since n.e.x.t
	 *
	 * @param array<string,mixed> $filters Associative array of meta filters.
	 *                                     Supports dot notation for nested keys.
	 * @return self New collection with filtered abilities.
	 */
	public function where_meta( array $filters ): self {
		return $this->filter(
			function ( $ability ) use ( $filters ) {
				$meta = $ability->get_meta();

				foreach ( $filters as $key => $expected_value ) {
					// Use array_get helper for dot notation support.
					$actual_value = $this->array_get( $meta, $key );

					if ( $actual_value !== $expected_value ) {
						return false;
					}
				}

				return true;
			}
		);
	}

	/**
	 * Search abilities by term across name, label, and description.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $term Search term.
	 * @return self New collection with matching abilities.
	 */
	public function search( string $term ): self {
		$term = strtolower( $term );

		return $this->filter(
			static function ( $ability ) use ( $term ) {
				$searchable = array(
					$ability->get_name(),
					$ability->get_label(),
					$ability->get_description(),
				);

				foreach ( $searchable as $text ) {
					if ( false !== stripos( $text, $term ) ) {
						return true;
					}
				}

				return false;
			}
		);
	}

	/**
	 * Sort abilities by property or callback.
	 *
	 * @since n.e.x.t
	 *
	 * @param string|callable $callback   Property name or callback function.
	 * @param bool            $descending Sort in descending order (default: false).
	 * @return self New sorted collection.
	 */
	public function sort_by( $callback, bool $descending = false ): self {
		// If callback is a string (property name), use wp_list_sort.
		if ( is_string( $callback ) ) {
			// Map property names to getter methods.
			$property_map = array(
				'name'        => 'name',
				'label'       => 'label',
				'description' => 'description',
				'category'    => 'category',
			);

			$field = $property_map[ $callback ] ?? $callback;
			$order = $descending ? 'DESC' : 'ASC';

			// Convert abilities to associative arrays for wp_list_sort.
			$abilities_array = array_map(
				static function ( $ability ) {
					return array(
						'object'      => $ability, // Keep original object.
						'name'        => $ability->get_name(),
						'label'       => $ability->get_label(),
						'description' => $ability->get_description(),
						'category'    => $ability->get_category(),
					);
				},
				$this->abilities
			);

			// Sort using wp_list_sort.
			$sorted = wp_list_sort( $abilities_array, $field, $order );

			// Extract back the WP_Ability objects.
			$sorted_abilities = wp_list_pluck( $sorted, 'object' );

			return new self( $sorted_abilities );
		}

		// For callbacks, use usort.
		$sorted = $this->abilities;
		usort( $sorted, $callback );

		if ( $descending ) {
			$sorted = array_reverse( $sorted );
		}

		return new self( $sorted );
	}

	/**
	 * Sort abilities by property or callback in descending order.
	 *
	 * @since n.e.x.t
	 *
	 * @param string|callable $callback Property name or callback function.
	 * @return self New sorted collection.
	 */
	public function sort_by_desc( $callback ): self {
		return $this->sort_by( $callback, true );
	}

	/**
	 * Reverse the order of abilities.
	 *
	 * @since n.e.x.t
	 *
	 * @return self New collection with reversed order.
	 */
	public function reverse(): self {
		return new self( array_reverse( $this->abilities ) );
	}

	/**
	 * Get first ability.
	 *
	 * @since n.e.x.t
	 *
	 * @param callable|null $callback      Optional filter callback.
	 * @param mixed         $default_value Default value if not found.
	 * @return mixed First ability or default.
	 */
	public function first( ?callable $callback = null, $default_value = null ) {
		if ( null === $callback ) {
			return ! empty( $this->abilities ) ? reset( $this->abilities ) : $default_value;
		}

		$filtered = $this->filter( $callback );
		return ! $filtered->is_empty() ? $filtered->first() : $default_value;
	}

	/**
	 * Get last ability.
	 *
	 * @since n.e.x.t
	 *
	 * @param callable|null $callback      Optional filter callback.
	 * @param mixed         $default_value Default value if not found.
	 * @return mixed Last ability or default.
	 */
	public function last( ?callable $callback = null, $default_value = null ) {
		if ( null === $callback ) {
			return ! empty( $this->abilities ) ? end( $this->abilities ) : $default_value;
		}

		$filtered = $this->filter( $callback );
		return ! $filtered->is_empty() ? $filtered->last() : $default_value;
	}

	/**
	 * Get ability by name.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $name          Ability name.
	 * @param mixed  $default_value Default value if not found.
	 * @return mixed Ability or default.
	 */
	public function get( string $name, $default_value = null ) {
		return $this->first(
			static function ( $ability ) use ( $name ) {
				return $ability->get_name() === $name;
			},
			$default_value
		);
	}

	/**
	 * Check if collection is empty.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool True if empty.
	 */
	public function is_empty(): bool {
		return empty( $this->abilities );
	}

	/**
	 * Check if collection is not empty.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool True if not empty.
	 */
	public function is_not_empty(): bool {
		return ! $this->is_empty();
	}
}
