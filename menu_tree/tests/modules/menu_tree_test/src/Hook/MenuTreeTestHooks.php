<?php

declare(strict_types=1);

namespace Drupal\menu_tree_test\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderAfter;

/**
 * Implements hooks for 'menu_tree_test' module.
 */
class MenuTreeTestHooks {

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_node_form_alter', order: new OrderAfter(['menu_tree']))]
  public function nodeFormAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    $form['menu']['link']['prev_sibling']['#type'] = 'textfield';
    $form['menu']['link']['next_sibling']['#type'] = 'textfield';
  }

}
