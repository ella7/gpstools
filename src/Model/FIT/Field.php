<?php

namespace App\Model\FIT;

use App\Utility\Utility;
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

  // TODO: Make decision about if/how to store multiple values
  public function getValues()
  {
    $values = $this->getValue();
    if(!is_array($values)){
      $values = [$values];
    }
    return $values;
  }

  public function getValueAsString()
  {
    $values = $this->getValues();
    foreach ($values as $key => $value) {
      $values[$key] = (is_float($value)) ? Utility::formatFloat($value) : (string)$value;
    }
    return implode('|', $values);
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

  public function setNumber($def_num)
  {
    $this->def_num = $def_num;
  }

}
