<?php

namespace Drupal\easy_responsive_images;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Manages easy responsive images.
 */
class EasyResponsiveImagesManager implements EasyResponsiveImagesManagerInterface {

  /**
   * The field formatter configuration.
   *
   * @var array|null
   */
  protected $configuration;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * Constructs a new Easy Responsive Images object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file url generator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, FileUrlGeneratorInterface $file_url_generator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * The initial image configuration array.
   *
   * @return array|null
   *   Returns the initial images configuration as an array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function initialImagesConfiguration(): ?array {
    if ($this->configuration === NULL) {
      $this->configuration = [];
      $image_style_storage = $this->entityTypeManager
        ->getStorage('image_style');
      $image_style_ids = $image_style_storage->getQuery()
        ->condition('name', 'responsive_', 'STARTS_WITH')
        ->execute();
      $loaded_styles = $image_style_storage->loadMultiple($image_style_ids);

      foreach ($loaded_styles as $style_name => $style) {
        $style_parts = explode('_', $style_name);
        // If style_parts array has 4 keys/values
        // (e.g. {"responsive", "16", "9", "100w"}) we are dealing
        // with a responsive image style.
        if (count($style_parts) === 4) {
          [, $aspect_w, $aspect_h, $width] = $style_parts;
          $width = (int) str_replace('w', '', $width);
          $height = (int) round($width / (int) $aspect_w * (int) $aspect_h);
          $group = $aspect_w . '_' . $aspect_h;

          // Process variables - build custom array.
          $this->configuration[$group][$style_name]['style'] = $style;
          $this->configuration[$group][$style_name]['width'] = $width;
          $this->configuration[$group][$style_name]['height'] = $height;
        }
        // If the style_parts array has two keys/values
        // (e.g. {"responsive", "1500w"}) we are dealing
        // with a scaling image.
        elseif (count($style_parts) === 2 && str_ends_with($style_parts[1], 'w')) {
          $width = str_replace('w', '', $style_parts[1]);
          $group = 'scale';

          // Process variables - build custom array.
          $this->configuration[$group][$style_name]['style'] = $style;
          $this->configuration[$group][$style_name]['width'] = $width;
        }
      }
      return $this->configuration;
    }
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getAspectRatios(): array {
    $images_configuration = $this->initialImagesConfiguration();
    unset($images_configuration['scale']);
    $options = [];

    foreach ($images_configuration as $key => $image_config_item) {
      $options[$key] = str_replace('_', ':', $key);
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getImagesByAspectRatio(string $uri, string $aspect_ratio): array {
    $images_configuration = $this->initialImagesConfiguration();
    $style_infos = $images_configuration[$aspect_ratio] ?? [];
    $style_urls = [];

    foreach ($style_infos as $style_info) {
      $style_url = $this->fileUrlGenerator->transformRelative($style_info['style']->buildUrl($uri));
      $style_url = $this->getBestFormatDerivatives($style_url, $uri);

      $style_urls[] = [
        'url' => $style_url,
        'width' => $style_info['width'],
        'height' => (int) $style_info['height'],
        'srcset_url' => $style_url . ' ' . $style_info['width'] . 'w',
      ];
    }

    usort($style_urls, static fn(array $a, array $b) => [$a['width']] <=> [$b['width']]);

    return $style_urls;
  }

  /**
   * {@inheritdoc}
   */
  public function getImagesByScale(string $uri): array {
    $images_configuration = $this->initialImagesConfiguration();
    $style_infos = $images_configuration['scale'] ?? [];
    $style_urls = [];

    foreach ($style_infos as $style_info) {
      $style_url = $this->fileUrlGenerator->transformRelative($style_info['style']->buildUrl($uri));
      $style_url = $this->getBestFormatDerivatives($style_url, $uri);

      $style_urls[] = [
        'url' => $style_url,
        'width' => $style_info['width'],
        'srcset_url' => $style_url . ' ' . $style_info['width'] . 'w',
      ];
    }

    usort($style_urls, fn(array $a, array $b) => [$a['width']] <=> [$b['width']]);

    return $style_urls;
  }

  /**
   * Get webp derivatives of an image.
   *
   * It checks if the module image_optimize_webp is installed
   * and the browser supports webp.
   *
   * @param string $url
   *   The url.
   * @param string $uri
   *   The uri.
   *
   * @return string
   *   Returns a WebP Derivative as a string.
   */
  protected function getWebpDerivatives(string $url, string $uri): string {
    // Check if a module creating WebP derivatives is available.
    if (!$this->moduleHandler->moduleExists('imageapi_optimize_webp') && !$this->moduleHandler->moduleExists('webp')) {
      return $url;
    }

    // Check if we have a local image with an extension.
    $path_parts = pathinfo($uri);
    if (UrlHelper::isExternal($uri) || !isset($path_parts['extension'])) {
      return $url;
    }

    $original_extension = '.' . $path_parts['extension'];
    $pos = strrpos($url, $original_extension);
    if ($pos !== FALSE) {
      $url = substr_replace($url, $original_extension . '.webp', $pos, strlen($original_extension));
    }

    return $url;
  }

  /**
   * Get avif derivatives of an image.
   *
   * It checks if the avif module is installed.
   *
   * @param string $url
   *   The url.
   * @param string $uri
   *   The uri.
   *
   * @return string
   *   Returns an Avif Derivative as a string.
   */
  protected function getAvifDerivatives(string $url, string $uri): string {
    // Check if a module creating WebP derivatives is available.
    if (!$this->moduleHandler->moduleExists('avif')) {
      return $url;
    }

    // Check if we have a local image with an extension.
    $path_parts = pathinfo($uri);
    if (UrlHelper::isExternal($uri) || !isset($path_parts['extension'])) {
      return $url;
    }

    $original_extension = '.' . $path_parts['extension'];
    $pos = strrpos($url, $original_extension);
    if ($pos !== FALSE) {
      $url = substr_replace($url, $original_extension . '.avif', $pos, strlen($original_extension));
    }

    return $url;
  }

  /**
   * Get the best format derivatives of an image.
   *
   * @param string $url
   *   The url.
   * @param string $uri
   *   The uri.
   *
   * @return string
   *   Returns the best format derivative as a string.
   */
  protected function getBestFormatDerivatives(string $url, string $uri): string {
    $avif_url = $this->getAvifDerivatives($url, $uri);
    if ($avif_url !== $url) {
      return $avif_url;
    }

    $webp_url = $this->getWebpDerivatives($url, $uri);
    if ($webp_url !== $url) {
      return $webp_url;
    }

    return $url;
  }

}
