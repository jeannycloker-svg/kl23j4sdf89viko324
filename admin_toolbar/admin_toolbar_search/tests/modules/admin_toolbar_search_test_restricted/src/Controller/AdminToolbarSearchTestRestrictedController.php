<?php

declare(strict_types=1);

namespace Drupal\admin_toolbar_search_test_restricted\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns a placeholder settings page for the restricted test module.
 */
class AdminToolbarSearchTestRestrictedController extends ControllerBase {

  /**
   * Returns a simple placeholder render array.
   *
   * @return array<string, string>
   *   A render array.
   */
  public function content(): array {
    return [
      '#markup' => 'Admin Toolbar Search Test Restricted settings page.',
    ];
  }

}
