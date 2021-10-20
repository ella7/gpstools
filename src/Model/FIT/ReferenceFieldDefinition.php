<?php

namespace App\Model\FIT;

use App\Utility\AutoSettablePropertiesTrait;

class ReferenceFieldDefinition
{
  use AutoSettablePropertiesTrait;
  
  protected $name;
  protected $value;
  protected $raw_value;

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

}
