<?php

namespace Drupal\hook_event_dispatcher\Manager;

use Drupal\Component\EventDispatcher\Event;
use Drupal\hook_event_dispatcher\Event\EventInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class HookEventDispatcherManager.
 *
 * Wrapper class for the external dispatcher dependency. If this ever changes
 * we only have to change it once.
 */
class HookEventDispatcherManager implements HookEventDispatcherManagerInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * EntityDispatcherManager constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher) {
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function register(EventInterface $event): Event {
    assert($event instanceof Event);
    // @phpstan-ignore-next-line
    if ($event->isPropagationStopped()) {
      return $event;
    }

    /** @var \Drupal\Component\EventDispatcher\Event */
    return $this->eventDispatcher->dispatch($event, $event->getDispatcherType());
  }

}
