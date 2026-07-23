<?php

namespace Drupal\redirect_domain\Hook;

use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for redirect_domain.
 */
class RedirectDomainHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    $output = '';
    switch ($route_name) {
      case 'help.page.redirect_domain':
        $output = '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('The Redirect domain module allows users to redirect between domains.') . '</p>';
        $output .= '<h3>' . $this->t('Uses') . '</h3>';
        $output .= '<dt>' . $this->t('Manage domain redirects') . '</dt>';
        $output .= '<dd>' . $this->t('The domain redirect is accessed through <a href=":domainlist">Domain Redirects</a>. The user can add the domain redirects through the domain redirect table which consists of the domain from which it needs to be redirected, the sub path and the complete url destination to which it needs to be redirected. The module also supports the usage of a wildcard redirecting, thus many requests can be handled with one instance of domain redirect.', [
          ':domainlist' => Url::fromRoute('redirect_domain.domain_list')->toString(),
        ]) . '</dd>';
        return $output;

      case 'redirect_domain.domain_list':
        $output = '<p>' . $this->t('The domain redirect table consists of the domain from which it needs to be redirected, the sub path and the complete url destination to which it needs to be redirected.') . '</p>';
        $output .= '<h5>' . $this->t('Example Configuration') . '</h5>';
        $output .= '<ul>';
        $output .= '<li> example.com/redirect => redirected.com/example-path </li>';
        $output .= '<li> foo.com/* => bar.com </li>';
        $output .= '</ul>';
        $output .= '<h5>' . $this->t('Example Redirects') . '</h5>';
        $output .= '<ul>';
        $output .= '<li>' . $this->t('Request: example.com/redirect => Response: redirected.com/example-path') . '</li>';
        $output .= '<li>' . $this->t('Request: foo.com/any-path => Response: bar.com') . '</li>';
        $output .= '</ul>';
        $output .= '<h5>' . $this->t('Precedence') . '</h5>';
        $output .= $this->t('Top-down precedence is used. This means the first matching redirect that is found will be taken. For example, assume these redirect rules:') . '</p>';
        $output .= '<ul>';
        $output .= '<li>' . $this->t('foo.com/some/path => some-domain.com/path') . '</li>';
        $output .= '<li>' . $this->t('foo.com/* => wildcard-redirect.com') . '</li>';
        $output .= '<li>' . $this->t('foo.com/other/path => other-domain.com/path') . '</li>';
        $output .= '</ul>';
        $output .= '<p>' . $this->t('The following redirects would actually occur:') . '</p>';
        $output .= '<ul>';
        $output .= '<li>' . $this->t('foo.com/some/path => some-domain.com/path') . '</li>';
        $output .= '<li>' . $this->t('foo.com/other/path => wildcard-redirect.com') . '</li>';
        $output .= '</ul>';
        return $output;
    }
  }

}
