### React + Encore + Inertia setup

For React:

```javascript
const Encore = require('@symfony/webpack-encore');
const path = require('path');

if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore.setOutputPath('public/build/')
  .setPublicPath('/build')
  .enableReactPreset()
  .addAliases({
    '@': path.resolve('assets/js'),
  })
  .addEntry('app', './assets/js/app.js')
  .splitEntryChunks()
  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .disableSingleRuntimeChunk()
  .configureBabel(() => {}, {
    useBuiltIns: 'usage',
    corejs: 3,
  })
  .enableSassLoader();

module.exports = Encore.getWebpackConfig();
```

## Continue with [usage](../usage.md) section.
