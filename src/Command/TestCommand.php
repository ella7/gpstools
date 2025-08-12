<?php
namespace App\Command;

use Ella7\Console\Command\InteractiveOptionCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use App\Service\GPSTrackFactory;
use App\Service\FITCSVParser;
use App\Service\FITParser;
use App\Service\FITCSVWriter;
use App\Service\FITCSVTool;
use App\Utility\GlobalProfileGenerator;
use App\Model\FIT\GlobalProfileAccess;
use Psr\Log\LoggerInterface;

class TestCommand extends InteractiveOptionCommand
{
  protected static $defaultName = 'gpstools:test';
  private $factory;
  private $fit_parser;
  private $logger;

  /**
   * Initialize the command with its dependencies.
   *
   * Stores the injected services on the command, disables caching on the GPSTrackFactory,
   * and runs parent constructor initialization.
   */
  public function __construct(
    GPSTrackFactory $factory,
    FITCSVParser $fit_parser,
    FITCSVWriter $fitcsv_writer,
    FitCSVTool $fitcsv_tool,
    LoggerInterface $logger
  )
  {
    $this->fit_parser       = $fit_parser;
    $this->fitcsv_writer    = $fitcsv_writer;
    $this->factory          = $factory;
    $this->logger           = $logger;
    $this->factory->disableCaching();
    parent::__construct();
  }

  /**
   * Configures the console command: description, help text, options, and interactive prompts.
   *
   * Defines two optional options:
   * - `cmd`: selects a sub-command to run (interactive ChoiceQuestion populated from validSubCommands()).
   * - `path`: prompts for a FIT file path (interactive Question).
   *
   * The `cmd` interactivity is registered with INTERACTION_UNSET_ONLY so the choice is asked only when not provided;
   * the `path` interactivity is registered with INTERACTION_NONE (no prompting by default).
   */
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
    $this->addInteractivityForOption('path', self::INTERACTION_NONE, $question);
  }

  /**
   * Execute the selected interactive sub-command.
   *
   * Supported sub-commands and effects:
   * - "output_gpx": builds a GPSTrack from the provided path.
   * - "read_fit": reads messages from a FIT/CSV file and writes CSV header and each message line to the console output.
   * - "global_profile": prints field definitions obtained from the global profile.
   * - "new_fit_parser": constructs a FITParser for the given path and parses the file.
   * - "misc": constructs a FITParser, injects the command logger, parses the file and dumps the message with ID 9231.
   * - default: writes a notice about the executed sub-command.
   *
   * The method writes to the provided OutputInterface and may invoke parsers/builders that read files from the path option.
   *
   * @param InputInterface  $input  Command input (expects an 'cmd' option and optionally a 'path' option).
   * @param OutputInterface $output Command output used for writing results.
   * @return int Exit status (InteractiveOptionCommand::SUCCESS on success).
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {

    switch ($input->getOption('cmd')) {
      case 'output_gpx':
        $track = $this->factory->buildTrackFromFile($input->getOption('path'));
        //dump($track);
        break;

      case 'read_fit':
        $messages = $this->fit_parser->messagesFromCSVFile($input->getOption('path'));
        // $messages = array_slice($messages, 0, 100);

        $output->writeln($this->fitcsv_writer->getHeaderString(52));
         foreach($messages as $message){
           $output->writeln($this->fitcsv_writer->getCSVString($message));
         }
        break;

      case 'global_profile':
        print_r(GlobalProfileAccess::getFieldDefinitionByNames('event', 'data'));
        break;

      case 'new_fit_parser':
        $fp = new FITParser($input->getOption('path'));
        $fp->parseFile();
        break;

      case 'misc':
        $fp = new FITParser($input->getOption('path'));
        $fp->setLogger($this->logger);
        $file = $fp->parseFile();
        dump($file->getMessage(9231));
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

  /**
   * Get the list of supported sub-command names for this command.
   *
   * Returns an array of strings representing valid sub-commands accepted by the command's
   * interactive and non-interactive execution paths.
   *
   * @return string[] Valid sub-command identifiers.
   */
  protected function validSubCommands()
  {
    return [
      'output_gpx',
      'test',
      'read_fit',
      'global_profile',
      'new_fit_parser',
      'misc',
      'generate_global_profile'
    ];
  }

  protected function isValidSubCommand($cmd)
  {
    return in_array($cmd, $this->validSubCommands());
  }

}
