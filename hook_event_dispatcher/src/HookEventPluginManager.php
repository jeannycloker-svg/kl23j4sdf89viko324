<?php

namespace Drupal\hook_event_dispatcher;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\hook_event_dispatcher\Annotation\HookEvent;
use Drupal\hook_event_dispatcher\Event\EventInterface;
use Drupal\hook_event_dispatcher\Plugin\Factory\EventFactory;

/**
 * HookEvent plugin manager.
 *
 * @method Event createInstance($plugin_id, array $configuration = [])
 */
class HookEventPluginManager extends DefaultPluginManager implements HookEventPluginManagerInterface {

  /**
   * Constructs HookEventPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cacheBackend, ModuleHandlerInterface $moduleHandler) {
    parent::__construct(
      'Event',
      $namespaces,
      $moduleHandler,
      EventInterface::class,
      HookEvent::class
    );
    $this->alterInfo('hook_event_info');
    $this->setCacheBackend($cacheBackend, 'hook_event_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getFactory() {
    if (!$this->factory) {
      $this->factory = new EventFactory($this, $this->pluginInterface);
    }

    return $this->factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getHookEventFactories(string $hook): \Generator {
    foreach ($this->getDefinitions() as $definition) {
      if (isset($definition['hook']) && $definition['hook'] === $hook) {
        yield function (&...$args) use ($definition): Event {
          return $this->createInstance($definition['id'], $args);
        };
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAlterEventFactories(string $alter): \Generator {
    foreach ($this->getDefinitions() as $definition) {
      if (isset($definition['alter']) && $definition['alter'] === $alter) {
        yield function (&...$args) use ($definition): Event {
          return $this->createInstance($definition['id'], $args);
        };
      }
    }
  }

}
