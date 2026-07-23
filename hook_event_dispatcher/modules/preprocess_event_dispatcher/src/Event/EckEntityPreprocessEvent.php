<?php

namespace Drupal\preprocess_event_dispatcher\Event;

use Drupal\preprocess_event_dispatcher\Variables\AbstractEventVariables;

/**
 * Class EckEntityPreprocessEvent.
 *
 * @deprecated in hook_event_dispatcher:3.4.0 and is removed from
 *   hook_event_dispatcher:4.0.0. There is no replacement.
 *
 * @see https://www.drupal.org/node/3308062
 */
final class EckEntityPreprocessEvent extends AbstractPreprocessEntityEvent {

  /**
   * {@inheritdoc}
   */
  public function __construct(AbstractEventVariables $variables) {
    @trigger_error('Support for eck module is deprecated in hook_event_dispatcher:3.4.0 and is removed from hook_event_dispatcher:4.0.0. There is no replacement. See https://www.drupal.org/node/3308062', E_USER_DEPRECATED);
    parent::__construct($variables);
  }

  /**
   * {@inheritdoc}
   */
  public static function getHook(): string {
    return 'eck_entity';
  }

}
