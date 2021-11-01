<?php

namespace App\Model\FIT;

use App\Utility\AutoSettablePropertiesTrait;

class BaseType
{
  use AutoSettablePropertiesTrait;

  protected $name;
  protected $identifier;
  protected $invalid_value;
  protected $size;


  public function __construct($properties)
  {
    $this->setPropertiesFromArray($properties);
  }

  public function getName()
  {
    return $this->name;
  }

  public function getIdentifier()
  {
    return $this->identifier;
  }

  public function getInvalidValue()
  {
    return $this->invalid_value;
  }

  public function getSize()
  {
    return $this->size;
  }

  public function setName($name)
  {
    if(!in_array($name, self::validNames())){
      throw new \Exception("Attempting to set BaseType name to an invalid value $name", 1);
    }
    $this->name = $name;
  }

  protected static function validNames()
  {
    return [
      'enum',
      'sint8',
      'uint8',
      'sint16',
      'uint16',
      'sint32',
      'uint32',
      'string',
      'float32',
      'float64',
      'uint8z',
      'uint16z',
      'uint32z',
      'byte',
      'sint64',
      'uint64',
      'uint64z',
    ];
  }

}
