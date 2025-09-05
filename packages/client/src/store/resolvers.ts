/**
 * WordPress dependencies
 */
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ENTITY_KIND, ENTITY_NAME } from './constants';
import { receiveAbilities } from './actions';
import type { Ability } from '../types';

/**
 * Resolver for getAbilities selector.
 * Fetches all abilities from the server with pagination.
 */
export function getAbilities() {
	// @ts-expect-error - registry types are not yet available
	return async ( { dispatch, registry } ) => {
		const resolveSelect = registry.resolveSelect( coreStore );

		const PER_PAGE = 1;

		const firstPage = await resolveSelect.getEntityRecords(
			ENTITY_KIND,
			ENTITY_NAME,
			{ per_page: PER_PAGE, page: 1 }
		);

		if ( ! firstPage || firstPage.length === 0 ) {
			dispatch( receiveAbilities( [] ) );
			return;
		}

		const select = registry.select( coreStore );
		const totalPages = select.getEntityRecordsTotalPages(
			ENTITY_KIND,
			ENTITY_NAME,
			{ per_page: PER_PAGE }
		);

		if ( totalPages === 1 ) {
			dispatch( receiveAbilities( firstPage ) );
			return;
		}

		const fetchRemainingPages = async (
			page = 2,
			accumulated: Ability[] = firstPage
		): Promise< Ability[] > => {
			if ( page > totalPages ) {
				return accumulated;
			}

			const abilities = await resolveSelect.getEntityRecords(
				ENTITY_KIND,
				ENTITY_NAME,
				{ per_page: PER_PAGE, page }
			);

			const combined = [ ...accumulated, ...( abilities || [] ) ];
			return fetchRemainingPages( page + 1, combined );
		};

		const allAbilities = await fetchRemainingPages();
		dispatch( receiveAbilities( allAbilities ) );
	};
}

/**
 * Resolver for getAbility selector.
 * Fetches a specific ability from the server.
 *
 * @param name Ability name.
 */
export function getAbility( name: string ) {
	// @ts-expect-error - registry types are not yet available
	return async ( { dispatch, registry } ) => {
		const ability = await registry
			.resolveSelect( coreStore )
			.getEntityRecord( ENTITY_KIND, ENTITY_NAME, name );

		if ( ability ) {
			dispatch( receiveAbilities( [ ability ] ) );
		}
	};
}
