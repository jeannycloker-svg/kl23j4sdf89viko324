<?php

namespace Drupal\purge_queuer_coretags\Plugin\Purge\Queuer;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Attribute\PurgeQueuer;
use Drupal\purge\Plugin\Purge\Queuer\QueuerBase;
use Drupal\purge\Plugin\Purge\Queuer\QueuerInterface;

/**
 * Queues every tag that Drupal invalidates internally.
 */
#[PurgeQueuer(
  id: 'coretags',
  label: new TranslatableMarkup('Core tags queuer'),
  description: new TranslatableMarkup('Queues every tag that Drupal invalidates internally.'),
  enable_by_default: TRUE,
  configform: '\Drupal\purge_queuer_coretags\Form\ConfigurationForm',
)]
class CoreTagsQueuer extends QueuerBase implements QueuerInterface {

}
