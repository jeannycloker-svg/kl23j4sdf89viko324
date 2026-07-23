<?php

declare(strict_types=1);

namespace Drupal\config_split\Drush\Commands;

use Drupal\config_split\ConfigSplitCliService;
use Drush\Commands\AutowireTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
  name: 'config-split:deactivate',
  description: 'Deactivate a config split.',
  aliases: [],
)]
final class DeactivateCommand extends Command {
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
      ->setHelp('Deactivate a config split.')
      ->addArgument('split', InputArgument::REQUIRED, 'The split configuration to deactivate.')
      ->addOption('override', NULL, InputOption::VALUE_NEGATABLE, 'Allows the deactivation via override.')
      ->addUsage('config-split:deactivate development');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $split = (string) $input->getArgument('split');
    $io = new SymfonyStyle($input, $output);
    $override = (bool) $input->getOption('override');

    return $this->cliService->ioDeactivate($split, $io, FALSE, $override) ? self::SUCCESS : self::FAILURE;
  }

}
