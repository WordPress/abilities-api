import type { ValidationError } from './types';
/**
 * Validates a value against a JSON Schema.
 *
 * @param value The value to validate.
 * @param args  The JSON Schema to validate against.
 * @param param Optional parameter name for error messages.
 * @return True if valid, error message string if invalid.
 */
export declare function validateValueFromSchema(value: any, args: Record<string, any>, param?: string): true | ValidationError;
//# sourceMappingURL=validation.d.ts.map