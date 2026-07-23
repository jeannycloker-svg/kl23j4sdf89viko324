<?php

namespace Drupal\hierarchy_manager\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Hierarchy Manager Setup Plugin item annotation object.
 *
 * @see \Drupal\hierarchy_manager\Plugin\HmSetupPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class HmSetupPlugin extends Plugin {


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
