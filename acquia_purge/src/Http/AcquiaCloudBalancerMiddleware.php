<?php

namespace Drupal\acquia_purge\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * HTTP middleware which asserts correct responses from Acquia Cloud.
 */
class AcquiaCloudBalancerMiddleware {

  /**
   * {@inheritdoc}
   */
  public function __invoke() {
    return function (callable $handler) {
      return function ($req, array $options) use ($handler) {

        // Don't interfere on requests not going to Acquia Load balancers.
        if (!isset($options['acquia_purge_balancer_middleware'])) {
          return $handler($req, $options);
        }

        // Return a handler that throws exceptions on bad responses.
        return $handler($req, $options)->then(
          function (ResponseInterface $rsp) use ($req) {
            $status = $rsp->getStatusCode();
            $method = $req->getMethod();

            // Define a tiny closure that throws exceptions for us.
            $e = function ($msg) use ($req, $rsp) {
              throw new AcquiaCloudBalancerException($msg, $req, $rsp);
            };

            // Handle 421 with specific VCL guidance before other checks.
            if ($status == 421) {
              $e('Invalid response (421 Misdirected Request), check that your custom VCL ACL includes all application and web server EIPs.');
            }

            // Test response codes and reply messages per type of invalidation.
            if ($method == 'PURGE') {
              if (!in_array($status, [200, 404])) {
                if ($status >= 400 && $status < 500) {
                  $e("Invalid response ($status), please contact Acquia Support.");
                }
                $e('Invalid response (no 200/404), please contact Acquia Support.');
              }
            }
            elseif ($method == 'BAN') {
              $path = $req->getRequestTarget();
              $reply = $rsp->getReasonPhrase();
              if ($status !== 200) {
                if ($status >= 400 && $status < 500) {
                  $e("Invalid response ($status), please contact Acquia Support.");
                }
                $e('Invalid response (not 200), please contact Acquia Support.');
              }
              elseif (($path == '/site') && ($reply !== 'Site banned.')) {
                $e("Reply mismatch for /site.");
              }
              elseif (($path == '/tags') && ($reply !== 'Tags banned.')) {
                $e("Reply mismatch for /tags.");
              }
              elseif (!in_array($path, ['/site', '/tags'])) {
                if (!in_array($reply, ['WILDCARD URL banned.', 'URL banned.'])) {
                  $e("Reply mismatch for (wildcard)URL.");
                }
              }
            }
            else {
              $e("Unsupported HTTP method!");
            }
            return $rsp;
          }
        );
      };
    };
  }

}
