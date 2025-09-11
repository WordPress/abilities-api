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

describe('Store Actions', () => {
	describe('receiveAbilities', () => {
		it('should create an action to receive abilities', () => {
			const abilities: Ability[] = [
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

			const action = receiveAbilities(abilities);

			expect(action).toEqual({
				type: RECEIVE_ABILITIES,
				abilities,
			});
		});

		it('should handle empty abilities array', () => {
			const abilities: Ability[] = [];
			const action = receiveAbilities(abilities);

			expect(action).toEqual({
				type: RECEIVE_ABILITIES,
				abilities: [],
			});
		});
	});

	describe('registerAbility', () => {
		it('should create an action to register an ability', () => {
			const ability: Ability = {
				name: 'test/ability',
				label: 'Test Ability',
				description: 'Test ability description',
				location: 'client',
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

			const action = registerAbility(ability);

			expect(action).toEqual({
				type: REGISTER_ABILITY,
				ability,
			});
		});

		it('should register server-side abilities', () => {
			const ability: Ability = {
				name: 'test/server-ability',
				label: 'Server Ability',
				description: 'Server-side ability',
				location: 'server',
				input_schema: { type: 'object' },
				output_schema: { type: 'object' },
			};

			const action = registerAbility(ability);

			expect(action).toEqual({
				type: REGISTER_ABILITY,
				ability,
			});
		});
	});

	describe('unregisterAbility', () => {
		it('should create an action to unregister an ability', () => {
			const abilityName = 'test/ability';
			const action = unregisterAbility(abilityName);

			expect(action).toEqual({
				type: UNREGISTER_ABILITY,
				name: abilityName,
			});
		});

		it('should handle namespaced ability names', () => {
			const abilityName = 'my-plugin/feature/action';
			const action = unregisterAbility(abilityName);

			expect(action).toEqual({
				type: UNREGISTER_ABILITY,
				name: abilityName,
			});
		});
	});
});
