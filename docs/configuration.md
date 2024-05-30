## Setup root template

The first step to using Inertia is creating a root template. We recommend using `app.html.twig`. This template should
include your assets, as well as the `inertia(page)` and `inertiaHead(page)` functions

```html
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Inertia powered page</title>
    {% block stylesheets %} {{ encore_entry_link_tags('app') }} {% endblock %}
    {{ inertiaHead(page) }}
  </head>
  <body>
    {{ inertia(page) }} {% block javascripts %} {{
    encore_entry_script_tags('app') }} {% endblock %}
  </body>
</html>
```

The `inertia(page)` function is a helper function for creating our base `div`. It includes a `data-page` attribute which
contains the initial page information. This is what it looks like:

```html
<div
  id="app"
  data-page="<?php echo htmlspecialchars(json_encode($page)); ?>"
></div>
```

If you'd like a different root view, you can change it by creating a `config/packages/inertia.yaml` file
and including this config:

```yaml
inertia:
  root_view: 'name.html.twig'
```

## Set up the frontend adapter

Find a frontend adapter that you wish to use here https://github.com/inertiajs. These README files are using Laravel
Mix.
It's not hard translating this to Webpack Encore, follow the documentation
here: https://symfony.com/doc/current/frontend.html.

Some config examples:

- [Vue](./encore_config_examples/vue.md)
- [React](./encore_config_examples/react.md)
- [Svelte](./encore_config_examples/svelte.md)

## Continue with [usage](usage.md) section.
