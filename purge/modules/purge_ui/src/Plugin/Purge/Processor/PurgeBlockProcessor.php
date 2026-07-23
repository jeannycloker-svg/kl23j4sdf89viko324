<?php

namespace Drupal\purge_ui\Plugin\Purge\Processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Attribute\PurgeProcessor;
use Drupal\purge\Plugin\Purge\Processor\ProcessorBase;
use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;

/**
 * Processor for \Drupal\purge_ui\Form\PurgeBlockForm.
 */
#[PurgeProcessor(
  id: 'purge_ui_block_processor',
  label: new TranslatableMarkup('Purge block(s)'),
  description: new TranslatableMarkup("Site builders can add 'purge this page' blocks to their block layout. Blocks configured to perform direct execution, will need this processor."),
  enable_by_default: TRUE,
  configform: '',
)]
class PurgeBlockProcessor extends ProcessorBase implements ProcessorInterface {

}
