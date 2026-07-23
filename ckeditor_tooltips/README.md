## CONTENTS OF THIS FILE

 * Introduction
 * Installation
 * Usage
 * DEV NOTES
 * Configuration
 * TODOs


## INTRODUCTION

The goal of this module is to create an ultimate CKEditor tooltip plugin, that
will include multiple tooltip libraries and functionalities.

Similar modules [Tooltip](https://www.drupal.org/project/tooltip),
[CKEditor Tippy Tooltip](https://www.drupal.org/project/ckeditor_tippy).


## INSTALLATION

1. Install it as a normal module.
2. In the "Text formats and editors" /admin/config/content/formats configure any of
   the Text formats, that you want the tooltip on. Drag and drop the tooltip icon
   to the "Active toolbar".
3. Go to "CKEditor Tooltips Configuration" /admin/config/content/ckeditor-tooltips
   for tooltip settings.


## USAGE

- In the Text format where you selected to use the Tooltip, move the cursor where you want to position the tooltip.
  - You can also select part of the text where you want to have the Tooltip.
- Click on the icon of the tooltip. A popup will appear.
- Enter the wanted content and save it.
- Selected text will appear underlined. If no text was selected a default icon with a letter "i" will appear.


## DEV NOTES

Use the module CKEditor 5 Dev Tools ([ckeditor5_dev](https://www.drupal.org/project/ckeditor5_dev)) to debug/inspect the CKEditor in Drupal.


Plugin developed from the instructions:
- Creating a basic plugin: https://ckeditor.com/docs/ckeditor5/latest/framework/guides/plugins/creating-simple-plugin-timestamp.html
- Creating an advanced plugin: https://ckeditor.com/docs/ckeditor5/latest/tutorials/abbreviation-plugin/abbreviation-plugin-level-1.html
- Fields: https://ckeditor.com/docs/ckeditor5/latest/api/module_ui_labeledfield_utils.html#function-createLabeledInputText

CKEditor5 UI Library
- https://ckeditor.com/docs/ckeditor5/latest/framework/architecture/ui-library.html


## CONFIGURATION

To install the dependencies run `npm install`.

After installing dependencies, plugins can be built with `npm run build` or
`npm run watch`. They will be built to `js/build/{pluginNameDirectory}.js`.


## TODOs

- Download the needed files to the download library or vendor folder of the project
- Sanitize entered text if needed
- Update documentation
- Create tests
- Beautify code
- Update webpack - (https://github.com/ckeditor/ckeditor5-tutorials-examples/blob/main/abbreviation-plugin/part-2/package.json)
