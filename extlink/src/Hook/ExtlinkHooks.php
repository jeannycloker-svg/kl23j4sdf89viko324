<?php

namespace Drupal\extlink\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\Icon\IconDefinitionInterface;
use Drupal\Core\Theme\Icon\Plugin\IconPackManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Hook implementations for extlink.
 */
class ExtlinkHooks {

  use StringTranslationTrait;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected MessengerInterface $messenger,
    protected ModuleHandlerInterface $moduleHandler,
    protected AdminContext $adminContext,
    protected RequestStack $requestStack,
    protected RendererInterface $renderer,
    protected ?IconPackManagerInterface $iconPackManager = NULL,
  ) {}

  /**
   * Implements hook_modules_installed().
   *
   * Be friendly to your users: show a message after installing the module.
   */
  #[Hook('modules_installed')]
  public function modulesInstalled($modules, $is_syncing): void {
    if (!in_array('extlink', $modules)) {
      return;
    }
    if ($is_syncing) {
      // Don't show the message when syncing configuration.
      return;
    }
    if (defined('MAINTENANCE_MODE') && constant('MAINTENANCE_MODE') === 'install') {
      // Don't show the message during installation.
      return;
    }
    $this->messenger->addStatus($this->t(
      'You can now <a href=":settingsUrl">configure the External Links module</a> for your site.',
      [':settingsUrl' => Url::fromRoute('extlink_admin.settings')->toString()]
    ));
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $arg) {
    if ($route_name == 'help.page.extlink') {
      $output = '<p>' . $this->t('External Links is used to differentiate between internal and external links. It will find all external links on a page and add an external icon indicating it will take you offsite or a mail icon for mailto links.') . '</p>';
      return ['#markup' => $output];
    }
  }

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    $config = $this->configFactory->get('extlink.settings');

    // Checks to see if external link is enabled on admin routes.
    if ($config->get('extlink_exclude_admin_routes') && $this->adminContext->isAdminRoute()) {
      return;
    }

    // Add the extlink.settings config as a cacheable dependency.
    $cacheable_metadata = CacheableMetadata::createFromRenderArray($attachments);
    $cacheable_metadata->addCacheableDependency($config);
    $cacheable_metadata->applyTo($attachments);

    $attachments['#attached']['library'][] = 'extlink/drupal.extlink';

    if (!empty($config->get('extlink_use_external_js_file'))) {
      // If using an external JS file, stop here.
      return;
    }

    $attachments['#attached']['drupalSettings']['data']['extlink'] = $this->getSettingsFromConfig($config);
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $extension): void {
    if (($extension === 'extlink') &&
        isset($libraries['drupal.extlink']) &&
        $this->configFactory->get('extlink.settings')->get('extlink_use_external_js_file')) {

      $host = $this->requestStack->getCurrentRequest()->getBasePath();
      $new_key[$host . '/extlink/settings.js'] = $libraries['extlink.settings']['js']['/extlink/settings.js'];
      $libraries['extlink.settings']['js'] = $new_key;

      // Add the external settings JS file as a dependency to the drupal.extlink
      // library so that it will be loaded when configuration is set to use the
      // external file.
      $libraries['drupal.extlink']['dependencies'][] = 'extlink/extlink.settings';
    }
  }

  /**
   * Prepares extlink JS drupalSettings from configuration.
   *
   * Public so the ExternalSettingsJsController can reuse the same builder when
   * serving the external settings JS file.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The extlink settings config object.
   *
   * @return array
   *   Associative array of extlink settings to use as JS drupalSettings.
   */
  public function getSettingsFromConfig(Config $config): array {
    $settings = $config->get();

    // Allow other modules to manipulate the settings.
    $this->moduleHandler->alter('extlink_settings', $settings);

    // Allow other modules to alter the excluded CSS selector settings.
    $this->moduleHandler->alter('extlink_css_exclude', $settings['extlink_css_exclude']);

    $callback = [Html::class, 'cleanCssIdentifier'];
    $fa_link_classes = $config->get('extlink_font_awesome_classes.links') ?: 'fa fa-external-link';
    $fa_link_classes = array_map($callback, explode(' ', $fa_link_classes));

    $fa_mailto_classes = $config->get('extlink_font_awesome_classes.mailto') ?: 'fa fa-envelope-o';
    $fa_mailto_classes = array_map($callback, explode(' ', $fa_mailto_classes));

    $fa_tel_classes = $config->get('extlink_font_awesome_classes.tel') ?: 'fa fa-phone';
    $fa_tel_classes = array_map($callback, explode(' ', $fa_tel_classes));

    // Icons.
    $linkIcon = '';
    $mailIcon = '';
    $telIcon = '';
    if ($this->iconPackManager !== NULL) {
      $linkIconSetting = (isset($settings['extlink_icons']['links']['icon'])) ? $settings['extlink_icons']['links']['icon'] : '';
      $icon = $this->iconPackManager->getIcon($linkIconSetting);
      if ($icon instanceof IconDefinitionInterface) {
        $renderable = $icon->getRenderable($settings['extlink_icons']['links']['icon'], $settings['extlink_icons']['links']['settings'][$icon->getPackId()] ?? []);
        $linkIcon = $this->renderer->render($renderable);
      }

      $mailIconSetting = (isset($settings['extlink_icons']['mailto']['icon'])) ? $settings['extlink_icons']['mailto']['icon'] : '';
      $icon = $this->iconPackManager->getIcon($mailIconSetting);
      if ($icon instanceof IconDefinitionInterface) {
        $renderable = $icon->getRenderable($settings['extlink_icons']['mailto']['icon'], $settings['extlink_icons']['mailto']['settings'][$icon->getPackId()] ?? []);
        $mailIcon = $this->renderer->render($renderable);
      }

      $telIconSetting = (isset($settings['extlink_icons']['tel']['icon'])) ? $settings['extlink_icons']['tel']['icon'] : '';
      $icon = $this->iconPackManager->getIcon($telIconSetting);
      if ($icon instanceof IconDefinitionInterface) {
        $renderable = $icon->getRenderable($settings['extlink_icons']['tel']['icon'], $settings['extlink_icons']['tel']['settings'][$icon->getPackId()] ?? []);
        $telIcon = $this->renderer->render($renderable);
      }
    }

    // Additional classes.
    $additional_link_classes = $config->get('extlink_additional_link_classes') ?: '';
    $additional_link_classes = array_map($callback, explode(' ', $additional_link_classes));

    $additional_mailto_classes = $config->get('extlink_additional_mailto_classes') ?: '';
    $additional_mailto_classes = array_map($callback, explode(' ', $additional_mailto_classes));

    $additional_tel_classes = $config->get('extlink_additional_tel_classes') ?: '';
    $additional_tel_classes = array_map($callback, explode(' ', $additional_tel_classes));

    $defaultText = $this->t('This link will take you to an external web site. We are not responsible for their content.');
    $extAlertText = $defaultText;
    if (!empty($settings['extlink_alert_text'])) {
      if (!is_array($settings['extlink_alert_text'])) {
        $extAlertText = (Xss::filterAdmin($settings['extlink_alert_text']));
      }
      else {
        $extAlertText = ((isset($settings['extlink_alert_text']['value'])) ? Html::escape($settings['extlink_alert_text']['value']) : $defaultText);
      }
    }

    return [
      'extTarget'           => ((isset($settings['extlink_target'])) ? $settings['extlink_target'] : FALSE),
      'extTargetAppendNewWindowDisplay' => ((isset($settings['extlink_target_display_default_title'])) ? $settings['extlink_target_display_default_title'] : FALSE),
      'extTargetAppendNewWindowLabel' => ((isset($settings['extlink_target_default_title_text'])) ? $settings['extlink_target_default_title_text'] : $this->t('(opens in a new window)')),
      'extTargetNoOverride' => ((isset($settings['extlink_target_no_override'])) ? $settings['extlink_target_no_override'] : FALSE),
      'extNofollow'         => ((isset($settings['extlink_nofollow'])) ? $settings['extlink_nofollow'] : FALSE),
      'extTitleNoOverride'  => ((isset($settings['extlink_title_no_override'])) ? $settings['extlink_title_no_override'] : FALSE),
      'extNoreferrer'       => ((isset($settings['extlink_noreferrer'])) ? $settings['extlink_noreferrer'] : FALSE),
      'extFollowNoOverride' => ((isset($settings['extlink_follow_no_override'])) ? $settings['extlink_follow_no_override'] : FALSE),
      'extClass'            => ((isset($settings['extlink_class'])) ? $settings['extlink_class'] : 'ext'),
      'extLabel'            => ((isset($settings['extlink_label'])) ? Html::escape($settings['extlink_label']) : $this->t('(link is external)')),
      'extImgClass'         => ((isset($settings['extlink_img_class'])) ? $settings['extlink_img_class'] : FALSE),
      'extSubdomains'       => ((isset($settings['extlink_subdomains'])) ? $settings['extlink_subdomains'] : TRUE),
      'extExclude'          => ((isset($settings['extlink_exclude'])) ? $settings['extlink_exclude'] : ''),
      'extInclude'          => ((isset($settings['extlink_include'])) ? $settings['extlink_include'] : ''),
      'extCssExclude'       => ((isset($settings['extlink_css_exclude'])) ? $settings['extlink_css_exclude'] : ''),
      'extCssInclude'       => ((isset($settings['extlink_css_include'])) ? $settings['extlink_css_include'] : ''),
      'extCssExplicit'      => ((isset($settings['extlink_css_explicit'])) ? $settings['extlink_css_explicit'] : ''),
      'extAlert'            => ((isset($settings['extlink_alert'])) ? $settings['extlink_alert'] : FALSE),
      'extAlertText'        => $extAlertText,
      'extHideIcons'        => ((isset($settings['extlink_hide_icons'])) ? $settings['extlink_hide_icons'] : FALSE),
      'mailtoClass'         => ((isset($settings['extlink_mailto_class'])) ? $settings['extlink_mailto_class'] : 'mailto'),
      'telClass'         => ((isset($settings['extlink_tel_class'])) ? $settings['extlink_tel_class'] : 'tel'),
      'mailtoLabel'         => ((isset($settings['extlink_mailto_label'])) ? Html::escape($settings['extlink_mailto_label']) : $this->t('(link sends email)')),
      'telLabel'         => ((isset($settings['extlink_tel_label'])) ? Html::escape($settings['extlink_tel_label']) : $this->t('(link is a phone number)')),
      'extUseFontAwesome'   => ((isset($settings['extlink_use_font_awesome'])) ? $settings['extlink_use_font_awesome'] : FALSE),
      'extIconPlacement'    => ((!empty($settings['extlink_icon_placement'])) ? $settings['extlink_icon_placement'] : 'append'),
      'extPreventOrphan' => $settings['extlink_prevent_orphan'] ?? FALSE,
      'extPreventOrphanTextLike' => $settings['extlink_prevent_orphan_text_like'] ?? TRUE,
      'extFaLinkClasses'    => implode(' ', $fa_link_classes),
      'extFaMailtoClasses'  => implode(' ', $fa_mailto_classes),
      'extUseIcon' => ((isset($settings['extlink_use_icon'])) ? $settings['extlink_use_icon'] : FALSE),
      'extLinkIcon' => $linkIcon,
      'extMailIcon' => $mailIcon,
      'extTelIcon' => $telIcon,
      'extAdditionalLinkClasses'      => implode(' ', $additional_link_classes),
      'extAdditionalMailtoClasses'    => implode(' ', $additional_mailto_classes),
      'extAdditionalTelClasses'    => implode(' ', $additional_tel_classes),
      'extFaTelClasses'  => implode(' ', $fa_tel_classes),
      'allowedDomains'  => $config->get('allowed_domains'),
      'extExcludeNoreferrer' => $settings['extlink_exclude_noreferrer'] ?? '',
    ];
  }

}
