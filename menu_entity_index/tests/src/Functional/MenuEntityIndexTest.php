<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_entity_index\Functional;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\menu_entity_index\Traits\MenuEntityIndexTestTrait;
use Drupal\node\Entity\Node;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Provides functional tests for Menu Entity Index module.
 *
 * @group menu_entity_index
 */
#[Group('menu_entity_index')]
#[RunTestsInSeparateProcesses]
class MenuEntityIndexTest extends BrowserTestBase {
  use MenuEntityIndexTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'language_test',
    'locale',
    'locale_test',
    'menu_entity_index',
    'menu_link_content',
    'menu_ui',
    'menu_test',
    'node',
    'test_menu_entity_index',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Users created during set-up.
   *
   * @var \Drupal\user\Entity\User[]
   */
  protected $users;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create users.
    $this->users['admin_user'] = $this->drupalCreateUser([
      'administer languages',
      'administer menu',
      'access administration pages',
      'administer menu_entity_index',
      'view menu_entity_index form field',
      'bypass node access',
    ]);
    $this->users['admin_nodes'] = $this->drupalCreateUser([
      'bypass node access',
    ]);

    // Create a node type.
    \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->create([
        'type' => 'page',
        'name' => 'Page',
      ])
      ->save();

    // Set up default form displays.
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'page')
      ->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('user', 'user')
      ->save();
  }

  /**
   * Tests tracker and form widget provided by Menu Entity Index module.
   */
  public function testTracker(): void {
    $this->drupalLogin($this->users['admin_user']);

    // Add a language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalGet('/admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => '1'];
    $this->drupalGet('/admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    // Enable content translation for menu links and nodes.
    \Drupal::service('content_translation.manager')->setEnabled('menu_link_content', 'menu_link_content', TRUE);
    \Drupal::service('content_translation.manager')->setEnabled('node', 'page', TRUE);

    // Front page is accessible.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);

    // Menu overview is accessible.
    $this->drupalGet('/admin/structure/menu/manage/main');
    $this->assertSession()->statusCodeEquals(200);

    // Create a published node.
    $node = \Drupal::entityTypeManager()->getStorage('node')
      ->create([
        'type' => 'page',
        'title' => $this->randomMachineName(),
        'status' => 1,
      ]);
    $node->save();
    self::assertInstanceOf(Node::class, $node);

    // Create a menu link.
    $menu_link = $this->addMenuLink($node);
    $this->assertEmptyTable();

    // Edit menu link.
    $this->editMenuLink($menu_link);
    $this->assertEmptyTable();

    // Delete menu link.
    $this->deleteMenuLink($menu_link);
    $this->assertEmptyTable();

    // Configure module to track content in main menu.
    $this->reconfigure([
      'menus[main]' => 'main',
      'entity_types[node]' => 'node',
    ]);

    // Add form widget to page nodes.
    $this->configureFormWidget('node', 'page', TRUE);

    // Create menu link.
    $menu_link = $this->addMenuLink($node);
    $this->assertTableRow($menu_link, $node);
    $this->assertFormWidget($menu_link, $node);
    $this->assertViewResult($menu_link, $node);

    // Edit menu link.
    $this->editMenuLink($menu_link);
    $this->assertTableRow($menu_link, $node);
    $this->assertFormWidget($menu_link, $node);
    $this->assertViewResult($menu_link, $node);

    // Delete menu link.
    $this->deleteMenuLink($menu_link);
    $this->assertEmptyTable();
    $this->assertEmptyFormWidget($menu_link, $node);
    $this->assertEmptyViewResult($node);

    // Add parent menu link.
    $parent_link = $this->addMenuLink();
    $this->assertEmptyTable();
    $this->assertEmptyFormWidget($parent_link, $node);
    $this->assertEmptyViewResult($node);

    // Add child menu link.
    $child_link = $this->addMenuLink($node, $parent_link);
    $this->assertTableRow($child_link, $node, $parent_link);
    $this->assertFormWidget($child_link, $node, $parent_link);
    $this->assertViewResult($child_link, $node, $parent_link);

    // Move child menu link back to root level.
    $child_link = $this->editMenuLink($child_link, [
      'menu_parent' => $child_link->getMenuName() . ':',
    ]);
    $this->assertTableRow($child_link, $node);
    $this->assertFormWidget($child_link, $node);
    $this->assertViewResult($child_link, $node);

    // Add french translation.
    $child_link->addTranslation('fr', [
      'title' => 'FR ' . $child_link->label(),
    ])->save();
    $translated_link = $child_link->getTranslation('fr');
    $this->assertTableRow($child_link, $node);
    $this->assertFormWidget($child_link, $node, NULL, 2);
    $this->assertTableRow($translated_link, $node);
    $this->assertFormWidget($translated_link, $node, NULL, 2);

    // Create a bogus entry in database table.
    Database::getConnection()
      ->insert('menu_entity_index')
      ->fields([
        'menu_name' => 'main',
        'level' => 99,
        'entity_type' => 'node',
        'entity_subtype' => 'page',
        'entity_id' => '99',
        'entity_uuid' => 'abc',
        'parent_type' => '',
        'parent_id' => NULL,
        'parent_uuid' => '',
        'langcode' => 'en',
        'target_type' => 'node',
        'target_subtype' => 'page',
        'target_id' => '99',
        'target_uuid' => 'abc',
        'target_langcode' => 'en',
      ])
      ->execute();
    $row_count = Database::getConnection()
      ->select('menu_entity_index')
      ->condition('level', 99)
      ->countQuery()
      ->execute()
      ->fetchField();
    self::assertSame('1', $row_count);

    // Rebuild index.
    $this->reconfigure([], 'Rebuild index');

    // Assert correct data after rebuild.
    $this->assertTableRow($child_link, $node);
    $this->assertFormWidget($child_link, $node, NULL, 2);
    $this->assertTableRow($translated_link, $node);
    $this->assertFormWidget($translated_link, $node, NULL, 2);

    // Assert bogus entry removed after rebuild.
    $row_count = Database::getConnection()
      ->select('menu_entity_index')
      ->condition('level', 99)
      ->countQuery()
      ->execute()
      ->fetchField();
    self::assertSame('0', $row_count);

    // Add menu link to footer.
    $footer_link = $this->addMenuLink($node, NULL, 'footer');
    $this->assertTableRow($child_link, $node);
    $this->assertFormWidget($child_link, $node, NULL, 2);
    $this->assertTableRow($translated_link, $node);
    $this->assertFormWidget($translated_link, $node, NULL, 2);

    // Track all menus.
    $this->reconfigure([
      'all_menus' => TRUE,
      'menus[main]' => 'main',
      'entity_types[node]' => 'node',
    ]);
    $this->assertTableRow($child_link, $node);
    $this->assertFormWidget($child_link, $node, NULL, 3);
    $this->assertTableRow($translated_link, $node);
    $this->assertFormWidget($translated_link, $node, NULL, 3);
    $this->assertTableRow($footer_link, $node);
    $this->assertFormWidget($footer_link, $node, NULL, 3);

    // Untrack main.
    $this->reconfigure([
      'all_menus' => FALSE,
      'menus[footer]' => 'footer',
      'menus[main]' => '',
      'entity_types[node]' => 'node',
    ]);
    $this->assertTableRow($footer_link, $node);
    $this->assertFormWidget($footer_link, $node, NULL, 1);
    $this->assertViewResult($footer_link, $node);
    $row_count = Database::getConnection()
      ->select('menu_entity_index')
      ->condition('menu_name', 'main')
      ->countQuery()
      ->execute()
      ->fetchField();
    self::assertSame('0', $row_count);

    // Track entity type without bundles.
    $this->reconfigure([
      'entity_types[node]' => 'node',
      'entity_types[user]' => 'user',
    ]);
    $this->assertTableRow($footer_link, $node);
    $this->assertFormWidget($footer_link, $node, NULL, 1);
    $this->assertViewResult($footer_link, $node);

    // Add form widget to users.
    $this->configureFormWidget('user', 'user', TRUE);

    // Test tracker and form widget for an entity type without bundles.
    $user_link = $this->addMenuLink($this->users['admin_user'], NULL, 'footer');
    $this->assertTableRow($user_link, $this->users['admin_user']);
    // @todo For users, the form operation is "default", not "edit", so we don't
    // currently show the form widget in the entity form, even if configured.
    // $this->assertFormWidget($user_link, $this->users['admin_user'], NULL, 1);
    $this->assertViewResult($user_link, $this->users['admin_user']);

    // Untrack entity type user.
    $this->reconfigure([
      'entity_types[node]' => 'node',
      'entity_types[user]' => '',
    ]);
    $this->assertTableRow($footer_link, $node);
    $this->assertFormWidget($footer_link, $node, NULL, 1);
    $this->assertViewResult($footer_link, $node);
    $this->assertEmptyViewResult($this->users['admin_user']);
    $row_count = Database::getConnection()
      ->select('menu_entity_index')
      ->condition('target_type', 'user')
      ->countQuery()
      ->execute()
      ->fetchField();
    self::assertSame('0', $row_count);

    // Assert form widget not visible without view permission.
    $this->drupalLogin($this->users['admin_nodes']);
    $this->assertNoFormWidget($node);
  }

}
