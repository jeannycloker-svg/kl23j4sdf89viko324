<?php

namespace Drupal\hierarchy_manager\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Taxonomy feeding controller class.
 */
class HmTaxonomyController extends ControllerBase {

  /**
   * CSRF Token.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The term storage handler.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $storageController;

  /**
   * The hierarchy manager plugin type manager.
   *
   * @var \Drupal\hierarchy_manager\PluginTypeManager
   */
  protected $hmPluginTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(CsrfTokenGenerator $csrfToken, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, $plugin_type_manager) {

    $this->csrfToken = $csrfToken;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->storageController = $entity_type_manager->getStorage('taxonomy_term');
    $this->hmPluginTypeManager = $plugin_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('csrf_token'),
        $container->get('entity_type.manager'),
        $container->get('entity.repository'),
        $container->get('hm.plugin_type_manager')
        );
  }

  /**
   * Access check callback for taxonomy tree json.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account.
   * @param string $vid
   *   Vocabulary ID.
   */
  public function access(AccountInterface $account, string $vid) {
    if ($account->hasPermission('administer taxonomy')) {
      return AccessResult::allowed();
    }
    return AccessResult::allowedIfHasPermission($account, "edit terms in {$vid}");
  }

  /**
   * Callback for taxonomy tree json.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Http request object.
   * @param string $vid
   *   Vocabulary ID.
   */
  public function taxonomyTreeJson(Request $request, string $vid) {
    // Access token.
    $token = $request->get('token');
    // The term array will be returned.
    $term_array = [];
    // Store the number of each term id present.
    $ids = [];
    // Store terms that have ambiguous parents.
    $am_terms = [];
    // Store all terms only have single ancestor.
    $single_parent = [];

    if (empty($token) || !$this->csrfToken->validate($token, $vid)) {
      return new Response($this->t('Access denied!'));
    }
    $parent = $request->get('parent') ?: 0;
    $depth = $request->get('depth');
    $destination = $request->get('destination');

    if (!empty($depth)) {
      $depth = intval($depth);
    }

    $tree = $this->storageController->loadTree($vid, $parent, $depth, TRUE);
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('taxonomy_term');

    foreach ($tree as $term) {
      if ($term instanceof Term) {
        $term = $this->entityRepository->getTranslationFromContext($term);
        // User can only access the terms that they can update.
        if ($access_control_handler->access($term, 'update')) {
          if (empty($destination)) {
            $url = $term->toUrl('edit-form')->toString();
          }
          else {
            $url = $term->toUrl('edit-form', ['query' => ['destination' => $destination]])->toString();
          }
          $term_parent = $term->parents;
          $id = $term->id();
          $count_parent = count($term_parent);
          if (isset($ids[$id])) {
            if ($ids[$id] === 0 && isset($single_parent[$id])) {
              // Update previous term in the term array
              // which has the same ID. Make it not draggable.
              $term_array[$single_parent[$id]]['draggable'] = FALSE;
            }
            $ids[$id]++;
            $term_id = $id . '_' . $ids[$id];
          }
          else {
            $ids[$id] = 0;
            $term_id = $id;
          }
          // If a taxonomy term has multiple parents,
          // It will present multiple times under different parents.
          // So the term id will be duplicated.
          // The solution is to format the term id as following,
          // {term_id}_{parent_index}.
          if ($count_parent > 1) {
            $draggable = FALSE;
            // This term has an ancestor with multiple parents.
            if ($ids[$id] === $count_parent) {
              // Put into the ambiguous array.
              // Will solve it later.
              $am_terms[] = [
                'solved' => FALSE,
                'id' => $term_id,
                'label' => $term->label(),
                'parent' => $term_parent,
                'url' => $url,
                'publish' => $term->isPublished(),
                'weight' => $term->getWeight(),
                'draggable' => FALSE,
              ];
              continue;
            }
            $parent_id = $term_parent[$ids[$id]];
          }
          else {
            if ($ids[$id]) {
              // The parent has multiple grandparent.
              $parent_id = $term_parent[0] . '_' . $ids[$id];
              $draggable = FALSE;
            }
            else {
              // The parent doesn't have multiple grandparent.
              $parent_id = $term_parent[0];
              $draggable = TRUE;
              // At this point, we still don't know
              // if this term has multiple ancestors or not.
              // So keep the index of term array for later update
              // if needed.
              $single_parent[$id] = count($term_array);
            }
          }

          $term_array[] = $this->hmPluginTypeManager->buildHierarchyItem(
            $term_id,
            $term->label(),
            $parent_id,
            $url,
            $term->isPublished(),
            $term->getWeight(),
            $draggable
          );
        }
      }
    }
    // Figure out the parent id for terms in the ambiguous term array.
    do {
      $found = FALSE;
      foreach ($am_terms as $key => $term) {
        if ($term['solved']) {
          continue;
        }
        $parent_ids = $term['parent'];
        foreach ($parent_ids as $id) {
          if ($ids[$id]) {
            // Found the parent with multiple grandparent.
            $term_array[] = $this->hmPluginTypeManager->buildHierarchyItem(
                $term['id'],
                $term['label'],
                $id . '_' . $ids[$id],
                $term['url'],
                $term['publish'],
                $term['weight'],
                $term['draggable']
            );
            $found = TRUE;
            // Remove this parent from ids array.
            $ids[$id]--;
            $am_terms[$key]['solved'] = TRUE;
            continue 2;
          }
        }
      }
    } while ($found);

    // Display profile.
    $display_profile = $this->hmPluginTypeManager->getDisplayProfile('hm_setup_taxonomy');
    // Display plugin instance.
    $display_plugin = $this->hmPluginTypeManager->getDisplayPluginInstance($display_profile);

    if (empty($display_plugin)) {
      return new JsonResponse(['result' => 'Display profile has not been set up.']);
    }

    if (method_exists($display_plugin, 'treeData')) {
      // Convert the tree data to the structure
      // that display plugin accepts.
      $tree_data = $display_plugin->treeData($term_array);
    }
    else {
      $tree_data = $term_array;
    }

    return new JsonResponse($tree_data);
  }

  /**
   * Callback for taxonomy tree json.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Http request object.
   * @param string $vid
   *   Vocabulary ID.
   */
  public function updateTerms(Request $request, string $vid) {
    // Access token.
    $token = $request->get('token');
    if (empty($token) || !$this->csrfToken->validate($token, $vid)) {
      return new Response($this->t('Access denied!'));
    }

    $target_position = $request->get('target');
    $old_position = (int) $request->get('old_position');
    $old_parent_id = $request->get('old_parent');
    // Remove the parent index from the parent id.
    $old_parent_id = explode('_', $old_parent_id)[0];
    $parent_id = $request->get('parent');
    // Remove the parent index from the parent id.
    $parent_id = explode('_', $parent_id)[0];
    $updated_terms = $request->get('keys');
    $success = FALSE;
    $all_siblings = [];

    if (is_array($updated_terms) && !empty($updated_terms)) {
      // Remove the parent index from the term id.
      for ($i = 0; $i < count($updated_terms); $i++) {
        $updated_terms[$i] = explode('_', $updated_terms[$i])[0];
      }
      // Taxonomy access control.
      $access_control_handler = $this->entityTypeManager->getAccessControlHandler('taxonomy_term');

      // Children of the parent term in weight and name alphabetically order.
      $children = $this->storageController->loadTree($vid, $parent_id, 1);
      if (!empty($children)) {
        // The parent term has children.
        $target_position = intval($target_position);

        foreach ($children as $child) {
          $all_siblings[$child->tid] = (int) $child->weight;
        }
      }

      $new_hierarchy = $this->hmPluginTypeManager->updateHierarchy($target_position, $all_siblings, $updated_terms, $old_position);
      $tids = array_keys($new_hierarchy);

      // Load all terms needed to update.
      $terms = Term::loadMultiple($tids);
      // Update all terms.
      foreach ($terms as $term) {
        if ($access_control_handler->access($term, 'update')) {
          $term->setWeight($new_hierarchy[$term->id()]);
          // Update the parent IDs.
          if (in_array($term->id(), $updated_terms)) {
            $parents = [];
            $same_parent = $old_parent_id === $parent_id;
            // Update the parent only if it is changed.
            if (!$same_parent) {
              foreach ($term->get('parent') as $parent) {
                $tid = $parent->get('target_id')->getValue();
                if ($tid === $old_parent_id) {
                  $tid = $parent_id;
                }
                elseif ($tid === $parent_id) {
                  continue;
                }
                $parents[] = ['target_id' => $tid];
              }
              // Set the new parent.
              $term->set('parent', $parents);
            }
          }
          $success = $term->save();
        }
      }
    }

    $result = [
      'result' => $success ? 'success' : 'fail',
      'updated_nodes' => $new_hierarchy,
    ];

    return new JsonResponse($result);
  }

}
