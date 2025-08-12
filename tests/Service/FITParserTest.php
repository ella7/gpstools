<?php
namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\FITParser;
use Psr\Log\LoggerInterface;
use function Symfony\Component\String\u;


final class FITParserTest extends KernelTestCase
{
  protected $project_dir;
  protected $parser;
  protected $logger;

  public function setUp() : void
  {
    self::bootKernel();
    $container = static::getContainer();
    $this->logger = $container->get(LoggerInterface::class);
    $this->project_dir = $container->get('kernel')->getProjectDir();
    // TODO: create parser here - setLogger and deal with setting the path later - requires
    // change to FITParser constructor
  }

  public function testParseFile()
  {
    $fit_path = $this->project_dir . '/tests/resources/Activity-HDR-DEFN-DATA.fit';
    $parser = new FITParser($fit_path);
    $parser->setLogger($this->logger);
    $parser->parseFile();
    $this->assertTrue(true);
  }

  public function testReadFileHeader()
  {
    $fit_path = $this->project_dir . '/tests/resources/Activity-HDR.fit';
    $parser = new FITParser($fit_path);
    $parser->setLogger($this->logger);
    $header = $parser->readFileHeader();

    $expected_header = [
      "header_size" => 14,
      "protocol_version" => 16,
      "profile_version" => 2135,
      "data_size" => 314666,
      "data_type" => ".FIT",
      "crc" => 61737
    ];


    $this->assertEquals($expected_header, $header);
  }

  /**
   * @dataProvider readRecordProvider
   */
  public function testReadRecord($fit_path, $expected_record_properties)
  {
    $parser = new FITParser($this->project_dir . $fit_path);
    $parser->setLogger($this->logger);
    $record = $parser->readRecord();

    foreach ($expected_record_properties as $key => $expected_value) {
      $property_getter = 'get' . u($key)->camel()->title();
      if(method_exists($record, $property_getter)){
        $value = $record->{$property_getter}();
      }
      $this->assertEquals($expected_value, $value);
    }
  }

  public function readRecordProvider()
  {
    return [
      [
        'fit_path' => '/tests/resources/Activity-DEFN.fit',
        'expected_record_properties' => [
          'type'          => 'Definition',
          'local_number'  => 0,
          'global_number' => 0,
          'name'          => 'file_id',
        ]
      ]
    ];
  }
}
