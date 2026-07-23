<?php

namespace Drupal\hierarchy_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hierarchy Manager configuration form.
 */
class HMConfigForm extends ConfigFormBase {

  /**
   * Setup plugin manager.
   *
   * @var \Drupal\hierarchy_manager\Plugin\HmSetupPluginManager
   */
  protected $pluginManagerHmSetup;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pluginManagerHmSetup = $container->get('plugin.manager.hm.hmsetup');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hierarchy_manager.hmconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hm_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hierarchy_manager.hmconfig');
    // Setup plugins.
    $setup_plugins = $this->pluginManagerHmSetup->getDefinitions();
    // Get setup plugin labels.
    $setup_plugins_labels = [];
    foreach ($setup_plugins as $key => $plugin) {
      $setup_plugins_labels[$plugin['id']] = $plugin['label']->render();
    }

    if (count($setup_plugins)) {
      $form['hm_allowed_setup_plugins'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Enabled setup plugins'),
        '#options' => $setup_plugins_labels,
        '#default_value' => $config->get('allowed_setup_plugins') ?: ['hm_setup_taxonomy'],
        '#description' => $this->t('Plugins that presents the hierarchy manager button in the edit form.'),
      ];
    }
    else {
      $form['no_setup_plugin'] = [
        '#value' => 'markup',
        '#markup' => $this->t('No available setup plugins available.'),
      ];
    }

    // Setup plugin advanced settings.
    $form['setup_plugin_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Setup Plugin Settings'),
      '#description' => $this->t('Setup plugin advanced settings.'),
      '#tree' => TRUE,
    ];
    foreach ($setup_plugins_labels as $key => $val) {
      $instance = $this->pluginManagerHmSetup->createInstance($key);

      if (method_exists($instance, 'buildConfigurationForm')) {
        $setup_enabled_state = [
          'visible' => [
                [
                  ':input[name="hm_allowed_setup_plugins[' . $key . ']"]' => ['checked' => TRUE],
                ],
          ],
        ];
        $form['setup_plugin_settings'][$key . '_container'] = [
          '#type' => 'container',
          '#states' => $setup_enabled_state,
          'title' => [
            '#type' => 'html_tag',
            '#tag' => 'h3',
            '#value' => $val,
          ],
        ];
        $container_name = $key . '_container';
        // Plugin settings.
        $form['setup_plugin_settings'][$container_name]['form'] = $instance->buildConfigurationForm($config, $setup_enabled_state);
        $form['setup_plugin_settings'][$container_name]['form']['#parents'] = ['setup_plugin_settings', $key];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Filter to only include checked values, but preserve keys for lookup.
    $allowed_setup_plugins = array_filter(
      $form_state->getValue('hm_allowed_setup_plugins') ?? []
    );

    // Filter bundle values to only include checked values.
    $setup_plugin_settings = $form_state->getValue('setup_plugin_settings') ?? [];
    foreach ($setup_plugin_settings as $plugin_id => $settings) {
      if (isset($settings['bundle']) && is_array($settings['bundle'])) {
        $setup_plugin_settings[$plugin_id]['bundle'] = array_filter($settings['bundle']);
      }
    }

    $this->config('hierarchy_manager.hmconfig')
      ->set('allowed_setup_plugins', $allowed_setup_plugins)
      ->set('setup_plugin_settings', $setup_plugin_settings)
      ->save();
  }

}
