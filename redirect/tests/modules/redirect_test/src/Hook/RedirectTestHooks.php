<?php

declare(strict_types=1);

namespace Drupal\redirect_test\Hook;

use Drupal\redirect\Entity\Redirect;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for redirect_test.
 */
class RedirectTestHooks {

  /**
   * Implements hook_redirect_response_alter().
   */
  #[Hook('redirect_response_alter')]
  public function redirectResponseAlter(TrustedRedirectResponse $response, Redirect $redirect): void {
    $path = 'test/redirect/2/successful';
    $replace = 'test/redirect/other';
    if ($redirect->getRedirect()['uri'] == "internal:/" . $path) {
      $response->setTargetUrl(str_replace($path, $replace, $redirect->getRedirectUrl()->setAbsolute()->toString()));
    }
  }

}
