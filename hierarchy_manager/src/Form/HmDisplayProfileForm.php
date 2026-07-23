<?php

namespace Drupal\hierarchy_manager\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hierarchy_manager\Plugin\HmDisplayPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * HmDisplayProfile entity form class.
 */
class HmDisplayProfileForm extends EntityForm {

  /**
   * Display plugin manager.
   *
   * @var \Drupal\hierarchy_manager\Plugin\HmDisplayPluginInterface
   */
  protected $pluginManagerHmDisplay;

  /**
   * Constructs a new HmDisplayProfileForm object.
   */
  public function __construct(
    HmDisplayPluginManager $plugin_manager_hm_display,
  ) {
    $this->pluginManagerHmDisplay = $plugin_manager_hm_display;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('plugin.manager.hm.display_plugin')
        );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Setup plugins.
    $display_plugins = $this->pluginManagerHmDisplay->getDefinitions();
    // Get display plugin labels.
    $display_plugin_labels = [];

    foreach ($display_plugins as $key => $plugin) {
      $display_plugin_labels[$plugin['id']] = $plugin['label']->render();

    }

    $hm_display_profile = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $hm_display_profile->label(),
      '#description' => $this->t("Label for the HM Display Profile."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $hm_display_profile->id(),
      '#machine_name' => [
        'exists' => '\Drupal\hierarchy_manager\Entity\HmDisplayProfile::load',
      ],
      '#disabled' => !$hm_display_profile->isNew(),
    ];

    // Display plugin.
    if (count($display_plugins)) {
      $form['plugin'] = [
        '#type' => 'radios',
        '#title' => $this->t('Display plugin'),
        '#options' => $display_plugin_labels,
        '#default_value' => $hm_display_profile->get("plugin") ?: 'hm_display_jstree',
        '#description' => $this->t('Display plugin that is in charge of rendering the hierarchy view.'),
        '#required' => TRUE,
      ];

      $form['config'] = [
        '#type' => 'hidden',
        '#value' => $hm_display_profile->get('config'),
        '#attributes' => [
          'id' => 'config-value',
        ],
      ];
      $form['json_editor'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'id' => 'json-editor',
          'style' => 'width:100%; height: 600px;',
        ],
        '#attached' => [
          'library' => [
            'hierarchy_manager/libraries.jsoneditor',
            'hierarchy_manager/feature.hm.jsoneditor',
            'hierarchy_manager/libraries.jsoneditor.default-theme',
          ],
        ],
      ];
    }
    else {
      $form['no_setup_plugin'] = [
        '#value' => 'markup',
        '#markup' => $this->t('No available setup plugins available.'),
      ];
    }

    $form['confirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Confirm drag&drop'),
      '#default_value' => $hm_display_profile->get("confirm"),
      '#description' => $this->t('Displays a dialog when changing the hierarchy.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $hm_display_profile = $this->entity;
    // User input.
    $input = $form_state->getUserInput();

    if (isset($input['config'])) {
      // Sanitize the user input.
      $input['config'] = Xss::filter($input['config']);
      // Update the json data from input.
      $hm_display_profile->set('config', $input['config']);
    }

    if (isset($input['confirm'])) {
      $hm_display_profile->set('confirm', $input['confirm']);
    }

    $status = $hm_display_profile->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label HM Display Profile.', [
          '%label' => $hm_display_profile->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label HM Display Profile.', [
          '%label' => $hm_display_profile->label(),
        ]));
    }
    $form_state->setRedirectUrl($hm_display_profile->toUrl('collection'));
  }

}
