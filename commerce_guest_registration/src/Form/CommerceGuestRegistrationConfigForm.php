<?php

namespace Drupal\commerce_guest_registration\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigManagerInterface;

/**
 * Class to manage commerce guest registration config form.
 */
class CommerceGuestRegistrationConfigForm extends ConfigFormBase {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   Configuration manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConfigManagerInterface $config_manager) {
    parent::__construct($config_factory);
    $this->configFactory = $config_factory;
    $this->configManager = $config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_guest_registration_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_guest_registration.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('commerce_guest_registration.settings');

    $form['create_new'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Assign orders to new accounts'),
      '#default_value' => $config->get('create_new'),
      '#weight' => '0',
    ];
    $form['assign_to_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Assign orders to existing accounts'),
      '#default_value' => $config->get('assign_to_existing'),
      '#description' => $this->t('This could potentially lead to GDPR / PII data problems. e.g. An anonymous user placed an order but made a typo in the email address, this order could be assigned to another user by mistake. Then, the second user could see the personal information of the first user.'),
      '#weight' => '0',
    ];
    $form['send_one_time_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send the One-Time-Login link'),
      '#default_value' => $config->get('send_one_time_login'),
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('commerce_guest_registration.settings');
    $config->set('create_new', $values['create_new']);
    $config->set('assign_to_existing', $values['assign_to_existing']);
    $config->set('send_one_time_login', $values['send_one_time_login']);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
