module.exports = {
	root: true,
	extends: [
		'plugin:@wordpress/eslint-plugin/recommended',
		'plugin:eslint-comments/recommended',
	],
	plugins: [ 'import' ],
	parser: '@typescript-eslint/parser',
	parserOptions: {
		ecmaVersion: 2021,
		sourceType: 'module',
		ecmaFeatures: {
			jsx: true,
		},
		project: './tsconfig.json',
	},
	settings: {
		'import/resolver': {
			typescript: {
				project: './tsconfig.json',
			},
		},
	},
	env: {
		browser: true,
		es6: true,
		node: true,
	},
	rules: {
		'@wordpress/dependency-group': 'error',
		'@wordpress/data-no-store-string-literals': 'error',
		'import/default': 'error',
		'import/no-extraneous-dependencies': [
			'error',
			{
				devDependencies: [
					'**/*.@(spec|test).@(j|t)s?(x)',
					'**/@(webpack|jest).config.@(j|t)s',
					'**/scripts/**',
				],
			},
		],
	},
	overrides: [
		{
			files: [ '**/*.ts?(x)' ],
			rules: {
				'@typescript-eslint/consistent-type-imports': 'error',
				'@typescript-eslint/no-shadow': 'error',
				'no-shadow': 'off',
				'jsdoc/require-param': 'off',
				'jsdoc/require-param-type': 'off',
				'jsdoc/require-returns-type': 'off',
			},
		},
	],
};
