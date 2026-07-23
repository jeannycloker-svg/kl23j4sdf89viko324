<?php

namespace Drupal\Tests\acquia_purge\Traits;

use Drupal\acquia_purge\Http\AcquiaCloudBalancerMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Provides HTTP client mocking for Acquia Purge tests.
 */
trait AcquiaPurgeTestHttpClientTrait {

  /**
   * The mock handler for intercepting HTTP requests.
   */
  protected MockHandler $mockHandler;

  /**
   * Create a mock HTTP client with predefined responses.
   *
   * @param array $responses
   *   Array of Response objects to return.
   * @param bool $withMiddleware
   *   Whether to include AcquiaCloudBalancerMiddleware.
   *
   * @return \GuzzleHttp\ClientInterface
   *   The configured mock client.
   */
  protected function createMockHttpClient(array $responses, bool $withMiddleware = TRUE): ClientInterface {
    $this->mockHandler = new MockHandler($responses);
    $handlerStack = HandlerStack::create($this->mockHandler);

    if ($withMiddleware) {
      $middleware = new AcquiaCloudBalancerMiddleware();
      $handlerStack->push($middleware());
    }

    return new Client(['handler' => $handlerStack]);
  }

  /**
   * Create a successful Fastly purge response.
   *
   * @param string $id
   *   Optional purge ID.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The mock response.
   */
  protected function createFastlySuccessResponse(string $id = 'test-purge-id'): Response {
    return new Response(200, ['Content-Type' => 'application/json'], json_encode([
      'status' => 'ok',
      'id' => $id,
    ]));
  }

  /**
   * Create a Fastly invalid credentials response.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The mock response.
   */
  protected function createFastlyInvalidCredentialsResponse(): Response {
    return new Response(401, ['Content-Type' => 'application/json'], json_encode([
      'msg' => 'Provided credentials are missing or invalid',
      'detail' => 'The API key provided is invalid.',
    ]));
  }

  /**
   * Create a Fastly service not found response.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The mock response.
   */
  protected function createFastlyServiceNotFoundResponse(): Response {
    return new Response(404, ['Content-Type' => 'application/json'], json_encode([
      'msg' => 'Record not found',
      'detail' => 'Cannot find service',
    ]));
  }

  /**
   * Create a Fastly error response.
   *
   * @param int $status
   *   The HTTP status code.
   * @param string $message
   *   The error message.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The mock response.
   */
  protected function createFastlyErrorResponse(int $status = 500, string $message = 'Internal Server Error'): Response {
    return new Response($status, ['Content-Type' => 'application/json'], json_encode([
      'msg' => $message,
    ]));
  }

  /**
   * Create a successful Varnish BAN /tags response.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The mock response.
   */
  protected function createVarnishBanTagsResponse(): Response {
    return new Response(200, [], '', '1.1', 'Tags banned.');
  }

  /**
   * Create a successful Varnish BAN /site response.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The mock response.
   */
  protected function createVarnishBanSiteResponse(): Response {
    return new Response(200, [], '', '1.1', 'Site banned.');
  }

  /**
   * Create a successful Varnish BAN URL response.
   *
   * @param bool $wildcard
   *   Whether this is a wildcard URL ban.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The mock response.
   */
  protected function createVarnishBanUrlResponse(bool $wildcard = FALSE): Response {
    $reason = $wildcard ? 'WILDCARD URL banned.' : 'URL banned.';
    return new Response(200, [], '', '1.1', $reason);
  }

  /**
   * Create a successful Varnish PURGE response.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The mock response.
   */
  protected function createVarnishPurgeResponse(): Response {
    return new Response(200, [], '');
  }

  /**
   * Create a Varnish 404 response (valid for PURGE).
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The mock response.
   */
  protected function createVarnishNotFoundResponse(): Response {
    return new Response(404, [], '');
  }

  /**
   * Create a Varnish error response.
   *
   * @param int $status
   *   The HTTP status code.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The mock response.
   */
  protected function createVarnishErrorResponse(int $status = 403): Response {
    return new Response($status, [], '');
  }

  /**
   * Get the number of remaining mock responses.
   *
   * @return int
   *   The count of remaining responses.
   */
  protected function getMockResponseCount(): int {
    return $this->mockHandler->count();
  }

}
