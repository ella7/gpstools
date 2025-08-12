<?php

namespace App\Model\FIT;

use App\Utility\AutoSettablePropertiesTrait;
use App\Model\FIT\BaseType;

class FieldType
{
  use AutoSettablePropertiesTrait;

  /** @var string */
  protected $name;

  /** @var BaseType */
  protected $base_type;

  /** @var array */
  protected $values;


  /**
   * Construct a FieldType from an associative properties array.
   *
   * The array is passed to AutoSettablePropertiesTrait::setPropertiesFromArray to initialize
   * the object's properties. The array is expected to include at minimum the keys
   * "name" and "base_type" (see looksLikeValidPropertiesArray).
   *
   * @param array $properties Associative array of initial properties for the FieldType.
   */
  public function __construct($properties)
  {
    $this->setPropertiesFromArray($properties);
  }

  /**
   * Get the field's name.
   *
   * @return string The field name.
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Return the field's BaseType instance.
   *
   * @return BaseType|null The assigned BaseType, or null if none has been set.
   */
  public function getBaseType()
  {
    return $this->base_type;
  }

  /**
   * Set the field's base type.
   *
   * Accepts either a BaseType instance or an array of properties which will be used
   * to construct a new BaseType. If the provided value is not a BaseType after
   * conversion, an exception is thrown.
   *
   * @param array|BaseType $base_type Array of properties for BaseType or a BaseType instance.
   * @throws \Exception If the resulting value is not an instance of BaseType.
   */
  public function setBaseType($base_type) : void
  {
    if(is_array($base_type)){
      $base_type = new BaseType($base_type);
    }
    if(!($base_type instanceof BaseType)){
      throw new \Exception("Attempting to add a base_type to a FieldDefinition that is not an instance of BaseType");
    }
    $this->base_type = $base_type;
  }

  /**
   * Return all defined values for this field type.
   *
   * @return string[] Array of possible values, keyed by their numeric identifier.
   */
  public function getValues()
  {
    return $this->values;
  }

  /**
   * Get the enumerated string value for the given identifier.
   *
   * The identifier selects an entry from this field type's internal values array and returns the corresponding string.
   *
   * @param int $identifier Index/key of the enumerated value in the values array.
   * @return string The enumerated value.
   */
  public function getValue(int $identifier) : string
  {
    return $this->values[$identifier];
  }

  /**
   * Set the field type's name.
   *
   * This assigns the provided name to the FieldType instance without additional validation.
   *
   * @param string $name The name for this field type.
   */
  public function setName($name)
  {
    $this->name = $name;
  }

  /**
   * Check whether a value is a properties array suitable for constructing a FieldType.
   *
   * Returns true only if the provided value is an array and contains the required keys
   * 'name' and 'base_type'. Returns false for non-arrays or arrays missing any required key.
   *
   * @param mixed $array Candidate properties value (expected to be an associative array).
   * @return bool True if $array is an array containing 'name' and 'base_type'; otherwise false.
   */
  public static function looksLikeValidPropertiesArray($array)
  {
    if(!is_array($array)) return false;
    $required_keys = ['name', 'base_type'];
    foreach($required_keys as $required_key){
      if(!array_key_exists($required_key, $array)) return false;
    }
    return true;
  }

}
