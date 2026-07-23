<?php

namespace Drupal\component_blocks\Plugin\Block;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Utility\Token;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\ui_patterns\Definition\PatternDefinitionField;
use Drupal\ui_patterns\Definition\PatternDefinitionVariant;
use Drupal\ui_patterns\UiPatterns;
use Drupal\ui_patterns\UiPatternsManager;
use Drupal\ui_patterns_settings\Form\SettingsFormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for a specially shaped block.
 *
 * @Block(
 *  id = "component_blocks",
 *  admin_label = @Translation("Component blocks"),
 *  category = @Translation("Component blocks"),
 *  deriver = "Drupal\component_blocks\Plugin\Deriver\ComponentBlockBlockDeriver",
 * )
 */
class ComponentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use LayoutBuilderContextTrait;

  const FIXED = '__fixed';

  /**
   * Plugin manager.
   *
   * @var \Drupal\ui_patterns\UiPatternsManager
   */
  private $uiPatternsManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  private $contextHandler;

  /**
   * Formatter manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  private $formatterPluginManager;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  private $token;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ComponentBlock.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin Id.
   * @param array $plugin_definition
   *   Plugin definition.
   * @param \Drupal\ui_patterns\UiPatternsManager $uiPatternsManager
   *   Plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $contextHandler
   *   Context handler.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatterPluginManager
   *   Formatter manager.
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    UiPatternsManager $uiPatternsManager,
    EntityTypeManagerInterface $entityTypeManager,
    ContextHandlerInterface $contextHandler,
    FormatterPluginManager $formatterPluginManager,
    Token $token,
    ModuleHandlerInterface $module_handler,
  ) {
    $this->uiPatternsManager = $uiPatternsManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->contextHandler = $contextHandler;
    $this->formatterPluginManager = $formatterPluginManager;
    $this->token = $token;
    $this->moduleHandler = $module_handler;
    // This has to be last because the parent constructor calls
    // ::setConfiguration.
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.ui_patterns'),
      $container->get('entity_type.manager'),
      $container->get('context.handler'),
      $container->get('plugin.manager.field.formatter'),
      $container->get('token'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $defaultConfiguration = $this->defaultConfiguration();
    $plugin = $this->uiPatternsManager()->getDefinition($this->pluginDefinition['ui_pattern_id']);
    foreach ($plugin['fields'] as $item) {
      if (!($item['ui'] ?? TRUE)) {
        // We don't want duplicates for no-ui items - default is enough.
        unset($configuration['variables'][$item->getName()]['value']);
      }
    }
    $this->configuration = NestedArray::mergeDeep(
      $this->baseConfigurationDefaults(),
      $defaultConfiguration,
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $plugin = $this->uiPatternsManager()->getDefinition($this->pluginDefinition['ui_pattern_id']);
    $defaults = array_map(function (PatternDefinitionField $item) {
      return ['source' => self::FIXED, 'value' => $item['default'] ?? ''];
    }, $plugin['fields']);
    return [
      'label_display' => FALSE,
      'variant' => NULL,
      'variables' => $defaults,
      'settings' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $definition = $this->uiPatternsManager()->getDefinition($this->pluginDefinition['ui_pattern_id']);
    $context = [];
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getContextValue('entity');
    $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $metadata = new BubbleableMetadata();
    $metadata->addCacheableDependency($entity);

    foreach ($this->getConfiguration()['variables'] as $context_id => $details) {
      if ($details['source'] === self::FIXED) {
        if (!is_scalar($details['value'])) {
          // Allow for array default values.
          $context[$context_id] = $details['value'];
          continue;
        }
        try {
          $value = $this->token->replace($details['value'], [
            $entity->getEntityTypeId() => $entity,
          ], [], $metadata);
          if ($value !== $details['value']) {
            // Token replacement sanitizes, so we need to flag as such.
            $value = Markup::create($value);
          }
        }
        catch (EntityMalformedException $e) {
          // Attempt to get e.g an entity URL without a saved entity in layout
          // builder.
          $value = '';
        }
        $context[$context_id] = $value;
        continue;
      }

      try {
        $formatter_output = $view_builder->viewField(
          $entity->get($details['source']),
          array_intersect_key($details,
          ['type' => TRUE, 'settings' => TRUE]) + ['label' => 'hidden']
        );
        if (Element::isEmpty($formatter_output)) {
          // No output other than cache metadata.
          $metadata->merge(CacheableMetadata::createFromRenderArray($formatter_output));
          continue;
        }
        $context[$context_id] = ['#theme' => 'field__component_block'] + $formatter_output;
      }
      catch (EntityMalformedException $e) {
        // Attempt to get e.g an entity URL without a saved entity in layout
        // builder.
        $context[$context_id] = '';
      }
      catch (\InvalidArgumentException $e) {
        $context[$context_id] = '';
      }
    }

    $build = [
      '#type' => 'pattern',
      '#id' => $this->pluginDefinition['ui_pattern_id'],
      '#fields' => $context,
      '#context' => [
        'type' => 'entity',
        'entity' => $entity,
      ],
    ];

    if (isset($this->getConfiguration()['variant'])) {
      $build['#variant'] = $this->getConfiguration()['variant'];
    }

    if (isset($this->getConfiguration()['settings'])) {
      $build['#settings'] = $this->getConfiguration()['settings'];
    }

    // Attach libraries to the block;.
    if (!empty($definition['libraries'])) {
      $metadata->addAttachments(['library' => $definition->getLibrariesNames()]);
    }

    $metadata->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $plugin = $this->uiPatternsManager()->getDefinition($this->pluginDefinition['ui_pattern_id']);
    $form = parent::blockForm($form, $form_state);
    if (!empty($plugin['variants'])) {
      $form['variant'] = [
        '#type' => 'select',
        '#title' => $this->t('Variant'),
        '#default_value' => $this->getConfiguration()['variant'] ?? NULL,
        '#options' => array_map(function (PatternDefinitionVariant $item) {
          return $item->getLabel();
        }, $plugin['variants']),
      ];
    }
    $form['variables'] = [
      '#type' => 'details',
      '#title' => $this->t('Context variables'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $contexts = $this->contextHandler()->getMatchingContexts($form_state->getTemporaryValue('gathered_contexts') ?: [], $this->getContextDefinition('entity'));
    $context = reset($contexts);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $sample_entity */
    $sample_entity = $context->getContextData()->getValue();
    $fields = array_map(function (FieldDefinitionInterface $field) {
      return $field->getLabel();
    }, $sample_entity->getFieldDefinitions());
    $fields[self::FIXED] = $this->t('Fixed input');
    foreach ($plugin['fields'] as $id => $details) {
      if (!($details['ui'] ?? TRUE)) {
        $form['variables'][$id] = [
          'source' => [
            '#type' => 'value',
            '#value' => self::FIXED,
          ],
          'value' => [
            '#type' => 'value',
            '#value' => $details['default'],
          ],
        ];
        continue;
      }
      $form['variables'][$id] = [
        '#type' => 'container',
        '#process' => [
          [$this, 'formatterSettingsProcessCallback'],
        ],
        '#prefix' => '<div id="component-settings-' . $id . '">',
        '#suffix' => '</div>',
        'label' => [
          '#type' => 'item',
          '#markup' => $details['label'],
        ],
        'source' => [
          '#type' => 'select',
          '#options' => $fields,
          '#default_value' => self::FIXED,
          '#title' => $this->t('Source'),
          '#ajax' => [
            'callback' => [get_class($this), 'updateElementValue'],
            'wrapper' => 'component-settings-' . $id,
          ],
        ],
      ];
    }
    // Settings are provided by the ui_patterns_settings module.
    if ($this->moduleHandler->moduleExists('ui_patterns_settings')) {
      $configuration['pattern']['settings'] = $this->getConfiguration()['settings'];
      $definition = UiPatterns::getPatternDefinition($this->pluginDefinition['ui_pattern_id']);

      SettingsFormBuilder::layoutForm($form, $definition, $configuration);

      // The 'settings' element added by UI Patterns Settings is a fieldset.
      // Alter it to match the 'variables' element above.
      $form['settings'] = array_merge($form['settings'] ?? [], [
        '#type' => 'details',
        '#title' => $this->t('Pattern settings'),
        '#open' => TRUE,
        '#tree' => TRUE,
      ]);
    }

    return $form;
  }

  /**
   * Render API callback: builds the formatter settings elements.
   */
  public function formatterSettingsProcessCallback(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if ($configuration = $this->getCurrentConfiguration($element['#parents'], $form_state)) {
      if ($configuration['source'] === self::FIXED) {
        $element['value'] = [
          '#type' => 'textfield',
          '#default_value' => $configuration['value'] ?? '',
          '#title' => $this->t('Fixed value'),
        ];
        return $element;
      }
      $contexts = $this->contextHandler()->getMatchingContexts($form_state->getTemporaryValue('gathered_contexts') ?: [], $this->getContextDefinition('entity'));
      // Contexts can become empty on subsequent ajax requests with layout
      // builder.
      if (!$contexts) {
        $contexts = $this->contextHandler()->getMatchingContexts($this->getPopulatedContexts($form_state->getBuildInfo()['args'][0]), $this->getContextDefinition('entity'));
      }
      $context = reset($contexts);
      /** @var \Drupal\Core\Entity\ContentEntityInterface $sample_entity */
      $sample_entity = $context->getContextData()->getValue();
      $field_definition = $sample_entity->getFieldDefinition($configuration['source']);
      $formatter_configuration = array_intersect_key($configuration, ['type' => TRUE, 'settings' => TRUE]) + ['label' => 'hidden'];
      $options = $this->getApplicablePluginOptions($field_definition);
      $keys = array_keys($options);

      $formatter_configuration += [
        'type' => reset($keys),
        'settings' => $this->formatterPluginManager()->getDefaultSettings(reset($keys)),
      ];

      $formatter = $this->formatterPluginManager()->getInstance([
        'configuration' => $formatter_configuration,
        'field_definition' => $field_definition,
        'view_mode' => EntityDisplayBase::CUSTOM_MODE,
        'prepare' => TRUE,
      ]);
      $element['source']['#default_value'] = $configuration['source'];
      $element['type'] = [
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $formatter_configuration['type'],
        '#required' => TRUE,
        '#title' => $this->t('Formatter'),
        '#ajax' => [
          'callback' => [static::class, 'updateElementValue'],
          'wrapper' => 'component-settings-' . end($element['#parents']),
        ],
      ];
      $element['settings'] = $formatter->settingsForm($complete_form, $form_state);
      $element['settings']['#parents'] = array_merge($element['#parents'], ['settings']);
    }
    return $element;
  }

  /**
   * Gets the current configuration for given parents.
   *
   * @param array $parents
   *   The #parents of the element representing the formatter.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|null
   *   The current configuration.
   */
  protected function getCurrentConfiguration(array $parents, FormStateInterface $form_state) : ?array {
    // Use the processed values, if available.
    $configuration = NestedArray::getValue($form_state->getValues(), $parents);
    $variable = end($parents);
    if (!$configuration) {
      // Next check the raw user input.
      $configuration = NestedArray::getValue($form_state->getUserInput(), $parents);
      if (!$configuration) {
        // If no user input exists, use the default values.
        $settings = $this->getConfiguration()['variables'][$variable];
        return $settings;
      }
    }
    return $configuration;
  }

  /**
   * Ajax callback that updates options.
   */
  public static function updateElementValue(array $form, FormStateInterface $form_state) {
    $array_parents = $form_state->getTriggeringElement()['#array_parents'];
    array_pop($array_parents);
    return NestedArray::getValue($form, $array_parents);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['variant'] = $form_state->getValue('variant');
    $this->configuration['variables'] = $form_state->getValue('variables');
    $this->configuration['settings'] = $form_state->getValue('settings');
  }

  /**
   * Returns an array of applicable formatter options for a field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return array
   *   An array of applicable formatter options.
   *
   * @see \Drupal\field_ui\Form\EntityDisplayFormBase::getApplicablePluginOptions()
   */
  protected function getApplicablePluginOptions(FieldDefinitionInterface $field_definition) {
    $options = $this->formatterPluginManager()->getOptions($field_definition->getType());
    $applicable_options = [];
    foreach ($options as $option => $label) {
      $plugin_class = DefaultFactory::getPluginClass($option, $this->formatterPluginManager()->getDefinition($option));
      if ($plugin_class::isApplicable($field_definition)) {
        $applicable_options[$option] = $label;
      }
    }
    return $applicable_options;
  }

  /**
   * {@inheritdoc}
   */
  protected function contextHandler() {
    // phpcs:disable DrupalPractice.Objects.GlobalDrupal.GlobalDrupal
    // @phpstan-ignore-next-line
    return $this->contextHandler ?: \Drupal::service('context.handler');
  }

  /**
   * Gets the formatter plugin manager.
   *
   * In some AJAX contexts, the constructor is not called.
   *
   * @return \Drupal\Core\Field\FormatterPluginManager
   *   Manager.
   */
  protected function formatterPluginManager() : FormatterPluginManager {
    if (!$this->formatterPluginManager) {
      // @phpstan-ignore-next-line
      $this->formatterPluginManager = \Drupal::service('plugin.manager.field.formatter');
    }
    return $this->formatterPluginManager;
  }

  /**
   * Gets the UI patterns manager.
   *
   * In some AJAX contexts, the constructor is not called.
   *
   * @return \Drupal\ui_patterns\UiPatternsManager
   *   Manager.
   */
  protected function uiPatternsManager(): UiPatternsManager {
    if (!$this->uiPatternsManager) {
      // @phpstan-ignore-next-line
      $this->uiPatternsManager = \Drupal::service('plugin.manager.ui_patterns');
    }
    return $this->uiPatternsManager;
  }

}
