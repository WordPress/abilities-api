/**
 * Internal dependencies
 */
import type { Ability, AbilityCategory } from '../types';
interface AbilitiesAction {
    type: string;
    abilities?: Ability[];
    ability?: Ability;
    categories?: AbilityCategory[];
    category?: AbilityCategory;
    name?: string;
    slug?: string;
}
declare const _default: import("redux").Reducer<{
    abilitiesByName: Record<string, Ability>;
    categoriesBySlug: Record<string, AbilityCategory>;
}, AbilitiesAction, Partial<{
    abilitiesByName: Record<string, Ability> | undefined;
    categoriesBySlug: Record<string, AbilityCategory> | undefined;
}>>;
export default _default;
//# sourceMappingURL=reducer.d.ts.map