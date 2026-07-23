<?php

/**
 * @file
 * Provide views data for redirect_404.module.
 */

use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\redirect_404\Hook\Redirect404ViewsHooks;

/**
 * Implements hook_views_data().
 */
#[LegacyHook]
function redirect_404_views_data() {
  return \Drupal::service(Redirect404ViewsHooks::class)->viewsData();
}
