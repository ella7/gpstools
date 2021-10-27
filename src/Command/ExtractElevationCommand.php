<?php
namespace App\Command;

use Ella7\Console\Command\InteractiveOptionCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use App\Service\GPSTrackFactory;
use App\Service\FITCSVWriter;
use App\Model\GPSTrack;
use Symfony\Component\Filesystem\Filesystem;

class ExtractElevationCommand extends InteractiveOptionCommand
{
  protected static $defaultName = 'gpstools:extractelevation';
  private $factory;

  public function __construct(GPSTrackFactory $factory)
  {
    $this->factory = $factory;
    $this->factory->disableCaching();
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setDescription('Output elevation from FIT file')
      ->setHelp('Takes a path to a FIT file and creats a json file with elevation data')
      ->addOption(
        'fitpath',
        null,
        InputOption::VALUE_OPTIONAL,
        'path to a valid .fit file',
        false
      )
      ->addOption(
        'output-format',
        null,
        InputOption::VALUE_OPTIONAL,
        'json or csv - default value is json',
        false
      )
    ;

    $question = new Question('Please provide the path to the FIT file' . "\n > ");
    $this->addInteractivityForOption('fitpath', self::INTERACTION_UNSET_ONLY, $question);

    $question = new ChoiceQuestion('Please select json or csv for the output file' . "\n > ", ['json', 'csv']);
    $this->addInteractivityForOption('output-format', self::INTERACTION_UNSET_ONLY, $question);
  }

// TODO: add testing
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $fit_path = $input->getOption('fitpath');
    $output_format = $input->getOption('output-format');

    $output_path = dirname($fit_path) . '/' . pathinfo($fit_path)['filename'].'_elevation.' . $output_format;
    $track = $this->factory->buildTrackFromFile($fit_path);
    $points = $track->elevationPoints();

    // GPSTrack needs to handle units, but for now we'll handle here.
    foreach ($points as $key => $point) {
      $points[$key][1] = round(GPSTrack::FEET_PER_METER * $points[$key][1]);
    }

    $content = '';

    if($output_format == 'json'){
      $content = json_encode($points);
    }

    if($output_format == 'csv'){
      foreach ($points as $point) {
        $content .= FITCSVWriter::str_putcsv($point) . "\n";
      }
    }

    $filesystem = new Filesystem();
    $filesystem->dumpFile($output_path, $content);
    return InteractiveOptionCommand::SUCCESS;
  }

}
