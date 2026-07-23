<?php

namespace Drupal\menu_entity_index\Drush\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\menu_entity_index\TrackerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Psr\Container\ContainerInterface;

/**
 * Drush commands for Menu Entity Index.
 */
class MenuEntityIndexCommands extends DrushCommands {

  /**
   * Constructs a new MenuEntityIndexCommands object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   * @param \Drupal\menu_entity_index\TrackerInterface $tracker
   *   The tracker.
   */
  public function __construct(
    protected readonly Connection $database,
    protected readonly ModuleExtensionList $moduleExtensionList,
    protected readonly TrackerInterface $tracker,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('database'),
      $container->get('extension.list.module'),
      $container->get('menu_entity_index.tracker')
    );
  }

  /**
   * Rebuild index.
   */
  #[CLI\Command(name: 'menu-entity-index:rebuild-index', aliases: ['mei-r'])]
  #[CLI\Argument(name: 'menu', description: 'Menu id')]
  #[CLI\Usage(name: 'menu-entity-index:rebuild-index', description: 'Rebuild the index for all tracked menus.')]
  #[CLI\Usage(name: 'menu-entity-index:rebuild-index main', description: 'Rebuild the index for a menu with ID of main.')]
  public function rebuildIndexCommand(?string $menu = NULL): bool {
    $tracked_menus = $this->tracker->getTrackedMenus();

    // Menu must be tracked.
    if ($menu && !in_array($menu, $tracked_menus)) {
      $this->io()->error(dt("Menu $menu is not a valid tracked menu."));
      return DrushCommands::EXIT_FAILURE;
    }

    // Tracked menus must not be empty.
    if (empty($tracked_menus)) {
      $this->io()->error(dt('No menus setup for tracking.'));
      return DrushCommands::EXIT_FAILURE;
    }

    $menus = $menu ? [$menu] : $tracked_menus;

    // @todo Update TrackerInterface to simplify code in 2.x.
    $this->database->delete('menu_entity_index')
      ->condition('menu_name', (array) $menus, 'IN')
      ->execute();

    $operations = array_map(function ($menu) {
      return ['menu_entity_index_track_batch', [[$menu]]];
    }, $menus);

    batch_set([
      'operations' => $operations,
      'file' => $this->moduleExtensionList->getPath('menu_entity_index') . '/menu_entity_index.batch.inc',
    ]);
    drush_backend_batch_process();
    return DrushCommands::EXIT_SUCCESS;
  }

}
