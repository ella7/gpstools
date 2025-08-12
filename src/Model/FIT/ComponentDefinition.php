<?php

namespace App\Model\FIT;

class ComponentDefinition extends FieldDefinition
{

  /* *** Other properties that may be needed down the road *** */
  protected $accumulate;
  protected $bits;
  protected $bit_offset;

  public function getBits()
  {
    return $this->bits;
  }

}
