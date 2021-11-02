<?php

namespace App\Model\FIT;

use App\Model\FIT\GlobalProfile2 as GlobalProfile;
use App\Model\FIT\FieldDefinition;
use App\Model\FIT\BaseType;

class GlobalProfileAccess
{

  protected static $field_lookup_map;

  public static function getFieldDefinition($message_global_number, $field_number)
  {
    return isset(GlobalProfile::MESSAGE_TYPES[$message_global_number]['fields'][$field_number])
      ? new FieldDefinition(GlobalProfile::MESSAGE_TYPES[$message_global_number]['fields'][$field_number])
      : null
    ;
  }

  /**
   * Returns a FieldDefinition from the GlobalProfile based on the message type name, and field name
   *
   * @param  string          $message_type_name
   * @param  string          $field_name
   * @return FieldDefinition
   */
  public static function getFieldDefinitionByNames($message_type_name, $field_name) : FieldDefinition
  {
    $map  = self::getFieldLookupMap();

    if(!isset($map[$message_type_name][$field_name])){
      throw new \Exception("No Field defined for message type: $message_type_name and field: $field_name", 1);
    }
    return new FieldDefinition($map[$message_type_name][$field_name]);
  }

  public static function getFieldLookupMap()
  {
    if(self::$field_lookup_map === null){
      self::buildFieldLookupMap();
    }
    return self::$field_lookup_map;
  }

  protected static function buildFieldLookupMap()
  {
    $map = [];
    foreach (GlobalProfile::MESSAGE_TYPES as $message_type) {
      foreach($message_type['fields'] as $field){
        if(isset($map[$message_type['name']][$field['name']])){
          dump($message_type);
          throw new \Exception("We've already got one", 1);
        }
        $map[$message_type['name']][$field['name']] = $field;
      }
    }
    self::$field_lookup_map = $map;
  }

  /**
   * Returns the string associated with the provided identifier: $key, for a given field type.
   *
   * One of the primary puproses of field types is to serve as a lookup for enumerated values (the
   * other is to store the base type for the given field type). getFieldTypeValue provides access
   * to those enumerated values.
   *
   * @param   string  $field_type_name  Name of the FieldType - FieldType.name
   * @param   int     $key              The identifier for the enumerated value (dec or hex)
   *
   * @return  string                    The enumerated value
   */
  public static function getFieldTypeValue(string $field_type_name, int $key)
  {
    return isset(GlobalProfile::FIELD_TYPES[$field_type_name]['values'][$key])
      ? GlobalProfile::FIELD_TYPES[$field_type_name]['values'][$key]
      : 'unknown'
    ;
  }

  public static function getAllFieldDefinitions()
  {
    foreach (GlobalProfile::MESSAGE_TYPES as $message_global_number => $message_definition) {
      foreach($message_definition['fields'] as $field_number => $field){
        $all_field_definitions[] = self::getFieldDefinition($message_global_number, $field_number);
      }
    }
    return $all_field_definitions;
  }

  public static function getBaseTypesArray()
  {
    return GlobalProfile::BASE_TYPES;
  }

  public function getBaseType($identifier)
  {
    return new BaseType(GlobalProfile::BASE_TYPES[$identifier]);
  }
}
