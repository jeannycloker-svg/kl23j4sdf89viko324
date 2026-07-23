<?php

declare(strict_types=1);

namespace Drupal\menu_tree\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderAfter;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\menu_tree\MenuTreeItems;
use Drupal\menu_tree\NodeFormSubmitHandler;
use Drupal\node\NodeTypeInterface;
use Drupal\system\Entity\Menu;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements various hooks.
 */
class Hooks implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Node form submit handler.
   *
   * @var \Drupal\menu_tree\NodeFormSubmitHandler
   */
  protected NodeFormSubmitHandler $nodeFormSubmitHandler;

  /**
   * Menu tree items service.
   *
   * @var \Drupal\menu_tree\MenuTreeItems
   */
  protected MenuTreeItems $menuTreeItems;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Class constructor.
   *
   * @param \Drupal\menu_tree\NodeFormSubmitHandler $node_form_submit_handler
   *   Node form submit handler.
   * @param \Drupal\menu_tree\MenuTreeItems $menu_tree_items
   *   Menu tree items service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(
    NodeFormSubmitHandler $node_form_submit_handler,
    MenuTreeItems $menu_tree_items,
    ModuleHandlerInterface $module_handler,
  ) {
    $this->nodeFormSubmitHandler = $node_form_submit_handler;
    $this->menuTreeItems = $menu_tree_items;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) : static {
    return new static(
      $container->get('menu_tree.node_form_submit_handler'),
      $container->get('menu_tree.items'),
      $container->get('module_handler')
    );
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) : string|\Stringable|array|null {
    if ($route_name == 'help.page.menu_tree') {
      $output = '';
      $output .= '<h3>' . t('About') . '</h3>';
      $output .= '<p>' . t('This modules replaces the standard widget for selecting Parent link on node add and edit forms with a tree widget.') . '</p>';
      $output .= '<h3>' . t('Supporting organizations:') . '</h3>';
      $output .= '<p>' . t('<a href="https://www.drupal.org//happiness">Happiness</a>') . '</p>';
      return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   *
   * This function modifies the node form elements based on the configuration
   * of the 'menu_tree' module. It uses the settings for bundles and available
   * menus to determine the alterations needed for the menu-related form
   * elements. When applicable, it hides the default menu parent and weight
   * elements and adds a tree selector for menu placement.
   */
  #[Hook('form_node_form_alter', order: new OrderAfter(['menu_ui']))]
  public function nodeFormAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $form_object->getEntity();
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $node->get('type')->entity;
    $use_tree_widget = $node_type->getThirdPartySetting('menu_tree', 'use_tree_widget', FALSE);

    // Is the tree widget enabled for this node type?
    if (!$use_tree_widget) {
      return;
    }

    // Get available menus from node.type.<bundle>.yml.
    $available_menus = $node_type->getThirdPartySetting('menu_ui', 'available_menus', []);

    // No menus configured for the bundle, do nothing.
    if (empty($available_menus)) {
      return;
    }

    // Load menu config entities and sort them by weight.
    $menus = \Drupal::entityTypeManager()->getStorage('menu')->loadMultiple($available_menus);
    uasort($menus, static function ($a, $b): int {
      return (int) $a->getThirdPartySetting('menu_tree', 'weight', 0)
        <=>
        (int) $b->getThirdPartySetting('menu_tree', 'weight', 0);
    });

    // Assign the root element.
    $root_element = &$form['menu'];

    // If 'menu_ui_async_widget' exists and is configured for this content
    // type, add special logic to handle this.
    if ($this->moduleHandler->moduleExists('menu_ui_async_widget')) {
      $use_async_widget = $node_type->getThirdPartySetting('menu_ui_async_widget', 'use_async_widget', FALSE);
      if ($use_async_widget) {
        $root_element = &$form['menu']['container'];
      }
      if ($use_async_widget && !$form_state->get('menu_ui_async_widget')) {
        return;
      }
    }

    // Hide menu parent selector and weight elements.
    $root_element['link']['menu_parent']['#type'] = 'hidden';
    $root_element['link']['weight']['#type'] = 'hidden';

    $defaults = menu_ui_get_menu_link_defaults($node);
    $exclude = $defaults['menu_name'] . ':' . $defaults['id'];
    $state = !$defaults['parent'] ? 'expand' : 'collapse';

    // Get menu links for enabled menus.
    $menu_links = [];
    foreach ($menus as $menu) {
      $menu_links[] = $this->menuTreeItems->getLinks($menu->id());
    }

    // Add the menu tree component.
    $elem_id = Html::getUniqueId('menu-tree');
    $attributes = new Attribute(['id' => $elem_id, 'class' => 'menu-tree']);
    $root_element['link']['menu_tree'] = [
      '#type' => 'component',
      // '#name' is required to fix class names in the theme wrapper.
      '#name' => 'menu-tree',
      '#id' => $elem_id,
      '#component' => 'menu_tree:menu-tree',
      '#props' => [
        'menus' => $menu_links,
        'exclude' => $exclude,
        'selected' => $defaults['parent'],
        'attributes' => $attributes,
        'expand_collapse_state' => $state,
      ],
      '#title' => $this->t('Parent link'),
      '#theme_wrappers' => ['form_element'],
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    $root_element['link']['prev_sibling'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];

    $root_element['link']['next_sibling'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];

    // Add submit handler.
    foreach (array_keys($form['actions']) as $action) {
      if ($action != 'preview' && isset($form['actions'][$action]['#type']) && $form['actions'][$action]['#type'] === 'submit') {
        $form['actions'][$action]['#submit'][] = [$this->nodeFormSubmitHandler, 'handleFormSubmit'];
      }
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   *
   * Adds a weight element to the menu form.
   */
  #[Hook('form_menu_form_alter')]
  public function menuFormAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    /** @var \Drupal\system\Entity\Menu $entity */
    $entity = $form_state->getFormObject()->getEntity();

    $form['label']['#weight'] = 0;
    $form['description']['#weight'] = 1;

    $form['weight'] = [
      '#type' => 'weight',
      '#title' => 'Weight',
      '#default_value' => $entity->getThirdPartySetting('menu_tree', 'weight', 0),
      '#weight' => 2,
    ];

    $form['links']['#weight'] = 3;

    $form['#entity_builders'][] = [$this, 'menuFormBuilder'];
  }

  /**
   * Entity builder for the menu form with a weight element.
   */
  public function menuFormBuilder($entity_type, Menu $menu, &$form, FormStateInterface $form_state) : void {
    $menu->setThirdPartySetting('menu_tree', 'weight', $form_state->getValue('weight'));
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Adds a checkbox to enable the tree widget on the node type settings form.
   *
   * @see \Drupal\node\Form\NodeTypeForm::form()
   */
  #[Hook('form_node_type_form_alter', order: new OrderAfter(['menu_ui']))]
  public function nodeTypeFormAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\node\NodeTypeInterface $type */
    $type = $form_object->getEntity();

    $form['menu']['use_tree_widget'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use tree widget for parent link'),
      '#default_value' => $type->getThirdPartySetting('menu_tree', 'use_tree_widget', FALSE),
      '#description' => $this->t('If checked, a tree widget will be used to select the parent link on node forms.'),
    ];

    $form['#entity_builders'][] = [$this, 'nodeTypeFormBuilder'];
  }

  /**
   * Entity builder for the node type form with menu options.
   */
  public function nodeTypeFormBuilder($entity_type, NodeTypeInterface $type, &$form, FormStateInterface $form_state) : void {
    $type->setThirdPartySetting('menu_tree', 'use_tree_widget', $form_state->getValue('use_tree_widget'));
  }

  /**
   * Implements hook_preprocess_HOOK().
   *
   * Removes the 'for' attribute of the menu tree label as we have no visible
   * form element to reference.
   */
  #[Hook('preprocess_form_element_label')]
  public function preprocessFormElementLabel(&$variables) : void {
    if (str_starts_with($variables['element']['#id'] ?? '', 'menu-tree')) {
      unset($variables['attributes']['for']);
    }
  }

}
