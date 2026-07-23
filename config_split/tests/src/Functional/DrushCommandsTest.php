<?php

declare(strict_types=1);

namespace Drupal\Tests\config_split\Functional;

use Drupal\config_split\Entity\ConfigSplitEntity;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Site\Settings;
use Drupal\Tests\BrowserTestBase;
use Drush\TestTraits\DrushTestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the drush command hook.
 */
#[Group('config_split')]
class DrushCommandsTest extends BrowserTestBase {
  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_split'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test that the drush export hook works.
   */
  public function testConfigExport(): void {
    $syncDir = Settings::get('config_sync_directory');
    $splitDir = dirname($syncDir) . '/split';
    mkdir($splitDir);

    ConfigSplitEntity::create([
      'id' => 'test',
      'label' => 'Test',
      'storage' => 'folder',
      'status' => TRUE,
      'folder' => $splitDir,
      'stackable' => FALSE,
      'weight' => 0,
      'complete_list' => ['system.menu.footer'],
      'partial_list' => [],
      'no_patching' => FALSE,
    ])->save();

    $this->drush('config:export');

    $syncStorage = new FileStorage($syncDir);
    $splitStorage = new FileStorage($splitDir);

    self::assertContains('system.menu.footer', $splitStorage->listAll());
    self::assertNotContains('system.menu.footer', $syncStorage->listAll());
    self::assertNotEmpty($syncStorage->listAll());
  }

}
