<?php

// src/Command/ActivityRepairCommand.php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;


class ActivityRepairCommand extends Command
{
  protected static $defaultName = 'repair:set-pace';

  protected function configure()
  {
    $this
      ->setDescription('Set distances based on specified pace(s)')
      ->setHelp('Takes a FIT file and JSON file as input and creates a
        TCX file where the distances are modified to match the paces
        specified in the JSON file')
      ->addOption(
        'activity',
        'a',
        InputOption::VALUE_REQUIRED,
        'File path for the activity to be modified',
        null
      )
    ;
  }

  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $helper = $this->getHelper('question');

    $question = new Question(
        'File path for activity: ',
        $input->getOption('activity')
    );
    $input->setOption(
      'activity',
      $helper->ask($input, $output, $question)
    );

  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    echo $input->getOption('activity');
    echo "\nhello\n";
  }
}
