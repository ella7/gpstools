<?php
namespace App\Command;

use Ella7\Console\Command\InteractiveOptionCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

class TestCommand extends InteractiveOptionCommand
{
  protected static $defaultName = 'gpstools:test';

  public function __construct()
  {
      //
      parent::__construct();
  }

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
      ->addOption(
        'intopt',
        null,
        InputOption::VALUE_OPTIONAL,
        'just testing here',
        false
      )
    ;

    $question = new Question('This is the interactive question: ' . "\n> ");
    $this->addInteractivityForOption('intopt', self::INTERACTION_ALWAYS, $question);

  }

  protected function interact(InputInterface $input, OutputInterface $output)
  {
    parent::interact($input, $output);
    $helper = $this->getHelper('question');
    $cmd = $input->getOption('cmd');

    if(!$this->isValidSubCommand($cmd)){
      if($cmd){
        $this->cmdInputValidator($cmd);
      }
      $question = new Question('Which test command do you want to run?' . "\n> " , $this->validSubCommands()[0]);
      $question->setValidator(function ($answer) {
        return $this->cmdInputValidator($answer);
      });
      $input->setOption(
        'cmd',
        $helper->ask($input, $output, $question)
      );
    }
  }

  protected function cmdInputValidator($cmd)
  {
    if(!$this->isValidSubCommand($cmd)) {
      throw new \RuntimeException(
        '\'' . $cmd . '\' is not in the list of valid commands.'
      );
    }
    return $cmd;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output->writeln(
      $input->getOption('intopt')
    );

    return InteractiveOptionCommand::SUCCESS;
  }

  protected function validSubCommands()
  {
    return [
      'output_gpx'
    ];
  }

  protected function isValidSubCommand($cmd)
  {
    return in_array($cmd, $this->validSubCommands());
  }
}
