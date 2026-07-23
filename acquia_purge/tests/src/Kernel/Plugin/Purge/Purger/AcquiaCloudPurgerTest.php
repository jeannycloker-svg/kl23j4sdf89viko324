<?php

namespace Drupal\Tests\acquia_purge\Kernel\Plugin\Purge\Purger;

use Drupal\acquia_purge\Plugin\Purge\Purger\AcquiaCloudPurger;
use Drupal\purge\Logger\LoggerChannelPart;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\Tests\acquia_purge\Kernel\AcquiaPurgeKernelTestBase;
use Psr\Log\NullLogger;

/**
 * Tests the AcquiaCloudPurger plugin.
 *
 * @coversDefaultClass \Drupal\acquia_purge\Plugin\Purge\Purger\AcquiaCloudPurger
 * @group acquia_purge
 */
class AcquiaCloudPurgerTest extends AcquiaPurgeKernelTestBase {

  /**
   * The purger plugin under test.
   */
  protected AcquiaCloudPurger $purger;

  /**
   * Creates an AcquiaCloudPurger instance with mocked dependencies.
   *
   * @param array $responses
   *   Mock HTTP responses.
   * @param array $platformOverrides
   *   Platform info overrides.
   *
   * @return \Drupal\acquia_purge\Plugin\Purge\Purger\AcquiaCloudPurger
   *   The configured purger.
   */
  protected function createPurger(array $responses = [], array $platformOverrides = []): AcquiaCloudPurger {
    $httpClient = $this->createMockHttpClient($responses);
    $platformInfo = $this->createMockPlatformInfo($platformOverrides);

    $purger = new AcquiaCloudPurger(
      $platformInfo,
      $httpClient,
      ['id' => 'acquia_purge_test'],
      'acquia_purge',
      [
        'id' => 'acquia_purge',
        'label' => 'Acquia Cloud',
        'description' => 'Invalidate content from Acquia Cloud.',
        'types' => ['url', 'wildcardurl', 'tag', 'everything'],
        'cooldown_time' => 0.2,
        'multi_instance' => FALSE,
        'configform' => '',
      ]
    );

    $logger = new LoggerChannelPart(new NullLogger(), 0, []);
    $purger->setLogger($logger);

    return $purger;
  }

  /**
   * Creates a mock invalidation object.
   *
   * @param string $type
   *   The invalidation type.
   * @param string $expression
   *   The expression value.
   *
   * @return \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
   *   The mock invalidation.
   */
  protected function createMockInvalidation(string $type, string $expression): InvalidationInterface {
    $invalidation = $this->createMock(InvalidationInterface::class);
    $invalidation->method('getType')->willReturn($type);
    $invalidation->method('getExpression')->willReturn($expression);
    $invalidation->method('getId')->willReturn(uniqid());

    $state = InvalidationInterface::FRESH;
    $invalidation->method('getState')->willReturnCallback(function () use (&$state) {
      return $state;
    });
    $invalidation->method('setState')->willReturnCallback(function ($newState) use (&$state) {
      $state = $newState;
    });

    return $invalidation;
  }

  /**
   * Tests successful tag invalidation.
   *
   * @covers ::invalidateTags
   */
  public function testInvalidateTagsSuccess(): void {
    $responses = [
      $this->createVarnishBanTagsResponse(),
      $this->createVarnishBanTagsResponse(),
    ];
    $purger = $this->createPurger($responses);

    $invalidation = $this->createMockInvalidation('tag', 'node:1');
    $purger->invalidateTags([$invalidation]);

    $this->assertEquals(InvalidationInterface::SUCCEEDED, $invalidation->getState());
  }

  /**
   * Tests that tags containing spaces are rejected.
   *
   * @covers ::invalidateTags
   */
  public function testInvalidateTagsWithSpaceFails(): void {
    $purger = $this->createPurger([]);

    $invalidation = $this->createMockInvalidation('tag', 'invalid tag');
    $purger->invalidateTags([$invalidation]);

    $this->assertEquals(InvalidationInterface::FAILED, $invalidation->getState());
  }

  /**
   * Tests that tags are batched into groups.
   *
   * @covers ::invalidateTags
   */
  public function testInvalidateTagsBatching(): void {
    $tags = [];
    for ($i = 0; $i < 20; $i++) {
      $tags[] = $this->createMockInvalidation('tag', "node:$i");
    }

    $responses = array_fill(0, 8, $this->createVarnishBanTagsResponse());
    $purger = $this->createPurger($responses);
    $purger->invalidateTags($tags);

    foreach ($tags as $tag) {
      $this->assertEquals(InvalidationInterface::SUCCEEDED, $tag->getState());
    }
  }

  /**
   * Tests that partial balancer failure fails all tags in group.
   *
   * @covers ::invalidateTags
   */
  public function testInvalidateTagsPartialFailure(): void {
    $responses = [
      $this->createVarnishBanTagsResponse(),
      $this->createVarnishErrorResponse(403),
    ];
    $purger = $this->createPurger($responses);

    $invalidation = $this->createMockInvalidation('tag', 'node:1');
    $purger->invalidateTags([$invalidation]);

    $this->assertEquals(InvalidationInterface::FAILED, $invalidation->getState());
  }

  /**
   * Tests successful URL invalidation.
   *
   * @covers ::invalidateUrls
   */
  public function testInvalidateUrlsSuccess(): void {
    $responses = [
      $this->createVarnishPurgeResponse(),
      $this->createVarnishPurgeResponse(),
    ];
    $purger = $this->createPurger($responses);

    $invalidation = $this->createMockInvalidation('url', 'https://example.com/page');
    $purger->invalidateUrls([$invalidation]);

    $this->assertEquals(InvalidationInterface::SUCCEEDED, $invalidation->getState());
  }

  /**
   * Tests that 404 is a valid response for URL PURGE.
   *
   * @covers ::invalidateUrls
   */
  public function testInvalidateUrlsAccepts404(): void {
    $responses = [
      $this->createVarnishNotFoundResponse(),
      $this->createVarnishNotFoundResponse(),
    ];
    $purger = $this->createPurger($responses);

    $invalidation = $this->createMockInvalidation('url', 'https://example.com/notfound');
    $purger->invalidateUrls([$invalidation]);

    $this->assertEquals(InvalidationInterface::SUCCEEDED, $invalidation->getState());
  }

  /**
   * Tests URL invalidation with partial failure.
   *
   * @covers ::invalidateUrls
   */
  public function testInvalidateUrlsPartialFailure(): void {
    $responses = [
      $this->createVarnishPurgeResponse(),
      $this->createVarnishErrorResponse(403),
    ];
    $purger = $this->createPurger($responses);

    $invalidation = $this->createMockInvalidation('url', 'https://example.com/page');
    $purger->invalidateUrls([$invalidation]);

    $this->assertEquals(InvalidationInterface::FAILED, $invalidation->getState());
  }

  /**
   * Tests successful wildcard URL invalidation.
   *
   * @covers ::invalidateWildcardUrls
   */
  public function testInvalidateWildcardUrlsSuccess(): void {
    $responses = [
      $this->createVarnishBanUrlResponse(TRUE),
      $this->createVarnishBanUrlResponse(TRUE),
    ];
    $purger = $this->createPurger($responses);

    $invalidation = $this->createMockInvalidation('wildcardurl', 'https://example.com/path/*');
    $purger->invalidateWildcardUrls([$invalidation]);

    $this->assertEquals(InvalidationInterface::SUCCEEDED, $invalidation->getState());
  }

  /**
   * Tests successful everything invalidation.
   *
   * @covers ::invalidateEverything
   */
  public function testInvalidateEverythingSuccess(): void {
    $responses = [
      $this->createVarnishBanSiteResponse(),
      $this->createVarnishBanSiteResponse(),
    ];
    $purger = $this->createPurger($responses);

    $invalidation = $this->createMockInvalidation('everything', '');
    $purger->invalidateEverything([$invalidation]);

    $this->assertEquals(InvalidationInterface::SUCCEEDED, $invalidation->getState());
  }

  /**
   * Tests everything invalidation with partial failure.
   *
   * @covers ::invalidateEverything
   */
  public function testInvalidateEverythingPartialFailure(): void {
    $responses = [
      $this->createVarnishBanSiteResponse(),
      $this->createVarnishErrorResponse(500),
    ];
    $purger = $this->createPurger($responses);

    $invalidation = $this->createMockInvalidation('everything', '');
    $purger->invalidateEverything([$invalidation]);

    $this->assertEquals(InvalidationInterface::FAILED, $invalidation->getState());
  }

  /**
   * Tests that ideal conditions limit scales with balancer count.
   *
   * @covers ::getIdealConditionsLimit
   */
  public function testGetIdealConditionsLimit(): void {
    $purger = $this->createPurger([], ['balancerAddresses' => ['10.0.0.1', '10.0.0.2']]);
    $this->assertEquals(100, $purger->getIdealConditionsLimit());

    $purger = $this->createPurger([], ['balancerAddresses' => ['10.0.0.1']]);
    $this->assertEquals(200, $purger->getIdealConditionsLimit());

    $purger = $this->createPurger([], ['balancerAddresses' => []]);
    $this->assertEquals(100, $purger->getIdealConditionsLimit());
  }

  /**
   * Tests that routeTypeToMethod returns correct mappings.
   *
   * @covers ::routeTypeToMethod
   */
  public function testRouteTypeToMethod(): void {
    $purger = $this->createPurger([]);

    $this->assertEquals('invalidateTags', $purger->routeTypeToMethod('tag'));
    $this->assertEquals('invalidateUrls', $purger->routeTypeToMethod('url'));
    $this->assertEquals('invalidateWildcardUrls', $purger->routeTypeToMethod('wildcardurl'));
    $this->assertEquals('invalidateEverything', $purger->routeTypeToMethod('everything'));
    $this->assertEquals('invalidate', $purger->routeTypeToMethod('unknown'));
  }

  /**
   * Tests that hasRuntimeMeasurement returns TRUE.
   *
   * @covers ::hasRuntimeMeasurement
   */
  public function testHasRuntimeMeasurement(): void {
    $purger = $this->createPurger([]);
    $this->assertTrue($purger->hasRuntimeMeasurement());
  }

  /**
   * Tests multiple tags in a single request batch.
   *
   * @covers ::invalidateTags
   */
  public function testMultipleTagsInSingleBatch(): void {
    $responses = [
      $this->createVarnishBanTagsResponse(),
      $this->createVarnishBanTagsResponse(),
    ];
    $purger = $this->createPurger($responses);

    $invalidations = [
      $this->createMockInvalidation('tag', 'node:1'),
      $this->createMockInvalidation('tag', 'node:2'),
      $this->createMockInvalidation('tag', 'node:3'),
    ];
    $purger->invalidateTags($invalidations);

    foreach ($invalidations as $inv) {
      $this->assertEquals(InvalidationInterface::SUCCEEDED, $inv->getState());
    }
  }

  /**
   * Tests multiple URLs are purged independently.
   *
   * @covers ::invalidateUrls
   */
  public function testMultipleUrlsIndependent(): void {
    $responses = [
      $this->createVarnishPurgeResponse(),
      $this->createVarnishPurgeResponse(),
      $this->createVarnishPurgeResponse(),
      $this->createVarnishErrorResponse(403),
    ];
    $purger = $this->createPurger($responses);

    $inv1 = $this->createMockInvalidation('url', 'https://example.com/page1');
    $inv2 = $this->createMockInvalidation('url', 'https://example.com/page2');

    $purger->invalidateUrls([$inv1, $inv2]);

    $this->assertEquals(InvalidationInterface::SUCCEEDED, $inv1->getState());
    $this->assertEquals(InvalidationInterface::FAILED, $inv2->getState());
  }

  /**
   * Tests empty invalidation array does not cause errors.
   *
   * @covers ::invalidateTags
   */
  public function testEmptyInvalidationArray(): void {
    $purger = $this->createPurger([]);
    $purger->invalidateTags([]);
    $this->assertTrue(TRUE);
  }

  /**
   * Tests single balancer configuration.
   *
   * @covers ::invalidateTags
   */
  public function testSingleBalancer(): void {
    $responses = [
      $this->createVarnishBanTagsResponse(),
    ];
    $purger = $this->createPurger($responses, ['balancerAddresses' => ['10.0.0.1']]);

    $invalidation = $this->createMockInvalidation('tag', 'node:1');
    $purger->invalidateTags([$invalidation]);

    $this->assertEquals(InvalidationInterface::SUCCEEDED, $invalidation->getState());
  }

  /**
   * Tests that invalidate() throws an exception.
   *
   * @covers ::invalidate
   */
  public function testInvalidateThrowsException(): void {
    $purger = $this->createPurger([]);

    $this->expectException(\Exception::class);
    $purger->invalidate([]);
  }

  /**
   * Tests that 421 responses are caught and don't crash the process.
   *
   * When a balancer returns 421 (Misdirected Request) due to VCL ACL
   * misconfiguration, the exception should be caught and logged rather
   * than terminating the PHP process.
   *
   * @covers ::invalidateTags
   */
  public function testInvalidateTagsCatches421Exception(): void {
    $responses = [
      $this->createVarnishErrorResponse(421),
      $this->createVarnishBanTagsResponse(),
    ];
    $purger = $this->createPurger($responses, ['balancerAddresses' => ['10.0.0.1', '10.0.0.2']]);

    $invalidation = $this->createMockInvalidation('tag', 'node:1');
    $purger->invalidateTags([$invalidation]);

    // The invalidation should fail due to partial failure, but importantly
    // no exception should have been thrown to crash the process.
    $this->assertEquals(InvalidationInterface::FAILED, $invalidation->getState());
  }

}
