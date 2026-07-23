# Menu tree

This module replaces the standard widget for selecting *Parent link* on node
add and edit forms with a tree widget.

For a full description of the module, visit the [project page](https://www.drupal.org/project/menu_tree).

Submit bug reports and feature suggestions or track changes in the [issue queue](https://www.drupal.org/project/issues/menu_tree).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

To enable the menu tree widget for a node type visit the node type settings
page, for example `/admin/structure/types/manage/page` for the *Basic page*
content type, and click the *Menu settings* vertical tab. At the bottom, check
the **Use tree widget for parent link** checkbox and save the settings.


## Maintainers

[Happiness](https://www.drupal.org/happiness) sponsors this module maintenance.

- Peter Törnstrand - [Peter Törnstrand](https://www.drupal.org/u/peter-t%C3%B6rnstrand)

### Shout-out

- Much of the code for the Twig extension used before 2.1.x was borrowed from [Simplify Menu](https://www.drupal.org/project/simplify_menu).
- The new UI in the 2.x version is based on the article [Tree views in CSS](https://iamkate.com/code/tree-views/) by
[Kate Morley](https://iamkate.com).
