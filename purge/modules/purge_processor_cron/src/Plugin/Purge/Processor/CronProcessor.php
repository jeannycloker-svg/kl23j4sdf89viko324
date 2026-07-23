<?php

namespace Drupal\purge_processor_cron\Plugin\Purge\Processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Attribute\PurgeProcessor;
use Drupal\purge\Plugin\Purge\Processor\ProcessorBase;
use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;

/**
 * Cron processor.
 */
#[PurgeProcessor(
  id: 'cron',
  label: new TranslatableMarkup('Cron processor'),
  description: new TranslatableMarkup('Processes the queue every time cron runs, recommended for most configurations.'),
  enable_by_default: TRUE,
  configform: '',
)]
class CronProcessor extends ProcessorBase implements ProcessorInterface {

}
