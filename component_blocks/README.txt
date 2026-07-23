INTRODUCTION
------------

This modules extends upon the functionality in the ui_patterns module to provide
layout builder blocks which can be used to link up fields/values from entities
or tokens to layouts defined in an ui_patterns definition.

You can define your template definitions by adding a
`module_name.ui_patterns.yml` file to your custom module or theme and specifying
which fields the template needs. When setting up the block configuration after
placing the block, each of these fields will be available as a form item which
can take a fixed value (or token value) or a field with field formatter for
input.

REQUIREMENTS
------------

 * components
 * layout_builder
 * ui_patterns

INSTALLATION
------------

 * Enable the module
 * You will need to do a cache rebuild after adding/updating layouts

CONFIGURATION
-------------

To start using this, you will need to create a new
`module_name.ui_patterns.yml` file in your custom module or theme to define each
of the templates you wish to make available. For example:

```
image_panel:
  label: Image Panel
  fields:
    title:
      label: Title
    body:
      label: Body
    link:
      label: Link
    media:
      label: Media
  use: "@some_namespace/layout/src/templates/patterns/section-media.twig"
    libraries:
      - some_theme/image-panel
```

Each item defined under the "fields" key will be made available in the block
configuration to setup later, unless you add `ui: false`. Each of the field
keys should match the variables used in your twig template file. The
corresponding twig template file for the above might look something like this:

```
<div class="image-panel">
  <div class="image-panel__img">
    {{ media }}
  </div>

  <div class="image-panel__content">
    <h2>{{ title }}</h2>
    {{ body }}
    {{ link }}
  </div>
</div>
```

The `use` key refers to the path to the twig file. If you have a
component library defined you can just reference the library with
"@[library-name]" as in the example above.

The `libraries` key attaches any required libraries to the block when rendered.
These libraries are defined in your theme theme_name.libraries.yml file.

After adding new definitions you will need to run `drush cr` for them to be
discovered, after that they will be made available as blocks in Layout
Builder.
