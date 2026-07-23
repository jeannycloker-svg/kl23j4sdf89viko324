<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Attribute\PurgeInvalidation;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes wildcardpath based invalidation, e.g. "news/*".
 */
#[PurgeInvalidation(
  id: 'wildcardpath',
  label: new TranslatableMarkup('Path wildcard'),
  description: new TranslatableMarkup('Invalidates by path.'),
  examples: ['news/*'],
  expression_required: TRUE,
  expression_can_be_empty: FALSE,
  expression_must_be_string: TRUE,
)]
class WildcardPathInvalidation extends PathInvalidation implements InvalidationInterface {

  /**
   * {@inheritdoc}
   */
  public function validateExpression() {
    $this->wildCardCheck = FALSE;
    parent::validateExpression();
    if (strpos($this->expression, '*') === FALSE) {
      throw new InvalidExpressionException('Wildcard invalidations should contain an asterisk.');
    }
  }

}
