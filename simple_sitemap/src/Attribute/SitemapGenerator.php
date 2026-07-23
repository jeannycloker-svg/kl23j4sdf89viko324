<?php

namespace Drupal\simple_sitemap\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a SitemapGenerator attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class SitemapGenerator extends Plugin {

  /**
   * Constructs a SitemapGenerator attribute.
   *
   * @param string $id
   *   The generator ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   The human-readable name of the generator.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   A short description of the generator.
   * @param array $settings
   *   Default generator settings.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly array $settings = [],
  ) {}

}
