<?php

namespace App\Model\FIT;
use App\Model\FIT\Field;

class DataMessage extends Message
{

  protected $definition;

  /**
   * Create a DataMessage.
   *
   * Initializes the message using the given properties and sets the message type to MESSAGE_TYPE_DATA.
   *
   * @param mixed $properties Initial properties used to populate the message (typically an associative array or object)
   */
  public function __construct($properties)
  {
    parent::__construct($properties);
    $this->type = Message::MESSAGE_TYPE_DATA;
  }

  /**
   * Set the DefinitionMessage used to resolve field metadata for this DataMessage.
   *
   * @param DefinitionMessage $definition Definition containing the field definitions referenced by this message.
   */
  public function setDefinition(DefinitionMessage $definition)
  {
      $this->definition = $definition;
  }

  /**
   * Get the DefinitionMessage associated with this DataMessage.
   *
   * Returns the DefinitionMessage used to resolve field metadata (definition fields,
   * invalid values, subfields, component base types, etc.), or null if none has been set.
   *
   * @return \App\Model\FIT\DefinitionMessage|null
   */
  public function getDefinition()
  {
    return $this->definition;
  }

  /**
   * Return the subset of this message's fields that contain a valid value.
   *
   * For fields with a definition number (def_num) this checks the field's value
   * against the corresponding definition field's `invalid` value; if they differ
   * the field is considered valid. For fields without a def_num the field is
   * considered valid when it has any truthy value. If a field's value is an
   * array the first element is used for the validity check.
   *
   * @return Field[] Array of Field objects that are considered valid for this message.
   */
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
   * Evaluate and apply matching subfields to this message's fields.
   *
   * For each field in the message, finds its field definition and checks any defined
   * subfields. If a subfield's criteria match this message, the field's name and units
   * are updated to the subfield's name and units.
   *
   * Side effects:
   * - Mutates Field objects returned by getFields() by calling setName() and setUnits().
   *
   * Notes:
   * - Does not currently handle cases where a matching subfield changes the base type;
   *   the spec permits subfields with a different (equal-or-smaller) base type, but that
   *   case is not implemented here.
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
   * Retrieve the field definition from the associated DefinitionMessage by definition number.
   *
   * @param int $def_num The definition field number (def_num) to look up.
   * @return Field The Field definition matching the given def_num.
   */
  public function getDefinitionFieldByDefNum(int $def_num): Field
  {
    return $this->definition->getFieldByDefNum($def_num);
  }

  /**
   * Resolve and return the definition Field that corresponds to a given field or component.
   *
   * If the provided value is already a ComponentDefinition, it is returned unchanged.
   * If the field has no definition number (def_num), the definition is looked up
   * by message name and field name via GlobalProfileAccess. Otherwise the definition
   * is retrieved from this message's DefinitionMessage by def_num.
   *
   * @param mixed $field Field-like object (typically a Field or ComponentDefinition) whose definition to resolve.
   * @return Field|null The matching definition Field, or null if none is found.
   */
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

  /**
   * Merge component fields into this message, setting their base types and adding their values.
   *
   * For each component in $components this method:
   * - sets the component's type to the base type from the global field definition for this message's global number,
   * - if this message already contains a field with the same def_num, appends the component's value to that field,
   * - otherwise adds the component as a new field on the message.
   *
   * @param iterable $components Iterable of component field objects. Each component must provide getNumber(), getValue(), and setType($type).
   * @return void
   */
  public function addComponents($components)
  {
    foreach ($components as $component) {
      $component->setType(
        GlobalProfileAccess::getFieldDefinition($this->getGlobalNumber(),$component->getNumber())
        ->getBaseType()
      );
      // if the message already has a field with the same def_num, add the component value to the existing field
      // it may make sense to move this functionality into addField()
      if($this->hasAFieldDefNum($component->getNumber())){
        $this->getFieldByDefNum($component->getNumber())->addValue($component->getValue());
      } else {
        $this->addField($component);
      }
    }
  }

}
