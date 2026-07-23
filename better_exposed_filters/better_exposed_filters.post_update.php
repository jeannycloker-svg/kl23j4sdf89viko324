<?php

/**
 * @file
 * Contains better_exposed_filters.post_update.combine_param.
 */

use Drupal\better_exposed_filters\BetterExposedFiltersConfigUpdater;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\views\ViewEntityInterface;

/**
 * If using combined sort, set the combine_param config to 'sort_bef_combine'.
 */
function better_exposed_filters_post_update_combine_param(?array &$sandbox = NULL): void {
  /** @var \Drupal\better_exposed_filters\BetterExposedFiltersConfigUpdater $config_updater */
  $config_updater = \Drupal::classResolver(BetterExposedFiltersConfigUpdater::class);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function (ViewEntityInterface $view) use ($config_updater): bool {
    return $config_updater->updateCombineParam($view);
  });
}

/**
 * Add soft limit param keys.
 */
function better_exposed_filters_post_update_soft_limit(?array &$sandbox = NULL): void {
  /** @var \Drupal\better_exposed_filters\BetterExposedFiltersConfigUpdater $config_updater */
  $config_updater = \Drupal::classResolver(BetterExposedFiltersConfigUpdater::class);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function (ViewEntityInterface $view) use ($config_updater): bool {
    return $config_updater->updateSoftLimitParams($view);
  });
}
