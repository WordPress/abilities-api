/**
 * Jest configuration for @wordpress/abilities package
 */

const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...defaultConfig,
	// Exclude TypeScript declaration files from test runs
	testPathIgnorePatterns: [
		...( defaultConfig.testPathIgnorePatterns || [] ),
		'<rootDir>/build-types/',
		'\\.d\\.ts$',
	],
	// Only look for test files in src directory
	testMatch: [
		'<rootDir>/src/**/__tests__/**/*.[jt]s?(x)',
		'<rootDir>/src/**/?(*.)(spec|test).[jt]s?(x)',
	],
};
