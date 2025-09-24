/**
 * WordPress dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	context: __dirname,
	entry: {
		index: './src/index.ts',
	},
	output: {
		...defaultConfig.output,
		path: __dirname + '/build',
		library: {
			name: [ 'wp', 'abilities' ],
			type: 'window',
		},
	},
};
