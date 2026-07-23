<?php

namespace Drupal\email_tfa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides a user Email TFA form.
 *
 * @internal
 */
class EmailTfaVerifyForm extends FormBase {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a TFA verification form.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger_factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, LoggerChannelFactoryInterface $logger_factory, AccountProxyInterface $current_user) {
    $this->tempStoreFactory = $temp_store_factory->get('email_tfa');
    $this->loggerFactory = $logger_factory;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('logger.factory'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_tfa_verify_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('email_tfa.settings');
    if ($this->getRequest()->query->get('destination')) {
      // save the destination in the $form_state to be used on submit.
      $form_state->set('destination', $this->getRequest()->query->get('destination'));
      // remove the destination from the query string.
      $this->getRequest()->query->remove('destination');
    }
    $form['email_tfa_verify'] = [
      '#type' => 'textfield',
      '#title' => $config->get('security_code_label_text'),
      '#description' => $config->get('security_code_description_text'),
      '#size' => 60,
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#name' => 'verify',
      '#value' => $config->get('security_code_verify_text'),
      '#button_type' => 'primary',
    ];
    $form['interrupt'] = [
      '#type' => 'submit',
      '#name' => 'interrupt',
      '#value' => $config->get('security_code_interrupt_text'),
      '#submit' => [[$this, 'interruptAuth']],
      '#limit_validation_errors' => [],
      '#button_type' => 'secondary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('email_tfa.settings');
    $tfa = $form_state->getValue('email_tfa_verify');
    if ($tfa == $this->tempStoreFactory->get('email_tfa_otp_number') && is_numeric($tfa)) {
      $this->tempStoreFactory->set('email_tfa_user_verify', 1);
      if ($message = $config->get('verification_succeeded_message')) {
        $this->messenger()->addStatus($message);
      }
      // get the destination from the $form_state.
      $destination = $form_state->get('destination');
      // use the destination from the $form_state if it exists
      if ($destination) {
        $url = Url::fromUserInput($destination);
      }
      else {
        $url = Url::fromRoute('<front>');
      }

      $replacements = [
        '@email' => $this->currentUser->getEmail(),
        '@uid' => $this->currentUser->id(),
      ];

      if ($config->get('log_events')) {
        $replacements = [
          '@email' => $this->currentUser->getEmail(),
          '@uid' => $this->currentUser->id(),
        ];
        $this->loggerFactory->get('email_tfa')->info('user-email:@email, user-id:@uid has been logged in via email_tfa', $replacements);
      }

      $form_state->setRedirectUrl($url);
    }
    else {
      $this->getRequest()->getSession()->clear();
      $url = Url::fromRoute('user.login.http');
      $form_state->setRedirectUrl($url);
      if ($message = $config->get('verification_failed_message')) {
        $this->messenger()->addError($message);
      }
    }
  }

  /**
   * Interrupt the two-factor authentication.
   */
  public function interruptAuth(array &$form, FormStateInterface $form_state) {
    $config = $this->config('email_tfa.settings');
    $this->getRequest()->getSession()->clear();
    $url = Url::fromRoute('user.login.http');
    $form_state->setRedirectUrl($url);
    if ($message = $config->get('verification_interrupted_message')) {
      $this->messenger()->addError($message);
    }
  }

}
