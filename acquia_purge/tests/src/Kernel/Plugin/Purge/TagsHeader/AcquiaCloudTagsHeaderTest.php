<?php

namespace Drupal\Tests\acquia_purge\Kernel\Plugin\Purge\TagsHeader;

use Drupal\acquia_purge\Plugin\Purge\TagsHeader\AcquiaCloudTagsHeader;
use Drupal\acquia_purge\Plugin\Purge\TagsHeader\TagsHeaderValue;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the AcquiaCloudTagsHeader plugin.
 *
 * @coversDefaultClass \Drupal\acquia_purge\Plugin\Purge\TagsHeader\AcquiaCloudTagsHeader
 * @group acquia_purge
 */
class AcquiaCloudTagsHeaderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Creates an AcquiaCloudTagsHeader instance.
   *
   * @return \Drupal\acquia_purge\Plugin\Purge\TagsHeader\AcquiaCloudTagsHeader
   *   The header plugin instance.
   */
  protected function createHeader(): AcquiaCloudTagsHeader {
    return new AcquiaCloudTagsHeader(
      [],
      'acquiapurgecloudtagsheader',
      [
        'id' => 'acquiapurgecloudtagsheader',
        'header_name' => 'X-Acquia-Purge-Tags',
        'dependent_purger_plugins' => ['acquia_purge'],
      ]
    );
  }

  /**
   * Tests that getValue returns a TagsHeaderValue object.
   *
   * @covers ::getValue
   */
  public function testGetValueReturnsTagsHeaderValue(): void {
    $header = $this->createHeader();

    $tags = ['node:1', 'node:2', 'config:system.site'];
    $value = $header->getValue($tags);

    $this->assertInstanceOf(TagsHeaderValue::class, $value);
  }

  /**
   * Tests that getValue preserves tag count.
   *
   * @covers ::getValue
   */
  public function testGetValuePreservesTagCount(): void {
    $header = $this->createHeader();

    $tags = ['node:1', 'node:2', 'config:system.site'];
    $value = $header->getValue($tags);
    $map = $value->getTagsMap();

    $this->assertCount(3, $map);
  }

  /**
   * Tests getValue with empty tags.
   *
   * @covers ::getValue
   */
  public function testGetValueWithEmptyTags(): void {
    $header = $this->createHeader();

    $value = $header->getValue([]);

    $this->assertInstanceOf(TagsHeaderValue::class, $value);
    $this->assertEquals('', (string) $value);
  }

  /**
   * Tests that header name is X-Acquia-Purge-Tags.
   *
   * @covers ::getHeaderName
   */
  public function testHeaderName(): void {
    $header = $this->createHeader();

    $this->assertEquals('X-Acquia-Purge-Tags', $header->getHeaderName());
  }

  /**
   * Tests getValue produces consistent hashes.
   *
   * @covers ::getValue
   */
  public function testGetValueConsistentHashes(): void {
    $header = $this->createHeader();

    $tags = ['node:1'];
    $value1 = $header->getValue($tags);
    $value2 = $header->getValue($tags);

    $this->assertEquals((string) $value1, (string) $value2);
  }

}
