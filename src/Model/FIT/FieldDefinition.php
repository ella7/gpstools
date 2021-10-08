<?php

namespace App\Model\FIT;

use function Symfony\Component\String\u;

class FieldDefinition extends Field
{

  protected $subfields  = [];
  protected $components = [];

  /* *** Other properties that may be needed down the road *** */
  protected $type;        // stores or references the Global MESSAGE_TYPE (the enum for the field)
  protected $def_num;     // the definition number for the field - ordinal index
  protected $scale;       // currently handled by the FitCSVTool, but might be good to know
  protected $offset;      // like scale, currently handled by the FitCSVTool


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

  public function setSubfieldsFromGlobalProfile($message_name)
  {
    $this->subfields = [];
    if($subfields_array = GlobalProfile::getSubfields($message_name, $this->name)){
      foreach ($subfields_array as $subfield_array) {
        $this->subfields[] = new SubfieldDefinition($subfield_array);
      }
    }
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

  public function setUnitsFromGlobalProfile(string $message_name)
  {
    $this->units = GlobalProfile::getUnitsForMessageAndFieldType($message_name, $this->name);
  }

}
