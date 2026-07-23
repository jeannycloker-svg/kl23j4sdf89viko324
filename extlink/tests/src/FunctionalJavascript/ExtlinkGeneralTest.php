<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Testing the basic functionality of External Links.
 */
#[Group('Extlink')]
#[RunTestsInSeparateProcesses]
class ExtlinkGeneralTest extends ExtlinkTestBase {

  /**
   * Checks to see if external links gets extlink svg.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testExtlink(): void {
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

    // Test that the page has the external link svg.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link Exists.');

    // Test that the page has the Mailto external link svg.
    $mailToLink = $page->find('xpath', self::EXTLINK_MAILTO_XPATH);
    $this->assertTrue(!is_null($mailToLink) && $mailToLink->isVisible(), 'External Link MailTo Exists.');
  }

  /**
   * Checks to see if a telephone link gets extlink svg.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testExtlinkTel(): void {
    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link on a telephone.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="tel:+4733378901">Google number</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();

    $this->assertTrue($page->hasLink('Google number'));

    $link = $page->findLink('Google number');
    // Link should have tel attribute.
    $this->assertTrue($link->hasClass('tel'));

    // Test that the page has the external link svg.
    $externalLink = $page->find('xpath', self::EXTLINK_TEL_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link Exists.');
  }

  /**
   * Checks to see if an image link gets extlink svg.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testExtlinkImg(): void {
    // Login.
    $this->drupalLogin($this->adminUser);

    $this->config('extlink.settings')->set('extlink_img_class', TRUE)->save();
    $test_image = current($this->drupalGetTestFiles('image'));
    $image_file_path = \Drupal::service('file_system')->realpath($test_image->uri);

    // Create a node with an external link on an image.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="https://google.com"><img src="' . $image_file_path . '" alt="Google!" /></a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();

    $this->assertTrue($page->hasLink('Google!'));

    // Test that the page has the external link svg.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link Exists.');
  }

  /**
   * Checks to see if external links works correctly when disabled.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testExtlinkDisabled(): void {
    // Disable Extlink.
    $this->config('extlink.settings')->set('extlink_class', '0')->save();
    $this->config('extlink.settings')->set('extlink_mailto_class', '0')->save();

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

    // Test that the page has the external link svg.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertTrue(is_null($externalLink), 'External Link does not exist.');

    // Test that the page has the Mailto external link svg.
    $mailToLink = $page->find('xpath', self::EXTLINK_MAILTO_XPATH);
    $this->assertTrue(is_null($mailToLink), 'External Link MailTo does not exist.');
  }

  /**
   * Checks to see if external additional custom CSS classes work.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testExtlinkAdditionalCssClasses(): void {
    // Set custom CSS classes for external and mailto links.
    $this->config('extlink.settings')
      ->set('extlink_additional_link_classes', 'ext-link-css')
      ->set('extlink_additional_mailto_classes', 'ext-mailto-css')
      ->set('extlink_additional_tel_classes', 'ext-tel-css')
      ->save();

    // Login.
    $this->drupalLogin($this->adminUser);

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

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));
    $this->assertTrue($page->hasLink('Send Mail'));
    $this->assertTrue($page->hasLink('Google number'));

    // Test that the external link element has the CSS class applied.
    $this->assertSession()->elementExists('css', 'a.ext-link-css');

    // Test that the external mailto link element has the CSS class applied.
    $this->assertSession()->elementExists('css', 'a.ext-mailto-css');

    // Test that the external tel link element has the CSS class applied.
    $this->assertSession()->elementExists('css', 'a.ext-tel-css');
  }

  /**
   * Checks to see if external additional custom CSS classes work.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testExtlinkAdditionalCssClassesWithExistingClasses(): void {
    // Set custom CSS classes for external and mailto links.
    $this->config('extlink.settings')
      ->set('extlink_additional_link_classes', 'ext-link-css')
      ->set('extlink_additional_mailto_classes', 'ext-mailto-css')
      ->set('extlink_additional_tel_classes', 'ext-tel-css')
      ->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a href="https://google.com" class="existing-class-ext-link">Google!</a></p><p><a href="mailto:someone@example.com" class="existing-class-ext-mailto">Send Mail</a></p><p><a href="tel:+4733378901" class="existing-class-ext-tel">Google number</a></p>',
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
    $this->assertTrue($page->hasLink('Google number'));

    // Test that the external link element has the CSS class applied.
    $this->assertSession()->elementExists('css', 'a.existing-class-ext-link.ext-link-css');

    // Test that the external mailto link element has the CSS class applied.
    $this->assertSession()->elementExists('css', 'a.existing-class-ext-mailto.ext-mailto-css');

    // Test that the external tel link element has the CSS class applied.
    $this->assertSession()->elementExists('css', 'a.existing-class-ext-tel.ext-tel-css');
  }

  /**
   * Checks the ability to update the labels.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testExtlinkLabels(): void {
    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link, telephone link, and mailto link.
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

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));
    $this->assertTrue($page->hasLink('Send Mail'));
    $this->assertTrue($page->hasLink('Google number'));

    // Test the default labels first.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertEquals('(link is external)', $externalLink->getAttribute('aria-label'));

    $mailToLink = $page->find('xpath', self::EXTLINK_MAILTO_XPATH);
    $this->assertEquals('(link sends email)', $mailToLink->getAttribute('aria-label'));

    $telLink = $page->find('xpath', self::EXTLINK_TEL_XPATH);
    $this->assertEquals('(link is a phone number)', $telLink->getAttribute('aria-label'));

    // Now update the labels.
    $this->config('extlink.settings')
      ->set('extlink_label', 'New ext link')
      ->set('extlink_mailto_label', 'New mail link')
      ->set('extlink_tel_label', 'Hello world')
      ->save();

    // Get the page again.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();

    // Test the new labels.
    $externalLink = $page->find('xpath', self::EXTLINK_EXT_XPATH);
    $this->assertEquals('New ext link', $externalLink->getAttribute('aria-label'));

    $mailToLink = $page->find('xpath', self::EXTLINK_MAILTO_XPATH);
    $this->assertEquals('New mail link', $mailToLink->getAttribute('aria-label'));

    $telLink = $page->find('xpath', self::EXTLINK_TEL_XPATH);
    $this->assertEquals('Hello world', $telLink->getAttribute('aria-label'));
  }

  /**
   * Checks that extlink have the "ext" class when an ext icon is not placed.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testExtlinkHasExtClassWhenNoIconIsPlaced(): void {
    // Icon disabled.
    $this->config('extlink.settings')->set('extlink_class', FALSE)->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a id="the-link" href="https://google.com">Google!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));

    // Test that the page has the external link.
    $externalLink = $page->find('css', '#the-link');
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link does not exist.');

    // Does the anchor tag have the "ext" class?
    $this->assertTrue($externalLink->hasClass('ext'), 'External link does not have the ext class.');

  }

  /**
   * Checks that extlink have the "ext" class even when an ext icon is placed.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testExtlinkHasExtClassWhenIconIsPlaced(): void {
    // Icon disabled.
    $this->config('extlink.settings')->set('extlink_class', TRUE)->save();

    // Login.
    $this->drupalLogin($this->adminUser);

    // Create a node with an external link.
    $settings = [
      'type' => 'page',
      'title' => 'test page',
      'body' => [
        [
          'value' => '<p><a id="the-link" href="https://google.com">Google!</a></p>',
          'format' => $this->emptyFormat->id(),
        ],
      ],
    ];
    $node = $this->drupalCreateNode($settings);

    // Get the page.
    $this->drupalGet($node->toUrl());
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->hasLink('Google!'));

    // Test that the page has the external link.
    $externalLink = $page->find('css', '#the-link');
    $this->assertTrue(!is_null($externalLink) && $externalLink->isVisible(), 'External Link does not exist.');

    // Does the anchor tag have the "ext" class?
    $this->assertTrue($externalLink->hasClass('ext'), 'External link does not have the ext class.');
  }

}
