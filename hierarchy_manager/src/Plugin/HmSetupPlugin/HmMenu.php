<?php

namespace Drupal\hierarchy_manager\Plugin\HmSetupPlugin;

use Drupal\hierarchy_manager\Plugin\HmSetupPluginBase;
use Drupal\hierarchy_manager\Plugin\HmSetupPluginInterface;
use Drupal\system\Entity\Menu;

/**
 * Menu link hierarchy setup plugin.
 *
 * @HmSetupPlugin(
 *   id = "hm_setup_menu",
 *   label = @Translation("Menu link hierarchy setup plugin")
 * )
 */
class HmMenu extends HmSetupPluginBase implements HmSetupPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getBundleOptions() {
    $menus = Menu::loadMultiple();
    $options = [];
    /** @var \Drupal\system\Entity\Menu $menu */
    foreach ($menus as $menu) {
      $options[$menu->id()] = $menu->label();
    }
    return $options;
  }

}
