<?php
namespace App\Command;

use Ella7\Console\Command\InteractiveOptionCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use App\Service\GPSTrackFactory;
use Twig\Environment;
use Symfony\Component\Filesystem\Filesystem;

class Fit2GPXCommand extends InteractiveOptionCommand
{
  protected static $defaultName = 'gpstools:fit2gpx';
  private $factory;
  private $twig;

  public function __construct(GPSTrackFactory $factory, Environment $twig)
  {
    $this->factory = $factory;
    $this->factory->disableCaching();

    $this->twig = $twig;
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setDescription('Convert FIT file to GPX')
      ->setHelp('Takes a path to a FIT file and outputs a GPX file')
      ->addOption(
        'fitpath',
        null,
        InputOption::VALUE_OPTIONAL,
        'path to a valid .fit file',
        false
      )
    ;

    $question = new Question('Please provide the path to the FIT file' . "\n > ");
    $this->addInteractivityForOption('fitpath', self::INTERACTION_UNSET_ONLY, $question);
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $fit_path = $input->getOption('fitpath');
    $gpx_path = dirname($fit_path) . '/' . pathinfo($fit_path)['filename'].'.gpx';

    $track = $this->factory->buildTrackFromFile($fit_path);
    $track->setTwig($this->twig);

    $filesystem = new Filesystem();
    $filesystem->dumpFile($gpx_path, $track->getGPX());

    return InteractiveOptionCommand::SUCCESS;
  }

}
