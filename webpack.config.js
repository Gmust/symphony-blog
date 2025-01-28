const path = require('path');
const ManifestPlugin = require('webpack-manifest-plugin').WebpackManifestPlugin;

module.exports = {
  mode: 'development',
  entry: './assets/app.js',
  output: {
    filename: 'bundle.js',
    path: path.resolve(__dirname, 'public/build')
  },
  module: {
    rules: [
      {
        test: /\.css$/,
        use: [
          'style-loader',
          'css-loader'
        ]
      }
    ]
  },
  plugins: [
    new ManifestPlugin({
      fileName: 'manifest.json'
    })
  ]
};
