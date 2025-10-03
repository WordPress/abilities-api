/**
 * Internal dependencies
 */
import type { Ability } from '../types';
interface AbilitiesAction {
    type: string;
    abilities?: Ability[];
    ability?: Ability;
    name?: string;
}
declare const _default: import("redux").Reducer<{
    abilitiesByName: Record<string, Ability>;
}, AbilitiesAction, Partial<{
    abilitiesByName: Record<string, Ability> | undefined;
}>>;
export default _default;
//# sourceMappingURL=reducer.d.ts.map