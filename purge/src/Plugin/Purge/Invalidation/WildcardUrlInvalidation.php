<?php

namespace Drupal\purge\Plugin\Purge\Invalidation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\purge\Attribute\PurgeInvalidation;
use Drupal\purge\Plugin\Purge\Invalidation\Exception\InvalidExpressionException;

/**
 * Describes wildcard URL based invalidation, e.g. "http://site.com/node/*".
 */
#[PurgeInvalidation(
  id: 'wildcardurl',
  label: new TranslatableMarkup('Url wildcard'),
  description: new TranslatableMarkup('Invalidates by URL.'),
  examples: ['http://site.com/node/*'],
  expression_required: TRUE,
  expression_can_be_empty: FALSE,
)]
class WildcardUrlInvalidation extends UrlInvalidation implements InvalidationInterface {

  /**
   * {@inheritdoc}
   */
  public function validateExpression() {
    $this->wildCardCheck = FALSE;
    $url = parent::validateExpression();
    if (strpos($url, '*') === FALSE) {
      throw new InvalidExpressionException('Wildcard invalidations should contain an asterisk.');
    }
  }

}
