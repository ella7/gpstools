<?php

namespace App\Model\FIT;

class SubfieldDefinition extends FieldDefinition
{
  protected $ref_fields;

  /**
   * Evaluates the rules contained in ref_fields against the passed messages
   *
   * @param DataMessage $message    The data message against which the rules will be evaluated
   * @return bool                   Returns true as soon as a rule is met, otherwise returns false
   */
  public function matchesMessage(DataMessage $message)
  {
    if(count($this->ref_fields) < 1 ){
      throw new \Exception('Subfields must contain at least one Reference Field');
    }
    foreach ($this->ref_fields as $ref_field) {
      if($message->getFieldValue($ref_field->getName()) == $ref_field->getValue()){
        return true;
      }
    }
    return false;
  }

  public function setRefFields($a)
  {
    $ref_fields = [];
    foreach($a as $ref_field){
      if(is_array($ref_field)){
        $ref_field = new ReferenceFieldDefinition($ref_field);
      }
      $ref_fields[] = $ref_field;
    }
    $this->ref_fields = $ref_fields;
  }
}
