<?php

/**
 * @file
 * Hooks related to Easy Responsive Images.
 */

use Drupal\image\ImageStyleInterface;

/**
 * Alter generated image styles.
 *
 * This hook allows modules to alter the generated image styles. This is also
 * called to update existing image styles, so it's important to check if the
 * image style already has the desired effect before adding it.
 *
 * @param \Drupal\image\ImageStyleInterface $entity
 *   The image style entity.
 */
function hook_easy_responsive_images_image_style_alter(ImageStyleInterface $entity): void {
  foreach ($entity->getEffects() as $effect) {
    if ($effect->getPluginId() === 'image_convert') {
      return;
    }
  }

  $entity->addImageEffect([
    'id' => 'image_convert',
    'data' => [
      'extension' => 'webp',
    ],
  ]);
}
