<?php

namespace Drupal\component_blocks\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\ui_patterns\UiPatternsManager;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for deriving blocks from component block plugins.
 */
class ComponentBlockBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Plugin manager.
   *
   * @var \Drupal\ui_patterns\UiPatternsManager
   */
  private $pluginManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Constructs a new ComponentBlockBlockDeriver.
   *
   * @param \Drupal\ui_patterns\UiPatternsManager $pluginManager
   *   Plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(UiPatternsManager $pluginManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->pluginManager = $pluginManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->pluginManager->getDefinitions() as $id => $definition) {
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type_definition) {
        // We have to loop per content entity for the correct context.
        if (!$entity_type_definition->entityClassImplements(ContentEntityInterface::class)) {
          continue;
        }
        $entity_type_id = $entity_type_definition->id();
        $entity_type_label = $entity_type_definition->getLabel();
        $context_definition = EntityContextDefinition::fromEntityTypeId($entity_type_id)->setLabel($entity_type_label);
        $this->derivatives[$entity_type_id . ':' . $id] = [
          'admin_label' => new TranslatableMarkup('@component with fields from @entity', [
            '@component' => $definition['label'],
            '@entity' => $entity_type_label,
          ]),
          // These are inherently bound to layout builder.
          '_block_ui_hidden' => TRUE,
          'ui_pattern_id' => $id,
          'context_definitions' => [
            'entity' => $context_definition,
          ],
        ] + $base_plugin_definition;
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    $base_plugin_id,
  ) {
    return new static(
      $container->get('plugin.manager.ui_patterns'),
      $container->get('entity_type.manager')
    );
  }

}
