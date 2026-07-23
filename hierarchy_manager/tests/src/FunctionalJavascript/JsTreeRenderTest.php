<?php

namespace Drupal\Tests\hierarchy_manager\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the JSTree rendering in the taxonomy manage page.
 *
 * @group hierarchy_manager
 */
class JsTreeRenderTest extends WebDriverTestBase {

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'taxonomy',
    'hierarchy_manager',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test term array.
   *
   * @var array
   */
  protected $testTerms = [
    'Term 1',
    'Term 2',
    'Term 3',
  ];

  /**
   * Set up the test environment.
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a user with necessary permissions.
    $this->adminUser = $this->drupalCreateUser([
      'administer taxonomy',
      'access taxonomy overview',
      'administer site configuration',
    ]);

    // Log in the user.
    $this->drupalLogin($this->adminUser);

    // Create a taxonomy vocabulary.
    Vocabulary::create([
      'vid' => 'tags',
      'description' => 'A vocabulary for testing.',
      'name' => 'Tags',
    ])->save();

    // Add terms to the 'Tags' vocabulary.
    foreach ($this->testTerms as $term_name) {
      Term::create([
        'vid' => 'tags',
        'name' => $term_name,
      ])->save();
    }

    // Set up the JsTree profile.
    $this->drupalGet('/admin/structure/hm_display_profile/add');
    $this->submitForm(['label' => 'test jstree'], 'Save');
  }

  /**
   * Test the JSTree rendering.
   */
  public function testJsTreeRendering() {
    $assertSession = $this->assertSession();
    // Manage taxonomy by using JsTree.
    $this->drupalGet('/admin/config/user-interface/hierarchy_manager/config');
    $edit = [
      'hm_allowed_setup_plugins[hm_setup_taxonomy]' => 'checked',
      'setup_plugin_settings[hm_setup_taxonomy][bundle][tags]' => 'checked',
    ];

    $this->submitForm($edit, 'Save configuration');

    // Navigate to the taxonomy manage page.
    $this->drupalGet('/admin/structure/taxonomy/manage/tags/overview');

    // Wait for the JSTree to be fully initialized and rendered.
    $assertSession->waitForElement('css', 'div.jstree-default');

    $term_tree_items = [];
    // Wait for all tree items.
    foreach ($this->testTerms as $term_name) {
      $term_tree_items[] = $assertSession->waitForLink($term_name);
    }
    // Check if all tree item exist.
    foreach ($term_tree_items as $item) {
      $this->assertNotEmpty($item);
    }
    // Check if the description text.
    $assertSession->pageTextContains('Click an item to edit it. Drag and drop items to change their position in the tree.');
  }

}
