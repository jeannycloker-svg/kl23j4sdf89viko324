<?php

namespace Drupal\layoutbuilder_search_api;

use Drupal\Component\Plugin\DerivativeInspectionInterface;

/**
 * The LayoutbuilderSearchApiManager class with ContentBlocks.
 */
class LayoutbuilderSearchApiManager {

  /**
   * {@inheritdoc}
   */
  public function getContentBlocks(array $sections) {
    $content_blocks = [];
    foreach ($sections as $section) {
      foreach ($section->getComponents() as $component) {
        $plugin = $component->getPlugin();
        if ($plugin instanceof DerivativeInspectionInterface && $plugin->getBaseId() === 'block_content') {
          $content_blocks[] = $component;
        }
      }
    }

    return $content_blocks;
  }

}
