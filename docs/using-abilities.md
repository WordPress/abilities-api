# Using Abilities

After registering abilities, you can check them in your code to control access.

## Check if Current User Has Ability

```php

if ( user_can( get_current_user_id(), 'my_plugin_edit_data' ) ) {
    // Perform action
} else {
    wp_die( __( 'You do not have permission to do this.', 'my-plugin' ) );
}
```

```

```Abilities can be assigned via:

WordPress role management functions

Custom role editing plugins

Direct database queries (not recommended unless necessary)

Removing Abilities :

remove_ability( 'my_plugin_edit_data' );
