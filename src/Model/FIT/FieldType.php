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


  public function __construct($properties)
  {
    $this->setPropertiesFromArray($properties);
  }

  public function getName()
  {
    return $this->name;
  }

  public function getBaseType()
  {
    return $this->base_type;
  }

  /**
   * Sets property $base_type to passed FIT\BaseType object or an array with properties representing a BaseType object
   *
   * @param array | BaseType  $base_type
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

  public function getValues()
  {
    return $this->values;
  }

  /**
   * Return the enumerated value identified by $identifier
   *
   * @param  int    $identifier
   * @return string
   */
  public function getValue(int $identifier) : string
  {
    return $this->values[$identifier];
  }

  public function setName($name)
  {
    $this->name = $name;
  }

  // TODO: Make this into a trait so we don't duplicate here and in BaseType
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
