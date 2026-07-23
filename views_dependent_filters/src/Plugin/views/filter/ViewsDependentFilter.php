<?php

namespace Drupal\views_dependent_filters\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filters by given list of related content title options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_dependent_filter")
 */
class ViewsDependentFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // Override the exposed default. This makes no sense not exposed.
    $options['exposed'] = ['default' => TRUE];

    $options['condition'] = ['default' => 'values'];
    $options['controller_filter'] = ['default' => NULL];
    $options['controller_values'] = ['default' => NULL];
    $options['dependent_filters'] = ['default' => []];
    $options['negate'] = ['default' => FALSE];
    return $options;
  }

  /**
   * Helper function to provide form options for lists of filters.
   *
   * @param string $type
   *   One of 'controller' or 'dependent'.
   *
   * @return array
   *   An array of filters suitable for use as Form API options.
   */
  public function getFilterOptions($type) {
    // Due to http://drupal.org/node/1426094 we can't just go looking in the
    // handlers array on the display.
    $filters = $this->view->display_handler->getHandlers('filter');

    // Get the unique id of this handler (ie allow more than one of this
    // handler).
    $this_id = $this->options['id'];
    $filters_controller = [];
    $filters_dependent  = [];
    $seen = FALSE;
    // Build up the options from all the fields up to this one but no further.
    foreach ($filters as $filter_id => $handler) {
      // Skip non-exposed filters.
      /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $handler*/
      if (!$handler->isExposed()) {
        continue;
      }
      // Required filters can't be dependent.
      if ($type == 'dependent' && $handler->options['expose']['required']) {
        continue;
      }
      // Note if we get to ourselves and skip.
      if ($filter_id == $this_id) {
        $seen = TRUE;
        continue;
      }
      // Skip other instances of this filter.
      if (array_key_exists('handler', $handler->definition) && $handler->definition['handler'] == 'views_dependent_filter') {
        continue;
      }
      $label = $filter_id;
      // All filters may be controllers, but to simplify things we just allow
      // the ones that come before us.
      if (!$seen) {
        $filters_controller[$filter_id] = $label;
      }
      // Only filters that follow us in the order may be dependent.
      if ($seen) {
        $filters_dependent[$filter_id] = $label;
      }
    }
    switch ($type) {
      case 'controller':
        return $filters_controller;

      case 'dependent':
        return $filters_dependent;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Lock the exposed checkbox.
    $form['expose_button']['checkbox']['checkbox']['#disabled'] = TRUE;
    $form['expose_button']['checkbox']['checkbox']['#description'] = t('This filter is always exposed.');

    // Not sure what the 'expose' button is for as there's the checkbox, but
    // it's not wanted here.
    unset($form['expose_button']['markup']);
    unset($form['expose_button']['button']);

    $form['condition'] = [
      '#title' => $this->t('Condition mode'),
      '#type' => 'radios',
      '#options' => [
        'not_empty' => $this->t('Filter is selected / not empty.'),
        'values' => $this->t('Filter is set to specific values.'),
      ],
      '#default_value' => $this->options['condition'] ?? 'values',
    ];

    $filters = $this->view->display_handler->handlers['filter'];
    if (isset($this->options['controller_filter'])) {
      // Get the handler for the controller filter.
      /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $controller_filter */
      $controller_filter = $filters[$this->options['controller_filter']];
      $plugin_id = $controller_filter->getPluginId();

      switch ($plugin_id) {
        case 'facets_filter':
          // Facets work differently, as the options are only available after
          // the search query has been executed.For now, let's just use an
          // inputfield for raw values instead of offering an options list.
          $form['controller_values'] = [
            '#type' => 'textfield',
            '#title' => t('Raw Facet values'),
            '#description' => $this->t('Enter a comma-separated list of values. Example: value1, value2, value3. These values represent the raw value of the Facet options on which the dependency should trigger.<br>The raw value is the value used in the url when the filter is active.'),
            '#default_value' => $this->options['controller_values'] ? implode(', ', $this->options['controller_values']) : '',
            '#states' => [
              'visible' => [
                ':input[name="options[condition]"]' => ['value' => 'values'],
              ],
            ],
          ];
          break;

        default:
          // Take copies of the form arrays to pass to the other handler.
          $form_copy = $form;

          // Fixup the form so the handler is fooled.
          // For some reason we need to add this for non-ajax admin operation.
          $form_copy['operator']['#type'] = '';

          // Get the value form from the filter handler.
          $controller_filter->valueForm($form_copy, $form_state);
          $controller_values_element = $form_copy['value'];

          // Clean up the form element.
          if ($controller_values_element['#type'] == 'checkboxes') {
            // We have to unset the 'select all' option on checkboxes.
            unset($controller_values_element['#options']['all']);
            // Force multiple.
            $controller_values_element['#multiple'] = TRUE;
          }
          // Add it to our own form element in the real form.
          $form['controller_values'] = [
            '#title' => t('Controller values'),
            '#description' => t('The values on the controller filter that will cause the dependent filters to be visible.'),
            '#default_value' => $this->options['controller_values'] ?? [],
            '#states' => [
              'visible' => [
                ':input[name="options[condition]"]' => ['value' => 'values'],
              ],
            ],
          ] + $controller_values_element;

          break;
      }
    }

    $options = $this->getFilterOptions('dependent');
    $form['dependent_filters'] = [
      '#type' => 'checkboxes',
      '#title' => t('Dependent filters'),
      '#options' => $options,
      '#default_value' => $this->options['dependent_filters'] ?? [],
      '#description' => t('The filters which should only be visible and active when the controller filter has the given values.'),
    ];
    if (empty($options)) {
      $form['dependent_filters']['#description'] .= ' ' . t('This filter needs other filters to be placed below it in the order to use as dependents.');
    }

    $form['negate'] = [
      '#title' => $this->t('Negate condition'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['negate'] ?? FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    // The parent validate only checks for exposed form stuff. We don't need it.
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $filters = $this->view->display_handler->getHandlers('filter');
    foreach ($filters as $key => $value) {
      if ($key == "views_dependent_filter") {
        $filters[$key]->options['expose']['identifier'] = 'views_dependent_filter';
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $filters = $this->view->display_handler->handlers['filter'];
    if (isset($this->options['controller_filter'])) {
      $controller_filter = $filters[$this->options['controller_filter']];
      $plugin_id = $controller_filter->getPluginId();
      if ($plugin_id == 'facets_filter') {
        // Convert comma-separated manually inputted values to the same array
        // structure as used by the default plugin.
        $controller_values = $form_state->getValue('options')['controller_values'];
        $controller_values = array_map('trim', explode(',', $controller_values));
        $controller_values = array_combine($controller_values, $controller_values);
        $form_state->setValue(['options', 'controller_values'], $controller_values);
      }
    }
  }

  /**
   * Provide extra options.
   *
   * If a handler has 'extra options' it will get a little settings widget and
   * another form called extra_options.
   */
  public function hasExtraOptions() {
    return TRUE;
  }

  /**
   * Provide defaults for the handler.
   */
  public function defineExtraOptions(&$option) {}

  /**
   * Extra settings form: select the controller filter.
   *
   * Selecting the controller filter here allows us to nicely show its value
   * form in the regular options form.
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    $options = $this->getFilterOptions('controller');
    $form['controller_filter'] = [
      '#type' => 'radios',
      '#title' => t('Controller filter'),
      '#options' => $options,
      '#default_value' => $this->options['controller_filter'] ?? '',
      '#description' => t('The exposed filter whose values will be used to control dependent filters. Only filters that are prior to this one in the order are allowed.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function showExposeForm(&$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    $controller_filter = $this->options['controller_filter'];
    $dependent_filters = implode(', ', array_filter($this->options['dependent_filters']));

    if (empty($controller_filter)) {
      return t("Controller filter not set");
    }
    if (empty($dependent_filters)) {
      return t("Dependent filters not set");
    }
    return t("@controller controlling @dependents", [
      '@controller' => $controller_filter,
      '@dependents' => $dependent_filters,
    ]);
  }

  /**
   * Make our changes to the form but don't return anything ourselves.
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    $filters = $this->view->display_handler->getHandlers('filter');
    // Build an array of dependency info.
    $dependency_info = [
      // An array keyed by controller filter IDs, where the values are arrays
      // of their possible values.
      // In practice there is only one controller filter, but technically there
      // could be several. The problem is that the admin UI to set them up
      // would become a nightmare, and there's the matter of whether to combine
      // them with AND or OR. Hence one for later, if ever required.
      'controllers' => [],
      // An array of dependent filter IDs.
      'dependents'  => [],
      // A lookup of filter IDs to filter URL identifiers.
      'identifiers' => [],
    ];
    $dependency_info['condition'] = $this->options["condition"] ?: 'values';
    $dependency_info['negate'] = $this->options["negate"] ?: FALSE;
    if (!empty($this->options['controller_filter'])) {
      $controller_filter = $this->options['controller_filter'];
      $dependency_info['controllers'][$controller_filter] = [];
      if (!empty($this->options['controller_values'])) {
        if (is_array($this->options['controller_values'])) {
          // Filter out the crud from Form API checkboxes and get rid of the
          // keys to avoid confusion: we compare on values further down.
          $controller_values = array_values(array_filter($this->options['controller_values']));
        }
        else {
          $controller_values = [$this->options['controller_values']];
        }

        $dependency_info['controllers'][$controller_filter] = $controller_values;

        $identifier = $filters[$controller_filter]->options['expose']['identifier'];
        $dependency_info['identifiers'][$controller_filter] = $identifier;
      }
    }
    $dependency_info['dependents'] = array_values(array_filter($this->options['dependent_filters']));
    // Populate the identifiers lookup with our dependent filters.
    foreach ($dependency_info['dependents'] as $dependent_filter_id) {
      $identifier = $filters[$dependent_filter_id]->options['expose']['identifier'];
      $dependency_info['identifiers'][$dependent_filter_id] = $identifier;
    }

    $dependent_exposed_filters = $form_state->get('dependent_exposed_filters') ?? [];
    $dependent_exposed_filters[] = $dependency_info;
    $form_state->set('dependent_exposed_filters', $dependent_exposed_filters);
    $form['#after_build'] = ['views_dependent_filters_exposed_form_after_build'];
  }

  /**
   * Doctor this so the whole form doesn't add our element to $form['#info'].
   *
   * @see views_exposed_form()
   */
  public function exposedInfo() {
  }

  /**
   * Prevent the view from accepting input from ourselves and dependents.
   */
  public function acceptExposedInput($input) {
    // Doctor this so the whole form doesn't go looking for our exposed input.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    // Return nothing for the value form.
    $form['value'] = [];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing: fake filter.
  }

  /**
   * {@inheritdoc}
   */
  public function isExposed() {
    return TRUE;
  }

}
