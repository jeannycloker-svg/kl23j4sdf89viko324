<?php

declare(strict_types=1);

namespace Drupal\redirect\Hook;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for redirect.
 */
class RedirectHelpHooks {
  use StringTranslationTrait;

  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected AccountInterface $currentUser,
  ) {

  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    $output = '';
    switch ($route_name) {
      case 'help.page.redirect':
        $output = '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('The Redirect module allows users to redirect from old URLs to new URLs.   For more information, see the <a href=":online">online documentation for Redirect</a>.', [
          ':online' => 'https://www.drupal.org/documentation/modules/path-redirect',
        ]) . '</p>';
        $output .= '<dl>';
        $output .= '<h3>' . $this->t('Uses') . '</h3>';
        $output .= '<dd>' . $this->t('Redirect is accessed from three tabs that help you manage <a href=":list">URL Redirects</a>.', [
          ':list' => Url::fromRoute('redirect.list')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Manage URL Redirects') . '</dt>';
        $output .= '<dd>' . $this->t('The <a href=":redirect">"URL Redirects"</a> page is used to setup and manage URL Redirects.  New redirects are created here using the <a href=":add_form">Add redirect</a> button which presents a form to simplify the creation of redirects . The URL redirects page provides a list of all redirects on the site and allows you to edit them.', [
          ':redirect' => Url::fromRoute('redirect.list')->toString(),
          ':add_form' => Url::fromRoute('redirect.add')->toString(),
        ]) . '</dd>';
        if ($this->moduleHandler->moduleExists('redirect_404')) {
          $output .= '<dt>' . $this->t('Fix 404 pages') . '</dt>';
          $output .= '<dd>' . $this->t('<a href=":fix_404">"Fix 404 pages"</a> lists all paths that have resulted in 404 errors and do not yet have any redirects assigned to them. This 404 (or Not Found) error message is an HTTP standard response code indicating that the client was able to communicate with a given server, but the server could not find what was requested.', [
            ':fix_404' => Url::fromRoute('redirect_404.fix_404')->toString(),
          ]) . '</dd>';
        }
        elseif (!$this->moduleHandler->moduleExists('redirect_404') && $this->currentUser->hasPermission('administer modules')) {
          $output .= '<dt>' . $this->t('Fix 404 pages') . '</dt>';
          $output .= '<dd>' . $this->t('404 (or Not Found) error message is an HTTP standard response code indicating that the client was able to communicate with a given server, but the server could not find what was requested. Please install the <a href=":extend">Redirect 404</a> submodule to be able to log all paths that have resulted in 404 errors.', [
            ':extend' => Url::fromRoute('system.modules_list')->toString(),
          ]) . '</dd>';
        }
        $output .= '<dt>' . $this->t('Configure Global Redirects') . '</dt>';
        $output .= '<dd>' . $this->t('The <a href=":settings">"Settings"</a> page presents you with a number of means to adjust redirect settings.', [
          ':settings' => Url::fromRoute('redirect.settings')->toString(),
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;
    }
  }

}
