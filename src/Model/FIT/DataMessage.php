<?php

namespace App\Model\FIT;

class DataMessage extends Message
{

  protected $definition;

  public function __construct(DefinitionMessage $definition)
  {
    $this->type = Message::MESSAGE_TYPE_DATA;
    $this->definition = $definition;
  }

  /**
   * Get the value associated with the field $field_name
   *
   * @param  string $field_name   Name of the feild
   * @return mixed                The value stored for the given field
   */
  public function getFieldValue(string $field_name)
  {
    return $this->fields[$field_name];
  }

  /**
   * Get the units associated with the field $field_name
   *
   * @param  int    $field_index   The index of the field in the fields array
   * @return mixed                 The units stored for the given field
   */
  public function getFieldUnits(int $field_index)
  {
    return $this->definition->getFieldUnits($field_index, $this);
  }

  /**
   * Returns the final FieldDefinition for a given index, name, and this DataMessage
   *
   * FieldDefinitions may contain SubfieldDefinitions and/or ComponentDefinitions whose properties
   * should supersede those from the FieldDefinition. This method gets the acutal FieldDefintion
   * that should be used for a given field index and name.
   */
  public function getFinalFieldDefinition(int $field_index, string $field_name)
  {
    return $this->definition->getFinalFieldDefinition($field_index, $field_name, $this);
  }

}
