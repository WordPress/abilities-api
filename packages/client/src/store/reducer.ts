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

/**
 * Valid keys for an Ability object.
 * Used to filter out non-standard properties from server responses.
 */
const ABILITY_KEYS = [
	'name',
	'label',
	'description',
	'input_schema',
	'output_schema',
	'annotations',
	'meta',
	'callback',
	'permissionCallback',
] as const;

/**
 * Filters an ability object to only include valid properties.
 * This ensures consistent shape regardless of source (server/client).
 *
 * @param ability Raw ability object that may contain extra properties.
 * @return Filtered ability with only valid properties.
 */
function filterAbility( ability: any ): Ability {
	return Object.keys( ability )
		.filter(
			( key ) =>
				ABILITY_KEYS.includes( key as any ) &&
				ability[ key ] !== undefined
		)
		.reduce(
			( obj, key ) => ( { ...obj, [ key ]: ability[ key ] } ),
			{} as Ability
		);
}

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
				newState[ ability.name ] = filterAbility( ability );
			} );
			return newState;
		}
		case REGISTER_ABILITY: {
			if ( ! action.ability ) {
				return state;
			}
			return {
				...state,
				[ action.ability.name ]: filterAbility( action.ability ),
			};
		}
		case UNREGISTER_ABILITY: {
			if ( ! action.name || ! state[ action.name ] ) {
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
