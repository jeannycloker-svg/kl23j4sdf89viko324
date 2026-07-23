<?php

namespace Drupal\redirect_404\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\State\StateInterface;
use Drupal\redirect\Entity\Redirect;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\redirect_404\RedirectNotFoundStorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Hook implementations for redirect_404.
 */
class Redirect404Hooks {
  use StringTranslationTrait;

  public function __construct(
    protected RedirectNotFoundStorageInterface $redirectNotFoundStorage,
    protected StateInterface $state,
    protected RequestStack $requestStack,
    protected TimeInterface $time,
    protected ConfigFactoryInterface $configFactory,
  ) {

  }

  /**
   * Implements hook_cron().
   *
   * Adds clean up job to drop the irrelevant rows from the redirect_404 table.
   */
  #[Hook('cron')]
  public function cron(): void {
    $this->redirectNotFoundStorage->purgeOldRequests();
    $last_daily_reset = $this->state->get('redirect_404.last_daily_reset', 0);
    if (date('d', $last_daily_reset) != date('d')) {
      $this->redirectNotFoundStorage->resetDailyCount();
      $this->state->set('redirect_404.last_daily_reset', $this->time->getRequestTime());
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for system_logging_settings().
   */
  #[Hook('form_redirect_settings_form_alter')]
  public function formRedirectSettingsFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    $config = $this->configFactory->getEditable('redirect_404.settings');
    $row_limits = [
      100,
      1000,
      10000,
      100000,
      1000000,
    ];
    $form['row_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('404 error database logs to keep'),
      '#default_value' => $config->get('row_limit'),
      '#options' => [
        0 => $this->t('All'),
      ] + array_combine($row_limits, $row_limits),
      '#description' => $this->t('The maximum number of 404 error logs to keep in the database log. Requires a <a href=":cron">cron maintenance task</a>.', [
        ':cron' => Url::fromRoute('system.status')->toString(),
      ]),
    ];
    $form['reset_404'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear all 404 log entries'),
      '#submit' => [
        'redirect_404_reset_submit',
      ],
    ];
    $ignored_pages = $config->get('pages');
    // Add a new path to be ignored, if there is an ignore argument in the
    // query.
    if ($path_to_ignore = $this->requestStack->getCurrentRequest()->query->get('ignore')) {
      $ignored_pages .= $path_to_ignore;
    }
    // Replace '\r\n' with '\n' to keep consistency in tests.
    // See: https://www.drupal.org/project/redirect/issues/3244924
    $ignored_pages = str_replace("\r\n", "\n", $ignored_pages);
    $form['ignore_pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pages to ignore'),
      '#default_value' => $ignored_pages,
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. An example path is %user-wildcard for every user page. %front is the front page.", [
        '%user-wildcard' => '/user/*',
        '%front' => '<front>',
      ]),
    ];
    $form['clear_ignored'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clear ignored 404 log entries when saving this form'),
      '#default_value' => FALSE,
    ];
    $form['suppress_404'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Suppress 'page not found' log messages"),
      '#default_value' => $config->get('suppress_404'),
      '#description' => $this->t("Prevents logging 'page not found' events. Can be safely enabled when redirect_404 module is used, which stores them separately, nothing else relies on those messages."),
    ];
    $form['#submit'][] = 'redirect_404_logging_settings_submit';
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for redirect entities.
   */
  #[Hook('redirect_presave')]
  public function redirectPresave(Redirect $redirect): void {
    $path = $redirect->getSourcePathWithQuery();
    $langcode = $redirect->get('language')->value;
    if ($langcode == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      $langcode = NULL;
    }
    // Mark a potentially existing log entry for this path as resolved.
    $this->redirectNotFoundStorage->resolveLogRequest($path, $langcode);
  }

}
