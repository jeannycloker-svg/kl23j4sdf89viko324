<?php

namespace Drupal\colorwidget\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Renders color widget.
 *
 * @FormElement("colorwidget")
 */
class ColorWidget extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process' => [
        [$class, 'processFormElement'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#input' => TRUE,

    ];
  }

  /**
   * Process render array.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  public static function processFormElement(&$element, FormStateInterface $form_state, &$complete_form) {

    $element['colorwidget'] = [
      '#prefix' => '<div class="colorwidget">',
      '#suffix' => '</div>',
      '#type' => 'radios',
      '#options' => [],
      '#required' => $element['#required'],
      '#default_value' => $element['#default_value'],
      '#title' => $element['#title'],
    ];
    if (empty($element['#colors'])) {
      return $element;
    }

    foreach ($element['#colors'] as $key => $details) {
      $details['label'] = $details['label'] ?? '';
      $details['css_color'] = $details['css_color'] ?? '';
      $details['css_class'] = $details['css_class'] ?? '';

      $element['colorwidget']['#options'][$key] = $details['label'];
      $element['colorwidget'][$key]['#attributes']['class'][] = "color-name--{$key}";

      if (!empty($details['css_color'])) {
        if (substr($details['css_color'], 1) != '#') {
          $element['colorwidget'][$key]['#attributes']['class'][] = "color-css--{$details['css_color']}";
        }

        if ($details['css_color'] != 'transparent') {
          $element['colorwidget'][$key]['#attributes']['style'] = "background:{$details['css_color']} !important;";
        }
      }

      if (!empty($details['css_class'])) {
        $element['colorwidget'][$key]['#attributes']['class'][] = "{$details['css_class']}";
      }
    }

    $element['colorwidget']['#attached']['library'][] = 'colorwidget/element.colorwidget';
    return $element;
  }

}
