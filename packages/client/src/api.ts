/**
 * WordPress dependencies
 */
import { dispatch, resolveSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { sprintf, __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { store } from './store';
import type { Ability, AbilityInput, AbilityOutput } from './types';
import { validateValueFromSchema } from './validation';

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
 * Register a client-side ability.
 *
 * Client abilities are executed locally in the browser and must include
 * a callback function.
 *
 * @param ability The ability definition including callback.
 * @throws Error if ability is invalid or missing required fields.
 *
 * @example
 * ```js
 * registerAbility({
 *   name: 'my-plugin/navigate',
 *   label: 'Navigate to URL',
 *   description: 'Navigates to a URL within WordPress admin',
 *   location: 'client',
 *   input_schema: {
 *     type: 'object',
 *     properties: {
 *       url: { type: 'string' }
 *     },
 *     required: ['url']
 *   },
 *   callback: async ({ url }) => {
 *     window.location.href = url;
 *     return { success: true };
 *   }
 * });
 * ```
 */
export function registerAbility( ability: Ability ): void {
	if ( ! ability.name ) {
		throw new Error( 'Ability name is required' );
	}
	if ( ! ability.label ) {
		throw new Error( 'Ability label is required' );
	}
	if ( ! ability.description ) {
		throw new Error( 'Ability description is required' );
	}

	// Check if it's a server ability and reject it
	if ( ability.location === 'server' ) {
		throw new Error( 'Server abilities cannot be registered via registerAbility' );
	}

	// Now we know it's supposed to be a client ability
	if ( ! ability.callback || typeof ability.callback !== 'function' ) {
		throw new Error(
			'Client abilities must include a callback function'
		);
	}

	dispatch( store ).registerAbility( ability );
}

/**
 * Unregister an ability from the store.
 *
 * Remove a client-side ability from the store.
 * Note: This will return an error for server-side abilities.
 *
 * @param name The ability name to unregister.
 */
export function unregisterAbility( name: string ): void {
	dispatch( store ).unregisterAbility( name );
}

/**
 * Execute a client-side ability.
 *
 * @param ability The ability to execute.
 * @param input   Input parameters for the ability.
 * @return Promise resolving to the ability execution result.
 * @throws Error if validation fails or execution errors.
 */
async function executeClientAbility(
	ability: Ability,
	input: AbilityInput
): Promise< AbilityOutput > {
	if ( ! ability.callback ) {
		throw new Error(
			`Client ability ${ ability.name } is missing callback function`
		);
	}

	if ( ability.input_schema ) {
		const inputValidation = validateValueFromSchema(
			input,
			ability.input_schema,
			'input'
		);
		if ( inputValidation !== true ) {
			const error = new Error(
				`Ability "${ ability.name }" has invalid input. Reason: ${ inputValidation }`
			);
			( error as any ).code = 'ability_invalid_input';
			throw error;
		}
	}

	let result: AbilityOutput;
	try {
		result = await ability.callback( input );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( `Error executing client ability ${ ability.name }:`, error );
		throw error;
	}

	if ( ability.output_schema ) {
		const outputValidation = validateValueFromSchema(
			result,
			ability.output_schema,
			'output'
		);
		if ( outputValidation !== true ) {
			const error = new Error(
				`Ability "${ ability.name }" has invalid output. Reason: ${ outputValidation }`
			);
			( error as any ).code = 'ability_invalid_output';
			throw error;
		}
	}

	return result;
}

/**
 * Execute a server-side ability.
 *
 * @param ability The ability to execute.
 * @param input   Input parameters for the ability.
 * @return Promise resolving to the ability execution result.
 * @throws Error if the API call fails.
 */
async function executeServerAbility(
	ability: Ability,
	input: AbilityInput
): Promise< AbilityOutput > {
	const isResource = ability.meta?.type === 'resource';
	const method = isResource ? 'GET' : 'POST';

	let path = `/wp/v2/abilities/${ ability.name }/run`;
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

	// Note: Input and output validation happens on the server side for these abilities.
	try {
		return await apiFetch< AbilityOutput >( {
			path,
			...options,
		} );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( `Error executing ability ${ ability.name }:`, error );
		throw error;
	}
}

/**
 * Execute an ability.
 *
 * Determines whether to execute locally (client abilities) or remotely (server abilities)
 * based on the ability's location property.
 *
 * @param name  The ability name.
 * @param input Optional input parameters for the ability.
 * @return Promise resolving to the ability execution result.
 * @throws Error if the ability is not found or execution fails.
 */
export async function executeAbility(
	name: string,
	input: AbilityInput = null
): Promise< AbilityOutput > {
	const ability = await getAbility( name );
	if ( ! ability ) {
		throw new Error(
			sprintf(
				/* translators: %s: ability name */
				__( 'Ability not found: %s' ),
				name
			)
		);
	}

	// Route to appropriate execution method
	if ( ability.location === 'client' ) {
		return executeClientAbility( ability, input );
	}

	return executeServerAbility( ability, input );
}
