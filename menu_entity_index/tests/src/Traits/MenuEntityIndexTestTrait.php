<?php

namespace Drupal\Tests\menu_entity_index\Traits;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Provides common methods for functional testing of Menu Entity Index module.
 */
trait MenuEntityIndexTestTrait {

  /**
   * Creates a new menu link to an entity.
   *
   * @param ?\Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to link to, if any.
   * @param ?\Drupal\menu_link_content\Entity\MenuLinkContent $parent
   *   Parent menu item, if any.
   * @param string $menu_name
   *   Machine-readable menu name. Defaults to 'main'.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   The menu link content entity for the new menu link.
   */
  protected function addMenuLink(?ContentEntityInterface $entity = NULL, ?MenuLinkContent $parent = NULL, string $menu_name = 'main'): MenuLinkContent {
    $this->drupalGet('admin/structure/menu/manage/' . $menu_name . '/add');
    $this->assertSession()->statusCodeEquals(200);

    $title = '!link_' . $this->randomMachineName(16);
    $edit = [
      'link[0][uri]' => is_object($entity) ? '/' . $entity->toUrl('canonical')->getInternalPath() : '<front>',
      'title[0][value]' => $title,
      'description[0][value]' => '',
      'enabled[value]' => 1,
      'expanded[value]' => 0,
      'menu_parent' => $menu_name . ':' . (is_object($parent) ? $parent->getPluginId() : ''),
      'weight[0][value]' => 10,
    ];
    // Add menu link.
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The menu link has been saved.');

    $menu_links = \Drupal::entityTypeManager()
      ->getStorage('menu_link_content')
      ->loadByProperties([
        'title' => $title,
      ]);
    $menu_link = reset($menu_links);
    self::assertInstanceOf(MenuLinkContent::class, $menu_link);

    return $menu_link;
  }

  /**
   * Asserts presence of form widget showing no menu links.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $entity
   *   The menu link content entity.
   * @param \Drupal\Core\Entity\ContentEntityInterface $target_entity
   *   The target entity.
   */
  protected function assertEmptyFormWidget(MenuLinkContent $entity, ContentEntityInterface $target_entity): void {
    $this->drupalGet($target_entity->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Referenced by 0 menu links');
    $details = $this->assertSession()->elementExists('xpath', '//details[@data-drupal-selector="edit-menu-entity-index"]');
    $none_column = $details->find('xpath', '//table/tbody/tr/td[@colspan="4" and text()="- None -"]');
    self::assertNotNull($none_column);
  }

  /**
   * Asserts that the Menu Entity Index database table is empty.
   */
  protected function assertEmptyTable(): void {
    $row_count = Database::getConnection()
      ->select('menu_entity_index')
      ->countQuery()
      ->execute()
      ->fetchField();
    self::assertSame('0', $row_count);
  }

  /**
   * Asserts an empty view result for a target entity and a parent entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $target_entity
   *   The target entity.
   * @param ?\Drupal\menu_link_content\Entity\MenuLinkContent $parent_entity
   *   The parent menu link content entity, if any.
   */
  protected function assertEmptyViewResult(ContentEntityInterface $target_entity, ?MenuLinkContent $parent_entity = NULL): void {
    $result = $this->getViewResult($target_entity, $parent_entity);
    self::assertCount(0, $result);
  }

  /**
   * Asserts presence of form widget showing a specific menu link.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $entity
   *   The menu link content entity.
   * @param \Drupal\Core\Entity\ContentEntityInterface $target_entity
   *   The target entity.
   * @param ?\Drupal\menu_link_content\Entity\MenuLinkContent $parent
   *   The parent menu link content entity, if any.
   * @param int $expected_link_count
   *   Total number of links to expect, defaults to 1.
   */
  protected function assertFormWidget(MenuLinkContent $entity, ContentEntityInterface $target_entity, ?MenuLinkContent $parent = NULL, int $expected_link_count = 1): void {
    $this->drupalGet($target_entity->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);

    // Assert correct menu link count.
    if ($expected_link_count === 1) {
      $this->assertSession()->pageTextContains('Referenced by 1 menu link');
    }
    else {
      $this->assertSession()->pageTextContains('Referenced by ' . $expected_link_count . ' menu links');
    }

    // Get details element of form widget.
    $details = $this->assertSession()->elementExists('xpath', '//details[@data-drupal-selector="edit-menu-entity-index"]');
    self::assertNotNull($details);

    // Get correct table row for menu link by href and label.
    // @note Tests may be executed in a subdirectory (e.g. by Drupal CI). Run
    // URL through \Drupal\Core\Url to get the correct URL in use.
    $label_column = $details->find('xpath', '//table/tbody/tr/td/a[@href="' . Url::fromUserInput('/admin/structure/menu/item/' . $entity->id() . '/edit', [
      'language' => $entity->language(),
    ])->toString() . '" and text()="' . $entity->label() . '"]');
    self::assertNotNull($label_column);
    $row = $label_column->find('xpath', '/../..');
    self::assertNotNull($row);
    self::assertEquals('tr', $row->getTagName());

    // Check other columns of row.
    $menu = \Drupal::service('entity_type.manager')
      ->getStorage('menu')
      ->load($entity->getMenuName());
    self::assertNotNull($menu);
    $menu_column = $row->find('xpath', '/td[text()="' . $menu->label() . '"]');
    self::assertNotNull($menu_column);
    $level_column = $row->find('xpath', '/td[text()="' . (is_object($parent) ? '1' : '0') . '"]');
    self::assertNotNull($level_column);
    $language_column = $row->find('xpath', '/td[text()="' . $entity->language()->getName() . '"]');
    self::assertNotNull($language_column);
  }

  /**
   * Asserts form widget not shown.
   */
  protected function assertNoFormWidget(ContentEntityInterface $target_entity): void {
    $this->drupalGet($target_entity->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementNotExists('xpath', '//details[@data-drupal-selector="edit-menu-entity-index"]');
  }

  /**
   * Asserts that a row is present in Menu Entity Index database table.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $entity
   *   The menu link content entity.
   * @param \Drupal\Core\Entity\ContentEntityInterface $target_entity
   *   The target entity.
   * @param ?\Drupal\menu_link_content\Entity\MenuLinkContent $parent
   *   The parent menu link content entity, if any.
   */
  protected function assertTableRow(MenuLinkContent $entity, ContentEntityInterface $target_entity, ?MenuLinkContent $parent = NULL): void {
    $query = Database::getConnection()
      ->select('menu_entity_index', 'mei')
      ->fields('mei', [
        'menu_name',
        'level',
        'entity_type',
        'entity_subtype',
        'entity_id',
        'entity_uuid',
        'parent_type',
        'parent_id',
        'parent_uuid',
        'langcode',
        'target_type',
        'target_subtype',
        'target_id',
        'target_uuid',
        'target_langcode',
      ])
      ->condition('menu_name', $entity->getMenuName())
      ->condition('level', (is_object($parent) ? 1 : 0))
      ->condition('entity_id', $entity->id())
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_subtype', $entity->bundle())
      ->condition('entity_uuid', $entity->uuid())
      ->condition('parent_type', (is_object($parent) ? $parent->getEntityTypeId() : ''))
      ->condition('parent_id', (is_object($parent) ? $parent->id() : NULL), (is_object($parent) ? '=' : 'IS NULL'))
      ->condition('parent_uuid', (is_object($parent) ? $parent->uuid() : ''))
      ->condition('langcode', $entity->language()->getId())
      ->condition('target_type', $target_entity->getEntityTypeId())
      ->condition('target_subtype', $target_entity->bundle())
      ->condition('target_id', $target_entity->id())
      ->condition('target_uuid', $target_entity->uuid())
      ->condition('target_langcode', $target_entity->language()->getId());
    $result = (clone $query)->execute();
    $row_count = $query
      ->countQuery()
      ->execute()
      ->fetchField();
    if ($row_count != 1) {
      $rows = [];
      while ($record = $result->fetchAssoc()) {
        $rows[] = $record;
      }
      self::assertSame([], $rows);
    }
    self::assertSame('1', $row_count);
  }

  /**
   * Asserts a view result for an entity, a target entity and parent entity.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $entity
   *   The menu link content entity.
   * @param \Drupal\Core\Entity\ContentEntityInterface $target_entity
   *   The target entity.
   * @param ?\Drupal\menu_link_content\Entity\MenuLinkContent $parent_entity
   *   The parent menu link content entity, if any.
   */
  protected function assertViewResult(MenuLinkContent $entity, ContentEntityInterface $target_entity, ?MenuLinkContent $parent_entity = NULL): void {
    $result = $this->getViewResult($target_entity, $this->expectParentRelationship($parent_entity) ? $parent_entity : NULL);
    self::assertCount(1, $result);
    $row = $result[0];

    // Target entity via view.
    self::assertSame($target_entity->getEntityTypeId(), $row->_entity->getEntityTypeId());
    self::assertSame($target_entity->id(), $row->_entity->id());
    self::assertSame($target_entity->language()->getId(), $row->_entity->language()->getId());
    self::assertSame($target_entity->id(), $row->{$target_entity->getEntityType()->getKey('id')});
    self::assertSame($target_entity->language()->getId(), $row->{$target_entity->getEntityType()->getDataTable() . '_langcode'});

    // Entity via MEI table.
    self::assertSame((is_object($parent_entity) ? '1' : '0'), $row->menu_link_content_data_menu_entity_index__menu_entity_index_);
    self::assertSame($entity->id(), $row->menu_link_content_data_menu_entity_index__menu_entity_index__1);
    self::assertSame($entity->language()->getId(), $row->menu_link_content_data_menu_entity_index__menu_entity_index__2);
    self::assertSame($entity->uuid(), $row->menu_link_content_data_menu_entity_index__menu_entity_index__3);
    self::assertSame($entity->getEntityTypeId(), $row->menu_link_content_data_menu_entity_index__menu_entity_index__4);
    self::assertSame($entity->bundle(), $row->menu_link_content_data_menu_entity_index__menu_entity_index__5);
    self::assertSame($entity->getMenuName(), $row->menu_link_content_data_menu_entity_index__menu_entity_index__6);

    // Parent entity via MEI table.
    self::assertSame((is_object($parent_entity) ? $parent_entity->id() : NULL), $row->menu_link_content_data_menu_entity_index__menu_entity_index__7);
    self::assertSame((is_object($parent_entity) ? $parent_entity->getEntityTypeId() : ''), $row->menu_link_content_data_menu_entity_index__menu_entity_index__8);
    self::assertSame((is_object($parent_entity) ? $parent_entity->uuid() : ''), $row->menu_link_content_data_menu_entity_index__menu_entity_index__9);

    // Target entity via MEI table.
    self::assertSame($target_entity->id(), $row->menu_link_content_data_menu_entity_index__menu_entity_index__10);
    self::assertSame($target_entity->language()->getId(), $row->menu_link_content_data_menu_entity_index__menu_entity_index__11);
    self::assertSame($target_entity->bundle(), $row->menu_link_content_data_menu_entity_index__menu_entity_index__12);
    self::assertSame($target_entity->getEntityTypeId(), $row->menu_link_content_data_menu_entity_index__menu_entity_index__13);
    self::assertSame($target_entity->uuid(), $row->menu_link_content_data_menu_entity_index__menu_entity_index__14);

    // Menu link content entity data via MEI table.
    if ($target_entity->getEntityTypeId() === 'node') {
      self::assertSame($entity->getMenuName(), $row->menu_link_content_data_menu_entity_index_menu_name);
      self::assertSame($entity->language()->getId(), $row->menu_link_content_data_menu_entity_index_langcode);
    }

    // Relationship ids via MEI table.
    self::assertSame($entity->id(), $row->menu_link_content_data_menu_entity_index_id);
    self::assertSame((is_object($parent_entity) ? $parent_entity->id() : NULL), $row->menu_link_content_data_menu_entity_index_1_id);
  }

  /**
   * Add form widget to default form display of an entity type bundle.
   *
   * @param string $entity_type_id
   *   Entity type id to add widget to.
   * @param string $bundle
   *   Bundle to add widget to.
   * @param bool $needs_reset
   *   Whether the caches need to be cleared so that the component becomes
   *   available. Usually needed when newly tracking an entity type. Defaults to
   *   FALSE.
   */
  protected function configureFormWidget(string $entity_type_id, string $bundle, bool $needs_reset = FALSE): void {
    if ($needs_reset) {
      $this->resetAll();
    }

    // Add form widget to form display.
    $form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($entity_type_id . '.' . $bundle . '.default')
      ->setComponent('menu_entity_index', [])
      ->save();
  }

  /**
   * Deletes an existing menu link.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $entity
   *   Menu link content entity to delete.
   */
  protected function deleteMenuLink(MenuLinkContent $entity): void {
    $this->drupalGet('/admin/structure/menu/item/' . $entity->id() . '/delete');
    $this->submitForm([], 'Delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The menu link ' . $entity->label() . ' has been deleted.');
  }

  /**
   * Edits an existing menu link.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $entity
   *   Menu link content entity to edit.
   * @param array<string,mixed> $edit
   *   Field data in an associative array, if any. Changes the current input
   *   fields (where possible) to the values indicated. A checkbox can be set to
   *   TRUE to be checked and should be set to FALSE to be unchecked.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   Updated menu link content entity.
   */
  protected function editMenuLink(MenuLinkContent $entity, array $edit = []): MenuLinkContent {
    $this->drupalGet('/admin/structure/menu/item/' . $entity->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The menu link has been saved.');

    \Drupal::entityTypeManager()
      ->getStorage('menu_link_content')
      ->resetCache([$entity->id()]);
    $entity = \Drupal::entityTypeManager()
      ->getStorage('menu_link_content')
      ->load($entity->id());
    self::assertInstanceOf(MenuLinkContent::class, $entity);

    return $entity;
  }

  /**
   * Whether to expect a views relationship to a parent entity.
   *
   * If the parent menu link does not link to a tracked entity type, there won't
   * be a views relationship from the child menu item to the parent menu item.
   * This method checks whether a relationship should be expected.
   *
   * @param ?\Drupal\menu_link_content\Entity\MenuLinkContent $parent_entity
   *   The parent entity.
   *
   * @return bool
   *   Whether to expect a views relationship to a parent entity.
   */
  protected function expectParentRelationship(?MenuLinkContent $parent_entity = NULL): bool {
    if (!is_object($parent_entity)) {
      return FALSE;
    }
    $row_count = Database::getConnection()
      ->select('menu_entity_index')
      ->condition('entity_id', $parent_entity->id())
      ->countQuery()
      ->execute()
      ->fetchField();
    return $row_count > 0;
  }

  /**
   * Gets test view result for a target entity and an optional parent entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $target_entity
   *   The target entity.
   * @param ?\Drupal\menu_link_content\Entity\MenuLinkContent $parent_entity
   *   The parent menu link content entity, if any.
   *
   * @return array<int,\Drupal\views\ResultRow>
   *   The view result.
   */
  protected function getViewResult(ContentEntityInterface $target_entity, ?MenuLinkContent $parent_entity = NULL) {
    $view_storage = \Drupal::service('entity_type.manager')
      ->getStorage('view')
      ->load('test_mei_' . $target_entity->getEntityTypeId());
    $view_storage->invalidateCaches();
    $view = $view_storage->getExecutable();
    $view->initDisplay();
    $view->setDisplay('default');
    if (is_object($parent_entity)) {
      $view->preExecute([$parent_entity->id()]);
    }
    else {
      $view->preExecute([]);
    }
    $view->execute();
    self::assertTrue($view->executed);
    $result = $view->result;
    $view->destroy();
    return $result;
  }

  /**
   * Reconfigure Menu Entity Index module.
   *
   * @param array<string, mixed> $edit
   *   Field data in an associative array, if any. Changes the current input
   *   fields (where possible) to the values indicated. A checkbox can be set to
   *   TRUE to be checked and should be set to FALSE to be unchecked.
   * @param string $button_text
   *   Text of button to click. Defaults to 'Save configuration'.
   */
  protected function reconfigure(array $edit = [], string $button_text = 'Save configuration'): void {
    $this->drupalGet('/admin/config/search/menu_entity_index');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($edit, $button_text);
    $this->checkForMetaRefresh();
    $this->assertSession()->statusCodeEquals(200);
    if ($button_text === 'Rebuild index') {
      $this->assertSession()->pageTextContains('The index has been rebuilt.');
    }
    else {
      $this->assertSession()->pageTextContains('The configuration options have been saved.');
    }
  }

}
