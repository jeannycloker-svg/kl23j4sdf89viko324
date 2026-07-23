<?php

namespace Drupal\preprocess_event_dispatcher\Variables;

use Drupal\Core\Entity\EntityInterface;
use Drupal\eck\EckEntityInterface;

/**
 * Class EckEntityEventVariables.
 *
 * @deprecated in hook_event_dispatcher:3.4.0 and is removed from
 *   hook_event_dispatcher:4.0.0. There is no replacement.
 *
 * @see https://www.drupal.org/node/3308062
 */
class EckEntityEventVariables extends AbstractEntityEventVariables {

  /**
   * {@inheritdoc}
   */
  public function __construct(array &$variables) {
    @trigger_error('Support for eck module is deprecated in hook_event_dispatcher:3.4.0 and is removed from hook_event_dispatcher:4.0.0. There is no replacement. See https://www.drupal.org/node/3308062', E_USER_DEPRECATED);
    parent::__construct($variables);
  }

  /**
   * Get the EckEntity.
   *
   * @return \Drupal\eck\EckEntityInterface
   *   EckEntity.
   */
  public function getEckEntity(): EckEntityInterface {
    return $this->variables['eck_entity'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): EntityInterface {
    return $this->getEckEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return $this->variables['bundle'];
  }

  /**
   * {@inheritdoc}
   */
  public function getViewMode(): string {
    return $this->variables['elements']['#view_mode'];
  }

}
