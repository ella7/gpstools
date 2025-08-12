<?php

namespace App\Model\FIT;

use App\Model\FIT\Field;
use App\Model\FIT\BaseType;
use App\Model\FIT\FieldType;
use App\Model\FIT\SubfieldDefinition;
use App\Model\FIT\GlobalProfileAccess;
use App\Model\FIT\ComponentDefinition;
use function Symfony\Component\String\u;

class FieldDefinition extends Field
{

  protected $subfields  = [];
  protected $components = [];

  /* *** Other properties that may be needed down the road *** */
  protected $type;        // Either a FieldType or BaseType for the field - will create an abstract class or interface for both to use
  protected $size;        // Size (in bytes) of the field
  protected $scale;       // A scale factor to be applied to the raw value found in the fit file
  protected $offset;      // An offset to be added to the raw value in the fit file

  /**
   * TODO:  getFinalFieldDefinition in other classes could return a Field, Subfield or Comoponent.
   *        Here we have the option to pass a message_name as well, and if it doesn't match what
   *        we would otherwise return, we could check the Components.
   */
  public function getFinalDefinition(DataMessage $message)
  {
    if($this->hasSubfields()){
      foreach ($this->subfields as $subfield) {
        if($subfield->matchesMessage($message)){
          return $subfield;
        }
      }
    }
    return $this;
  }

  public function getFinalUnits(DataMessage $message)
  {
    return $this->getFinalDefinition($message)->units;
  }

  /**
   * Whether this field has any subfield definitions.
   *
   * @return bool True if one or more SubfieldDefinition objects are present; false otherwise.
   */
  public function hasSubfields()
  {
    return (count($this->subfields) > 0);
  }

  /**
   * Get the subfield definitions for this field.
   *
   * @return SubfieldDefinition[] Array of SubfieldDefinition objects (may be empty).
   */
  public function getSubfields()
  {
    return $this->subfields;
  }

  /**
   * Replace the current subfields with the given list.
   *
   * Each element of `$a` must be either a SubfieldDefinition instance or an associative
   * array of subfield properties (which will be used to construct a SubfieldDefinition).
   * Existing subfields are cleared before the new list is set.
   *
   * @param array $a Array of SubfieldDefinition objects or arrays convertible to SubfieldDefinition.
   * @throws \Exception If any element is not a SubfieldDefinition after conversion.
   */
  public function setSubfields($a)
  {
    $this->subfields = [];
    foreach($a as $subfield){
      if(is_array($subfield)){
        $subfield = new SubfieldDefinition($subfield);
      }
      if(!($subfield instanceof SubfieldDefinition)){
        throw new \Exception("Attempting to add a subfield to a FieldDefinition that is not an instance of SubfieldDefinition");
      }
      $this->subfields[] = $subfield;
    }
  }

  /**
   * Replace the current components list with the provided components.
   *
   * Accepts an array where each element is either a ComponentDefinition instance
   * or an associative array of properties that will be used to construct a
   * ComponentDefinition. Clears any existing components and appends each valid
   * ComponentDefinition to the field.
   *
   * @param array $a Array of ComponentDefinition objects or arrays convertible to ComponentDefinition.
   * @throws \Exception If any element is not a ComponentDefinition after conversion.
   */
  public function setComponents($a)
  {
    $this->components = [];
    foreach($a as $component){
      if(is_array($component)){
        $component = new ComponentDefinition($component);
      }
      if(!($component instanceof ComponentDefinition)){
        throw new \Exception("Attempting to add a component to a FieldDefinition that is not an instance of ComponentDefinition");
      }
      $this->components[] = $component;
    }
  }

  /**
   * Whether this field definition defines any components.
   *
   * @return bool True if one or more ComponentDefinition objects are present; false otherwise.
   */
  public function hasComponents()
  {
    return (count($this->components) > 0);
  }

  /**
   * Return the components that make up this field.
   *
   * Each element is a ComponentDefinition describing a component of the field.
   * Returns an empty array if no components are defined.
   *
   * @return ComponentDefinition[] Array of ComponentDefinition objects.
   */
  public function getComponents()
  {
    return $this->components;
  }

  /**
   * Create a FieldDefinition populated from the global profile and apply local properties.
   *
   * Looks up a FieldDefinition in the global profile using the given message global number
   * and the 'def_num' entry from $properties, then calls setPropertiesFromArray($properties)
   * on the retrieved definition and returns it.
   *
   * @param int   $message_global_number Global message number used for the global-profile lookup.
   * @param array $properties            Properties to apply to the retrieved definition. Must include 'def_num'.
   * @return self The FieldDefinition instance with properties applied.
   */
  public static function initFromGlobalProfile($message_global_number, $properties)
  {
    $field_definition = GlobalProfileAccess::getFieldDefinition(
      $message_global_number, $properties['def_num']
    );
    return $field_definition->setPropertiesFromArray($properties);
  }

  /**
   * Create a FieldDefinition by looking it up in the global profile using message and field names,
   * then apply the provided properties to that definition.
   *
   * Retrieves a FieldDefinition from GlobalProfileAccess using the given message type name and
   * the 'name' value contained in $properties, calls setPropertiesFromArray($properties) on it,
   * and returns the resulting FieldDefinition.
   *
   * @param string $message_type_name Message type name used for the global-profile lookup.
   * @param array $properties Associative array of field properties; must contain a 'name' key.
   * @return FieldDefinition The field definition from the global profile with $properties applied.
   */
  public static function initFromGlobalProfileByNames($message_type_name, $properties) : FieldDefinition
  {
    $field_definition = GlobalProfileAccess::getFieldDefinitionByNames(
      $message_type_name, $properties['name']
    );
    return $field_definition->setPropertiesFromArray($properties);
  }

  /**
   * Set the field's type.
   *
   * Accepts either a FieldType or BaseType instance, or an associative array of properties
   * that will be wrapped into a BaseType or FieldType automatically (arrays matching
   * BaseType properties become a BaseType; arrays matching FieldType properties become a FieldType).
   *
   * @param mixed $type FieldType|BaseType|array Properties or an instance to assign as the field's type.
   * @throws \Exception If the provided value cannot be resolved to a FieldType or BaseType.
   */
  public function setType($type)
  {
    // if the array contains the base_type properties
    if(BaseType::looksLikeValidPropertiesArray($type)){
      $type = new BaseType($type);
    }
    // if the array contains the field_type properties
    if(FieldType::looksLikeValidPropertiesArray($type)){
      $type = new FieldType($type);
    }
    if(!($type instanceof FieldType || $type instanceof BaseType)){
      throw new \Exception("Attempting to set the FieldDefinition type to a value that is not an instance of FieldType");
    }
    $this->type = $type;
  }

  /**
   * Return the underlying BaseType for this field.
   *
   * If the field's `type` is already a BaseType it is returned directly;
   * if `type` is a FieldType the FieldType's base type is returned.
   *
   * @return BaseType The resolved base type instance.
   * @throws \Exception If neither a BaseType nor FieldType is set on the field.
   */
  public function getBaseType()
  {
    if($this->type instanceof BaseType){
      return $this->type;
    }

    if($this->type instanceof FieldType){
      return $this->type->getBaseType();
    }

    throw new \Exception("Couldn't find a base_type", 1);
  }

  /**
   * Get the name of the field's base type.
   *
   * Returns the base type's name regardless of whether this field's type is a BaseType
   * or a FieldType wrapper.
   *
   * @return string
   * @throws \Exception If a base type cannot be resolved.
   */
  public function getBaseTypeName()
  {
    return $this->getBaseType()->getName();
  }

  /**
   * Get the size (in bytes) of the field's underlying base type.
   *
   * @return int Size in bytes of the base type.
   * @throws \Exception If a base type cannot be determined.
   */
  public function getBaseTypeSize()
  {
    return $this->getBaseType()->getSize();
  }

  /**
   * Return the sentinel value that represents an invalid/missing value for this field's base type.
   *
   * The returned value is the base type's defined invalid value (raw, before any scale/offset).
   *
   * @return mixed The invalid value used by the base type.
   */
  public function getInvalidValue()
  {
    return $this->getBaseType()->getInvalidValue();
  }

  /**
   * Get the field's size in bytes.
   *
   * @return int|null Size of the field (in bytes), or null if not set.
   */
  public function getSize()
  {
    return $this->size;
  }

  /**
   * Set the field size in bytes.
   *
   * The size must be a multiple of the field's base type size; otherwise an exception is thrown.
   *
   * @param int $size Field size in bytes.
   * @throws \Exception If $size is not a multiple of the base type size.
   */
  public function setSize($size)
  {
    $base_type_size = $this->getBaseTypeSize();
    if($size % $base_type_size !== 0){
      throw new \Exception("Attempting to set field size to $size. Must be a multiple of the base type size: $base_type_size", 1);
    }
    $this->size = $size;
  }

  /**
   * Set the field size based on a count of base-type values.
   *
   * Multiplies the given number of base-type values by the base type size and sets
   * the resulting byte size on this field.
   *
   * @param int $n Number of base-type values to allocate space for.
   */
  public function setSizeFromNumberOfValues($n)
  {
    $this->setSize($n * $this->getBaseTypeSize());
  }

  /**
   * Return the number of base-type values contained in this field.
   *
   * Computed as the field size divided by the size of the base type.
   *
   * @return int Number of base-type values (size / base type size).
   */
  public function getNumberOfValues()
  {
    return (int)$this->size / $this->getBaseTypeSize();
  }

  /**
   * Get the field's scale factor.
   *
   * The scale is applied to raw stored values to convert them into real-world units (e.g. value = raw / scale).
   *
   * @return float|int|null The scale factor, or null if no scale is defined.
   */
  public function getScale()
  {
    return $this->scale;
  }

  /**
   * Returns the field's offset applied to raw values.
   *
   * The offset is added to raw numeric values after scaling. Can be null if no offset is defined.
   *
   * @return float|int|null
   */
  public function getOffset()
  {
    return $this->offset;
  }
}
