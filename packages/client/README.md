# WordPress Abilities API Client

Client library for the WordPress Abilities API, providing a standardized way to discover and execute WordPress capabilities.

## Installation

The client is available in two ways:

### 1. As an npm Package (Coming Soon)

The npm package `@wordpress/abilities` is planned for future publication. Once published, you'll be able to install it via:

```bash
npm install @wordpress/abilities
```

The npm package is designed for use with WordPress build tools (`@wordpress/scripts`). It requires the Abilities API plugin to be active in WordPress or via the Composer package, as it references the globally-loaded `wp.abilities` script at runtime.

For now, the client is available through the WordPress script registration below.

### 2. As a WordPress Script (Available Now)

When the Abilities API is installed as a WordPress plugin, the client is automatically registered and available to enqueue:

```php
wp_enqueue_script( 'wp-abilities' );
```

#### For Composer Installations

If you've installed the Abilities API via Composer, you need to register the script first.

```php
// Register the client script (usually in your plugin's init hook)
add_action( 'init', function() {
    if ( function_exists( 'wp_abilities_register_client_script' ) ) {
        // Provide the path and URL to your vendor directory
        $base_path = __DIR__ . '/vendor/wordpress/abilities-api';
        $base_url = plugins_url( 'vendor/wordpress/abilities-api', __FILE__ );

        wp_abilities_register_client_script( $base_path, $base_url );
    }
} );

// Then enqueue it where needed
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_script( 'wp-abilities' );
} );
```

## Usage

```javascript
// In your WordPress plugin or theme JavaScript
const { listAbilities, getAbility, executeAbility } = wp.abilities;
// or import { listAbilities, getAbility, executeAbility } from '@wordpress/abilities'; depending on your setup

// List all abilities
const abilities = await listAbilities();

// Get a specific ability
const ability = await getAbility( 'my-plugin/my-ability' );

// Execute an ability
const result = await executeAbility( 'my-plugin/my-ability', {
    param1: 'value1',
    param2: 'value2'
} );
```

### Using with React and WordPress Data

The client includes a data store that integrates with `@wordpress/data` for use in React components:

```javascript
import { useSelect } from '@wordpress/data';
import { store as abilitiesStore } from '@wordpress/abilities';

function MyComponent() {
    const abilities = useSelect(
        ( select ) => select( abilitiesStore ).getAbilities(),
        []
    );

    const specificAbility = useSelect(
        ( select ) => select( abilitiesStore ).getAbility( 'my-plugin/my-ability' ),
        []
    );

    return (
        <div>
            <h2>All Abilities</h2>
            <ul>
                { abilities.map( ( ability ) => (
                    <li key={ ability.name }>
                        <strong>{ ability.label }</strong>: { ability.description }
                    </li>
                ) ) }
            </ul>
        </div>
    );
}
```

## API Reference

### Functions

#### `listAbilities(): Promise<Ability[]>`

Returns all registered abilities. Automatically handles pagination to fetch all abilities across multiple pages if needed.

```javascript
const abilities = await listAbilities();
console.log( `Found ${abilities.length} abilities` );
```

#### `getAbility(name: string): Promise<Ability | null>`

Returns a specific ability by name, or null if not found.

```javascript
const ability = await getAbility( 'my-plugin/create-post' );
if ( ability ) {
    console.log( `Found ability: ${ability.label}` );
}
```

#### `executeAbility(name: string, input?: Record<string, any>): Promise<any>`

Executes an ability with optional input parameters. The HTTP method is automatically determined based on the ability's type:

- `resource` type abilities use GET (read-only operations)
- `tool` type abilities use POST (write operations)

```javascript
// Execute a resource ability (GET)
const data = await executeAbility( 'my-plugin/get-data', {
    id: 123
} );

// Execute a tool ability (POST)
const result = await executeAbility( 'my-plugin/create-item', {
    title: 'New Item',
    content: 'Item content'
} );
```

### Store Selectors

When using with `@wordpress/data`:

- `getAbilities()` - Returns all abilities from the store
- `getAbility(name)` - Returns a specific ability from the store

## Development

```bash
# Install dependencies
npm install

# Build the package
npm run build

# Run linting
npm run lint:js

# Type checking
npm run typecheck
```
