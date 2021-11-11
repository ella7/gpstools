<?php
namespace App\Tests\App;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\FITCSVParser;
use App\Service\FITCSVWriter;
use App\Service\FITParser;
use Psr\Log\LoggerInterface;


final class ApplicationTest extends KernelTestCase
{

  public function setUp() : void
  {
    // TODO: Need to figure out best way to handle DI here. $container->get(FITParser::class)
    // for example, doesn't work. "service or alias has been removed or inlined when the container
    // was compiled"

    self::bootKernel();
    $container = static::getContainer();
    $this->logger      = $container->get(LoggerInterface::class);
    $this->project_dir = $container->get('kernel')->getProjectDir();
    $this->cache_dir =   $container->get('kernel')->getCacheDir();
    $this->csv_parser  = $container->get(FITCSVParser::class);
    $this->csv_writer  = $container->get(FITCSVWriter::class);
  }

  /**
   * @group slowTests
   */
  public function testParseFITCSVFile(): void
  {
    $n = 2;
    $this->csv_parser->setMessageLimit($n);

    $input_path  = $this->project_dir   . '/tests/resources/Activity.csv';
    $output_path = $this->cache_dir     . '/tests/TestParseFITCSVFile-Output.csv';

    $messages = $this->csv_parser->messagesFromCSVFile($input_path);
    $this->csv_writer->CSVFileFromMessages($output_path, $messages);

    $expected_lines = array_slice(file($input_path), 0, $n);
    $actual_lines   = file($output_path);

    for ($i=0; $i < $n; $i++) {
      $this->assertEquals(rtrim($expected_lines[$i], ",\n"), rtrim($actual_lines[$i], ",\n"));
    }
  }

  public function testParseFITFile(): void
  {

    $n = 1166; // number of rows to parse and compare with expected CSV file
    $fit_path           = $this->project_dir . '/tests/resources/Activity.fit';
    $expected_csv_path  = $this->project_dir . '/tests/resources/Activity.csv';
    $output_path        = $this->cache_dir   . '/tests/TestParseFITFile-Output.csv';

    $this->fit_parser  = new FITParser($fit_path);
    $this->fit_parser->setLogger($this->logger);
    $this->fit_parser->setMessageLimit($n);

    $fit_file = $this->fit_parser->parseFile();
    $this->csv_writer->writeFile($fit_file, $output_path);

    $expected_lines = array_slice(file($expected_csv_path), 0, $n);
    $actual_lines   = file($output_path);

    for ($i=0; $i < $n; $i++) {
      $this->assertEquals(rtrim($expected_lines[$i], ",\n"), rtrim($actual_lines[$i], ",\n"));
    }
    // WIP
  }
}
