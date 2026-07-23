<?php

namespace Drupal\metatag_test_integration\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Metatag hook implementations for metatag_test_integration.
 */
class MetatagHooks {

  /**
   * Implements hook_metatags_attachments_alter().
   */
  #[Hook('metatags_attachments_alter')]
  public function metatagsAttachmentsAlter(array &$attachments) {
    $title = "This is the title I want | [site:name] | Yeah!";
    _metatag_test_integration_replace_tag('title', \Drupal::token()->replace($title), $attachments);
  }

}
