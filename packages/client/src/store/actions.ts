/**
 * Internal dependencies
 */
import type { Ability } from '../types';
import { RECEIVE_ABILITIES } from './constants';

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
