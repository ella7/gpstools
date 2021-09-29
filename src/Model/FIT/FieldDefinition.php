<?php

namespace App\Model\FIT;

class FieldDefinition
{

  protected $name;
  protected $value; // TODO: I don't know what value represents in the CSV file
  protected $units;
  protected $subfields;

  /* *** Other properties that may be needed down the road *** */
  // protected $type        // stores or references the Global MESSAGE_TYPE (the enum for the field)
  // protected $def_num     // the definition number for the field - ordinal index
  // protected $scale       // currently handled by the FitCSVTool, but might be good to know
  // protected $components  // need to learn about components still
  // protected $offset      // like scale, currently handled by the FitCSVTool

  public function __construct($name, $value, $units)
  {
    $this->name   = $name;
    $this->value  = $value;
    $this->units  = $units;
  }

  public function getUnits()
  {
    return $this->units;
  }

  public function setSubfieldsFromGlobalProfile($message_name)
  {
    $this->subfields = null;
    if($subfields_array = GlobalProfile::getSubfields($message_name, $this->name)){
      foreach ($subfields_array as $subfield_array) {
        $this->subfields[] = new SubfieldDefinition($subfield_array);
      }
    }
  }

  public function setUnitsFromGlobalProfile($message_name)
  {
    $this->units = GlobalProfile::getUnitsForMessageAndFieldType($message_name, $this->name);
  }

  // TODO: This seems bad. Need to look at if/how/why this is needed and make sure it belongs. Likely temporary.
  public function exportAsArray()
  {
    return [
      'name' => $this->name,
      'value' => $this->value,
      'units' => $this->units,
    ];
  }

}
