<?php

namespace Drupal\purge\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a PurgeQueuer attribute object.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class PurgeQueuer extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly bool $enable_by_default = FALSE,
    public readonly string $configform = '',
    public readonly ?string $deriver = NULL,
  ) {}

}
