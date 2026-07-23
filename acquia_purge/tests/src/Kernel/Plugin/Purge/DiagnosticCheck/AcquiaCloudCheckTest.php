<?php

namespace Drupal\Tests\acquia_purge\Kernel\Plugin\Purge\DiagnosticCheck;

use Drupal\acquia_purge\Plugin\Purge\DiagnosticCheck\AcquiaCloudCheck;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\Tests\acquia_purge\Kernel\AcquiaPurgeKernelTestBase;

/**
 * Tests the AcquiaCloudCheck diagnostic check.
 *
 * @coversDefaultClass \Drupal\acquia_purge\Plugin\Purge\DiagnosticCheck\AcquiaCloudCheck
 * @group acquia_purge
 */
class AcquiaCloudCheckTest extends AcquiaPurgeKernelTestBase {

  /**
   * Creates an AcquiaCloudCheck instance with mocked dependencies.
   *
   * @param array $platformOverrides
   *   Platform info overrides.
   *
   * @return \Drupal\acquia_purge\Plugin\Purge\DiagnosticCheck\AcquiaCloudCheck
   *   The diagnostic check instance.
   */
  protected function createCheck(array $platformOverrides = []): AcquiaCloudCheck {
    $platformInfo = $this->createMockPlatformInfo($platformOverrides);
    $moduleExtensionList = $this->container->get('extension.list.module');

    return new AcquiaCloudCheck(
      $platformInfo,
      $moduleExtensionList,
      [],
      'acquia_purge_cloud_check',
      [
        'id' => 'acquia_purge_cloud_check',
        'title' => 'Acquia Cloud',
        'description' => 'Validates the Acquia Cloud configuration.',
        'dependent_queue_plugins' => [],
        'dependent_purger_plugins' => ['acquia_purge'],
      ]
    );
  }

  /**
   * Tests that run returns OK on Acquia Cloud.
   *
   * @covers ::run
   */
  public function testRunReturnsOkOnAcquiaCloud(): void {
    $check = $this->createCheck(['isAcquiaCloud' => TRUE]);
    $severity = $check->run();

    $this->assertEquals(DiagnosticCheckInterface::SEVERITY_OK, $severity);
  }

  /**
   * Tests that run returns info when not on Acquia Cloud.
   *
   * @covers ::run
   */
  public function testRunReturnsInfoOffAcquiaCloud(): void {
    $check = $this->createCheck(['isAcquiaCloud' => FALSE]);
    $severity = $check->run();

    $this->assertEquals(DiagnosticCheckInterface::SEVERITY_INFO, $severity);
  }

  /**
   * Tests that value contains module version.
   *
   * @covers ::run
   */
  public function testValueContainsModuleVersion(): void {
    $check = $this->createCheck(['isAcquiaCloud' => TRUE]);
    $check->run();

    $value = $check->getValue();
    $this->assertNotEmpty($value);
  }

  /**
   * Tests check with missing balancer addresses.
   *
   * @covers ::run
   */
  public function testRunWithMissingBalancers(): void {
    $check = $this->createCheck([
      'isAcquiaCloud' => FALSE,
      'balancerAddresses' => [],
    ]);
    $severity = $check->run();

    $this->assertEquals(DiagnosticCheckInterface::SEVERITY_INFO, $severity);
  }

}
