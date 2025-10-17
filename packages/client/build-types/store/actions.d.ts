/**
 * Internal dependencies
 */
import type { Ability, AbilityCategory, AbilityCategoryArgs } from '../types';
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
 * Returns an action object used to receive categories into the store.
 *
 * @param categories Array of categories to store.
 * @return Action object.
 */
export declare function receiveCategories(categories: AbilityCategory[]): {
    type: string;
    categories: AbilityCategory[];
};
/**
 * Registers an ability in the store.
 *
 * This action validates the ability before registration. If validation fails,
 * an error will be thrown. Categories will be automatically fetched from the
 * REST API if they haven't been loaded yet.
 *
 * @param  ability The ability to register.
 * @return Action object or function.
 * @throws {Error} If validation fails.
 */
export declare function registerAbility(ability: Ability): ({ select, dispatch }: {
    select: any;
    dispatch: any;
}) => Promise<void>;
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
/**
 * Registers a client-side ability category in the store.
 *
 * This action validates the category before registration. If validation fails,
 * an error will be thrown. Categories will be automatically fetched from the
 * REST API if they haven't been loaded yet to check for duplicates.
 *
 * @param  slug The unique category slug identifier.
 * @param  args Category arguments (label, description, optional meta).
 * @return Action object or function.
 * @throws {Error} If validation fails.
 */
export declare function registerAbilityCategory(slug: string, args: AbilityCategoryArgs): ({ select, dispatch }: {
    select: any;
    dispatch: any;
}) => Promise<void>;
/**
 * Returns an action object used to unregister a client-side ability category.
 *
 * @param slug The slug of the category to unregister.
 * @return Action object.
 */
export declare function unregisterAbilityCategory(slug: string): {
    type: string;
    slug: string;
};
//# sourceMappingURL=actions.d.ts.map