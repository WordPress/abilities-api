# Registering Abilities

The Abilities API allows you to define new permissions in WordPress.

## Basic Example

```php

register_ability(
    'my_plugin_edit_data',
    array(
        'label'       => __( 'Edit My Plugin Data', 'my-plugin' ),
        'description' => __( 'Allows editing of data in My Plugin.', 'my-plugin' ),
    )
);
```

Next Step : **Parameters**

Name (string) – Unique identifier for the ability.

Args (array) – Associative array with:

label (string) – Human-readable name.

description (string) – Short explanation.

Tip: Always prefix your ability names to avoid conflicts.
