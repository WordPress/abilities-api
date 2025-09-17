/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

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
export function receiveAbilities(abilities: Ability[]) {
	return {
		type: RECEIVE_ABILITIES,
		abilities,
	};
}

/**
 * Registers an ability in the store.
 *
 * This action validates the ability before registration. If validation fails,
 * an error will be thrown.
 *
 * @param ability The ability to register.
 * @return Action object or function.
 * @throws {Error} If validation fails.
 */
export function registerAbility(ability: Ability) {
	// @ts-expect-error - registry types are not yet available
	return ({ select, dispatch }) => {
		if (!ability.name) {
			throw new Error(__('Ability name is required'));
		}

		// Validate name format matches server implementation
		if (!/^[a-z0-9-]+\/[a-z0-9-]+$/.test(ability.name)) {
			throw new Error(
				__(
					'Ability name must be a string containing a namespace prefix, i.e. "my-plugin/my-ability". It can only contain lowercase alphanumeric characters, dashes and the forward slash.'
				)
			);
		}

		if (!ability.label) {
			throw new Error(
				__(`Ability "${ability.name}" must have a label`)
			);
		}

		if (!ability.description) {
			throw new Error(
				__(`Ability "${ability.name}" must have a description`)
			);
		}

		// Client-side abilities must have a callback
		if (ability.callback && typeof ability.callback !== 'function') {
			throw new Error(
				__(
					`Ability "${ability.name}" has an invalid callback. Callback must be a function`
				)
			);
		}

		// Check if ability is already registered
		const existingAbility = select.getAbility(ability.name);
		if (existingAbility) {
			throw new Error(
				__(`Ability "${ability.name}" is already registered`)
			);
		}

		// All validation passed, dispatch the registration action
		dispatch({
			type: REGISTER_ABILITY,
			ability,
		});
	};
}

/**
 * Returns an action object used to unregister a client-side ability.
 *
 * @param name The name of the ability to unregister.
 * @return Action object.
 */
export function unregisterAbility(name: string) {
	return {
		type: UNREGISTER_ABILITY,
		name,
	};
}
