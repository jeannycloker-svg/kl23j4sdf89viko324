<?php

declare(strict_types=1);

namespace Drupal\config_split;

use Drupal\config_split\Config\ConfigImporterTrait;
use Drupal\config_split\Config\StatusOverride;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\Console\Style\StyleInterface;

/**
 * The CLI service class for interoperability.
 *
 * @internal This service is not an api and may change at any time.
 */
class ConfigSplitCliService {

  use StorageCopyTrait;
  use ConfigImporterTrait;
  use StringTranslationTrait;

  /**
   * The return value indicating no changes were imported.
   */
  const NO_CHANGES = 'no_changes';

  /**
   * The return value indicating that the import is already in progress.
   */
  const ALREADY_IMPORTING = 'already_importing';

  /**
   * The return value indicating that the process is complete.
   */
  const COMPLETE = 'complete';

  /**
   * List of messages.
   *
   * @var array
   */
  protected $errors = [];

  /**
   * Constructor.
   *
   * @param \Drupal\config_split\ConfigSplitManager $manager
   *   The split manager.
   * @param \Drupal\Core\Config\StorageInterface $activeStorage
   *   Active Config Storage.
   * @param \Drupal\Core\Config\StorageInterface $syncStorage
   *   Sync Config Storage.
   * @param \Drupal\config_split\Config\StatusOverride $statusOverride
   *   The split status override service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation service.
   */
  public function __construct(
    private readonly ConfigSplitManager $manager,
    private readonly StorageInterface $activeStorage,
    private readonly StorageInterface $syncStorage,
    private readonly StatusOverride $statusOverride,
    TranslationInterface $stringTranslation,
  ) {
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * Handle the export interaction.
   *
   * @param string $split
   *   The split name to export.
   * @param \Symfony\Component\Console\Style\StyleInterface $io
   *   The io interface of the cli tool calling the method.
   * @param bool $confirmed
   *   Whether the export is already confirmed by the console input.
   */
  public function ioExport(string $split, StyleInterface $io, bool $confirmed = FALSE): bool {
    $config = $this->getSplitFromArgument($split, $io);
    if ($config === NULL) {
      return FALSE;
    }

    if (!$config->get('status')) {
      $io->warning("Inactive splits can not not be exported.");
      return FALSE;
    }

    $message = $this->t('Export the split config configuration?');
    if ($confirmed || $io->confirm((string) $message)) {
      $target = $this->manager->singleExportTarget($config);
      self::replaceStorageContents($this->manager->singleExportPreview($config), $target);
      $io->success((string) $this->t("Configuration successfully exported."));
    }

    return TRUE;
  }

  /**
   * Handle the import interaction.
   *
   * @param string $split
   *   The split name to import.
   * @param \Symfony\Component\Console\Style\StyleInterface $io
   *   The $io interface of the cli tool calling.
   * @param bool $confirmed
   *   Whether the import is already confirmed by the console input.
   */
  public function ioImport(string $split, StyleInterface $io, bool $confirmed = FALSE): bool {
    $config = $this->getSplitFromArgument($split, $io);
    if ($config === NULL) {
      return FALSE;
    }

    $message = $this->t('Import the split config configuration?');
    $storage = $this->manager->singleImport($config, FALSE);

    if ($confirmed || $io->confirm((string) $message)) {
      return $this->tryImport($storage, $io);
    }
    return TRUE;
  }

  /**
   * Handle the activation interaction.
   *
   * @param string $split
   *   The split name to activate.
   * @param \Symfony\Component\Console\Style\StyleInterface $io
   *   The $io interface of the cli tool calling.
   * @param bool $confirmed
   *   Whether the import is already confirmed by the console input.
   */
  public function ioActivate(string $split, StyleInterface $io, bool $confirmed = FALSE): bool {
    $config = $this->getSplitFromArgument($split, $io);
    if ($config === NULL) {
      return FALSE;
    }

    $message = $this->t('Activate the split config configuration?');
    $storage = $this->manager->singleActivate($config, TRUE);

    if ($confirmed || $io->confirm((string) $message)) {
      return $this->tryImport($storage, $io);
    }
    return TRUE;
  }

  /**
   * Handle the deactivation interaction.
   *
   * @param string $split
   *   The split name to deactivate.
   * @param \Symfony\Component\Console\Style\StyleInterface $io
   *   The $io interface of the cli tool calling.
   * @param bool $confirmed
   *   Whether the import is already confirmed by the console input.
   * @param bool $override
   *   Allows the deactivation via override.
   */
  public function ioDeactivate(string $split, StyleInterface $io, bool $confirmed = FALSE, bool $override = FALSE): bool {
    $config = $this->getSplitFromArgument($split, $io);
    if ($config === NULL) {
      return FALSE;
    }

    $message = $this->t('Deactivate the split config configuration?');
    $storage = $this->manager->singleDeactivate($config, FALSE, $override);

    if ($confirmed || $io->confirm((string) $message)) {
      return $this->tryImport($storage, $io);
    }
    return TRUE;
  }

  /**
   * The hook to invoke after having exported all config.
   */
  public function postExportAll() {
    // We need to make sure the split config is also written to the permanent
    // split storage.
    $this->manager->commitAll();
  }

  /**
   * Get and set status config overrides.
   *
   * @param string $name
   *   The split name to override.
   * @param string|bool $status
   *   The status to set.
   * @param \Symfony\Component\Console\Style\StyleInterface $io
   *   The $io interface of the cli tool calling.
   */
  public function statusOverride(string $name, string|bool $status, StyleInterface $io) {
    if ($this->getSplitFromArgument($name, $io) === NULL) {
      return FALSE;
    }
    $map = [
      NULL => 'none/default',
      TRUE => 'active',
      FALSE => 'inactive',
    ];

    $settings = $this->statusOverride->getSettingsOverride($name);
    if ($settings !== NULL) {
      $io->caution((string) $this->t('The status for @name is overridden in settings.php to @status', ['@name' => $name, '@status' => $map[$settings]]));
    }

    if ($status === '') {
      $state = $this->statusOverride->getSplitOverride($name);
      $io->success((string) $this->t('The status override for @name is @status', ['@name' => $name, '@status' => $map[$state]]));
      return TRUE;
    }

    switch (strtolower((string) $status)) {
      case 'active':
      case '1':
      case 'true':
        $state = TRUE;
        break;

      case 'inactive':
      case '0':
      case 'false':
        $state = FALSE;
        break;

      case 'default':
      case 'null':
      case 'none':
        $state = NULL;
        break;

      default:
        throw new \InvalidArgumentException(sprintf('The status must be one of "active", "inactive", "default" or "none". %s given', $status));
    }

    $this->statusOverride->setSplitOverride($name, $state);
    $io->success((string) $this->t('The status override for @name was set to @status', ['@name' => $name, '@status' => $map[$state]]));
    return TRUE;
  }

  /**
   * Import the configuration.
   *
   * This is the quintessential config import.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The config storage to import from.
   *
   * @return string
   *   The state of importing.
   */
  private function import(StorageInterface $storage) {

    $comparer = new StorageComparer($storage, $this->activeStorage);

    if (!$comparer->createChangelist()->hasChanges()) {
      return static::NO_CHANGES;
    }

    $importer = $this->getConfigImporterFromComparer($comparer);

    if ($importer->alreadyImporting()) {
      return static::ALREADY_IMPORTING;
    }

    try {
      // Do the import with the ConfigImporter.
      $importer->import();
    }
    catch (ConfigImporterException $e) {
      // Catch and re-trow the ConfigImporterException.
      $this->errors = $importer->getErrors();
      throw $e;
    }

    return static::COMPLETE;
  }

  /**
   * Returns error messages created while running the import.
   *
   * @return array
   *   List of messages.
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Get the split from the argument.
   *
   * @param string $split
   *   The split name.
   * @param \Symfony\Component\Console\Style\StyleInterface $io
   *   The io object.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|null
   *   The split config.
   *
   * @throws \InvalidArgumentException
   *   When there is no split argument.
   */
  private function getSplitFromArgument(string $split, StyleInterface $io): ?ImmutableConfig {
    if (!$split) {
      throw new \InvalidArgumentException('Split can not be empty');
    }

    $config = $this->manager->getSplitConfig($split);
    if ($config === NULL) {
      // Try to get the split from the sync storage. This may not make sense
      // for all the operations.
      $config = $this->manager->getSplitConfig($split, $this->syncStorage);
      if ($config === NULL) {
        $io->error((string) $this->t('There is no split with name @name', ['@name' => $split]));
      }
    }

    return $config;
  }

  /**
   * Try importing the storage.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage to import.
   * @param \Symfony\Component\Console\Style\StyleInterface $io
   *   The io object.
   *
   * @return bool
   *   The success status.
   */
  private function tryImport(StorageInterface $storage, StyleInterface $io): bool {
    try {
      $status = $this->import($storage);
      switch ($status) {
        case ConfigSplitCliService::COMPLETE:
          $io->success((string) $this->t("Configuration successfully imported."));
          return TRUE;

        case ConfigSplitCliService::NO_CHANGES:
          $io->text((string) $this->t("There are no changes to import."));
          return TRUE;

        case ConfigSplitCliService::ALREADY_IMPORTING:
          $io->error(
            (string) $this->t("Another request may be synchronizing configuration already.")
          );
          return FALSE;

        default:
          $io->error((string) $this->t("Something unexpected happened"));
          return FALSE;
      }
    }
    catch (ConfigImporterException $e) {
      $io->error(
        (string) $this->t(
          'There have been errors importing: @errors',
          ['@errors' => strip_tags(implode("\n", $this->getErrors()))]
        )
      );
      return FALSE;
    }
  }

}
