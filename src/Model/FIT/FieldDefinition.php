<?php

namespace App\Model\FIT;

use App\Model\FIT\GlobalProfileAccess;
use function Symfony\Component\String\u;

class FieldDefinition extends Field
{

  protected $subfields  = [];
  protected $components = [];

  /* *** Other properties that may be needed down the road *** */
  protected $type;        // Either a FILED_TYPE or BASE_TYPE for the field - exact usage is still unclear
  protected $def_num;     // the definition number for the field - ordinal index
  protected $size;        // Size (in bytes) of the field
  protected $scale;       // currently handled by the FitCSVTool, but might be good to know
  protected $offset;      // like scale, currently handled by the FitCSVTool

  public function getNumber()
  {
    return $this->def_num;
  }

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

  public function setSubfields($a)
  {
    $this->subfields = [];
    foreach($a as $subfield){
      if(is_array($subfield)){
        $this->subfields[] = new SubfieldDefinition($subfield);
      }
    }
  }

  public function setComponents($a)
  {
    $this->components = [];
    foreach($a as $component){
      if(is_array($component)){
        $this->components[] = new ComponentDefinition($component);
      }
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

  public function getBaseType()
  {
    if(array_key_exists('base_type', $this->type)){
      return $this->type['base_type'];
    }
    // if all three keys exist on the $type array, it is the base_type
    if(!array_diff_key(array_flip(['name', 'identifier', 'invalid_value']), $this->type)){
      return $this->type;
    }
    throw new \Exception("Couldn't find a base_type", 1);
  }

  public function getBaseTypeName()
  {
    $base_type = $this->getBaseType();
    return $base_type['name'];
  }

  public function getBaseTypeSize()
  {
    $base_type = $this->getBaseType();
    return $base_type['size'];
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

  public function getNumberOfValues()
  {
    if($this->raw_value) return $this->raw_value; // TODO: This is a hack - need to fix
    return (int)$this->size / $this->getBaseTypeSize();
  }
}
