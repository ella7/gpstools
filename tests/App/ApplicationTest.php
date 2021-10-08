<?php
namespace App\Tests\App;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\FITParser;
use App\Service\FITCSVWriter;

final class ApplicationTest extends KernelTestCase
{

  public function testReadAndWriteSampleActivityCSV(): void
  {
    self::bootKernel();
    $container      = static::getContainer();
    $fit_parser     = $container->get(FITParser::class);
    $fitcsv_writer  = $container->get(FITCSVWriter::class);

    // TODO: Make this more dynamic
    $input_path = '/Users/rpacker/Projects/gpstools/tests/Data/Activity.csv';
    $compare_path = '/Users/rpacker/Projects/gpstools/tests/Data/Activity_10_Messages.csv';
    $output_path = '/Users/rpacker/Projects/gpstools/tests/Data/Activity-Parsed.csv';

    $messages = $fit_parser->messagesFromCSVFile($input_path);
    $messages = array_slice($messages, 0, 100);

    $fitcsv_writer->CSVFileFromMessages($output_path, $messages);

    $this->assertFileEquals($compare_path, $output_path);
    // $this->assertStringEqualsFile($compare_path, $output);
  }
}
