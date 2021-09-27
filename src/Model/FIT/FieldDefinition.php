<?php

namespace App\Model\FIT;


class FieldDefinition
{

  protected $name;
  protected $value; // unsure if this should be called value or perhaps base_type - needs more reasearch
  protected $units;

  public function __construct($name, $value, $units)
  {
    $this->name   = $name;
    $this->value  = $value;
    $this->units  = $units;
  }

}
