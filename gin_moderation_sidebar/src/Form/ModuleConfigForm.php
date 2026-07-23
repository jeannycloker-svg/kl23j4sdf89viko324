<?php

namespace Drupal\gin_moderation_sidebar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ModuleConfigForm.
 *
 * Configuration settings for Gin Moderation Sidebar module.
 */
class ModuleConfigForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'gin_moderation_sidebar.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gin_moderation_sidebar_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['tab_style'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose the Moderation Sidebar tab style.'),
      '#default_value' => $config->get('tab_style'),
      '#options' => [
        'default' => $this->t('Default'),
        'contrast' => $this->t('High contrast'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::SETTINGS)
      ->set('tab_style', $form_state->getValue('tab_style'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
