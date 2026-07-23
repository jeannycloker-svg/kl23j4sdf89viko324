<?php

namespace Drupal\hierarchy_manager\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Hierarchy manager display plugin plugins.
 */
interface HmDisplayPluginInterface extends PluginInspectionInterface {

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
   *
   * @return array
   *   The form structure.
   */
  public function getForm(string $url_source, string $url_update, array &$form = [], ?FormStateInterface &$form_state = NULL, $options = NULL);

  /**
   * Build the data array that JS library accepts.
   */
  public function treeData(array $data);

}
