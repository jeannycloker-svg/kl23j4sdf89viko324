<?php

namespace Drupal\Tests\section_library_reusable\Kernel;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests for SectionLibraryReusableManager.
 *
 * @group section_library_reusable
 */
class SectionLibraryReusableManagerTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_discovery',
    'layout_builder',
    'entity_test',
    'field',
    'text',
    'system',
    'user',
    'section_library_reusable',
    'section_library',
    'block_content',
    'language',
  ];

  /**
   * The section library reusable manager.
   *
   * @var \Drupal\section_library_reusable\SectionLibraryReusableManagerInterface
   */
  private $sectionLibraryReusableManager;

  /**
   * The plugin.
   *
   * @var \Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpCurrentUser();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('block_content');
    $this->installConfig('block_content');
    $this->installConfig('section_library_reusable');

    $definition = $this->container->get(
      'plugin.manager.layout_builder.section_storage'
    )->getDefinition('overrides');
    $this->plugin = OverridesSectionStorage::create(
      $this->container,
      [],
      'overrides',
      $definition
    );

    $bundle = BlockContentType::create(
      [
        'id' => 'basic',
        'label' => 'Basic block',
        'revision' => 1,
      ]
    );
    $bundle->save();
    block_content_add_body_field($bundle->id());
    $this->sectionLibraryReusableManager = \Drupal::service(
      'section_library_reusable.manager'
    );
  }

  /**
   * Test testGetMediaQueries.
   */
  public function testMakeReusable() {
    $display = LayoutBuilderEntityViewDisplay::create(
      [
        'targetEntityType' => 'entity_test',
        'bundle' => 'entity_test',
        'mode' => 'default',
        'status' => TRUE,
      ]
    );
    $display
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $entity = EntityTest::create();
    $block_one = BlockContent::create(['type' => 'basic']);
    $block_one->save();
    $section_one = new Section(
      'layout_onecol', [],
      [
        $block_one->uuid() => new SectionComponent(
          $block_one->uuid(),
          'content',
          [
            'id' => 'block_content:' . $block_one->uuid(),
            'label' => 'Block 1',
            'provider' => 'block_content',
            'status' => TRUE,
          ]
        ),
      ]
    );

    $entity->set(OverridesSectionStorage::FIELD_NAME, [$section_one]);
    $entity->save();
    $entity = EntityTest::load($entity->id());
    $result = $this->plugin->deriveContextsFromRoute(
      'entity_test.1',
      [],
      '',
      []
    );
    $this->assertSame(['entity', 'view_mode'], array_keys($result));
    $this->assertSame($entity, $result['entity']->getContextValue());
    $this->assertSame('default', $result['view_mode']->getContextValue());
    $this->plugin->setContext('entity', EntityContext::fromEntity($entity));
    $this->sectionLibraryReusableManager->makeSectionReusable($this->plugin, 0, $block_one->label());
  }

}
