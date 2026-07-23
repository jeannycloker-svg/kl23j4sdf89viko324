<?php

namespace Drupal\http_client_manager\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\http_client_manager\HttpClientManagerFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Wrapper action for all http_client_manager commands.
 *
 * @Action(
 *   id = "http_client_manager_command",
 *   deriver = "\Drupal\http_client_manager\Plugin\Action\CommandDeriver",
 *   nodocs = true
 * )
 */
class Command extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  use HttpActionResultTrait {
    defaultConfiguration as resultDefaultConfiguration;
    buildConfigurationForm as resultBuildForm;
    submitConfigurationForm as resultSubmitForm;
  }

  /**
   * The client manager factory.
   *
   * @var \Drupal\http_client_manager\HttpClientManagerFactoryInterface
   */
  protected HttpClientManagerFactoryInterface $clientManagerFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, HttpClientManagerFactoryInterface $clientManagerFactory) {
    $this->clientManagerFactory = $clientManagerFactory;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client_manager.factory')
    );

    $instance->privateTempStoreFactory = $container->get('tempstore.private');
    $instance->setStringTranslation($container->get('string_translation'));
    $instance->resultCache = $container->get('cache.http_client_manager_result');
    $instance->token = $container->get('token');
    if ($container->has('eca.token_services')) {
      $instance->ecaToken = $container->get('eca.token_services');
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = [];

    [, $serviceId, $commandName] = explode(':', $this->getPluginId());
    $client = $this->clientManagerFactory->get($serviceId);
    $command = $client->getCommand($commandName);
    foreach ($command->getParams() as $id => $param) {
      if ($param->getType() !== NULL) {
        $config[$id] = $param->getDefault();
      }
    }

    return $config + $this->resultDefaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = $this->resultBuildForm($form, $form_state);

    [, $serviceId, $commandName] = explode(':', $this->getPluginId());
    $client = $this->clientManagerFactory->get($serviceId);
    $command = $client->getCommand($commandName);
    foreach ($command->getParams() as $id => $param) {
      if ($param->getType() !== NULL) {
        $form[$id] = [
          '#type' => 'textfield',
          '#title' => $param->getDescription(),
          '#default_value' => $this->configuration[$id],
          '#required' => $param->isRequired(),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->resultSubmitForm($form, $form_state);

    [, $serviceId, $commandName] = explode(':', $this->getPluginId());
    $client = $this->clientManagerFactory->get($serviceId);
    $command = $client->getCommand($commandName);
    foreach ($command->getParams() as $id => $param) {
      if ($param->getType() !== NULL) {
        $this->configuration[$id] = $form_state->getValue($id);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = AccessResult::allowed();
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL): void {
    [, $serviceId, $commandName] = explode(':', $this->getPluginId());
    $client = $this->clientManagerFactory->get($serviceId);
    $command = $client->getCommand($commandName);
    $params = [];
    foreach ($command->getParams() as $id => $param) {
      if ($param->getType() !== NULL) {
        $params[$id] = $this->configuration[$id];
      }
    }
    $result = $client->call($commandName, $params);

    $this->storeResult($result);
  }

}
