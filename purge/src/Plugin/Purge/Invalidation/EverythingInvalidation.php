<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Attribute\PurgeInvalidation;

/**
 * Describes that everything is to be invalidated.
 */
#[PurgeInvalidation(
  id: 'everything',
  label: new TranslatableMarkup('Everything'),
  description: new TranslatableMarkup('Invalidates everything.'),
  expression_required: FALSE,
)]
class EverythingInvalidation extends InvalidationBase implements InvalidationInterface {}
