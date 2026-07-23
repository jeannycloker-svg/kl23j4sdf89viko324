<?php

namespace Drupal\purge_ui\Plugin\Purge\Queuer;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Attribute\PurgeQueuer;
use Drupal\purge\Plugin\Purge\Queuer\QueuerBase;
use Drupal\purge\Plugin\Purge\Queuer\QueuerInterface;

/**
 * Queuer for \Drupal\purge_ui\Form\PurgeBlockForm.
 */
#[PurgeQueuer(
  id: 'purge_ui_block_queuer',
  label: new TranslatableMarkup('Purge block(s)'),
  description: new TranslatableMarkup("Site builders can add 'purge this page' blocks to their block layout. Blocks configured to queue the items, will need this queuer."),
  enable_by_default: TRUE,
  configform: '',
)]
class PurgeBlockQueuer extends QueuerBase implements QueuerInterface {

}
