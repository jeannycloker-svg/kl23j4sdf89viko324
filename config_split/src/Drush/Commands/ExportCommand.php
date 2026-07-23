<?php

declare(strict_types=1);

namespace Drupal\config_split\Drush\Commands;

use Drupal\config_split\ConfigSplitCliService;
use Drush\Commands\AutowireTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
  name: 'config-split:export',
  description: 'Export only split configuration to a directory.',
  aliases: [],
)]
final class ExportCommand extends Command {
  use AutowireTrait;

  /**
   * The constructor.
   *
   * @param \Drupal\config_split\ConfigSplitCliService $cliService
   *   The cli service which abstracts the operation.
   */
  public function __construct(
    protected readonly ConfigSplitCliService $cliService,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setHelp('Export only split configuration to a directory.')
      ->addArgument('split', InputArgument::REQUIRED, 'The split configuration to export.')
      ->addUsage('config-split:export development');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $split = (string) $input->getArgument('split');
    $io = new SymfonyStyle($input, $output);

    return $this->cliService->ioExport($split, $io) ? self::SUCCESS : self::FAILURE;
  }

}
