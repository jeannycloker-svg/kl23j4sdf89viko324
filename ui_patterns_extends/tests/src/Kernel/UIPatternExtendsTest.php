<?php

namespace Drupal\Tests\ui_patterns_extends\Kernel;

use Drupal\Tests\ui_patterns\Kernel\AbstractUiPatternsTest;
use Drupal\ui_patterns\UiPatterns;

/**
 * Tests UIPatternExtends.
 *
 * @group ui_patterns_extends
 */
class UIPatternExtendsTest extends AbstractUiPatternsTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'ui_patterns',
    'ui_patterns_library',
    'ui_patterns_extends',
    'ui_patterns_extends_test',
  ];

  /**
   * Return the email validation dataProvider based on yaml fixture file.
   *
   * @return array
   *   - pattern_name
   *   - fields count
   *   - settings count
   */
  public function patternExpectedDataProvider() {
    return [
      ['foo_settings_setting', 0, 1],
      ['foo_complete', 2, 2],
      ['foo_settings', 0, 2],
      ['foo_fields', 2, 0],
      ['foo_hierachy', 2, 2],
    ];
  }

  /**
   * Test extends.
   *
   * @dataProvider patternExpectedDataProvider
   *
   * @covers ::getDerivativeDefinitions
   */
  public function testExtendComplete($pattern_id, $fields_count, $settings_count) {
    UiPatterns::getManager()->clearCachedDefinitions();
    $definitions = UiPatterns::getManager()->getDefinitions();
    $this->assertEquals(count($definitions[$pattern_id]->getFields()), $fields_count);
    $additional = $definitions[$pattern_id]->getAdditional();
    if (!isset($additional['settings'])) {
      $additional['settings'] = [];
    }
    $this->assertEquals(count($additional['settings']), $settings_count);
  }

}
