Add this near the top after the main heading:

````md
## Quick Start

```php
// Register a custom ability
register_ability(
    'my_plugin_edit_data',
    array(
        'label'       => __( 'Edit plugin data', 'my-plugin' ),
        'description' => __( 'Allows editing of My Plugin data.', 'my-plugin' ),
    )
);

// Check if a user has the ability
if ( user_can( get_current_user_id(), 'my_plugin_edit_data' ) ) {
    // Perform restricted action
}
```
````
