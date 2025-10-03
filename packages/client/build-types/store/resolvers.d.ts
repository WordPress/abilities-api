/**
 * Resolver for getAbilities selector.
 * Fetches all abilities from the server.
 */
export declare function getAbilities(): ({ dispatch, registry }: {
    dispatch: any;
    registry: any;
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