<?php

declare(strict_types=1);

namespace Drupal\Tests\token_filter\Kernel;

use PHPUnit\Framework\Attributes\Group;
use Drupal\filter\FilterPluginCollection;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Filter module filters individually.
 *
 * @group filter
 */
#[RunTestsInSeparateProcesses]
#[Group('filter')]
class TokenFilterKernelTest extends KernelTestBase {

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
  ];

  /**
   * The token filter plugin.
   *
   * @var \Drupal\filter\Plugin\FilterInterface
   */
  protected $filter;

  /**
   * The test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['system', 'filter', 'node']);

    // Create a manager user.
    $manager = $this->createUser([], 'manager');

    // Create a page content type.
    $this->createContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);

    // Create a test node.
    $this->node = $this->createNode([
      'type' => 'page',
      'title' => 'Test Page',
      'uid' => $manager->id(),
    ]);

    // Get the token filter.
    $filter_manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($filter_manager, []);
    $filters = $bag->getAll();
    $this->filter = $filters['token_filter'];
  }

  /**
   * Tests global token replacement.
   */
  public function testGlobalTokenReplacement() {
    // Set the site name.
    \Drupal::configFactory()->getEditable('system.site')
      ->set('name', 'Pink flamingo bazaar')
      ->save();

    // Test replacing a global token.
    $input = "Site name is: [site:name]";
    $output = $this->filter->process($input, 'en');
    $processed = $output->getProcessedText();

    $this->assertEquals('Site name is: Pink flamingo bazaar', $processed);
  }

  /**
   * Tests entity token replacement.
   */
  public function testEntityTokenReplacement() {
    // Set the static entity for token replacement.
    drupal_static_reset('token_filter_entity');
    $entity = &drupal_static('token_filter_entity');
    $entity = $this->node;

    // Test replacing an entity token.
    $input = "Node title is: [node:title]";
    $output = $this->filter->process($input, 'en');
    $processed = $output->getProcessedText();

    $this->assertEquals('Node title is: Test Page', $processed);
  }

  /**
   * Tests the replace_empty setting when set to FALSE.
   */
  public function testReplaceEmptyFalse() {
    // Configure the filter to not replace empty tokens.
    $configuration = $this->filter->getConfiguration();
    $configuration['settings']['replace_empty'] = FALSE;
    $this->filter->setConfiguration($configuration);

    // Test with a non-existent token.
    $input = "Invalid token: [invalid:token]";
    $output = $this->filter->process($input, 'en');
    $processed = $output->getProcessedText();

    // The token should remain in the text.
    $this->assertEquals('Invalid token: [invalid:token]', $processed);
  }

  /**
   * Tests the replace_empty setting when set to TRUE.
   */
  public function testReplaceEmptyTrue() {
    // Configure the filter to replace empty tokens.
    $configuration = $this->filter->getConfiguration();
    $configuration['settings']['replace_empty'] = TRUE;
    $this->filter->setConfiguration($configuration);

    // Test with a non-existent token.
    $input = "Invalid token: [invalid:token]";
    $output = $this->filter->process($input, 'en');
    $processed = $output->getProcessedText();

    // The token should be removed from the text.
    $this->assertEquals('Invalid token: ', $processed);
  }

  /**
   * Tests multiple tokens in the same text.
   */
  public function testMultipleTokens() {
    // Set the site name.
    \Drupal::configFactory()->getEditable('system.site')
      ->set('name', 'Test Site')
      ->save();

    // Set the static entity for token replacement.
    drupal_static_reset('token_filter_entity');
    $entity = &drupal_static('token_filter_entity');
    $entity = $this->node;

    // Test replacing multiple tokens.
    $input = "Site: [site:name], Node: [node:title]";
    $output = $this->filter->process($input, 'en');
    $processed = $output->getProcessedText();

    $this->assertEquals('Site: Test Site, Node: Test Page', $processed);
  }

}
