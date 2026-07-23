<?php

namespace Drupal\Tests\acquia_purge\Kernel\AcquiaCloud;

use Drupal\acquia_purge\AcquiaCloud\PlatformInfo;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the PlatformInfo service.
 *
 * @coversDefaultClass \Drupal\acquia_purge\AcquiaCloud\PlatformInfo
 * @group acquia_purge
 */
class PlatformInfoTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'purge',
    'acquia_purge',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Creates a PlatformInfo instance with custom settings.
   *
   * @param array $settings
   *   Settings to use.
   * @param array $stateValues
   *   State values to set.
   *
   * @return \Drupal\acquia_purge\AcquiaCloud\PlatformInfo
   *   The platform info instance.
   */
  protected function createPlatformInfo(array $settings = [], array $stateValues = []): PlatformInfo {
    new Settings($settings);
    $state = $this->container->get('state');
    foreach ($stateValues as $key => $value) {
      $state->set($key, $value);
    }
    return new PlatformInfo('sites/default', Settings::getInstance(), $state);
  }

  /**
   * Tests that by default, site is not detected as Acquia Cloud.
   *
   * @covers ::isThisAcquiaCloud
   */
  public function testIsNotAcquiaCloudByDefault(): void {
    $platformInfo = $this->createPlatformInfo();
    $this->assertFalse($platformInfo->isThisAcquiaCloud());
  }

  /**
   * Tests that balancer addresses are parsed from settings.
   *
   * @covers ::getBalancerAddresses
   * @covers ::__construct
   */
  public function testBalancerAddressesFromSettings(): void {
    $platformInfo = $this->createPlatformInfo([
      'reverse_proxies' => ['10.0.0.1', '10.0.0.2', '10.0.0.3'],
    ]);

    $addresses = $platformInfo->getBalancerAddresses();
    $this->assertCount(3, $addresses);
    $this->assertContains('10.0.0.1', $addresses);
    $this->assertContains('10.0.0.2', $addresses);
    $this->assertContains('10.0.0.3', $addresses);
  }

  /**
   * Tests that invalid balancer addresses are filtered out.
   *
   * @covers ::getBalancerAddresses
   */
  public function testBalancerAddressesFiltersInvalid(): void {
    $platformInfo = $this->createPlatformInfo([
      'reverse_proxies' => ['10.0.0.1', '', NULL, 'not-an-ip', '192.168.1.1'],
    ]);

    $addresses = $platformInfo->getBalancerAddresses();
    $this->assertContains('10.0.0.1', $addresses);
    $this->assertContains('192.168.1.1', $addresses);
    $this->assertNotContains('', $addresses);
    $this->assertNotContains(NULL, $addresses);
  }

  /**
   * Tests that balancer token defaults to site name.
   *
   * @covers ::getBalancerToken
   */
  public function testBalancerTokenFromSiteName(): void {
    $platformInfo = $this->createPlatformInfo();
    $this->assertEquals('', $platformInfo->getBalancerToken());
  }

  /**
   * Tests that acquia_purge_token setting overrides default token.
   *
   * @covers ::getBalancerToken
   */
  public function testBalancerTokenOverride(): void {
    $platformInfo = $this->createPlatformInfo([
      'acquia_purge_token' => 'custom-token',
    ]);

    $this->assertEquals('custom-token', $platformInfo->getBalancerToken());
  }

  /**
   * Tests that empty acquia_purge_token does not override.
   *
   * @covers ::getBalancerToken
   */
  public function testBalancerTokenEmptyOverride(): void {
    $platformInfo = $this->createPlatformInfo([
      'acquia_purge_token' => '',
    ]);

    $this->assertEquals('', $platformInfo->getBalancerToken());
  }

  /**
   * Tests reading Platform CDN configuration from settings.
   *
   * @covers ::getPlatformCdnConfiguration
   */
  public function testPlatformCdnFromSettings(): void {
    $platformInfo = $this->createPlatformInfo([
      'acquia_service_credentials' => [
        'platform_cdn' => [
          'vendor' => 'fastly',
          'configuration' => [
            'service_id' => 'test-service-id',
            'token' => 'test-token',
          ],
        ],
      ],
    ]);

    $cdnConfig = $platformInfo->getPlatformCdnConfiguration();

    $this->assertEquals('settings', $cdnConfig['config']);
    $this->assertEquals('fastly', $cdnConfig['vendor']);
    $this->assertEquals('test-service-id', $cdnConfig['service_id']);
    $this->assertEquals('test-token', $cdnConfig['token']);
  }

  /**
   * Tests falling back to state for Platform CDN configuration.
   *
   * @covers ::getPlatformCdnConfiguration
   */
  public function testPlatformCdnFromState(): void {
    $platformInfo = $this->createPlatformInfo([], [
      'acquia_purge.platform_cdn' => [
        'vendor' => 'fastly',
        'service_id' => 'state-service-id',
        'token' => 'state-token',
      ],
    ]);

    $cdnConfig = $platformInfo->getPlatformCdnConfiguration();

    $this->assertEquals('state', $cdnConfig['config']);
    $this->assertEquals('fastly', $cdnConfig['vendor']);
    $this->assertEquals('state-service-id', $cdnConfig['service_id']);
    $this->assertEquals('state-token', $cdnConfig['token']);
  }

  /**
   * Tests settings take priority over state for CDN configuration.
   *
   * @covers ::getPlatformCdnConfiguration
   */
  public function testPlatformCdnSettingsPriority(): void {
    $platformInfo = $this->createPlatformInfo(
      [
        'acquia_service_credentials' => [
          'platform_cdn' => [
            'vendor' => 'fastly',
            'configuration' => [
              'service_id' => 'settings-service',
              'token' => 'settings-token',
            ],
          ],
        ],
      ],
      [
        'acquia_purge.platform_cdn' => [
          'vendor' => 'fastly',
          'service_id' => 'state-service',
          'token' => 'state-token',
        ],
      ]
    );

    $cdnConfig = $platformInfo->getPlatformCdnConfiguration();
    $this->assertEquals('settings', $cdnConfig['config']);
    $this->assertEquals('settings-service', $cdnConfig['service_id']);
  }

  /**
   * Tests that getPlatformCdnConfiguration throws when unconfigured.
   *
   * @covers ::getPlatformCdnConfiguration
   */
  public function testPlatformCdnThrowsWhenUnconfigured(): void {
    $platformInfo = $this->createPlatformInfo();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('No Platform CDN configuration available.');
    $platformInfo->getPlatformCdnConfiguration();
  }

  /**
   * Tests that site identifier is generated consistently.
   *
   * @covers ::getSiteIdentifier
   */
  public function testSiteIdentifierGeneration(): void {
    $platformInfo1 = $this->createPlatformInfo();
    $platformInfo2 = $this->createPlatformInfo();

    $this->assertEquals(
      $platformInfo1->getSiteIdentifier(),
      $platformInfo2->getSiteIdentifier(),
      'Site identifier should be consistent across instances.'
    );
    $this->assertEquals(16, strlen($platformInfo1->getSiteIdentifier()));
  }

  /**
   * Tests getSitePath returns the site path.
   *
   * @covers ::getSitePath
   */
  public function testGetSitePath(): void {
    $platformInfo = $this->createPlatformInfo();
    $this->assertEquals('sites/default', $platformInfo->getSitePath());
  }

  /**
   * Tests getSiteEnvironment returns empty when not on Acquia.
   *
   * @covers ::getSiteEnvironment
   */
  public function testGetSiteEnvironment(): void {
    $platformInfo = $this->createPlatformInfo();
    $this->assertEquals('', $platformInfo->getSiteEnvironment());
  }

  /**
   * Tests getSiteGroup returns empty when not on Acquia.
   *
   * @covers ::getSiteGroup
   */
  public function testGetSiteGroup(): void {
    $platformInfo = $this->createPlatformInfo();
    $this->assertEquals('', $platformInfo->getSiteGroup());
  }

  /**
   * Tests getSiteName returns empty when not on Acquia.
   *
   * @covers ::getSiteName
   */
  public function testGetSiteName(): void {
    $platformInfo = $this->createPlatformInfo();
    $this->assertEquals('', $platformInfo->getSiteName());
  }

  /**
   * Tests CDN config with missing vendor throws exception.
   *
   * @covers ::getPlatformCdnConfiguration
   */
  public function testPlatformCdnMissingVendorThrows(): void {
    $platformInfo = $this->createPlatformInfo([], [
      'acquia_purge.platform_cdn' => [
        'service_id' => 'test',
      ],
    ]);

    $this->expectException(\RuntimeException::class);
    $platformInfo->getPlatformCdnConfiguration();
  }

  /**
   * Tests CDN config with empty vendor throws exception.
   *
   * @covers ::getPlatformCdnConfiguration
   */
  public function testPlatformCdnEmptyVendorThrows(): void {
    $platformInfo = $this->createPlatformInfo([], [
      'acquia_purge.platform_cdn' => [
        'vendor' => '',
        'service_id' => 'test',
        'token' => 'test',
      ],
    ]);

    $this->expectException(\RuntimeException::class);
    $platformInfo->getPlatformCdnConfiguration();
  }

}
