<?php

namespace Drupal\menu_item_limit\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @Constraint(
 *   id = "MenuItemOverLimit",
 *   label = @Translation("Menu would have too many items.", context = "Validation"),
 *   type = "string"
 * )
 */
class MenuItemOverLimit extends Constraint {

  /**
   * The error message to be returned if validation fails.
   *
   * @var string
   */
  public $overMenuItemLimit = 'New link cannot be added because the menu item limit has been reached.';

}
