<?php

declare(strict_types=1);

namespace Drupal\Tests\views_dependent_filters\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

// The helper lives in the procedural .module file, which isn't autoloaded.
require_once __DIR__ . '/../../../views_dependent_filters.module';

/**
 * Tests _views_dependent_filters_get_field_dependency().
 */
#[Group('views_dependent_filters')]
class FieldDependencyHelperTest extends UnitTestCase {

  /**
   * Textfield only ever get a "filled" dependency, regardless of value.
   */
  public function testTextfieldDependency(): void {
    $form = [
      'controller' => [
        '#processed' => TRUE,
        '#type' => 'textfield',
      ],
    ];
    $dependency = _views_dependent_filters_get_field_dependency($form, 'controller');
    $this->assertSame([
      [':input[name="controller"]' => ['filled' => TRUE]],
    ], $dependency);
  }

  /**
   * Checkboxes get one OR-ed condition per triggering value.
   */
  public function testCheckboxesDependencyWithExplicitValues(): void {
    $form = [
      'controller' => [
        '#processed' => TRUE,
        '#type' => 'checkboxes',
      ],
    ];
    $dependency = _views_dependent_filters_get_field_dependency($form, 'controller', ['1' => '1', '2' => '2']);
    $this->assertSame([
      [':input[name="controller[1]"]' => ['checked' => TRUE]],
      [':input[name="controller[2]"]' => ['checked' => TRUE]],
    ], $dependency);
  }

  /**
   * With no triggering values given, all of the field's own options apply.
   */
  public function testCheckboxesDependencyDefaultsToAllOptions(): void {
    $form = [
      'controller' => [
        '#processed' => TRUE,
        '#type' => 'checkboxes',
        '#options' => ['All' => 'All', '1' => 'One', '2' => 'Two'],
      ],
    ];
    $dependency = _views_dependent_filters_get_field_dependency($form, 'controller');
    $this->assertSame([
      [':input[name="controller[1]"]' => ['checked' => TRUE]],
      [':input[name="controller[2]"]' => ['checked' => TRUE]],
    ], $dependency);
  }

  /**
   * Radios get one condition per triggering value, matched by value.
   */
  public function testRadiosDependency(): void {
    $form = [
      'controller' => [
        '#processed' => TRUE,
        '#type' => 'radios',
      ],
    ];
    $dependency = _views_dependent_filters_get_field_dependency($form, 'controller', ['a' => 'a']);
    $this->assertSame([
      [':input[name="controller"]' => ['value' => 'a']],
    ], $dependency);
  }

  /**
   * Single-value selects behave like radios.
   */
  public function testSingleSelectDependency(): void {
    $form = [
      'controller' => [
        '#processed' => TRUE,
        '#type' => 'select',
        '#multiple' => FALSE,
      ],
    ];
    $dependency = _views_dependent_filters_get_field_dependency($form, 'controller', ['a' => 'a', 'b' => 'b']);
    $this->assertSame([
      [':input[name="controller"]' => ['value' => 'a']],
      [':input[name="controller"]' => ['value' => 'b']],
    ], $dependency);
  }

  /**
   * Multi-value selects are collapsed into a single OR-ed condition.
   */
  public function testMultipleSelectDependency(): void {
    $form = [
      'controller' => [
        '#processed' => TRUE,
        '#type' => 'select',
        '#multiple' => TRUE,
      ],
    ];
    $dependency = _views_dependent_filters_get_field_dependency($form, 'controller', ['1' => '1', '2' => '2']);
    $this->assertSame([
      [
        ':input[name="controller[]"]' => [
          ['value' => ['1']],
          ['value' => ['2']],
        ],
      ],
    ], $dependency);
  }

  /**
   * Unprocessed fields (not yet present in the form) yield no dependency.
   */
  public function testUnprocessedFieldReturnsEmptyDependency(): void {
    $form = [
      'controller' => [
        '#processed' => FALSE,
        '#type' => 'textfield',
      ],
    ];
    $dependency = _views_dependent_filters_get_field_dependency($form, 'controller');
    $this->assertSame([], $dependency);
  }

  /**
   * Unsupported widget types are skipped and a warning is emitted instead.
   */
  public function testUnsupportedTypeEmitsWarning(): void {
    $messenger = $this->createMock(MessengerInterface::class);
    $messenger->expects($this->once())->method('addWarning');

    $container = new ContainerBuilder();
    $container->set('messenger', $messenger);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $form = [
      'controller' => [
        '#processed' => TRUE,
        '#type' => 'date',
      ],
    ];
    $dependency = _views_dependent_filters_get_field_dependency($form, 'controller');
    $this->assertSame([], $dependency);
  }

}
