<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Testing of the External Links administration interface and functionality.
 */
#[Group('Extlink Admin Tests')]
#[RunTestsInSeparateProcesses]
class ExtlinkAdminTest extends ExtlinkTestBase {

  use StringTranslationTrait;

  /**
   * Test access to the admin pages.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testAdminAccess(): void {
    $this->drupalLogin($this->normalUser);
    $this->drupalGet(self::EXTLINK_ADMIN_PATH);
    $this->assertSession()->pageTextContains($this->t('Access denied'));

    $this->drupalLogin($this->adminUser);
    $this->drupalGet(self::EXTLINK_ADMIN_PATH);
    $this->assertSession()->pageTextNotContains($this->t('Access denied'));
  }

  /**
   * Checks to see if external links are disabled on admin routes.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testExtlinkDisabledOnAdminRoutes(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(self::EXTLINK_ADMIN_PATH);
    $this->assertSession()->checkboxNotChecked('extlink_exclude_admin_routes');
    $this->assertSession()->responseContains('/js/extlink.js');

    // Disable Extlink on admin routes.
    $this->drupalGet(self::EXTLINK_ADMIN_PATH);
    $this->submitForm(['extlink_exclude_admin_routes' => TRUE], 'Save configuration');
    $this->assertSession()->responseNotContains('/js/extlink.js');
  }

  /**
   * Tests orphan text option only appears when icon placement is append.
   */
  public function testOrphanVisibilityState(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(self::EXTLINK_ADMIN_PATH);
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('extlink_icon_placement', 'append');
    $orphan_wrap_field = $assert->fieldExists('extlink_prevent_orphan_text_like');
    $this->assertTrue($orphan_wrap_field->isVisible());
    $page->selectFieldOption('extlink_icon_placement', 'prepend');
    $orphan_wrap_field = $assert->fieldExists('extlink_prevent_orphan_text_like');
    $this->assertFalse($orphan_wrap_field->isVisible());
  }

}
