<?php
namespace App\Command;

use Ella7\Console\Command\InteractiveOptionCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use App\Service\GPSTrackFactory;
use App\Service\FITParser;
use App\Service\FITCSVWriter;

class TestCommand extends InteractiveOptionCommand
{
  protected static $defaultName = 'gpstools:test';
  private $factory;
  private $fit_parser;

  public function __construct(GPSTrackFactory $factory, FITParser $fit_parser, FITCSVWriter $fitcsv_writer)
  {
    $this->fit_parser       = $fit_parser;
    $this->fitcsv_writer    = $fitcsv_writer;
    $this->factory          = $factory;
    $this->factory->disableCaching();
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
        'path',
        null,
        InputOption::VALUE_OPTIONAL,
        'path to a file',
        false
      )
    ;

    $question = new ChoiceQuestion('Which test command do you want to run?', $this->validSubCommands());
    $this->addInteractivityForOption('cmd', self::INTERACTION_UNSET_ONLY, $question);

    $question = new Question('Please provide the path to the FIT file' . "\n > ");
    $this->addInteractivityForOption('path', self::INTERACTION_UNSET_ONLY, $question);
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    switch ($input->getOption('cmd')) {
      case 'output_gpx':
        $track = $this->factory->buildTrackFromFile($input->getOption('path'));
        //dump($track);
        break;

      case 'read_fit':
        $messages = $this->fit_parser->messagesFromCSVFile($input->getOption('path'));
        $debug_messages = array_slice($messages, 0, 2);

        foreach($debug_messages as $message){
          $output->writeln($this->fitcsv_writer->getCSVString($message));
        }
        //dump($debug_messages);
        break;

      default:
        $output->writeln('Executing sub-command ' . $input->getOption('cmd'));
        break;
    }

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
      'test',
      'read_path',
    ];
  }

  protected function isValidSubCommand($cmd)
  {
    return in_array($cmd, $this->validSubCommands());
  }

}
