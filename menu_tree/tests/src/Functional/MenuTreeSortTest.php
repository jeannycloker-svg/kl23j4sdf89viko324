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
class MenuTreeSortTest extends BrowserTestBase {

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
    $this->config('node.type.page')
      ->set('third_party_settings.menu_ui.available_menus', ['main'])
      ->set('third_party_settings.menu_ui.parent', 'main:')
      ->set('third_party_settings.menu_tree.use_tree_widget', TRUE)
      ->save();

    $this->editor = $this->drupalCreateUser([
      'access administration pages',
      'administer content types',
      'administer menu',
      'create page content',
      'edit any page content',
      'delete any page content',
    ]);
    $this->drupalLogin($this->editor);
  }

  /**
   * Tests enabling the 'menu_tree' widget.
   */
  public function testMenuTreeSort(): void {
    // Add a 'Home' link to the main menu.
    $this->drupalGet('admin/structure/menu/manage/main/add');
    $edit = [
      'title[0][value]' => 'Home',
      'link[0][uri]' => '<front>',
      'enabled[value]' => 1,
    ];
    $this->submitForm($edit, 'Save');

    // Create a node with a menu link.
    $this->drupalGet('node/add/page');
    $node_title = $this->randomMachineName();
    $edit = [
      'title[0][value]' => $node_title,
      'body[0][value]' => $this->randomString(),
      'menu[enabled]' => 1,
      'menu[title]' => $node_title,
    ];
    $this->submitForm($edit, 'Save');

    // Reorder the menu.
    $this->drupalGet('node/1/edit');
    $edit = [
      'menu[prev_sibling]' => '',
      'menu[next_sibling]' => $this->getSession()->getPage()->findButton('Home')->getAttribute('data-value'),
    ];
    $this->submitForm($edit, 'Save');

    // Verify the menu link position after reorder.
    $this->drupalGet('node/1');
    $this->assertSession()->elementExists('css', 'nav');
    $links = $this->getSession()->getPage()->findAll('css', 'nav a');
    /** @var \Behat\Mink\Element\NodeElement $first_link */
    $first_link = array_shift($links);
    $this->assertSession()->assert(($first_link->getAttribute('data-drupal-link-system-path') == 'node/1'), 'Link is positioned before the Home link.');
  }

}
