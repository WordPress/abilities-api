/**
 * Internal dependencies
 */
import type { Ability } from '../types';
/**
 * Returns an action object used to receive abilities into the store.
 *
 * @param abilities Array of abilities to store.
 * @return Action object.
 */
export declare function receiveAbilities(abilities: Ability[]): {
    type: string;
    abilities: Ability[];
};
/**
 * Registers an ability in the store.
 *
 * This action validates the ability before registration. If validation fails,
 * an error will be thrown.
 *
 * @param  ability The ability to register.
 * @return Action object or function.
 * @throws {Error} If validation fails.
 */
export declare function registerAbility(ability: Ability): ({ select, dispatch }: {
    select: any;
    dispatch: any;
}) => void;
/**
 * Returns an action object used to unregister a client-side ability.
 *
 * @param name The name of the ability to unregister.
 * @return Action object.
 */
export declare function unregisterAbility(name: string): {
    type: string;
    name: string;
};
//# sourceMappingURL=actions.d.ts.map