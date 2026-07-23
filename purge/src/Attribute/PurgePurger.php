<?php

namespace Drupal\purge\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a PurgePurger attribute object.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class PurgePurger extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly string $configform = '',
    public readonly float $cooldown_time = 0.0,
    public readonly bool $multi_instance = FALSE,
    public readonly array $types = [],
    public readonly ?string $deriver = NULL,
  ) {}

}
