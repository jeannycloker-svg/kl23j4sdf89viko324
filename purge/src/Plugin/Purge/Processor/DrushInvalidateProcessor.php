<?php

namespace Drupal\purge\Plugin\Purge\Processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Attribute\PurgeProcessor;

/**
 * Processor for the 'drush p:invalidate' command.
 */
#[PurgeProcessor(
  id: 'drush_purge_invalidate',
  label: new TranslatableMarkup('Drush p:invalidate'),
  description: new TranslatableMarkup("Processor for the 'drush p:invalidate' command."),
)]
class DrushInvalidateProcessor extends ProcessorBase implements ProcessorInterface {

}
