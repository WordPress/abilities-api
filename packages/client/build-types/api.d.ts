import type { Ability, AbilitiesQueryArgs, AbilityInput, AbilityOutput } from './types';
/**
 * Get all available abilities with optional filtering.
 *
 * @param args Optional query arguments to filter. Defaults to empty object.
 * @return Promise resolving to array of abilities.
 */
export declare function getAbilities(args?: AbilitiesQueryArgs): Promise<Ability[]>;
/**
 * Get a specific ability by name.
 *
 * @param name The ability name.
 * @return Promise resolving to the ability or null if not found.
 */
export declare function getAbility(name: string): Promise<Ability | null>;
/**
 * Register a client-side ability.
 *
 * Client abilities are executed locally in the browser and must include
 * a callback function. The ability will be validated by the store action,
 * and an error will be thrown if validation fails.
 *
 * @param  ability The ability definition including callback.
 * @throws {Error} If the ability fails validation.
 *
 * @example
 * ```js
 * registerAbility({
 *   name: 'my-plugin/navigate',
 *   label: 'Navigate to URL',
 *   description: 'Navigates to a URL within WordPress admin',
 *   input_schema: {
 *     type: 'object',
 *     properties: {
 *       url: { type: 'string' }
 *     },
 *     required: ['url']
 *   },
 *   callback: async ({ url }) => {
 *     window.location.href = url;
 *     return { success: true };
 *   }
 * });
 * ```
 */
export declare function registerAbility(ability: Ability): void;
/**
 * Unregister an ability from the store.
 *
 * Remove a client-side ability from the store.
 * Note: This will return an error for server-side abilities.
 *
 * @param name The ability name to unregister.
 */
export declare function unregisterAbility(name: string): void;
/**
 * Execute an ability.
 *
 * Determines whether to execute locally (client abilities) or remotely (server abilities)
 * based on whether the ability has a callback function.
 *
 * @param name  The ability name.
 * @param input Optional input parameters for the ability.
 * @return Promise resolving to the ability execution result.
 * @throws Error if the ability is not found or execution fails.
 */
export declare function executeAbility(name: string, input?: AbilityInput): Promise<AbilityOutput>;
//# sourceMappingURL=api.d.ts.map