<?php

namespace App\Model\FIT;

use App\Utility\AutoSettablePropertiesTrait;

class Field
{
  use AutoSettablePropertiesTrait;

  protected $name;
  protected $value;
  protected $units;
  protected $def_num;     // the definition number for the field

  public function __construct($properties)
  {
    $this->setPropertiesFromArray($properties);
  }

  public function getName()
  {
    return $this->name;
  }

  public function getValue()
  {
    return $this->value;
  }

  public function getUnits()
  {
    return $this->units;
  }

  public function setName($name)
  {
    $this->name = $name;
  }

  public function setValue($value)
  {
    $this->value = $value;
  }

  public function setUnits($units)
  {
    $this->units = $units;
  }

  public function getNumber()
  {
    return $this->def_num;
  }

}
