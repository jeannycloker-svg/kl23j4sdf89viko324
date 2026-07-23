<?php

namespace Drupal\easy_responsive_images\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Template\Attribute;
use Drupal\easy_responsive_images\EasyResponsiveImagesManagerInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'easy responsive image' formatter.
 *
 * @FieldFormatter(
 *   id = "easy_responsive_images",
 *   label = @Translation("Easy Responsive Images"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class EasyResponsiveImagesFormatter extends ImageFormatter {

  /**
   * The easy responsive images manager.
   *
   * @var \Drupal\easy_responsive_images\EasyResponsiveImagesManagerInterface
   */
  protected EasyResponsiveImagesManagerInterface $easyResponsiveImagesManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->easyResponsiveImagesManager = $container->get('easy_responsive_images.manager');
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    $instance->routeMatch = $container->get('current_route_match');

    return $instance;
  }

  /**
   * Returns the handling options.
   *
   * @return array
   *   The image handling options key|label.
   */
  public function imageHandlingOptions(): array {
    $options = [
      'scale' => $this->t('Scale'),
    ];
    // Only add aspect ratio when configured.
    if ($this->easyResponsiveImagesManager->getAspectRatios()) {
      $options['aspect_ratio'] = $this->t('Aspect Ratio');
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'image_handling' => 'scale',
      'aspect_ratio' => '',
      'multiplier' => '',
      'cover' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::settingsForm($form, $form_state);

    // Do not use an image style here.
    unset($element['image_style']);

    $element['image_handling'] = [
      '#type' => 'radios',
      '#title' => $this->t('Image handling'),
      '#default_value' => $this->getSetting('image_handling'),
      '#options' => $this->imageHandlingOptions(),
      'scale' => [
        '#description' => $this->t('The image will be scaled in width until it fits. This maintains the original aspect ratio of the image.'),
      ],
      'aspect_ratio' => [
        '#description' => $this->t('The image will be scaled and cropped to an exact aspect ratio you define.'),
      ],
    ];

    $element['aspect_ratio'] = [
      '#title' => $this->t('Aspect Ratio'),
      '#type' => 'select',
      '#description' => $this->t('Select the desired aspect ratio for the image.'),
      '#default_value' => $this->getSetting('aspect_ratio'),
      '#states' => [
        'visible' => [
          ':input[name$="[image_handling]"]' => [
            'value' => 'aspect_ratio',
          ],
        ],
      ],
      '#options' => $this->easyResponsiveImagesManager->getAspectRatios(),
    ];

    $element['multiplier'] = [
      '#title' => $this->t('Multiplier'),
      '#type' => 'select',
      '#description' => $this->t('Increase the size of the loaded images to improve the image quality.'),
      '#default_value' => $this->getSetting('multiplier'),
      '#options' => [
        '1x' => $this->t('1x'),
        '1.25x' => $this->t('1.25x'),
        '1.5x' => $this->t('1.5x'),
        '2x' => $this->t('2x'),
        '3x' => $this->t('3x'),
        '4x' => $this->t('4x'),
      ],
    ];

    $element['cover'] = [
      '#title' => $this->t('Use the container height to select the best image (for example when using the object-fit CSS property).'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('cover'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    $options = $this->imageHandlingOptions();
    $handler = $this->getSetting('image_handling');
    $args = [
      '@image_handling' => $options[$handler],
    ];

    // Add extra options for some handlers.
    if ($handler === 'aspect_ratio') {
      $args['@image_handling'] .= ' (' . $this->getAspectRatio() . ')';
    }

    $summary[] = $this->t('Image handling: @image_handling', $args);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items */
    $elements = parent::viewElements($items, $langcode);
    /** @var \Drupal\file\Entity\File[] $files */
    $files = $this->getEntitiesToView($items, $langcode);

    // When showing media in a WYSIWYG editor, want to use the largest
    // version of our file to make sure it looks good. Our custom JavaScript
    // is not applied here.
    $preview_routes = ['media.filter.preview', 'embed.preview'];
    $is_preview = in_array($this->routeMatch->getRouteName(), $preview_routes, TRUE);

    foreach ($elements as $delta => $element) {
      $elements[$delta]['#theme'] = 'easy_responsive_images_formatter';
      $elements[$delta]['#view_mode'] = $this->viewMode;

      $elements[$delta]['#item_attributes'] = new Attribute($elements[$delta]['#attributes'] ?? $elements[$delta]['#item_attributes'] ?? []);
      $elements[$delta]['#item_attributes']->setAttribute('alt', $element['#item']->getValue()['alt'] ?? NULL);
      $elements[$delta]['#item_attributes']->setAttribute('width', $element['#item']->getValue()['width'] ?? NULL);
      $elements[$delta]['#item_attributes']->setAttribute('height', $element['#item']->getValue()['height'] ?? NULL);

      // Add the multiplier value to the image attributes.
      $elements[$delta]['#item_attributes']->setAttribute('data-multiplier', $this->getSetting('multiplier'));

      // Specify whether to use the container height to select the best image.
      if ($this->getSetting('cover')) {
        $elements[$delta]['#item_attributes']->setAttribute('data-cover', '1');
      }

      // Add image_handling and specific data for the type of handling.
      $elements[$delta]['#data']['image_handling'] = $this->getSetting('image_handling');
      switch ($elements[$delta]['#data']['image_handling']) {
        case 'aspect_ratio':
          $aspect_ratio = $this->getSetting('aspect_ratio');
          [$aspect_ratio_w, $aspect_ratio_h] = explode('_', $aspect_ratio);
          $elements[$delta]['#data'] = [
            'uri' => $files[$delta]->getFileUri(),
            'url' => $this->fileUrlGenerator->transformRelative($this->fileUrlGenerator->generateAbsoluteString($files[$delta]->getFileUri())),
            'aspect_ratio' => $this->getSetting('aspect_ratio'),
          ];
          $elements[$delta]['#srcset'] = $this->easyResponsiveImagesManager->getImagesByAspectRatio($elements[$delta]['#data']['uri'], $elements[$delta]['#data']['aspect_ratio']);
          $src_key = $is_preview ? array_key_last($elements[$delta]['#srcset']) : 0;
          $elements[$delta]['#src'] = $elements[$delta]['#srcset'][$src_key]['url'] ?? $this->fileUrlGenerator->generateString($files[$delta]->getFileUri());
          // Temporarily set a width and height to make the browser render the
          // image more efficiently. The width and height will be updated by the
          // resizer JavaScript.
          $elements[$delta]['#item_attributes']->setAttribute('width', $elements[$delta]['#srcset'][$src_key]['width']);
          $height = $elements[$delta]['#srcset'][$src_key]['width'] ? floor(($elements[$delta]['#srcset'][$src_key]['width'] * $aspect_ratio_h) / $aspect_ratio_w) : NULL;
          $elements[$delta]['#item_attributes']->setAttribute('height', $height);
          $elements[$delta]['#item_attributes']->setAttribute('data-ratio', $aspect_ratio_w . ':' . $aspect_ratio_h);
          break;

        case 'scale':
          $elements[$delta]['#data'] = [
            'uri' => $files[$delta]->getFileUri(),
            'url' => $this->fileUrlGenerator->transformRelative($this->fileUrlGenerator->generateAbsoluteString($files[$delta]->getFileUri())),
          ];
          $elements[$delta]['#srcset'] = $this->easyResponsiveImagesManager->getImagesByScale($elements[$delta]['#data']['uri']);
          $src_key = $is_preview ? array_key_last($elements[$delta]['#srcset']) : 0;
          $elements[$delta]['#src'] = $elements[$delta]['#srcset'][$src_key]['url'] ?? $this->fileUrlGenerator->generateString($files[$delta]->getFileUri());

          // Temporarily set a width and height to make the browser render the
          // image more efficiently. The width and height will be updated by the
          // resizer JavaScript.
          $original_width = $elements[$delta]['#item_attributes']['width']->value();
          $original_height = $elements[$delta]['#item_attributes']['height']->value();
          if ($original_height === null) {
            break;
          }

          $aspect_ratio = $original_width / $original_height;
          $width = $elements[$delta]['#srcset'][$src_key]['width'];
          $height = floor($width / $aspect_ratio);

          $elements[$delta]['#item_attributes']->setAttribute('width', $width);
          $elements[$delta]['#item_attributes']->setAttribute('height', $height);
          break;

        default:
          // Nothing extra needed here.
          break;
      }
    }

    // Cache the output using the accept header since we use it to detect
    // support for WebP images.
    $elements['#cache']['contexts'] = ['headers:accept', 'route.name'];

    return $elements;
  }

  /**
   * Helper method to format the aspect ratio.
   *
   * Checks if the key 'aspect_ratio' already exists and if it is an array
   * transforms it into a string with a '_' separator. This is needed because we
   * ran into cases where the key was an array when drimage was used previously.
   *
   * @return string
   *   Transforms a given aspect ratio into a string and returns it.
   */
  private function getAspectRatio(): string {
    $aspect_ratio = $this->getSetting('aspect_ratio');
    if (is_array($aspect_ratio)) {
      $aspect_ratio = implode('_', $aspect_ratio);
    }

    return $aspect_ratio;
  }

}
