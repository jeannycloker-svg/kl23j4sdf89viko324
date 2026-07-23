<?php

namespace Drupal\menu_item_limit\Plugin\Validation\Constraint;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Entity Validator for menu_link_content which checks item limits.
 *
 * @package Drupal\menu_item_limit\Plugin\Validation\Constraint
 */
class MenuItemOverLimitValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {

    // If the item is not new, do not validate this item again.
    if (!$entity->isNew()) {
      return;
    }

    // Check limit of the menu the item will be added.
    if ($entity instanceof MenuLinkContent) {
      // Get item limit set for the menu of the item.
      $menu_name = $entity->getMenuName();
      $config = \Drupal::service('config.factory')->getEditable('menu_item_limit.settings');
      $limit = empty($config->get($menu_name)) ? 0 : $config->get($menu_name);

      // If the limit is set to unlimited, validate the form.
      if ($limit == 0) {
        return;
      }
      // Get the current amount of items and check if the count is
      // within limits.
      else {
        $menu_manager = \Drupal::service('menu.link_tree');
        $parameters = $menu_manager->getCurrentRouteMenuTreeParameters($menu_name);

        // Load the tree based on this set of parameters.
        $tree = $menu_manager->load($menu_name, $parameters);
        $item_count = !empty($tree) ? count($tree) : 0;

        // Add a violation if the amount of items has already been reached or
        // would exceed if the new item would be added to the menu.
        if ($item_count >= $limit) {
          $this->context->addViolation($constraint->overMenuItemLimit, ['%value' => $entity]);
        }
      }
    }
  }

}
