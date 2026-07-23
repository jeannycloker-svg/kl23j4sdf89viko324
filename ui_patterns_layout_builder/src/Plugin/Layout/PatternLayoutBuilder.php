<?php

namespace Drupal\ui_patterns_layout_builder\Plugin\Layout;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\ui_patterns_layouts\Plugin\Layout\PatternLayout;

/**
 * Class PatternLayoutBuilder.
 *
 * @package Drupal\ui_patterns_layout_builder\Plugin\Layout
 */
class PatternLayoutBuilder extends PatternLayout implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Returns the region names.
   *
   * @return string[]
   *   The region names.
   */
  public function getRegionNames() {
    return $this->pluginDefinition->getRegionNames();
  }

  /**
   * Returns the region.
   *
   * @return array[]
   *   The regions.
   */
  public function getRegions() {
    return $this->pluginDefinition->getRegions();
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build['#layout'] = $this;
    $build = $build + $build['#fields'];
    return $build;
  }

}
