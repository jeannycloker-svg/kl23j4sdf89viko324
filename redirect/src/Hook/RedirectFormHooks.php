<?php

declare(strict_types=1);

namespace Drupal\redirect\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\redirect\RedirectRepository;
use Drupal\Core\Url;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for redirect.
 */
class RedirectFormHooks {
  use StringTranslationTrait;

  public function __construct(
    protected LanguageManagerInterface $languageManager,
    protected RedirectRepository $redirectRepository,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RedirectDestinationInterface $redirectDestination,
    protected AccountInterface $currentUser,
  ) {

  }

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * (on behalf of locale.module)
   */
  #[Hook('form_redirect_edit_form_alter', module: 'locale')]
  public function localeFormRedirectEditFormAlter(array &$form, FormStateInterface $form_state): void {
    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => [
        LanguageInterface::LANGCODE_NOT_SPECIFIED => $this->t('All languages'),
      ] + $this->languageManager->getLanguages(),
      '#default_value' => $form['language']['#value'],
      '#description' => $this->t('A redirect set for a specific language will always be used when requesting this page in that language, and takes precedence over redirects set for <em>All languages</em>.'),
    ];
  }

  /**
   * Implements hook_form_node_form_alter().
   */
  #[Hook('form_node_form_alter')]
  public function formNodeFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $form_object->getEntity();
    if (!$node->isNew() && $node->toUrl()->isRouted() && $this->currentUser->hasPermission('administer redirects')) {
      $nid = $node->id();
      // Find redirects to this node.
      $redirects = $this->redirectRepository->findByDestinationUri([
        "internal:/node/{$nid}",
        "entity:node/{$nid}",
      ]);
      // Assemble the rows for the table.
      $rows = [];
      /** @var \Drupal\Core\Entity\EntityListBuilder $list_builder */
      $list_builder = $this->entityTypeManager->getListBuilder('redirect');
      foreach ($redirects as $redirect) {
        $row = [];
        $path = $redirect->getSourcePathWithQuery();
        $row['path'] = [
          'class' => [
            'redirect-table__path',
          ],
          'data' => [
            '#plain_text' => $path,
          ],
          'title' => $path,
        ];
        $row['operations'] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $list_builder->getOperations($redirect),
          ],
        ];
        $rows[] = $row;
      }
      // Add the list to the vertical tabs section of the form.
      $header = [
            [
              'class' => [
                'redirect-table__path',
              ],
              'data' => $this->t('From'),
            ],
            [
              'class' => [
                'redirect-table__operations',
              ],
              'data' => $this->t('Operations'),
            ],
      ];
      $form['url_redirects'] = [
        '#type' => 'details',
        '#title' => $this->t('URL redirects'),
        '#group' => 'advanced',
        '#open' => FALSE,
        'table' => [
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#empty' => $this->t('No URL redirects available.'),
          '#attributes' => [
            'class' => [
              'redirect-table',
            ],
          ],
        ],
        '#attached' => [
          'library' => [
            'redirect/drupal.redirect.admin',
          ],
        ],
      ];
      if (!empty($rows)) {
        $form['url_redirects']['warning'] = [
          '#markup' => $this->t('Note: links open in the current window.'),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        ];
      }
      $form['url_redirects']['actions'] = [
        '#theme' => 'links',
        '#links' => [],
        '#attributes' => [
          'class' => [
            'action-links',
          ],
        ],
      ];
      $form['url_redirects']['actions']['#links']['add'] = [
        'title' => $this->t('Add URL redirect'),
        'url' => Url::fromRoute('redirect.add', [
          'redirect' => $node->toUrl()->getInternalPath(),
          'destination' => $this->redirectDestination->get(),
        ]),
        'attributes' => [
          'class' => [
            'button',
          ],
          'target' => '_blank',
        ],
      ];
    }
  }

}
