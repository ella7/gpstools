<?php
namespace App\Command;

use Ella7\Console\Command\InteractiveOptionCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use App\Service\GPSTrackFactory;

class TestCommand extends InteractiveOptionCommand
{
  protected static $defaultName = 'gpstools:test';
  private $factory;

  public function __construct(GPSTrackFactory $factory)
  {
    $this->factory = $factory;
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
        'fit',
        null,
        InputOption::VALUE_OPTIONAL,
        'path to the fit file',
        false
      )
    ;

    $question = new ChoiceQuestion('Which test command do you want to run?', $this->validSubCommands());
    $this->addInteractivityForOption('cmd', self::INTERACTION_UNSET_ONLY, $question);

    $question = new Question('Please provide the path to the FIT file' . "\n > ");
    $this->addInteractivityForOption('fit', self::INTERACTION_UNSET_ONLY, $question);
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    switch ($input->getOption('cmd')) {
      case 'output_gpx':
        $output->writeln($this->factory->buildTrackFromFile($input->getOption('fit')));
        break;

      default:
        $output->writeln('Executing sub-command ' . $input->getOption('cmd'));
        break;
    }

    $output->writeln($this->factory->getMsg());

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
