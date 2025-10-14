/**
 * Internal dependencies
 */
import type { Ability, AbilitiesQueryArgs, AbilitiesState } from '../types';
/**
 * Returns all registered abilities.
 * Optionally filters by query arguments.
 *
 * @param state Store state.
 * @param args  Optional query arguments to filter. Defaults to empty object.
 * @return Array of abilities.
 */
export declare const getAbilities: ((state: AbilitiesState, { category }?: AbilitiesQueryArgs) => Ability[]) & import("rememo").EnhancedSelector;
/**
 * Returns a specific ability by name.
 *
 * @param state Store state.
 * @param name  Ability name.
 * @return Ability object or null if not found.
 */
export declare function getAbility(state: AbilitiesState, name: string): Ability | null;
//# sourceMappingURL=selectors.d.ts.map