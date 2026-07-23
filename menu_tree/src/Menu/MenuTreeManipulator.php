<?php

declare(strict_types=1);

namespace Drupal\menu_tree\Menu;

use Drupal\Core\Menu\MenuLinkBase;

/**
 * Filters a menu tree by excluding specific links.
 */
class MenuTreeManipulator {

  /**
   * Filter a menu tree.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   * @param string $exclude
   *   Menu link ID to exclude from tree.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function filterExcluded(array $tree, string $exclude) : array {
    foreach ($tree as $key => $element) {
      if (!$element->link instanceof MenuLinkBase) {
        continue;
      }

      // Remove item.
      $id = $element->link->getMenuName() . ':' . $element->link->getPluginId();
      if ($id == $exclude) {
        unset($tree[$key]);
        return $tree;
      }

      // Filter children recursively.
      if ($element->hasChildren && !empty($element->subtree)) {
        $element->subtree = $this->filterExcluded($element->subtree, $exclude);
      }
    }

    return $tree;
  }

}
