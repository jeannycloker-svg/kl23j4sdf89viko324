<?php

namespace Drupal\Tests\menu_tree\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\user\UserInterface;

/**
 * Test 'menu_tree' functionality.
 *
 * @group menu_tree
 */
class MenuTreeNodeFormTest extends WebDriverTestBase {

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
   * Tests adding a menu link when creating a node.
   */
  public function testMenuTreeNodeForm() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();

    // Add a 'Home' link to the main menu.
    $this->drupalGet('admin/structure/menu/manage/main/add');
    $session->getPage()->fillField('title[0][value]', 'Home');
    $session->getPage()->fillField('link[0][uri]', '<front>');
    $session->getPage()->checkField('enabled[value]');
    $session->getPage()->pressButton('Save');

    // Verify that the 'menu_ui' form has been altered and the 'menu_tree'
    // widget exists.
    $this->drupalGet('node/add/page');
    $node_title = $this->randomMachineName();
    $session->getPage()->fillField('title[0][value]', $node_title);
    $session->getPage()->fillField('body[0][value]', $this->randomString());
    $session->getPage()->findLink('Menu settings')->click();
    $session->getPage()->checkField('menu[enabled]');
    $web_assert->elementExists('css', '.menu-tree[data-component-id="menu_tree:menu-tree"]');
    $web_assert->fieldValueEquals('menu[title]', $node_title);
    $web_assert->hiddenFieldValueEquals('menu[menu_parent]', 'main:');
    $web_assert->hiddenFieldExists('menu[weight]');
    $web_assert->hiddenFieldExists('menu[prev_sibling]');
    $web_assert->hiddenFieldExists('menu[next_sibling]');
    $session->getPage()->pressButton('Save');

    // Verify the new menu link is positioned directly after the 'Home' link.
    $this->drupalGet('node/1');
    $html = $session->getPage()->getHtml();
    $web_assert->linkExists($node_title);
    $links = $web_assert->elementExists('css', 'nav')->findAll('css', 'a');
    /** @var \Behat\Mink\Element\NodeElement $last_link */
    $last_link = array_pop($links);
    $web_assert->assert(($last_link->getAttribute('data-drupal-link-system-path') == 'node/1'), 'Link is positioned after the Home link.');
  }

}
