<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Testing of External Links with the UI Icons module.
 */
#[Group('extlink')]
#[RunTestsInSeparateProcesses]
class ExtlinkUiIconsTest extends ExtlinkTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ui_icons', 'ui_icons_test'];

  /**
   * Test extlink UI Icons.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testExtlinkUiIcons(): void {
    // Verify settings form appears.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(self::EXTLINK_ADMIN_PATH);
    $this->assertSession()->pageTextContains('Use icons instead of images.');

    $this->config('extlink.settings')->set('extlink_use_icon', TRUE)->save();
    $icons = [
      'links' => [
        'icon' => 'test_svg_sprite:icon-test-3',
      ],
      'mailto' => [
        'icon' => 'test_svg_sprite:icon-test-1',
      ],
      'tel' => [
        'icon' => 'test_svg_sprite:icon-test-2',
      ],
    ];
    $this->config('extlink.settings')->set('extlink_icons', $icons)->save();

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="https://google.com">Google!</a></p><p><a href="mailto:someone@example.com">Send Mail</a></p><p><a href="tel:+4733378901">Google number</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();

    $this->assertTrue($page->hasLink('Google!'));
    $link = $page->findLink('Google!');
    $span = $link->find('css', '.extlink');
    $this->assertTrue($span->hasAttribute('data-extlink-icon-type'));
    $this->assertEquals('link', $span->getAttribute('data-extlink-icon-type'));

    $this->assertTrue($page->hasLink('Send Mail'));
    $link = $page->findLink('Send Mail');
    $span = $link->find('css', '.extlink');
    $this->assertTrue($span->hasAttribute('data-extlink-icon-type'));
    $this->assertEquals('mailto', $span->getAttribute('data-extlink-icon-type'));

    $this->assertTrue($page->hasLink('Google number'));
    $link = $page->findLink('Google number');
    $span = $link->find('css', '.extlink');
    $this->assertTrue($span->hasAttribute('data-extlink-icon-type'));
    $this->assertEquals('tel', $span->getAttribute('data-extlink-icon-type'));
  }

}
