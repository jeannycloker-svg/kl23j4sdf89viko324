<?php

namespace Drupal\metatag_views\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\ViewEntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for metatag_views.
 */
class MetatagHooks {

  /**
   * Implements hook_metatags_alter().
   */
  #[Hook('metatags_alter')]
  public function metatagsAlter(array &$metatags, array &$context) {
    if (!$context['entity'] instanceof ViewEntityInterface) {
      return;
    }
    $view = $context['entity']->getExecutable();
    // If display_id is not available, will default to Master display.
    $route_match = \Drupal::routeMatch();
    $display_id = $route_match->getParameter('display_id');
    $args = [];
    $route = $route_match->getRouteObject();
    $map = $route->hasOption('_view_argument_map') ? $route->getOption('_view_argument_map') : [];
    foreach ($map as $attribute => $parameter_name) {
      if (isset($map[$attribute])) {
        $attribute = $map[$attribute];
      }
      if (!$arg = $route_match->getRawParameter($attribute)) {
        $arg = $route_match->getParameter($attribute);
      }
      if (isset($arg)) {
        $args[] = $arg;
      }
    }
    // Apply view overrides.
    if ($tags = metatag_views_get_view_tags($view, $display_id, $args)) {
      $metatags = array_merge($metatags, $tags);
    }
  }

  /**
   * Implements hook_metatag_route_entity().
   */
  #[Hook('metatag_route_entity')]
  public function metatagRouteEntity(RouteMatchInterface $route_match) {
    if ($view_id = $route_match->getParameter('view_id')) {
      $entity = \Drupal::entityTypeManager()->getStorage('view')->load($view_id);
      return $entity;
    }
  }

}
