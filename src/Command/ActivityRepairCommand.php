<?php

// src/Command/ActivityRepairCommand.php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Ella7\Console\Command\Ella7Command;

class ActivityRepairCommand extends Ella7Command
{
  protected static $defaultName = 'repair:set-pace';

  protected function configure()
  {
    $this
      ->setDescription('Set distances based on specified pace(s)')
      ->setHelp('Takes a FIT file and JSON file as input and creates a TCX file where the distances are modified to match the paces specified in the JSON file')
      ->addOption(
        'activity_file_path',
        'a',
        InputOption::VALUE_REQUIRED,
        'File path for the activity to be modified',
        null
      )
      ->addOption(
        'pace_scheme_file_path',
        'p',
        InputOption::VALUE_REQUIRED,
        'File path for pace scheme json file',
        null
      )
    ;
    parent::configure();

    $this->setInteractiveOptions([
      'activity_file_path',
      'pace_scheme_file_path'
    ]);
  }

  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $this->setOptionInteractively('activity_file_path', 'File path for the activity to be modified', '', $input, $output);
    $this->setOptionInteractively('pace_scheme_file_path', 'File path for pace scheme json file', '', $input, $output);
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    echo "\nEXECUTION\n##################\nActivity Path: ".$input->getOption('activity_file_path');
    echo "\nhello\n";
  }
}
