<?php

namespace Drupal\remove_http_headers\EventSubscriber;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\remove_http_headers\Config\ConfigManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Invalidates the headers to remove cache when its config changes.
 */
class RemoveHttpHeadersConfigSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected CacheBackendInterface $cache,
  ) {
  }

  /**
   * Invalidates the headers to remove cache.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The event object.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    if ($event->getConfig()->getName() === 'remove_http_headers.settings' && $event->isChanged('headers_to_remove')) {
      $this->cache->invalidate(ConfigManager::HEADERS_TO_REMOVE_CACHE_TAG);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[ConfigEvents::SAVE][] = 'onConfigSave';
    return $events;
  }

}
