<?php

namespace Drupal\preprocess_event_dispatcher\Factory;

use Drupal\preprocess_event_dispatcher\Event\AbstractPreprocessEvent;
use Drupal\preprocess_event_dispatcher\Event\EckEntityPreprocessEvent;
use Drupal\preprocess_event_dispatcher\Variables\EckEntityEventVariables;

/**
 * Class EckEntityPreprocessEventFactory.
 *
 * @deprecated in hook_event_dispatcher:3.4.0 and is removed from
 *   hook_event_dispatcher:4.0.0. There is no replacement.
 *
 * @see https://www.drupal.org/node/3308062
 */
final class EckEntityPreprocessEventFactory implements PreprocessEventFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function createEvent(array &$variables): AbstractPreprocessEvent {
    @trigger_error('Support for eck module is deprecated in hook_event_dispatcher:3.4.0 and is removed from hook_event_dispatcher:4.0.0. There is no replacement. See https://www.drupal.org/node/3308062', E_USER_DEPRECATED);
    return new EckEntityPreprocessEvent(new EckEntityEventVariables($variables));
  }

  /**
   * {@inheritdoc}
   */
  public function getEventHook(): string {
    return EckEntityPreprocessEvent::getHook();
  }

}
