<?php

namespace App\Model\FIT;

use App\Model\FIT\GlobalProfile;
use App\Model\FIT\FieldDefinition;
use App\Model\FIT\BaseType;

class GlobalProfileAccess
{

  protected static $field_lookup_map;

  /**
   * Retrieve a FieldDefinition by message global number and field number.
   *
   * Looks up the field definition in GlobalProfile::MESSAGE_TYPES and, if present,
   * returns a new FieldDefinition wrapping that definition; otherwise returns null.
   *
   * @param int $message_global_number Index of the message type in GlobalProfile::MESSAGE_TYPES.
   * @param int $field_number Index of the field inside the message type's 'fields' array.
   * @return FieldDefinition|null The FieldDefinition for the specified message/field, or null if not found.
   */
  public static function getFieldDefinition($message_global_number, $field_number)
  {
    return isset(GlobalProfile::MESSAGE_TYPES[$message_global_number]['fields'][$field_number])
      ? new FieldDefinition(GlobalProfile::MESSAGE_TYPES[$message_global_number]['fields'][$field_number])
      : null
    ;
  }

  /**
   * Retrieve a FieldDefinition by global message type name and field name.
   *
   * Uses the cached GlobalProfile lookup map to find the field definition for the
   * given message type name and field name and returns a new FieldDefinition.
   *
   * @param string $message_type_name Message type name as defined in GlobalProfile::MESSAGE_TYPES.
   * @param string $field_name Field name as defined for that message type in GlobalProfile.
   * @return FieldDefinition The corresponding FieldDefinition.
   * @throws \Exception If no field definition exists for the given message type and field name.
   */
  public static function getFieldDefinitionByNames($message_type_name, $field_name) : FieldDefinition
  {
    $map  = self::getFieldLookupMap();

    if(!isset($map[$message_type_name][$field_name])){
      throw new \Exception("No Field defined for message type: $message_type_name and field: $field_name", 1);
    }
    return new FieldDefinition($map[$message_type_name][$field_name]);
  }

  /**
   * Return the cached map of message type name -> field name -> field definition.
   *
   * Builds the map on first access (lazy initialization) by scanning GlobalProfile::MESSAGE_TYPES.
   *
   * @return array<string, array<string, array>> Map keyed by message type name, then field name, with each value being the raw field definition array from GlobalProfile.
   */
  public static function getFieldLookupMap()
  {
    if(self::$field_lookup_map === null){
      self::buildFieldLookupMap();
    }
    return self::$field_lookup_map;
  }

  /**
   * Build and cache a lookup map of field definitions keyed by message type name and field name.
   *
   * Iterates GlobalProfile::MESSAGE_TYPES and stores each field under
   * self::$field_lookup_map[<message_type_name>][<field_name>] = <field_definition_array>.
   * Ensures duplicate (message type name, field name) entries are not allowed; on a duplicate it
   * dumps the offending message type and throws an Exception with code 1.
   *
   * Side effects:
   * - Populates the protected static property self::$field_lookup_map.
   *
   * @throws \Exception If a duplicate field entry is encountered while building the map.
   */
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
   * Return the string value for a given field type's enumerated key.
   *
   * Looks up GlobalProfile::FIELD_TYPES[$field_type_name]['values'][$key] and returns it,
   * or the literal string `'unknown'` when no entry exists for that key.
   *
   * @param string $field_type_name Name of the FieldType (FieldType.name)
   * @param int    $key             Identifier for the enumerated value
   * @return string                 The enumerated string value, or `'unknown'` if not found
   */
  public static function getFieldTypeValue(string $field_type_name, int $key)
  {
    return isset(GlobalProfile::FIELD_TYPES[$field_type_name]['values'][$key])
      ? GlobalProfile::FIELD_TYPES[$field_type_name]['values'][$key]
      : 'unknown'
    ;
  }

  /**
   * Retrieve FieldDefinition objects for every field in the GlobalProfile.
   *
   * Iterates all message types in GlobalProfile::MESSAGE_TYPES and collects the
   * FieldDefinition produced by getFieldDefinition for each field.
   *
   * @return \App\Model\FIT\FieldDefinition[] Array of FieldDefinition objects (empty if no fields).
   */
  public static function getAllFieldDefinitions()
  {
    foreach (GlobalProfile::MESSAGE_TYPES as $message_global_number => $message_definition) {
      foreach($message_definition['fields'] as $field_number => $field){
        $all_field_definitions[] = self::getFieldDefinition($message_global_number, $field_number);
      }
    }
    return $all_field_definitions;
  }

  /**
   * Return the raw base type definitions from the FIT GlobalProfile.
   *
   * The returned value is the contents of GlobalProfile::BASE_TYPES and represents
   * the base type metadata used to construct BaseType instances.
   *
   * @return array<string,mixed> Array of base type definitions keyed by identifier.
   */
  public static function getBaseTypesArray()
  {
    return GlobalProfile::BASE_TYPES;
  }

  /**
   * Create a BaseType wrapper for the base type identified by the given identifier.
   *
   * @param string|int $identifier Key of the base type in GlobalProfile::BASE_TYPES.
   * @return BaseType The BaseType instance for the specified identifier.
   */
  public function getBaseType($identifier)
  {
    return new BaseType(GlobalProfile::BASE_TYPES[$identifier]);
  }
}
