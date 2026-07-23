<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_tree\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Test 'menu_tree' functionality.
 *
 * @group menu_tree
 */
class MenuTreeNodeTypeFormTest extends BrowserTestBase {

  /**
   * The user performing the tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $editor;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'link',
    'menu_link_content',
    'menu_ui',
    'menu_tree',
    'menu_tree_test',
    'node',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('system_menu_block:main');
    $this->drupalPlaceBlock('page_title_block');

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $this->editor = $this->drupalCreateUser([
      'access administration pages',
      'administer content types',
      'administer menu',
    ]);
    $this->drupalLogin($this->editor);
  }

  /**
   * Tests enabling the 'menu_tree' widget.
   */
  public function testMenuTreeNodeTypeFormSettings(): void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Assert the description of the 'Use tree widget for parent link' checkbox
    // field.
    $this->drupalGet('admin/structure/types/manage/page');
    $assert->fieldExists('edit-use-tree-widget')->setValue('1');
    $page->pressButton('Save');
    $assert->pageTextContains('The content type Basic page has been updated.');
  }

}
