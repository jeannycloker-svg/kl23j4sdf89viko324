<?php

namespace Drupal\core_event_dispatcher\Event\Entity;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Session\AccountInterface;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\hook_event_dispatcher\Event\AccessEventInterface;
use Drupal\hook_event_dispatcher\Event\AccessEventTrait;
use Drupal\hook_event_dispatcher\Event\EventFactoryInterface;
use Drupal\hook_event_dispatcher\Event\EventFactoryTrait;
use Drupal\hook_event_dispatcher\Event\EventInterface;
use Drupal\hook_event_dispatcher\Event\HookReturnInterface;

/**
 * Class EntityCreateAccessEvent.
 *
 * @HookEvent(
 *   id = "entity_create_access",
 *   hook = "entity_create_access"
 * )
 */
final class EntityCreateAccessEvent extends Event implements EventInterface, EventFactoryInterface, AccessEventInterface, HookReturnInterface {

  use EventFactoryTrait;
  use AccessEventTrait;

  /**
   * An associative array of additional context values.
   *
   * @var array
   */
  protected $context = [];

  /**
   * The entity bundle name.
   *
   * @var string|null
   */
  protected $entityBundle;

  /**
   * EntityCreateAccessEvent constructor.
   */
  public function __construct(AccountInterface $account, array $context, string $entityBundle = NULL) {
    $this->account = $account;
    $this->context = $context;
    $this->entityBundle = $entityBundle;
  }

  /**
   * Gets additional context values.
   *
   * @return array
   *   An array of additional context values.
   */
  public function getContext(): array {
    return $this->context;
  }

  /**
   * Gets the entity bundle name.
   *
   * @return string|null
   *   The entity bundle name.
   */
  public function getEntityBundle(): ?string {
    return $this->entityBundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getDispatcherType(): string {
    return EntityHookEvents::ENTITY_CREATE_ACCESS;
  }

  /**
   * {@inheritdoc}
   */
  public function getReturnValue() {
    return $this->getAccessResult();
  }

}
