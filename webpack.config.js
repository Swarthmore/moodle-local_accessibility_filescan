const webpack = require("webpack");
const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

const config = {
    entry: [
        "./src/report.js"
    ],
    output: {
        path: path.resolve(__dirname, "views", "js"),
        filename: "report.js"
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: "style.css",
            chunkFilename: "[name].css"
        }),
    ],  
    module: {
        rules: [
            {
                test: /\.(js|jsx)$/,
                use: "babel-loader",
                exclude: /node_modules/
            },
            {
                test: /\.css$/,
                use: [MiniCssExtractPlugin.loader,
                    "css-loader"
                ]
            }
        ]
    },
    resolve: {
        extensions: [
            ".js",
            ".jsx"
        ]
    },
    devServer: {
        contentBase: "./dist"
    }
};

module.exports = config;