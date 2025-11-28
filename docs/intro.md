# Introduction & Overview

## What is the Abilities API?

The WordPress Abilities API provides a standardized way to register and discover distinct units of functionality within a WordPress site. These units, called "Abilities", represent specific actions or capabilities that components can perform, with clearly defined inputs, outputs, and permissions.

It acts as a central registry, making it easier for different parts of WordPress, third-party plugins, themes, and external systems (like AI agents) to understand and interact with the capabilities available on a specific site.

## Core Concepts

- **Ability:** A distinct piece of functionality with a unique name following the `namespace/ability-name` pattern. Each ability has a human-readable name and description, input/output definitions (using JSON Schema), a category assignment, optional permissions, and an associated callback function for execution. Each registered Ability is an instance of the `WP_Ability` class.
- **Category:** A way to organize related abilities. Each ability must belong to exactly one category. Categories have a slug, label, and description. Each registered category is an instance of the `WP_Ability_Category` class.
- **Registry:** A central, singleton object (`WP_Abilities_Registry`) that holds all registered abilities. It provides methods for registering, unregistering, finding, and querying abilities. Similarly, `WP_Abilities_Category_Registry` manages all registered categories.
- **Callback:** The PHP function or method executed when an ability is called via `WP_Ability::execute()`.
- **Schema:** JSON Schema definitions for an ability's expected input (`input_schema`) and its returned output (`output_schema`). This allows for validation and helps agents understand how to use the ability.
- **Permission Callback:** An optional function that determines if the current user can execute a specific ability.
- **Namespace:** The first part of an ability name (before the slash), typically matching the plugin or component name that registers the ability.

## Goals and Benefits

- **Standardization:** Provides a single, consistent way to expose site capabilities.
- **Discoverability:** Makes functionality easily discoverable by AI systems and automation tools.
- **Validation:** Built-in input/output validation using JSON Schema ensures data integrity.
- **Security:** Permission callbacks provide fine-grained access control.
- **Extensibility:** Simple registration pattern allows any plugin or theme to expose their capabilities.
- **AI-Friendly:** Machine-readable format enables intelligent automation and AI agent interactions.

## Use Cases

- **AI Integration:** Allow AI agents to discover and interact with site capabilities.
- **Plugin Interoperability:** Enable plugins to discover and use each other's functionality.
- **Automation Tools:** Provide programmatic access to site features.
- **API Documentation:** Self-documenting capabilities with schema validation.
- **Developer Tools:** Standardized way to expose plugin functionality.

## Registration Example

```php
// First, register a category, or use one of the existing categories.
add_action( 'wp_abilities_api_categories_init', 'my_plugin_register_category');
function my_plugin_register_category(){
	wp_register_ability_category( 'site-information', array(
		'label' => __( 'Site Information', 'my-plugin' ),
		'description' => __( 'Abilities that provide information about the WordPress site.', 'my-plugin' ),
	));
}

// Then, register an ability in that category
add_action( 'wp_abilities_api_init', 'my_plugin_register_ability');
function my_plugin_register_ability(){
	wp_register_ability( 'my-plugin/site-info', array(
		'label' => __( 'Site Info', 'my-plugin' ),
		'description' => __( 'Returns information about this WordPress site', 'my-plugin' ),
		'category' => 'site-information',
		'input_schema' => array(),
		'output_schema' => array(
			'type' => 'object',
			'properties' => array(
				'site_name' => array(
					'type' => 'string',
					'description' => __( 'The name of the WordPress site', 'my-plugin' )
				),
				'site_url' => array(
					'type' => 'string',
					'description' => __( 'The URL of the WordPress site', 'my-plugin' )
				),
				'active_theme' => array(
					'type' => 'string',
					'description' => __( 'The active theme of the WordPress site', 'my-plugin' )
				),
				'active_plugins' => array(
					'type' => 'array',
					'items' => array(
						'type' => 'string'
					),
					'description' => __( 'List of active plugins on the WordPress site', 'my-plugin' )
				),
				'php_version' => array(
					'type' => 'string',
					'description' => __( 'The PHP version of the WordPress site', 'my-plugin' )
				),
				'wordpress_version' => array(
					'type' => 'string',
					'description' => __( 'The WordPress version of the site', 'my-plugin' )
				)
			),
		),
		'execute_callback' => 'my_plugin_get_siteinfo',
		'permission_callback' => function( $input ) {
			return current_user_can( 'manage_options' );
		},
    'meta' => array(
      'show_in_rest' => true,
    ),
	) );
}
```

This creates a machine-readable capability that AI systems and automation tools can discover, understand, and execute safely within the bounds of WordPress permissions and validation rules.
