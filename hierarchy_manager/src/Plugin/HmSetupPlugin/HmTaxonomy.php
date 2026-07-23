<?php

namespace Drupal\hierarchy_manager\Plugin\HmSetupPlugin;

use Drupal\hierarchy_manager\Plugin\HmSetupPluginBase;
use Drupal\hierarchy_manager\Plugin\HmSetupPluginInterface;

/**
 * Taxonomy hierarchy setup plugin.
 *
 * @HmSetupPlugin(
 *   id = "hm_setup_taxonomy",
 *   label = @Translation("Taxonomy hierarchy setup plugin")
 * )
 */
class HmTaxonomy extends HmSetupPluginBase implements HmSetupPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getBundleOptions() {
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('taxonomy_term');
    $options = [];
    foreach ($bundles as $key => $value) {
      $options[$key] = $value['label'];
    }
    return $options;
  }

}
