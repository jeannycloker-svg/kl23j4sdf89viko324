CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

 INTRODUCTION
------------

Drupal provides a draggable table to manage the hierarchy of menu links 
and taxonomy terms. 
The Drupal draggable table is not able to present a massive hierarchy 
in one page.

This module provides a plugin architecture to delivery a flexibility 
of managing hierarchy for taxonomy terms, 
menu links and others. There are two out of box plugins, 
taxonomy hierarchy management plugin and menu hierarchy plugin. 
The front-end JavaScript libraries is also pluginable. 
The out of box display plugin using jsTree to render 
the hierarchy tree with filter. 
The hierarchy tree is draggable which means you can update
 the hierarchy by dragging a node in the tree.

Other modules can define their own management plugin to manage 
hierarchy for any other entities or display plugin to render 
the hierarchy tree by a JavaScript library other than jsTree.

REQUIREMENTS
------------

This module requires the following library:

* jsTree JS (This module will automatically load this library
from remote CDN if it wasn't hosted locally under 
/libraries/jquery.jstree/3.3.8/ folder)

INSTALLATION
------------

* Install as you would normally install a contributed Drupal module.

CONFIGURATION
-------------

* Go the hierarchy manage display management page 
(/admin/structure/hm_display_profile) under 
the Structure menu to create a display profile

* Go to the hierarchy management configuration page
 (/admin/config/user-interface/hierarchy_manager/config) to 
 enable hierarchy management plugins, such as taxonomy plugin, 
 and specify a display profile created in step above.

* Once a hierarchy mange plugin is enabled, the related edit form 
should be replaced with a hierarchy tree form. 
For instance, the taxonomy term edit form 
(/admin/structure/taxonomy/manage/{tid}/overview) 
will be replaced with a hierarchy tree implemented by 
the taxonomy hierarchy manage plugin.

MAINTAINERS
-----------

Mingsong Hu (Mingsong) - https://www.drupal.org/u/mingsong
