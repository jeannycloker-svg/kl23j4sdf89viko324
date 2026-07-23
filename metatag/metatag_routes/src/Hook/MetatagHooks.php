<?php

namespace Drupal\metatag_routes\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for metatag_routes.
 */
class MetatagHooks {

  /**
   * Implements hook_metatags_alter().
   */
  #[Hook('metatags_alter')]
  public function metatagsAlter(array &$metatags, array $context) {
    // Ignore some system routes that are not appropriate for meta tags.
    if (metatag_is_current_route_supported()) {
      // Look to see if a configuration was assigned for this route.
      /** @var \Drupal\metatag_routes\Helper\MetatagRoutesHelperInterface $metatag_routes_helper */
      $metatag_routes_helper = \Drupal::service('metatag_routes.helper');
      $current_route = $metatag_routes_helper->getCurrentMetatagRouteId();
      if (!empty($current_route)) {
        $defaults = \Drupal::entityTypeManager()->getStorage('metatag_defaults')->load($current_route);
        if (!empty($defaults)) {
          $tags = $defaults->get('tags');
          // Replace the new values and keep on the global values.
          $metatags = array_merge($metatags, $tags);
        }
      }
    }
  }

}
