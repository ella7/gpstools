<?php

namespace App\Model\FIT;


class DefinitionMessage extends Message
{

  public function __construct()
  {
    $this->type = Message::MESSAGE_TYPE_DEFINITION;
  }

  /**
   * Get the value associated with the field $field_name
   *
   * @param  string $field_name   Name of the feild
   * @return mixed                The value stored for the given field
   */
  public function getFieldValue(string $field_name)
  {
    return $this->fields[$field_name]->value;
  }

  /**
   * Get the units associated with the field $field_name
   *
   * @param  string $field_index  The index of the field in the fields array
   * @return mixed                The units stored for the given field
   */
  public function getFieldUnits(string $field_index, DataMessage $message)
  {
    return $this->getFieldByIndex($field_index)->getUnits($message);
  }

  public function setUnitsForAllFieldDefinitionsFromGlobalProfile()
  {
    foreach ($this->fields as $field_definition) {
      $field_definition->setUnitsFromGlobalProfile($this->message);
    }
  }

}
