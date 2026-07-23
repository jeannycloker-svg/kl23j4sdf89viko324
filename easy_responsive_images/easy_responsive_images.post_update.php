<?php

/**
 * @file
 * Post-update functions for Easy Responsive Images.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Update easy responsive images field widget cover setting.
 */
function easy_responsive_images_post_update_cover_attribute(?array &$sandbox = NULL) : void {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_view_display', function (EntityViewDisplayInterface $view_display): bool {
    $changed = FALSE;

    foreach ($view_display->getComponents() as $field => $component) {
      if (isset($component['type']) && ($component['type'] === 'easy_responsive_images')) {
        $component['settings']['cover'] = (bool) $component['settings']['cover'];
        $view_display->setComponent($field, $component);
        $changed = TRUE;
      }
    }

    return $changed;
  });
}
