<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Testing the rel nofollow/follow functionality of External Links.
 */
#[Group('Extlink')]
#[RunTestsInSeparateProcesses]
class ExtlinkNoFollowTest extends ExtlinkTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->config('extlink.settings')->set('extlink_nofollow', TRUE)->save();

    // Login.
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Checks to see if extlink and nofollow work together when both are enabled.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testExtlinkEnabledNoFollowEnabled(): void {
    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="https://google.com">Google!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));

    // Test that the page has the external link svg.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link does not exist.');

    // Does the anchor tag have no follow?
    $link = $page->findLink('Google!');
    $this->assertTrue($link->hasAttribute('rel'), 'ExtLink does not have rel attribute.');
    $this->assertStringContainsString('nofollow', $link->getAttribute('rel'), 'ExtLink rel attribute does not contain "nofollow".');
  }

  /**
   * Checks to see if external links no follow works with extlink disabled.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testExtlinkDisabledNoFollowEnabled(): void {
    $this->config('extlink.settings')->set('extlink_class', '0')->save();
    $this->config('extlink.settings')->set('extlink_mailto_class', '0')->save();

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="https://google.com">Google!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));

    // Test that the page has the external link svg.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(is_null($externalLink), 'External Link exists.');

    // Does the anchor tag have no follow?
    $link = $page->findLink('Google!');
    $this->assertTrue($link->hasAttribute('rel'), 'ExtLink does not have rel attribute.');
    $this->assertStringContainsString('nofollow', $link->getAttribute('rel'), 'ExtLink rel attribute does not contain "nofollow".');
  }

  /**
   * Checks to see if rel no follow is added if disabled.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testExtlinkDisabledNoFollowDisabled(): void {
    // No Follow Enabled, Extlink Disabled.
    $this->config('extlink.settings')->set('extlink_nofollow', FALSE)->save();
    $this->config('extlink.settings')->set('extlink_follow_no_override', FALSE)->save();
    $this->config('extlink.settings')->set('extlink_class', '0')->save();
    $this->config('extlink.settings')->set('extlink_mailto_class', '0')->save();

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="https://google.com">Google!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));

    // Test that the page doesn't have the external link svg.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(is_null($externalLink), 'External Link exists.');

    // Check for no 'nofollow'.
    $link = $page->findLink('Google!');
    $this->assertStringNotContainsString('nofollow', $link->getAttribute('rel'), 'ExtLink rel attribute does not contain "nofollow".');
  }

  /**
   * Checks to see if rel no follow is overridden.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testExtlinkNoFollowNoOverride(): void {
    $this->config('extlink.settings')->set('extlink_follow_no_override', TRUE)->save();
    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="https://google.com" rel="follow">Google!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));

    // Test that the page has the external link svg.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link does not exist.');

    // Does the anchor tag have no follow?
    $link = $page->findLink('Google!');
    $this->assertTrue($link->hasAttribute('rel'), 'ExtLink does not have rel attribute.');
    $this->assertStringContainsString('follow', $link->getAttribute('rel'), 'ExtLink rel attribute does not contain "follow".');
  }

}
