<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Attribute\PurgeInvalidation;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes path based invalidation, e.g. "news/article-1".
 */
#[PurgeInvalidation(
  id: 'path',
  label: new TranslatableMarkup('Path'),
  description: new TranslatableMarkup('Invalidates by path.'),
  examples: ['news/article-1'],
  expression_required: TRUE,
  expression_can_be_empty: TRUE,
  expression_must_be_string: TRUE,
)]
class PathInvalidation extends InvalidationBase implements InvalidationInterface {

  /**
   * Whether wildcard should be checked.
   *
   * @var bool
   */
  protected $wildCardCheck = TRUE;

  /**
   * {@inheritdoc}
   */
  public function validateExpression() {
    parent::validateExpression();
    if ($this->wildCardCheck && (strpos($this->expression, '*') !== FALSE)) {
      throw new InvalidExpressionException('Path invalidations should not contain asterisks.');
    }
    if ($this->wildCardCheck && $this->expression === '*') {
      throw new InvalidExpressionException('Path invalidations cannot be "*".');
    }
    if (strpos($this->expression, ' ') !== FALSE) {
      throw new InvalidExpressionException('Path invalidations cannot contain spaces, use %20 instead.');
    }
    if (strpos($this->expression, '/') === 0) {
      throw new InvalidExpressionException('Path invalidations cannot start with slashes.');
    }
  }

}
