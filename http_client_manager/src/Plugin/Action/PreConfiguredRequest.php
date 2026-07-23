<?php

namespace Drupal\http_client_manager\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Wrapper action for all pre-configured http requests.
 *
 * @Action(
 *   id = "http_client_manager_preconfigured_request",
 *   deriver = "\Drupal\http_client_manager\Plugin\Action\PreConfiguredRequestDeriver",
 *   nodocs = true
 * )
 */
class PreConfiguredRequest extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  use HttpActionResultTrait;

  /**
   * The entity storage interface for http config request config entities.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected ConfigEntityStorageInterface $entityStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->privateTempStoreFactory = $container->get('tempstore.private');
    $instance->setStringTranslation($container->get('string_translation'));
    $instance->resultCache = $container->get('cache.http_client_manager_result');
    $instance->token = $container->get('token');
    if ($container->has('eca.token_services')) {
      $instance->ecaToken = $container->get('eca.token_services');
    }

    $instance->entityStorage = $container->get('entity_type.manager')->getStorage('http_config_request');
    return $instance;
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
    [, $entityId] = explode(':', $this->getPluginId());
    /** @var \Drupal\http_client_manager\Entity\HttpConfigRequestInterface $request */
    $request = $this->entityStorage->load($entityId);
    $result = $request->execute();

    $this->storeResult($result);
  }

}
