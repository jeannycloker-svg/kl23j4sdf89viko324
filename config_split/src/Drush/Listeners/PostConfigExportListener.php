<?php

declare(strict_types=1);

namespace Drupal\config_split\Drush\Listeners;

use Drupal\config_split\ConfigSplitCliService;
use Drush\Commands\AutowireTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * The event listener to react to the config being exported.
 */
#[AsEventListener]
class PostConfigExportListener {

  use AutowireTrait;

  /**
   * The constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\config_split\ConfigSplitCliService $cliService
   *   The cli service.
   */
  public function __construct(
    protected readonly LoggerInterface $logger,
    protected readonly ConfigSplitCliService $cliService,
  ) {}

  /**
   * Export the splits after the config export.
   *
   * @param \Symfony\Component\Console\Event\ConsoleTerminateEvent $event
   *   The terminate event.
   */
  public function __invoke(ConsoleTerminateEvent $event): void {
    if ($event->getCommand()->getName() === 'config:export') {
      if ($event->getExitCode() === Command::SUCCESS) {
        $this->logger->info('Exporting all active configuration splits.');
        $this->cliService->postExportAll();
      }
      else {
        $this->logger->info('Did not export configuration splits.');
      }
    }
  }

}
