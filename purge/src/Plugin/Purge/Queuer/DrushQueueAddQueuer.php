<?php

namespace Drupal\purge\Plugin\Purge\Queuer;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Attribute\PurgeQueuer;

/**
 * Queuer for the 'drush p:queue-add' command.
 */
#[PurgeQueuer(
  id: 'drush_purge_queue_add',
  label: new TranslatableMarkup('Drush p:queue-add'),
  description: new TranslatableMarkup("Queuer for the 'drush p:queue-add' command."),
)]
class DrushQueueAddQueuer extends QueuerBase implements QueuerInterface {

}
