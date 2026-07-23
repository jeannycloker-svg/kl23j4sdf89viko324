<?php

namespace Drupal\hierarchy_manager\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Hierarchy manager display plugin item annotation object.
 *
 * @see \Drupal\hierarchy_manager\Plugin\HmDisplayPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class HmDisplayPlugin extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
