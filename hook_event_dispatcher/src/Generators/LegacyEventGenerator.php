<?php

namespace Drupal\hook_event_dispatcher\Generators;

use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class EventGenerator.
 */
class LegacyEventGenerator extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  protected $name = 'd8:hook:event-dispatcher';

  /**
   * {@inheritdoc}
   */
  protected $description = 'Generates hook event dispatcher class and kernel test';

  /**
   * {@inheritdoc}
   */
  protected $templatePath = __DIR__ . '/../../templates';

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $questions = Utils::moduleQuestions();

    $questions['event'] = new ChoiceQuestion('Type of event', [
      'hook' => 'Hook',
      'alter' => 'Alter',
    ]);
    $questions['hook_alter_name'] = new Question('Hook/Alter name (without <options=bold>hook_</> prefix and <options=bold>_alter</> suffix)');
    $questions['hook_alter_name']->setValidator([
      Utils::class, 'validateRequired',
    ]);

    $questions['sub_namespace'] = new Question('Sub namespace');
    $vars = &$this->collectVars($input, $output, $questions);

    $vars[$vars['event']] = $vars['hook_alter_name'];
    $vars['id'] = $vars['event'] === 'hook' ? $vars[$vars['event']] : $vars[$vars['event']] . '_alter';
    $vars['event_name'] = Utils::camelize($vars['id']);
    $vars['class'] = $vars['event_name'] . 'Event';
    $vars['type'] = strtoupper($vars['id']);

    $directory = $vars['sub_namespace'] ? 'src/Event/' . $vars['sub_namespace'] : 'src/Event';
    $this->addFile($directory . '/' . $vars['class'] . '.php')->template('event.twig');

    $testDirectory = $vars['sub_namespace'] ? 'tests/src/Kernel/' . $vars['sub_namespace'] : 'tests/src/Kernel';
    $this->addFile($testDirectory . '/' . $vars['class'] . 'Test.php')->template('kernel.twig');
  }

}
