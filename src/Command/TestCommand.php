<?php
namespace App\Command;

use Ella7\Console\Command\InteractiveOptionCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class TestCommand extends InteractiveOptionCommand
{
  protected static $defaultName = 'gpstools:test';

  protected function configure()
  {
    $this
      ->setDescription('Test command')
      ->setHelp('A place for trying commands without the overhead of creating a full new command')
      ->addOption(
        'cmd',
        null,
        InputOption::VALUE_OPTIONAL,
        'sub-command to run',
        false
      )
    ;

    $question = new ChoiceQuestion('Which test command do you want to run?', $this->validSubCommands());
    $this->addInteractivityForOption('cmd', self::INTERACTION_UNSET_ONLY, $question);
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output->writeln(
      $input->getOption('cmd')
    );

    return InteractiveOptionCommand::SUCCESS;
  }

  protected function cmdInputValidator($cmd)
  {
    if(!$this->isValidSubCommand($cmd)) {
      throw new \RuntimeException('\'' . $cmd . '\' is not in the list of valid commands.');
    }
    return $cmd;
  }

  protected function validSubCommands()
  {
    return [
      'output_gpx',
      'test'
    ];
  }

  protected function isValidSubCommand($cmd)
  {
    return in_array($cmd, $this->validSubCommands());
  }
}
