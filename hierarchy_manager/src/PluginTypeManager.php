<?php

namespace Drupal\hierarchy_manager;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hierarchy_manager\Plugin\HmDisplayPluginManager;
use Drupal\hierarchy_manager\Plugin\HmSetupPluginManager;

/**
 * Hierarchy Manager plugin manager class.
 */
class PluginTypeManager {

  /**
   * Display plugin manager.
   *
   * @var \Drupal\hierarchy_manager\Plugin\HmDisplayPluginManager
   */
  protected $displayManager;

  /**
   * Setup plugin manager.
   *
   * @var \Drupal\hierarchy_manager\Plugin\HmSetupPluginManager
   */
  protected $setupManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, HmDisplayPluginManager $display_manager, HmSetupPluginManager $setup_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->displayManager = $display_manager;
    $this->setupManager = $setup_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Construct an item inside the hierarchy.
   *
   * @param string|int $id
   *   Item id.
   * @param string $label
   *   Item text.
   * @param string $parent
   *   Parent id of the item.
   * @param string $edit_url
   *   The URL where to edit this item.
   * @param bool $status
   *   The item status.
   * @param int $weight
   *   The weight of the node.
   * @param bool $draggable
   *   If this item draggable.
   *
   * @return array
   *   The hierarchy item array.
   */
  public function buildHierarchyItem($id, $label, $parent, $edit_url, $status = TRUE, $weight = 0, $draggable = TRUE) {
    $item = [
      'id' => $id,
      'text' => $label,
      'parent' => $parent,
      'edit_url' => $edit_url,
      'status' => $status,
      'weight' => $weight,
      'draggable' => $draggable,
    ];

    $this->moduleHandler->alter('hierarchy_manager_item', $item);

    return $item;
  }

  /**
   * Get a display plugin instance according to a setup plugin.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityBase $display_profile
   *   Display profile entity.
   *
   * @return null|object
   *   The display plugin instance.
   */
  public function getDisplayPluginInstance(ConfigEntityBase $display_profile) {
    if (empty($display_profile)) {
      return NULL;
    }
    // Display plugin ID.
    $display_plugin_id = $display_profile->get("plugin");

    return $this->displayManager->createInstance($display_plugin_id);
  }

  /**
   * Get a display profile entity according to a setup plugin.
   *
   * @param string $setup_plugin_id
   *   setup plugin ID.
   *
   * @return null|\Drupal\Core\Config\Entity\ConfigEntityBase
   *   The display profile entity.
   */
  public function getDisplayProfile(string $setup_plugin_id) {
    // The setup plugin instance.
    $setup_plugin = $this->setupManager->createInstance($setup_plugin_id);
    // Return the display profile.
    return $this->entityTypeManager->getStorage('hm_display_profile')->load($setup_plugin->getDisplayProfileId());
  }

  /**
   * Update the items for a hierarchy.
   *
   * @param int $target_position
   *   Which position the new items will be insert.
   * @param array $all_siblings
   *   All siblings of the new items in an array[$item_id => (int)$weight].
   * @param array $updated_items
   *   IDs of new items inserted.
   * @param int $old_position
   *   The old position of moving items.
   *
   * @return array
   *   All siblings needed to move and their new weights.
   */
  public function updateHierarchy(int $target_position, array $all_siblings, array $updated_items, int $old_position) {
    $new_hierarchy = [];
    $weight = 0;
    if (!empty($all_siblings)) {
      $total = count($all_siblings);
      $num_new = count(array_diff($updated_items, array_keys($all_siblings)));
      if ($target_position <= 0) {
        // The insert position is into the first.
        // we don't need to move any siblings.
        $weight = reset($all_siblings) - 1;
      }
      elseif ($target_position >= $total + $num_new - 1) {
        // The insert position is at the end,
        // we don't need to move any siblings.
        $last_item = array_slice($all_siblings, -1, 1, TRUE);
        $weight = reset($last_item) + 1;
      }
      else {
        $target_item = array_slice($all_siblings, $target_position, 1);
        $target_weight = reset($target_item);
        $weight = $target_weight;
        $total_insert = count($updated_items);
        // Figure out if the target element should move forward.
        if ($num_new || ($old_position > $target_position)) {
          $move_forward = TRUE;
        }
        else {
          $move_forward = FALSE;
        }

        // If the target position is in the second half,
        // we will move all siblings
        // after the target position forward.
        // Otherwise, we will move siblings
        // before the target position backwards.
        if ($target_position - $num_new >= $total / 2) {
          if ($move_forward) {
            $moving_siblings = array_slice($all_siblings, $target_position, NULL, TRUE);
            $weight = $target_weight;
          }
          else {
            $moving_siblings = array_slice($all_siblings, $target_position + 1, NULL, TRUE);
            $weight = $target_weight + 1;
          }
          $after = TRUE;
          $expected_weight = $weight + $total_insert;
        }
        // Move the first bundle.
        else {
          if ($move_forward) {
            $moving_siblings = array_slice($all_siblings, 0, $target_position, TRUE);
            $weight = $target_weight - 1;
          }
          else {
            $moving_siblings = array_slice($all_siblings, 0, $target_position + 1, TRUE);
            $weight = $target_weight;
          }
          $after = FALSE;
          $expected_weight = $weight - count($moving_siblings) - $total_insert + 1;
        }

        // Move all siblings that need to move.
        foreach ($moving_siblings as $item_id => $item_weight) {
          // Skip all items that are in the updated array.
          // They will be moved later.
          if (in_array($item_id, $updated_items)) {
            continue;
          }
          if ($after) {
            if ($item_weight < $expected_weight) {
              $new_hierarchy[$item_id] = $expected_weight;
            }
            else {
              // The weight is bigger than expected.
              // No need to move the rest of siblings.
              break;
            }
          }
          else {
            if ($item_weight > $expected_weight) {
              $new_hierarchy[$item_id] = $expected_weight;
            }
          }
          // Move to next sibling.
          $expected_weight++;
        }
      }
    }

    foreach ($updated_items as $item) {
      $new_hierarchy[$item] = $weight++;
    }

    return $new_hierarchy;
  }

}
