<?php

namespace Drupal\wingsuit_ui_patterns\TwigExtension;

use Drupal\Component\Utility\Html;
use Drupal\Core\Site\Settings;
use Drupal\Core\Template\TwigExtension;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class WingsuitExtension.
 */
class WingsuitExtension extends AbstractExtension {

  /**
   * Geting some twig value.
   */
  public function getFunctions() {
    return [
      new TwigFunction('ws_itok', [$this, 'wsItok']),
      new TwigFunction('uuid', [$this, 'wsUuid']),
    ];
  }

  /**
   * Uses deployment key as cache key for generated svgs.
   *
   * @return mixed|null
   *   A string containing a URL that may be used to access the file.
   */
  public static function wsItok() {
    return urlencode((string) Settings::get('deployment_identifier'));
  }

  /**
   * Returns a unique id.
   *
   * @return mixed|null
   *   A string containing a URL that may be used to access the file.
   */
  public static function wsUuid() {
    return Html::getId(\Drupal::service('uuid')->generate());
  }

}
