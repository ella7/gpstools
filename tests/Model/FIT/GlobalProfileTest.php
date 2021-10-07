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
   * The functionality of FIT\Message::getFinalFieldDefinition depends on component names being
   * unique per message type. This test examines each message type to see if any component names
   * are repeated.
   *
   * This test can be simplified significantly once the GlobalProfile::MESSAGE_TYPES structure
   * contains FIT\FieldDefintions so that methods of that class can be used rather than manually
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
   * contains FIT\FieldDefintions so that methods of that class can be used rather than manually
   * traversing the structure.
   */
  public function testMessageTypesDoNotContainNestedSubfields(): void
  {
    foreach(GlobalProfile::MESSAGE_TYPES as $message_type){
      foreach($message_type['fields'] as $field){

        if(array_key_exists('subfields', $field)){
          foreach($field['subfields'] as $subfield){
            $this->assertArrayNotHasKey('subfield', $subfield);
          } // end foreach subfield
        } // end if(field has subfields)
      } // end foreach $field
    } // end foreach $message_type
  } // end function testMessageTypesDoNotContainNestedSubfields()
}
