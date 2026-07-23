<?php

namespace Drupal\Tests\acquia_purge\Traits;

use Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface;

/**
 * Provides mock PlatformInfo objects for Acquia Purge tests.
 */
trait AcquiaPurgePlatformInfoTrait {

  /**
   * Create a mock PlatformInfo object.
   *
   * @param array $overrides
   *   Optional overrides for default values.
   *
   * @return \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface
   *   The mocked platform info object.
   */
  protected function createMockPlatformInfo(array $overrides = []): PlatformInfoInterface {
    $defaults = [
      'balancerAddresses' => ['10.0.0.1', '10.0.0.2'],
      'balancerToken' => 'sitedev',
      'siteIdentifier' => 'abc123def456gh78',
      'siteName' => 'sitedev',
      'siteGroup' => 'site',
      'siteEnvironment' => 'dev',
      'sitePath' => 'sites/default',
      'isAcquiaCloud' => TRUE,
      'platformCdn' => NULL,
    ];

    $config = array_merge($defaults, $overrides);

    $platformInfo = $this->createMock(PlatformInfoInterface::class);

    $platformInfo->method('getBalancerAddresses')
      ->willReturn($config['balancerAddresses']);

    $platformInfo->method('getBalancerToken')
      ->willReturn($config['balancerToken']);

    $platformInfo->method('getSiteIdentifier')
      ->willReturn($config['siteIdentifier']);

    $platformInfo->method('getSiteName')
      ->willReturn($config['siteName']);

    $platformInfo->method('getSiteGroup')
      ->willReturn($config['siteGroup']);

    $platformInfo->method('getSiteEnvironment')
      ->willReturn($config['siteEnvironment']);

    $platformInfo->method('getSitePath')
      ->willReturn($config['sitePath']);

    $platformInfo->method('isThisAcquiaCloud')
      ->willReturn($config['isAcquiaCloud']);

    if ($config['platformCdn'] !== NULL) {
      $platformInfo->method('getPlatformCdnConfiguration')
        ->willReturn($config['platformCdn']);
    }
    else {
      $platformInfo->method('getPlatformCdnConfiguration')
        ->willThrowException(new \RuntimeException('No Platform CDN configuration available.'));
    }

    return $platformInfo;
  }

  /**
   * Create a mock PlatformInfo configured for Fastly CDN.
   *
   * @param array $overrides
   *   Optional overrides for default values.
   *
   * @return \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface
   *   The mocked platform info object.
   */
  protected function createMockPlatformInfoWithFastly(array $overrides = []): PlatformInfoInterface {
    $defaults = [
      'platformCdn' => [
        'vendor' => 'fastly',
        'config' => 'settings',
        'service_id' => 'test-service-id',
        'token' => 'test-fastly-token',
      ],
    ];

    return $this->createMockPlatformInfo(array_merge($defaults, $overrides));
  }

  /**
   * Create a mock PlatformInfo for non-Acquia environment.
   *
   * @return \Drupal\acquia_purge\AcquiaCloud\PlatformInfoInterface
   *   The mocked platform info object.
   */
  protected function createMockPlatformInfoNonAcquia(): PlatformInfoInterface {
    return $this->createMockPlatformInfo([
      'balancerAddresses' => [],
      'balancerToken' => '',
      'siteIdentifier' => '',
      'siteName' => '',
      'siteGroup' => '',
      'siteEnvironment' => '',
      'isAcquiaCloud' => FALSE,
    ]);
  }

}
