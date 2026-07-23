<?php

namespace Drupal\simple_sitemap\Manager;

use Drupal\simple_sitemap\Entity\SimpleSitemapInterface;
use Drupal\simple_sitemap\Queue\QueueWorker;

/**
 * Provides an interface for the generator.
 */
interface GeneratorInterface extends SitemapGetterInterface {

  /**
   * Returns a specific setting or a default value if setting does not exist.
   *
   * @param string $name
   *   Name of the setting, like 'max_links'.
   * @param mixed $default
   *   Value to be returned if the setting does not exist in the configuration.
   *
   * @return mixed
   *   The current setting from configuration or a default value.
   */
  public function getSetting(string $name, $default = NULL);

  /**
   * Stores a specific sitemap setting in configuration.
   *
   * @param string $name
   *   Setting name, like 'max_links'.
   * @param mixed $setting
   *   The setting to be saved.
   *
   * @return $this
   */
  public function saveSetting(string $name, $setting): GeneratorInterface;

  /**
   * Gets the default sitemap from the currently set sitemaps.
   *
   * @return \Drupal\simple_sitemap\Entity\SimpleSitemapInterface|null
   *   The default sitemap or NULL if there are no sitemaps.
   */
  public function getDefaultSitemap(): ?SimpleSitemapInterface;

  /**
   * Returns a sitemap variant, its index, or its requested chunk.
   *
   * @param int|null $delta
   *   Optional delta of the chunk.
   *
   * @return string|null
   *   If no chunk delta is provided, either the sitemap string is returned,
   *   or its index string in case of a chunked sitemap.
   *   If a chunk delta is provided, the relevant chunk string is returned.
   *   Returns null if the content is not retrievable from the database.
   */
  public function getContent(?int $delta = NULL): ?string;

  /**
   * Generates all sitemaps.
   *
   * @param string $from
   *   Can be 'form', 'drush', 'cron' and 'backend'.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function generate(string $from = QueueWorker::GENERATE_TYPE_FORM): GeneratorInterface;

  /**
   * Queues links from currently set variants.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function queue(): GeneratorInterface;

  /**
   * Deletes the queue and queues links from currently set variants.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function rebuildQueue(): GeneratorInterface;

  /**
   * Gets the simple_sitemap.entity_manager service.
   *
   * @return \Drupal\simple_sitemap\Manager\EntityManager
   *   The simple_sitemap.entity_manager service.
   */
  public function entityManager(): EntityManager;

  /**
   * Gets the simple_sitemap.custom_link_manager service.
   *
   * @return \Drupal\simple_sitemap\Manager\CustomLinkManager
   *   The simple_sitemap.custom_link_manager service.
   */
  public function customLinkManager(): CustomLinkManager;

}
