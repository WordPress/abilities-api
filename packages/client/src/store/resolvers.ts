/**
 * WordPress dependencies
 */
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ENTITY_KIND, ENTITY_NAME } from './constants';
import { receiveAbilities } from './actions';

/**
 * Resolver for getAbilities selector.
 * Fetches all abilities from the server.
 */
export function getAbilities() {
	// @ts-expect-error - registry types are not yet available
	return async ({ dispatch, registry }) => {
		const abilities = await registry
			.resolveSelect(coreStore)
			.getEntityRecords(ENTITY_KIND, ENTITY_NAME, {
				per_page: -1,
			});

		dispatch(receiveAbilities(abilities || []));
	};
}

/**
 * Resolver for getAbility selector.
 * Fetches a specific ability from the server if not already in store.
 *
 * @param name Ability name.
 */
export function getAbility(name: string) {
	// @ts-expect-error - registry types are not yet available
	return async ({ dispatch, registry, select }) => {
		// Check if ability already exists in store (i.e. client ability or already fetched)
		const existingAbility = select.getAbility(name);
		if (existingAbility) {
			return;
		}

		try {
			const ability = await registry
				.resolveSelect(coreStore)
				.getEntityRecord(ENTITY_KIND, ENTITY_NAME, name);

			if (ability) {
				dispatch(receiveAbilities([ability]));
			}
		} catch (error) {
			// If ability doesn't exist ore return, we'll return null from the selector
			// eslint-disable-next-line no-console
			console.debug(`Ability not found: ${name}`);
		}
	};
}
