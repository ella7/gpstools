<?php

namespace App\Model\FIT;

use App\Model\FIT\GlobalProfile2 as GlobalProfile;

class GlobalProfileAccess
{
  
  public static function getFieldDefinition($message_num, $field_num)
  {
    return isset(GlobalProfile::MESSAGE_TYPES[$message_num]['fields'][$field_num])
      ? new FieldDefinition(GlobalProfile::MESSAGE_TYPES[$message_num]['fields'][$field_num])
      : null
    ;
  }
}
