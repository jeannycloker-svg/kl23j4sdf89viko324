<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Ext link domain matching functionality.
 */
#[Group('Extlink Admin Tests')]
#[RunTestsInSeparateProcesses]
class ExtlinkDomainTest extends ExtlinkTestBase {

  /**
   * Checks to see if external links works with an extended set of links.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testExtlinkDomainMatchingExcludeSubDomainsEnabled(): void {
    $this->config('extlink.settings')->set('extlink_subdomains', TRUE)->save();
    // Login.
    $this->drupalLogin($this->adminUser);

    $domains = [
      'https://www.example.com',
      'https://www.example.com:8080',
      'https://www.example.co.uk',
      'https://test.example.com',
      'https://example.com',
      'https://www.whatever.com',
      'https://www.domain.org',
      'https://www.domain.nl',
      'https://www.domain.de',
      'https://www.auspigs.com',
      'https://www.usapigs.com',
      'https://user:password@example.com',
    ];

    // Build the HTML for the page.
    $node_html = '';
    foreach ($domains as $item) {
      $node_html .= '<p><a href="' . $item . '">' . $item . '</a></p><p>';
    }

    // Create the node.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => $node_html,
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();

    // Test that the page has an external link on each link.
    foreach ($domains as $item) {
      $externalLink = $page->findLink($item);
      $this->assertTrue($externalLink->hasAttribute('data-extlink'), 'External Link failed for "' . $item . '"');
    }
  }

}
