<?php

namespace Drupal\Tests\better_exposed_filters\FunctionalJavascript;

use Drupal\views\Views;

/**
 * Tests functionality around checkboxes.
 *
 * @group better_exposed_filters
 */
class BetterExposedCheckboxTest extends BetterExposedFiltersTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a few test nodes.
    $this->createNode([
      'title' => 'Page One',
      'field_bef_price' => '10',
      'type' => 'bef_test',
    ]);
    $this->createNode([
      'title' => 'Page Two',
      'field_bef_price' => '75',
      'type' => 'bef_test',
    ]);
  }

  /**
   * Tests the soft limit feature.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testBefCheckboxSoftLimit(): void {
    $view = Views::getView('bef_test');
    $session = $this->assertSession();

    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_letters_value' => [
          'plugin_id' => 'bef',
          'soft_limit' => '3',
          'soft_limit_label_less' => 'Less test',
          'soft_limit_label_more' => 'More test',
        ],
      ],
    ], 'page_5');

    $this->drupalGet('/bef-test-checkboxes');
    $session->elementTextEquals('css', '.bef-soft-limit-link', 'More test');
    $session->pageTextContains('Aardvark');
    $session->pageTextContains('Bumble & the Bee');
    $session->pageTextContains('Le Chimpanzé');
    $session->pageTextNotContains('Donkey');
    $session->pageTextNotContains('Elephant');
    $this->clickLink('More test');
    $session->pageTextContains('Aardvark');
    $session->pageTextContains('Bumble & the Bee');
    $session->pageTextContains('Le Chimpanzé');
    $session->pageTextContains('Donkey');
    $session->pageTextContains('Elephant');
    $session->elementTextEquals('css', '.bef-soft-limit-link', 'Less test');

    // Now lets test soft limit on links.
    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_letters_value' => [
          'plugin_id' => 'bef_links',
          'soft_limit' => '3',
          'soft_limit_label_less' => 'Less test',
          'soft_limit_label_more' => 'More test',
        ],
      ],
    ], 'page_5');

    $this->drupalGet('/bef-test-checkboxes');
    $session->elementTextEquals('css', '.bef-soft-limit-link', 'More test');
    $session->pageTextContains('Aardvark');
    $session->pageTextContains('Bumble & the Bee');
    $session->pageTextContains('Le Chimpanzé');
    $session->pageTextNotContains('Donkey');
    $session->pageTextNotContains('Elephant');
    $this->clickLink('More test');
    $session->pageTextContains('Aardvark');
    $session->pageTextContains('Bumble & the Bee');
    $session->pageTextContains('Le Chimpanzé');
    $session->pageTextContains('Donkey');
    $session->pageTextContains('Elephant');
    $session->elementTextEquals('css', '.bef-soft-limit-link', 'Less test');
  }

}
