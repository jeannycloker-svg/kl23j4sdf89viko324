<?php

namespace Drupal\wingsuit_page_manager\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\gin_lb\Service\ContextValidator;

/**
 * Sets the frontend theme to page manager layout builder page.
 */
class PageManagerThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config factory.
   *
   * @var \Drupal\gin_lb\Service\ContextValidator
   */
  protected $contextValidator;

  /**
   * PageManagerThemeNegotiator constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ContextValidator $context_validator) {
    $this->configFactory = $config_factory;
    $this->contextValidator = $context_validator;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $this->getActiveTheme($route_match) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->getActiveTheme($route_match);
  }

  /**
   * Determine the active theme for the current route.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return string
   *   The active theme or an empty string.
   */
  protected function getActiveTheme(RouteMatchInterface $route_match) {
    if ($this->contextValidator->isLayoutBuilderRoute()) {
      return $this->configFactory->get('system.theme')->get('default');
    }
  }

}
