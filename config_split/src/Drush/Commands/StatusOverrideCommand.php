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
  name: 'config-split:status-override',
  description: 'Override the status of a split via state.',
  aliases: ['csso'],
)]
final class StatusOverrideCommand extends Command {
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
      ->setHelp('Override the status of a split via state.')
      ->addArgument('split', InputArgument::REQUIRED, 'The split configuration to activate.')
      ->addArgument('status', InputArgument::OPTIONAL, 'One of: active|1|true| inactive|0|false| default||null|none.', '')
      ->addUsage('config-split:status-override development active');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $split = (string) $input->getArgument('split');
    $status = (string) $input->getArgument('status');
    $io = new SymfonyStyle($input, $output);

    return $this->cliService->statusOverride($split, $status, $io) ? self::SUCCESS : self::FAILURE;
  }

}
