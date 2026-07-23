<?php

namespace Drupal\Tests\acquia_purge_geoip\Kernel;

use Drupal\acquia_purge_geoip\EventSubscriber\SetVaryHeader;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the SetVaryHeader event subscriber.
 *
 * @coversDefaultClass \Drupal\acquia_purge_geoip\EventSubscriber\SetVaryHeader
 * @group acquia_purge
 */
class SetVaryHeaderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * The event subscriber under test.
   */
  protected SetVaryHeader $subscriber;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->subscriber = new SetVaryHeader();
  }

  /**
   * Creates a ResponseEvent for testing.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response object.
   *
   * @return \Symfony\Component\HttpKernel\Event\ResponseEvent
   *   The event.
   */
  protected function createResponseEvent(Response $response): ResponseEvent {
    $kernel = $this->createMock(HttpKernelInterface::class);
    $request = Request::create('/');

    return new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
  }

  /**
   * Tests that Vary header is set to X-Geo-Country.
   *
   * @covers ::onRespond
   */
  public function testAddsVaryHeader(): void {
    $response = new Response();
    $event = $this->createResponseEvent($response);

    $this->subscriber->onRespond($event);

    $this->assertEquals('X-Geo-Country', $response->headers->get('Vary'));
  }

  /**
   * Tests that existing Vary header is overwritten.
   *
   * @covers ::onRespond
   */
  public function testOverwritesExistingVaryHeader(): void {
    $response = new Response();
    $response->headers->set('Vary', 'Accept-Encoding');
    $event = $this->createResponseEvent($response);

    $this->subscriber->onRespond($event);

    $this->assertEquals('X-Geo-Country', $response->headers->get('Vary'));
  }

  /**
   * Tests that getSubscribedEvents returns correct configuration.
   *
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEvents(): void {
    $events = SetVaryHeader::getSubscribedEvents();

    $this->assertIsArray($events);
    $this->assertArrayHasKey('kernel.response', $events);
  }

}
