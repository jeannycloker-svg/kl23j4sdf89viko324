<?php

declare(strict_types=1);

namespace Drupal\Tests\layoutbuilder_search_api\Functional;

use Drupal\layoutbuilder_search_api\LayoutbuilderSearchApiManager;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Basic module testing.
 */
#[Group('layoutbuilder_search_api')]
#[RunTestsInSeparateProcesses]
final class InstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['layoutbuilder_search_api'];

  /**
   * Test basic module setup.
   */
  public function testSomething(): void {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertInstanceOf(LayoutbuilderSearchApiManager::class, \Drupal::service('layoutbuilder_search_api.manager'));
    $this->assertArrayHasKey('layout_builder_references', \Drupal::service('plugin.manager.search_api.processor')->getDefinitions());
  }

}
