# CKEditor 5 Paste Filter

This module allows you to filter content pasted into CKEditor 5 through a set
of regular expressions (search and replace).

The most common use case is cleaning up the markup of content from rich text
sources such as Microsoft Word and Google Docs. Drupal core ships with a
CKEditor 5 plugin which filters out some of the markup from these sources but
does not go as far as the CKEditor 4 "Paste from Word" functionality.

This is a CKEditor 5 version of [CKEditor Paste Filter] with additional
features. This module has been created as a separate project so that sites that
are transitioning over to CKEditor 5 can have both modules installed easily.

[CKEditor Paste Filter]: https://www.drupal.org/project/ckeditor_paste_filter


## Requirements

This module requires the CKEditor 5 module from Drupal core.


## Installation

- Install as you would normally install a contributed Drupal module. For
  further information, see _[Installing Drupal Modules]_.

[Installing Drupal Modules]: https://www.drupal.org/docs/extending-drupal/installing-drupal-modules


## Configuration

1. Add or edit a text format (/admin/config/content/formats)
2. Set the 'Text editor' of the text format to CKEditor 5
3. Under 'CKEditor 5 plugin settings' select the 'Paste filter' vertical tab
4. Enable the plugin by checking the 'Filter pasted content' checkbox
5. (Optional) Add/edit/remove the pre-configured filters if you have different
  filtering requirements:
    - To add filters, click the "Add another filter" button inside the Paste
    filter configuration form
    - To remove filters, empty the search and replace text boxes, the filters
    will be removed after saving the text format
6. Save the text format: Scroll to the bottom and click "Save configuration"
