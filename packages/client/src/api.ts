/**
 * WordPress dependencies
 */
import { resolveSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { store } from './store';
import type { Ability, AbilityInput, AbilityOutput } from './types';

/**
 * Get all available abilities.
 *
 * @return Promise resolving to array of abilities.
 */
export async function getAbilities(): Promise< Ability[] > {
	return await resolveSelect( store ).getAbilities();
}

/**
 * Get a specific ability by name.
 *
 * @param name The ability name.
 * @return Promise resolving to the ability or null if not found.
 */
export async function getAbility( name: string ): Promise< Ability | null > {
	return await resolveSelect( store ).getAbility( name );
}

/**
 * Execute an ability.
 *
 * Uses apiFetch since this is a custom action endpoint, not a standard REST resource.
 * The method (GET or POST) is determined by the ability's type metadata.
 *
 * @param name  The ability name.
 * @param input Optional input parameters for the ability.
 * @return Promise resolving to the ability execution result.
 * @throws Error if the ability is not found.
 */
export async function executeAbility(
	name: string,
	input: AbilityInput = null
): Promise< AbilityOutput > {
	const ability = await getAbility( name );
	if ( ! ability ) {
		throw new Error( `Ability not found: ${ name }` );
	}

	const isResource = ability.meta?.type === 'resource';
	const method = isResource ? 'GET' : 'POST';

	let path = `/wp/v2/abilities/${ name }/run`;
	const options: {
		method: string;
		data?: { input: AbilityInput };
	} = {
		method,
	};

	if ( method === 'GET' && input !== null ) {
		// For GET requests, pass the input directly
		path = addQueryArgs( path, { input } );
	} else if ( method === 'POST' && input !== null ) {
		options.data = { input };
	}

	try {
		return await apiFetch< AbilityOutput >( {
			path,
			...options,
		} );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( `Error executing ability ${ name }:`, error );
		throw error;
	}
}
