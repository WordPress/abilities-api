/**
 * Tests for store selectors.
 */

/**
 * Internal dependencies
 */
import { getAbilities, getAbility } from '../selectors';
import type { AbilitiesState } from '../../types';

describe('Store Selectors', () => {
	describe('getAbilities', () => {
		it('should return all abilities as an array', () => {
			const state: AbilitiesState = {
				abilitiesById: {
					'test/ability1': {
						name: 'test/ability1',
						label: 'Test Ability 1',
						description: 'First test ability',
						location: 'server',
						input_schema: { type: 'object' },
						output_schema: { type: 'object' },
					},
					'test/ability2': {
						name: 'test/ability2',
						label: 'Test Ability 2',
						description: 'Second test ability',
						location: 'client',
						input_schema: { type: 'object' },
						output_schema: { type: 'object' },
						callback: jest.fn(),
					},
				},
			};

			const abilities = getAbilities(state);

			expect(abilities).toHaveLength(2);
			expect(abilities).toContainEqual(
				state.abilitiesById['test/ability1']
			);
			expect(abilities).toContainEqual(
				state.abilitiesById['test/ability2']
			);
		});

		it('should return empty array when no abilities exist', () => {
			const state: AbilitiesState = {
				abilitiesById: {},
			};

			const abilities = getAbilities(state);

			expect(abilities).toEqual([]);
		});

		it('should memoize results when state unchanged', () => {
			const state: AbilitiesState = {
				abilitiesById: {
					'test/ability': {
						name: 'test/ability',
						label: 'Test Ability',
						description: 'Test ability',
						location: 'server',
						input_schema: { type: 'object' },
						output_schema: { type: 'object' },
					},
				},
			};

			const result1 = getAbilities(state);
			const result2 = getAbilities(state);

			// Should return the same reference when state unchanged
			expect(result1).toBe(result2);
		});

		it('should return new array reference when state changes', () => {
			const state1: AbilitiesState = {
				abilitiesById: {
					'test/ability1': {
						name: 'test/ability1',
						label: 'Test Ability 1',
						description: 'Test ability',
						location: 'server',
						input_schema: { type: 'object' },
						output_schema: { type: 'object' },
					},
				},
			};

			const state2: AbilitiesState = {
				abilitiesById: {
					...state1.abilitiesById,
					'test/ability2': {
						name: 'test/ability2',
						label: 'Test Ability 2',
						description: 'Another test ability',
						location: 'client',
						input_schema: { type: 'object' },
						output_schema: { type: 'object' },
					},
				},
			};

			const result1 = getAbilities(state1);
			const result2 = getAbilities(state2);

			// Should return different references when state changes
			expect(result1).not.toBe(result2);
			expect(result1).toHaveLength(1);
			expect(result2).toHaveLength(2);
		});
	});

	describe('getAbility', () => {
		const state: AbilitiesState = {
			abilitiesById: {
				'test/ability1': {
					name: 'test/ability1',
					label: 'Test Ability 1',
					description: 'First test ability',
					location: 'server',
					input_schema: { type: 'object' },
					output_schema: { type: 'object' },
				},
				'test/ability2': {
					name: 'test/ability2',
					label: 'Test Ability 2',
					description: 'Second test ability',
					location: 'client',
					input_schema: { type: 'object' },
					output_schema: { type: 'object' },
					callback: jest.fn(),
				},
			},
		};

		it('should return a specific ability by name', () => {
			const ability = getAbility(state, 'test/ability1');

			expect(ability).toEqual(state.abilitiesById['test/ability1']);
		});

		it('should return null if ability not found', () => {
			const ability = getAbility(state, 'non-existent');

			expect(ability).toBeNull();
		});

		it('should handle empty state', () => {
			const emptyState: AbilitiesState = {
				abilitiesById: {},
			};

			const ability = getAbility(emptyState, 'test/ability');

			expect(ability).toBeNull();
		});

		it('should return client abilities with callbacks', () => {
			const ability = getAbility(state, 'test/ability2');

			expect(ability).toEqual(state.abilitiesById['test/ability2']);
			expect(ability?.callback).toBeDefined();
		});

		it('should handle namespaced ability names correctly', () => {
			const stateWithNamespaced: AbilitiesState = {
				abilitiesById: {
					'my-plugin/feature/action': {
						name: 'my-plugin/feature/action',
						label: 'Namespaced Action',
						description: 'Complex namespaced ability',
						location: 'server',
						input_schema: { type: 'object' },
						output_schema: { type: 'object' },
					},
				},
			};

			const ability = getAbility(
				stateWithNamespaced,
				'my-plugin/feature/action'
			);

			expect(ability).toEqual(
				stateWithNamespaced.abilitiesById['my-plugin/feature/action']
			);
		});
	});
});
