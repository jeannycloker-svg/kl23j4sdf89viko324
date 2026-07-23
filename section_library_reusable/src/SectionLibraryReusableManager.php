<?php

namespace Drupal\section_library_reusable;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\layout_builder\OverridesSectionStorageInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\section_library\DeepCloningTrait;

/**
 * Moving sections to resuable block.
 */
class SectionLibraryReusableManager implements SectionLibraryReusableManagerInterface {

  use LayoutEntityHelperTrait;
  use DeepCloningTrait;

  /**
   * The UUID generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct the SectionLibraryReusableManager object.
   *
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The uuid generator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(UuidInterface $uuid, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->uuidGenerator = $uuid;
  }

  /**
   * Returns block id referenced in sections.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   Block id lists.
   */
  public function getInlineBlockIdsForEntity(EntityInterface $entity) {
    $storage = $this->getSectionStorageForEntity($entity);
    $block_storage = $this->entityTypeManager->getStorage('block_content');
    $block_ids = [];
    foreach ($storage->getSections() as $section) {
      $components = $section->getComponents();
      foreach ($components as $component) {
        $component_ary = $component->toArray();
        $revision_id = $component_ary['configuration']['block_revision_id'] ?? NULL;

        if ($revision_id !== NULL) {
          $block = $block_storage->loadRevision($revision_id);
          $block_ids[] = $block->id();
        }
      }
    }
    return $block_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function makeSectionReusable(OverridesSectionStorageInterface $section_storage, $delta, $label) {
    $current_section = $section_storage->getSection($delta);
    $deep_cloned_section = $this->deepCloneSection($current_section);
    $reusable_block = BlockContent::create([
      'type' => 'section_library_reusable',
      'info' => $label,
    ]);
    $reusable_block->save();
    $reusable_block->layout_builder__layout = $deep_cloned_section;
    $reusable_block->save();

    $section_wrapper = new Section(
      'section_library_reusable_wrapper', [],
      [
        $reusable_block->uuid() => new SectionComponent(
          $reusable_block->uuid(),
          'content',
          [
            'id' => 'block_content:' . $reusable_block->uuid(),
            'label' => $reusable_block->label(),
            'provider' => 'block_content',
            'status' => TRUE,
          ]
        ),
      ]
    );
    $section_storage->removeSection($delta);
    $section_storage->insertSection($delta, $section_wrapper);
  }

}
