<?php

namespace App\Model\FIT;

class FieldDefinition
{

  protected $name;
  protected $value; // TODO: I don't know what value represents in the CSV file
  protected $units;

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

  public function setUnitsFromGlobalProfile($message)
  {
    $this->units = GlobalProfile::getUnitsForMessageAndFieldType($message, $this->name);
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
