<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use App\Service\GPSTrackFactory;
use Twig\Environment;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'gpstools:fit2gpx',
    description: 'Convert FIT file to GPX'
)]
class Fit2GPXCommand extends Command
{
  private $factory;
  private $twig;

  public function __construct(GPSTrackFactory $factory, Environment $twig)
  {
    $this->factory = $factory;
    $this->factory->disableCaching();

    $this->twig = $twig;
    parent::__construct();
  }

  protected function configure(): void
  {
    $this
      ->setHelp('Takes a path to a FIT file and outputs a GPX file')
      ->addOption(
        'fitpath',
        null,
        InputOption::VALUE_OPTIONAL,
        'path to a valid .fit file'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $fit_path = $input->getOption('fitpath');
    
    if (!$fit_path) {
      $helper = $this->getHelper('question');
      $question = new Question('Please provide the path to the FIT file' . "\n > ");
      $fit_path = $helper->ask($input, $output, $question);
    }
    
    if (!file_exists($fit_path)) {
      $output->writeln('<error>File not found: ' . $fit_path . '</error>');
      return Command::FAILURE;
    }
    
    $gpx_path = dirname($fit_path) . '/' . pathinfo($fit_path)['filename'].'.gpx';

    $track = $this->factory->buildTrackFromFile($fit_path);
    $track->setTwig($this->twig);

    $filesystem = new Filesystem();
    $filesystem->dumpFile($gpx_path, $track->getGPX());
    
    $output->writeln('<info>GPX file created: ' . $gpx_path . '</info>');

    return Command::SUCCESS;
  }
}