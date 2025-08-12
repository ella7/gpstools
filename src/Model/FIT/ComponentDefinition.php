<?php

namespace App\Model\FIT;

class ComponentDefinition extends FieldDefinition
{

  /* *** Other properties that may be needed down the road *** */
  protected $accumulate;
  protected $bits;
  protected $bit_offset;

  /**
   * Get the stored bits value for this component.
   *
   * @return mixed The value of the protected $bits property.
   */
  public function getBits()
  {
    return $this->bits;
  }

}
