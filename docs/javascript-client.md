# JavaScript/TypeScript Client

The JavaScript client provides an interface for discovering and executing WordPress Abilities from the browser.

## Overview

The JavaScript client enables frontend code to interact with the Abilities API system. It can:

- Discover all registered abilities on your WordPress site
- Execute server-side PHP abilities
- Register and execute client-side JavaScript abilities

You can read more about installation and setup in the [package readme](../packages/client/README.md).

## Core API Functions

### `getAbilities( args = {} )`

Returns an array of all registered abilities (both server-side and client-side).

**Parameters:** `args` (object, optional) - Query arguments to filter abilities. Supported arguments:

- `category` (string) - Filter abilities by category slug

**Returns:** `Promise<Array>` - Array of ability objects

**Example:**

```javascript
import { getAbilities } from `@wordpress/abilities`;

const abilities = await getAbilities();
console.log(`Found ${abilities.length} abilities`);

// List all abilities
abilities.forEach(ability => {
  console.log(`${ability.name}: ${ability.description}`);
});

// Get abilities in a specific category
const dataAbilities = await getAbilities( { category: 'data-retrieval' } );

console.log( `Found ${ dataAbilities.length } data retrieval abilities` );
```

### getAbility( name )

Retrieves a specific ability by name.

**Parameters:**

- `name` (string) - The ability name (e.g., 'my-plugin/get-posts')

**Returns:** `Promise<Object|null>` - The ability object or null if not found

**Example:**

```javascript
const ability = await getAbility( 'my-plugin/get-site-info' );
if ( ability ) {
  console.log( 'Label:', ability.label );
  console.log( 'Description:', ability.description );
  console.log( 'Input Schema:', ability.input_schema );
}
```

### `getAbilityCategories()`

Returns an array of all registered ability categories.

**Parameters:** None

**Returns:** `Promise<Array>` - Array of category objects

**Example:**

```javascript
const categories = await getAbilityCategories();
console.log( `Found ${ categories.length } categories` );

// List all categories
categories.forEach( ( category ) => {
  console.log( `${ category.label }: ${ category.description }` );
} );
```

### `getAbilityCategory( slug )`

Retrieves a specific category by slug.

**Parameters:**

- `slug` (string) - The category slug (e.g., 'data-retrieval')

**Returns:** `Promise<Object|null>` - The category object or null if not found

**Example:**

```javascript
const category = await getAbilityCategory( 'data-retrieval' );
if ( category ) {
  console.log( 'Label:', category.label );
  console.log( 'Description:', category.description );
}
```

### `executeAbility( name, input = null )`

Executes an ability with the provided input data.

**Parameters:**

- `name` (string) - The ability name
- `input` (any, optional) - Input data for the ability

**Returns:** `Promise<any>` - The ability's output

**Example:**

```javascript
// Execute without input
const siteTitle = await executeAbility( 'my-plugin/get-site-title' );
console.log( 'Site:', siteTitle );

// Execute with input parameters
const posts = await executeAbility( 'my-plugin/get-posts', {
  category: 'news',
  limit: 5,
} );
posts.forEach( ( post ) => console.log( post.title ) );
```

### `registerAbility( ability )`

Registers a client-side ability that runs in the browser.

**Parameters:**

- `ability` (object) - The ability configuration object

**Returns:** `Promise<void>`

**Example:**

```javascript
// showNotification function
const showNotification = ( message ) => {
  new Notification( message );
  return { success: true, displayed: message };
};

// Register a notification ability which calls the showNotification function
await registerAbility( {
  name: 'my-plugin/show-notification',
  label: 'Show Notification',
  description: 'Display a notification message to the user',
  input_schema: {
    type: 'object',
    properties: {
      message: { type: 'string' },
      type: { type: 'string', enum: [ 'success', 'error', 'warning', 'info' ] },
    },
    required: [ 'message' ],
  },
  callback: async ( { message, type = 'info' } ) => {
    // Show browser notification
    if ( ! ( 'Notification' in window ) ) {
      alert( 'This browser does not support desktop notification' );
      return {
        success: false,
        error: 'Browser does not support notifications',
      };
    }
    if ( Notification.permission !== 'granted' ) {
      Notification.requestPermission().then( ( permission ) => {
        if ( permission === 'granted' ) {
          return showNotification( message );
        }
      } );
    }
    return showNotification( message );
  },
  permissionCallback: () => {
    return !! wp.data.select( 'core' ).getCurrentUser();
  },
} );

// Use the registered ability
const result = await executeAbility( 'my-plugin/show-notification', {
  message: 'Hello World!',
  type: 'success',
} );
```

### `unregisterAbility( name )`

Removes a previously registered client-side ability.

**Parameters:**

- `name` (string) - The ability name to unregister

**Returns:** `void`

**Example:**

```javascript
// Unregister an ability
unregisterAbility( 'my-plugin/old-ability' );
```

### `registerAbilityCategory( slug, args )`

Registers a client-side ability category. This is useful when registering client-side abilities that introduce new categories not defined by the server.

**Parameters:**

- `slug` (string) - The category slug (lowercase alphanumeric with dashes only)
- `args` (object) - Category configuration object
  - `label` (string) - Human-readable label for the category
  - `description` (string) - Detailed description of the category
  - `meta` (object, optional) - Optional metadata about the category

**Returns:** `Promise<void>`

**Example:**

```javascript
// Register a new category
await registerAbilityCategory( 'block-editor', {
  label: 'Block Editor',
  description: 'Abilities for interacting with the WordPress block editor',
} );

// Register a category with metadata
await registerAbilityCategory( 'custom-category', {
  label: 'Custom Category',
  description: 'A category for custom abilities',
  meta: {
    priority: 'high',
    icon: 'dashicons-admin-customizer',
  },
} );

// Then register abilities using the new category
await registerAbility( {
  name: 'my-plugin/insert-block',
  label: 'Insert Block',
  description: 'Inserts a block into the editor',
  category: 'block-editor', // Uses the client-registered category
  callback: async ( { blockType } ) => {
    // Implementation
    return { success: true };
  },
} );
```

### `unregisterAbilityCategory( slug )`

Removes a previously registered client-side category.

**Parameters:**

- `slug` (string) - The category slug to unregister

**Returns:** `void`

**Example:**

```javascript
// Unregister a category
unregisterAbilityCategory( 'block-editor' );
```

## Error Handling

All functions return promises that may reject with specific error codes:

```javascript
try {
  const result = await executeAbility( 'my-plugin/restricted-action', input );
  console.log( 'Success:', result );
} catch ( error ) {
  switch ( error.code ) {
    case 'ability_permission_denied':
      console.error( 'Permission denied:', error.message );
      break;
    case 'ability_invalid_input':
      console.error( 'Invalid input:', error.message );
      break;
    case 'rest_ability_not_found':
      console.error( 'Ability not found:', error.message );
      break;
    default:
      console.error( 'Execution failed:', error.message );
  }
}
```
