<?php

namespace App\Model\FIT;
use App\Model\FIT\Field;

class DataMessage extends Message
{

  protected $definition;

  public function __construct($properties)
  {
    parent::__construct($properties);
    $this->type = Message::MESSAGE_TYPE_DATA;
  }

  public function setDefinition(DefinitionMessage $definition)
  {
      $this->definition = $definition;
  }

  public function getDefinition()
  {
    return $this->definition;
  }

  /**
   * Look at each field to see if a subfield exists - then look at each subfield to see if the
   * criteria defined in a reference fields is met. If so, replace the field accordingly.
   */
  public function evaluateSubfields(): void
  {
    foreach ($this->getFields() as $field) {
      $def_field = $this->getDefinitionFieldByDefNum($field->getNumber());
      if($def_field->hasSubfields()){
        foreach ($def_field->getSubfields() as $subfield) {
          if($subfield->matchesMessage($this)){
            $field->setName($subfield->getName());
            $field->setUnits($subfield->getUnits());
            // TODO: This is incomplete. We don't currently handle a situation where the base type
            // changes. The spec allows the subfield to have a different base_type as long as it
            // is equal to or smaller than the main field's base_type. It's unclear how this is
            // supposed to work. 
          }
        }
      }
    }
  }

  /**
   * Returns the field from the definition message with a def_num equal to the passed value
   *
   * @param  int   $def_num
   * @return Field
   */
  public function getDefinitionFieldByDefNum(int $def_num): Field
  {
    return $this->definition->getFieldByDefNum($def_num);
  }

}
