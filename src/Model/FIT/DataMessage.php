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

  public function getValidFields()
  {
    // if the field has a def_num, check for a valid value
    // if the field doesn't have a def_num, just check for any value
    $valid_fields = [];
    foreach ($this->getFields() as $field) {
      if($field->getNumber() !== null){
        $def_field = $this->getDefinitionFieldForField($field);

        // TODO: Need more careful handling of multi-value fields
        $value = $field->getValue();
        if(is_array($value)) $value = $value[0];

        if($value !== $def_field->getInvalidValue()){
          $valid_fields[] = $field;
        }
      } else {
        if($field->getValue()){
          $valid_fields[] = $field;
        }
      }
    }
    return $valid_fields;
  }

  /**
   * Look at each field to see if a subfield exists - then look at each subfield to see if the
   * criteria defined in a reference fields is met. If so, replace the field accordingly.
   */
  public function evaluateSubfields(): void
  {
    foreach ($this->getFields() as $field) {
      $def_field = $this->getDefinitionFieldForField($field);
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

  public function getDefinitionFieldForField($field)
  {
    if($field instanceof ComponentDefinition) return $field;          // HACK: is this a hack?
    if($field->getNumber() === null){
      return GlobalProfileAccess::getFieldDefinitionByNames(
        $this->getName(),
        $field->getName()
      );
    }
    return $this->getDefinitionFieldByDefNum($field->getNumber());
  }

  public function addComponents($components)
  {
    foreach ($components as $component) {
      $compenet_base_type = GlobalProfileAccess::getFieldDefinition(
        $this->getGlobalNumber(),
        $component->getNumber()
      )->getBaseType();
      $component->setType($compenet_base_type);                       // HACK: More hack to be fixed
      $this->addField($component);
    }
  }

}
