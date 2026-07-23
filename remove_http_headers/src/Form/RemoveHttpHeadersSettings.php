<?php

namespace Drupal\remove_http_headers\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\RedundantEditableConfigNamesTrait;

/**
 * Form for the module settings.
 */
class RemoveHttpHeadersSettings extends ConfigFormBase {

  use RedundantEditableConfigNamesTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'remove_http_headers_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['headers_to_remove'] = [
      '#type' => 'textarea',
      '#title' => $this->t('HTTP headers'),
      '#description' => $this->t('Add headers that should be removed from responses, one header per line.'),
      '#config_target' => new ConfigTarget(
        'remove_http_headers.settings',
        'headers_to_remove',
        fromConfig: fn($value) => implode(PHP_EOL, $value),
        toConfig: fn($value) => array_filter(array_map('trim', explode(PHP_EOL, $value))),
      ),
    ];

    $form['x_generator_info'] = [
      '#markup' => $this->t("If the header <code>X-Generator</code> is configured for removal, Drupal's default <code>&lt;meta name='Generator' value='Drupal (version N)'&gt;</code> will be removed from HTML output."),
      '#prefix' => '<div class="description">',
      '#suffix' => '</div>',
    ];

    return parent::buildForm($form, $form_state);
  }

}
