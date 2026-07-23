<?php

namespace Drupal\Tests\acquia_purge\Kernel\EventSubscriber;

use Drupal\acquia_purge\EventSubscriber\CacheableResponseSubscriber;
use Drupal\KernelTests\KernelTestBase;
use Drupal\purge\Plugin\Purge\TagsHeader\TagsHeadersServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tests the CacheableResponseSubscriber event subscriber.
 *
 * @coversDefaultClass \Drupal\acquia_purge\EventSubscriber\CacheableResponseSubscriber
 * @group acquia_purge
 */
class CacheableResponseSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests that getSubscribedEvents registers onRespond at priority -1000.
   *
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEventsRegistersOnRespond(): void {
    $events = CacheableResponseSubscriber::getSubscribedEvents();
    $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
    $this->assertContains(['onRespond', -1000], $events[KernelEvents::RESPONSE]);
  }

  /**
   * Tests that onRespond skips sub-requests.
   *
   * @covers ::onRespond
   */
  public function testOnRespondSkipsSubRequests(): void {
    $tagsHeaders = $this->createMock(TagsHeadersServiceInterface::class);
    $subscriber = new CacheableResponseSubscriber($tagsHeaders);
    $kernel = $this->createMock(HttpKernelInterface::class);
    $request = Request::create('/');
    $response = new Response('test');
    $initialHeaders = $response->headers->all();
    $event = new ResponseEvent(
      $kernel,
      $request,
      HttpKernelInterface::SUB_REQUEST,
      $response
    );
    $subscriber->onRespond($event);
    $this->assertSame($initialHeaders, $response->headers->all());
  }

  /**
   * Tests that onRespond skips non-cacheable responses.
   *
   * @covers ::onRespond
   */
  public function testOnRespondSkipsNonCacheableResponses(): void {
    $tagsHeaders = $this->createMock(TagsHeadersServiceInterface::class);
    $subscriber = new CacheableResponseSubscriber($tagsHeaders);
    $kernel = $this->createMock(HttpKernelInterface::class);
    $request = Request::create('/');
    $response = new Response('test');
    $initialHeaders = $response->headers->all();
    $event = new ResponseEvent(
      $kernel,
      $request,
      HttpKernelInterface::MAIN_REQUEST,
      $response
    );
    $subscriber->onRespond($event);
    $this->assertSame($initialHeaders, $response->headers->all());
  }

}
