<?php

namespace App\Model\FIT;

use App\Utility\AutoSettablePropertiesTrait;
use App\Model\FIT\FieldDefinition;
use App\Model\FIT\GlobalProfileAccess;

class Message
{
  use AutoSettablePropertiesTrait;

  const MESSAGE_TYPE_DATA = 'Data';
  const MESSAGE_TYPE_DEFINITION = 'Definition';

  protected $type;              // $type must be MESSAGE_TYPE_DATA or MESSAGE_TYPE_DEFINITION
  protected $local_number;      // this is only applicable to definitions within a FIT file (not GlobalProfile for example)
  protected $global_number;     // this is the index to be used to find the message definition in the GlobalProfile
  protected $name;              // $name is derived from the Global Profile
  protected $fields;            // array of FIT\Field (or child) objects
  protected $num_empty_fields;  /**
   * Construct a Message instance from an array of properties.
   *
   * Populates the object's properties from the provided associative array and,
   * if a `global_number` is present and `name` is not set, derives and sets the
   * message `name` from the global profile.
   *
   * @param array $properties Associative array of properties used to populate the instance.
   */

  public function __construct($properties)
  {
    $this->setPropertiesFromArray($properties);
    if(isset($this->global_number) && $this->name === null){
      $this->setNameFromGlobalNumber($this->global_number);
    }
  }

  /**
   * Get the message type.
   *
   * Returns the message's type identifier. Expected values are the class constants
   * `MESSAGE_TYPE_DATA` ("Data") or `MESSAGE_TYPE_DEFINITION` ("Definition").
   *
   * @return string The message type.
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Set the message type.
   *
   * Accepts only self::MESSAGE_TYPE_DATA or self::MESSAGE_TYPE_DEFINITION and assigns it to the message.
   *
   * @param string $type The message type to set.
   * @throws \Exception If $type is not `Data` or `Definition`.
   */
  public function setType($type)
  {
    if(!($type === self::MESSAGE_TYPE_DATA || $type === self::MESSAGE_TYPE_DEFINITION)){
      throw new \Exception("FIT\Message type cannot be set to $type. Must be `Data` or `Definition`", 1);
    }
    $this->type = $type;
  }

  /**
   * Get the message name.
   *
   * Returns the message name, which may be derived from the global profile or left null if unset.
   *
   * @return string|null The message name or null if not set.
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Get the message's fields.
   *
   * Returns the array of Field (or FieldDefinition) objects belonging to this message.
   *
   * @return Field[] Array of fields (may be empty).
   */
  public function getFields()
  {
    return $this->fields;
  }

  /**
   * Set the message's fields from an array of items.
   *
   * Accepts an array of Field instances, FieldDefinition instances, or associative arrays.
   * If an element is an array it will be converted to a Field when this message's type
   * is MESSAGE_TYPE_DATA, or to a FieldDefinition otherwise. Each resulting Field is
   * appended via addField().
   *
   * @param array $a Array of Field|FieldDefinition|array entries representing fields.
   * @throws \Exception If any item after conversion is not an instance of Field.
   */
  public function setFields($a)
  {
    $this->fields = [];
    foreach($a as $field){
      if(is_array($field)){
        $field = ($this->type === self::MESSAGE_TYPE_DATA) ? new Field($field) : new FieldDefinition($field);
      }
      if(!($field instanceof Field)){
        throw new \Exception("Attempting to add a field to a Message that is not an instance of Field");
      }
      $this->addField($field);
    }
  }

  /**
   * Append a Field to this message's fields collection.
   *
   * @param Field $field The field to add; must be an instance of App\Model\FIT\Field (or subclass).
   */
  public function addField(Field $field)
  {
    $this->fields[] = $field;
  }

  / **
   * Return the number of fields contained in this message.
   *
   * @return int The count of Field objects in the message's fields array.
   */
  public function numberOfFields()
  {
    return count($this->fields);
  }

  /**
   * Get the number of empty fields tracked for this message.
   *
   * This value is intended to assist CSV FIT data parsing and represents
   * how many fields are considered empty for the message.
   *
   * @return int The number of empty fields.
   */
  public function numberOfEmptyFields()
  {
    return $this->num_empty_fields;
  }

  /**
   * Return a compact key describing this message.
   *
   * The returned associative array contains three entries:
   * - `type`: message type (one of MESSAGE_TYPE_DATA or MESSAGE_TYPE_DEFINITION).
   * - `local_number`: local message number used within a FIT file (may be null).
   * - `name`: message name (may be null or derived from the global profile).
   *
   * @return array{type:string,local_number:int|null,name:string|null}
   */
  public function getMessageKey()
  {
    return [
      'type' => $this->type,
      'local_number' => $this->local_number,
      'name' => $this->name
    ];
  }

  /**
   * Get the message's local number (index used within a FIT file).
   *
   * @return int|null The local message number, or null if not set.
   */
  public function getLocalNumber()
  {
    return $this->local_number;
  }

  /**
   * Set the local message number used within a FIT file.
   *
   * The local number identifies this message instance inside a FIT file (distinct from the
   * global_number which indexes the Global Profile). Typically used for message definitions.
   *
   * @param int $local_number The local message number.
   */
  public function setLocalNumber($local_number)
  {
    $this->local_number = $local_number;
  }

  /**
   * Get the message's global number (index into the Global Profile).
   *
   * The global number identifies this message type in the shared Global Profile and may be null
   * when the message is not associated with a global definition.
   *
   * @return int|null The global profile index for this message, or null if not set.
   */
  public function getGlobalNumber()
  {
    return $this->global_number;
  }

  /**
   * Set the message name by looking up the global profile name for a global message number.
   *
   * Uses GlobalProfileAccess to resolve the name for the provided global message number and assigns it
   * to this message's `name` property.
   *
   * @param int $global_number Global profile message number used to derive the message name.
   */
  public function setNameFromGlobalNumber($global_number)
  {
    $this->name = GlobalProfileAccess::getFieldTypeValue('global_number', $global_number);
  }

  /**
   * Get the value of the field with the given definition number.
   *
   * Looks up the Field by its definition number and returns that Field's value.
   *
   * @param int $def_num The field definition number to look up.
   * @return mixed The value stored in the matched Field.
   */
  public function getFieldValueByDefNum($def_num)
  {
    return $this->getFieldByDefNum($def_num)->getValue();
  }

  /**
   * Determine whether the message contains a field with the specified definition number.
   *
   * @param int $def_num The field definition number to look for.
   * @return bool True if a field with the given definition number exists, false otherwise.
   */
  public function hasAFieldDefNum(int $def_num): bool
  {
    foreach ($this->fields as $field) {
      if($def_num == $field->getNumber()){
        return true;
      }
    }
    return false;
  }

  /**
   * Return the Field whose definition number equals the given definition number.
   *
   * Searches this message's fields for a Field with a matching definition number.
   *
   * @param int $def_num Definition number to find.
   * @return Field The matching Field.
   * @throws \Exception If no field with the given definition number exists.
   */
  public function getFieldByDefNum(int $def_num): Field
  {
    foreach ($this->fields as $field) {
      if($def_num == $field->getNumber()){
        return $field;
      }
    }
    throw new \Exception("This message does not contain any fields of def_num: $def_num", 1);
  }

}
