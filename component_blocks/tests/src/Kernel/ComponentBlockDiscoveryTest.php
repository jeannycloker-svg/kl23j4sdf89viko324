<?php

namespace Drupal\Tests\component_blocks\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Defines a class for testing component block discovery.
 *
 * @group component_blocks
 * @requires module components
 */
class ComponentBlockDiscoveryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'component_blocks',
    'components',
    'block',
    'component_blocks_test',
    'entity_test',
    'field',
    'ui_patterns',
    'ui_patterns_library',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Pre-warm the entity type cache so that hook_entity_type_alter fires in
    // a fully-initialised context. Without this, AbstractPatternsDeriver in
    // UI Patterns triggers EntityTypeManager::getDefinitions() via
    // TypedDataManager for the first time inside the block discovery chain,
    // causing the entity_test hook to receive an incomplete $entity_types on
    // Drupal 11 (re-entrant hook scenario).
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
  }

  /**
   * Tests discovery.
   */
  public function testDiscoveryAndDefaults() {
    $blocks = \Drupal::service('plugin.manager.block')->getDefinitions();
    $this->assertArrayHasKey('component_blocks:entity_test:test_component', $blocks);
  }

}
