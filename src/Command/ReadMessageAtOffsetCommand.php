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

  public function __construct(LoggerInterface $logger)
  {
    $this->logger = $logger;
    parent::__construct();
  }

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
  // This can be used to get information about a specific message if the offset of that message is known
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
