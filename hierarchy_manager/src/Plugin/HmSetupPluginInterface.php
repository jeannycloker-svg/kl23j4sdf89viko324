<?php

namespace Drupal\hierarchy_manager\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Hierarchy Manager Setup Plugin plugins.
 */
interface HmSetupPluginInterface extends PluginInspectionInterface {

  /**
   * Get a list of bundles supported by the Setup Plugin.
   */
  public function getBundleOptions();

}
