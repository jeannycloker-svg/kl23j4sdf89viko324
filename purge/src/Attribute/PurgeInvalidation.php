<?php

namespace Drupal\purge\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a PurgeInvalidation attribute object.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class PurgeInvalidation extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly array $examples = [],
    public readonly bool $expression_required = TRUE,
    public readonly bool $expression_can_be_empty = FALSE,
    public readonly bool $expression_must_be_string = FALSE,
    public readonly ?string $deriver = NULL,
  ) {}

}
