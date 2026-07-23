<?php

namespace Drupal\Tests\acquia_purge\Kernel\Plugin\Purge\Purger;

use Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface;
use Drupal\acquia_purge\Plugin\Purge\Purger\AcquiaPlatformCdnPurger;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\ClientInterface;

/**
 * Tests the AcquiaPlatformCdnPurger plugin.
 *
 * @coversDefaultClass \Drupal\acquia_purge\Plugin\Purge\Purger\AcquiaPlatformCdnPurger
 * @group acquia_purge
 */
class AcquiaPlatformCdnPurgerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'purge',
    'purge_queuer_coretags',
    'acquia_purge',
  ];

  /**
   * The purger instance under test.
   */
  protected AcquiaPlatformCdnPurger $purger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['purge']);
    $platformInfo = $this->createMock(PlatformInfoInterface::class);
    $httpClient = $this->createMock(ClientInterface::class);
    $this->purger = new AcquiaPlatformCdnPurger(
      $platformInfo,
      $httpClient,
      ['id' => 'test_purger'],
      'acquia_platform_cdn',
      [
        'id' => 'acquia_platform_cdn',
        'label' => 'Acquia Platform CDN',
        'description' => 'Test',
        'provider' => 'acquia_purge',
        'class' => AcquiaPlatformCdnPurger::class,
      ]
    );
  }

  /**
   * Tests routeTypeToMethod returns the correct method names.
   *
   * @covers ::routeTypeToMethod
   */
  public function testRouteTypeToMethodReturnsCorrectMappings(): void {
    $this->assertSame('invalidateTags', $this->purger->routeTypeToMethod('tag'));
    $this->assertSame('invalidateUrls', $this->purger->routeTypeToMethod('url'));
    $this->assertSame('invalidateEverything', $this->purger->routeTypeToMethod('everything'));
  }

  /**
   * Tests routeTypeToMethod falls back to invalidate for unknown types.
   *
   * @covers ::routeTypeToMethod
   */
  public function testRouteTypeToMethodFallsBackToInvalidate(): void {
    $this->assertSame('invalidate', $this->purger->routeTypeToMethod('nonexistent'));
  }

  /**
   * Tests hasRuntimeMeasurement returns TRUE.
   *
   * @covers ::hasRuntimeMeasurement
   */
  public function testHasRuntimeMeasurementReturnsTrue(): void {
    $this->assertTrue($this->purger->hasRuntimeMeasurement());
  }

  /**
   * Tests that invalidate throws an exception.
   *
   * @covers ::invalidate
   */
  public function testInvalidateThrowsException(): void {
    $this->expectException(\Exception::class);
    $this->purger->invalidate([]);
  }

}
