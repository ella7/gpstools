<?php

namespace App\Model\FIT;

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
      if($field->hasSubfields()){
        foreach ($field->getSubfields() as $subfield) {
          if($subfield->matchesMessage($this)){
            // set values in the field accordingly
            // TODO: This isn't done. Marking so I don't lose track
          }
        }
      }
    }
  }

}
