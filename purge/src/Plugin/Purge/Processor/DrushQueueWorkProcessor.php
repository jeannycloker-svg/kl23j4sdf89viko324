<?php

namespace Drupal\purge\Plugin\Purge\Processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Attribute\PurgeProcessor;

/**
 * Processor for the 'drush p:queue-work' command.
 */
#[PurgeProcessor(
  id: 'drush_purge_queue_work',
  label: new TranslatableMarkup('Drush p:queue-work'),
  description: new TranslatableMarkup("Processor for the 'drush p:queue-work' command."),
)]
class DrushQueueWorkProcessor extends ProcessorBase implements ProcessorInterface {

}
