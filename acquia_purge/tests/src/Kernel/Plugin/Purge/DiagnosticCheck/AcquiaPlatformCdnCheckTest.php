<?php

namespace Drupal\Tests\acquia_purge\Kernel\Plugin\Purge\DiagnosticCheck;

use Drupal\acquia_purge\Plugin\Purge\DiagnosticCheck\AcquiaPlatformCdnCheck;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\Tests\acquia_purge\Kernel\AcquiaPurgeKernelTestBase;

/**
 * Tests the AcquiaPlatformCdnCheck diagnostic check.
 *
 * @coversDefaultClass \Drupal\acquia_purge\Plugin\Purge\DiagnosticCheck\AcquiaPlatformCdnCheck
 * @group acquia_purge
 */
class AcquiaPlatformCdnCheckTest extends AcquiaPurgeKernelTestBase {

  /**
   * Creates an AcquiaPlatformCdnCheck instance.
   *
   * @param array $platformOverrides
   *   Platform info overrides.
   *
   * @return \Drupal\acquia_purge\Plugin\Purge\DiagnosticCheck\AcquiaPlatformCdnCheck
   *   The diagnostic check instance.
   */
  protected function createCheck(array $platformOverrides = []): AcquiaPlatformCdnCheck {
    $platformInfo = $this->createMockPlatformInfo($platformOverrides);

    return new AcquiaPlatformCdnCheck(
      $platformInfo,
      [],
      'acquia_purge_platformcdn_check',
      [
        'id' => 'acquia_purge_platformcdn_check',
        'title' => 'Acquia Platform CDN',
        'description' => 'Validates the Acquia Platform CDN configuration.',
        'dependent_queue_plugins' => [],
        'dependent_purger_plugins' => ['acquia_platform_cdn'],
      ]
    );
  }

  /**
   * Tests that run returns OK when properly configured.
   *
   * @covers ::run
   */
  public function testRunReturnsOkWhenConfigured(): void {
    $check = $this->createCheck([
      'platformCdn' => [
        'vendor' => 'fastly',
        'config' => 'settings',
        'service_id' => 'test-service',
        'token' => 'test-token',
      ],
    ]);
    $severity = $check->run();

    $this->assertEquals(DiagnosticCheckInterface::SEVERITY_OK, $severity);
  }

  /**
   * Tests that run returns error without configuration.
   *
   * @covers ::run
   */
  public function testRunReturnsErrorWithoutConfig(): void {
    $check = $this->createCheck();
    $severity = $check->run();

    $this->assertEquals(DiagnosticCheckInterface::SEVERITY_ERROR, $severity);
  }

  /**
   * Tests that run returns error for unknown vendor.
   *
   * @covers ::run
   */
  public function testRunReturnsErrorForUnknownVendor(): void {
    $check = $this->createCheck([
      'platformCdn' => [
        'vendor' => 'unknown_cdn',
        'config' => 'settings',
      ],
    ]);
    $severity = $check->run();

    $this->assertEquals(DiagnosticCheckInterface::SEVERITY_ERROR, $severity);
  }

  /**
   * Tests that run returns error for malformed config.
   *
   * @covers ::run
   */
  public function testRunReturnsErrorForInvalidConfig(): void {
    $check = $this->createCheck([
      'platformCdn' => [
        'vendor' => 'fastly',
        'config' => 'settings',
      ],
    ]);
    $severity = $check->run();

    $this->assertEquals(DiagnosticCheckInterface::SEVERITY_ERROR, $severity);
  }

  /**
   * Tests that run returns error for runtime error.
   *
   * @covers ::run
   */
  public function testRunReturnsErrorForRuntimeError(): void {
    \Drupal::cache()->set('acquia_purge_cdn_runtime_error', 'Test error', time() + 300);

    $check = $this->createCheck([
      'platformCdn' => [
        'vendor' => 'fastly',
        'config' => 'settings',
        'service_id' => 'test-service',
        'token' => 'test-token',
      ],
    ]);
    $severity = $check->run();

    $this->assertEquals(DiagnosticCheckInterface::SEVERITY_ERROR, $severity);

    \Drupal::cache()->delete('acquia_purge_cdn_runtime_error');
  }

  /**
   * Tests OK status when no runtime error.
   *
   * @covers ::run
   */
  public function testRunOkWithoutRuntimeError(): void {
    \Drupal::cache()->delete('acquia_purge_cdn_runtime_error');

    $check = $this->createCheck([
      'platformCdn' => [
        'vendor' => 'fastly',
        'config' => 'settings',
        'service_id' => 'test-service',
        'token' => 'test-token',
      ],
    ]);
    $severity = $check->run();

    $this->assertEquals(DiagnosticCheckInterface::SEVERITY_OK, $severity);
  }

}
