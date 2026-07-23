<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Attribute\PurgeInvalidation;

/**
 * Describes invalidation by regular expression, e.g.: '\.(jpg|jpeg|css|js)$'.
 */
#[PurgeInvalidation(
  id: 'regex',
  label: new TranslatableMarkup('Regular expression'),
  description: new TranslatableMarkup('Invalidates by regular expression.'),
  examples: ['\.(jpg|jpeg|css|js)$'],
  expression_required: TRUE,
  expression_can_be_empty: FALSE,
  expression_must_be_string: TRUE,
)]
class RegularExpressionInvalidation extends InvalidationBase implements InvalidationInterface {}
