<?php

namespace Drupal\wingsuit_companion\StreamWrapper;

use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\StreamWrapper\LocalReadOnlyStream;

/**
 * Defines the read-only ws-assets:// stream wrapper for theme files.
 */
class WingsuitStreamWrapper extends LocalReadOnlyStream {

  /**
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Config factory.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Request Stack.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * WingsuitStreamWrapper constructor.
   *
   * @param ConfigFactory $config_factory
   *   Config factory service.
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The Request Stack.
   */
  public function __construct(
    ConfigFactory $config_factory = NULL,
    RequestStack $request_stack = NULL
  ) {
    if ($config_factory === NULL) {
      $config_factory = \Drupal::service('config.factory');
    }
    if ($request_stack === NULL) {
      $request_stack = \Drupal::service('request_stack');
    }
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
  }

  /**
   * Create method.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack')
    );
  }

  /**
   * Returns the directory path to Wingsuit dist directory set in settings.php.
   *
   * Otherwise it will return the path to Wingsuit default dist location.
   *
   * @return string
   *   A string containing a URL that may be used to access the file.
   */
  public function getDirectoryPath() {
    $dist_path = $this->configFactory->get('wingsuit_companion.config')->get('dist_path');
    return $dist_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Wingsuit files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Local files stored under wingsuit dist directory.');
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    $dir = $this->getDirectoryPath();
    if (empty($dir)) {
      throw new \InvalidArgumentException(
        "Extension directory for {$this->uri} does not exist."
      );
    }

    $path = rtrim(base_path() . $dir . '/' . $this->getTarget(), '/');
    return $this->getRequest()->getUriForPath($path);
  }

  /**
   * Returns the current request object.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The current request object.
   */
  protected function getRequest() {
    return  $this->requestStack->getCurrentRequest();
  }

}
