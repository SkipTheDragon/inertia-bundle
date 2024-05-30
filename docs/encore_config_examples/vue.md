### Vue + Encore + Inertia setup

For Vue:

```console
yarn add @inertiajs/inertia-vue
```

```javascript
const Encore = require('@symfony/webpack-encore');
const path = require('path');

if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore.setOutputPath('public/build/')
  .setPublicPath('/build')
  .enableVueLoader()
  .addAliases({
    vue$: 'vue/dist/vue.runtime.esm.js',
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

```javascript
//assets/app.js
import { createInertiaApp } from '@inertiajs/inertia-vue';
import Vue from 'vue';

createInertiaApp({
  resolve: (name) => require(`./Pages/${name}`),
  setup({ el, app, props }) {
    new Vue({
      render: (h) => h(app, props),
    }).$mount(el);
  },
});
```

## Continue with [usage](../usage.md) section.
