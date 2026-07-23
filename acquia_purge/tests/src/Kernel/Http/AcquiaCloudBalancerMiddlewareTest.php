<?php

namespace Drupal\Tests\acquia_purge\Kernel\Http;

use Drupal\acquia_purge\Http\AcquiaCloudBalancerException;
use Drupal\acquia_purge\Http\AcquiaCloudBalancerMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the AcquiaCloudBalancerMiddleware.
 *
 * @coversDefaultClass \Drupal\acquia_purge\Http\AcquiaCloudBalancerMiddleware
 * @group acquia_purge
 */
class AcquiaCloudBalancerMiddlewareTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Creates an HTTP client with the middleware attached.
   *
   * @param array $responses
   *   Mock responses to queue.
   *
   * @return \GuzzleHttp\Client
   *   The configured client.
   */
  protected function createClientWithMiddleware(array $responses): Client {
    $mockHandler = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mockHandler);

    $middleware = new AcquiaCloudBalancerMiddleware();
    $handlerStack->push($middleware());

    return new Client(['handler' => $handlerStack]);
  }

  /**
   * Tests that requests without middleware option pass through unchanged.
   *
   * @covers ::__invoke
   */
  public function testIgnoresNonAcquiaRequests(): void {
    $responses = [new Response(403, [], 'Forbidden')];
    $client = $this->createClientWithMiddleware($responses);

    $response = $client->request('GET', 'http://example.com', [
      'http_errors' => FALSE,
    ]);

    $this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * Tests that 403 response throws exception.
   *
   * @covers ::__invoke
   */
  public function testThrowsOn403Response(): void {
    $responses = [new Response(403)];
    $client = $this->createClientWithMiddleware($responses);

    $this->expectException(AcquiaCloudBalancerException::class);
    $this->expectExceptionMessage('Invalid response (403)');

    $client->request('PURGE', 'http://10.0.0.1/path', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);
  }

  /**
   * Tests that 405 response throws exception.
   *
   * @covers ::__invoke
   */
  public function testThrowsOn405Response(): void {
    $responses = [new Response(405)];
    $client = $this->createClientWithMiddleware($responses);

    $this->expectException(AcquiaCloudBalancerException::class);
    $this->expectExceptionMessage('Invalid response (405)');

    $client->request('PURGE', 'http://10.0.0.1/path', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);
  }

  /**
   * Tests that 421 response throws exception with VCL guidance.
   *
   * A 421 Misdirected Request typically indicates that the custom VCL ACL
   * is missing entries for application or web server EIPs.
   *
   * @covers ::__invoke
   */
  public function testThrowsOn421ResponseWithVclGuidance(): void {
    $responses = [new Response(421)];
    $client = $this->createClientWithMiddleware($responses);

    $this->expectException(AcquiaCloudBalancerException::class);
    $this->expectExceptionMessage('Invalid response (421 Misdirected Request), check that your custom VCL ACL includes all application and web server EIPs.');

    $client->request('PURGE', 'http://10.0.0.1/path', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);
  }

  /**
   * Tests that other 4xx responses throw generic exception.
   *
   * @covers ::__invoke
   */
  public function testThrowsOnOther4xxResponses(): void {
    $responses = [new Response(429)];
    $client = $this->createClientWithMiddleware($responses);

    $this->expectException(AcquiaCloudBalancerException::class);
    $this->expectExceptionMessage('Invalid response (429)');

    $client->request('PURGE', 'http://10.0.0.1/path', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);
  }

  /**
   * Tests that 200 PURGE response is accepted.
   *
   * @covers ::__invoke
   */
  public function testValidatesPurgeResponse200(): void {
    $responses = [new Response(200)];
    $client = $this->createClientWithMiddleware($responses);

    $response = $client->request('PURGE', 'http://10.0.0.1/path', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);

    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * Tests that 404 PURGE response is accepted.
   *
   * @covers ::__invoke
   */
  public function testValidatesPurgeResponse404(): void {
    $responses = [new Response(404)];
    $client = $this->createClientWithMiddleware($responses);

    $response = $client->request('PURGE', 'http://10.0.0.1/path', [
      'acquia_purge_balancer_middleware' => TRUE,
      'http_errors' => FALSE,
    ]);

    $this->assertEquals(404, $response->getStatusCode());
  }

  /**
   * Tests that invalid PURGE response throws exception.
   *
   * @covers ::__invoke
   */
  public function testPurgeInvalidResponseThrows(): void {
    $responses = [new Response(500)];
    $client = $this->createClientWithMiddleware($responses);

    $this->expectException(AcquiaCloudBalancerException::class);
    $this->expectExceptionMessage('Invalid response (no 200/404)');

    $client->request('PURGE', 'http://10.0.0.1/path', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);
  }

  /**
   * Tests that BAN /tags requires correct reason phrase.
   *
   * @covers ::__invoke
   */
  public function testValidatesBanTagsResponse(): void {
    $responses = [new Response(200, [], '', '1.1', 'Tags banned.')];
    $client = $this->createClientWithMiddleware($responses);

    $response = $client->request('BAN', 'http://10.0.0.1/tags', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);

    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * Tests that BAN /tags with wrong reason throws.
   *
   * @covers ::__invoke
   */
  public function testBanTagsWrongReasonThrows(): void {
    $responses = [new Response(200, [], '', '1.1', 'Wrong response.')];
    $client = $this->createClientWithMiddleware($responses);

    $this->expectException(AcquiaCloudBalancerException::class);
    $this->expectExceptionMessage('Reply mismatch for /tags');

    $client->request('BAN', 'http://10.0.0.1/tags', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);
  }

  /**
   * Tests that BAN /site requires correct reason phrase.
   *
   * @covers ::__invoke
   */
  public function testValidatesBanSiteResponse(): void {
    $responses = [new Response(200, [], '', '1.1', 'Site banned.')];
    $client = $this->createClientWithMiddleware($responses);

    $response = $client->request('BAN', 'http://10.0.0.1/site', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);

    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * Tests that BAN /site with wrong reason throws.
   *
   * @covers ::__invoke
   */
  public function testBanSiteWrongReasonThrows(): void {
    $responses = [new Response(200, [], '', '1.1', 'Wrong response.')];
    $client = $this->createClientWithMiddleware($responses);

    $this->expectException(AcquiaCloudBalancerException::class);
    $this->expectExceptionMessage('Reply mismatch for /site');

    $client->request('BAN', 'http://10.0.0.1/site', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);
  }

  /**
   * Tests that BAN URL accepts "URL banned." reason.
   *
   * @covers ::__invoke
   */
  public function testValidatesBanUrlResponse(): void {
    $responses = [new Response(200, [], '', '1.1', 'URL banned.')];
    $client = $this->createClientWithMiddleware($responses);

    $response = $client->request('BAN', 'http://10.0.0.1/path/to/resource', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);

    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * Tests that BAN URL accepts "WILDCARD URL banned." reason.
   *
   * @covers ::__invoke
   */
  public function testValidatesBanWildcardUrlResponse(): void {
    $responses = [new Response(200, [], '', '1.1', 'WILDCARD URL banned.')];
    $client = $this->createClientWithMiddleware($responses);

    $response = $client->request('BAN', 'http://10.0.0.1/path/*', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);

    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * Tests that BAN URL with wrong reason throws.
   *
   * @covers ::__invoke
   */
  public function testBanUrlWrongReasonThrows(): void {
    $responses = [new Response(200, [], '', '1.1', 'Wrong response.')];
    $client = $this->createClientWithMiddleware($responses);

    $this->expectException(AcquiaCloudBalancerException::class);
    $this->expectExceptionMessage('Reply mismatch for (wildcard)URL');

    $client->request('BAN', 'http://10.0.0.1/path/to/resource', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);
  }

  /**
   * Tests that non-200 BAN response throws exception.
   *
   * @covers ::__invoke
   */
  public function testBanNon200Throws(): void {
    $responses = [new Response(500, [], '', '1.1', 'Server error')];
    $client = $this->createClientWithMiddleware($responses);

    $this->expectException(AcquiaCloudBalancerException::class);
    $this->expectExceptionMessage('Invalid response (not 200)');

    $client->request('BAN', 'http://10.0.0.1/tags', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);
  }

  /**
   * Tests that unsupported HTTP method throws exception.
   *
   * @covers ::__invoke
   */
  public function testThrowsOnUnsupportedMethod(): void {
    $responses = [new Response(200)];
    $client = $this->createClientWithMiddleware($responses);

    $this->expectException(AcquiaCloudBalancerException::class);
    $this->expectExceptionMessage('Unsupported HTTP method');

    $client->request('GET', 'http://10.0.0.1/path', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);
  }

  /**
   * Tests that POST method throws exception.
   *
   * @covers ::__invoke
   */
  public function testThrowsOnPostMethod(): void {
    $responses = [new Response(200)];
    $client = $this->createClientWithMiddleware($responses);

    $this->expectException(AcquiaCloudBalancerException::class);
    $this->expectExceptionMessage('Unsupported HTTP method');

    $client->request('POST', 'http://10.0.0.1/path', [
      'acquia_purge_balancer_middleware' => TRUE,
    ]);
  }

}
