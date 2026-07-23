<?php

namespace Drupal\token_filter\Hook;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Service to provide module hooks.
 */
final class TokenFilterHooks {

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_field')]
  public function preprocessField(array &$variables): void {
    if (
      isset($variables['element']['#object']) &&
      $variables['element']['#object'] instanceof ContentEntityInterface
    ) {
      $entity = &drupal_static('token_filter_entity');
      $entity = $variables['element']['#object'];
    }
  }

  /**
   * Implements hook_migration_plugins_alter().
   */
  #[Hook('migration_plugins_alter')]
  public function migrationPluginsAlter(array &$migrations): void {
    if (isset($migrations['d7_filter_format'])) {
      $migration = &$migrations['d7_filter_format'];

      $migration['process']['filters']['process']['id']['map']['filter_tokens'] = 'token_filter';
    }
  }

}
