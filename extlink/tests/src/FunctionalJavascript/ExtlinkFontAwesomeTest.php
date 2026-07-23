<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests font awesome integration.
 */
#[Group('Extlink Admin Tests')]
#[RunTestsInSeparateProcesses]
class ExtlinkFontAwesomeTest extends ExtlinkTestBase {

  /**
   * Checks to see if external link font awesome works.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testExtlinkUseFontAwesome(): void {
    // Enable Use Font Awesome.
    $this->config('extlink.settings')->set('extlink_use_font_awesome', TRUE)->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="https://google.com">Google!</a></p><p><a href="mailto:someone@example.com">Send Mail</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));
    $this->assertTrue($page->hasLink('Send Mail'));

    // Test that the page has the external link span.
    $this->assertSession()->elementExists('css', 'span.fa-external-link');

    // Test that the page has the Mailto external link span.
    $this->assertSession()->elementExists('css', 'span.fa-envelope-o');
  }

}
