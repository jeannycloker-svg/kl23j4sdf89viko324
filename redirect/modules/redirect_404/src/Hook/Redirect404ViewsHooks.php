<?php

namespace Drupal\redirect_404\Hook;

use Drupal\redirect_404\RedirectNotFoundStorageInterface;
use Drupal\redirect_404\SqlRedirectNotFoundStorage;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for redirect_404.
 */
class Redirect404ViewsHooks {
  use StringTranslationTrait;

  public function __construct(
    protected RedirectNotFoundStorageInterface $redirectNotFoundStorage,
  ) {}

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData(): array {
    $data = [];
    // Only define views data if the service uses our specific implementation.
    if (!$this->redirectNotFoundStorage instanceof SqlRedirectNotFoundStorage) {
      return $data;
    }
    $data['redirect_404']['table']['group'] = $this->t('Redirect 404');
    $data['redirect_404']['table']['base'] = [
      'field' => '',
      'title' => $this->t('Fix 404 pages'),
      'help' => $this->t('Overview for 404 error paths with no redirect assigned yet.'),
    ];
    $data['redirect_404']['path'] = [
      'title' => $this->t('Path'),
      'help' => $this->t('The path of the request.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'string',
      ],
    ];
    $data['redirect_404']['langcode'] = [
      'title' => $this->t('Language'),
      'help' => $this->t('The language of this request.'),
      'field' => [
        'id' => 'redirect_404_langcode',
      ],
      'filter' => [
        'id' => 'language',
      ],
    ];
    $data['redirect_404']['count'] = [
      'title' => $this->t('Count'),
      'help' => $this->t('The number of requests with that path and language.'),
      'field' => [
        'id' => 'numeric',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'numeric',
      ],
    ];
    $data['redirect_404']['daily_count'] = [
      'title' => $this->t('Daily count'),
      'help' => $this->t('The number of requests with that path and language in a day.'),
      'field' => [
        'id' => 'numeric',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'numeric',
      ],
    ];
    $data['redirect_404']['timestamp'] = [
      'title' => $this->t('Timestamp'),
      'help' => $this->t('The timestamp of the last request with that path and language.'),
      'field' => [
        'id' => 'date',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'date',
      ],
    ];
    $data['redirect_404']['resolved'] = [
      'title' => $this->t('Resolved'),
      'help' => $this->t('Whether or not this path has a redirect assigned.'),
      'field' => [
        'id' => 'boolean',
      ],
      'filter' => [
        'id' => 'boolean',
        'label' => $this->t('Resolved'),
        'use_equal' => TRUE,
      ],
    ];
    $data['redirect_404']['redirect_404_operations'] = [
      'title' => $this->t('Operations'),
      'help' => $this->t('Provide operation buttons to handle the 404 path.'),
      'field' => [
        'id' => 'redirect_404_operations',
        'additional fields' => [
          'path',
          'langcode',
        ],
        'real field' => 'path',
      ],
    ];
    return $data;
  }

}
