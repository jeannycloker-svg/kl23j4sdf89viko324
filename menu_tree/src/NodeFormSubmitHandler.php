<?php

declare(strict_types=1);

namespace Drupal\menu_tree;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Provides a custom node form submit handler.
 *
 * The handler places a menu link at the configured position in the current
 * tree branch and calculates weights for all menu links in that branch.
 */
class NodeFormSubmitHandler implements ContainerInjectionInterface {

  use DependencySerializationTrait;

  /**
   * Menu link tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected MenuLinkTreeInterface $menuLinkTree;

  /**
   * Menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected MenuLinkManagerInterface $menuLinkManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   Menu link tree service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   Menu link manager.
   */
  public function __construct(
    MenuLinkTreeInterface $menu_link_tree,
    MenuLinkManagerInterface $menu_link_manager,
  ) {
    $this->menuLinkTree = $menu_link_tree;
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('menu.link_tree'),
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * Handle submit of a node form.
   *
   * Positions a menu link at the configured position and saves new weights for
   * menu link objects in the current branch.
   */
  public function handleFormSubmit($form, FormStateInterface $form_state): void {
    /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();

    /** @var \Drupal\node\NodeInterface $node */
    $node = $form_object->getEntity();

    // Get parent.
    $values = $form_state->getValue('menu');
    $parent = $values['menu_parent'];

    // Menu item is not enabled for this node.
    if (!$values['enabled']) {
      return;
    }

    // Get the menu name and plugin ID.
    [$menu, $parent_id] = explode(':', $parent, 2);

    // Create a tree parameter object.
    $params = new MenuTreeParameters();
    if ($parent !== $menu . ':') {
      $params->setRoot($parent_id);
      $params->excludeRoot();
    }

    // Load the menu tree.
    $tree = $this->menuLinkTree->load($menu, $params);

    // Make sure we have a tree.
    if (empty($tree)) {
      return;
    }

    // Sort the menu tree.
    if (count($tree) > 1) {
      $tree = $this->menuLinkTree->transform($tree, [
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ]);
    }

    // Loop through the menu tree and extract links.
    $links = [];
    $menu_link = NULL;
    foreach ($tree as $item) {
      // Exclude the current menu link from the links.
      if ($item->link->getPluginId() == $values['id']) {
        $menu_link = $item->link;
        continue;
      }
      $key = $item->link->getMenuName() . ':' . $item->link->getPluginId();
      $links[$key] = $item->link;
    }

    // If the menu link is not in the current tree, load id based on ID or if
    // it's a new menu link, load it based on entity ID. If no ID or entity ID
    // is available, we resort to loading node defaults.
    if ($menu_link === NULL) {
      if ($values['id']) {
        $menu_link = $this->menuLinkManager->createInstance($values['id']);
      }
      elseif ($values['entity_id']) {
        $entity = MenuLinkContent::load($values['entity_id']);
        $menu_link = $this->menuLinkManager->createInstance($entity->getPluginId());
      }
      else {
        // Drupal 10.3 does not set the menu.entity_id in the form state, so we
        // resort to loading defaults for the node.
        $defaults = menu_ui_get_menu_link_defaults($node);
        $menu_link = $this->menuLinkManager->createInstance($defaults['id']);
      }

      // Remove the current menu link from the links.
      unset($links[$menu_link->getMenuName() . ':' . $menu_link->getPluginId()]);
    }

    // Get user input.
    $input = $form_state->getUserInput();

    // Add the menu link at the correct position.
    if (empty($input['menu']['prev_sibling'])) {
      // Add the menu item as the first element.
      array_unshift($links, $menu_link);
    }
    elseif (empty($input['menu']['next_sibling'])) {
      // Add the menu item as the last element.
      $links[] = $menu_link;
    }
    else {
      // Add the menu item after the previous sibling.
      $offset = array_search($input['menu']['prev_sibling'], array_keys($links));
      array_splice($links, $offset + 1, 0, [$menu_link]);
    }

    // Update menu link weights.
    $weight = -50;
    foreach ($links as $link) {
      $this->menuLinkManager->updateDefinition($link->getPluginId(), ['weight' => $weight]);
      $weight++;
    }
  }

}
