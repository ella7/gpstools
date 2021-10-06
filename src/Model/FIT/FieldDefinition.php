<?php

namespace App\Model\FIT;

use function Symfony\Component\String\u;

class FieldDefinition
{

  protected $name;

  protected $units;
  protected $subfields  = [];
  protected $components = [];

  protected $value; // TODO: I don't know what value represents in the CSV file

  /* *** Other properties that may be needed down the road *** */
  protected $type;        // stores or references the Global MESSAGE_TYPE (the enum for the field)
  protected $def_num;     // the definition number for the field - ordinal index
  protected $scale;       // currently handled by the FitCSVTool, but might be good to know
  protected $offset;      // like scale, currently handled by the FitCSVTool

  public function __construct($properties)
  {
    $this->setPropertiesFromArray($properties);
    return $this;
  }

  public function getName()
  {
    return $this->name;
  }

  public function getValue()
  {
    return $this->value;
  }

  public function getUnits(DataMessage $message)
  {
    if($this->hasSubfields()){
      foreach ($this->subfields as $subfield) {
        if($subfield->matchesMessage($message)){
          return $subfield->units;
        }
      }
    }
    return $this->units;
  }

  public function hasSubfields()
  {
    return (count($this->subfields) > 0);
  }

  public function setSubfieldsFromGlobalProfile($message_name)
  {
    $this->subfields = [];
    if($subfields_array = GlobalProfile::getSubfields($message_name, $this->name)){
      foreach ($subfields_array as $subfield_array) {
        $this->subfields[] = new SubfieldDefinition($subfield_array);
      }
    }
  }

  public function setUnitsFromGlobalProfile(string $message_name)
  {
    $this->units = GlobalProfile::getUnitsForMessageAndFieldType($message_name, $this->name);
  }

  /**
   * Attempt to set object properties from an associative array
   *
   * @param array $a  An associatve array with keys matching the object vars for `$this`
   */
  public function setPropertiesFromArray($a)
  {
    $settable_properties = $this->getAutoSettableProperties();

    foreach ($settable_properties as $property_name) {
      if(array_key_exists($property_name, $a)){
        // First look for $this->setProperty(), and then use "$this->property = ... "
        $property_setter = 'set' . u($property_name)->camel()->title();
        if(method_exists($this, $property_setter)){
          $this->{$property_setter}($a[$property_name]);
        } else {
          $this->{$property_name} = $a[$property_name];
        }
      }
    }
  }

  /**
   * Returns the list of properties that can be set by setPropertiesFromArray
   *
   * Can be overwritten by children in order to provide more specific control
   */
  protected function getAutoSettableProperties()
  {
    return array_keys(get_object_vars($this));
  }
}
