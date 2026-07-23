<?php

namespace Drupal\hook_event_dispatcher\Manager;

use Drupal\Component\EventDispatcher\Event;
use Drupal\hook_event_dispatcher\Event\EventInterface;

/**
 * Class LegacyHookEventDispatcherManager.
 */
class LegacyHookEventDispatcherManager extends HookEventDispatcherManager {

  /**
   * {@inheritdoc}
   */
  public function register(EventInterface $event): Event {
    if (version_compare(\Drupal::VERSION, '9.4', '>=')) {
      return $event;
    }

    return parent::register($event);
  }

}
