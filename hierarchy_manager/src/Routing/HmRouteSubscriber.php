<?php

namespace Drupal\hierarchy_manager\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 *
 * Class HmRouteSubscriber.
 *
 * @package Drupal\hierarchy_manager\Routing
 */
class HmRouteSubscriber extends RouteSubscriberBase {

  /**
   * Overrides entity.taxonomy_vocabulary.overview_form route.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   Route Collection.
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Override taxonomy overview form for Drupal 10 and older 11.x versions
    // where the route uses '_form' instead of '_entity_form'.
    // In Drupal 11.3+, this is handled via hook_entity_type_alter().
    // @see https://www.drupal.org/node/3528300
    if ($route = $collection->get('entity.taxonomy_vocabulary.overview_form')) {
      // Only alter if the route uses '_form' (Drupal 10 / older 11.x).
      // Drupal 11.3+ uses '_entity_form' and is handled via
      // hook_entity_type_alter() in hierarchy_manager.module.
      if ($route->getDefault('_form')) {
        $route->setDefault('_form', '\Drupal\hierarchy_manager\Form\HmOverviewTerms');
      }
    }
  }

}
