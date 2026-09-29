const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		'blocks.min': './assets/src/blocks.js',
		viewer: './assets/src/viewer/index.js',
		library: './assets/src/library/index.js',
		'document-editor': './assets/src/admin/document-editor.js',
		commands: './assets/src/admin/commands.js',
		documents: './assets/src/frontend/documents.scss',
	},
	output: {
		path: path.resolve(__dirname, 'assets/build'),
		filename: '[name].js',
		clean: true,
	},
};
