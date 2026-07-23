<?php

declare(strict_types=1);

namespace Drupal\Tests\admin_toolbar_tools\Functional;

use Drupal\Tests\admin_toolbar\Traits\AdminToolbarHelperTestTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the Admin Toolbar Tools integration with the Automatic Updates module.
 *
 * Install the Automatic Updates module and verify that the Admin Toolbar Tools
 * links are correctly displayed in the admin toolbar, in the expected order.
 *
 * @see \Drupal\admin_toolbar_tools\Plugin\Derivative\ExtraLinks
 *
 * @group admin_toolbar
 * @group admin_toolbar_tools
 */
class AdminToolbarToolsAutomaticUpdatesTest extends BrowserTestBase {

  use AdminToolbarHelperTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'admin_toolbar',
    'admin_toolbar_tools',
  ];

  /**
   * A user with access to the Admin Toolbar Tools admin menu links.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   *
   * Conditionally install the Automatic Updates module based on whether a
   * compatible version could be found by the composer job, since versions below
   * 4 are not supported anymore (requires 11.2 or above).
   * Create an admin user with the required permissions to access the Automatic
   * Updates routes.
   *
   * @see composer.json
   */
  protected function setUp(): void {
    parent::setUp();

    /* Custom configuration for Automatic Updates */

    // Skip the test if the Automatic Updates module does not exist, because no
    // compatible version was found.
    if (!\Drupal::service('extension.list.module')->exists('automatic_updates')) {
      $this->markTestSkipped('The Automatic Updates module does not exist in the file system.');
    }
    // Install the Automatic Updates module to test the integration.
    \Drupal::service('module_installer')->install(['automatic_updates']);

    /* Create an admin user */

    $permissions = [
      'access toolbar',
      // Required to access the Automatic Updates routes.
      'administer software updates',
      // Required to access routes under 'Extend'.
      'administer modules',
      // Required to access routes under 'Appearance'.
      'administer themes',
      // Required to access the reports pages.
      'access site reports',
      'administer site configuration',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
  }

  /**
   * Test Admin Toolbar Tools adds Automatic Updates links.
   *
   * Check the 'Update' links are displayed in the expected order under
   * 'Extend', 'Appearance', and 'Reports'.
   */
  public function testAdminToolbarToolsAutomaticUpdatesExtraLinks(): void {
    // Log in as an admin user to test the admin toolbar links under 'Extend',
    // 'Appearance', and 'Reports'.
    $this->drupalLogin($this->adminUser);

    // Test the custom extra links provided by the module that integrated with
    // the Automatic Updates module.
    $custom_extra_links = [
    // Verify that the 'Appearance' link exists and is displayed second after
    // the 'Tools' link added by default by Admin Toolbar Tools.
      [
        'url' => 'admin/appearance',
        'text' => 'Appearance',
        'position' => 2,
        'css_classes' => 'toolbar-icon-system-themes-page',
      ],
      // Verify that the 'Update' link exists in the expected order.
      [
        'url' => 'admin/appearance/update',
        'text' => 'Update',
        'position' => 2,
      ],
      // Verify that the 'Extend' link exists and is displayed third.
      [
        'url' => 'admin/modules',
        'text' => 'Extend',
        'position' => 3,
        'css_classes' => 'toolbar-icon-system-modules-list',
      ],
      // Verify that the 'Update' link exists in the expected order.
      [
        'url' => 'admin/modules/update',
        'text' => 'Update',
        'position' => 2,
      ],
      // Verify that the 'Reports' link exists and is displayed fourth.
      [
        'url' => 'admin/reports',
        'text' => 'Reports',
        'position' => 4,
        'css_classes' => 'toolbar-icon-system-admin-reports',
      ],
      // Verify that the 'Update' link exists in the expected order.
      [
        'url' => 'admin/reports/updates',
        'text' => 'Available updates',
        'position' => 2,
      ],
      [
        'url' => 'admin/reports/updates/update',
        'text' => 'Update',
        'position' => 1,
      ],
    ];

    // Check all the custom extra links are found in the admin toolbar menu with
    // expected text, URL, position and CSS classes.
    foreach ($custom_extra_links as $link) {
      $this->assertAdminToolbarMenuLinkExists($link['url'], $link['text'], $link['position'], $link['css_classes'] ?? '');
    }
  }

}
