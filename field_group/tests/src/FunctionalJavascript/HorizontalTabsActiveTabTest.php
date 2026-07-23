<?php

namespace Drupal\Tests\field_group\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;

/**
 * Tests horizontal tabs active-tab resolution.
 *
 * @group field_group
 */
class HorizontalTabsActiveTabTest extends WebDriverTestBase {

  use FieldGroupTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'field_group',
    'node',
    'user',
  ];

  /**
   * The test node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $testNodeType;

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The test node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $testNode;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->testNodeType = $this->drupalCreateContentType([
      'type' => 'test_node_bundle',
      'name' => 'Test Node Type',
    ]);

    $fields = [
      'test_non_required' => [
        'label' => 'Test Non Required',
        'required' => FALSE,
      ],
      'test_required' => [
        'label' => 'Test Required',
        'required' => TRUE,
      ],
    ];

    // Create fields for the test node type.
    foreach ($fields as $field_name => $field_info) {
      $field_storage = $this->container->get('entity_type.manager')
        ->getStorage('field_storage_config')
        ->create([
          'field_name' => $field_name,
          'entity_type' => 'node',
          'type' => 'string',
        ]);
      $field_storage->save();

      $this->container->get('entity_type.manager')
        ->getStorage('field_config')
        ->create([
          'label' => $field_info['label'],
          'field_storage' => $field_storage,
          'bundle' => $this->testNodeType->id(),
          'required' => $field_info['required'],
        ])
        ->save();
    }

    $data = [
      'label' => 'Tab 1',
      'group_name' => 'group_tab1',
      'weight' => '1',
      'children' => [
        0 => 'test_non_required',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => 'Tab 1',
        'formatter' => 'open',
      ],
    ];
    $this->createGroup('node', $this->testNodeType->id(), 'form', 'default', $data);

    $data = [
      'label' => 'Tab 2',
      'group_name' => 'group_tab2',
      'weight' => '2',
      'children' => [
        0 => 'test_required',
      ],
      'format_type' => 'tab',
      'format_settings' => [
        'label' => 'Tab 2',
        'formatter' => 'closed',
      ],
    ];
    $this->createGroup('node', $this->testNodeType->id(), 'form', 'default', $data);

    $horizontal_tabs = [
      'label' => 'Horizontal tabs',
      'group_name' => 'group_horizontal_tabs',
      'weight' => '-5',
      'children' => [
        'group_tab1',
        'group_tab2',
      ],
      'format_type' => 'tabs',
      'format_settings' => [
        'direction' => 'horizontal',
        'label' => 'Horizontal tabs',
      ],
    ];
    $this->createGroup('node', $this->testNodeType->id(), 'form', 'default', $horizontal_tabs);
    \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load(implode('.', [
        'node',
        $this->testNodeType->id(),
        'default',
      ]))
      ->setComponent('test_non_required', ['weight' => 1])
      ->setComponent('test_required', ['weight' => 2])
      ->save();

    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'create test_node_bundle content',
      'edit any test_node_bundle content',
    ]);

    $this->testNode = $this->createNode([
      'type' => $this->testNodeType->id(),
      'title' => 'Test Active Tab By Fragment',
    ]);
  }

  /**
   * Test active tab by fragment.
   */
  public function testActiveTabByFragment() {
    $this->drupalLogin($this->adminUser);
    $session = $this->assertSession();

    // Visit the form with the first tab active by default.
    $this->drupalGet('node/' . $this->testNode->id() . '/edit');
    $session->elementExists('xpath', '//li[@data-horizontaltabbutton="0" and contains(@class, "selected")]');
    $session->elementExists('xpath', '//li[@data-horizontaltabbutton="1" and not(contains(@class, "selected"))]');

    // Move away from the page to ensure the session is reset.
    $this->drupalGet('node/' . $this->testNode->id());
    $this->drupalGet('node/' . $this->testNode->id() . '/edit', [
      'fragment' => 'edit-group-tab2',
    ]);

    // Assert that the second tab is the active one.
    $session->elementExists('xpath', '//li[@data-horizontaltabbutton="1" and contains(@class, "selected")]');
    $session->elementExists('xpath', '//li[@data-horizontaltabbutton="0" and not(contains(@class, "selected"))]');

    // Visit the form with an invalid fragment.
    $this->drupalGet('node/' . $this->testNode->id());
    $this->drupalGet('node/' . $this->testNode->id() . '/edit', [
      'fragment' => 'invalid-fragment',
    ]);
    // Assert that the first tab is active by default.
    $session->elementExists('xpath', '//li[@data-horizontaltabbutton="0" and contains(@class, "selected")]');
    $session->elementExists('xpath', '//li[@data-horizontaltabbutton="1" and not(contains(@class, "selected"))]');
  }

  /**
   * Test that required fields affect the active tab.
   *
   * After a form rebuild triggered by a validation error on the second tab,
   * the tab containing the error must become active. This exercises the
   * end-to-end restoration of the previously-active tab on rebuild; the
   * URL here carries no fragment, so the focusID fallback in
   * horizontal-tabs.js must resolve to tab 2 rather than the first pane.
   */
  public function testRequiredFieldsActiveTab() {
    $this->drupalLogin($this->adminUser);
    $session = $this->assertSession();
    $this->drupalGet('node/' . $this->testNode->id() . '/edit');
    $session->elementExists('xpath', '//li[@data-horizontaltabbutton="0" and contains(@class, "selected")]');

    // Submitting the form should change the active tab to the second one,
    // since it is the first tab with an unfilled required field.
    $this->submitForm([], 'Save');
    $this->assertStringNotContainsString('#edit-group-tab', $this->getSession()->getCurrentUrl());
    $session->elementExists('xpath', '//li[@data-horizontaltabbutton="1" and contains(@class, "selected")]');
  }

  /**
   * Test that the URL updates when switching tabs.
   */
  public function testUrlUpdate() {
    $this->drupalLogin($this->adminUser);
    $session = $this->assertSession();

    // Visit the form with the first tab active by default.
    $this->drupalGet('node/' . $this->testNode->id() . '/edit');
    $session->elementExists('xpath', '//li[@data-horizontaltabbutton="0" and contains(@class, "selected")]');

    // Switch to the second tab.
    $this->getSession()->getPage()->clickLink('Tab 2');

    // Assert that the URL has been updated with the correct fragment.
    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertStringContainsString('#edit-group-tab2', $current_url);
    // Assert that the second tab is now active.
    $session->elementExists('xpath', '//li[@data-horizontaltabbutton="1" and contains(@class, "selected")]');

    // Click the first tab again.
    $this->getSession()->getPage()->clickLink('Tab 1');
    // Assert that the URL has been updated with the first tab's fragment.
    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertStringContainsString('#edit-group-tab1', $current_url);
    // Assert that the first tab is now active.
    $session->elementExists('xpath', '//li[@data-horizontaltabbutton="0" and contains(@class, "selected")]');
  }

}
