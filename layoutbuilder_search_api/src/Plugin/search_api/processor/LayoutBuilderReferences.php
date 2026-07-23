<?php

namespace Drupal\layoutbuilder_search_api\Plugin\search_api\processor;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\EntityProcessorProperty;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allows indexing of layout builder references.
 *
 * @SearchApiProcessor(
 *   id = "layout_builder_references",
 *   label = @Translation("Layout builder references"),
 *   description = @Translation("Allows indexing of entities used by layout builder."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 * )
 */
class LayoutBuilderReferences extends ProcessorPluginBase implements PluginFormInterface {
  use LayoutEntityHelperTrait;
  use PluginFormTrait;

  /**
   * Static cache for all entity references.
   *
   * @var array[][]|null
   */
  protected $references;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The block content helper.
   *
   * @var \Drupal\layoutbuilder_search_api\LayoutbuilderSearchApiManager
   */
  protected $layoutbuilderSearchApiManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->entityTypeManager = $container->get('entity_type.manager');
    $processor->entityFieldManager = $container->get('entity_field.manager');
    $processor->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    $processor->languageManager = $container->get('language_manager');
    $processor->cache = $container->get('cache.default');
    $processor->sectionStorageManager = $container->get('plugin.manager.layout_builder.section_storage');
    $processor->layoutbuilderSearchApiManager = $container->get('layoutbuilder_search_api.manager');
    $processor->entityRepository = $container->get('entity.repository');

    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();

    $configuration += [
      'block_content_types' => [],
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    if ($this->entityTypeManager->hasDefinition('block_content_type') && $types = $this->entityTypeManager->getStorage(
      'block_content_type'
    )->loadMultiple()) {
      foreach ($types as $key => $type) {
        $options[$key] = $type->label();
      }
    }

    $form['block_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Block Content Types'),
      '#description' => $this->t('Which block types should be exposed for indexing?'),
      '#options' => $options,
      '#default_value' => $this->configuration['block_content_types'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource || !$datasource->getEntityTypeId()) {
      return $properties;
    }

    $references = $this->getBlockReferences();

    foreach ($references as $bundle => $reference) {
      $entity_type_id = $reference['entity_type'];
      $args = [
        '@entity_type' => $reference['label'],
      ];
      $definition = [
        'label' => $this->t('Layoutbuilder Block Content: @entity_type', $args),
        'type' => "entity:$entity_type_id",
        'processor_id' => $this->getPluginId(),
        'is_list' => TRUE,
      ];
      $property = new EntityProcessorProperty($definition);
      $property->setEntityTypeId($entity_type_id);
      $property->setBundles([$bundle]);
      $properties["search_api_layoutbuilder_references_{$bundle}"] = $property;
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    try {
      $entity = $item->getOriginalObject()->getValue();
    }
    catch (SearchApiException $e) {
      return;
    }

    if (!($entity instanceof EntityInterface)) {
      return;
    }

    $langcode = $entity->language()->getId();
    $datasource_id = $item->getDatasourceId();

    /** @var \Drupal\search_api\Item\FieldInterface[][][] $to_extract */
    $to_extract = [];
    $prefix = 'search_api_layoutbuilder_references_';
    $prefix_length = strlen($prefix);
    foreach ($item->getFields() as $field) {
      $property_path = $field->getPropertyPath();
      [$direct, $nested] = Utility::splitPropertyPath($property_path, FALSE);
      if ($field->getDatasourceId() === $datasource_id
          && substr($direct, 0, $prefix_length) === $prefix) {
        $property_name = substr($direct, $prefix_length);
        $to_extract[$property_name][$nested][] = $field;
      }
    }

    // Prepare inline blocks from layout builder.
    $sections = $this->getEntitySections($entity);
    $components = [];
    $block_components = $this->getInlineBlockComponents($sections);
    $block_components = array_merge($block_components, $this->layoutbuilderSearchApiManager->getContentBlocks($sections));
    // Prepare content block from layout builder.
    foreach ($block_components as $component) {
      $base_id = $component->getPlugin()->getBaseId();
      if ($base_id === "inline_block") {
        $configuration = $component->getPlugin()->getConfiguration();
        if (!empty($configuration['block_revision_id'])) {
          $bundle = str_replace('inline_block:', '', $component->getPlugin()->getPluginId());
          $components[$bundle][] = [
            'block_revision_id' => $configuration['block_revision_id'],
            'component' => $component,
            'bundle' => $bundle,
          ];
        }
      }
      else {
        $uuid = $component->getPlugin()->getDerivativeId();
        $block = $this->entityRepository->loadEntityByUuid('block_content', $uuid);
        if ($block) {
          $bundle = $block->bundle();
          $components[$bundle][] = [
            'component' => $component,
            'bundle' => $bundle,
            'block_revision_id' => $block->getRevisionId(),
          ];
        }
      }
    }

    $references = $this->getBlockReferences();
    foreach ($to_extract as $bundle => $fields_to_extract) {
      $entities = [];

      // Skip extract if no matching inline block component is found.
      if (!isset($components[$bundle])) {
        continue;
      }

      try {
        $this->entityTypeManager
          ->getStorage($references[$bundle]['entity_type']);
      }
      catch (InvalidPluginDefinitionException $e) {
        continue;
      }
      catch (PluginNotFoundException $e) {
        continue;
      }

      foreach ($components[$bundle] as $component) {
        if ($component['bundle'] === $bundle) {
          $block = $this->entityTypeManager->getStorage('block_content')->loadRevision(
            $component['block_revision_id']
          );
          if ($block) {
            $entities[] = $block;
          }
        }
      }

      if (!$entities) {
        continue;
      }

      if (isset($fields_to_extract[''])) {
        foreach ($fields_to_extract[''] as $field) {
          $field->setValues(array_values($entities));
        }
        unset($fields_to_extract['']);
      }
      foreach ($entities as $referencing_entity) {
        $typed_data = $referencing_entity->getTypedData();
        $this->getFieldsHelper()
          ->extractFields($typed_data, $fields_to_extract, $langcode);
      }
    }
  }

  /**
   * Collects all block references.
   *
   * @return array[][]
   *   An associative array of block reference.
   */
  public function getBlockReferences() {
    if ($this->references !== NULL) {
      return $this->references;
    }

    // Property labels differ by language, so we need to vary the cache
    // according to the current language.
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $cid = "search_api:layoutbuilder_references:$langcode";
    $cache = $this->cache->get($cid);
    if (isset($cache->data)) {
      $this->references = $cache->data;
    }
    else {
      $this->references = [];

      $block_types = $this->entityTypeManager->getStorage('block_content_type')->loadMultiple(
        $this->configuration['block_content_types']
      );

      $field_manager = $this->entityFieldManager;

      foreach ($block_types as $block_type) {
        $entity_type_id = $block_type->getEntityType()->getProvider();
        $this->references[$block_type->id()] = [
          'label' => $block_type->label(),
          'entity_type' => $entity_type_id,
        ];

        /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $properties */
        $properties = $field_manager->getBaseFieldDefinitions($entity_type_id);
        $properties += $field_manager->getFieldDefinitions($entity_type_id, $block_type->id());

        foreach ($properties as $name => $property) {
          $this->references[$block_type->id()][$name] = [
            'label' => $property->getLabel(),
            'entity_type' => $entity_type_id,
            'property' => $name,
          ];
        }
      }

      $tags = [
        'entity_types',
        'entity_bundles',
        'entity_field_info',
      ];
      $this->cache->set($cid, $this->references, Cache::PERMANENT, $tags);
    }

    return $this->references;
  }

}
