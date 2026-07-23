<?php

namespace Drupal\hierarchy_manager\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Hierarchy manager display plugin plugin manager.
 */
class HmDisplayPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new HmDisplayPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/HmDisplayPlugin', $namespaces, $module_handler, 'Drupal\hierarchy_manager\Plugin\HmDisplayPluginInterface', 'Drupal\hierarchy_manager\Annotation\HmDisplayPlugin');

    $this->alterInfo('hierarchy_manager_hm_display_plugin_info');
    $this->setCacheBackend($cache_backend, 'hierarchy_manager_hm_display_plugin_plugins');
  }

}
