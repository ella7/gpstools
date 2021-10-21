<?php
namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\FITCSVParser;
use App\Model\FIT\DefinitionMessage;
use App\Model\FIT\FieldDefinition;

final class FITCSVParserTest extends KernelTestCase
{

  protected $parser;

  public function setUp() : void
  {
    self::bootKernel();
    $container      = static::getContainer();
    $this->parser   = $container->get(FITCSVParser::class);
  }

  /**
   * @dataProvider getMessageFromCSVLineProvider
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
          'local_number'      => 0,
          'name'              => 'file_id',
          'fields'            => [
            'type'         => new FieldDefinition(['name' => 'type',          'value' => '1', 'units' => '']),
            'manufacturer' => new FieldDefinition(['name' => 'manufacturer',  'value' => '1', 'units' => '']),
            'product'      => new FieldDefinition(['name' => 'product',       'value' => '1', 'units' => '']),
            'time_created' => new FieldDefinition(['name' => 'time_created',  'value' => '1', 'units' => '']),
          ],
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
        'expected_fields' => [
          'type'         => new FieldDefinition(['name' => 'type',          'value' => '1', 'units' => '']),
          'manufacturer' => new FieldDefinition(['name' => 'manufacturer',  'value' => '1', 'units' => '']),
          'product'      => new FieldDefinition(['name' => 'product',       'value' => '1', 'units' => '']),
          'time_created' => new FieldDefinition(['name' => 'time_created',  'value' => '1', 'units' => '']),
        ]
      ]
    ];
  }


}
