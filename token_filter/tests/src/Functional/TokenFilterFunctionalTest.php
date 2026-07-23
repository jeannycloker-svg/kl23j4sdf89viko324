<?php

declare(strict_types=1);

namespace Drupal\Tests\token_filter\Functional;

use PHPUnit\Framework\Attributes\Group;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests token replacement in node content.
 *
 * @group token_filter
 */
#[RunTestsInSeparateProcesses]
#[Group('token_filter')]
class TokenFilterFunctionalTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'filter',
    'token',
    'token_filter',
    'user',
    'node',
    'field',
    'text',
    'filter_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a basic page content type.
    $this->createContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);

    // Create a text format that uses the token filter.
    $token_format = $this->container->get('entity_type.manager')
      ->getStorage('filter_format')
      ->create([
        'format' => 'token_filter_format',
        'name' => 'Token Filter Format',
        'weight' => 0,
        'filters' => [
          'token_filter' => [
            'id' => 'token_filter',
            'provider' => 'token_filter',
            'status' => 1,
            'weight' => 0,
            'settings' => [
              'replace_empty' => FALSE,
            ],
          ],
        ],
      ]);
    $token_format->save();

    // Create a user with permission to create and view content.
    $this->drupalLogin($this->createUser([
      'create page content',
      'edit own page content',
      'access content',
      $token_format->getPermissionName(),
    ]));
  }

  /**
   * Tests token replacement in node content.
   */
  public function testTokenReplacementInNodeContent() {
    // Set the site name for testing.
    $this->config('system.site')
      ->set('name', 'Test Site Name')
      ->save();

    // Create a node with a token in the body field.
    $this->drupalGet('node/add/page');
    $this->assertSession()->statusCodeEquals(200);

    // Fill in the title and body fields.
    $this->submitForm([
      'title[0][value]' => 'Test Token Page',
      'body[0][value]' => 'Site name: [site:name]',
      'body[0][format]' => 'token_filter_format',
    ], 'Save');

    // Check that we're on the node page.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Test Token Page');

    // Check that the token has been replaced in the rendered content.
    $this->assertSession()->pageTextContains('Site name: Test Site Name');
    $this->assertSession()->pageTextNotContains('[site:name]');
  }

}
