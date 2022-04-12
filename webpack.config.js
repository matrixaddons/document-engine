const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const {CleanWebpackPlugin} = require("clean-webpack-plugin");
const path = require("path");

const config = {
    ...defaultConfig,
    entry: {
        "blocks.min": "./assets/src/blocks.js",
    },
    output: {
        path: path.resolve(__dirname, "assets/build"),
        filename: "[name].js",
    },
    plugins: [...defaultConfig.plugins, new CleanWebpackPlugin()],
};

module.exports = config;
