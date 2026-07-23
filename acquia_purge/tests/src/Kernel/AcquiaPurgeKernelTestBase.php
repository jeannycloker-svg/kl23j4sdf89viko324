<?php

namespace Drupal\Tests\acquia_purge\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\acquia_purge\Traits\AcquiaPurgePlatformInfoTrait;
use Drupal\Tests\acquia_purge\Traits\AcquiaPurgeTestHttpClientTrait;

/**
 * Base class for Acquia Purge kernel tests.
 */
abstract class AcquiaPurgeKernelTestBase extends KernelTestBase {

  use AcquiaPurgeTestHttpClientTrait;
  use AcquiaPurgePlatformInfoTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'purge',
    'purge_queuer_coretags',
    'acquia_purge',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
  }

}
