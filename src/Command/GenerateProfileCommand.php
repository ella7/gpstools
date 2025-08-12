<?php
namespace App\Command;

use Ella7\Console\Command\InteractiveOptionCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use App\Utility\GlobalProfileGenerator;


class GenerateProfileCommand extends InteractiveOptionCommand
{
  protected static $defaultName = 'gpstools:generate-profile';

  /**
   * Configure the console command: description, help text, and CLI options.
   *
   * Adds the optional `--output-path` option (default: false) and registers an interactive
   * Question prompt that will ask for the output file path when the option is not provided.
   */
  protected function configure()
  {
    $this
      ->setDescription('generate a new GlobalProfile')
      ->setHelp('Execute a series of search and replace functions on the python version of the global profile')
      ->addOption(
        'output-path',
        null,
        InputOption::VALUE_OPTIONAL,
        'path for the newly created global profile',
        false
      )
    ;
    $question = new Question('Please provide the desired path for the output file' . "\n > ");
    $this->addInteractivityForOption('output-path', self::INTERACTION_UNSET_ONLY, $question);
  }

  /**
   * Executes the console command to generate a GlobalProfile.
   *
   * Reads the 'output-path' option from the console input and delegates profile creation
   * to GlobalProfileGenerator::generateProfile().
   *
   * @return int Command exit code (InteractiveOptionCommand::SUCCESS on success).
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output_path = $input->getOption('output-path');
    GlobalProfileGenerator::generateProfile($output_path);
    return InteractiveOptionCommand::SUCCESS;
  }

}
