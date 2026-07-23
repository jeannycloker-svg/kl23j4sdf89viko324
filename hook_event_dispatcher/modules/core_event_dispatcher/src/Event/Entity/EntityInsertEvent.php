<?php

namespace Drupal\core_event_dispatcher\Event\Entity;

use Drupal\core_event_dispatcher\EntityHookEvents;

/**
 * Class EntityInsertEvent.
 *
 * @HookEvent(
 *   id = "entity_insert",
 *   hook = "entity_insert"
 * )
 */
class EntityInsertEvent extends AbstractEntityEvent {

  /**
   * {@inheritdoc}
   */
  public function getDispatcherType(): string {
    return EntityHookEvents::ENTITY_INSERT;
  }

}
