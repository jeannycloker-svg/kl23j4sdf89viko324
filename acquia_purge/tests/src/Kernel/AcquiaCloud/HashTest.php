<?php

namespace Drupal\Tests\acquia_purge\Kernel\AcquiaCloud;

use Drupal\acquia_purge\AcquiaCloud\Hash;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Hash helper class.
 *
 * @coversDefaultClass \Drupal\acquia_purge\AcquiaCloud\Hash
 * @group acquia_purge
 */
class HashTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests that cache tags are hashed to 4-character strings.
   *
   * @covers ::cacheTags
   */
  public function testCacheTagsHashing(): void {
    $tags = ['node:1', 'node:2', 'config:system.site'];
    $hashes = Hash::cacheTags($tags);

    $this->assertCount(3, $hashes);

    foreach ($hashes as $hash) {
      $this->assertIsString($hash);
      $this->assertEquals(4, strlen($hash), 'Hash should be 4 characters long.');
      $this->assertMatchesRegularExpression('/^[a-z0-9]+$/', $hash, 'Hash should only contain lowercase alphanumeric characters.');
    }
  }

  /**
   * Tests that the same input always produces the same hash.
   *
   * @covers ::cacheTags
   */
  public function testCacheTagsConsistency(): void {
    $tags = ['node:1', 'user:5', 'taxonomy_term:10'];

    $hashes1 = Hash::cacheTags($tags);
    $hashes2 = Hash::cacheTags($tags);

    $this->assertEquals($hashes1, $hashes2, 'Same input should produce same hashes.');

    $singleTag = ['node:1'];
    $hash1 = Hash::cacheTags($singleTag)[0];
    $hash2 = Hash::cacheTags($singleTag)[0];
    $this->assertEquals($hash1, $hash2, 'Same single tag should produce same hash.');
  }

  /**
   * Tests that different inputs produce different hashes.
   *
   * @covers ::cacheTags
   */
  public function testCacheTagsUniqueness(): void {
    $tags1 = ['node:1'];
    $tags2 = ['node:2'];
    $tags3 = ['user:1'];

    $hash1 = Hash::cacheTags($tags1)[0];
    $hash2 = Hash::cacheTags($tags2)[0];
    $hash3 = Hash::cacheTags($tags3)[0];

    $this->assertNotEquals($hash1, $hash2, 'Different node IDs should produce different hashes.');
    $this->assertNotEquals($hash1, $hash3, 'Different entity types should produce different hashes.');
    $this->assertNotEquals($hash2, $hash3, 'All hashes should be unique for different inputs.');
  }

  /**
   * Tests that site identifier hashes are 16 characters long.
   *
   * @covers ::siteIdentifier
   */
  public function testSiteIdentifierHashing(): void {
    $hash = Hash::siteIdentifier('mysite', 'sites/default');

    $this->assertIsString($hash);
    $this->assertEquals(16, strlen($hash), 'Site identifier hash should be 16 characters long.');
    $this->assertMatchesRegularExpression('/^[a-z0-9]+$/', $hash, 'Hash should only contain lowercase alphanumeric characters.');
  }

  /**
   * Tests that site identifier produces consistent hashes.
   *
   * @covers ::siteIdentifier
   */
  public function testSiteIdentifierConsistency(): void {
    $hash1 = Hash::siteIdentifier('mysite', 'sites/default');
    $hash2 = Hash::siteIdentifier('mysite', 'sites/default');

    $this->assertEquals($hash1, $hash2, 'Same input should produce same site identifier.');
  }

  /**
   * Tests that different site configurations produce unique identifiers.
   *
   * @covers ::siteIdentifier
   */
  public function testSiteIdentifierUniqueness(): void {
    $hash1 = Hash::siteIdentifier('site1', 'sites/default');
    $hash2 = Hash::siteIdentifier('site2', 'sites/default');
    $hash3 = Hash::siteIdentifier('site1', 'sites/multisite');
    $hash4 = Hash::siteIdentifier('site1dev', 'sites/default');

    $this->assertNotEquals($hash1, $hash2, 'Different site names should produce different identifiers.');
    $this->assertNotEquals($hash1, $hash3, 'Different site paths should produce different identifiers.');
    $this->assertNotEquals($hash1, $hash4, 'Similar but different site names should produce different identifiers.');
  }

  /**
   * Tests hashing with empty input.
   *
   * @covers ::cacheTags
   */
  public function testCacheTagsEmptyInput(): void {
    $hashes = Hash::cacheTags([]);
    $this->assertCount(0, $hashes);
    $this->assertIsArray($hashes);
  }

  /**
   * Tests hashing with special characters in tags.
   *
   * @covers ::cacheTags
   */
  public function testCacheTagsSpecialCharacters(): void {
    $tags = [
      'config:views.view.content',
      'node_list',
      '4xx-response',
      'rendered',
    ];
    $hashes = Hash::cacheTags($tags);

    $this->assertCount(4, $hashes);
    foreach ($hashes as $hash) {
      $this->assertEquals(4, strlen($hash));
    }
  }

  /**
   * Tests that order of tags is preserved in output.
   *
   * @covers ::cacheTags
   */
  public function testCacheTagsOrderPreserved(): void {
    $tags = ['a', 'b', 'c'];
    $hashes = Hash::cacheTags($tags);

    $hashA = Hash::cacheTags(['a'])[0];
    $hashB = Hash::cacheTags(['b'])[0];
    $hashC = Hash::cacheTags(['c'])[0];

    $this->assertEquals($hashA, $hashes[0]);
    $this->assertEquals($hashB, $hashes[1]);
    $this->assertEquals($hashC, $hashes[2]);
  }

  /**
   * Tests that cache tag hashes only contain base32 characters.
   *
   * @covers ::cacheTags
   */
  public function testCacheTagsReturnsBase32Characters(): void {
    $hashes = Hash::cacheTags(['node:1', 'node:2', 'config:system.site']);
    foreach ($hashes as $hash) {
      $this->assertMatchesRegularExpression('/^[0-9a-v]+$/', $hash);
    }
  }

}
