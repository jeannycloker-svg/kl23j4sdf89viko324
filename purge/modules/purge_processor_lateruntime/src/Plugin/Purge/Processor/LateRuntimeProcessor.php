<?php

namespace Drupal\purge_processor_lateruntime\Plugin\Purge\Processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Attribute\PurgeProcessor;
use Drupal\purge\Plugin\Purge\Processor\ProcessorBase;
use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;

/**
 * Late runtime processor.
 */
#[PurgeProcessor(
  id: 'lateruntime',
  label: new TranslatableMarkup('Late runtime processor'),
  description: new TranslatableMarkup('Process the queue on every request, this is only recommended on high latency configurations.'),
  enable_by_default: TRUE,
  configform: '',
)]
class LateRuntimeProcessor extends ProcessorBase implements ProcessorInterface {

}
