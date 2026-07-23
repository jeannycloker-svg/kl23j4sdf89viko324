<?php

namespace Drupal\Tests\acquia_purge\Kernel\Plugin\Purge\TagsHeader;

use Drupal\acquia_purge\Plugin\Purge\TagsHeader\TagsHeaderValue;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the TagsHeaderValue class.
 *
 * @coversDefaultClass \Drupal\acquia_purge\Plugin\Purge\TagsHeader\TagsHeaderValue
 * @group acquia_purge
 */
class TagsHeaderValueTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests that __toString returns space-separated hashes.
   *
   * @covers ::__toString
   */
  public function testToStringReturnsSeparatedHashes(): void {
    $tags = ['node:1', 'node:2', 'config:system.site'];
    $hashes = ['a1b2', 'c3d4', 'e5f6'];

    $headerValue = new TagsHeaderValue($tags, $hashes);

    $this->assertEquals('a1b2 c3d4 e5f6', (string) $headerValue);
  }

  /**
   * Tests that getTagsMap returns correct mapping.
   *
   * @covers ::getTagsMap
   */
  public function testGetTagsMapReturnsMapping(): void {
    $tags = ['node:1', 'node:2', 'config:system.site'];
    $hashes = ['a1b2', 'c3d4', 'e5f6'];

    $headerValue = new TagsHeaderValue($tags, $hashes);
    $map = $headerValue->getTagsMap();

    $this->assertIsArray($map);
    $this->assertCount(3, $map);
    $this->assertEquals('a1b2', $map['node:1']);
    $this->assertEquals('c3d4', $map['node:2']);
    $this->assertEquals('e5f6', $map['config:system.site']);
  }

  /**
   * Tests that constructor throws on unequal arrays.
   *
   * @covers ::__construct
   */
  public function testThrowsOnUnequalArrays(): void {
    $tags = ['node:1', 'node:2', 'node:3'];
    $hashes = ['a1b2', 'c3d4'];

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('TagsHeaderValue received unequal tag sets');

    new TagsHeaderValue($tags, $hashes);
  }

  /**
   * Tests with empty arrays.
   *
   * @covers ::__construct
   * @covers ::__toString
   */
  public function testEmptyArrays(): void {
    $headerValue = new TagsHeaderValue([], []);

    $this->assertEquals('', (string) $headerValue);
    $this->assertEmpty($headerValue->getTagsMap());
  }

  /**
   * Tests with single tag.
   *
   * @covers ::__toString
   * @covers ::getTagsMap
   */
  public function testSingleTag(): void {
    $tags = ['node:1'];
    $hashes = ['a1b2'];

    $headerValue = new TagsHeaderValue($tags, $hashes);

    $this->assertEquals('a1b2', (string) $headerValue);
    $this->assertEquals(['node:1' => 'a1b2'], $headerValue->getTagsMap());
  }

  /**
   * Tests that separator constant is space.
   *
   * @coversNothing
   */
  public function testSeparatorIsSpace(): void {
    $this->assertEquals(' ', TagsHeaderValue::SEPARATOR);
  }

  /**
   * Tests tags with special characters.
   *
   * @covers ::__toString
   * @covers ::getTagsMap
   */
  public function testSpecialCharacterTags(): void {
    $tags = ['config:views.view.content', 'node_list', '4xx-response'];
    $hashes = ['aaaa', 'bbbb', 'cccc'];

    $headerValue = new TagsHeaderValue($tags, $hashes);

    $this->assertEquals('aaaa bbbb cccc', (string) $headerValue);

    $map = $headerValue->getTagsMap();
    $this->assertEquals('aaaa', $map['config:views.view.content']);
    $this->assertEquals('bbbb', $map['node_list']);
    $this->assertEquals('cccc', $map['4xx-response']);
  }

  /**
   * Tests that order is preserved.
   *
   * @covers ::__toString
   */
  public function testOrderPreserved(): void {
    $tags = ['c', 'a', 'b'];
    $hashes = ['hash_c', 'hash_a', 'hash_b'];

    $headerValue = new TagsHeaderValue($tags, $hashes);

    $this->assertEquals('hash_c hash_a hash_b', (string) $headerValue);
  }

}
