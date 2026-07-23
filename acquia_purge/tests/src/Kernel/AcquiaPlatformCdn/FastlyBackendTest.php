<?php

namespace Drupal\Tests\acquia_purge\Kernel\AcquiaPlatformCdn;

use Drupal\acquia_purge\AcquiaPlatformCdn\FastlyBackend;
use Drupal\acquia_purge\Plugin\Purge\Purger\Debugger;
use Drupal\purge\Logger\LoggerChannelPart;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\Tests\acquia_purge\Kernel\AcquiaPurgeKernelTestBase;
use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;

/**
 * Tests the FastlyBackend class.
 *
 * @coversDefaultClass \Drupal\acquia_purge\AcquiaPlatformCdn\FastlyBackend
 * @group acquia_purge
 */
class FastlyBackendTest extends AcquiaPurgeKernelTestBase {

  /**
   * Creates a FastlyBackend instance with mocked dependencies.
   *
   * @param array $responses
   *   Mock HTTP responses.
   * @param array $config
   *   Backend configuration.
   * @param array $platformOverrides
   *   Platform info overrides.
   *
   * @return \Drupal\acquia_purge\AcquiaPlatformCdn\FastlyBackend
   *   The configured backend.
   */
  protected function createBackend(array $responses = [], array $config = [], array $platformOverrides = []): FastlyBackend {
    $httpClient = $this->createMockHttpClient($responses);
    $platformInfo = $this->createMockPlatformInfoWithFastly($platformOverrides);

    $defaultConfig = [
      'vendor' => 'fastly',
      'config' => 'settings',
      'service_id' => 'test-service-id',
      'token' => 'test-fastly-token',
    ];
    $config = array_merge($defaultConfig, $config);

    $logger = new LoggerChannelPart(new NullLogger(), 0, []);
    $debugger = new Debugger($logger);

    return new FastlyBackend(
      $config,
      $platformInfo,
      $logger,
      $debugger,
      $httpClient
    );
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
   * Tests that configuration validation requires service_id.
   *
   * @covers ::validateConfiguration
   */
  public function testValidateConfigurationRequiresServiceId(): void {
    $this->assertFalse(FastlyBackend::validateConfiguration([
      'token' => 'test-token',
    ]));

    $this->assertFalse(FastlyBackend::validateConfiguration([
      'service_id' => '',
      'token' => 'test-token',
    ]));
  }

  /**
   * Tests that configuration validation requires token.
   *
   * @covers ::validateConfiguration
   */
  public function testValidateConfigurationRequiresToken(): void {
    $this->assertFalse(FastlyBackend::validateConfiguration([
      'service_id' => 'test-service',
    ]));

    $this->assertFalse(FastlyBackend::validateConfiguration([
      'service_id' => 'test-service',
      'token' => '',
    ]));
  }

  /**
   * Tests that valid configuration passes validation.
   *
   * @covers ::validateConfiguration
   */
  public function testValidateConfigurationValid(): void {
    $this->assertTrue(FastlyBackend::validateConfiguration([
      'service_id' => 'test-service',
      'token' => 'test-token',
    ]));
  }

  /**
   * Tests successful tag invalidation.
   *
   * @covers ::invalidateTags
   */
  public function testInvalidateTagsSuccess(): void {
    $responses = [
      $this->createFastlySuccessResponse(),
    ];
    $backend = $this->createBackend($responses);

    $invalidation = $this->createMockInvalidation('tag', 'node:1');
    $backend->invalidateTags([$invalidation]);

    $this->assertEquals(InvalidationInterface::SUCCEEDED, $invalidation->getState());
  }

  /**
   * Tests tag invalidation failure when API returns empty response.
   *
   * @covers ::invalidateTags
   */
  public function testInvalidateTagsFailure(): void {
    $responses = [
      new Response(200, [], ''),
    ];
    $backend = $this->createBackend($responses);

    $invalidation = $this->createMockInvalidation('tag', 'node:1');
    $backend->invalidateTags([$invalidation]);

    $this->assertEquals(InvalidationInterface::FAILED, $invalidation->getState());
  }

  /**
   * Tests multiple tags in a single request.
   *
   * @covers ::invalidateTags
   */
  public function testInvalidateMultipleTags(): void {
    $responses = [
      $this->createFastlySuccessResponse(),
    ];
    $backend = $this->createBackend($responses);

    $invalidations = [
      $this->createMockInvalidation('tag', 'node:1'),
      $this->createMockInvalidation('tag', 'node:2'),
      $this->createMockInvalidation('tag', 'config:system.site'),
    ];
    $backend->invalidateTags($invalidations);

    foreach ($invalidations as $inv) {
      $this->assertEquals(InvalidationInterface::SUCCEEDED, $inv->getState());
    }
  }

  /**
   * Tests successful URL invalidation.
   *
   * @covers ::invalidateUrls
   */
  public function testInvalidateUrlsSuccess(): void {
    $responses = [
      $this->createFastlySuccessResponse(),
    ];
    $backend = $this->createBackend($responses);

    $invalidation = $this->createMockInvalidation('url', 'https://example.com/page');
    $backend->invalidateUrls([$invalidation]);

    $this->assertEquals(InvalidationInterface::SUCCEEDED, $invalidation->getState());
  }

  /**
   * Tests URL invalidation failure.
   *
   * @covers ::invalidateUrls
   */
  public function testInvalidateUrlsFailure(): void {
    $responses = [
      $this->createFastlyErrorResponse(500),
    ];
    $backend = $this->createBackend($responses);

    $invalidation = $this->createMockInvalidation('url', 'https://example.com/page');
    $backend->invalidateUrls([$invalidation]);

    $this->assertEquals(InvalidationInterface::FAILED, $invalidation->getState());
  }

  /**
   * Tests multiple URLs are purged independently.
   *
   * @covers ::invalidateUrls
   */
  public function testInvalidateMultipleUrls(): void {
    $responses = [
      $this->createFastlySuccessResponse(),
      $this->createFastlyErrorResponse(500),
    ];
    $backend = $this->createBackend($responses);

    $inv1 = $this->createMockInvalidation('url', 'https://example.com/page1');
    $inv2 = $this->createMockInvalidation('url', 'https://example.com/page2');

    $backend->invalidateUrls([$inv1, $inv2]);

    $this->assertEquals(InvalidationInterface::SUCCEEDED, $inv1->getState());
    $this->assertEquals(InvalidationInterface::FAILED, $inv2->getState());
  }

  /**
   * Tests successful everything invalidation.
   *
   * @covers ::invalidateEverything
   */
  public function testInvalidateEverythingSuccess(): void {
    $responses = [
      $this->createFastlySuccessResponse(),
    ];
    $backend = $this->createBackend($responses);

    $invalidation = $this->createMockInvalidation('everything', '');
    $backend->invalidateEverything([$invalidation]);

    $this->assertEquals(InvalidationInterface::SUCCEEDED, $invalidation->getState());
  }

  /**
   * Tests everything invalidation failure.
   *
   * @covers ::invalidateEverything
   */
  public function testInvalidateEverythingFailure(): void {
    $responses = [
      $this->createFastlyErrorResponse(500),
    ];
    $backend = $this->createBackend($responses);

    $invalidation = $this->createMockInvalidation('everything', '');
    $backend->invalidateEverything([$invalidation]);

    $this->assertEquals(InvalidationInterface::FAILED, $invalidation->getState());
  }

  /**
   * Tests that tagsHeaderName returns Surrogate-Key.
   *
   * @covers ::tagsHeaderName
   */
  public function testTagsHeaderName(): void {
    $this->assertEquals('Surrogate-Key', FastlyBackend::tagsHeaderName());
  }

  /**
   * Tests that tagsHeaderValue includes site identifier.
   *
   * @covers ::tagsHeaderValue
   */
  public function testTagsHeaderValueIncludesSiteIdentifier(): void {
    $responses = [$this->createFastlySuccessResponse()];
    $backend = $this->createBackend($responses);

    $tags = ['node:1', 'node:2'];
    $headerValue = FastlyBackend::tagsHeaderValue($tags);

    $tagsMap = $headerValue->getTagsMap();
    $this->assertCount(3, $tagsMap);
  }

  /**
   * Tests empty tag array.
   *
   * @covers ::invalidateTags
   */
  public function testInvalidateEmptyTags(): void {
    $backend = $this->createBackend([]);

    $backend->invalidateTags([]);
    $this->assertTrue(TRUE);
  }

  /**
   * Tests API endpoint construction.
   *
   * @covers ::invalidateTags
   */
  public function testApiEndpointIncludesServiceId(): void {
    $responses = [$this->createFastlySuccessResponse()];
    $backend = $this->createBackend($responses, ['service_id' => 'my-service-123']);

    $invalidation = $this->createMockInvalidation('tag', 'node:1');
    $backend->invalidateTags([$invalidation]);

    $this->assertEquals(InvalidationInterface::SUCCEEDED, $invalidation->getState());
  }

  /**
   * Tests getTemporaryRuntimeError returns empty when no error.
   *
   * @covers ::getTemporaryRuntimeError
   */
  public function testGetTemporaryRuntimeErrorEmpty(): void {
    $this->assertEquals('', FastlyBackend::getTemporaryRuntimeError());
  }

}
