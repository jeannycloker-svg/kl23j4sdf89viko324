<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Testing the extlink wrapping functionality.
 */
#[Group('Extlink')]
#[RunTestsInSeparateProcesses]
class ExtlinkWrappingTest extends ExtlinkTestBase {

  /**
   * Tests the extlink_prevent_orphan_text_like setting.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testExtlinkOrphanText(): void {
    // Tests with orphan text disabled.
    $this->config('extlink.settings')
      ->set('extlink_prevent_orphan', TRUE)
      ->set('extlink_prevent_orphan_text_like', FALSE)
      ->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<a href="https://google.com"><div class="card"><div class="title">link card title</div><div class="body">link card</div></div></a>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();

    // Test that the page has the external link.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible());
    // Test that the external link does have the extlink-nobreak class applied.
    $this->assertNotEmpty(
      $page->find('css', 'span.extlink-nobreak'),
    );
    // Set extlink_prevent_orphan_text_like to true.
    $this->config('extlink.settings')
      ->set('extlink_prevent_orphan', TRUE)
      ->set('extlink_prevent_orphan_text_like', TRUE)
      ->save();

    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();

    // Test that the page has the external link.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible());
    // Test that extlink-nobreak class is not applied.
    $this->assertEmpty(
      $page->find('css', 'span.extlink-nobreak'),
    );
  }

}
