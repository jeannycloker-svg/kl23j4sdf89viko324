<?php

namespace Drupal\http_client_manager\Plugin\Action;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Utility\Token;

/**
 * Trait for Http Client Manager actions that store fetched results.
 */
trait HttpActionResultTrait {

  /**
   * Private tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected PrivateTempStoreFactory $privateTempStoreFactory;

  /**
   * Result cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $resultCache;

  /**
   * The Drupal token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * The ECA token service, if available.
   *
   * @var \Drupal\eca\Token\TokenServices|null
   */
  protected $ecaToken = NULL;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'received_result_storage' => 'result_cache',
      'received_result_key' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $storages = $this->supportedStorages();
    if (count($storages) > 1) {
      $form['received_result_storage'] = [
        '#type' => 'select',
        '#title' => $this->t('Result storage'),
        '#description' => $this->t('The fetched result will be written to this selected storage.'),
        '#options' => $storages,
        '#default_value' => $this->configuration['received_result_storage'] ?? (isset($storages['result_cache']) ? 'result_cache' : key($storages)),
        '#required' => TRUE,
      ];
    }
    $form['received_result_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Storage key'),
      '#description' => $this->t('The key identifies the stored result from the used storage. When fetching user-related results, this key must be unique per user. Otherwise, results may overwrite each other. This field supports tokens.'),
      '#size' => 64,
      '#maxlength' => 1024,
      '#default_value' => $this->configuration['received_result_key'] ?? '',
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (NULL !== ($storage = $form_state->getValue('received_result_storage'))) {
      $this->configuration['received_result_storage'] = (string) $storage;
    }
    if (NULL !== ($key = $form_state->getValue('received_result_key'))) {
      $this->configuration['received_result_key'] = (string) $key;
    }
  }

  /**
   * Returns supported storages where the fetched result can be put.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The supported storages as options list.
   */
  protected function supportedStorages(): array {
    $storages = [
      'private_tempstore' => $this->t('PrivateTempStore "@name"', ['@name' => 'http_client_manager']),
      'result_cache' => $this->t('Cache "@name"', ['@name' => 'http_client_manager_result']),
    ];
    if ($this->ecaToken) {
      $storages['eca_token'] = $this->t('Token for ECA');
    }

    return $storages;
  }

  /**
   * Stores the result into the targeted storage.
   *
   * @param mixed $result
   *   The fetched result.
   */
  protected function storeResult(mixed $result): void {
    $storage = $this->configuration['received_result_storage'] ?? key($this->supportedStorages());
    $key = ($this->configuration['received_result_key'] ?? '') !== '' ? $this->configuration['received_result_key'] : 'last result';
    $key = $this->token->replacePlain($key);

    switch ($storage) {

      case 'result_cache':
        $this->resultCache->set($key, $result->toArray());
        break;

      case 'private_tempstore':
        $this->privateTempStoreFactory->get('http_client_manager')->set($key, $result->toArray());
        break;

      case 'eca_token':
        if (!$this->ecaToken) {
          throw new \RuntimeException("Targeted storage is ECA token but the ECA module is not installed.");
        }
        $this->ecaToken->addTokenData($key, $result->toArray());
        break;

    }
  }

}
