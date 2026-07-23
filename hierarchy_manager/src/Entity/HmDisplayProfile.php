<?php

namespace Drupal\hierarchy_manager\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the HM Display Profile Entity entity.
 *
 * @ConfigEntityType(
 *   id = "hm_display_profile",
 *   label = @Translation("HM Display Profile"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\hierarchy_manager\HmDisplayProfileListBuilder",
 *     "form" = {
 *       "add" = "Drupal\hierarchy_manager\Form\HmDisplayProfileForm",
 *       "edit" = "Drupal\hierarchy_manager\Form\HmDisplayProfileForm",
 *       "delete" = "Drupal\hierarchy_manager\Form\HmDisplayProfileDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\hierarchy_manager\HmDisplayProfileHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "hm_display_profile",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "plugin",
 *     "config",
 *     "confirm",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/hm_display_profile/{hm_display_profile}",
 *     "add-form" = "/admin/structure/hm_display_profile/add",
 *     "edit-form" = "/admin/structure/hm_display_profile/{hm_display_profile}/edit",
 *     "delete-form" = "/admin/structure/hm_display_profile/{hm_display_profile}/delete",
 *     "collection" = "/admin/structure/hm_display_profile"
 *   }
 * )
 */
class HmDisplayProfile extends ConfigEntityBase implements HmDisplayProfileInterface {

  /**
   * The HM Display Profile Entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The HM Display Profile Entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The display plugin machine name.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The configurations.
   *
   * @var string
   */
  protected $config;

  /**
   * The confirmation option.
   *
   * @var bool
   */
  protected $confirm = FALSE;

}
