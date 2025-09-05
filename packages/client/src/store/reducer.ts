/**
 * WordPress dependencies
 */
import { combineReducers } from '@wordpress/data';

/**
 * Internal dependencies
 */
import type { Ability } from '../types';
import { RECEIVE_ABILITIES } from './constants';

interface AbilitiesAction {
	type: string;
	abilities?: Ability[];
}

const DEFAULT_STATE: Record< string, Ability > = {};

/**
 * Reducer managing the abilities by ID.
 *
 * @param state  Current state.
 * @param action Dispatched action.
 * @return New state.
 */
function abilitiesById(
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
		default:
			return state;
	}
}

export default combineReducers( {
	abilitiesById,
} );
