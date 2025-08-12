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
  protected $def_num;     /**
   * Construct a Field and initialize its properties.
   *
   * @param array|object|null $properties Initial values for the field's properties (e.g. 'name', 'value', 'units', 'def_num').
   */

  public function __construct($properties)
  {
    $this->setPropertiesFromArray($properties);
  }

  public function getName()
  {
    return $this->name;
  }

  /**
   * Return the field's stored value.
   *
   * The value may be null, a scalar (string/number/etc.), or an array when multiple
   * values have been added to the field.
   *
   * @return mixed The current value (null, scalar, or array of values).
   */
  public function getValue()
  {
    return $this->value;
  }

  /**
   * Adds a value to this field, normalizing storage to allow single or multiple values.
   *
   * If the current value is null the provided value becomes the stored value.
   * If the current value is an array the provided value is appended.
   * If the current value is a non-array scalar it is converted into a two-element array
   * containing the previous value followed by the new value.
   *
   * @param mixed $value Value to add; may be any scalar or complex type.
   * @return void
   */
  public function addValue($value)
  {
    if ($this->value === null) $this->value = $value;
    if (is_array($this->value)) $this->value[] = $value;
    if ($this->value !== null && !is_array($this->value)){
      $this->value = [$this->value, $value];
    }
  }

  /**
   * Return the field's values as an array.
   *
   * Ensures the result of getValue() is an array: if the underlying value is
   * a scalar (including null) it will be returned as a single-element array,
   * otherwise the original array is returned unchanged. Preserves element types
   * and order.
   *
   * @return mixed[] Array of values for the field.
   */
  public function getValues()
  {
    $values = $this->getValue();
    if(!is_array($values)){
      $values = [$values];
    }
    return $values;
  }

  /**
   * Return the field's values as a pipe-separated string.
   *
   * Retrieves the field values (via getValues()), formats any float values with Utility::formatFloat,
   * casts other values to string, and joins them with '|' into a single string.
   *
   * @return string Pipe-separated string representation of the field's values (empty string if there are no values).
   */
  public function getValueAsString()
  {
    $values = $this->getValues();
    foreach ($values as $key => $value) {
      $values[$key] = (is_float($value)) ? Utility::formatFloat($value) : (string)$value;
    }
    return implode('|', $values);
  }

  /**
   * Get the units associated with this field.
   *
   * @return string|null The units string, or null if not set.
   */
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

  /**
   * Set the units for this field.
   *
   * @param string|null $units Unit label (e.g. "m", "s") or null to clear units.
   */
  public function setUnits($units)
  {
    $this->units = $units;
  }

  /**
   * Get the field's definition number.
   *
   * @return int|null The definition number (def_num) for this field, or null if not set.
   */
  public function getNumber()
  {
    return $this->def_num;
  }

  /**
   * Set the field's definition number.
   *
   * @param int|null $def_num Definition number for this field (or null to unset).
   * @return void
   */
  public function setNumber($def_num)
  {
    $this->def_num = $def_num;
  }

}
