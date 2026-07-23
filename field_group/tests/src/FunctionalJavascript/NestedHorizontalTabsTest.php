<?php

namespace Drupal\Tests\field_group\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;

/**
 * Tests that nested horizontal tabs do not break the parent default tab.
 *
 * @see https://www.drupal.org/project/field_group/issues/3535860
 *
 * @group field_group
 */
class NestedHorizontalTabsTest extends WebDriverTestBase {

  use FieldGroupTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field_ui',
    'field_group',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The content type used for testing.
   *
   * @var string
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $admin = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'bypass node access',
    ]);
    $this->drupalLogin($admin);

    $node_type = $this->drupalCreateContentType([
      'type' => 'nested_tabs_test',
      'name' => 'Nested Tabs Test',
    ]);
    $this->type = $node_type->id();

    // One string field per leaf tab so that no tab is empty.
    $field_names = [
      'field_outer_a',
      'field_outer_b',
      'field_inner_a',
      'field_inner_b',
    ];
    foreach ($field_names as $field_name) {
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'type' => 'string',
      ])->save();
      FieldConfig::create([
        'field_storage' => FieldStorageConfig::loadByName('node', $field_name),
        'bundle' => $this->type,
        'label' => $field_name,
      ])->save();
    }

    $form_display = \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', $this->type, 'default');
    foreach ($field_names as $field_name) {
      $form_display->setComponent($field_name, [
        'type' => 'string_textfield',
        'region' => 'content',
      ]);
    }
    $form_display->save();
  }

  /**
   * Outer default tab must win even when an inner tabset is nested inside it.
   *
   * Before the fix in horizontal-tabs.js the outer tabset would compute its
   * focus ID by reading the first descendant ".horizontal-tabs-active-tab"
   * hidden input, which — because that input is appended after the panes —
   * is the *inner* tabset's input in document order. The outer set then
   * found no matching pane id and silently fell back to its first tab,
   * ignoring the configured "open" tab.
   */
  public function testNestedHorizontalTabsRespectOuterDefaultTab(): void {
    // Inner tab A — the open default within the inner tabset.
    $this->createGroup('node', $this->type, 'form', 'default', [
      'group_name' => 'group_inner_tab_a',
      'label' => 'Inner Tab A',
      'weight' => 0,
      'children' => ['field_inner_a'],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => 'Inner Tab A',
        'formatter' => 'open',
      ],
    ]);

    // Inner tab B — closed.
    $this->createGroup('node', $this->type, 'form', 'default', [
      'group_name' => 'group_inner_tab_b',
      'label' => 'Inner Tab B',
      'weight' => 1,
      'children' => ['field_inner_b'],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => 'Inner Tab B',
        'formatter' => 'closed',
      ],
    ]);

    // Inner horizontal tabs container, embedded inside outer Tab 2.
    $this->createGroup('node', $this->type, 'form', 'default', [
      'group_name' => 'group_inner_tabs',
      'label' => 'Inner tabs',
      'weight' => 0,
      'children' => ['group_inner_tab_a', 'group_inner_tab_b'],
      'format_type' => 'tabs',
      'format_settings' => [
        'direction' => 'horizontal',
        'label' => 'Inner tabs',
      ],
    ]);

    // Outer tab 1 — closed, so the outer default is not the first tab.
    $this->createGroup('node', $this->type, 'form', 'default', [
      'group_name' => 'group_outer_tab1',
      'label' => 'Outer Tab 1',
      'weight' => 0,
      'children' => ['field_outer_a'],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => 'Outer Tab 1',
        'formatter' => 'closed',
      ],
    ]);

    // Outer tab 2 — open by default and host of the inner tabset.
    $this->createGroup('node', $this->type, 'form', 'default', [
      'group_name' => 'group_outer_tab2',
      'label' => 'Outer Tab 2',
      'weight' => 1,
      'children' => ['field_outer_b', 'group_inner_tabs'],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => 'Outer Tab 2',
        'formatter' => 'open',
      ],
    ]);

    // Outer horizontal tabs container.
    $this->createGroup('node', $this->type, 'form', 'default', [
      'group_name' => 'group_outer_tabs',
      'label' => 'Outer tabs',
      'weight' => -5,
      'children' => ['group_outer_tab1', 'group_outer_tab2'],
      'format_type' => 'tabs',
      'format_settings' => [
        'direction' => 'horizontal',
        'label' => 'Outer tabs',
      ],
    ]);

    $this->drupalGet('node/add/' . $this->type);
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Wait for the horizontal-tabs JS behavior to finish wiring up: at that
    // point exactly one tab button per tabset has the .selected class.
    $assert->waitForElement(
      'css',
      '.horizontal-tab-button.selected a[href^="#edit-group-outer-tab"]'
    );
    $assert->waitForElement(
      'css',
      '.horizontal-tab-button.selected a[href^="#edit-group-inner-tab-"]'
    );

    // The configured outer default ("open") tab must be selected.
    $this->assertNotNull(
      $page->find(
        'css',
        '.horizontal-tab-button.selected a[href="#edit-group-outer-tab2"]'
      ),
      'Outer Tab 2 should be selected on initial render despite a nested horizontal tabset.'
    );

    // The outer first tab must not be selected — it would be if the outer
    // tabset incorrectly read the inner tabset's active-tab hidden input.
    $this->assertNull(
      $page->find(
        'css',
        '.horizontal-tab-button.selected a[href="#edit-group-outer-tab1"]'
      ),
      'Outer Tab 1 should not be selected when the configured default is Outer Tab 2.'
    );

    // Sanity check: the inner tabset still picks its own configured default.
    $this->assertNotNull(
      $page->find(
        'css',
        '.horizontal-tab-button.selected a[href="#edit-group-inner-tab-a"]'
      ),
      'Inner Tab A should be selected (inner tabset must be unaffected).'
    );
  }

}
