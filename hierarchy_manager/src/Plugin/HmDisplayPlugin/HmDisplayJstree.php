<?php

namespace Drupal\hierarchy_manager\Plugin\HmDisplayPlugin;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hierarchy_manager\Plugin\HmDisplayPluginBase;
use Drupal\hierarchy_manager\Plugin\HmDisplayPluginInterface;

/**
 * JsTree display plugin.
 *
 * @HmDisplayPlugin(
 *   id = "hm_display_jstree",
 *   label = @Translation("JsTree")
 * )
 */
class HmDisplayJstree extends HmDisplayPluginBase implements HmDisplayPluginInterface {
  use StringTranslationTrait;

  /**
   * Build the tree form.
   *
   * @param string $url_source
   *   The URL source for the tree data.
   * @param string $url_update
   *   The URL for updating tree data.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface|null $form_state
   *   The current state of the form.
   * @param mixed $options
   *   The display options.
   * @param bool $confirm
   *   Whether to confirm changes.
   *
   * @return array
   *   The form structure.
   */
  public function getForm(string $url_source, string $url_update, array &$form = [], ?FormStateInterface &$form_state = NULL, $options = NULL, $confirm = FALSE) {
    if (!empty($url_source)) {
      if (!empty(($form_state))) {
        $parent_formObj = $form_state->getFormObject();
        $parent_id = $parent_formObj->getFormId();
      }

      // The jsTree default theme.
      $theme = 'default';
      // Drag & drop status.
      $dnd = !empty($form_state->get('dnd')) ? $form_state->get('dnd') : 'enabled';

      if (!empty($options)) {
        $jsonObj = json_decode($options);
        if (isset($jsonObj->theme) && isset($jsonObj->theme->name)) {
          $theme = $jsonObj->theme->name;
        }
      }

      // Search input.
      $form['search'] = [
        '#type' => 'textfield',
        '#title' => $this
          ->t('Search'),
        '#description' => $this->t('Type in the search keyword here to filter the tree below. Multiple keywords separated by spaces. Empty the keyword to reset the tree.'),
        '#attributes' => [
          'name' => 'jstree-search',
          'id' => isset($parent_id) ? 'hm-jstree-search-' . $parent_id : 'hm-jstree-search',
          'parent-id' => $parent_id ?? '',
          'class' => [
            'hm-jstree-search',
          ],
        ],
        '#size' => 60,
        '#maxlength' => 128,
      ];

      $form['jstree'] = [
        '#type' => 'html_tag',
        '#suffix' => '<div class="description">' . $this->t('Click an item to edit it. Drag and drop items to change their position in the tree.') . '</div>',
        '#tag' => 'div',
        '#value' => '',
        '#attributes' => [
          'class' => [
            'hm-jstree',
          ],
          'id' => isset($parent_id) ? 'hm-jstree-' . $parent_id : 'hm-jstree',
          'parent-id' => $parent_id ?? '',
          'options' => $options,
          'confirm' => $confirm,
          'data-source' => $url_source,
          'url-update' => $url_update,
          'dnd' => $dnd,
        ],
      ];

      $form['#attached']['library'][] = 'hierarchy_manager/libraries.jquery.jstree.' . $theme;
      $form['#attached']['library'][] = 'hierarchy_manager/feature.hm.jstree';
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    }

    return $form;

  }

  /**
   * Build the data array that JS library accepts.
   */
  public function treeData(array $data) {
    $jstree_data = [];

    // The array key of jsTree is different from the data source.
    // So we need to translate them.
    foreach ($data as $tree_node) {
      // Applies a very permissive XSS/HTML filter for node text.
      $tree_node['text'] = Xss::filterAdmin($tree_node['text']);
      $jstree_node = $tree_node;
      // The root id for jsTree is #.
      if (empty($tree_node['parent'])) {
        $jstree_node['parent'] = '#';
      }

      if (!$tree_node['status']) {
        $jstree_node['li_attr'] = [
          'class' => 'hm-tree-node-disabled',
        ];
      }

      $dialog_options = [
        'minWidth' => '300',
        'width' => '960',
        'title' => $this->t('Edit') . ' ' . preg_replace('~<span(.*?)</span>~Usi', '', $tree_node['text']),
      ];
      // Custom data.
      $jstree_node['a_attr'] = [
        'href' => $jstree_node['edit_url'],
        'class' => 'use-ajax',
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode($dialog_options),
      ];
      $jstree_node['data'] = [
        'weight' => $tree_node['weight'],
        'draggable' => $tree_node['draggable'],
      ];
      unset($jstree_node['edit_url']);
      // Add this node into the data array.
      $jstree_data[] = $jstree_node;
    }

    return $jstree_data;
  }

}
