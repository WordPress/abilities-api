# Filtering Abilities

The Abilities API provides powerful filtering capabilities to help manage large numbers of registered abilities from core and plugins.

## Using `wp_get_abilities()` with Filtering

The `wp_get_abilities()` function now accepts optional query parameters to filter the results while maintaining backward compatibility.

### Basic Usage

```php
// Get all abilities (backward compatible)
$all_abilities = wp_get_abilities();

// Filter by namespace
$plugin_abilities = wp_get_abilities( array( 'namespace' => 'my-plugin' ) );

// Search by label/description
$math_abilities = wp_get_abilities( array( 'search' => 'math' ) );

// Filter by schema presence
$abilities_with_input = wp_get_abilities( array( 'has_input_schema' => true ) );
$abilities_with_output = wp_get_abilities( array( 'has_output_schema' => false ) );
```

### Advanced Filtering

```php
// Filter by meta fields
$advanced_abilities = wp_get_abilities( 
    array(
        'meta_query' => array(
            array(
                'key'   => 'category',
                'value' => 'ai',
            ),
        ),
    )
);

// Combine multiple filters
$filtered_abilities = wp_get_abilities( 
    array(
        'namespace'        => 'my-plugin',
        'search'           => 'process',
        'has_input_schema' => true,
        'meta_query'       => array(
            array(
                'key'     => 'level',
                'compare' => 'EXISTS',
            ),
        ),
    )
);
```

## Available Filter Parameters

### `namespace`
Filter abilities by namespace prefix.
- **Type:** `string`
- **Example:** `'my-plugin'` matches `'my-plugin/ability-name'`

### `search`
Search abilities by label or description text (case-insensitive).
- **Type:** `string`
- **Example:** `'math'` matches abilities with "Math" in label or description

### `has_input_schema`
Filter by presence of input schema.
- **Type:** `boolean`
- **Example:** `true` returns only abilities with input schema defined

### `has_output_schema`
Filter by presence of output schema.
- **Type:** `boolean`
- **Example:** `false` returns only abilities without output schema

### `meta_query`
Filter by meta field values using WordPress-style meta queries.
- **Type:** `array`
- **Supported compare operators:**
  - `'='` or `'=='` - Exact match (default)
  - `'!='` - Not equal
  - `'EXISTS'` - Meta key exists
  - `'NOT EXISTS'` - Meta key does not exist
  - `'IN'` - Value is in array
  - `'NOT IN'` - Value is not in array

#### Meta Query Examples

```php
// Exact match
$abilities = wp_get_abilities( 
    array(
        'meta_query' => array(
            array(
                'key'   => 'category',
                'value' => 'ai',
            ),
        ),
    )
);

// Check if meta key exists
$abilities = wp_get_abilities( 
    array(
        'meta_query' => array(
            array(
                'key'     => 'premium',
                'compare' => 'EXISTS',
            ),
        ),
    )
);

// Value in array
$abilities = wp_get_abilities( 
    array(
        'meta_query' => array(
            array(
                'key'     => 'category',
                'value'   => array( 'ai', 'ml', 'nlp' ),
                'compare' => 'IN',
            ),
        ),
    )
);
```

## REST API Filtering

The REST API also supports filtering via query parameters:

### Endpoints

- `GET /wp-json/wp/v2/abilities` - List abilities with optional filtering
- `GET /wp-json/wp/v2/abilities/{name}` - Get specific ability

### Query Parameters

All the same filtering parameters are available via REST API:

```bash
# Filter by namespace
GET /wp-json/wp/v2/abilities?namespace=my-plugin

# Search abilities
GET /wp-json/wp/v2/abilities?search=math

# Filter by schema presence
GET /wp-json/wp/v2/abilities?has_input_schema=true

# Combine filters with pagination
GET /wp-json/wp/v2/abilities?namespace=my-plugin&has_input_schema=true&per_page=10&page=1
```

## Using WP_Abilities_Query Class Directly

For advanced use cases, you can use the `WP_Abilities_Query` class directly:

```php
$query = new WP_Abilities_Query( 
    array(
        'namespace' => 'my-plugin',
        'search'    => 'process',
    )
);

$abilities = $query->get_abilities();
```

## Performance Considerations

- Filtering is performed in memory after loading all abilities
- For large numbers of abilities, consider using pagination with REST API
- Meta queries with complex conditions may impact performance
- Use specific filters (namespace, schema presence) for better performance over broad searches

## Examples

### Plugin Integration

```php
// Register abilities with meaningful meta data
add_action( 'abilities_api_init', function() {
    wp_register_ability( 
        'my-plugin/text-analyzer',
        array(
            'label'            => 'Text Analyzer',
            'description'      => 'Analyzes text for sentiment and keywords',
            'input_schema'     => array( 'type' => 'string' ),
            'output_schema'    => array( 'type' => 'object' ),
            'execute_callback' => 'my_plugin_analyze_text',
            'meta'             => array(
                'category' => 'ai',
                'level'    => 'advanced',
                'premium'  => true,
            ),
        )
    );
});

// Later, filter your plugin's abilities
$my_abilities = wp_get_abilities( array( 'namespace' => 'my-plugin' ) );
```

### Admin Interface

```php
// Build admin interface with filtering
function display_abilities_admin() {
    $category = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';
    
    $args = array();
    if ( $category ) {
        $args['meta_query'] = array(
            array(
                'key'   => 'category',
                'value' => $category,
            ),
        );
    }
    if ( $search ) {
        $args['search'] = $search;
    }
    
    $abilities = wp_get_abilities( $args );
    
    foreach ( $abilities as $ability ) {
        echo '<div>' . esc_html( $ability->get_label() ) . '</div>';
    }
}
```