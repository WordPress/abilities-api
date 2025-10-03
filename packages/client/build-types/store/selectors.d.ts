/**
 * Internal dependencies
 */
import type { Ability, AbilitiesState } from '../types';
/**
 * Returns all registered abilities.
 *
 * @param state Store state.
 * @return Array of abilities.
 */
export declare const getAbilities: ((state: AbilitiesState) => Ability[]) & import("rememo").EnhancedSelector;
/**
 * Returns a specific ability by name.
 *
 * @param state Store state.
 * @param name  Ability name.
 * @return Ability object or null if not found.
 */
export declare function getAbility(state: AbilitiesState, name: string): Ability | null;
//# sourceMappingURL=selectors.d.ts.map