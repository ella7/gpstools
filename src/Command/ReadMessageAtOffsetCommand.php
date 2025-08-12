<?php
namespace App\Command;

use Ella7\Console\Command\InteractiveOptionCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use App\Utility\GlobalProfileGenerator;
use App\Service\FITParser;
use Psr\Log\LoggerInterface;

class ReadMessageAtOffsetCommand extends InteractiveOptionCommand
{
  protected static $defaultName = 'gpstools:read-message';

  /**
   * Initialize the command and store the injected logger.
   *
   * Stores the provided LoggerInterface on $this->logger and calls the parent constructor.
   */
  public function __construct(LoggerInterface $logger)
  {
    $this->logger = $logger;
    parent::__construct();
  }

  /**
   * Configure the gpstools:read-message console command.
   *
   * Defines command description, help text, and four optional options:
   *  - --path        Path to the .fit file (optional).
   *  - --offset      Byte offset of the start of the message (optional).
   *  - --size        Number of bytes to read from the offset (optional).
   *  - --defn-offset Offset of the associated definition message (optional).
   *
   * Also registers interactive prompts for `path` and `offset` that are
   * activated only when the corresponding option is unset.
   */
  protected function configure()
  {
    $this
      ->setDescription('Read a message at a given offset')
      ->setHelp('Given a .fit file and an offset, read the messaage at that offset')
      ->addOption(
        'path',
        null,
        InputOption::VALUE_OPTIONAL,
        'path to the .fit file',
        false
      )
      ->addOption(
        'offset',
        null,
        InputOption::VALUE_OPTIONAL,
        'offset in bytes for the start of the message',
        false
      )
      ->addOption(
        'size',
        null,
        InputOption::VALUE_OPTIONAL,
        'size of the message in bytes',
        false
      )
      ->addOption(
        'defn-offset',
        null,
        InputOption::VALUE_OPTIONAL,
        'offset of the definition message',
        false
      )
    ;
    $question = new Question('Please provide the path for the .fit file' . "\n > ");
    $this->addInteractivityForOption('path', self::INTERACTION_UNSET_ONLY, $question);

    $question = new Question('What is the offset of the start of the message' . "\n > ");
    $this->addInteractivityForOption('offset', self::INTERACTION_UNSET_ONLY, $question);
  }

  // This command is not fully functional, and there is no error handling.
  /**
   * Read and display a FIT message located at a given byte offset in a FIT file.
   *
   * If a definition offset is provided, the command first reads the DefinitionMessage
   * at that offset and registers it as a local definition so the subsequent DataMessage
   * at the main offset can be interpreted. The DataMessage is read and dumped, and a
   * hex dump of `size` bytes starting at the message offset is written to stdout.
   *
   * Side effects: reads from the filesystem, writes dumps and hex output to stdout.
   *
   * @return int Symfony Console command exit status (InteractiveOptionCommand::SUCCESS on success).
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $path = $input->getOption('path');
    $offset = $input->getOption('offset');
    $size = $input->getOption('size');
    $defn_offset = $input->getOption('defn-offset');

    $fp = new FitParser($path);
    $fp->setLogger($this->logger);

    // in order to read a DataMessage, we have to first read a DefinitionMessage
    if($defn_offset){
      $fp->setPosition($defn_offset);
      $defn = $fp->readRecord();
      dump($defn);
      $i = 0;
      foreach($defn->getFields() as $field){
        for ($j=0; $j < $field->getSize(); $j++) {
          echo "Field $i |" . $field->getName() . PHP_EOL;
        }
        $i++;
      }
      $fp->addLocalDefinition($defn, $defn->getLocalNumber());
    }

    $fp->setPosition($offset);
    $message = $fp->readRecord();
    dump($message);

    $handle = fopen($path, 'r');
    fseek($handle, $offset);
    $relative_position = 0;
    while (!feof($handle) && $relative_position < $size) {
        echo bin2hex(fread ($handle , 1 )) . PHP_EOL;
        $relative_position++;
    }
    fclose($handle);
    return InteractiveOptionCommand::SUCCESS;
  }

}
