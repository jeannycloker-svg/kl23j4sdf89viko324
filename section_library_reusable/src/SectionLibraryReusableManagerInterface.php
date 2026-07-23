<?php

namespace Drupal\section_library_reusable;

use Drupal\layout_builder\OverridesSectionStorageInterface;

/**
 * Provides an interface for moving sections to reusable blocks.
 */
interface SectionLibraryReusableManagerInterface {

  /**
   * Store the section to a reusable block_content.
   *
   * @param \Drupal\layout_builder\OverridesSectionStorageInterface $section_storage
   *   The section storage.
   * @param string $delta
   *   The delta.
   * @param string $label
   *   The label of the block.
   */
  public function makeSectionReusable(OverridesSectionStorageInterface $section_storage, $delta, $label);

}
