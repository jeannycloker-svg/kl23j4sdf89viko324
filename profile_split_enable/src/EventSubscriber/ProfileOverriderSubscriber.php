<?php

namespace Drupal\profile_split_enable\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\StorageTransformEvent;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Extension\ProfileExtensionList;

/**
 * Class ProfileOverriderSubscriber.
 *
 * Event Subscriber for overriding the profile being imported or exported.
 *
 * @package Drupal\profile_split_enable\EventSubscriber
 */
class ProfileOverriderSubscriber implements EventSubscriberInterface {

  use ContainerAwareTrait;

  /**
   * Service that allows us to obtain the profile name and parent.
   *
   * @var ProfileExtensionList
   */
  private $profileExtensionList;

  /**
   * ProfileOverriderSubscriber constructor.
   *
   * @param ProfileExtensionList $profileExtensionList
   *   The list of extensions.
   */
  public function __construct(ProfileExtensionList $profileExtensionList) {
    $this->profileExtensionList = $profileExtensionList;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::STORAGE_TRANSFORM_IMPORT][] = ['onImportTransform'];
    $events[ConfigEvents::STORAGE_TRANSFORM_EXPORT][] = ['onExportTransform'];
    return $events;
  }

  /**
   * Transform storage for importing.
   *
   * The DB will contain our installed profile name. Override the value read
   * from the sync directory with the currently installed profile so they match.
   *
   * @param StorageTransformEvent $event
   *   The config storage transform event.
   *
   * @return void
   */
  public function onImportTransform(StorageTransformEvent $event) {
    $storage = $event->getStorage();
    $data = $storage->read('core.extension');

    // Tell Drupal the current profile is enabled and override base profile.
    $data['module'][$this->container->getParameter('install_profile')] = 1000;
    $data['profile'] = $this->container->getParameter('install_profile');

    $storage->write('core.extension', $data);
  }

  /**
   * Transform storage for exporting.
   *
   * Overwrite the core.extension details to match the base profile since this
   * value will be in the sync directory and we do not want to alter it.
   *
   * @param StorageTransformEvent $event
   *   The config storage transform event.
   *
   * @return void
   */
  public function onExportTransform(StorageTransformEvent $event) {
    $storage = $event->getStorage();
    $data = $storage->read('core.extension');

    // Get the profile hierarchy.
    $profileInfo = $this->profileExtensionList->getExtensionInfo($this->container->getParameter('install_profile'));

    if (isset($profileInfo['base profile']) && $profileInfo['base profile'] != 'lightning') {
      // Change the profile back to the base profile (unless it is lightning).
      $data['profile'] = $profileInfo['base profile'];
    }

    $storage->write('core.extension', $data);
  }

}
