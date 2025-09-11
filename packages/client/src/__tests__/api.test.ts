/**
 * Tests for API functions.
 */

/**
 * WordPress dependencies
 */
import { dispatch, resolveSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	listAbilities,
	getAbility,
	registerAbility,
	unregisterAbility,
	executeAbility,
} from '../api';
import { store } from '../store';
import type { Ability } from '../types';

// Mock WordPress dependencies
jest.mock('@wordpress/data', () => ({
	dispatch: jest.fn(),
	resolveSelect: jest.fn(),
}));

jest.mock('@wordpress/api-fetch');

jest.mock('../store', () => ({
	store: 'abilities-api/store',
}));

describe('API functions', () => {
	beforeEach(() => {
		jest.clearAllMocks();
	});

	describe('listAbilities', () => {
		it('should resolve and return all abilities from the store', async () => {
			const mockAbilities: Ability[] = [
				{
					name: 'test/ability1',
					label: 'Test Ability 1',
					description: 'First test ability',
					location: 'server',
					input_schema: { type: 'object' },
					output_schema: { type: 'object' },
				},
				{
					name: 'test/ability2',
					label: 'Test Ability 2',
					description: 'Second test ability',
					location: 'client',
					input_schema: { type: 'object' },
					output_schema: { type: 'object' },
				},
			];

			const mockGetAbilities = jest.fn().mockResolvedValue(mockAbilities);
			(resolveSelect as jest.Mock).mockReturnValue({
				getAbilities: mockGetAbilities,
			});

			const result = await listAbilities();

			expect(resolveSelect).toHaveBeenCalledWith(store);
			expect(mockGetAbilities).toHaveBeenCalled();
			expect(result).toEqual(mockAbilities);
		});
	});

	describe('getAbility', () => {
		it('should return a specific ability by name', async () => {
			const mockAbility: Ability = {
				name: 'test/ability',
				label: 'Test Ability',
				description: 'Test ability description',
				location: 'server',
				input_schema: { type: 'object' },
				output_schema: { type: 'object' },
			};

			const mockGetAbility = jest.fn().mockResolvedValue(mockAbility);
			(resolveSelect as jest.Mock).mockReturnValue({
				getAbility: mockGetAbility,
			});

			const result = await getAbility('test/ability');

			expect(resolveSelect).toHaveBeenCalledWith(store);
			expect(mockGetAbility).toHaveBeenCalledWith('test/ability');
			expect(result).toEqual(mockAbility);
		});

		it('should return null if ability not found', async () => {
			const mockGetAbility = jest.fn().mockResolvedValue(null);
			(resolveSelect as jest.Mock).mockReturnValue({
				getAbility: mockGetAbility,
			});

			const result = await getAbility('non-existent');

			expect(mockGetAbility).toHaveBeenCalledWith('non-existent');
			expect(result).toBeNull();
		});
	});

	describe('registerAbility', () => {
		it('should register a client-side ability with a callback', () => {
			const mockRegisterAbility = jest.fn();
			(dispatch as jest.Mock).mockReturnValue({
				registerAbility: mockRegisterAbility,
			});

			const ability = {
				name: 'test/client-ability',
				label: 'Client Ability',
				description: 'Test client ability',
				location: 'client' as const,
				input_schema: { type: 'object' },
				output_schema: { type: 'object' },
				callback: jest.fn(),
			};

			registerAbility(ability);

			expect(dispatch).toHaveBeenCalledWith(store);
			expect(mockRegisterAbility).toHaveBeenCalledWith(ability);
		});

		it('should throw error for non-client abilities', () => {
			const mockRegisterAbility = jest.fn();
			(dispatch as jest.Mock).mockReturnValue({
				registerAbility: mockRegisterAbility,
			});

			const ability = {
				name: 'test/server-ability',
				label: 'Server Ability',
				description: 'Test server ability',
				location: 'server' as const,
				input_schema: { type: 'object' },
				output_schema: { type: 'object' },
			};

			expect(() => registerAbility(ability as any)).toThrow(
				'Client abilities must include a callback function'
			);
		});

		it('should throw error for client abilities without callback', () => {
			const mockRegisterAbility = jest.fn();
			(dispatch as jest.Mock).mockReturnValue({
				registerAbility: mockRegisterAbility,
			});

			const ability = {
				name: 'test/client-ability',
				label: 'Client Ability',
				description: 'Test client ability',
				location: 'client' as const,
				input_schema: { type: 'object' },
				output_schema: { type: 'object' },
			};

			expect(() => registerAbility(ability as any)).toThrow(
				'Client abilities must include a callback function'
			);
		});

		it('should throw error for ability without name', () => {
			const ability = {
				label: 'Test Ability',
				description: 'Test ability',
				callback: jest.fn(),
			};

			expect(() => registerAbility(ability as any)).toThrow(
				'Ability name is required'
			);
		});

		it('should throw error for ability without label', () => {
			const ability = {
				name: 'test/ability',
				description: 'Test ability',
				callback: jest.fn(),
			};

			expect(() => registerAbility(ability as any)).toThrow(
				'Ability label is required'
			);
		});

		it('should throw error for ability without description', () => {
			const ability = {
				name: 'test/ability',
				label: 'Test Ability',
				callback: jest.fn(),
			};

			expect(() => registerAbility(ability as any)).toThrow(
				'Ability description is required'
			);
		});
	});

	describe('unregisterAbility', () => {
		it('should unregister an ability', () => {
			const mockUnregisterAbility = jest.fn();
			(dispatch as jest.Mock).mockReturnValue({
				unregisterAbility: mockUnregisterAbility,
			});

			unregisterAbility('test/ability');

			expect(dispatch).toHaveBeenCalledWith(store);
			expect(mockUnregisterAbility).toHaveBeenCalledWith('test/ability');
		});
	});

	describe('executeAbility', () => {
		it('should execute a server-side ability via API', async () => {
			const mockAbility: Ability = {
				name: 'test/server-ability',
				label: 'Server Ability',
				description: 'Test server ability',
				location: 'server',
				input_schema: {
					type: 'object',
					properties: {
						message: { type: 'string' },
					},
					required: ['message'],
				},
				output_schema: { type: 'object' },
			};

			const mockGetAbility = jest.fn().mockResolvedValue(mockAbility);
			(resolveSelect as jest.Mock).mockReturnValue({
				getAbility: mockGetAbility,
			});

			const mockResponse = { success: true, result: 'test' };
			(apiFetch as unknown as jest.Mock).mockResolvedValue(mockResponse);

			const input = { message: 'Hello' };
			const result = await executeAbility('test/server-ability', input);

			expect(mockGetAbility).toHaveBeenCalledWith('test/server-ability');
			expect(apiFetch).toHaveBeenCalledWith({
				path: '/wp/v2/abilities/test/server-ability/run',
				method: 'POST',
				data: { input },
			});
			expect(result).toEqual(mockResponse);
		});

		it('should execute a client-side ability locally', async () => {
			const mockCallback = jest.fn().mockResolvedValue({ success: true });
			const mockAbility: Ability = {
				name: 'test/client-ability',
				label: 'Client Ability',
				description: 'Test client ability',
				location: 'client',
				input_schema: { type: 'object' },
				output_schema: { type: 'object' },
				callback: mockCallback,
			};

			const mockGetAbility = jest.fn().mockResolvedValue(mockAbility);
			(resolveSelect as jest.Mock).mockReturnValue({
				getAbility: mockGetAbility,
			});

			const input = { test: 'data' };
			const result = await executeAbility('test/client-ability', input);

			expect(mockGetAbility).toHaveBeenCalledWith('test/client-ability');
			expect(mockCallback).toHaveBeenCalledWith(input);
			expect(apiFetch).not.toHaveBeenCalled();
			expect(result).toEqual({ success: true });
		});

		it('should throw error if ability not found', async () => {
			const mockGetAbility = jest.fn().mockResolvedValue(null);
			(resolveSelect as jest.Mock).mockReturnValue({
				getAbility: mockGetAbility,
			});

			await expect(executeAbility('non-existent', {})).rejects.toThrow(
				'Ability not found: non-existent'
			);
		});

		it('should validate input for client abilities', async () => {
			const mockCallback = jest.fn();
			const mockAbility: Ability = {
				name: 'test/client-ability',
				label: 'Client Ability',
				description: 'Test client ability',
				location: 'client',
				input_schema: {
					type: 'object',
					properties: {
						message: { type: 'string' },
					},
					required: ['message'],
				},
				output_schema: { type: 'object' },
				callback: mockCallback,
			};

			const mockGetAbility = jest.fn().mockResolvedValue(mockAbility);
			(resolveSelect as jest.Mock).mockReturnValue({
				getAbility: mockGetAbility,
			});

			await expect(
				executeAbility('test/client-ability', {})
			).rejects.toThrow('invalid input');
		});

		it('should execute a resource-type ability via GET', async () => {
			const mockAbility: Ability = {
				name: 'test/resource',
				label: 'Resource Ability',
				description: 'Test resource ability',
				location: 'server',
				meta: { type: 'resource' },
				input_schema: {
					type: 'object',
					properties: {
						id: { type: 'string' },
						format: { type: 'string' },
					},
				},
				output_schema: { type: 'object' },
			};

			const mockGetAbility = jest.fn().mockResolvedValue(mockAbility);
			(resolveSelect as jest.Mock).mockReturnValue({
				getAbility: mockGetAbility,
			});

			const mockResponse = { data: 'resource data' };
			(apiFetch as unknown as jest.Mock).mockResolvedValue(mockResponse);

			const input = { id: '123', format: 'json' };
			const result = await executeAbility('test/resource', input);

			expect(apiFetch).toHaveBeenCalledWith({
				path: '/wp/v2/abilities/test/resource/run?input%5Bid%5D=123&input%5Bformat%5D=json',
				method: 'GET',
			});
			expect(result).toEqual(mockResponse);
		});

		it('should execute a resource-type ability with empty input', async () => {
			const mockAbility: Ability = {
				name: 'test/resource',
				label: 'Resource Ability',
				description: 'Test resource ability',
				location: 'server',
				meta: { type: 'resource' },
				input_schema: { type: 'object' },
				output_schema: { type: 'object' },
			};

			const mockGetAbility = jest.fn().mockResolvedValue(mockAbility);
			(resolveSelect as jest.Mock).mockReturnValue({
				getAbility: mockGetAbility,
			});

			const mockResponse = { data: 'all resources' };
			(apiFetch as unknown as jest.Mock).mockResolvedValue(mockResponse);

			const result = await executeAbility('test/resource', {});

			expect(apiFetch).toHaveBeenCalledWith({
				path: '/wp/v2/abilities/test/resource/run',
				method: 'GET',
			});
			expect(result).toEqual(mockResponse);
		});

		it('should handle errors in client ability execution', async () => {
			const consoleErrorSpy = jest
				.spyOn(console, 'error')
				.mockImplementation();
			const executionError = new Error('Execution failed');
			const mockCallback = jest.fn().mockRejectedValue(executionError);

			const mockAbility: Ability = {
				name: 'test/client-ability',
				label: 'Client Ability',
				description: 'Test client ability',
				location: 'client',
				input_schema: { type: 'object' },
				output_schema: { type: 'object' },
				callback: mockCallback,
			};

			const mockGetAbility = jest.fn().mockResolvedValue(mockAbility);
			(resolveSelect as jest.Mock).mockReturnValue({
				getAbility: mockGetAbility,
			});

			await expect(
				executeAbility('test/client-ability', {})
			).rejects.toThrow('Execution failed');

			expect(consoleErrorSpy).toHaveBeenCalledWith(
				'Error executing client ability test/client-ability:',
				executionError
			);

			consoleErrorSpy.mockRestore();
		});

		it('should handle errors in server ability execution', async () => {
			const consoleErrorSpy = jest
				.spyOn(console, 'error')
				.mockImplementation();
			const apiError = new Error('API request failed');

			const mockAbility: Ability = {
				name: 'test/server-ability',
				label: 'Server Ability',
				description: 'Test server ability',
				location: 'server',
				input_schema: { type: 'object' },
				output_schema: { type: 'object' },
			};

			const mockGetAbility = jest.fn().mockResolvedValue(mockAbility);
			(resolveSelect as jest.Mock).mockReturnValue({
				getAbility: mockGetAbility,
			});

			(apiFetch as unknown as jest.Mock).mockRejectedValue(apiError);

			await expect(
				executeAbility('test/server-ability', {})
			).rejects.toThrow('API request failed');

			expect(consoleErrorSpy).toHaveBeenCalledWith(
				'Error executing ability test/server-ability:',
				apiError
			);

			consoleErrorSpy.mockRestore();
		});

		it('should throw error when client ability is missing callback during execution', async () => {
			const mockAbility: Ability = {
				name: 'test/client-ability',
				label: 'Client Ability',
				description: 'Test client ability',
				location: 'client',
				input_schema: { type: 'object' },
				output_schema: { type: 'object' },
				// Intentionally missing callback to test the edge case
			} as Ability;

			const mockGetAbility = jest.fn().mockResolvedValue(mockAbility);
			(resolveSelect as jest.Mock).mockReturnValue({
				getAbility: mockGetAbility,
			});

			await expect(
				executeAbility('test/client-ability', {})
			).rejects.toThrow(
				'Client ability test/client-ability is missing callback function'
			);
		});

		it('should validate output for client abilities', async () => {
			const mockCallback = jest
				.fn()
				.mockResolvedValue({ invalid: 'response' });
			const mockAbility: Ability = {
				name: 'test/client-ability',
				label: 'Client Ability',
				description: 'Test client ability',
				location: 'client',
				input_schema: { type: 'object' },
				output_schema: {
					type: 'object',
					properties: {
						result: { type: 'string' },
					},
					required: ['result'],
				},
				callback: mockCallback,
			};

			const mockGetAbility = jest.fn().mockResolvedValue(mockAbility);
			(resolveSelect as jest.Mock).mockReturnValue({
				getAbility: mockGetAbility,
			});

			await expect(
				executeAbility('test/client-ability', {})
			).rejects.toThrow('invalid output');
		});
	});
});
