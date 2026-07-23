<?php

declare(strict_types=1);

namespace Drupal\Tests\easy_breadcrumb\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the installation, uninstallation, and reinstallation of the module.
 *
 * @group easy_breadcrumb
 * @group functional
 */
class EasyBreadcrumbInstallUninstallTest extends BrowserTestBase {

  /**
   * The default theme to use during testing.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [];

  /**
   * A test user with admin permissions.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a user with permission to administer modules.
    $this->adminUser = $this->createUser([
      'administer modules',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the installation, uninstallation, and reinstallation of the module.
   */
  public function testInstallUninstallReinstall(): void {
    // Step 1: Verify that the module is not installed initially.
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('easy_breadcrumb'), 'The easy_breadcrumb module is installed initially when it should not be.');

    // Step 2: Install the module.
    \Drupal::service('module_installer')->install(['easy_breadcrumb']);
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('easy_breadcrumb'), 'The easy_breadcrumb module failed to install.');

    // Step 3: Verify that the module's configuration exists.
    $config = \Drupal::service('config.factory')->getEditable('easy_breadcrumb.settings');
    $this->assertNotEmpty($config->getRawData(), 'The easy_breadcrumb configuration does not exist after installation.');

    // Step 4: Uninstall the module.
    \Drupal::service('module_installer')->uninstall(['easy_breadcrumb']);
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('easy_breadcrumb'), 'The easy_breadcrumb module failed to uninstall.');

    // Step 5: Verify that the module's configuration is removed.
    $config = \Drupal::service('config.factory')->getEditable('easy_breadcrumb.settings');
    $this->assertEmpty($config->getRawData(), 'The easy_breadcrumb configuration still exists after uninstallation.');

    // Step 6: Reinstall the module.
    \Drupal::service('module_installer')->install(['easy_breadcrumb']);
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('easy_breadcrumb'), 'The easy_breadcrumb module failed to reinstall.');

    // Step 7: Verify that the module's configuration exists again.
    $config = \Drupal::service('config.factory')->getEditable('easy_breadcrumb.settings');
    $this->assertNotEmpty($config->getRawData(), 'The easy_breadcrumb configuration does not exist after reinstallation.');
  }

}
