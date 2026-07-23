<?php

namespace Drupal\Tests\acquia_purge\Kernel\AcquiaPlatformCdn;

use Drupal\acquia_purge\AcquiaPlatformCdn\BackendFactory;
use Drupal\acquia_purge\AcquiaPlatformCdn\FastlyBackend;
use Drupal\acquia_purge\Plugin\Purge\Purger\Debugger;
use Drupal\purge\Logger\LoggerChannelPart;
use Drupal\Tests\acquia_purge\Kernel\AcquiaPurgeKernelTestBase;
use Psr\Log\NullLogger;

/**
 * Tests the BackendFactory class.
 *
 * @coversDefaultClass \Drupal\acquia_purge\AcquiaPlatformCdn\BackendFactory
 * @group acquia_purge
 */
class BackendFactoryTest extends AcquiaPurgeKernelTestBase {

  /**
   * Tests that get() returns NULL without CDN configuration.
   *
   * @covers ::get
   * @covers ::getConfig
   */
  public function testGetReturnsNullWithoutConfig(): void {
    $platformInfo = $this->createMockPlatformInfo();
    $httpClient = $this->createMockHttpClient([]);
    $logger = new LoggerChannelPart(new NullLogger(), 0, []);
    $debugger = new Debugger($logger);

    $backend = BackendFactory::get($platformInfo, $logger, $debugger, $httpClient);

    $this->assertNull($backend);
  }

  /**
   * Tests that get() returns NULL for unknown vendor.
   *
   * @covers ::get
   * @covers ::getClassFromConfig
   */
  public function testGetReturnsNullForUnknownVendor(): void {
    $platformInfo = $this->createMockPlatformInfo([
      'platformCdn' => [
        'vendor' => 'unknown_vendor',
        'config' => 'settings',
        'service_id' => 'test',
        'token' => 'test',
      ],
    ]);
    $httpClient = $this->createMockHttpClient([]);
    $logger = new LoggerChannelPart(new NullLogger(), 0, []);
    $debugger = new Debugger($logger);

    $backend = BackendFactory::get($platformInfo, $logger, $debugger, $httpClient);

    $this->assertNull($backend);
  }

  /**
   * Tests that get() returns FastlyBackend for fastly vendor.
   *
   * @covers ::get
   * @covers ::getClassFromConfig
   */
  public function testGetReturnsFastlyBackend(): void {
    $platformInfo = $this->createMockPlatformInfoWithFastly();
    $httpClient = $this->createMockHttpClient([]);
    $logger = new LoggerChannelPart(new NullLogger(), 0, []);
    $debugger = new Debugger($logger);

    $backend = BackendFactory::get($platformInfo, $logger, $debugger, $httpClient);

    $this->assertInstanceOf(FastlyBackend::class, $backend);
  }

  /**
   * Tests that get() returns NULL when validation fails.
   *
   * @covers ::get
   */
  public function testGetReturnsNullForInvalidConfig(): void {
    $platformInfo = $this->createMockPlatformInfo([
      'platformCdn' => [
        'vendor' => 'fastly',
        'config' => 'settings',
      ],
    ]);
    $httpClient = $this->createMockHttpClient([]);
    $logger = new LoggerChannelPart(new NullLogger(), 0, []);
    $debugger = new Debugger($logger);

    $backend = BackendFactory::get($platformInfo, $logger, $debugger, $httpClient);

    $this->assertNull($backend);
  }

  /**
   * Tests getClassFromConfig mapping.
   *
   * @covers ::getClassFromConfig
   */
  public function testGetClassFromConfig(): void {
    $fastlyClass = BackendFactory::getClassFromConfig([
      'vendor' => 'fastly',
    ]);
    $this->assertEquals(FastlyBackend::class, $fastlyClass);

    $unknownClass = BackendFactory::getClassFromConfig([
      'vendor' => 'unknown',
    ]);
    $this->assertNull($unknownClass);
  }

  /**
   * Tests getClass method.
   *
   * @covers ::getClass
   */
  public function testGetClass(): void {
    $platformInfo = $this->createMockPlatformInfoWithFastly();

    $class = BackendFactory::getClass($platformInfo);
    $this->assertEquals(FastlyBackend::class, $class);
  }

  /**
   * Tests getClass returns NULL when no config available.
   *
   * @covers ::getClass
   */
  public function testGetClassReturnsNullWithoutConfig(): void {
    $platformInfo = $this->createMockPlatformInfo();

    $class = BackendFactory::getClass($platformInfo);
    $this->assertNull($class);
  }

  /**
   * Tests getConfig returns NULL on RuntimeException.
   *
   * @covers ::getConfig
   */
  public function testGetConfigReturnsNullOnException(): void {
    $platformInfo = $this->createMockPlatformInfo();

    $config = BackendFactory::getConfig($platformInfo);
    $this->assertNull($config);
  }

  /**
   * Tests getConfig returns config when available.
   *
   * @covers ::getConfig
   */
  public function testGetConfigReturnsConfig(): void {
    $platformInfo = $this->createMockPlatformInfoWithFastly();

    $config = BackendFactory::getConfig($platformInfo);

    $this->assertIsArray($config);
    $this->assertEquals('fastly', $config['vendor']);
  }

}
