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
					category: 'test-category',
					input_schema: { type: 'object' },
					output_schema: { type: 'object' },
				},
				{
					name: 'test/ability2',
					label: 'Test Ability 2',
					description: 'Second test ability',
					category: 'test-category',
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
				getAbilityCategories: jest.fn().mockReturnValue( [
					{
						slug: 'test-category',
						label: 'Test Category',
						description: 'Test category for testing',
					},
					{
						slug: 'data-retrieval',
						label: 'Data Retrieval',
						description: 'Abilities that retrieve data',
					},
				] ),
				getAbilityCategory: jest.fn().mockImplementation( ( slug ) => {
					const categories: Record< string, any > = {
						'test-category': {
							slug: 'test-category',
							label: 'Test Category',
							description: 'Test category for testing',
						},
						'data-retrieval': {
							slug: 'data-retrieval',
							label: 'Data Retrieval',
							description: 'Abilities that retrieve data',
						},
					};
					return categories[ slug ] || null;
				} ),
			};
			mockDispatch = jest.fn();
		} );

		it( 'should register a valid client ability', async () => {
			const ability: Ability = {
				name: 'test/ability',
				label: 'Test Ability',
				description: 'Test ability description',
				category: 'test-category',
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
			await action( { select: mockSelect, dispatch: mockDispatch } );

			expect( mockDispatch ).toHaveBeenCalledWith( {
				type: REGISTER_ABILITY,
				ability,
			} );
		} );

		it( 'should register server-side abilities', async () => {
			const ability: Ability = {
				name: 'test/server-ability',
				label: 'Server Ability',
				description: 'Server-side ability',
				category: 'test-category',
				input_schema: { type: 'object' },
				output_schema: { type: 'object' },
			};

			const action = registerAbility( ability );
			await action( { select: mockSelect, dispatch: mockDispatch } );

			expect( mockDispatch ).toHaveBeenCalledWith( {
				type: REGISTER_ABILITY,
				ability,
			} );
		} );

		it( 'should validate and reject ability without name', async () => {
			const ability: Ability = {
				name: '',
				label: 'Test Ability',
				description: 'Test description',
				category: 'test-category',
				callback: jest.fn(),
			};

			const action = registerAbility( ability );

			await expect(
				action( { select: mockSelect, dispatch: mockDispatch } )
			).rejects.toThrow( 'Ability name is required' );
			expect( mockDispatch ).not.toHaveBeenCalled();
		} );

		it( 'should validate and reject ability with invalid name format', async () => {
			const testCases = [
				'invalid', // No namespace
				'my-plugin/feature/action', // Multiple slashes
				'My-Plugin/feature', // Uppercase letters
				'my_plugin/feature', // Underscores not allowed
				'my-plugin/feature!', // Special characters not allowed
				'my plugin/feature', // Spaces not allowed
			];

			for ( const invalidName of testCases ) {
				const ability: Ability = {
					name: invalidName,
					label: 'Test Ability',
					description: 'Test description',
					category: 'test-category',
					callback: jest.fn(),
				};

				const action = registerAbility( ability );

				await expect(
					action( { select: mockSelect, dispatch: mockDispatch } )
				).rejects.toThrow(
					'Ability name must be a string containing a namespace prefix'
				);
				expect( mockDispatch ).not.toHaveBeenCalled();
				mockDispatch.mockClear();
			}
		} );

		it( 'should validate and reject ability without label', async () => {
			const ability: Ability = {
				name: 'test/ability',
				label: '',
				description: 'Test description',
				category: 'test-category',
				callback: jest.fn(),
			};

			const action = registerAbility( ability );

			await expect(
				action( { select: mockSelect, dispatch: mockDispatch } )
			).rejects.toThrow( 'Ability "test/ability" must have a label' );
			expect( mockDispatch ).not.toHaveBeenCalled();
		} );

		it( 'should validate and reject ability without description', async () => {
			const ability: Ability = {
				name: 'test/ability',
				label: 'Test Ability',
				description: '',
				category: 'test-category',
				callback: jest.fn(),
			};

			const action = registerAbility( ability );

			await expect(
				action( { select: mockSelect, dispatch: mockDispatch } )
			).rejects.toThrow( 'Ability "test/ability" must have a description' );
			expect( mockDispatch ).not.toHaveBeenCalled();
		} );

		it( 'should validate and reject ability without category', async () => {
			const ability: Ability = {
				name: 'test/ability',
				label: 'Test Ability',
				description: 'Test description',
				category: '',
				callback: jest.fn(),
			};

			const action = registerAbility( ability );

			await expect(
				action( { select: mockSelect, dispatch: mockDispatch } )
			).rejects.toThrow( 'Ability "test/ability" must have a category' );
			expect( mockDispatch ).not.toHaveBeenCalled();
		} );

		it( 'should validate and reject ability with invalid category format', async () => {
			const testCases = [
				'Data-Retrieval', // Uppercase letters
				'data_retrieval', // Underscores not allowed
				'data.retrieval', // Dots not allowed
				'data/retrieval', // Slashes not allowed
				'-data-retrieval', // Leading dash
				'data-retrieval-', // Trailing dash
				'data--retrieval', // Double dash
			];

			for ( const invalidCategory of testCases ) {
				const ability: Ability = {
					name: 'test/ability',
					label: 'Test Ability',
					description: 'Test description',
					category: invalidCategory,
					callback: jest.fn(),
				};

				const action = registerAbility( ability );

				await expect(
					action( { select: mockSelect, dispatch: mockDispatch } )
				).rejects.toThrow(
					'Ability "test/ability" has an invalid category. Category must be lowercase alphanumeric with dashes only'
				);
				expect( mockDispatch ).not.toHaveBeenCalled();
				mockDispatch.mockClear();
			}
		} );

		it( 'should accept ability with valid category format', async () => {
			const validCategories = [
				'data-retrieval',
				'user-management',
				'analytics-123',
				'ecommerce',
			];

			for ( const validCategory of validCategories ) {
				const ability: Ability = {
					name: 'test/ability-' + validCategory,
					label: 'Test Ability',
					description: 'Test description',
					category: validCategory,
					callback: jest.fn(),
				};

				// Update mock to include this category
				mockSelect.getAbilityCategory.mockImplementation(
					( slug: string ) => {
						if ( slug === validCategory || slug === 'test-category' || slug === 'data-retrieval' ) {
							return {
								slug,
								label: 'Test',
								description: 'Test',
							};
						}
						return null;
					}
				);

				mockSelect.getAbility.mockReturnValue( null );
				mockDispatch.mockClear();

				const action = registerAbility( ability );
				await action( { select: mockSelect, dispatch: mockDispatch } );

				expect( mockDispatch ).toHaveBeenCalledWith( {
					type: REGISTER_ABILITY,
					ability,
				} );
			}
		} );

		it( 'should validate and reject ability with non-existent category', async () => {
			// Override getAbilityCategory to only return null
			mockSelect.getAbilityCategory.mockReturnValue( null );

			const ability: Ability = {
				name: 'test/ability',
				label: 'Test Ability',
				description: 'Test description',
				category: 'non-existent-category',
				callback: jest.fn(),
			};

			const action = registerAbility( ability );

			await expect(
				action( { select: mockSelect, dispatch: mockDispatch } )
			).rejects.toThrow(
				'Ability "test/ability" references non-existent category "non-existent-category". Please register the category first.'
			);
			expect( mockDispatch ).not.toHaveBeenCalled();
		} );

		it( 'should accept ability with existing category', async () => {
			mockSelect.getAbilityCategory.mockReturnValue( {
				slug: 'data-retrieval',
				label: 'Data Retrieval',
				description: 'Abilities that retrieve data',
			} );

			const ability: Ability = {
				name: 'test/ability',
				label: 'Test Ability',
				description: 'Test description',
				category: 'data-retrieval',
				callback: jest.fn(),
			};

			const action = registerAbility( ability );
			await action( { select: mockSelect, dispatch: mockDispatch } );

			expect( mockSelect.getAbilityCategory ).toHaveBeenCalledWith(
				'data-retrieval'
			);
			expect( mockDispatch ).toHaveBeenCalledWith( {
				type: REGISTER_ABILITY,
				ability,
			} );
		} );

		it( 'should validate and reject ability with invalid callback', async () => {
			const ability: Ability = {
				name: 'test/ability',
				label: 'Test Ability',
				description: 'Test description',
				category: 'test-category',
				callback: 'not a function' as any,
			};

			const action = registerAbility( ability );

			await expect(
				action( { select: mockSelect, dispatch: mockDispatch } )
			).rejects.toThrow(
				'Ability "test/ability" has an invalid callback. Callback must be a function'
			);
			expect( mockDispatch ).not.toHaveBeenCalled();
		} );

		it( 'should validate and reject already registered ability', async () => {
			const existingAbility: Ability = {
				name: 'test/ability',
				label: 'Existing Ability',
				description: 'Already registered',
				category: 'test-category',
			};

			mockSelect.getAbility.mockReturnValue( existingAbility );

			const ability: Ability = {
				name: 'test/ability',
				label: 'Test Ability',
				description: 'Test description',
				category: 'test-category',
				callback: jest.fn(),
			};

			const action = registerAbility( ability );

			await expect(
				action( { select: mockSelect, dispatch: mockDispatch } )
			).rejects.toThrow( 'Ability "test/ability" is already registered' );
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
