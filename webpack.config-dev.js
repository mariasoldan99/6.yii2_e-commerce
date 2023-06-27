const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const {merge} = require('webpack-merge');
const common = require('./webpack.config-common.js');
module.exports = merge(common, {
        mode: "development",
        watch: true,
        watchOptions: {
            ignored: ['vendor/**/*.js', 'frontend/web/**', 'node_modules/**']
        },
        devtool: "source-map"
    }
);