/**
 * WordPress dependencies
 */
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import type { Ability } from '../types';
import { ENTITY_KIND, ENTITY_NAME } from './constants';
import { receiveAbilities } from './actions';

/**
 * Resolver for getAbilities selector.
 * Fetches all abilities from the server.
 *
 * The resolver only fetches once (without category filter) and stores all abilities.
 * Category filtering handled client-side by the selector for better performance
 * and to avoid multiple API requests when filtering by different categories.
 *
 * @param _category Optional category slug
 */
export function getAbilities( _category?: string ) {
	// @ts-expect-error - registry types are not yet available
	return async ( { dispatch, registry, select } ) => {
		const existingAbilities = select.getAbilities();

		// Check if we have any server-side abilities (abilities without callbacks)
		// Client abilities have callbacks and are registered immediately on page load
		// We only want to skip fetching if we've already fetched server abilities
		const hasServerAbilities = existingAbilities.some(
			( ability: Ability ) => ! ability.callback
		);

		if ( hasServerAbilities ) {
			return;
		}

		const abilities = await registry
			.resolveSelect( coreStore )
			.getEntityRecords( ENTITY_KIND, ENTITY_NAME, {
				per_page: -1,
			} );

		dispatch( receiveAbilities( abilities || [] ) );
	};
}

/**
 * Resolver for getAbility selector.
 * Fetches a specific ability from the server if not already in store.
 *
 * @param name Ability name.
 */
export function getAbility( name: string ) {
	// @ts-expect-error - registry types are not yet available
	return async ( { dispatch, registry, select } ) => {
		// Check if ability already exists in store (i.e. client ability or already fetched)
		const existingAbility = select.getAbility( name );
		if ( existingAbility ) {
			return;
		}

		try {
			const ability = await registry
				.resolveSelect( coreStore )
				.getEntityRecord( ENTITY_KIND, ENTITY_NAME, name );

			if ( ability ) {
				dispatch( receiveAbilities( [ ability ] ) );
			}
		} catch ( error ) {
			// If ability doesn't exist ore return, we'll return null from the selector
			// eslint-disable-next-line no-console
			console.debug( `Ability not found: ${ name }` );
		}
	};
}
