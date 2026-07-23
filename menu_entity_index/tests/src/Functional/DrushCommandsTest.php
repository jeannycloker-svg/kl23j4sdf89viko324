<?php

namespace Drupal\Tests\menu_entity_index\Functional;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\menu_entity_index\Traits\MenuEntityIndexTestTrait;
use Drush\TestTraits\DrushTestTrait;
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
class DrushCommandsTest extends BrowserTestBase {
  use DrushTestTrait;
  use MenuEntityIndexTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_entity_index',
    'menu_link_content',
    'menu_ui',
    'menu_test',
    'node',
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
      'administer menu',
      'access administration pages',
      'administer menu_entity_index',
    ]);

    // Create a node type.
    \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->create([
        'type' => 'page',
        'name' => 'Page',
      ])
      ->save();
  }

  /**
   * Tests rebuild index Drush command.
   */
  public function testRebuildIndex(): void {
    $this->drush('mei-r');
    self::assertStringContainsString('No menus setup for tracking.', $this->getOutput());

    $this->drupalLogin($this->users['admin_user']);

    // Configure module to track content in main menu.
    $this->reconfigure([
      'menus[main]' => 'main',
      'entity_types[node]' => 'node',
    ]);

    $this->drush('menu-entity-index:rebuild-index');
    self::assertStringContainsString('Completed scanning of menu links.', $this->getErrorOutput());
    $this->drush('mei-r');
    self::assertStringContainsString('Completed scanning of menu links.', $this->getErrorOutput());
    $this->drush('mei-r', ['main']);
    self::assertStringContainsString('Completed scanning of menu links.', $this->getErrorOutput());
    $this->drush('mei-r', ['footer']);
    self::assertStringContainsString('Menu footer is not a valid tracked menu.', $this->getOutput());
    self::assertStringNotContainsString('Completed scanning of menu links.', $this->getErrorOutput());

    $this->assertEmptyTable();

    // Create a published node.
    $node = \Drupal::entityTypeManager()->getStorage('node')
      ->create([
        'type' => 'page',
        'title' => $this->randomMachineName(),
        'status' => 1,
      ]);
    $node->save();
    self::assertInstanceOf(Node::class, $node);

    // Create menu link.
    $menu_link = $this->addMenuLink($node);
    $this->assertTableRow($menu_link, $node);

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
    $this->drush('mei-r');
    self::assertStringContainsString('Completed scanning of menu links.', $this->getErrorOutput());

    // Assert correct data after rebuild.
    $this->assertTableRow($menu_link, $node);

    // Assert bogus entry removed after rebuild.
    $row_count = Database::getConnection()
      ->select('menu_entity_index')
      ->condition('level', 99)
      ->countQuery()
      ->execute()
      ->fetchField();
    self::assertSame('0', $row_count);
  }

}
