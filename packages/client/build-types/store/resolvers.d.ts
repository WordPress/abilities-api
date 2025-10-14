/**
 * Resolver for getAbilities selector.
 * Fetches all abilities from the server.
 *
 * The resolver only fetches once (without query args filter) and stores all abilities.
 * Query args filtering handled client-side by the selector for better performance
 * and to avoid multiple API requests when filtering by different categories.
 */
export declare function getAbilities(): ({ dispatch, registry, select }: {
    dispatch: any;
    registry: any;
    select: any;
}) => Promise<void>;
/**
 * Resolver for getAbility selector.
 * Fetches a specific ability from the server if not already in store.
 *
 * @param name Ability name.
 */
export declare function getAbility(name: string): ({ dispatch, registry, select }: {
    dispatch: any;
    registry: any;
    select: any;
}) => Promise<void>;
//# sourceMappingURL=resolvers.d.ts.map