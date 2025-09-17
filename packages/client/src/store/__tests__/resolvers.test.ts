/**
 * Tests for store resolvers.
 */

/**
 * WordPress dependencies
 */
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { getAbilities, getAbility } from '../resolvers';
import { receiveAbilities } from '../actions';
import { ENTITY_KIND, ENTITY_NAME } from '../constants';
import type { Ability } from '../../types';

// Mock the WordPress core data store
jest.mock('@wordpress/core-data', () => ({
	store: 'core',
}));

describe('Store Resolvers', () => {
	let mockDispatch: jest.Mock;
	let mockRegistry: any;
	let mockSelect: any;

	beforeEach(() => {
		mockDispatch = jest.fn();
		mockSelect = jest.fn();
		mockRegistry = {
			resolveSelect: jest.fn(),
			select: jest.fn(),
		};
	});

	describe('getAbilities', () => {
		it('should fetch and dispatch abilities from the server', async () => {
			const mockAbilities: Ability[] = [
				{
					name: 'test/ability1',
					label: 'Test Ability 1',
					description: 'First test ability',
					input_schema: { type: 'object' },
					output_schema: { type: 'object' },
				},
			];

			const mockResolveSelect = {
				getEntityRecords: jest.fn().mockResolvedValue(mockAbilities),
			};

			const mockSelectInstance = {
				getEntityRecordsTotalPages: jest.fn().mockReturnValue(1),
			};

			mockRegistry.resolveSelect.mockReturnValue(mockResolveSelect);
			mockRegistry.select.mockReturnValue(mockSelectInstance);

			const resolver = getAbilities();
			await resolver({ dispatch: mockDispatch, registry: mockRegistry });

			expect(mockRegistry.resolveSelect).toHaveBeenCalledWith(coreStore);
			expect(mockResolveSelect.getEntityRecords).toHaveBeenCalledWith(
				ENTITY_KIND,
				ENTITY_NAME,
				{ per_page: -1 }
			);
			expect(mockDispatch).toHaveBeenCalledWith(
				receiveAbilities(mockAbilities)
			);
		});

		it('should handle empty abilities', async () => {
			const mockResolveSelect = {
				getEntityRecords: jest.fn().mockResolvedValue([]),
			};

			mockRegistry.resolveSelect.mockReturnValue(mockResolveSelect);

			const resolver = getAbilities();
			await resolver({ dispatch: mockDispatch, registry: mockRegistry });

			expect(mockDispatch).toHaveBeenCalledWith(receiveAbilities([]));
		});

		it('should handle null response', async () => {
			const mockResolveSelect = {
				getEntityRecords: jest.fn().mockResolvedValue(null),
			};

			mockRegistry.resolveSelect.mockReturnValue(mockResolveSelect);

			const resolver = getAbilities();
			await resolver({ dispatch: mockDispatch, registry: mockRegistry });

			expect(mockDispatch).toHaveBeenCalledWith(receiveAbilities([]));
		});

		it('should fetch all abilities in a single request', async () => {
			const allAbilities: Ability[] = [
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
				{
					name: 'test/ability3',
					label: 'Test Ability 3',
					description: 'Third test ability',
					input_schema: { type: 'object' },
					output_schema: { type: 'object' },
				},
			];

			const mockResolveSelect = {
				getEntityRecords: jest.fn().mockResolvedValue(allAbilities),
			};

			mockRegistry.resolveSelect.mockReturnValue(mockResolveSelect);

			const resolver = getAbilities();
			await resolver({ dispatch: mockDispatch, registry: mockRegistry });

			// Should fetch all abilities in one request with per_page: -1
			expect(mockResolveSelect.getEntityRecords).toHaveBeenCalledTimes(1);
			expect(mockResolveSelect.getEntityRecords).toHaveBeenNthCalledWith(
				1,
				ENTITY_KIND,
				ENTITY_NAME,
				{ per_page: -1 }
			);

			// Should dispatch all abilities
			expect(mockDispatch).toHaveBeenCalledWith(
				receiveAbilities(allAbilities)
			);
		});
	});

	describe('getAbility', () => {
		it('should fetch and dispatch a specific ability', async () => {
			const mockAbility: Ability = {
				name: 'test/ability',
				label: 'Test Ability',
				description: 'Test ability description',
				input_schema: { type: 'object' },
				output_schema: { type: 'object' },
			};

			const mockResolveSelect = {
				getEntityRecord: jest.fn().mockResolvedValue(mockAbility),
			};

			mockRegistry.resolveSelect.mockReturnValue(mockResolveSelect);
			mockSelect.getAbility = jest.fn().mockReturnValue(null); // Ability not in store

			const resolver = getAbility('test/ability');
			await resolver({
				dispatch: mockDispatch,
				registry: mockRegistry,
				select: mockSelect,
			});

			expect(mockSelect.getAbility).toHaveBeenCalledWith('test/ability');
			expect(mockRegistry.resolveSelect).toHaveBeenCalledWith(coreStore);
			expect(mockResolveSelect.getEntityRecord).toHaveBeenCalledWith(
				ENTITY_KIND,
				ENTITY_NAME,
				'test/ability'
			);
			expect(mockDispatch).toHaveBeenCalledWith(
				receiveAbilities([mockAbility])
			);
		});

		it('should not fetch if ability already exists in store', async () => {
			const existingAbility: Ability = {
				name: 'test/ability',
				label: 'Test Ability',
				description: 'Already in store',
				input_schema: { type: 'object' },
				output_schema: { type: 'object' },
				callback: jest.fn(),
			};

			mockSelect.getAbility = jest.fn().mockReturnValue(existingAbility);

			const resolver = getAbility('test/ability');
			await resolver({
				dispatch: mockDispatch,
				registry: mockRegistry,
				select: mockSelect,
			});

			expect(mockSelect.getAbility).toHaveBeenCalledWith('test/ability');
			expect(mockRegistry.resolveSelect).not.toHaveBeenCalled();
			expect(mockDispatch).not.toHaveBeenCalled();
		});

		it('should handle non-existent abilities', async () => {
			const mockResolveSelect = {
				getEntityRecord: jest.fn().mockResolvedValue(null),
			};

			mockRegistry.resolveSelect.mockReturnValue(mockResolveSelect);
			mockSelect.getAbility = jest.fn().mockReturnValue(null);

			const resolver = getAbility('non-existent');
			await resolver({
				dispatch: mockDispatch,
				registry: mockRegistry,
				select: mockSelect,
			});

			expect(mockResolveSelect.getEntityRecord).toHaveBeenCalledWith(
				ENTITY_KIND,
				ENTITY_NAME,
				'non-existent'
			);
			expect(mockDispatch).not.toHaveBeenCalled();
		});

		it('should handle namespaced ability names', async () => {
			const mockAbility: Ability = {
				name: 'my-plugin/feature/action',
				label: 'Namespaced Action',
				description: 'Complex namespaced ability',
				input_schema: { type: 'object' },
				output_schema: { type: 'object' },
			};

			const mockResolveSelect = {
				getEntityRecord: jest.fn().mockResolvedValue(mockAbility),
			};

			mockRegistry.resolveSelect.mockReturnValue(mockResolveSelect);
			mockSelect.getAbility = jest.fn().mockReturnValue(null);

			const resolver = getAbility('my-plugin/feature/action');
			await resolver({
				dispatch: mockDispatch,
				registry: mockRegistry,
				select: mockSelect,
			});

			expect(mockResolveSelect.getEntityRecord).toHaveBeenCalledWith(
				ENTITY_KIND,
				ENTITY_NAME,
				'my-plugin/feature/action'
			);
			expect(mockDispatch).toHaveBeenCalledWith(
				receiveAbilities([mockAbility])
			);
		});
	});
});
