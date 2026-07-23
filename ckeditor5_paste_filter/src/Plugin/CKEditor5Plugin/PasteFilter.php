<?php

declare(strict_types=1);

namespace Drupal\ckeditor5_paste_filter\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 PasteFilter plugin.
 */
class PasteFilter extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'enabled' => FALSE,
      'filters' => [
        [
          'enabled' => TRUE,
          'weight' => -10,
          'search' => '<o:p><\/o:p>',
          'replace' => '',
        ],
        [
          'enabled' => TRUE,
          'weight' => -9,
          'search' => '(<[^>]*) (style="[^"]*")',
          'replace' => '$1',
        ],
        [
          'enabled' => TRUE,
          'weight' => -8,
          'search' => '(<[^>]*) (face="[^"]*")',
          'replace' => '$1',
        ],
        [
          'enabled' => TRUE,
          'weight' => -7,
          'search' => '(<[^>]*) (class="[^"]*")',
          'replace' => '$1',
        ],
        [
          'enabled' => TRUE,
          'weight' => -6,
          'search' => '(<[^>]*) (valign="[^"]*")',
          'replace' => '$1',
        ],
        [
          'enabled' => TRUE,
          'weight' => -5,
          'search' => '<font[^>]*>',
          'replace' => '',
        ],
        [
          'enabled' => TRUE,
          'weight' => -4,
          'search' => '<\/font>',
          'replace' => '',
        ],
        [
          'enabled' => TRUE,
          'weight' => -3,
          'search' => '<span[^>]*>',
          'replace' => '',
        ],
        [
          'enabled' => TRUE,
          'weight' => -2,
          'search' => '<\/span>',
          'replace' => '',
        ],
        [
          'enabled' => TRUE,
          'weight' => -1,
          'search' => '<p>&nbsp;<\/p>',
          'replace' => '',
        ],
        [
          'enabled' => TRUE,
          'weight' => 0,
          'search' => '<p><\/p>',
          'replace' => '',
        ],
        [
          'enabled' => TRUE,
          'weight' => 1,
          'search' => '<b><\/b>',
          'replace' => '',
        ],
        [
          'enabled' => TRUE,
          'weight' => 2,
          'search' => '<i><\/i>',
          'replace' => '',
        ],
        [
          'enabled' => TRUE,
          'weight' => 3,
          'search' => '<a name="OLE_LINK[^"]*">(.*?)<\/a>',
          'replace' => '$1',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): void {
    if (empty($this->configuration)) {
      $this->configuration = $this->defaultConfiguration();
    }
    else {
      $this->configuration = $configuration;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter pasted content'),
      '#default_value' => $this->configuration['enabled'],
    ];

    $form['help'] = [
      '#type' => 'details',
      '#title' => $this->t('Help'),
      '#access' => $this->configuration['enabled'],
    ];
    $form['help']['info']['#markup'] = '';
    $form['help']['info']['#markup'] .= '<p>' . $this->t('Content pasted into the editor will be processed through the configured filters.') . '</p>';
    $form['help']['info']['items'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Each filter consists of a <a href="@regex">JavaScript regular expression</a> and a replacement string.', ['@regex' => 'https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Regular_expressions']),
        $this->t('Filters run in the order specified below.'),
        $this->t('In the search expression, any <a href="@regex_cheat_sheet">special characters</a> that you want to match literally must be escaped with a backslash. For example, since the forward slash is a special character, a closing paragraph tag must be written as <code><\/p></code>.', ['@regex_cheat_sheet' => 'https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Regular_Expressions/Cheatsheet']),
        $this->t('Each search expression automatically has the flags <code>gimsu</code> (<a href="@regex_flags">global, ignoreCase, multiline, dotAll, and unicode</a>) applied. Do not enter delimiters around your search expression.', ['@regex_flags' => 'https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Regular_expressions#advanced_searching_with_flags']),
        $this->t('All matches to the search expression are replaced with the replacement string (can be empty).'),
        $this->t('The replacement string can <a href="@regex_replace">reference capture groups from the search expression</a>, such as <code>$1</code>, <code>$2</code>, etc.', ['@regex_replace' => 'https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/replace#specifying_a_string_as_the_replacement']),
      ],
    ];

    $wrapper_id = 'paste-filters-wrapper';
    $form['filters'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Enabled'),
        $this->t('Filter'),
        $this->t('Weight'),
      ],
      '#title' => $this->t('Paste Filters'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'paste-filter-weight',
        ],
      ],
      // We add this via prefix and suffix rather than #attributes so that we
      // can enclose related elements such as the tabledrag toggle weight
      // button.
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#after_build' => [[static::class, 'sortFilterRows']],
      '#access' => $this->configuration['enabled'],
    ];

    $filters = $this->configuration['filters'];

    // Store number of rows (used for "Add another filter" functionality).
    $storage = $form_state->getStorage();
    if (empty($storage['paste-filter-rows'])) {
      $count = count($filters);
      // If there are no configured filters, add an empty row to provide a
      // starting point.
      if ($count === 0) {
        $count = 1;
      }

      $storage['paste-filter-rows'] = $count;
      $form_state->setStorage($storage);
    }
    // Build rows for the filter config table.
    for ($i = 0; $i < $storage['paste-filter-rows']; $i++) {
      $form['filters'][$i] = $this->buildFilterConfigurationRow($filters, $i, $storage['paste-filter-rows']);
    }

    $form['actions'] = [
      '#type' => 'actions',
      '#access' => $this->configuration['enabled'],
    ];

    $form['actions']['add-row'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another filter'),
      '#submit' => [[static::class, 'addExtraRow']],
      '#ajax' => [
        'callback' => [static::class, 'addExtraRowAjaxCallback'],
        'wrapper' => $wrapper_id,
      ],
    ];

    return $form;
  }

  /**
   * Build a single row of the paste filter configuration table.
   *
   * @param array $filter_config
   *   Filter configuration.
   * @param int $key
   *   Row number.
   * @param int $total_rows
   *   Total number of rows.
   *
   * @return array
   *   One row for the table of paste filter configurations.
   */
  private function buildFilterConfigurationRow(array $filter_config, $key, $total_rows) {
    if (!empty($filter_config[$key])) {
      $filter = $filter_config[$key];
    }
    else {
      $filter = [
        'enabled' => TRUE,
        'search' => '',
        'replace' => '',
        'weight' => NULL,
      ];
    }

    $row = [];
    $row['#attributes']['class'][] = 'draggable';
    $row['enabled'] = [
      '#type' => 'checkbox',
      '#default_value' => $filter['enabled'],
      // See ckeditor5_form_filter_format_form_alter().
      '#ajax' => FALSE,
    ];

    // @todo Consider adding a 'description' field.
    // @todo Try to put search and replace visually next to each other.
    $row['filter']['search'] = [
      '#type' => 'textarea',
      '#rows' => 1,
      '#title' => $this->t('Search expression'),
      '#default_value' => $filter['search'],
      // See ckeditor5_form_filter_format_form_alter().
      '#ajax' => FALSE,
    ];
    $row['filter']['replace'] = [
      '#type' => 'textarea',
      '#rows' => 1,
      '#title' => $this->t('Replacement'),
      '#default_value' => $filter['replace'],
      // See ckeditor5_form_filter_format_form_alter().
      '#ajax' => FALSE,
    ];

    $row['weight']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight for filter replacing @title', ['@title' => $filter['search']]),
      '#title_display' => 'invisible',
      '#value' => $filter['weight'],
      // @todo Better handling for when the delta gets inflated, for example
      //   adding many rows and then removing most rows. The best thing in this
      //   case may be to re-weight all rows in sortFilterRows().
      '#delta' => $total_rows + 1,
      '#attributes' => [
        'class' => ['paste-filter-weight'],
      ],
    ];

    $row['weight']['config_order'] = [
      '#type' => 'value',
      '#value' => $key,
    ];

    return $row;
  }

  /**
   * Sort and set weights of filter rows.
   *
   * #after_build callback.
   */
  public static function sortFilterRows($element, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // @todo Could this be made less fragile? What if upstream changes form
    //   structure?
    $filters = $values['editor']['settings']['plugins']['ckeditor5_paste_filter_pasteFilter']['filters'];

    if (!$filters) {
      return $element;
    }

    // Reorder table rows based on the filter weights.
    $rows = [];
    $filter_weights = array_column(array_column($filters, 'weight'), 'weight');

    foreach (Element::children($element) as $key) {
      // Fill in missing weights (anything without a weight should go to the
      // end of the list).
      if (!isset($filter_weights[$key])) {
        $filter_weights[$key] = max($filter_weights) + 1;
      }

      // Build out separate $rows variable and remove row from $element.
      $rows[$key] = $element[$key];
      unset($element[$key]);
    }

    uksort($rows, function ($a, $b) use ($filter_weights) {
      return $filter_weights[$a] - $filter_weights[$b];
    });

    // Add weight to any rows that do not have a weight set from
    // buildFilterConfigurationRow().
    foreach ($rows as $key => &$row) {
      if (!isset($row['weight']['weight']['#value'])) {
        $row['weight']['weight']['#value'] = $filter_weights[$key];
      }
    }

    // Return element including the re-sorted and updated rows.
    return $element + $rows;
  }

  /**
   * Submit handler to add extra row.
   *
   * @param array $form
   *   Drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Drupal form state object.
   */
  public static function addExtraRow(array $form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $storage['paste-filter-rows']++;
    $form_state->setStorage($storage);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Callback for Ajax functionality.
   *
   * @param array $form
   *   Drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Drupal form state object.
   *
   * @return mixed
   *   Form element for ajax response to replace the table with an updated
   *   version.
   */
  public static function addExtraRowAjaxCallback(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go two levels up in the form array, to get to the correct part of the
    // subform.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));

    return $element['filters'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $filters = $form_state->getValue('filters');
    foreach ($filters as $row => $filter) {
      if (empty($filter['filter']['search']) && !empty($filter['filter']['replace'])) {
        $form_state->setError($form['filters'][$row]['filter']['search'], $this->t('The Search expression field is required. To remove the filter, empty both the Search expression and Replacement fields.'));
      }
    }

    // Sort filters by config_order, so that when manipulating filter weights
    // we don't reorder the config items.
    array_multisort(array_column(array_column($filters, 'weight'), 'config_order'), $filters);

    // Set up correct data types to match configuration schema.
    $validated_config = [];
    foreach ($filters as $filter) {
      $validated_config[] = [
        'enabled' => (bool) $filter['enabled'],
        'search' => (string) $filter['filter']['search'],
        'replace' => (string) $filter['filter']['replace'],
        'weight' => (int) $filter['weight']['weight'],
      ];
    }

    $form_state->setValue('enabled', (bool) $form_state->getValue('enabled'));
    $form_state->setValue('filters', $validated_config);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Remove any empty filter rows entirely from the final configuration. Since
    // the replacement string is allowed to be empty we only check the search
    // expression.
    // @todo Consider providing 'Remove' button for each row.
    // @todo Consider adding 'Remove all filters' button, particularly for the
    //   use case of discarding the defaults and starting fresh.
    $valid_filters = array_filter($form_state->getValue('filters'), function (array $filter): bool {
      return (bool) !empty($filter['search']);
    });
    $this->configuration = [
      'enabled' => $form_state->getValue('enabled'),
      // Re-number the array.
      'filters' => array_values($valid_filters),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    // Return false if the plugin is not enabled, or there are no filters
    // configured.
    if (!$this->configuration['enabled'] || empty($this->configuration['filters'])) {
      return ['pasteFilter' => FALSE];
    }

    // Return only enabled filters sorted by weight.
    $enabled_filters = array_filter($this->configuration['filters'], function (array $filter): bool {
      return (bool) $filter['enabled'];
    });
    usort($enabled_filters, [
      'Drupal\Component\Utility\SortArray',
      'sortByWeightElement',
    ]);

    return [
      'pasteFilter' => $enabled_filters,
    ];
  }

}
