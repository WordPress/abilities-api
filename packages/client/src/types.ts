/**
 * WordPress Abilities API Types
 */

/**
 * Callback function for client-side abilities.
 */
export type AbilityCallback = (
	input: AbilityInput
) => AbilityOutput | Promise< AbilityOutput >;

/**
 * Represents an ability in the WordPress Abilities API.
 *
 * @see WP_Ability
 */
export interface Ability {
	/**
	 * The unique name/identifier of the ability, with its namespace.
	 * Example: 'my-plugin/my-ability'
	 * @see WP_Ability::get_name()
	 */
	name: string;

	/**
	 * The human-readable label for the ability.
	 * @see WP_Ability::get_label()
	 */
	label: string;

	/**
	 * The detailed description of the ability.
	 * @see WP_Ability::get_description()
	 */
	description: string;

	/**
	 * JSON Schema for the ability's input parameters.
	 * @see WP_Ability::get_input_schema()
	 */
	input_schema?: Record< string, any >;

	/**
	 * JSON Schema for the ability's output format.
	 * @see WP_Ability::get_output_schema()
	 */
	output_schema?: Record< string, any >;

	/**
	 * Where the ability is executed.
	 * 'server' means it's executed via REST API on the server.
	 * 'client' means it's executed locally in the browser.
	 * Defaults to 'server'.
	 */
	location?: 'server' | 'client';

	/**
	 * Callback function for client-side abilities.
	 * Only used when location is 'client'.
	 */
	callback?: AbilityCallback;

	/**
	 * Metadata about the ability.
	 * @see WP_Ability::get_meta()
	 */
	meta?: {
		/**
		 * The type of ability - 'resource' uses GET, 'tool' uses POST.
		 */
		type?: 'resource' | 'tool';
		[ key: string ]: any;
	};
}

/**
 * The state shape for the abilities store.
 */
export interface AbilitiesState {
	/**
	 * Map of ability names to ability objects.
	 */
	abilitiesByName: Record< string, Ability >;
}

/**
 * Input parameters for ability execution.
 * Can be any JSON-serializable value: primitive, array, object, or null.
 */
export type AbilityInput = any;

/**
 * Result from ability execution.
 * The actual shape depends on the ability's output schema.
 */
export type AbilityOutput = any;

/**
 * Validation error - just a message string.
 * The Abilities API wraps this with the appropriate error code.
 */
export type ValidationError = string;
