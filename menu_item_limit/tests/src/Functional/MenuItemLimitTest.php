<?php

namespace Drupal\Tests\menu_ui\Functional;

use Drupal\Core\Url;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;
use Drupal\system\MenuStorage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\menu_ui\Traits\MenuUiTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Check limiting menu items for menus.
 *
 * @group menu_item_limit
 */
class MenuItemLimitTest extends BrowserTestBase {

  use MenuUiTrait;
  use StringTranslationTrait;
  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'contextual',
    'help',
    'menu_link_content',
    'menu_ui',
    'menu_item_limit',
    'node',
    'path',
    'test_page_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with administration rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authenticatedUser;

  /**
   * Array of placed menu blocks keyed by block ID.
   *
   * @var array
   */
  protected $blockPlacements;

  /**
   * A test menu.
   *
   * @var \Drupal\system\Entity\Menu
   */
  protected $menu;

  /**
   * An array of test menu links.
   *
   * @var \Drupal\menu_link_content\MenuLinkContentInterface[]
   */
  protected $items;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create users.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer menu',
    ]);
    $this->authenticatedUser = $this->drupalCreateUser([]);

  }

  /**
   * Test creating a menu and adding menu items to a menu.
   *
   * Make sure that adding items beyond the item limit of a menu fails.
   */
  public function testMenuItemLimit() {
    // Log in the user.
    $this->drupalLogin($this->adminUser);
    $this->items = [];

    $this->menu = $this->addCustomMenu();
    $menu_name = $this->menu->id();
    $this->menu = $this->setItemLimit();
    $this->addMenuLinkSuccess('', '<front>', $menu_name, TRUE);
    $this->addMenuLinkSuccess('', 'http://example.com', $menu_name, TRUE);
    $this->addMenuLinkItemOverLimit('', 'http://example.com/a', $menu_name, TRUE);
  }

  /**
   * Creates a custom menu.
   *
   * @return \Drupal\system\Entity\Menu
   *   The custom menu that has been created.
   */
  public function addCustomMenu() {
    // Try adding a menu using a menu_name that is too long.
    $this->drupalGet('admin/structure/menu/add');
    $menu_name = strtolower($this->randomMachineName(MenuStorage::MAX_ID_LENGTH));
    $label = $this->randomMachineName(16);
    $edit = [
      'id' => $menu_name,
      'description' => '',
      'label' => $label,
    ];
    $this->drupalGet('admin/structure/menu/add');
    $this->submitForm($edit, $this->t('Save'));
    return Menu::load($menu_name);
  }

  /**
   * Set the item limit of a menu.
   *
   * @return \Drupal\system\Entity\Menu
   *   The custom menu that has been created.
   */
  public function setItemLimit() {
    $menu = $this->menu;
    $this->drupalGet(Url::fromRoute('entity.menu.edit_form', ['menu' => $menu->id()]));
    $edit = [
      'id' => $menu->id(),
      'description' => '',
      'label' => $menu->label(),
      'menu_item_limit' => '2',
    ];
    $this->assertSession()->pageTextContains($this->t('Item Limitation'));
    $this->drupalGet('admin/structure/menu/manage/' . $menu->id());
    $this->submitForm($edit, $this->t('Save'));
    return Menu::load($menu->id());

  }

  /**
   * Adds a menu link using the UI and assume it succeeds.
   *
   * @param string $parent
   *   Optional parent menu link id.
   * @param string $path
   *   The path to enter on the form. Defaults to the front page.
   * @param string $menu_name
   *   Menu name. Defaults to 'tools'.
   * @param bool $expanded
   *   Whether or not this menu link is expanded. Setting this to TRUE should
   *   test whether it works when we do the authenticatedUser tests. Defaults
   *   to FALSE.
   * @param string $weight
   *   Menu weight. Defaults to 0.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   A menu link entity.
   */
  public function addMenuLinkSuccess($parent = '', $path = '/', $menu_name = 'tools', $expanded = FALSE, $weight = '0') {
    // View add menu link page.
    $this->drupalGet("admin/structure/menu/manage/$menu_name/add");
    $this->assertSession()->statusCodeEquals(200);

    $title = '!link_' . $this->randomMachineName(16);
    $edit = [
      'link[0][uri]' => $path,
      'title[0][value]' => $title,
      'description[0][value]' => '',
      'enabled[value]' => 1,
      'expanded[value]' => $expanded,
      'menu_parent' => $menu_name . ':' . $parent,
      'weight[0][value]' => $weight,
    ];

    // Add menu link.
    $this->submitForm($edit, $this->t('Save'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The menu link has been saved.');

    $menu_links = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['title' => $title]);

    $menu_link = reset($menu_links);
    $this->assertInstanceOf(MenuLinkContent::class, $menu_link);
    $this->assertMenuLink(
      [
        'menu_name' => $menu_name,
        'children' => [],
        'parent' => $parent,
      ],
      $menu_link->getPluginId());

    return $menu_link;
  }

  /**
   * Adds a menu link using the UI and assume it fails because of the limit.
   *
   * @param string $parent
   *   Optional parent menu link id.
   * @param string $path
   *   The path to enter on the form. Defaults to the front page.
   * @param string $menu_name
   *   Menu name. Defaults to 'tools'.
   * @param bool $expanded
   *   Whether or not this menu link is expanded. Setting this to TRUE should
   *   test whether it works when we do the authenticatedUser tests. Defaults
   *   to FALSE.
   * @param string $weight
   *   Menu weight. Defaults to 0.
   */
  public function addMenuLinkItemOverLimit($parent = '', $path = '/', $menu_name = 'tools', $expanded = FALSE, $weight = '0') {
    // View add menu link page.
    $this->drupalGet("admin/structure/menu/manage/$menu_name/add");
    $this->assertSession()->statusCodeEquals(200);

    $title = '!link_' . $this->randomMachineName(16);
    $edit = [
      'link[0][uri]' => $path,
      'title[0][value]' => $title,
      'description[0][value]' => '',
      'enabled[value]' => 1,
      'expanded[value]' => $expanded,
      'menu_parent' => $menu_name . ':' . $parent,
      'weight[0][value]' => $weight,
    ];

    // Add menu link.
    $this->submitForm($edit, $this->t('Save'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->t('New link cannot be added because the menu item limit has been reached.'));
  }

}
