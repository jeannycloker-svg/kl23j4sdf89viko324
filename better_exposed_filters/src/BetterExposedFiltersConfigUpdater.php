<?php

namespace Drupal\better_exposed_filters;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewEntityInterface;

/**
 * Provides a BC layer for modules providing old configurations.
 *
 * @internal
 */
class BetterExposedFiltersConfigUpdater {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * Add sort_bef_combine to views.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   *
   * @return bool
   *   Whether the view was updated.
   */
  public function updateCombineParam(ViewEntityInterface $view): bool {
    $changed = FALSE;
    // Go through each display on each view.
    $displays = $view->get('display');
    foreach ($displays as $display) {
      if (isset($display['display_options']['exposed_form']['type'])) {
        if ($display['display_options']['exposed_form']['type'] == 'bef') {
          $advanced_sort_options = $display['display_options']['exposed_form']['options']['bef']['sort']['advanced'] ?? NULL;
          if (isset($advanced_sort_options)) {
            if ($advanced_sort_options['combine'] === TRUE) {
              // Update the "combine_param" key if "combine" is true.
              $combine_param = 'sort_bef_combine';
              // Write the updated options back to the display.
              $display_id = $display['id'];
              $view->set("display.$display_id.display_options.exposed_form.options.bef.sort.advanced.combine_param", $combine_param);
              try {
                $view->save();
                $changed = TRUE;
              }
              catch (EntityStorageException) {
                $this->getLogger('better_exposed_filters')->error('Error saving @view_id', ['@view_id' => $view->id()]);
              }
            }
          }
        }
      }
    }
    return $changed;
  }

  /**
   * Add soft_limit params to views.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   *
   * @return bool
   *   Whether the view as updated.
   */
  public function updateSoftLimitParams(ViewEntityInterface $view): bool {
    $changed = FALSE;
    // Go through each display on each view.
    $displays = $view->get('display');
    foreach ($displays as &$display) {
      if (isset($display['display_options']['exposed_form']['type'])) {
        if ($display['display_options']['exposed_form']['type'] == 'bef') {
          $exposed_form = $display['display_options']['exposed_form'];

          $bef_settings = $exposed_form['options']['bef'];
          foreach ($bef_settings["filter"] as $filter_id => $settings) {
            if (!in_array($settings['plugin_id'], ['bef_links', 'bef'])) {
              // "soft_limit" is only supported for links and checkboxes/radios.
              continue;
            }
            if (isset($settings['soft_limit'])) {
              // "soft_limit" option already configured.
              continue;
            }
            $display['display_options']['exposed_form']['options']['bef']['filter'][$filter_id]['soft_limit'] = 0;
            $display['display_options']['exposed_form']['options']['bef']['filter'][$filter_id]['soft_limit_label_less'] = $this->t('Show less');
            $display['display_options']['exposed_form']['options']['bef']['filter'][$filter_id]['soft_limit_label_more'] = $this->t('Show more');
            $changed = TRUE;
          }
        }
      }
    }
    if ($changed) {

      $view->set('display', $displays);
    }
    return $changed;
  }

}
