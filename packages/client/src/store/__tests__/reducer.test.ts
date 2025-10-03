/**
 * Tests for store reducer.
 */

/**
 * Internal dependencies
 */
import reducer from '../reducer';
import {
	RECEIVE_ABILITIES,
	REGISTER_ABILITY,
	UNREGISTER_ABILITY,
} from '../constants';

describe( 'Store Reducer', () => {
	describe( 'abilitiesByName', () => {
		const defaultState = {};

		describe( 'RECEIVE_ABILITIES', () => {
			it( 'should add abilities to the state', () => {
				const abilities = [
					{
						name: 'test/ability1',
						label: 'Test Ability 1',
						description: 'First test ability',
						input_schema: { type: 'object' },
					},
					{
						name: 'test/ability2',
						label: 'Test Ability 2',
						description: 'Second test ability',
						output_schema: { type: 'object' },
					},
				];

				const action = {
					type: RECEIVE_ABILITIES,
					abilities,
				};

				const state = reducer(
					{ abilitiesByName: defaultState },
					action
				);

				expect( state.abilitiesByName ).toHaveProperty(
					'test/ability1'
				);
				expect( state.abilitiesByName ).toHaveProperty(
					'test/ability2'
				);
				expect( state.abilitiesByName[ 'test/ability1' ].label ).toBe(
					'Test Ability 1'
				);
				expect( state.abilitiesByName[ 'test/ability2' ].label ).toBe(
					'Test Ability 2'
				);
			} );

			it( 'should filter out _links from server responses', () => {
				const abilities = [
					{
						name: 'test/ability',
						label: 'Test Ability',
						description: 'Test ability with links',
						_links: {
							self: { href: '/wp/v2/abilities/test/ability' },
							collection: { href: '/wp/v2/abilities' },
						},
					},
				];

				const action = {
					type: RECEIVE_ABILITIES,
					abilities,
				};

				const state = reducer(
					{ abilitiesByName: defaultState },
					action
				);

				expect(
					state.abilitiesByName[ 'test/ability' ]
				).not.toHaveProperty( '_links' );
				expect( state.abilitiesByName[ 'test/ability' ].name ).toBe(
					'test/ability'
				);
				expect( state.abilitiesByName[ 'test/ability' ].label ).toBe(
					'Test Ability'
				);
			} );

			it( 'should filter out _embedded from server responses', () => {
				const abilities = [
					{
						name: 'test/ability',
						label: 'Test Ability',
						description: 'Test ability with embedded',
						_embedded: {
							author: { id: 1, name: 'Admin' },
						},
					},
				];

				const action = {
					type: RECEIVE_ABILITIES,
					abilities,
				};

				const state = reducer(
					{ abilitiesByName: defaultState },
					action
				);

				expect(
					state.abilitiesByName[ 'test/ability' ]
				).not.toHaveProperty( '_embedded' );
			} );

			it( 'should preserve all valid ability properties', () => {
				const abilities = [
					{
						name: 'test/ability',
						label: 'Test Ability',
						description: 'Full test ability.',
						input_schema: { type: 'object' },
						output_schema: { type: 'object' },
						annotations: { read_only: true },
						meta: { category: 'test' },
						callback: () => Promise.resolve( {} ),
						permissionCallback: () => true,
						// Extra properties that should be filtered out
						_links: { self: { href: '/test' } },
						_embedded: { test: 'value' },
						extra_field: 'should be removed',
					},
				];

				const action = {
					type: RECEIVE_ABILITIES,
					abilities,
				};

				const state = reducer(
					{ abilitiesByName: defaultState },
					action
				);
				const ability = state.abilitiesByName[ 'test/ability' ];

				// Should have valid properties
				expect( ability.name ).toBe( 'test/ability' );
				expect( ability.label ).toBe( 'Test Ability' );
				expect( ability.description ).toBe( 'Full test ability.' );
				expect( ability.input_schema ).toEqual( { type: 'object' } );
				expect( ability.output_schema ).toEqual( { type: 'object' } );
				expect( ability.annotations ).toEqual( { read_only: true } );
				expect( ability.meta ).toEqual( { category: 'test' } );
				expect( ability.callback ).toBeDefined();
				expect( ability.permissionCallback ).toBeDefined();

				// Should NOT have invalid properties
				expect( ability ).not.toHaveProperty( '_links' );
				expect( ability ).not.toHaveProperty( '_embedded' );
				expect( ability ).not.toHaveProperty( 'extra_field' );
			} );
		} );

		describe( 'REGISTER_ABILITY', () => {
			it( 'should add ability to the state', () => {
				const ability = {
					name: 'test/ability',
					label: 'Test Ability',
					description: 'Test ability',
					callback: () => Promise.resolve( {} ),
				};

				const action = {
					type: REGISTER_ABILITY,
					ability,
				};

				const state = reducer(
					{ abilitiesByName: defaultState },
					action
				);

				expect( state.abilitiesByName ).toHaveProperty(
					'test/ability'
				);
				expect( state.abilitiesByName[ 'test/ability' ].label ).toBe(
					'Test Ability'
				);
			} );

			it( 'should filter out extra properties when registering', () => {
				const ability = {
					name: 'test/ability',
					label: 'Test Ability',
					description: 'Test ability',
					callback: () => Promise.resolve( {} ),
					// Extra properties that should be filtered out
					_links: { self: { href: '/test' } },
					extra_field: 'should be removed',
				};

				const action = {
					type: REGISTER_ABILITY,
					ability,
				};

				const state = reducer(
					{ abilitiesByName: defaultState },
					action
				);
				const registeredAbility =
					state.abilitiesByName[ 'test/ability' ];

				// Should have valid properties
				expect( registeredAbility.name ).toBe( 'test/ability' );
				expect( registeredAbility.label ).toBe( 'Test Ability' );
				expect( registeredAbility.description ).toBe( 'Test ability' );
				expect( registeredAbility.callback ).toBeDefined();

				// Should NOT have invalid properties
				expect( registeredAbility ).not.toHaveProperty( '_links' );
				expect( registeredAbility ).not.toHaveProperty( 'extra_field' );
			} );

			it( 'should replace existing ability', () => {
				const initialState = {
					'test/ability': {
						name: 'test/ability',
						label: 'Old Label',
						description: 'Old description',
					},
				};

				const ability = {
					name: 'test/ability',
					label: 'New Label',
					description: 'New description',
					input_schema: { type: 'string' },
				};

				const action = {
					type: REGISTER_ABILITY,
					ability,
				};

				const state = reducer(
					{ abilitiesByName: initialState },
					action
				);

				expect( state.abilitiesByName[ 'test/ability' ].label ).toBe(
					'New Label'
				);
				expect(
					state.abilitiesByName[ 'test/ability' ].description
				).toBe( 'New description' );
				expect(
					state.abilitiesByName[ 'test/ability' ].input_schema
				).toEqual( { type: 'string' } );
			} );
		} );

		describe( 'UNREGISTER_ABILITY', () => {
			it( 'should remove ability from the state', () => {
				const initialState = {
					'test/ability1': {
						name: 'test/ability1',
						label: 'Test Ability 1',
						description: 'First test ability',
					},
					'test/ability2': {
						name: 'test/ability2',
						label: 'Test Ability 2',
						description: 'Second test ability',
					},
				};

				const action = {
					type: UNREGISTER_ABILITY,
					name: 'test/ability1',
				};

				const state = reducer(
					{ abilitiesByName: initialState },
					action
				);

				expect( state.abilitiesByName ).not.toHaveProperty(
					'test/ability1'
				);
				expect( state.abilitiesByName ).toHaveProperty(
					'test/ability2'
				);
			} );

			it( 'should handle unregistering non-existent ability', () => {
				const initialState = {
					'test/ability': {
						name: 'test/ability',
						label: 'Test Ability',
						description: 'Test ability',
					},
				};

				const action = {
					type: UNREGISTER_ABILITY,
					name: 'test/non-existent',
				};

				const state = reducer(
					{ abilitiesByName: initialState },
					action
				);

				expect( state.abilitiesByName ).toEqual( initialState );
			} );
		} );

		describe( 'Edge cases', () => {
			it( 'should handle undefined abilities in RECEIVE_ABILITIES', () => {
				const action = {
					type: RECEIVE_ABILITIES,
					abilities: undefined,
				};

				const state = reducer(
					{ abilitiesByName: defaultState },
					action
				);

				expect( state.abilitiesByName ).toEqual( defaultState );
			} );

			it( 'should handle undefined ability in REGISTER_ABILITY', () => {
				const action = {
					type: REGISTER_ABILITY,
					ability: undefined,
				};

				const state = reducer(
					{ abilitiesByName: defaultState },
					action
				);

				expect( state.abilitiesByName ).toEqual( defaultState );
			} );

			it( 'should handle undefined properties gracefully', () => {
				const abilities = [
					{
						name: 'test/minimal',
						label: 'Minimal',
						description:
							'Minimal ability with undefined properties',
						input_schema: undefined,
						output_schema: undefined,
						meta: undefined,
						callback: undefined,
						permissionCallback: undefined,
					},
				];

				const action = {
					type: RECEIVE_ABILITIES,
					abilities,
				};

				const state = reducer(
					{ abilitiesByName: defaultState },
					action
				);
				const ability = state.abilitiesByName[ 'test/minimal' ];

				expect( ability.name ).toBe( 'test/minimal' );
				expect( ability.label ).toBe( 'Minimal' );
				expect( ability.description ).toBe(
					'Minimal ability with undefined properties'
				);
				// Undefined properties should not be present
				expect( ability ).not.toHaveProperty( 'input_schema' );
				expect( ability ).not.toHaveProperty( 'output_schema' );
				expect( ability ).not.toHaveProperty( 'meta' );
				expect( ability ).not.toHaveProperty( 'callback' );
				expect( ability ).not.toHaveProperty( 'permissionCallback' );
			} );
		} );
	} );
} );
