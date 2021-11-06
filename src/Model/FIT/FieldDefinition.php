<?php

namespace App\Model\FIT;

use App\Model\FIT\Field;
use App\Model\FIT\BaseType;
use App\Model\FIT\FieldType;
use App\Model\FIT\SubfieldDefinition;
use App\Model\FIT\GlobalProfileAccess;
use App\Model\FIT\ComponentDefinition;
use function Symfony\Component\String\u;

class FieldDefinition extends Field
{

  protected $subfields  = [];
  protected $components = [];

  /* *** Other properties that may be needed down the road *** */
  protected $type;        // Either a FieldType or BaseType for the field - will create an abstract class or interface for both to use
  protected $size;        // Size (in bytes) of the field
  protected $scale;       // A scale factor to be applied to the raw value found in the fit file
  protected $offset;      // An offset to be added to the raw value in the fit file

  /**
   * TODO:  getFinalFieldDefinition in other classes could return a Field, Subfield or Comoponent.
   *        Here we have the option to pass a message_name as well, and if it doesn't match what
   *        we would otherwise return, we could check the Components.
   */
  public function getFinalDefinition(DataMessage $message)
  {
    if($this->hasSubfields()){
      foreach ($this->subfields as $subfield) {
        if($subfield->matchesMessage($message)){
          return $subfield;
        }
      }
    }
    return $this;
  }

  public function getFinalUnits(DataMessage $message)
  {
    return $this->getFinalDefinition($message)->units;
  }

  public function hasSubfields()
  {
    return (count($this->subfields) > 0);
  }

  public function getSubfields()
  {
    return $this->subfields;
  }

  public function setSubfields($a)
  {
    $this->subfields = [];
    foreach($a as $subfield){
      if(is_array($subfield)){
        $subfield = new SubfieldDefinition($subfield);
      }
      if(!($subfield instanceof SubfieldDefinition)){
        throw new \Exception("Attempting to add a subfield to a FieldDefinition that is not an instance of SubfieldDefinition");
      }
      $this->subfields[] = $subfield;
    }
  }

  public function setComponents($a)
  {
    $this->components = [];
    foreach($a as $component){
      if(is_array($component)){
        $component = new ComponentDefinition($component);
      }
      if(!($component instanceof ComponentDefinition)){
        throw new \Exception("Attempting to add a component to a FieldDefinition that is not an instance of ComponentDefinition");
      }
      $this->components[] = $component;
    }
  }

  public function hasComponents()
  {
    return (count($this->components) > 0);
  }

  public static function initFromGlobalProfile($message_global_number, $properties)
  {
    $field_definition = GlobalProfileAccess::getFieldDefinition(
      $message_global_number, $properties['def_num']
    );
    return $field_definition->setPropertiesFromArray($properties);
  }

  public static function initFromGlobalProfileByNames($message_type_name, $properties) : FieldDefinition
  {
    $field_definition = GlobalProfileAccess::getFieldDefinitionByNames(
      $message_type_name, $properties['name']
    );
    return $field_definition->setPropertiesFromArray($properties);
  }

  public function setType($type)
  {
    // if the array contains the base_type properties
    if(BaseType::looksLikeValidPropertiesArray($type)){
      $type = new BaseType($type);
    }
    // if the array contains the field_type properties
    if(FieldType::looksLikeValidPropertiesArray($type)){
      $type = new FieldType($type);
    }
    if(!($type instanceof FieldType || $type instanceof BaseType)){
      throw new \Exception("Attempting to set the FieldDefinition type to a value that is not an instance of FieldType");
    }
    $this->type = $type;
  }

  public function getBaseType()
  {
    if($this->type instanceof BaseType){
      return $this->type;
    }

    if($this->type instanceof FieldType){
      return $this->type->getBaseType();
    }

    throw new \Exception("Couldn't find a base_type", 1);
  }

  public function getBaseTypeName()
  {
    return $this->getBaseType()->getName();
  }

  public function getBaseTypeSize()
  {
    return $this->getBaseType()->getSize();
  }

  public function getInvalidValue()
  {
    return $this->getBaseType()->getInvalidValue();
  }

  public function getSize()
  {
    return $this->size;
  }

  // TODO: Test to make sure an error is thrown for invalid values
  public function setSize($size)
  {
    $base_type_size = $this->getBaseTypeSize();
    if($size % $base_type_size !== 0){
      throw new \Exception("Attempting to set field size to $size. Must be a multiple of the base type size: $base_type_size", 1);
    }
    $this->size = $size;
  }

  public function setSizeFromNumberOfValues($n)
  {
    $this->setSize($n * $this->getBaseTypeSize());
  }

  public function getNumberOfValues()
  {
    return (int)$this->size / $this->getBaseTypeSize();
  }

  public function getScale()
  {
    return $this->scale;
  }

  public function getOffset()
  {
    return $this->offset;
  }
}
