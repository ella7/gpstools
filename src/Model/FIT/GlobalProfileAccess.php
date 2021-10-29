<?php

namespace App\Model\FIT;

use App\Model\FIT\GlobalProfile2 as GlobalProfile;

class GlobalProfileAccess
{

  public static function getFieldDefinition($message_global_number, $field_number)
  {
    return isset(GlobalProfile::MESSAGE_TYPES[$message_global_number]['fields'][$field_number])
      ? new FieldDefinition(GlobalProfile::MESSAGE_TYPES[$message_global_number]['fields'][$field_number])
      : null
    ;
  }

  public static function getFieldTypeValue($field_name, $key)
  {
    return isset(GlobalProfile::FIELD_TYPES[$field_name]['values'][$key])
      ? GlobalProfile::FIELD_TYPES[$field_name]['values'][$key]
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
}
