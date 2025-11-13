# REST API Reference

The WordPress Abilities API provides REST endpoints that allow external systems to discover and execute abilities via HTTP requests.

## User access

Access to all Abilities REST API endpoints requires an authenticated user (see the [Authentication](#authentication) section). Access to execute individual Abilities is restricted based on the `permission_callback()` of the Ability.

## Controlling REST API Exposure

By default, registered abilities are **not** exposed via the REST API. You can control whether an individual ability appears in the REST API by using the `show_in_rest` meta when registering the ability:

- `show_in_rest => true`: The ability is listed in REST API responses and can be executed via REST endpoints.
- `show_in_rest => false` (default): The ability is hidden from REST API listings and cannot be executed via REST endpoints. The ability remains available for internal PHP usage via `wp_execute_ability()`.

Abilities with meta `show_in_rest => false` will return a `rest_ability_not_found` error if accessed via REST endpoints.

## Schema

The Abilities API endpoints are available under the `/wp-abilities/v1` namespace.

### Ability Object

Abilities are represented in JSON with the following structure:

```json
{
  "name": "my-plugin/get-site-info",
  "label": "Get Site Information",
  "description": "Retrieves basic information about the WordPress site.",
  "category": "site-information",
  "output_schema": {
    "type": "object",
    "properties": {
      "name": {
        "type": "string",
        "description": "Site name"
      },
      "url": {
        "type": "string",
        "format": "uri",
        "description": "Site URL"
      }
    }
  },
  "meta": {
    "annotations": {
      "instructions": "",
      "readonly": true,
      "destructive": false,
      "idempotent": false
    }
  }
}
```

## List Abilities

### Definition

`GET /wp-abilities/v1/abilities`

### Arguments

- `page` _(integer)_: Current page of the collection. Default: `1`.
- `per_page` _(integer)_: Maximum number of items to return per page. Default: `50`, Maximum: `100`.
- `category` _(string)_: Filter abilities by category slug.

### Example Request

```bash
curl https://example.com/wp-json/wp-abilities/v1/abilities
```

### Example Response

```json
[
  {
    "name": "my-plugin/get-site-info",
    "label": "Get Site Information",
    "description": "Retrieves basic information about the WordPress site.",
    "category": "site-information",
    "output_schema": {
      "type": "object",
      "properties": {
        "name": {
          "type": "string",
          "description": "Site name"
        },
        "url": {
          "type": "string",
          "format": "uri",
          "description": "Site URL"
        }
      }
    },
    "meta": {
      "annotations": {
        "instructions": "",
        "readonly": false,
        "destructive": true,
        "idempotent": false
      }
    }
  }
]
```

## List Categories

### Definition

`GET /wp-abilities/v1/categories`

### Arguments

- `page` _(integer)_: Current page of the collection. Default: `1`.
- `per_page` _(integer)_: Maximum number of items to return per page. Default: `50`, Maximum: `100`.

### Example Request

```bash
curl -u 'USERNAME:APPLICATION_PASSWORD' \
  https://example.com/wp-json/wp-abilities/v1/categories
```

### Example Response

```json
[
  {
    "slug": "data-retrieval",
    "label": "Data Retrieval",
    "description": "Abilities that retrieve and return data from the WordPress site.",
    "meta": {},
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wp-abilities/v1/categories/data-retrieval"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wp-abilities/v1/categories"
        }
      ],
      "abilities": [
        {
          "href": "https://example.com/wp-json/wp-abilities/v1/?category=data-retrieval"
        }
      ]
    }
  }
]
```

## Retrieve a Category

### Definition

`GET /wp-abilities/v1/categories/{slug}`

### Arguments

- `slug` _(string)_: The unique slug of the category.

### Example Request

```bash
curl -u 'USERNAME:APPLICATION_PASSWORD' \
  https://example.com/wp-json/wp-abilities/v1/categories/data-retrieval
```

### Example Response

```json
{
  "slug": "data-retrieval",
  "label": "Data Retrieval",
  "description": "Abilities that retrieve and return data from the WordPress site.",
  "meta": {},
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wp-abilities/v1/categories/data-retrieval"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wp-abilities/v1/categories"
      }
    ],
    "abilities": [
      {
        "href": "https://example.com/wp-json/wp-abilities/v1?category=data-retrieval"
      }
    ]
  }
}
```

## Retrieve an Ability

### Definition

`GET /wp-abilities/v1/(?P<namespace>[a-z0-9-]+)/(?P<ability>[a-z0-9-]+)`

### Arguments

- `namespace` _(string)_: The namespace part of the ability name.
- `ability` _(string)_: The ability name part.

### Example Request

```bash
curl https://example.com/wp-json/wp-abilities/v1/my-plugin/get-site-info
```

### Example Response

```json
{
  "name": "my-plugin/get-site-info",
  "label": "Get Site Information",
  "description": "Retrieves basic information about the WordPress site.",
  "category": "site-information",
  "output_schema": {
    "type": "object",
    "properties": {
      "name": {
        "type": "string",
        "description": "Site name"
      },
      "url": {
        "type": "string",
        "format": "uri",
        "description": "Site URL"
      }
    }
  },
  "meta": {
    "annotations": {
      "instructions": "",
      "readonly": true,
      "destructive": false,
      "idempotent": false
    }
  }
}
```

## Execute an Ability

Abilities are executed via the `/run` endpoint. The required HTTP method depends on the ability's `readonly` annotation:

- **Read-only abilities** (`readonly: true`) must use **GET**
- **Regular abilities** (default) must use **POST**

This distinction ensures read-only operations use safe HTTP methods that can be cached and don't modify server state.

### Definition

`GET|POST /wp-abilities/v1/(?P<namespace>[a-z0-9-]+)/(?P<ability>[a-z0-9-]+)/run`

### Arguments

- `namespace` _(string)_: The namespace part of the ability name.
- `ability` _(string)_: The ability name part.
- `input` _(integer|number|boolean|string|array|object|null)_: Optional input data for the ability as defined by its input schema.
  - For **GET requests**: pass as `input` query parameter (URL-encoded JSON)
  - For **POST requests**: pass in JSON body

### Example Request (Read-only, GET)

```bash
# No input
curl https://example.com/wp-json/wp-abilities/v1/my-plugin/get-site-info/run

# With input (URL-encoded)
curl "https://example.com/wp-json/wp-abilities/v1/my-plugin/get-user-info/run?input=%7B%22user_id%22%3A1%7D"
```

### Example Request (Regular, POST)

```bash
# No input
curl -X POST https://example.com/wp-json/wp-abilities/v1/my-plugin/create-draft/run

# With input
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"input":{"option_name":"blogname","option_value":"New Site Name"}}' \
  https://example.com/wp-json/wp-abilities/v1/my-plugin/update-option/run
```

### Example Response (Success)

```json
{
  "name": "My WordPress Site",
  "url": "https://example.com"
}
```

### Example Response (Error)

```json
{
  "code": "ability_invalid_permissions",
  "message": "Ability \"my-plugin/update-option\" does not have necessary permission.",
  "data": {
    "status": 403
  }
}
```

## Authentication

The Abilities API supports all WordPress REST API authentication methods:

- Cookie authentication (same-origin requests)
- Application passwords (recommended for external access)
- Custom authentication plugins

### Using Application Passwords

```bash
curl -u 'USERNAME:APPLICATION_PASSWORD' \
  https://example.com/wp-json/wp-abilities/v1
```

## Error Responses

The API returns standard WordPress REST API error responses with these common codes:

- `ability_missing_input_schema` – the ability requires input but none was provided.
- `ability_invalid_input` - input validation failed according to the ability's schema.
- `ability_invalid_permissions` - current user lacks permission to execute the ability.
- `ability_invalid_output` - output validation failed according to the ability's schema.
- `ability_invalid_execute_callback` - the ability's execute callback is not callable.
- `rest_ability_not_found` - the requested ability is not registered.
- `rest_ability_category_not_found` - the requested category is not registered.
- `rest_ability_invalid_method` - the requested HTTP method is not allowed for executing the selected ability (e.g., using GET on a read-only ability, or POST on a regular ability).
- `rest_ability_cannot_execute` - the ability cannot be executed due to insufficient permissions.
