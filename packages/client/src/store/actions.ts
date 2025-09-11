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
 * Returns an action object used to receive abilities into the store.
 *
 * @param abilities Array of abilities to store.
 * @return Action object.
 */
export function receiveAbilities( abilities: Ability[] ) {
	return {
		type: RECEIVE_ABILITIES,
		abilities,
	};
}

/**
 * Returns an action object used to register a client-side ability.
 *
 * @param ability The ability to register.
 * @return Action object.
 */
export function registerAbility( ability: Ability ) {
	return {
		type: REGISTER_ABILITY,
		ability,
	};
}

/**
 * Returns an action object used to unregister a client-side ability.
 *
 * @param name The name of the ability to unregister.
 * @return Action object.
 */
export function unregisterAbility( name: string ) {
	return {
		type: UNREGISTER_ABILITY,
		name,
	};
}
