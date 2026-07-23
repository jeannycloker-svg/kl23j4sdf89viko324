<?php

declare(strict_types=1);

namespace Drupal\redirect\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\redirect\Entity\Redirect;
use Drupal\Component\Utility\DeprecationHelper;
use Drupal\path_alias\PathAliasInterface;
use Drupal\redirect\RedirectRepository;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for redirect.
 */
class RedirectEntityHooks {
  use StringTranslationTrait;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected RedirectRepository $redirectRepository,
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Implements hook_entity_predelete().
   *
   * Will delete redirects based on the entity URL.
   */
  #[Hook('entity_predelete')]
  public function entityPredelete(EntityInterface $entity): void {
    try {
      if ($entity->getEntityType()->hasLinkTemplate('canonical') && $entity->toUrl('canonical')->isRouted()) {
        $this->redirectRepository->deleteByPath('internal:/' . $entity->toUrl('canonical')->getInternalPath());
        $this->redirectRepository->deleteByPath('entity:' . $entity->getEntityTypeId() . '/' . $entity->id());
      }
    }
    catch (RouteNotFoundException | MissingMandatoryParametersException) {
      // This can happen if a module incorrectly defines a link template, ignore
      // such errors.
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for path_alias.
   */
  #[Hook('path_alias_update')]
  public function pathAliasUpdate(PathAliasInterface $path_alias): void {
    $config = $this->configFactory->get('redirect.settings');
    if (!$config->get('auto_redirect')) {
      return;
    }
    /** @var \Drupal\path_alias\PathAliasInterface $original_path_alias */
    // @phpstan-ignore-next-line
    $original_path_alias = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.2.0', fn() => $path_alias->getOriginal(), fn() => $path_alias->original);
    // Delete all redirects having the same source as this alias.
    $this->redirectRepository->deleteByPath($path_alias->getAlias(), $path_alias->language()->getId(), FALSE);
    // Create redirect from the old path alias to the new one.
    if ($original_path_alias->getAlias() != $path_alias->getAlias()) {
      if (!$this->redirectRepository->findMatchingRedirect($original_path_alias->getAlias(), [], $original_path_alias->language()->getId())) {
        $redirect = Redirect::create();
        $redirect->setSource($original_path_alias->getAlias());
        $redirect->setRedirect($path_alias->getPath());
        $redirect->setLanguage($original_path_alias->language()->getId());
        $redirect->setStatusCode($config->get('default_status_code'));
        $redirect->save();
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for path_alias.
   */
  #[Hook('path_alias_insert')]
  public function pathAliasInsert(PathAliasInterface $path_alias): void {
    // Delete all redirects having the same source as this alias.
    $this->redirectRepository->deleteByPath($path_alias->getAlias(), $path_alias->language()->getId(), FALSE);
  }

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo(): array {
    $extra = [];
    if ($this->moduleHandler->moduleExists('node')) {
      $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
      foreach ($node_types as $node_type) {
        $extra['node'][$node_type->id()]['form']['url_redirects'] = [
          'label' => $this->t('URL redirects'),
          'description' => $this->t('Redirect module form elements'),
          'weight' => 50,
        ];
      }
    }
    return $extra;
  }

}
