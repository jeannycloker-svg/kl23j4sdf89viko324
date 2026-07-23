<?php

namespace Drupal\hierarchy_manager\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Hierarchy Manager Setup Plugin plugins.
 */
abstract class HmSetupPluginBase extends PluginBase implements HmSetupPluginInterface {
  use StringTranslationTrait;

  /**
   * Display profile ID.
   *
   * @var string
   */
  protected $displayProfile;

  /**
   * Enabled entity bundles.
   *
   * @var array
   */
  protected $enabledBundles;

  /**
   * Constructs a new setup plugin object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $plugin_settings = \Drupal::config('hierarchy_manager.hmconfig')->get('setup_plugin_settings');
    if (isset($plugin_settings[$this->pluginId])) {
      $this->displayProfile = $plugin_settings[$this->pluginId]['display_profile'];
      $this->enabledBundles = $plugin_settings[$this->pluginId]['bundle'];
    }
    else {
      $this->displayProfile = '';
      $this->enabledBundles = [];
    }
  }

  /**
   * Common methods and abstract methods for HM setup plugin type.
   */
  public function buildConfigurationForm($config, $state) {
    // All display profiles.
    $display_profiles = \Drupal::entityTypeManager()->getStorage('hm_display_profile')->loadMultiple();
    $display_options = [];
    foreach ($display_profiles as $profile) {
      $display_options[$profile->id()] = $profile->label();
    }
    $settings_form['display_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Display Profile'),
      '#options' => $display_options,
      '#description' => $this->t('Specify the display profile to render the hierarchy tree.'),
      '#default_value' => $this->displayProfile,
      '#required' => TRUE,
    ];
    $settings_form['bundle'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled bundles'),
      '#options' => $this->getBundleOptions(),
      '#default_value' => $this->enabledBundles,
      '#description' => $this->t('Specify bundles for which hierarchy manager should be enabled.'),
    ];

    return $settings_form;
  }

  /**
   * Get the display profile ID.
   *
   * @return string
   *   The profile ID.
   */
  public function getDisplayProfileId() {
    return $this->displayProfile;
  }

}
