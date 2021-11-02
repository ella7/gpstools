<?php
namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\FITCSVParser;
use App\Model\FIT\DefinitionMessage;
use App\Model\FIT\FieldDefinition;

final class FITCSVParserTest extends KernelTestCase
{

  protected $parser;
  protected $project_dir;

  public function setUp() : void
  {
    self::bootKernel();
    $container      = static::getContainer();
    $this->parser   = $container->get(FITCSVParser::class);
    $this->project_dir = $container->get('kernel')->getProjectDir();
  }

  // TODO: This is a bad test and should be replaced or removed.
  public function testMessagesFromCSVFile()
  {
    $csv_path = $this->project_dir . '/tests/resources/Activity.csv';
    $messages = $this->parser->messagesFromCSVFile($csv_path);
    $this->assertEquals(9232, count($messages));
    $this->assertEquals('uint32', $messages[0]->getFields()[3]->getBaseTypeName());
  }

  /**
   * @dataProvider getMessageFromCSVLineProvider
   *
   * // TODO: This is a bad test and should be replaced or removed.
   */
  public function testGetMessageFromCSVLine($line, $expectedMessage): void
  {
    $this->assertEquals(
      $expectedMessage,
      $this->parser->getMessageFromCSVLine($line)
    );
  }

  public function getMessageFromCSVLineProvider()
  {
    return [
      [
        'line' => 'Definition,0,file_id,type,1,,manufacturer,1,,product,1,,time_created,1,,',
        'message' => new DefinitionMessage([
          'type'              => 'Definition',
          'local_number'      => '0',
          'name'              => 'file_id',
          'fields'            => self::getExpectedFields(),
          'num_empty_fields'  => 0
        ])
      ]
    ];
  }

  /**
   * @dataProvider getFieldsFromCSVArrayProvider
   */
  public function testGetFieldsFromCSVArray($csv_array, $expected_fields)
  {
    $fields = FITCSVParser::getFieldsFromCSVArray($csv_array);
    $this->assertEquals($expected_fields, $fields);
    //$this->assertTrue(true);
  }

  // TODO: This is a bad test and should be replaced or removed.
  public function getFieldsFromCSVArrayProvider()
  {
    return [
      [
        'csv_array' => [
          0 => "Definition",
          1 => "0",
          2 => "file_id",
          3 => "type",
          4 => "1",
          5 => "",
          6 => "manufacturer",
          7 => "1",
          8 => "",
          9 => "product",
          10 => "1",
          11 => "",
          12 => "time_created",
          13 => "1",
          14 => ""
        ],
        'expected_fields' => self::getExpectedFields()
      ]
    ];
  }

  public static function getExpectedFields()
  {
    return [
      FieldDefinition::initFromGlobalProfileByNames('file_id', ['name' => 'type',          'raw_value' => '1', 'units' => '']),
      FieldDefinition::initFromGlobalProfileByNames('file_id', ['name' => 'manufacturer',  'raw_value' => '1', 'units' => '']),
      FieldDefinition::initFromGlobalProfileByNames('file_id', ['name' => 'product',       'raw_value' => '1', 'units' => '']),
      FieldDefinition::initFromGlobalProfileByNames('file_id', ['name' => 'time_created',  'raw_value' => '1', 'units' => ''])
    ];
  }


}
