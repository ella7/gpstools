<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use App\Service\GPSTrackFactory;
use App\Service\FITParser;
use App\Service\FITCSVWriter;
use App\Model\FIT\GlobalProfile;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'gpstools:test',
    description: 'Test command'
)]
class TestCommand extends Command
{
  private $factory;
  private $fit_parser;
  private $fitcsv_writer;

  public function __construct(GPSTrackFactory $factory, FITParser $fit_parser, FITCSVWriter $fitcsv_writer)
  {
    $this->fit_parser       = $fit_parser;
    $this->fitcsv_writer    = $fitcsv_writer;
    $this->factory          = $factory;
    $this->factory->disableCaching();
    parent::__construct();
  }

  protected function configure(): void
  {
    $this
      ->setHelp('A place for trying commands without the overhead of creating a full new command')
      ->addOption(
        'cmd',
        null,
        InputOption::VALUE_OPTIONAL,
        'sub-command to run'
      )
      ->addOption(
        'path',
        null,
        InputOption::VALUE_OPTIONAL,
        'path to a file'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $helper = $this->getHelper('question');
    
    $cmd = $input->getOption('cmd');
    if (!$cmd) {
      $question = new ChoiceQuestion('Which test command do you want to run?', $this->validSubCommands());
      $cmd = $helper->ask($input, $output, $question);
    }
    
    $path = $input->getOption('path');
    if (!$path && in_array($cmd, ['output_gpx', 'read_fit'])) {
      $question = new Question('Please provide the path to the FIT file' . "\n > ");
      $path = $helper->ask($input, $output, $question);
    }

    switch ($cmd) {
      case 'output_gpx':
        $track = $this->factory->buildTrackFromFile($path);
        $output->writeln('GPX track generated successfully');
        break;

      case 'read_fit':
        $messages = $this->fit_parser->messagesFromCSVFile($path);
        
        $output->writeln($this->fitcsv_writer->getHeaderString(52));
        foreach($messages as $message){
          $output->writeln($this->fitcsv_writer->getCSVString($message));
        }
        break;

      case 'global_profile':
        print_r(GlobalProfile::getFieldDefinition('event', 'data'));
        break;

      case 'misc':
        foreach (GlobalProfile::MESSAGE_TYPES as $message_type) {
          echo '\'' . $message_type['name'] . '\' => ' . $message_type['global_message_number'] . ",\n";
        }
        break;

      default:
        $output->writeln('Executing sub-command ' . $cmd);
        break;
    }

    return Command::SUCCESS;
  }

  protected function validSubCommands(): array
  {
    return [
      'output_gpx',
      'test',
      'read_fit',
      'global_profile',
      'misc'
    ];
  }
}