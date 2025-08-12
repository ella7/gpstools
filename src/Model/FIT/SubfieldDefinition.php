<?php

namespace App\Model\FIT;

class SubfieldDefinition extends FieldDefinition
{
  protected $ref_fields;

  /**
   * Check whether the given DataMessage satisfies any reference-field rule.
   *
   * Throws an exception if this SubfieldDefinition has no reference fields.
   * Iterates reference fields and returns true on the first match where the
   * message contains a field with the reference field's definition number
   * and its value equals the reference field's raw value; returns false if none match.
   *
   * @param DataMessage $message The data message to evaluate.
   * @return bool True if any reference-field rule matches the message, otherwise false.
   * @throws \Exception If no reference fields are defined on this SubfieldDefinition.
   */
  public function matchesMessage(DataMessage $message)
  {
    if(count($this->ref_fields) < 1 ){
      throw new \Exception('Subfields must contain at least one Reference Field');
    }
    foreach ($this->ref_fields as $ref_field) {
      if(
        $message->hasAFieldDefNum($ref_field->getDefNum()) &&
        $message->getFieldValueByDefNum($ref_field->getDefNum()) == $ref_field->getRawValue()
      ){
        return true;
      }
    }
    return false;
  }

  /**
   * Initialize and set the internal list of reference field definitions.
   *
   * Accepts an iterable of ReferenceFieldDefinition instances or associative arrays
   * describing a reference field. Any array entries will be converted to a
   * ReferenceFieldDefinition via its constructor. The resulting list replaces the
   * current $this->ref_fields value.
   *
   * @param iterable<ReferenceFieldDefinition|array> $a Iterable of reference fields or their array representations.
   */
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

  /**
   * Get the list of reference field definitions used by this subfield.
   *
   * @return ReferenceFieldDefinition[] Array of ReferenceFieldDefinition objects (may be empty).
   */
  public function getRefFields()
  {
    return $this->ref_fields;
  }
}
