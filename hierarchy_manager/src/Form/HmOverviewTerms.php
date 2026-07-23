<?php

namespace Drupal\hierarchy_manager\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Form\OverviewTerms;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Taxonomy overview form class.
 */
class HmOverviewTerms extends OverviewTerms {

  /**
   * Form constructor.
   *
   * Override the form submit method to avoid the parent one from running,
   * If the hierarchy manager taxonomy plugin is enabled.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\taxonomy\VocabularyInterface|null $taxonomy_vocabulary
   *   The vocabulary to display the overview form for.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?VocabularyInterface $taxonomy_vocabulary = NULL) {
    // Override the form if the taxonomy hierarchy manager has been set up.
    if (!empty($taxonomy_vocabulary) && $config = \Drupal::config('hierarchy_manager.hmconfig')) {
      if ($allowed_setup_plugins = $config->get('allowed_setup_plugins')) {
        // If the taxonomy setup plugin is enabled.
        // Override the taxonomy overview form.
        if (!empty($allowed_setup_plugins['hm_setup_taxonomy'])) {
          // Hierarchy Manager setup plugin configuration.
          $plugin_settings = $config->get('setup_plugin_settings');
          if (!empty($plugin_settings['hm_setup_taxonomy'])) {
            // Enabled bundles.
            $enabled_bundles = array_keys(array_filter($plugin_settings['hm_setup_taxonomy']['bundle']));
            // Display profile ID.
            $display_profile_id = $plugin_settings['hm_setup_taxonomy']['display_profile'];
            // Display profile.
            $display_profile = $this->entityTypeManager->getStorage('hm_display_profile')->load($display_profile_id);
            if (!empty($display_profile) && in_array($taxonomy_vocabulary->id(), $enabled_bundles)) {
              // Display plugin instance.
              $instance = \Drupal::service('plugin.manager.hm.display_plugin')->createInstance($display_profile->get("plugin"));
              if (method_exists($instance, 'getForm')) {
                // Vocabulary ID.
                $vid = $taxonomy_vocabulary->id();
                // CSRF token.
                $token = \Drupal::csrfToken()->get($vid);
                // Destination for edit link.
                $destination = $this->getDestinationArray();
                if (isset($destination['destination'])) {
                  $destination = $destination['destination'];
                }
                else {
                  $destination = '/';
                }
                // Urls.
                $source_url = Url::fromRoute('hierarchy_manager.taxonomy.tree.json',
                    ['vid' => $vid],
                    [
                      'query' => [
                        'token' => $token,
                        'destination' => $destination,
                      ],
                    ]
                )->toString();
                $update_url = Url::fromRoute('hierarchy_manager.taxonomy.tree.update',
                    ['vid' => $vid],
                    ['query' => ['token' => $token]]
                )->toString();
                $config = $display_profile->get("config");
                $confirm = $display_profile->get('confirm');
                // Add properties required for compatibility
                // with taxonomy_access_fix.
                if (\Drupal::service('module_handler')->moduleExists('taxonomy_access_fix')) {
                  $form_state->set(['taxonomy', 'vocabulary'], $taxonomy_vocabulary);
                  $form['terms'] = [];
                  // Drag & drop should respect the 'reorder terms
                  // in {vocabulary}' permission.
                  if (!$this->currentUser()->hasPermission("reorder terms in {$taxonomy_vocabulary->id()}") && !$this->currentUser()->hasPermission('administer taxonomy')) {
                    $form_state->set('dnd', 'disabled');
                  }
                }
                return $instance->getForm($source_url, $update_url, $form, $form_state, $config, $confirm);
              }
            }
          }
        }
      }
    }

    // The taxonomy setup plugin is not enabled.
    return parent::buildForm($form, $form_state, $taxonomy_vocabulary);

  }

  /**
   * Form submission handler.
   *
   * Override the form submit method to avoid the parent one from running,
   * If the hierarchy manager taxonomy plugin is enabled.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Override the form if the taxonomy hierarchy manager has been set up.
    if ($config = \Drupal::config('hierarchy_manager.hmconfig')) {
      if ($allowed_setup_plugins = $config->get('allowed_setup_plugins')) {
        // If the taxonomy setup plugin is enabled,
        // override the submitForm function.
        if (!empty($allowed_setup_plugins['hm_setup_taxonomy'])) {
          $plugin_settings = $config->get('setup_plugin_settings');
          $enabled_bundles = array_keys(array_filter($plugin_settings['hm_setup_taxonomy']['bundle']));
          $vocabulary = $form_state->get(['taxonomy', 'vocabulary']);
          if (in_array($vocabulary->id(), $enabled_bundles)) {
            // We don't need to do anything here,
            // as the taxonomy plugin take it over.
            return;
          }
        }
      }
    }

    // The taxonomy setup plugin is not enabled.
    // Let the submitForm function from core handle this request.
    return parent::submitForm($form, $form_state);
  }

}
