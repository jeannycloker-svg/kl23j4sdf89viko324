<?php

namespace Drupal\purge\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a PurgeDiagnosticCheck attribute object.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class PurgeDiagnosticCheck extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $title = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly array $dependent_queue_plugins = [],
    public readonly array $dependent_purger_plugins = [],
    public readonly ?string $deriver = NULL,
  ) {}

}
