# PHP API

## Registering Categories

Before registering abilities, you must register at least one category. Categories help organize abilities and make them easier to discover and filter.

### Function Signature

```php
wp_register_ability_category( string $slug, array $args ): ?\WP_Ability_Category
```

**Parameters:**
- `$slug` (`string`): A unique identifier for the category. Must contain only lowercase alphanumeric characters and dashes (no underscores, no uppercase).
- `$args` (`array`): Category configuration with these keys:
  - `label` (`string`, **Required**): Human-readable name for the category. Should be translatable.
  - `description` (`string`, **Required**): Detailed description of the category's purpose. Should be translatable.
  - `meta` (`array`, **Optional**): An associative array for storing arbitrary additional metadata about the category.

**Return:** (`?\WP_Ability_Category`) An instance of the registered category if it was successfully registered, `null` on failure (e.g., invalid arguments, duplicate slug).

**Note:** Categories must be registered during the `wp_abilities_api_categories_init` action hook.

### Code Example

```php
add_action( 'wp_abilities_api_categories_init', 'my_plugin_register_categories' );
function my_plugin_register_categories() {
    wp_register_ability_category( 'data-retrieval', array(
        'label' => __( 'Data Retrieval', 'my-plugin' ),
        'description' => __( 'Abilities that retrieve and return data from the WordPress site.', 'my-plugin' ),
    ));

    wp_register_ability_category( 'data-modification', array(
        'label' => __( 'Data Modification', 'my-plugin' ),
        'description' => __( 'Abilities that modify data on the WordPress site.', 'my-plugin' ),
    ));

    wp_register_ability_category( 'communication', array(
        'label' => __( 'Communication', 'my-plugin' ),
        'description' => __( 'Abilities that send messages or notifications.', 'my-plugin' ),
    ));
}
```

### Category Slug Convention

The `$slug` parameter must follow these rules:

- **Format:** Must contain only lowercase alphanumeric characters (`a-z`, `0-9`) and hyphens (`-`).
- **Valid examples:** `data-retrieval`, `ecommerce`, `site-information`, `user-management`, `category-123`
- **Invalid examples:**
  - Uppercase: `Data-Retrieval`, `MyCategory`
  - Underscores: `data_retrieval`
  - Special characters: `data.retrieval`, `data/retrieval`, `data retrieval`
  - Leading/trailing dashes: `-data`, `data-`
  - Double dashes: `data--retrieval`

## Unregister a Category

Remove a registered category.

### Function Signature

```php
wp_unregister_ability_category( string $slug ) ?\WP_Ability_Category
```

**Parameters:**
- `$slug` (`string`): The slug of the registered category.

**Return:** (`?\WP_Ability_Category`) The unregistered category instance on success, `null` on failure.

## Fetch a Category

Retrieve a specific category by slug.

### Function Signature

```php
wp_get_ability_category( string $slug ) ?\WP_Ability_Category
```

**Parameters:**
- `$slug` (`string`): The slug of the registered category.

**Return:** (`?\WP_Ability_Category`) The category instance on success, `null` on failure.

## Fetch all Categories

Get all registered categories as an associative array keyed by slug.

### Function Signature

```php
wp_get_ability_categories() array
```

**Return:** (`array`) An associative array of all registered categories, keyed by slug. Each value is an instance of `WP_Ability_Category`.

## Registering Abilities (`wp_register_ability`)

The primary way to add functionality to the Abilities API is by using the `wp_register_ability()` function, typically hooked into the `wp_abilities_api_init` action.

### Function Signature

```php
wp_register_ability( string $id, array $args ): ?\WP_Ability
```

**Parameters:**
- `$id` (`string`): A unique identifier for the ability.
- `$args` (`array`): An array of arguments defining the ability configuration.

- **Return:** (`?\WP_Ability`) An instance of the registered ability if it was successfully registered, `null` on failure (e.g., invalid arguments, duplicate ID).

### Parameters Explained

The `$args` array accepts the following keys:

- `label` (`string`, **Required**): A human-readable name for the ability. Used for display purposes. Should be translatable.
- `description` (`string`, **Required**): A detailed description of what the ability does, its purpose, and its parameters or return values. This is crucial for AI agents to understand how and when to use the ability. Should be translatable.
- `category` (`string`, **Required**): The slug of the category this ability belongs to. The category must be registered before registering the ability using `wp_register_ability_category()`. Categories help organize and filter abilities by their purpose. See [Registering Categories](#registering-categories) for details.
- `input_schema` (`array`, **Optional**): A JSON Schema definition describing the expected input parameters for the ability's execute callback. Only needed when creating Abilities that require inputs. Defaults to `null` only when no schema is provided. Used for validation and documentation.
- `output_schema` (`array`, **Required**): A JSON Schema definition describing the expected format of the data returned by the ability. Used for validation and documentation.
- `execute_callback` (`callable`, **Required**): The PHP function or method to execute when this ability is called.
  - The callback receives one optional argument, the input data for the ability. The argument is required when the input schema is defined.
  - The input argument will have the same type as defined in the input schema (e.g., `array`, `object`, `string`, etc.).
  - The callback should return the result of the ability's operation or return a `WP_Error` object on failure.
- `permission_callback` (`callable`, **Required**): A callback function to check if the current user has permission to execute this ability.
  - The callback receives one optional argument, the input data for the ability. The argument is required when the input schema is defined.
  - The input argument will have the same type as defined in the input schema (e.g., `array`, `object`, `string`, etc.).
  - The callback should return a boolean (`true` if the user has permission, `false` otherwise), or a `WP_Error` object on failure.
  - If an input schema is set, and the input does not validate against the input schema, the permission callback will not be called, and a `WP_Error` will be returned instead.
- `meta` (`array`, **Optional**): An associative array for storing arbitrary additional metadata about the ability.
  - `annotations` (`array`, **Optional**): An associative array of annotations providing hints about the ability's behavior characteristics. Supports the following keys:
    - `instructions` (`string`, **Optional**): Custom instructions or guidance for using the ability (default: `''`).
    - `readonly` (`boolean`, **Optional**): Whether the ability only reads data without modifying its environment (default: `false`).
    - `destructive` (`boolean`, **Optional**): Whether the ability may perform destructive updates to its environment. If `true`, the ability may perform any type of modification, including deletions or other destructive changes. If `false`, the ability performs only additive updates (default: `true`).
    - `idempotent` (`boolean`, **Optional**): Whether calling the ability repeatedly with the same arguments will have no additional effect on its environment (default: `false`).
  - `show_in_rest` (`boolean`, **Optional**): Whether to expose this ability via the REST API. Default: `false`.
    - When `true`, the ability will be listed in REST API responses and can be executed via REST endpoints.
    - When `false`, the ability will be hidden from REST API listings and cannot be executed via REST endpoints, but remains available for internal PHP usage.
- `ability_class` (`string`, **Optional**): The fully-qualified class name of a custom ability class that extends `WP_Ability`. This allows you to customize the behavior of an ability by extending the base `WP_Ability` class and overriding its methods. The custom class must extend `WP_Ability`. Default: `WP_Ability`.

### Ability ID Convention

The `$id` parameter must follow the pattern `namespace/ability-name`:

- **Format:** Must contain only lowercase alphanumeric characters (`a-z`, `0-9`), hyphens (`-`), and one forward slash (`/`) for namespacing.
- **Convention:** Use your plugin slug as the namespace, like `my-plugin/ability-name`.
- **Examples:** `my-plugin/update-settings`, `woocommerce/get-product`, `contact-form/send-message`, `analytics/track-event`

### Code Examples

#### Registering a simple data retrieval Ability without an input schema

```php
add_action( 'wp_abilities_api_init', 'my_plugin_register_site_info_ability' );
function my_plugin_register_site_info_ability() {
    wp_register_ability( 'my-plugin/get-site-info', array(
        'label' => __( 'Get Site Information', 'my-plugin' ),
        'description' => __( 'Retrieves basic information about the WordPress site including name, description, and URL.', 'my-plugin' ),
        'category' => 'data-retrieval',
        'output_schema' => array(
            'type' => 'object',
            'properties' => array(
                'name' => array(
                    'type' => 'string',
                    'description' => 'Site name'
                ),
                'description' => array(
                    'type' => 'string',
                    'description' => 'Site tagline'
                ),
                'url' => array(
                    'type' => 'string',
                    'format' => 'uri',
                    'description' => 'Site URL'
                )
            )
        ),
        'execute_callback' => function() {
            return array(
                'name' => get_bloginfo( 'name' ),
                'description' => get_bloginfo( 'description' ),
                'url' => home_url()
            );
        },
        'permission_callback' => '__return_true',
        'meta' => array(
            'annotations' => array(
                'readonly' => true,
                'destructive' => false
            ),
        ),
    ));
}
```

#### Registering an Ability with Input Parameters

```php
add_action( 'wp_abilities_api_init', 'my_plugin_register_update_option_ability' );
function my_plugin_register_update_option_ability() {
    wp_register_ability( 'my-plugin/update-option', array(
        'label' => __( 'Update WordPress Option', 'my-plugin' ),
        'description' => __( 'Updates the value of a WordPress option in the database. Requires manage_options capability.', 'my-plugin' ),
        'category' => 'data-modification',
        'input_schema' => array(
            'type' => 'object',
            'properties' => array(
                'option_name' => array(
                    'type' => 'string',
                    'description' => 'The name of the option to update',
                    'minLength' => 1
                ),
                'option_value' => array(
                    'description' => 'The new value for the option'
                )
            ),
            'required' => array( 'option_name', 'option_value' ),
            'additionalProperties' => false
        ),
        'output_schema' => array(
            'type' => 'object',
            'properties' => array(
                'success' => array(
                    'type' => 'boolean',
                    'description' => 'Whether the option was successfully updated'
                ),
                'previous_value' => array(
                    'description' => 'The previous value of the option'
                )
            )
        ),
        'execute_callback' => function( $input ) {
            $option_name = $input['option_name'];
            $new_value = $input['option_value'];

            $previous_value = get_option( $option_name );
            $success = update_option( $option_name, $new_value );

            return array(
                'success' => $success,
                'previous_value' => $previous_value
            );
        },
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
        'meta' => array(
            'annotations' => array(
                'destructive' => false,
                'idempotent' => true
            ),
        ),
    ));
}
```

#### Registering an Ability with Plugin Dependencies

```php
add_action( 'wp_abilities_api_init', 'my_plugin_register_woo_stats_ability' );
function my_plugin_register_woo_stats_ability() {
    // Only register if WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    wp_register_ability( 'my-plugin/get-woo-stats', array(
        'label' => __( 'Get WooCommerce Statistics', 'my-plugin' ),
        'description' => __( 'Retrieves basic WooCommerce store statistics including total orders and revenue.', 'my-plugin' ),
        'category' => 'ecommerce',
        'input_schema' => array(
            'type' => 'object',
            'properties' => array(
                'period' => array(
                    'type' => 'string',
                    'enum' => array( 'today', 'week', 'month', 'year' ),
                    'default' => 'month',
                    'description' => 'Time period for statistics'
                )
            ),
            'additionalProperties' => false
        ),
        'output_schema' => array(
            'type' => 'object',
            'properties' => array(
                'total_orders' => array(
                    'type' => 'integer',
                    'description' => 'Number of orders in period'
                ),
                'total_revenue' => array(
                    'type' => 'number',
                    'description' => 'Total revenue in period'
                )
            )
        ),
        'execute_callback' => function( $input ) {
            $period = $input['period'] ?? 'month';

            // Implementation would calculate stats based on period
            return array(
                'total_orders' => 42,
                'total_revenue' => 1250.50
            );
        },
        'permission_callback' => function() {
            return current_user_can( 'manage_woocommerce' );
        },
        'meta' => array(
            'requires_plugin' => 'woocommerce'
        )
    ));
}
```

#### Registering an Ability That May Fail

```php
add_action( 'wp_abilities_api_init', 'my_plugin_register_send_email_ability' );
function my_plugin_register_send_email_ability() {
    wp_register_ability( 'my-plugin/send-email', array(
        'label' => __( 'Send Email', 'my-plugin' ),
        'description' => __( 'Sends an email to the specified recipient using WordPress mail functions.', 'my-plugin' ),
        'category' => 'communication',
        'input_schema' => array(
            'type' => 'object',
            'properties' => array(
                'to' => array(
                    'type' => 'string',
                    'format' => 'email',
                    'description' => 'Recipient email address'
                ),
                'subject' => array(
                    'type' => 'string',
                    'minLength' => 1,
                    'description' => 'Email subject'
                ),
                'message' => array(
                    'type' => 'string',
                    'minLength' => 1,
                    'description' => 'Email message body'
                )
            ),
            'required' => array( 'to', 'subject', 'message' ),
            'additionalProperties' => false
        ),
        'output_schema' => array(
            'type' => 'object',
            'properties' => array(
                'sent' => array(
                    'type' => 'boolean',
                    'description' => 'Whether the email was successfully sent'
                )
            )
        ),
        'execute_callback' => function( $input ) {
            $sent = wp_mail(
                $input['to'],
                $input['subject'],
                $input['message']
            );

            if ( ! $sent ) {
                return new \WP_Error(
                    'email_send_failed',
                    sprintf( __( 'Failed to send email' ), 'my-plugin' )
                );
            }

            return array( 'sent' => true );
        },
        'permission_callback' => function() {
            return current_user_can( 'publish_posts' );
        }
    ));
}
```

#### Registering an Ability with a Custom Ability Class

The `ability_class` parameter allows you to use a custom class that extends `WP_Ability`. This is useful when you want to extend the default behavior of the base `WP_Ability` class.

**Example: Creating a custom ability class with additional methods**

```php
/**
 * Custom ability class that adds logging.
 *
 * This example shows how to extend WP_Ability to add custom behavior
 * while still leveraging all the standard ability functionality.
 */
class My_Plugin_Post_Validator_Ability extends WP_Ability {
    /**
     * Override the do_execute method to add custom logging.
     *
     * This demonstrates how you can override methods from WP_Ability
     * to customize behavior before or after the standard execution.
     *
     * @param mixed $input Optional. The input data for the ability.
     * @return mixed|\WP_Error The result of the ability execution.
     */
    protected function do_execute( $input = null ) {
        // Log the execution for debugging purposes
        error_log( sprintf(
            'Executing ability: %s with input: %s',
            $this->get_name(),
            json_encode( $input )
        ) );

        // Call the parent's do_execute to run the normal execute_callback
        $result = parent::do_execute( $input );

        // Log the result
        if ( is_wp_error( $result ) ) {
            error_log( sprintf(
                'Ability %s failed: %s',
                $this->get_name(),
                $result->get_error_message()
            ) );
        } else {
            error_log( sprintf(
                'Ability %s completed successfully',
                $this->get_name()
            ) );
        }

        return $result;
    }
}

/**
 * Register the ability using the custom ability class.
 */
add_action( 'wp_abilities_api_init', 'my_plugin_register_post_validator_ability' );
function my_plugin_register_post_validator_ability() {
    wp_register_ability( 'my-plugin/validate-post', array(
        'label' => __( 'Validate Post', 'my-plugin' ),
        'description' => __( 'Validates that a post exists, is published, and returns its metadata.', 'my-plugin' ),
        'category' => 'data-retrieval',
        'input_schema' => array(
            'type' => 'object',
            'properties' => array(
                'post_id' => array(
                    'type' => 'integer',
                    'description' => 'The ID of the post to validate',
                    'minimum' => 1
                )
            ),
            'required' => array( 'post_id' ),
            'additionalProperties' => false
        ),
        'output_schema' => array(
            'type' => 'object',
            'properties' => array(
                'valid' => array(
                    'type' => 'boolean',
                    'description' => 'Whether the post is valid'
                ),
                'post_title' => array(
                    'type' => 'string',
                    'description' => 'The post title'
                ),
                'post_date' => array(
                    'type' => 'string',
                    'description' => 'The post publication date'
                )
            )
        ),
        'execute_callback' => function( $input ) {
            $post_id = $input['post_id'];   
            $post = get_post( $post_id );
            if ( ! $post ) {
                return new \WP_Error(
                    'invalid_post',
                    __( 'The specified post does not exist.', 'my-plugin' )
                );
            }
            // Check if the post is published
            if ( 'publish' !== $post->post_status ) {
                return new \WP_Error(
                    'post_not_published',
                    __( 'The specified post is not published.', 'my-plugin' )
                );
            }
            // If validation passes, return post information
            return array(
                'valid' => true,
                'post_title' => $post->post_title,
                'post_date' => $post->post_date
            );
        },
        'permission_callback' => function() {
            // Any logged-in user can validate posts
            return is_user_logged_in();
        },
        'meta' => array(
            'annotations' => array(
                'readonly' => true,
                'destructive' => false
            )
        ),
        // Specify the custom ability class to use
        'ability_class' => 'My_Plugin_Post_Validator_Ability'
    ));
}
```

**Important notes about custom ability classes:**

- Your custom class **must** extend `WP_Ability`
- The custom class is only used to instantiate the ability - the `ability_class` parameter is not stored as a property of the ability
- You can override protected methods like `do_execute()`, `validate_input()`, or `validate_output()` to customize behavior
- You can add custom methods to provide additional functionality specific to your ability
- The custom class receives the same `$name` and `$args` parameters in its constructor as the base `WP_Ability` class
- If the specified class does not exist or does not extend `WP_Ability`, registration will fail with a `_doing_it_wrong()` notice

## Checking if an Ability is Registered

You can check if an ability is registered using the `wp_has_ability()` function.

### Function Signature

```php
wp_has_ability( string $name ): bool
```

**Parameters:**
- `$name` (`string`): The name of the ability to check (namespace/ability-name).

**Return:** (`bool`) `true` if the ability is registered, `false` otherwise.

### Code Example

```php
$ability_name = 'my-plugin/get-site-info';
if ( wp_has_ability( $ability_name ) ) {
    // Ability is registered
}
```

## Using Abilities (`wp_get_ability`, `wp_get_abilities`)

Once abilities are registered, they can be retrieved and executed using global functions from the Abilities API.

### Getting a Specific Ability (`wp_get_ability`)

To get a single ability object by its name (namespace/ability-name):

```php
/**
 * Retrieves a registered ability using Abilities API.
 *
 * @param string $name The name of the registered ability, with its namespace.
 * @return ?WP_Ability The registered ability instance, or null if it is not registered.
 */
function wp_get_ability( string $name ): ?WP_Ability

// Example:
$site_info_ability = wp_get_ability( 'my-plugin/get-site-info' );

if ( $site_info_ability ) {
	// Ability exists and is registered
	$site_info = $site_info_ability->execute();
	if ( is_wp_error( $site_info ) ) {
		// Handle WP_Error
		echo 'Error: ' . $site_info->get_error_message();
	} else {
		// Use $site_info array
		echo 'Site Name: ' . $site_info['name'];
	}
} else {
	// Ability not found or not registered
}
```

### Getting All Registered Abilities (`wp_get_abilities`)

To get an array of all registered abilities:

```php
/**
 * Retrieves all registered abilities using Abilities API.
 *
 * @return WP_Ability[] The array of registered abilities.
 */
function wp_get_abilities(): array

// Example: Get all registered abilities
$all_abilities = wp_get_abilities();

foreach ( $all_abilities as $name => $ability ) {
    echo 'Ability Name: ' . esc_html( $ability->get_name() ) . "\n";
    echo 'Label: ' . esc_html( $ability->get_label() ) . "\n";
    echo 'Description: ' . esc_html( $ability->get_description() ) . "\n";
    echo "---\n";
}
```

### Executing an Ability (`$ability->execute()`)

Once you have a `WP_Ability` object (usually from `wp_get_ability`), you execute it using the `execute()` method.

```php
/**
 * Executes the ability after input validation and running a permission check.
 *
 * @param mixed $input Optional. The input data for the ability. Defaults to `null`.
 * @return mixed|WP_Error The result of the ability execution, or WP_Error on failure.
 */
// public function execute( $input = null )

// Example 1: Ability with no input parameters
$ability = wp_get_ability( 'my-plugin/get-site-info' );
if ( $ability ) {
    $site_info = $ability->execute(); // No input required
    if ( is_wp_error( $site_info ) ) {
        // Handle WP_Error
        echo 'Error: ' . $site_info->get_error_message();
    } else {
        // Use $site_info array
        echo 'Site Name: ' . $site_info['name'];
    }
}

// Example 2: Ability with input parameters
$ability = wp_get_ability( 'my-plugin/update-option' );
if ( $ability ) {
    $input = array(
        'option_name'  => 'blogname',
        'option_value' => 'My Updated Site Name',
    );

    $result = $ability->execute( $input );
    if ( is_wp_error( $result ) ) {
        // Handle WP_Error
        echo 'Error: ' . $result->get_error_message();
    } else {
        // Use $result
        if ( $result['success'] ) {
            echo 'Option updated successfully!';
            echo 'Previous value: ' . $result['previous_value'];
        }
    }
}

// Example 3: Ability with complex input validation
$ability = wp_get_ability( 'my-plugin/send-email' );
if ( $ability ) {
    $input = array(
        'to'      => 'user@example.com',
        'subject' => 'Hello from WordPress',
        'message' => 'This is a test message from the Abilities API.',
    );

    $result = $ability->execute( $input );
    if ( is_wp_error( $result ) ) {
        // Handle WP_Error
        echo 'Error: ' . $result->get_error_message();
    } elseif ( $result['sent'] ) {
        echo 'Email sent successfully!';
    } else {
        echo 'Email failed to send.';
    }
}
```

### Checking Permissions (`$ability->check_permissions()`)

You can check if the current user has permissions to execute the ability, also without executing it. The `check_permissions()` method returns either `true`, `false`, or a `WP_Error` object. `true` means permission is granted, `false` means the user simply lacks permission, and a `WP_Error` return value typically indicates a failure in the permission check process (such as an internal error or misconfiguration). You must use `is_wp_error()` to handle errors properly and distinguish between permission denial and actual errors:

```php
$ability = wp_get_ability( 'my-plugin/update-option' );
if ( $ability ) {
    $input = array(
        'option_name'  => 'blogname',
        'option_value' => 'New Site Name',
    );

    // Check permission before execution - always use is_wp_error() first
    $has_permissions = $ability->check_permissions( $input );
    if ( true === $has_permissions ) {
        // Permissions granted – safe to execute.
        echo 'You have permissions to execute this ability.';
    } else {
        // Don't leak permission errors to unauthenticated users.
        if ( is_wp_error( $has_permissions ) ) {
            error_log( 'Permissions check failed: ' . $has_permissions->get_error_message() );
        }

        echo 'You do not have permissions to execute this ability.';
    }
}
```

### Inspecting Ability Properties

The `WP_Ability` class provides several getter methods to inspect ability properties:

```php
$ability = wp_get_ability( 'my-plugin/get-site-info' );
if ( $ability ) {
    // Basic properties
    echo 'Name: ' . $ability->get_name() . "\n";
    echo 'Label: ' . $ability->get_label() . "\n";
    echo 'Description: ' . $ability->get_description() . "\n";

    // Schema information
    $input_schema = $ability->get_input_schema();
    $output_schema = $ability->get_output_schema();

    echo 'Input Schema: ' . json_encode( $input_schema, JSON_PRETTY_PRINT ) . "\n";
    echo 'Output Schema: ' . json_encode( $output_schema, JSON_PRETTY_PRINT ) . "\n";

    // Metadata
    $meta = $ability->get_meta();
    if ( ! empty( $meta ) ) {
        echo 'Metadata: ' . json_encode( $meta, JSON_PRETTY_PRINT ) . "\n";
    }
}
```

### Error Handling Patterns

The Abilities API uses several error handling mechanisms:

```php
$ability = wp_get_ability( 'my-plugin/some-ability' );

if ( ! $ability ) {
    // Ability not registered
    echo 'Ability not found';
    return;
}

$result = $ability->execute( $input );

// Check for WP_Error (validation, permission, or callback errors)
if ( is_wp_error( $result ) ) {
    echo 'WP_Error: ' . $result->get_error_message();
    return;
}

// Check for null result (permission denied, invalid callback, or validation failure)
if ( is_null( $result ) ) {
    echo 'Execution returned null - check permissions and callback validity';
    return;
}

// Success - use the result
// Process $result based on the ability's output schema
```

