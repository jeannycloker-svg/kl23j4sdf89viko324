<?php

namespace Drupal\easy_responsive_images\TwigExtension;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig filter to create a local image URL from an URI or external URL.
 */
class ImageUrl extends AbstractExtension {

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
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected StreamWrapperManagerInterface $streamWrapperManager;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * Constructs a new ImageUrl object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file url generator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, StreamWrapperManagerInterface $stream_wrapper_manager, FileUrlGeneratorInterface $file_url_generator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * Generates a list of all Twig filters that this extension defines.
   *
   * @return array
   *   An array of twig filters.
   */
  public function getFilters(): array {
    return [
      new TwigFilter('image_url', $this->createImageUrl(...)),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   *
   * @return string
   *   The extension name.
   */
  public function getName(): string {
    return 'easy_responsive_images.image_url';
  }

  /**
   * Get a local image URL from an URI or external URL.
   *
   * @param string|null $uri
   *   File URI or external image URL.
   * @param string|null $style
   *   The image style name.
   *
   * @return string
   *   The local image URL.
   */
  public function createImageUrl(?string $uri, ?string $style): string {
    $image_style = $this->entityTypeManager->getStorage('image_style')->load($style);
    if (!$uri || !$image_style) {
      return '';
    }

    // If we do not have a stream wrapper, it might be an external URL. If the
    // imagecache_external module is installed, try to get a local URI using
    // that module.
    if ($this->moduleHandler->moduleExists('imagecache_external')) {
      $stream_wrapper = $this->streamWrapperManager->getViaUri($uri);
      if (!$stream_wrapper) {
        $uri = imagecache_external_generate_path($uri);
      }
    }

    if (!$uri) {
      return '';
    }

    $file_url = $this->fileUrlGenerator->transformRelative($image_style->buildUrl($uri));

    // If the avif module is installed, return the avif version of the image.
    if ($this->moduleHandler->moduleExists('avif')) {
      $path_parts = pathinfo($uri);
      $original_extension = '.' . $path_parts['extension'];
      $pos = strrpos($file_url, $original_extension);
      if ($pos !== FALSE) {
        $file_url = substr_replace($file_url, $original_extension . '.avif', $pos, strlen($original_extension));
      }
    }
    // If the imageapi_optimize_webp module is installed and the browser
    // supports webp, return the webp version of the image.
    elseif ($this->moduleHandler->moduleExists('imageapi_optimize_webp') || $this->moduleHandler->moduleExists('webp')) {
      $path_parts = pathinfo($uri);
      $original_extension = '.' . $path_parts['extension'];
      $pos = strrpos($file_url, $original_extension);
      if ($pos !== FALSE) {
        $file_url = substr_replace($file_url, $original_extension . '.webp', $pos, strlen($original_extension));
      }
    }

    return $file_url;
  }

}
