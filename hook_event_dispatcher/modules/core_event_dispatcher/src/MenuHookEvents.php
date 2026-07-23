<?php

namespace Drupal\core_event_dispatcher;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;

/**
 * Define events for menu hooks.
 */
final class MenuHookEvents {

  /**
   * Alter local tasks displayed on the page before they are rendered.
   *
   * @Event
   *
   * @see \Drupal\core_event_dispatcher\Event\Menu\MenuLocalTasksAlterEvent
   * @see core_event_dispatcher_menu_local_tasks_alter()
   * @see hook_menu_local_tasks_alter()
   *
   * @var string
   */
  public const MENU_LOCAL_TASKS_ALTER = HookEventDispatcherInterface::PREFIX . 'menu_local_tasks.alter';

}
