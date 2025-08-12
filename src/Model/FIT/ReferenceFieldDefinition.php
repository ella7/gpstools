<?php

namespace App\Model\FIT;

use App\Utility\AutoSettablePropertiesTrait;

class ReferenceFieldDefinition
{
  use AutoSettablePropertiesTrait;

  protected $name;
  protected $def_num;
  protected $value;
  protected $raw_value;

  public function __construct($properties)
  {
    $this->setPropertiesFromArray($properties);
    return $this;
  }

  /**
   * Get the reference field's name.
   *
   * @return string|null The name of the reference field, or null if not set.
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Get the definition number for this reference field.
   *
   * Returns the field's `def_num` value, or null if it has not been set.
   *
   * @return int|null
   */
  public function getDefNum()
  {
    return $this->def_num;
  }

  /**
   * Get the reference field's value.
   *
   * @return mixed The value stored for this reference field.
   */
  public function getValue()
  {
    return $this->value;
  }

  /**
   * Returns the raw (unprocessed) value for this reference field.
   *
   * The raw value is the original value assigned to the field (no normalization or transformation).
   *
   * @return mixed The raw value stored in the field.
   */
  public function getRawValue()
  {
    return $this->raw_value;
  }

}
