<?php

namespace Drupal\Tests\config_split\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\config_filter\Kernel\ConfigStorageTestTrait;

/**
 * Test explicit 'config_split_priorities' handling.
 *
 * @group config_split
 */
class ExplicitPriorityTest extends KernelTestBase {

  use ConfigStorageTestTrait;
  use SplitTestTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'config_split',
  ];

  /**
   * The event subscriber.
   *
   * @var \Drupal\config_split\EventSubscriber\SplitImportExportSubscriber
   */
  protected $subscriber;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->subscriber = $this->container->get('config_split.event_subscriber');
  }

  /**
   * {@inheritdoc}
   */
  protected function bootKernel() {
    // Set explicit priorities for kernel boot.
    $priorities = [
      'priority' => -100,
    ];
    $this->setSetting('config_split_priorities', $priorities);

    parent::bootKernel();
  }

  /**
   * Test that explicit priorities are excluded from default priority.
   */
  public function testPriority() {
    // Create splits with one matching priority setting key.
    $this->createSplitConfig('normal', [
      'storage' => 'folder',
    ]);
    $this->createSplitConfig('priority', [
      'storage' => 'folder',
    ]);

    // Test event subscriptions.
    $events = $this->subscriber->getSubscribedEvents();
    self::assertEquals([
      'config.transform.export' => [
        ['exportDefaultPriority', 0],
        ['_exportExplicit_priority', -100],
      ],
      'config.transform.import' => [
        ['importDefaultPriority', 0],
        ['_importExplicit_priority', 100],
      ],
    ], $events);

    // Test default priority list (in protected method listing splits).
    $class = new \ReflectionClass($this->subscriber);
    $method = $class->getMethod('getDefaultPrioritySplitConfigs');
    $splits = $method->invoke($this->subscriber);
    self::assertEquals(['config_split.config_split.normal'], array_keys($splits));
  }

  /**
   * Test that explicit priorities allows adding indirect imports.
   */
  public function testIndirectImport() {
    /*
     * This behavior arises from explicit priorities invoking
     * ConfigSplitManager::importTransform() in a preceding event, resulting in
     * the subsequent ConfigSplitManager::listAll() seeing the indirect split.
     */

    // Prepare sync storage.
    $sync = $this->getSyncFileStorage();
    $this->copyConfig($this->getActiveStorage(), $sync);

    // Create priority split to define another split.
    $sync->write('config_split.config_split.priority',
      [
        'uuid' => 'a65fadbf-8634-4515-ac31-e1516dab8b4e',
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [],
        'id' => 'priority',
        'label' => 'Priority',
        'description' => 'This adds an indirect split',
        'weight' => 0,
        'storage' => 'collection',
        'folder' => '',
        'module' => [],
        'theme' => [],
        'complete_list' => ['config_split.config_split.indirect'],
        'partial_list' => [],
      ]
    );
    $sync->createCollection('split.priority')->write('config_split.config_split.indirect',
      [
        'uuid' => '2ddf1883-b8c7-4cd8-9b99-d74b150012cd',
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [],
        'id' => 'indirect',
        'label' => 'Indirect',
        'description' => 'This indirect split defines a new config item to import',
        'weight' => 0,
        'storage' => 'collection',
        'folder' => '',
        'module' => [],
        'theme' => [],
        'complete_list' => ['core.date_format.dummy'],
        'partial_list' => [],
      ]
    );
    $sync->createCollection('split.indirect')->write('dummy', []);

    // Check indirectly included dummy item imported.
    $import = $this->getImportStorage();
    self::assertTrue($import->exists('dummy'));
  }

}
