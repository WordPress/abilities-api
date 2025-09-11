/**
 * WordPress dependencies
 */
import { combineReducers } from '@wordpress/data';

/**
 * Internal dependencies
 */
import type { Ability } from '../types';
import {
	RECEIVE_ABILITIES,
	REGISTER_ABILITY,
	UNREGISTER_ABILITY,
} from './constants';

interface AbilitiesAction {
	type: string;
	abilities?: Ability[];
	ability?: Ability;
	name?: string;
}

const DEFAULT_STATE: Record< string, Ability > = {};

/**
 * Reducer managing the abilities by name.
 *
 * @param state  Current state.
 * @param action Dispatched action.
 * @return New state.
 */
function abilitiesByName(
	state: Record< string, Ability > = DEFAULT_STATE,
	action: AbilitiesAction
): Record< string, Ability > {
	switch ( action.type ) {
		case RECEIVE_ABILITIES: {
			if ( ! action.abilities ) {
				return state;
			}
			const newState = { ...state };
			action.abilities.forEach( ( ability ) => {
				newState[ ability.name ] = ability;
			} );
			return newState;
		}
		case REGISTER_ABILITY: {
			if ( ! action.ability ) {
				return state;
			}
			const clientAbility = {
				...action.ability,
				location: 'client' as const,
			};
			return {
				...state,
				[ clientAbility.name ]: clientAbility,
			};
		}
		case UNREGISTER_ABILITY: {
			if ( ! action.name || ! state[ action.name ] ) {
				return state;
			}
			// Only allow unregistering client abilities
			if ( state[ action.name ].location !== 'client' ) {
				// eslint-disable-next-line no-console
				console.warn(
					`Cannot unregister server-side ability: ${ action.name }`
				);
				return state;
			}
			const newState = { ...state };
			delete newState[ action.name ];
			return newState;
		}
		default:
			return state;
	}
}

export default combineReducers( {
	abilitiesByName,
} );
