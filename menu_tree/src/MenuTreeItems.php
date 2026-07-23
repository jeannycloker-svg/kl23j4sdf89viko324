<?php

declare(strict_types=1);

namespace Drupal\menu_tree;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;

/**
 * Provides functionality to retrieve and transform menu tree items.
 */
class MenuTreeItems {

  /**
   * Menu link tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected MenuLinkTreeInterface $menuLinkTree;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   Menu link tree service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(
    MenuLinkTreeInterface $menu_link_tree,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->menuLinkTree = $menu_link_tree;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Transform a menu tree into an array.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $links
   *   The links to transform.
   */
  protected function transform(array $links): array {
    $result = [];
    foreach ($links as $item) {
      // Per DefaultMenuLinkTreeManipulators::checkAccess(), which we run in
      // getMenuTree, "inaccessible links are *not* removed; it's up to the code
      // doing something with the tree to exclude inaccessible links, just like
      // MenuLinkTree::build() does" - whose code we replicate here.
      /**
       * @var \Drupal\Core\Menu\MenuLinkInterface $link
       */
      $link = $item->link;
      // Generally we only deal with visible links, but just in case.
      if (!$link->isEnabled()) {
        continue;
      }

      if ($item->access !== NULL && !$item->access instanceof AccessResultInterface) {
        throw new \DomainException('MenuLinkTreeElement::access must be either NULL or an AccessResultInterface object.');
      }

      // Only render accessible links.
      if ($item->access instanceof AccessResultInterface && !$item->access->isAllowed()) {
        continue;
      }

      // Build the link item.
      $transformed_link = [
        'text' => $link->getTitle(),
        'weight' => $link->getWeight(),
        'url' => $link->getUrlObject()->toString(),
        'id' => implode(':', [
          $item->link->getMenuName(),
          $item->link->getPluginId(),
        ]),
      ];

      if ($item->hasChildren) {
        $transformed_link['submenu'] = $this->transform($item->subtree);
      }
      $result[] = $transformed_link;
    }
    return $result;
  }

  /**
   * Get links for a menu.
   */
  public function getLinks(string $menu_id = 'main', string|null $exclude = NULL): array {
    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks();
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    if ($exclude) {
      $manipulators[] = [
        'callable' => 'menu_tree.menu_tree_manipulators:filterExcluded',
        'args' => [$exclude],
      ];
    }

    $tree = $this->menuLinkTree->load($menu_id, $parameters);
    $tree = $this->menuLinkTree->transform($tree, $manipulators);

    // Load menu entity.
    $menu = $this->entityTypeManager->getStorage('menu')->load($menu_id);

    if (empty($menu)) {
      return [];
    }

    return [
      'label' => $menu->label(),
      'id' => $menu_id,
      'menu_tree' => $this->transform($tree),
    ];
  }

}
