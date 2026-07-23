<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Attribute\PurgeQueue;

/**
 * API-compliant null queue back-end.
 *
 * This plugin is not intended for usage but gets loaded during module
 * installation, when configuration rendered invalid or when no other plugins
 * are available. Because its API compliant, Drupal won't crash visibly.
 */
#[PurgeQueue(
  id: 'null',
  label: new TranslatableMarkup('Null'),
  description: new TranslatableMarkup('API-compliant null queue back-end.'),
)]
class NullQueue extends MemoryQueue implements QueueInterface {}
