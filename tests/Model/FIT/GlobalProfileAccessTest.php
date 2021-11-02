<?php

use PHPUnit\Framework\TestCase;
use App\Model\FIT\GlobalProfileAccess;
use App\Model\FIT\FieldDefinition;

final class GlobalProfileAccessTest extends TestCase
{

  /**
   * @dataProvider getFieldDefinitionProvider
   */
  public function testGetFieldDefinition($inputs, $expected_field_definition)
  {
    $this->assertEquals(
      $expected_field_definition,
      GlobalProfileAccess::getFieldDefinition($inputs['message_global_number'], $inputs['field_number'])
    );
  }

  /**
   * @dataProvider getFieldTypeValueProvider
   */
  public function testGetFieldTypeValue($field_type_name, $key, $expected_value)
  {
    $this->assertEquals($expected_value, GlobalProfileAccess::getFieldTypeValue($field_type_name, $key));
  }

  /**
   * @dataProvider getFieldDefinitionByNamesProvider
   */
  public function testGetFieldDefinitionByNames($message_type_name, $message_global_number, $field_name, $field_number)
  {
    $this->assertEquals(
      GlobalProfileAccess::getFieldDefinition($message_global_number, $field_number),
      GlobalProfileAccess::getFieldDefinitionByNames($message_type_name, $field_name)
    );
  }

  public function getFieldDefinitionProvider()
  {
    return [
      [
        'inputs' => [
          'message_global_number' => 5,
          'field_number'          => 3
        ],
        'expected_field_definition' => new FieldDefinition([
          'subfields' => [],
          'components' => [],
          'type' => [
            "name" => "uint32",
            "identifier" => 134,
            "size" => 4,
            "invalid_value" => 4294967295
          ],
          'def_num' => 3,
          'scale' => 100,
          'offset' => null,
          'name' => "odometer",
          'value' => null,
          'units' => "m"
        ])
      ]
    ];
  }

  public function getFieldTypeValueProvider()
  {
    return [
      ['field_type_name' => 'activity',          'key' => 1,     'expected_value' => 'auto_multi_sport'],
      ['field_type_name' => 'activity_subtype',  'key' => 14,    'expected_value' => 'indoor_rowing'],
      ['field_type_name' => 'file',              'key' => 20,    'expected_value' => 'activity_summary'],
      ['field_type_name' => 'garmin_product',    'key' => 2697,  'expected_value' => 'fenix5'],
      ['field_type_name' => 'sub_sport',         'key' => 11,    'expected_value' => 'cyclocross'],
    ];
  }

  public function getFieldDefinitionByNamesProvider()
  {
    return [
      ['message_type_name' => 'session',  'message_global_number' => 18, 'field_name' => 'sport',         'field_number' => 5],
      ['message_type_name' => 'lap',      'message_global_number' => 19, 'field_name' => 'total_cycles',  'field_number' => 10]
    ];
  }
}
