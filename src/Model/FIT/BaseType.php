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


  /**
   * Construct a BaseType from an associative properties array.
   *
   * The array should contain keys used to initialize the instance (for example: 'name', 'identifier', 'size', 'invalid_value').
   *
   * @param array $properties Associative array of properties used to initialize the instance.
   */
  public function __construct($properties)
  {
    $this->setPropertiesFromArray($properties);
  }

  /**
   * Get the BaseType name.
   *
   * Returns the type name for this BaseType instance (one of the values defined by validNames()).
   *
   * @return string|null The name of the base type, or null if not set.
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Get the type identifier.
   *
   * Returns the identifier value associated with this BaseType (may be an integer or string, or null if unset).
   *
   * @return int|string|null
   */
  public function getIdentifier()
  {
    return $this->identifier;
  }

  /**
   * Return the invalid-value placeholder for this FIT base type.
   *
   * The invalid value represents the sentinel used to indicate missing or invalid data
   * for this type.
   *
   * @return mixed|null The configured invalid value, or null if not set.
   */
  public function getInvalidValue()
  {
    return $this->invalid_value;
  }

  /**
   * Get the size of the base type.
   *
   * @return int|null The size (in bytes) if known, or null if not set.
   */
  public function getSize()
  {
    return $this->size;
  }

  /**
   * Set the BaseType name.
   *
   * The provided name must be one of the allowed FIT base type names (see validNames()).
   *
   * @param string $name One of the allowed base type names.
   * @throws \Exception If $name is not in the allowed list.
   */
  public function setName($name)
  {
    if(!in_array($name, self::validNames())){
      throw new \Exception("Attempting to set BaseType name to an invalid value $name", 1);
    }
    $this->name = $name;
  }

  /**
   * Return the list of valid BaseType names.
   *
   * This list is used for validating values passed to setName().
   *
   * @return string[] Allowed base type names.
   */
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

  /**
   * Determine whether a value is an array that contains the required BaseType properties.
   *
   * Returns true only if $array is an array and contains the keys:
   * 'name', 'identifier', 'size', and 'invalid_value'. Any non-array or missing key yields false.
   *
   * @param mixed $array The value to validate as a properties array.
   * @return bool True if $array looks like a valid BaseType properties array, false otherwise.
   */
  public static function looksLikeValidPropertiesArray($array)
  {
    if(!is_array($array)) return false;
    $required_keys = ['name', 'identifier', 'size', 'invalid_value'];
    foreach($required_keys as $required_key){
      if(!array_key_exists($required_key, $array)) return false;
    }
    return true;
  }

}
