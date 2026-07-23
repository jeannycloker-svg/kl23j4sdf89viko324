<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Attribute\PurgeInvalidation;

/**
 * Describes an entire domain to be invalidated.
 */
#[PurgeInvalidation(
  id: 'domain',
  label: new TranslatableMarkup('Domain'),
  description: new TranslatableMarkup('Invalidates an entire domain name.'),
  examples: ['www.site.com', 'site.com'],
  expression_required: TRUE,
  expression_can_be_empty: FALSE,
  expression_must_be_string: TRUE,
)]
class DomainInvalidation extends InvalidationBase implements InvalidationInterface {}
