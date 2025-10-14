# WordPress Abilities API Client

Client library for the WordPress Abilities API, providing a standardized way to discover and execute WordPress capabilities.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [API Reference](#api-reference)
- [Development and Testing](#development-and-testing)

## Installation

The client is currently available as a part of the Composer package.

### As a WordPress Script

When the Abilities API is installed, the client is automatically registered and enqueue in the admin.

## Usage

```javascript
// In your WordPress plugin or theme JavaScript
const { getAbilities, getAbility, executeAbility } = wp.abilities;
// or import { getAbilities, getAbility, executeAbility } from '@wordpress/abilities'; depending on your setup

// Get all abilities
const abilities = await getAbilities();

// Get a specific ability
const ability = await getAbility( 'my-plugin/my-ability' );

// Execute an ability
const result = await executeAbility( 'my-plugin/my-ability', {
  param1: 'value1',
  param2: 'value2',
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

#### `getAbilities( args: AbilitiesQueryArgs = {} ): Promise<Ability[]>`

Returns all registered abilities. Optionally filter by category slug. Automatically handles pagination to fetch all abilities across multiple pages if needed.

```javascript
// Get all abilities
const abilities = await getAbilities();
console.log( `Found ${ abilities.length } abilities` );

// Get abilities in a specific category
const dataAbilities = await getAbilities( { category: 'data-retrieval' } );
console.log( `Found ${ dataAbilities.length } data retrieval abilities` );
```

#### `getAbility( name: string ): Promise<Ability | null>`

Returns a specific ability by name, or null if not found.

```javascript
const ability = await getAbility( 'my-plugin/create-post' );
if ( ability ) {
  console.log( `Found ability: ${ ability.label }` );
}
```

#### `registerAbility( ability: Ability ): void`

Registers a client-side ability. Client abilities are executed locally in the browser and must include a callback function and a valid category.

```javascript
import { registerAbility } from '@wordpress/abilities';

registerAbility( {
  name: 'my-plugin/navigate',
  label: 'Navigate to URL',
  description: 'Navigates to a URL within WordPress admin',
  category: 'navigation',
  input_schema: {
    type: 'object',
    properties: {
      url: { type: 'string' },
    },
    required: [ 'url' ],
  },
  callback: async ( { url } ) => {
    window.location.href = url;
    return { success: true };
  },
} );
```

#### `executeAbility( name: string, input?: Record<string, any> ): Promise<any>`

Executes an ability with optional input parameters. The HTTP method is automatically determined based on the ability's annotations:

- `readonly` abilities use GET (read-only operations)
- regular abilities use POST (write operations)

```javascript
// Execute a read-only ability (GET)
const data = await executeAbility( 'my-plugin/get-data', {
  id: 123,
} );

// Execute a regular ability (POST)
const result = await executeAbility( 'my-plugin/create-item', {
  title: 'New Item',
  content: 'Item content',
} );
```

### Store Selectors

When using with `@wordpress/data`:

- `getAbilities( args: AbilitiesQueryArgs = {} )` - Returns all abilities from the store, optionally filtered by query arguments
- `getAbility( name: string )` - Returns a specific ability from the store

```javascript
import { useSelect } from '@wordpress/data';
import { store as abilitiesStore } from '@wordpress/abilities';

function MyComponent() {
  // Get all abilities
  const allAbilities = useSelect(
    ( select ) => select( abilitiesStore ).getAbilities(),
    []
  );

  // Get abilities in a specific category
  const dataAbilities = useSelect(
    ( select ) =>
      select( abilitiesStore ).getAbilities( { category: 'data-retrieval' } ),
    []
  );

  return (
    <div>
      <h2>All Abilities ({ allAbilities.length })</h2>
      <h2>Data Retrieval Abilities</h2>
      <ul>
        { dataAbilities.map( ( ability ) => (
          <li key={ ability.name }>{ ability.label }</li>
        ) ) }
      </ul>
    </div>
  );
}
```

## Development and Testing

For development and contributing guidelines, see [CONTRIBUTING.md](../../CONTRIBUTING.md).
