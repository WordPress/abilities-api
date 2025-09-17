/**
 * WordPress dependencies
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
	...defaultConfig,
	entry: {
		index: './src/index.ts',
	},
	output: {
		...defaultConfig.output,
		library: {
			name: ['wp', 'abilities'],
			type: 'window',
		},
	},
};
