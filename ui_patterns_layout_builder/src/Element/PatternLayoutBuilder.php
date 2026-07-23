<?php

namespace Drupal\ui_patterns_layout_builder\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Template\Attribute;
use Drupal\ui_patterns\UiPatterns;

/**
 * Renders a pattern element.
 */
class PatternLayoutBuilder implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['processLayoutBuilderRegions'];
  }

  /**
   * Process layout builder regions.
   *
   * Layout builder adds foreach region an renderable array
   * after the pattern is built. So reassign the region to fields.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  public static function processLayoutBuilderRegions(array $element) {
    $definiton = UiPatterns::getPatternDefinition($element['#id']);

    if ($definiton != NULL) {
      foreach (Element::children($element) as $key) {
        if ($definiton->hasField($key)) {
          $region_children = Element::children($element[$key]);
          $region_properties = Element::properties($element[$key]);
          if (isset($element['#' . $key]) && is_string($element['#' . $key])) {
            continue;
          }

          foreach ($region_children as $region_key) {
            $element['#' . $key][$region_key] = $element[$key][$region_key];
          }

          foreach ($region_properties as $property_key) {
            if ($property_key === '#attributes') {
              $element['#region_attributes'][$key] = new Attribute($element[$key][$property_key]);
            }
          }

          // Disabled unset region key. Region Used in quick edit.
          // unset($element[$key]);.
        }
      }
    }
    return $element;
  }

}
