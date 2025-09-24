/**
 * Tests for store actions.
 */

/**
 * Internal dependencies
 */
import {
	receiveAbilities,
	registerAbility,
	unregisterAbility,
} from '../actions';
import {
	RECEIVE_ABILITIES,
	REGISTER_ABILITY,
	UNREGISTER_ABILITY,
} from '../constants';
import type { Ability } from '../../types';

describe( 'Store Actions', () => {
	describe( 'receiveAbilities', () => {
		it( 'should create an action to receive abilities', () => {
			const abilities: Ability[] = [
				{
					name: 'test/ability1',
					label: 'Test Ability 1',
					description: 'First test ability',
					input_schema: { type: 'object' },
					output_schema: { type: 'object' },
				},
				{
					name: 'test/ability2',
					label: 'Test Ability 2',
					description: 'Second test ability',
					input_schema: { type: 'object' },
					output_schema: { type: 'object' },
				},
			];

			const action = receiveAbilities( abilities );

			expect( action ).toEqual( {
				type: RECEIVE_ABILITIES,
				abilities,
			} );
		} );

		it( 'should handle empty abilities array', () => {
			const abilities: Ability[] = [];
			const action = receiveAbilities( abilities );

			expect( action ).toEqual( {
				type: RECEIVE_ABILITIES,
				abilities: [],
			} );
		} );
	} );

	describe( 'registerAbility', () => {
		let mockSelect: any;
		let mockDispatch: jest.Mock;

		beforeEach( () => {
			jest.clearAllMocks();
			mockSelect = {
				getAbility: jest.fn().mockReturnValue( null ),
			};
			mockDispatch = jest.fn();
		} );

		it( 'should register a valid client ability', () => {
			const ability: Ability = {
				name: 'test/ability',
				label: 'Test Ability',
				description: 'Test ability description',
				input_schema: {
					type: 'object',
					properties: {
						message: { type: 'string' },
					},
				},
				output_schema: {
					type: 'object',
					properties: {
						success: { type: 'boolean' },
					},
				},
				callback: jest.fn(),
			};

			const action = registerAbility( ability );
			action( { select: mockSelect, dispatch: mockDispatch } );

			expect( mockDispatch ).toHaveBeenCalledWith( {
				type: REGISTER_ABILITY,
				ability,
			} );
		} );

		it( 'should register server-side abilities', () => {
			const ability: Ability = {
				name: 'test/server-ability',
				label: 'Server Ability',
				description: 'Server-side ability',
				input_schema: { type: 'object' },
				output_schema: { type: 'object' },
			};

			const action = registerAbility( ability );
			action( { select: mockSelect, dispatch: mockDispatch } );

			expect( mockDispatch ).toHaveBeenCalledWith( {
				type: REGISTER_ABILITY,
				ability,
			} );
		} );

		it( 'should validate and reject ability without name', () => {
			const ability: Ability = {
				name: '',
				label: 'Test Ability',
				description: 'Test description',
				callback: jest.fn(),
			};

			const action = registerAbility( ability );

			expect( () =>
				action( { select: mockSelect, dispatch: mockDispatch } )
			).toThrow( 'Ability name is required' );
			expect( mockDispatch ).not.toHaveBeenCalled();
		} );

		it( 'should validate and reject ability with invalid name format', () => {
			const testCases = [
				'invalid', // No namespace
				'my-plugin/feature/action', // Multiple slashes
				'My-Plugin/feature', // Uppercase letters
				'my_plugin/feature', // Underscores not allowed
				'my-plugin/feature!', // Special characters not allowed
				'my plugin/feature', // Spaces not allowed
			];

			testCases.forEach( ( invalidName ) => {
				const ability: Ability = {
					name: invalidName,
					label: 'Test Ability',
					description: 'Test description',
					callback: jest.fn(),
				};

				const action = registerAbility( ability );

				expect( () =>
					action( { select: mockSelect, dispatch: mockDispatch } )
				).toThrow(
					'Ability name must be a string containing a namespace prefix'
				);
				expect( mockDispatch ).not.toHaveBeenCalled();
			} );
		} );

		it( 'should validate and reject ability without label', () => {
			const ability: Ability = {
				name: 'test/ability',
				label: '',
				description: 'Test description',
				callback: jest.fn(),
			};

			const action = registerAbility( ability );

			expect( () =>
				action( { select: mockSelect, dispatch: mockDispatch } )
			).toThrow( 'Ability "test/ability" must have a label' );
			expect( mockDispatch ).not.toHaveBeenCalled();
		} );

		it( 'should validate and reject ability without description', () => {
			const ability: Ability = {
				name: 'test/ability',
				label: 'Test Ability',
				description: '',
				callback: jest.fn(),
			};

			const action = registerAbility( ability );

			expect( () =>
				action( { select: mockSelect, dispatch: mockDispatch } )
			).toThrow( 'Ability "test/ability" must have a description' );
			expect( mockDispatch ).not.toHaveBeenCalled();
		} );

		it( 'should validate and reject ability with invalid callback', () => {
			const ability: Ability = {
				name: 'test/ability',
				label: 'Test Ability',
				description: 'Test description',
				callback: 'not a function' as any,
			};

			const action = registerAbility( ability );

			expect( () =>
				action( { select: mockSelect, dispatch: mockDispatch } )
			).toThrow(
				'Ability "test/ability" has an invalid callback. Callback must be a function'
			);
			expect( mockDispatch ).not.toHaveBeenCalled();
		} );

		it( 'should validate and reject already registered ability', () => {
			const existingAbility: Ability = {
				name: 'test/ability',
				label: 'Existing Ability',
				description: 'Already registered',
			};

			mockSelect.getAbility.mockReturnValue( existingAbility );

			const ability: Ability = {
				name: 'test/ability',
				label: 'Test Ability',
				description: 'Test description',
				callback: jest.fn(),
			};

			const action = registerAbility( ability );

			expect( () =>
				action( { select: mockSelect, dispatch: mockDispatch } )
			).toThrow( 'Ability "test/ability" is already registered' );
			expect( mockDispatch ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'unregisterAbility', () => {
		it( 'should create an action to unregister an ability', () => {
			const abilityName = 'test/ability';
			const action = unregisterAbility( abilityName );

			expect( action ).toEqual( {
				type: UNREGISTER_ABILITY,
				name: abilityName,
			} );
		} );

		it( 'should handle valid namespaced ability names', () => {
			const abilityName = 'my-plugin/feature-action';
			const action = unregisterAbility( abilityName );

			expect( action ).toEqual( {
				type: UNREGISTER_ABILITY,
				name: abilityName,
			} );
		} );
	} );
} );
