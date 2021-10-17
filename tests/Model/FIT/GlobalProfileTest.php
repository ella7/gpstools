<?php

use PHPUnit\Framework\TestCase;
use App\Model\FIT\GlobalProfile;

final class GlobalProfileTest extends TestCase
{
  public function testGetFieldDefinition(): void
  {
    $definition = GlobalProfile::getFieldDefinition('event', 'data');
    $this->assertIsObject($definition);
    $this->assertEquals('data', $definition->getName());
  }

  /**
   * @dataProvider baseTypesProvider
   */
  public function testGetBaseType($base_type_key, $expected_base_type): void
  {
    $this->assertEquals($expected_base_type, GlobalProfile::getBaseType($base_type_key));
  }

  public function baseTypesProvider()
  {
    return [
      [0x00, ['name' => 'enum',    'identifier' => 0x00, 'invalid_value' => 0xFF]],
      [0x01, ['name' => 'sint8',   'identifier' => 0x01, 'invalid_value' => 0x7F]],
      [0x02, ['name' => 'uint8',   'identifier' => 0x02, 'invalid_value' => 0xFF]],
      [0x83, ['name' => 'sint16',  'identifier' => 0x83, 'invalid_value' => 0x7FFF]],
      [0x84, ['name' => 'uint16',  'identifier' => 0x84, 'invalid_value' => 0xFFFF]],
      [0x85, ['name' => 'sint32',  'identifier' => 0x85, 'invalid_value' => 0x7FFFFFFF]],
      [0x86, ['name' => 'uint32',  'identifier' => 0x86, 'invalid_value' => 0xFFFFFFFF]],
      [0x07, ['name' => 'string',  'identifier' => 0x07, 'invalid_value' => null]],
      [0x88, ['name' => 'float32', 'identifier' => 0x88, 'invalid_value' => null]],
      [0x89, ['name' => 'float64', 'identifier' => 0x89, 'invalid_value' => null]],
      [0x0A, ['name' => 'uint8z',  'identifier' => 0x0A, 'invalid_value' => 0x0]],
      [0x8B, ['name' => 'uint16z', 'identifier' => 0x8B, 'invalid_value' => 0x0]],
      [0x8C, ['name' => 'uint32z', 'identifier' => 0x8C, 'invalid_value' => 0x0]],
      [0x0D, ['name' => 'byte',    'identifier' => 0x0D, 'invalid_value' => null]],
      [0x8E, ['name' => 'sint64',  'identifier' => 0x8E, 'invalid_value' => 0x7FFFFFFFFFFFFFFF]],
      [0x8F, ['name' => 'uint64',  'identifier' => 0x8F, 'invalid_value' => 0xFFFFFFFFFFFFFFFF]],
      [0x90, ['name' => 'uint64z', 'identifier' => 0x90, 'invalid_value' => 0]],
    ];
  }

  /**
   * The functionality of FIT\Message::getFinalFieldDefinition depends on component names being
   * unique per message type. This test examines each message type to see if any component names
   * are repeated.
   *
   * This test can be simplified significantly once the GlobalProfile::MESSAGE_TYPES structure
   * contains FIT\FieldDefinitions so that methods of that class can be used rather than manually
   * traversing the structure.
   */
  public function testComponentNamesAreUniquePerMessageType(): void
  {
    foreach(GlobalProfile::MESSAGE_TYPES as $message_type){

      $component_names  = [];

      foreach($message_type['fields'] as $field){

        if(array_key_exists('components', $field)){
          foreach($field['components'] as $component){
            $message = "Checking message type: "
              . $message_type['name']
              . " | field: " . $field['name']
              . " | component " . $component['name']
            ;
            $this->assertNotContains($component['name'], $component_names, $message);
            $component_names[] = $component['name'];
          }
        }

        if(array_key_exists('subfields', $field)){
          foreach($field['subfields'] as $subfield){
            if(array_key_exists('components', $subfield)){
              foreach($subfield['components'] as $component){
                $message = "Checking message type: "
                  . $message_type['name']
                  . " | field: " . $field['name']
                  . " | subfield: " . $subfield['name']
                  . " | component " . $component['name']
                ;
                $this->assertNotContains($component['name'], $component_names, $message);
                $component_names[] = $component['name'];
              } // end foreach component
            } // end if(subfield has components)
          } // end foreach subfield
        } // end if(field has subfields)
      } // end foreach $field
    } // end foreach $message_type
  } // end function testComponentNamesAreUniquePerMessageType()


  /**
   * The App assumes no nested subfields. This test examines each message type to see if any
   * of the fields contain more than one layer of subfields.
   *
   * This test can be simplified significantly once the GlobalProfile::MESSAGE_TYPES structure
   * contains FIT\FieldDefinitions so that methods of that class can be used rather than manually
   * traversing the structure.
   */
  public function testMessageTypesDoNotContainNestedSubfields(): void
  {
    foreach(GlobalProfile::MESSAGE_TYPES as $message_type){
      foreach($message_type['fields'] as $field){

        if(array_key_exists('subfields', $field)){
          foreach($field['subfields'] as $subfield){
            $this->assertArrayNotHasKey('subfields', $subfield);
          } // end foreach subfield
        } // end if(field has subfields)
      } // end foreach $field
    } // end foreach $message_type
  } // end function testMessageTypesDoNotContainNestedSubfields()
}
