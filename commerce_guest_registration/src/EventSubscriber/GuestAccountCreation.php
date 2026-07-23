<?php

namespace Drupal\commerce_guest_registration\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\user\Entity\User;

/**
 * Class to manage guest account creation.
 */
class GuestAccountCreation implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current active database's master connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Commerce Guest Registration config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a new OrderCompleteRegistrationSubscriber object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The current active database's master connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, Connection $database, ConfigFactoryInterface $config_factory) {
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('commerce_guest_registration.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['commerce_order.place.pre_transition'] = ['accountCreationHandler'];

    return $events;
  }

  /**
   * Method is call commerce_order.place.post_transition event is dispatched.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function accountCreationHandler(WorkflowTransitionEvent $event) {

    /** @var \Drupal\commerce_order\Entity\OrderInterface $orderObj */
    // Getting current order object.
    $orderObj = $event->getEntity();

    // Sees if current user has a UID or is an Anonymous user.
    if (!$orderObj->getCustomer()->id()) {
      $email = $orderObj->getEmail();

      // Tests to see if email already exits in the system.
      $existingUser = $this->doesUserExist($email);
      if ($existingUser !== FALSE) {
        if (!empty($this->config->get('assign_to_existing'))) {
          $event->getEntity()->setCustomer($existingUser);
        }
      }
      else {
        if (!empty($this->config->get('create_new'))) {
          $potentialNewUserName = $this->registrationCleanupUsername($email);
          $newName = $this->returnNewUsername($potentialNewUserName);
          $this->createNewUser($email, $newName, $event);
        }
      }
    }
  }

  /**
   * Testing to see if user email already exists.
   *
   * @param string $mail
   *   Email is needed to see if user already exists.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\EntityInterface[]|mixed
   *   The entity or FALSE if it does not exist.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function doesUserExist($mail) {

    $existingUser = $this->entityTypeManager
      ->getStorage('user')
      ->loadByProperties(['mail' => $mail]);

    $existingUser = reset($existingUser);
    if (is_object($existingUser) && $existingUser->id()) {
      return $existingUser;
    }

    return FALSE;
  }

  /**
   * Cleans up a potential new username.
   *
   * Runs username sanitation, e.g.:
   *     - Replaces two or more spaces with a single underscore and
   *       strips illegal characters.
   *     - The potential username will be created by taking every
   *       character before the `@` symbol.
   *
   * @param string $mail
   *   Email to be cleaned up to create username.
   *
   * @return string
   *   Cleaned and created potential new username.
   */
  protected function registrationCleanupUsername($mail) {
    $strippedIllegalCharacters = preg_replace('/[^\x{80}-\x{F7} a-zA-Z0-9@_.\'-]/', '', $mail);
    $splitName = explode('@', $strippedIllegalCharacters);
    $trimmedName = trim($splitName[0]);
    $spacesToUnderscores = preg_replace('/\s+/', '_', $trimmedName);
    // If there's nothing left use a default.
    return ('' === $spacesToUnderscores) ? $this->t('user') : $spacesToUnderscores;
  }

  /**
   * Creates a new username that is unique.
   *
   * @param string $name
   *   Potential new username.
   *
   * @return string
   *   New username that is going to be added to the into the database.
   *
   * @see user_validate_name()
   */
  protected function returnNewUsername($name) {
    // Creates a new username that is unique by testing the variable `$name`
    // against the `username_field_data` table in the database to see if
    // it exists. If the username already exists The method will concatenate
    // `'_' . $i` onto the end of `$name` until there is a negative match.
    $i = 0;
    do {
      $newName = empty($i) ? $name : $name . '_' . $i;
      $qry = "SELECT uid from {users_field_data} WHERE name = :name";
      $found = $this->database->queryRange($qry, 0, 1, [':name' => $newName])
        ->fetchAssoc();
      $i++;
    } while (!empty($found));

    return $newName;
  }

  /**
   * Creates a new user using drupal internal api.
   *
   * @param string $mail
   *   Email to be used by new.
   * @param string $name
   *   Username to be used by new user.
   * @param object $event
   *   Injected Event WorkflowTransitionEvent Object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNewUser($mail, $name, $event) {
    $language = $this->languageManager->getCurrentLanguage()->getId();

    // Creates a new user using drupal internal api and sends
    // the new user an email one time login. This allows
    // the user to set their password and gives them
    // the relevant access to the website.
    $userObj = User::create();

    // Mandatory.
    $userObj->setEmail($mail);
    $userObj->setUsername($name);
    $userObj->enforceIsNew();

    // Optional.
    $userObj->set('init', 'email');
    $userObj->set('langcode', $language);
    $userObj->set('preferred_langcode', $language);
    $userObj->set('preferred_admin_langcode', $language);
    $userObj->activate();

    // Create new account.
    $userObj->save();

    // Save the order.
    $event->getEntity()->setCustomer($userObj);

    // Email new user.
    if (!empty($this->config->get('send_one_time_login'))) {
      _user_mail_notify('register_no_approval_required', $userObj);
    }
  }

}
