## Server-side rendering

For frontend configuration just follow the document https://inertiajs.com/server-side-rendering#setting-up-ssr

### Setting up Encore / webpack

To run the webpack properly install `webpack-node-externals`

```shell
npm install webpack-node-externals
```

Next we will create a new file namely `webpack.ssr.config.js` this is almost the same with
your `webpack.config.js`. Remember that you need to keep this both config.

```shell
touch webpack.ssr.mix.js
```

Here is an example file for `webpack.ssr.config.js`

```js
const Encore = require('@symfony/webpack-encore');
const webpackNodeExternals = require('webpack-node-externals');
const path = require('path');

if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore.setOutputPath('public/build-ssr/')
  .setPublicPath('/build-ssr')
  .enableVueLoader(() => {}, { version: 3 })
  .addEntry('ssr', './assets/ssr.js')
  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .enableSassLoader();

const config = Encore.getWebpackConfig();
config.target = 'node';
config.externals = [webpackNodeExternals()];

module.exports = config;
```

### Enabling SSR

To enable the ssr, you need to add a configuration for your package `config/packages/skipthedragon_inertia.yaml` file

```yaml
inertia:
  ssr:
    enabled: true
    url: 'http://127.0.0.1:13714/render'
```

### Building your application

You now have two build processes you need to run—one for your client-side bundle,
and another for your server-side bundle:

```shell
encore build
encore build -- -c ./webpack.ssr.config.js
```

The build folder for the ssr will be located in `public/build-ssr/ssr.js`.
You can change the path by changing the output path (setOutputPath) in your `./webpack.ssr.config.js`

### Running the Node.js Service

To run the ssr service, you will need to run it via node.

```shell
node public/build-ssr/ssr.js
```

This will be available in `http://127.0.0.1:13714` where this is the path we need to put in our `ssr.url`
