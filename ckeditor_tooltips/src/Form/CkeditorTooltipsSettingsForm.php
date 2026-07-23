<?php

namespace Drupal\ckeditor_tooltips\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The settings form for the CKEditor Tooltips.
 *
 * @package Drupal\ckeditor_tooltips\Form
 */
class CkeditorTooltipsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckeditor_tooltips_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['ckeditor_tooltips.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ckeditor_tooltips.settings');

    if (empty($config->get('custom_styling'))) {
      $form['#attached']['library'][] = 'ckeditor_tooltips/tippy-overrides';
    }

    $form['settings'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $this->t("This text is an example of text with tooltip.<br/>Interact with it after the save to see the new configuration.<br/>Another new line. Useful to test 'follow cursor' method on Y axis."),
      '#attributes' => [
        'data-tippy-content' => '<span class="tooltip-title">Hello World</span>This is the content.',
        'class' => [
          'ckeditor-tooltip-text',
        ],
      ],
    ];

    $form['follow_cursor'] = [
      '#type' => 'radios',
      '#title' => $this->t('Follow cursor'),
      '#description' => $this->t("Determines if the tippy tooltip follows the user's mouse cursor."),
      '#options' => [
        FALSE => $this->t('Default'),
        'initial' => $this->t('Initial'),
        TRUE => $this->t('Follow on both x and y axes'),
        'horizontal' => $this->t('Horizontal - follow on x axis'),
        'vertical' => $this->t('Vertical - follow on y axis'),
      ],
      '#default_value' => $config->get('follow_cursor') ?? FALSE,
    ];

    $form['prevent_overflow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent overflow'),
      '#description' => $this->t('Modifier used to prevent the popper from being positioned outside the boundary.'),
      '#default_value' => $config->get('prevent_overflow') ?? 0,
    ];

    $form['allow_html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow HTML'),
      '#description' => $this->t('NOTICE: It has security implications. (TODO: Allowed tags: a, br, strong, em.)'),
      '#default_value' => $config->get('allow_html') ?? 1,
    ];

    $form['interactive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Interactive tooltip'),
      '#description' => $this->t('Determines if the tippy toolbar has interactive content inside of it, so that it can be hovered over and clicked inside without hiding.'),
      '#default_value' => $config->get('interactive') ?? 1,
    ];

    $form['max_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Max width'),
      '#default_value' => $config->get('max_width') ?? 500,
      '#step' => 1,
    ];

    $form['offset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Offset'),
    ];

    $form['offset']['skidding'] = [
      '#type' => 'number',
      '#title' => $this->t('Skidding'),
      '#step' => 1,
      '#default_value' => $config->get('skidding') ?? 0,
    ];

    $form['offset']['distance'] = [
      '#type' => 'number',
      '#title' => $this->t('Distance'),
      '#step' => 1,
      '#default_value' => $config->get('distance') ?? 15,
    ];

    // @todo add option to select multiple triggers separated by space.
    $form['trigger'] = [
      '#type' => 'radios',
      '#title' => $this->t('Trigger'),
      '#description' => $this->t("Determines the events that cause the tippy toolbar to show. Currently only one is possible."),
      '#options' => [
        'click' => $this->t('click'),
        'mouseenter' => $this->t('mouseenter'),
        'manual' => $this->t('manual'),
        'focus' => $this->t('focus'),
        'focusin' => $this->t('focusin'),
      ],
      '#default_value' => $config->get('trigger') ?? 'click',
    ];

    // @todo Add also the other animations.
    //   https://atomiks.github.io/tippyjs/v6/animations/
    $form['animations'] = [
      '#type' => 'radios',
      '#title' => $this->t('Animations'),
      '#options' => [
        'none' => $this->t('None'),
        'fade' => $this->t('fade'),
        // 'shift-away' => 'shift-away',
        // 'shift-toward' => 'shift-toward',
        'scale' => $this->t('scale'),
        // 'perspective' => 'perspective',
      ],
      '#default_value' => $config->get('animations') ?? 'scale',
    ];

    $form['custom_styling'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use custom styling'),
      '#description' => $this->t("Select it if you want to override the provided styling of the tooltip. If you don't see the changes, clear the cache."),
      '#default_value' => $config->get('custom_styling') ?? 0,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ckeditor_tooltips.settings');
    $values = $form_state->getValues();

    $config->set('follow_cursor', $values['follow_cursor']);
    $config->set('prevent_overflow', $values['prevent_overflow']);
    $config->set('interactive', $values['interactive']);
    $config->set('allow_html', $values['allow_html']);
    $config->set('max_width', $values['max_width']);
    $config->set('skidding', $values['skidding']);
    $config->set('distance', $values['distance']);
    $config->set('trigger', $values['trigger']);
    $config->set('animations', $values['animations']);
    $config->set('custom_styling', $values['custom_styling']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
