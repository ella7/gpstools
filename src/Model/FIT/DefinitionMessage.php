<?php

namespace App\Model\FIT;


class DefinitionMessage extends Message
{

  protected $architecture;

  public function __construct($properties)
  {
    parent::__construct($properties);
    $this->type = Message::MESSAGE_TYPE_DEFINITION;
  }

  // if the function is passed an array of arrays, create a new FieldDefinition
  public function setFields($a)
  {
    $this->fields = [];
    foreach($a as $field){
      if(is_array($field)){
        $field = new FieldDefinition($field);
      }
      if(!($field instanceof FieldDefinition)){
        throw new \Exception("Attempting to add a field to a DefinitionMessage that is not an instance of FieldDefinition");
      }
      $this->addField($field);
    }
  }

  public function addField(FieldDefinition $field)
  {
    $this->fields[] = $field;
  }


  /**
   * Returns the final FieldDefinition for a given index, name, and DataMessage
   *
   * FieldDefinitions may contain SubfieldDefinitions and/or ComponentDefinitions whose properties
   * should supersede those from the FieldDefinition. This method gets the acutal FieldDefinition
   * that should be used for a given field index and name.
   */
  public function getFinalFieldDefinition(int $field_index, string $field_name, DataMessage $message)
  {
    // if there's a FieldDefinition for the given index, ask it for its finalFieldDefinition
    if($this->isValidFieldIndex($field_index)){
      return $this->getFieldByIndex($field_index)->getFinalDefinition();
    }

    // JUST REALIZING - the fact that the index is INSIDE the range, doesn't mean that it's the
    // right index. A definition row might define 10 fields, but then a data row might only use
    // 8 of those fields plus 2 component fields. The data row fields would always look "valid"
    // but they wouldn't be. The bottom line is you can't go from data row to definition row -
    // at least not easily. Saving for tonight, but will require more thought tomorrow.

    // if the index is outside the range
  }

  /**
   * Get the units associated with the field $field_name
   *
   * @param  string $field_index  The index of the field in the fields array
   * @return mixed                The units stored for the given field
   */
  public function getFinalFieldUnits(string $field_index, DataMessage $message)
  {
    return $this->getFieldByIndex($field_index)->getUnits($message);
  }

}
