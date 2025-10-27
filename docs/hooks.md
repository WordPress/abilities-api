# Hooks

The Abilities API provides [WordPress Action and Filter Hooks](https://developer.wordpress.org/apis/hooks/) that allow developers to monitor and respond to ability execution events.

## Quick Links

- [Actions](#actions)
  - [`wp_abilities_api_categories_init`](#wp_abilities_api_categories_init)
  - [`wp_abilities_api_init`](#wp_abilities_api_init)
  - [`wp_before_execute_ability`](#wp_before_execute_ability)
  - [`wp_after_execute_ability`](#wp_after_execute_ability)
- [Filters](#filters)
  - [`wp_register_ability_args`](#wp_register_ability_args)
  - [`wp_register_ability_category_args`](#wp_register_ability_category_args)

## Actions

### `wp_abilities_api_categories_init`

Fires when the category registry is first initialized. This is the proper hook to use when registering categories.

```php
do_action( 'wp_abilities_api_categories_init', $registry );
```

#### Parameters

- `$registry` (`\WP_Abilities_Category_Registry`): The category registry instance.

#### Usage Example

```php
add_action( 'wp_abilities_api_categories_init', 'my_plugin_register_categories' );
/**
 * Register custom ability categories.
 *
 * @param \WP_Abilities_Category_Registry $registry The category registry instance.
 */
function my_plugin_register_categories( $registry ) {
    wp_register_ability_category( 'ecommerce', array(
        'label' => __( 'E-commerce', 'my-plugin' ),
        'description' => __( 'Abilities related to e-commerce functionality.', 'my-plugin' ),
    ));

    wp_register_ability_category( 'analytics', array(
        'label' => __( 'Analytics', 'my-plugin' ),
        'description' => __( 'Abilities that provide analytical data and insights.', 'my-plugin' ),
    ));
}
```

### `wp_abilities_api_init`

Fires when the abilities registry has been initialized. This is the proper hook to use when registering abilities.

```php
do_action( 'wp_abilities_api_init', $registry );
```

#### Parameters

- `$registry` (`\WP_Abilities_Registry`): The abilities registry instance.

#### Usage Example

```php
add_action('wp_abilities_api_init', 'my_plugin_register_abilities');
/**
 * Register custom abilities.
 */
function function my_plugin_register_abilities() {
    wp_register_ability( 'my-plugin/ability', array(
        'label'               => __( 'Title', 'my-plugin' ),
        'description'         => __( 'Description.', 'my-plugin' ),
        'category'            => 'analytics',
        'input_schema'        => array(
            'type'                 => 'object',
            'properties'           => array(),
            'additionalProperties' => false,
        ),
        'output_schema'       => array(
            'type'        => 'string',
            'description' => 'The site title.',
        ),
        'execute_callback'    => 'my_plugin_get_site_title',
        'permission_callback' => '__return_true', // Everyone can access this
        'meta'                => array(
            'category'    => 'site-info',
            'show_in_rest' => true, // Optional: expose via REST API
    ) );
}
```
### `wp_before_execute_ability`

Fires immediately before an ability gets executed, after permission checks have passed but before the execution callback is called.

```php
do_action( 'wp_before_execute_ability', $ability_name, $input );
```

#### Parameters

- `$ability_name` (`string`): The namespaced name of the ability being executed (e.g., `my-plugin/get-posts`).
- `$input` (`mixed`): The input data passed to the ability.

#### Usage Example

```php
add_action( 'wp_before_execute_ability', 'log_ability_execution', 10, 2 );
/**
 * Log each ability execution attempt.
 * @param string $ability_name The name of the ability being executed.
 * @param mixed  $input        The input data passed to the ability.
 */
function log_ability_execution( string $ability_name, $input ) {
    error_log( 'About to execute ability: ' . $ability_name );
    if ( $input !== null ) {
        error_log( 'Input: ' . wp_json_encode( $input ) );
    }
}
```

### `wp_after_execute_ability`

Fires immediately after an ability has finished executing successfully, after output validation has passed.

```php
do_action( 'wp_after_execute_ability', string $ability_name, $input, $result );
```

#### Parameters

- `$ability_name` (`string`): The namespaced name of the ability that was executed.
- `$input` (`mixed`): The input data that was passed to the ability.
- `$result` (`mixed`): The validated result returned by the ability's execution callback.

#### Usage Example

```php
add_action( 'wp_after_execute_ability', 'log_ability_result', 10, 3 );
/**
 * Log the result of each ability execution.
 *
 * @param string $ability_name The name of the executed ability.
 * @param mixed  $input        The input data passed to the ability.
 * @param mixed  $result       The result returned by the ability.
 */
function log_ability_result( string $ability_name, $input, $result ) {
    error_log( 'Completed ability: ' . $ability_name );
    error_log( 'Result: ' . wp_json_encode( $result ) );
}
```

## Filters

### `wp_register_ability_args`

Allows modification of an Ability's args before they are validated and used to instantiate the Ability.

```php
$args = apply_filters( 'wp_register_ability_args', array $args, string $ability_name );
```

#### Parameters

- `$args` (`array<string,mixed>`): The arguments used to instantiate the ability. See [wp_register_ability()](./3.registering-abilities.md#wp_register_ability) for the full list of args.
- `$ability_name` (`string`): The namespaced name of the ability being registered (e.g., `my-plugin/get-posts`).

#### Usage Example

```php
add_filter( 'wp_register_ability_args', 'my_modify_ability_args', 10, 2 );
/**
 * Modify ability args before validation.
 *
 * @param array<string,mixed> $args         The arguments used to instantiate the ability.
 * @param string              $ability_name The name of the ability, with its namespace.
 *
 * @return array<string,mixed> The modified ability arguments.
 */
function my_modify_ability_args( array $args, string $ability_name ): array {
    // Check if the ability name matches what you're looking for.
    if ( 'my-namespace/my-ability' !== $ability_name ) {
      return $args;
    }

    // Modify the args as needed.
    $args['label'] = __('My Custom Ability Label');

    // You can use the old args to build new ones.
    $args['description'] = sprintf(
        /* translators: 1: Ability name 2: Previous description */
        __('This is a custom description for the ability %s. Previously the description was %s', 'text-domain'),
        $ability_name,
        $args['description'] ?? 'N/A'
    );

    // Even if they're callbacks.
    $args['permission_callback' ] = static function ( $input = null ) use ( $args, $ability_name ) {
        $previous_check = is_callable( $args['permission_callback'] ) ? $args['permission_callback']( $input ) : true;

        // If we already failed, no need for stricter checks.
        if ( ! $previous_check || is_wp_error( $previous_check ) ) {
            return $previous_check;
        }

        return current_user_can( 'my_custom_ability_cap', $ability_name );
    }

    return $args;
}
```

### `wp_register_ability_category_args`

Allows modification of a category's arguments before validation.

```php
$args = apply_filters( 'wp_register_ability_category_args', array $args, string $slug );
```

#### Parameters

- `$args` (`array<string,mixed>`): The arguments used to instantiate the category (label, description).
- `$slug` (`string`): The slug of the category being registered.

#### Usage Example

```php
add_filter( 'wp_register_ability_category_args', 'my_modify_category_args', 10, 2 );
/**
 * Modify category args before validation.
 *
 * @param array<string,mixed> $args The arguments used to instantiate the category.
 * @param string              $slug The slug of the category being registered.
 *
 * @return array<string,mixed> The modified category arguments.
 */
function my_modify_category_args( array $args, string $slug ): array {
    if ( 'my-category' === $slug ) {
        $args['label'] = __( 'My Custom Label', 'my-plugin' );
        $args['description'] = __( 'My custom description for this category.', 'my-plugin' );
    }
    return $args;
}
```
