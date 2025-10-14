/**
 * WordPress dependencies
 */
import { createSelector } from '@wordpress/data';

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
export const getAbilities = createSelector(
	( state: AbilitiesState, { category }: AbilitiesQueryArgs = {} ): Ability[] => {
		const abilities = Object.values( state.abilitiesByName );
		if ( category ) {
			return abilities.filter(
				( ability ) => ability.category === category
			);
		}
		return abilities;
	},
	( state: AbilitiesState, category?: string ) => [
		state.abilitiesByName,
		category,
	]
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
