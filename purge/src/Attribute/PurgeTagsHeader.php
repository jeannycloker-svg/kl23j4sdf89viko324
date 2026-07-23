<?php

namespace Drupal\purge\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines a PurgeTagsHeader attribute object.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class PurgeTagsHeader extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly string $header_name = '',
    public readonly array $dependent_purger_plugins = [],
    public readonly ?string $deriver = NULL,
  ) {}

}
