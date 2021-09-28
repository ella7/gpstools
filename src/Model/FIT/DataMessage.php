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
   * @param  string $field_name   Name of the feild
   * @return mixed                The units stored for the given field
   */
  public function getFieldUnits(string $field_name)
  {
    dump($this->definition);
    return $this->definition->getFieldUnits($field_name);
  }

}
