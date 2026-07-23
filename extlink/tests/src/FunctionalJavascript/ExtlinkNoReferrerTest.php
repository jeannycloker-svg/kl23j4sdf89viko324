<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests no referrer functionality.
 */
#[Group('Extlink')]
#[RunTestsInSeparateProcesses]
class ExtlinkNoReferrerTest extends ExtlinkTestBase {

  /**
   * Checks to see if noreferrer exclusion for external links work.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testExtlinkNoreferrerExclusion(): void {
    // Enable target for external links.
    $this->config('extlink.settings')->set('extlink_target', TRUE)->save();
    // Add pattern to exclude 'noreferrer' tag from external links.
    $this->config('extlink.settings')->set('extlink_exclude_noreferrer', '(example\.com)')->save();

    // Admin login.
    $this->drupalLogin($this->adminUser);

    // Create a node with two external links.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="https://google.com">Google!</a><a href="https://example.com">Example link!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the test page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();

    $this->assertTrue($page->hasLink('Google!'));
    $this->assertTrue($page->hasLink('Example link!'));

    $link = $page->findLink('Google!');
    // Link should have rel attribute 'noopener noreferrer'.
    $this->assertTrue($link->getAttribute('rel') === 'noopener noreferrer' || $link->getAttribute('rel') === 'noreferrer noopener', 'ExtLink rel attribute is not "noopener noreferrer".');

    $link = $page->findLink('Example link!');
    // Link should have rel attribute as 'noopener' only.
    $this->assertTrue($link->getAttribute('rel') === 'noopener', 'ExtLink rel attribute is not "noopener".');
  }

}
