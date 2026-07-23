<?php

namespace Drupal\Tests\better_exposed_filters\FunctionalJavascript;

use Drupal\views\Views;

/**
 * Tests BEF replacement settings.
 *
 * @group better_exposed_filters
 */
class BetterExposedFiltersReplacementTest extends BetterExposedFiltersTestBase {

  /**
   * Tests replacement setting.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testReplacementSetting(): void {
    $view = Views::getView('bef_test');

    // Test with filter_rewrite_values_key enabled first.
    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_integer_value' => [
          'plugin_id' => 'default',
          'advanced' => [
            'rewrite' => [
              'filter_rewrite_values' => "1|One replace\r\n2|",
              'filter_rewrite_values_key' => TRUE,
            ],
          ],
        ],
      ],
    ]);

    $this->drupalGet('bef-test');

    $this->assertSession()->optionExists('field_bef_integer_value', 'One replace');
    // Checking for empty value because optionNotExists() doesn't check key.
    $this->assertSession()->optionNotExists('field_bef_integer_value', '');
    $this->assertSession()->optionExists('field_bef_integer_value', 'Three');

    // Test with filter_rewrite_values_key disabled next.
    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_integer_value' => [
          'plugin_id' => 'default',
          'advanced' => [
            'rewrite' => [
              'filter_rewrite_values' => "One|One replace\r\nTwo|",
              'filter_rewrite_values_key' => FALSE,
            ],
          ],
        ],
      ],
    ]);

    $this->drupalGet('bef-test');

    $this->assertSession()->optionExists('field_bef_integer_value', 'One replace');
    // Checking for empty value because optionNotExists() doesn't check key.
    $this->assertSession()->optionNotExists('field_bef_integer_value', '');
    $this->assertSession()->optionExists('field_bef_integer_value', 'Three');
  }

}
