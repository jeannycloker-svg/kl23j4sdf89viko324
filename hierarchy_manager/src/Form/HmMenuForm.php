<?php

namespace Drupal\hierarchy_manager\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\menu_ui\MenuForm;
use Drupal\system\MenuInterface;

/**
 * Hierarchy manager menu plugin configuration form.
 */
class HmMenuForm extends MenuForm {

  /**
   * The indicator if the menu hierarchy manager is enabled.
   *
   * @var bool|null
   */
  private $isEnabled = NULL;

  /**
   * The hierarchy manager plugin type manager.
   *
   * @var \Drupal\hierarchy_manager\PluginTypeManager
   */
  private $hmPluginTypeManager = NULL;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $menu = $this->entity;
    // If the menu hierarchy manager plugin is enabled for this menu.
    // Override the menu overview form.
    if ($this->isMenuPluginEnabled($menu) && $this->loadPluginManager()) {
      // Add menu links administration form for existing menus.
      if (!$menu->isNew() || $menu->isLocked()) {
        // We are removing the menu link overview form
        // and using our own hierarchy manager tree instead.
        // The overview form implemented by Drupal Menu UI module.
        // @see \Drupal\menu_ui\MenuForm::form()
        unset($form['links']);
        $form['hm_links'] = $this->buildOverviewTree([], $form_state);
      }
    }

    return $form;
  }

  /**
   * Submit handler for the menu overview form.
   *
   * The hierarchy manager tree is a pure front-end solution in which
   * we don't need to deal with the submission data from the back-end.
   * Therefore nothing need to do,
   * if the menu hierarchy plugin is enabled.
   */
  protected function submitOverviewForm(array $complete_form, FormStateInterface $form_state) {
    if (!$this->isMenuPluginEnabled($this->entity)) {
      parent::submitOverviewForm($complete_form, $form_state);
    }
  }

  /**
   * Build a menu links overview tree element.
   *
   * @param array $form
   *   Parent form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return null|array
   *   The tree elements.
   */
  protected function buildOverviewTree(array $form, FormStateInterface $form_state) {

    $display_profile = $this->hmPluginTypeManager->getDisplayProfile('hm_setup_menu');

    if (empty($display_profile)) {
      return [];
    }

    $display_plugin_instance = $this->hmPluginTypeManager->getDisplayPluginInstance($display_profile);

    if (!empty($display_plugin_instance)) {
      if (method_exists($display_plugin_instance, 'getForm')) {
        // Menu ID.
        $mid = $this->entity->id();
        // CSRF token.
        $token = \Drupal::csrfToken()->get($mid);
        // Destination for edit link.
        $destination = $this->getDestinationArray();
        if (isset($destination['destination'])) {
          $destination = $destination['destination'];
        }
        else {
          $destination = '/';
        }
        // Urls.
        $source_url = Url::fromRoute('hierarchy_manager.menu.tree.json',
            ['mid' => $mid],
            [
              'query' =>
              [
                'token' => $token,
                'destination' => $destination,
              ],
            ])->toString();
        $update_url = Url::fromRoute('hierarchy_manager.menu.tree.update',
            ['mid' => $mid],
            ['query' => ['token' => $token]]
        )->toString();
        $config = $display_profile->get("config");
        $confirm = $display_profile->get("confirm");
        return $display_plugin_instance->getForm($source_url, $update_url, $form, $form_state, $config, $confirm);
      }
    }

    return [];
  }

  /**
   * Create a hierarchy manager plugin manager.
   *
   * @return \Drupal\hierarchy_manager\PluginTypeManager
   *   The plugin manager instance.
   */
  protected function loadPluginManager() {
    if (empty($this->hmPluginTypeManager)) {
      $this->hmPluginTypeManager = \Drupal::service('hm.plugin_type_manager');
    }

    return $this->hmPluginTypeManager;
  }

  /**
   * Check if the menu hierarchy plugin is enabled.
   *
   * @param \Drupal\system\MenuInterface $menu
   *   The menu entity.
   *
   * @return bool|null
   *   Return TRUE if the menu plugin is enabled,
   *   otherwise return FALSE.
   */
  protected function isMenuPluginEnabled(MenuInterface $menu) {
    if ($this->isEnabled === NULL) {
      if ($config = \Drupal::config('hierarchy_manager.hmconfig')) {
        if ($allowed_setup_plugins = $config->get('allowed_setup_plugins')) {
          if (!empty($allowed_setup_plugins['hm_setup_menu'])) {
            $plugin_settings = $config->get('setup_plugin_settings');
            if (!empty($plugin_settings['hm_setup_menu'])) {
              $enabled_bundles = array_keys(array_filter($plugin_settings['hm_setup_menu']['bundle']));
              if (in_array($menu->id(), $enabled_bundles)) {
                $this->isEnabled = TRUE;
              }
            }
          }
          else {
            $this->isEnabled = FALSE;
          }
        }
      }
    }

    return $this->isEnabled;
  }

}
