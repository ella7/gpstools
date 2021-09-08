<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class TestCommand extends Command
{
  // the name of the command (the part after "bin/console")
  protected static $defaultName = 'gpstools:test';

  public function __construct()
  {
      //
      parent::__construct();
  }

  protected function configure()
  {

  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output->writeln(
      'Hello World'
    );

    return Command::SUCCESS;
  }

}
