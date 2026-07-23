<?php

declare(strict_types=1);

namespace Drupal\hook_event_dispatcher;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hook_event_dispatcher\Event\HookReturnInterface;
use Drupal\hook_event_dispatcher\Manager\HookEventDispatcherManager;

/**
 * Hook event dispatcher module handler decorator.
 */
final class HookEventDispatcherModuleHandler implements ModuleHandlerInterface {

  use HookEventDispatcherModuleHandlerProxyTrait;

  /**
   * The decorated module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $inner;

  /**
   * The hook event dispatcher manager.
   *
   * @var \Drupal\hook_event_dispatcher\Manager\HookEventDispatcherManager
   */
  protected $dispatcherManager;

  /**
   * The hook event plugin manager.
   *
   * @var \Drupal\hook_event_dispatcher\HookEventPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * An array of hook event factories for a hook, keyed by hook name.
   *
   * @var callable[][]
   */
  private $hookFactories = [];

  /**
   * An array of alter event factories for an alter, keyed by alter name.
   *
   * @var callable[][]
   */
  private $alterFactories = [];

  /**
   * Constructs a new HookEventDispatcherModuleHandler.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $inner
   *   The inner module handler.
   * @param \Drupal\hook_event_dispatcher\Manager\HookEventDispatcherManager $dispatcherManager
   *   Hook event dispatcher manager.
   * @param \Drupal\hook_event_dispatcher\HookEventPluginManagerInterface $pluginManager
   *   The hook event plugin manager.
   */
  public function __construct(ModuleHandlerInterface $inner, HookEventDispatcherManager $dispatcherManager, HookEventPluginManagerInterface $pluginManager) {
    $this->inner = $inner;
    $this->dispatcherManager = $dispatcherManager;
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function invokeAll($hook, array $args = []) {
    $return = [];
    $this->invokeAllWith($hook, static function (callable $hookInvoker, string $module) use ($args, &$return) {
      $result = $hookInvoker(...$args);
      if (isset($result) && is_array($result)) {
        $return = NestedArray::mergeDeep($return, $result);
      }
      elseif (isset($result)) {
        $return[] = $result;
      }
    });
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function invokeAllWith(string $hook, callable $callback): void {
    $this->inner->invokeAllWith($hook, $callback);

    if (!isset($this->hookFactories[$hook])) {
      $this->hookFactories[$hook] = [];
      foreach ($this->pluginManager->getHookEventFactories($hook) as $eventFactory) {
        $this->hookFactories[$hook][] = $eventFactory;
      }
    }

    foreach ($this->hookFactories[$hook] as $eventFactory) {
      $callback(function (&...$args) use ($eventFactory) {
        $event = $eventFactory(...$args);
        $this->dispatcherManager->register($event);

        return ($event instanceof HookReturnInterface) ? $event->getReturnValue() : NULL;
      }, 'hook_event_dispatcher');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL): void {
    $this->inner->alter($type, $data, $context1, $context2);

    $types = is_array($type) ? $type : [$type];
    foreach ($types as $alter) {
      if (!isset($this->alterFactories[$alter])) {
        $this->alterFactories[$alter] = [];
        foreach ($this->pluginManager->getAlterEventFactories($alter) as $eventFactory) {
          $this->alterFactories[$alter][] = $eventFactory;
        }
      }

      foreach ($this->alterFactories[$alter] as $eventFactory) {
        $event = $eventFactory($data, $context1, $context2);
        $this->dispatcherManager->register($event);
      }
    }
  }

}
