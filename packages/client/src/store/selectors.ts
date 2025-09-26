/**
 * WordPress dependencies
 */
import { createSelector } from '@wordpress/data';

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
export const getAbilities = createSelector(
	( state: AbilitiesState ): Ability[] => {
		return Object.values( state.abilitiesByName );
	},
	( state: AbilitiesState ) => [ state.abilitiesByName ]
);

/**
 * Returns a specific ability by name.
 *
 * @param state Store state.
 * @param name  Ability name.
 * @return Ability object or null if not found.
 */
export function getAbility(
	state: AbilitiesState,
	name: string
): Ability | null {
	return state.abilitiesByName[ name ] || null;
}
