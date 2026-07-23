<?php

declare(strict_types=1);

namespace Drupal\Tests\views_dependent_filters\Kernel;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the views_dependent_filter plugin using a real fixture view.
 *
 * Uses the "vdf_test" view shipped by the views_dependent_filters_test
 * module: a node listing with an exposed "Vocab1" taxonomy filter acting as
 * the controller for a dependent "Vocab2" taxonomy filter.
 */
#[Group('views_dependent_filters')]
#[RunTestsInSeparateProcesses]
class ViewsDependentFilterTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'system',
    'views',
    'views_test_config',
    'views_test_data',
    'user',
    'node',
    'field',
    'taxonomy',
    'text',
    'views_dependent_filters',
    'views_dependent_filters_test',
  ];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['vdf_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    // Import the views_dependent_filters_test module's fixture view rather
    // than the generic views_test_config one used by the parent class.
    parent::setUp(FALSE);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig([
      'node',
      'taxonomy',
      'views_dependent_filters_test',
    ]);

    ViewTestData::createTestViews(static::class, ['views_dependent_filters_test']);
  }

  /**
   * Creates a vdf_test_cont node referencing the given vocab1/vocab2 terms.
   */
  protected function createTestNode(string $title, ?Term $vocab1_term = NULL, ?Term $vocab2_term = NULL): Node {
    $node = Node::create([
      'type' => 'vdf_test_cont',
      'title' => $title,
      'field_vocab1' => $vocab1_term ? [$vocab1_term->id()] : [],
      'field_vocab2' => $vocab2_term ? [$vocab2_term->id()] : [],
    ]);
    $node->save();
    return $node;
  }

  /**
   * Tests that the handler only offers filters before/after it as options.
   */
  public function testGetFilterOptions(): void {
    $view = Views::getView('vdf_test');
    $view->setDisplay();
    $view->initHandlers();
    /** @var \Drupal\views_dependent_filters\Plugin\views\filter\ViewsDependentFilter $handler */
    $handler = $view->filter['views_dependent_filter'];

    $this->assertSame(['field_vocab1_target_id' => 'field_vocab1_target_id'], $handler->getFilterOptions('controller'));
    $this->assertSame(['field_vocab2_target_id' => 'field_vocab2_target_id'], $handler->getFilterOptions('dependent'));
  }

  /**
   * Tests the admin summary text for the configured handler.
   */
  public function testAdminSummary(): void {
    $view = Views::getView('vdf_test');
    $view->setDisplay();
    $view->initHandlers();
    /** @var \Drupal\views_dependent_filters\Plugin\views\filter\ViewsDependentFilter $handler */
    $handler = $view->filter['views_dependent_filter'];

    $this->assertSame('field_vocab1_target_id controlling field_vocab2_target_id', (string) $handler->adminSummary());
  }

  /**
   * Tests that the rendered exposed form marks the dependent field's states.
   */
  public function testExposedFormStatesReflectDependency(): void {
    $vocab1_term = Term::create(['vid' => 'vocab1', 'name' => 'Vocab1 term']);
    $vocab1_term->save();
    // A vocab2 term needs to exist so field_vocab2_target_id's select widget
    // has an option to hold its own "stay visible once selected" dependency.
    $vocab2_term = Term::create(['vid' => 'vocab2', 'name' => 'Vocab2 term']);
    $vocab2_term->save();

    $view = Views::getView('vdf_test');
    $view->setDisplay();
    $filters = $view->displayHandlers->get('default')->getOption('filters');
    $filters['views_dependent_filter']['controller_values'] = [$vocab1_term->id() => $vocab1_term->id()];
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);

    $this->executeView($view);
    /** @var \Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase $exposed_form */
    $exposed_form = $view->display_handler->getPlugin('exposed_form');
    $form = $exposed_form->renderExposedForm();

    // PHP coerces the numeric-string controller/option values into integer
    // array keys when they round-trip through the "values" lookups in
    // views_dependent_filters_exposed_form_after_build().
    $this->assertSame([
      [':input[name="field_vocab1_target_id"]' => ['value' => (int) $vocab1_term->id()]],
      [':input[name="field_vocab2_target_id"]' => ['value' => (int) $vocab2_term->id()]],
    ], $form['field_vocab2_target_id']['#states']['visible']);
  }

  /**
   * Tests that the dependent field is not shown for a non-matching value.
   *
   * The #states API is evaluated client-side by JavaScript, so a Kernel test
   * can't observe actual DOM visibility. What we can verify server-side is
   * that the "visible" conditions are scoped only to the configured trigger
   * term: a term that was never selected as a controller_values trigger
   * never appears in the conditions, so selecting it in the browser could
   * not satisfy the "visible" rule and the dependent field would stay
   * hidden.
   */
  public function testDependentFieldNotVisibleForNonMatchingControllerValue(): void {
    $vocab1_term_selected = Term::create(['vid' => 'vocab1', 'name' => 'Vocab1 selected']);
    $vocab1_term_selected->save();
    $vocab1_term_other = Term::create(['vid' => 'vocab1', 'name' => 'Vocab1 other']);
    $vocab1_term_other->save();

    $view = Views::getView('vdf_test');
    $view->setDisplay();
    $filters = $view->displayHandlers->get('default')->getOption('filters');
    // Only the "selected" term triggers visibility of the dependent field.
    $filters['views_dependent_filter']['controller_values'] = [
      $vocab1_term_selected->id() => $vocab1_term_selected->id(),
    ];
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);

    $this->executeView($view);
    /** @var \Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase $exposed_form */
    $exposed_form = $view->display_handler->getPlugin('exposed_form');
    $form = $exposed_form->renderExposedForm();

    $visible_conditions = $form['field_vocab2_target_id']['#states']['visible'];

    $this->assertContains(
      [':input[name="field_vocab1_target_id"]' => ['value' => (int) $vocab1_term_selected->id()]],
      $visible_conditions,
      'The configured controller value is present as a visibility trigger.'
    );
    $this->assertNotContains(
      [':input[name="field_vocab1_target_id"]' => ['value' => (int) $vocab1_term_other->id()]],
      $visible_conditions,
      'A controller value that was never configured as a trigger must not be able to reveal the dependent field.'
    );
  }

  /**
   * Tests that the view returns the correct nodes for submitted filters.
   *
   * This proves the fake views_dependent_filter handler (whose query()
   * does nothing) does not interfere with the real, adjacent taxonomy
   * filters when the exposed form is actually submitted.
   */
  public function testResultSetRespectsSubmittedFilterValues(): void {
    $vocab1_term_a = Term::create(['vid' => 'vocab1', 'name' => 'Vocab1 A']);
    $vocab1_term_a->save();
    $vocab1_term_b = Term::create(['vid' => 'vocab1', 'name' => 'Vocab1 B']);
    $vocab1_term_b->save();
    $vocab2_term = Term::create(['vid' => 'vocab2', 'name' => 'Vocab2 term']);
    $vocab2_term->save();

    $this->createTestNode('Matches both filters', $vocab1_term_a, $vocab2_term);
    $this->createTestNode('Matches only vocab1', $vocab1_term_a, NULL);
    $this->createTestNode('Matches neither', $vocab1_term_b, NULL);

    $view = Views::getView('vdf_test');
    $view->setExposedInput([
      'field_vocab1_target_id' => $vocab1_term_a->id(),
      'field_vocab2_target_id' => $vocab2_term->id(),
    ]);
    $this->executeView($view);

    $this->assertIdenticalResultset(
      $view,
      [['title' => 'Matches both filters']],
      ['title' => 'title']
    );
  }

}
