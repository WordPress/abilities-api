=== Abilities API ===
Contributors: wordpressdotorg
Tags: abilities, ai, capabilities, rest-api, developer
Requires at least: 6.8
Tested up to: 6.8
Stable tag: 0.3.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A developer-focused framework for registering, exposing and executing "abilities" (capabilities/actions) in WordPress, including REST API endpoints.

== Description ==

The Abilities API plugin provides a lightweight framework for registering and executing abilities in WordPress. An "ability" is a named, self-describing unit of work (for example: `my-plugin/generate-summary`) that includes a label, description, input/output schemas, permission checks and an execution callback.

This plugin is intended for developers and integrations that need a stable programmatic interface to:

- Register discoverable abilities (with metadata) using procedural helpers or the registry classes.
- Expose abilities over REST via standardized endpoints.
- Execute abilities with input validation and permission checks.

It is intentionally focused on developer ergonomics and programmatic integrations. It does not provide a UI for managing roles/capabilities.

== Features ==

- Register abilities programmatically with `wp_register_ability()`.
- Query registered abilities with `wp_get_abilities()` and `wp_get_ability()`.
- Register ability categories via `wp_register_ability_category()`.
- Built-in REST endpoints for listing, fetching and executing abilities (`/wp/v2/abilities`).
- Input normalization, permission callbacks and JSON Schema-style input/output declarations.

== Installation ==

1. Upload the `abilities-api` folder to the `/wp-content/plugins/` directory, or install via your preferred deployment method (Composer, ZIP, etc.).
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Register abilities on the `abilities_api_init` action (see Usage below).

== Usage ==

Register an ability (example):

	add_action( 'abilities_api_init', function() {
		wp_register_ability( 'my-plugin/generate-summary', array(
			'label'              => 'Generate Summary',
			'description'        => 'Generates a short summary from provided text.',
			'category'           => 'content',
			'input_schema'       => array( 'type' => 'object', 'properties' => array( 'text' => array( 'type' => 'string' ) ), 'required' => array( 'text' ) ),
			'output_schema'      => array( 'type' => 'object', 'properties' => array( 'summary' => array( 'type' => 'string' ) ) ),
			'execute_callback'   => function( $input ) {
				// Return result or WP_Error
				return array( 'summary' => wp_trim_words( $input['text'], 25 ) );
			},
			'permission_callback' => function( $input ) {
				return current_user_can( 'edit_posts' );
			},
			'meta'               => array( 'show_in_rest' => true ),
		) );
	} );

Procedural helpers:

- `wp_register_ability( string $name, array $args )` — register an ability.
- `wp_unregister_ability( string $name )` — unregister an ability.
- `wp_get_ability( string $name )` — get a single registered ability object.
- `wp_get_abilities()` — get all registered abilities.
- `wp_register_ability_category( string $slug, array $args )` — register a category.

== REST API ==

The plugin registers REST endpoints under the `wp/v2` namespace with the base `abilities`:

- `GET /wp-json/wp/v2/abilities` — List abilities (supports `page`, `per_page`, `category`). Requires `read` capability.
- `GET /wp-json/wp/v2/abilities/{name}` — Get metadata for a specific ability. Requires `read` capability.
- `GET /wp-json/wp/v2/abilities/{name}/run` — Execute a read-only ability (uses query param `input`).
- `POST /wp-json/wp/v2/abilities/{name}/run` — Execute a non-read-only ability (JSON body `{ "input": ... }`).

Notes:

- An ability must set `meta['show_in_rest']` to a truthy value to be exposed via the REST API.
- Read-only abilities (annotated `readonly`) require GET; mutating abilities require POST. The controllers enforce HTTP method checks and permission callbacks.

Example REST request (execute):

	POST /wp-json/wp/v2/abilities/my-plugin/generate-summary/run
	Content-Type: application/json

	{ "input": { "text": "Long text to summarize..." } }

== Hooks & Filters ==

- `abilities_api_init` — action where abilities should be registered.
- `abilities_api_categories_init` — action where categories can be registered.
- `abilities_api_register_core_abilities` — filter used during bootstrap to control registration of bundled/core abilities.

== Developer Notes ==

- The plugin defines a small set of classes in `includes/` (registry, ability, categories and REST controllers). See `includes/abilities-api.php` and `includes/rest-api/` for details.
- The plugin defines procedural wrapper functions to simplify registering and querying abilities from legacy code or non-namespaced code.

== Changelog ==

= 1.0 =
* Initial version.

== Contributing ==

Contributions, bug reports and feature requests are welcome on GitHub: https://github.com/WordPress/abilities-api 
Please follow the repository's contribution guidelines and run the test suite when adding features.

== Support ==

Open an issue on the plugin's GitHub repository. Include: plugin version, WordPress version, PHP version and a short reproduction or code sample.

