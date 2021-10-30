<?php

namespace App\Model\FIT;

use App\Utility\AutoSettablePropertiesTrait;
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
  protected $num_empty_fields;  // I expect this to be removed - currently helps handle the extra commas issue when parsing a FIT CSV file

  public function __construct($properties)
  {
    $this->setPropertiesFromArray($properties);
    if(isset($this->global_number) && $this->name === null){
      $this->setNameFromGlobalNumber($this->global_number);
    }
  }

  public function getType()
  {
    return $this->type;
  }

  public function setType($type)
  {
    if(!($type === self::MESSAGE_TYPE_DATA || $type === self::MESSAGE_TYPE_DEFINITION)){
      throw new \Exception("FIT\Message type cannot be set to $type. Must be `Data` or `Definition`", 1);
    }
    $this->type = $type;
  }

  public function getName()
  {
    return $this->name;
  }

  public function getFields()
  {
    return $this->fields;
  }

  public function numberOfFields()
  {
    return count($this->fields);
  }

  public function numberOfEmptyFields()
  {
    return $this->num_empty_fields;
  }

  /**
   * Get a field by it's name index in the fields array
   *
   * TODO: validate that field names are unique in a message
   *
   * @param  string    $field_name    Key (which is name) to the field in fields array
   * @return Field                    The field object stored at field_name in the fields array
   */
  public function getFieldByName(string $field_name)
  {
    return $this->fields[$field_name];
  }

  /**
   * Get the value associated with the field $field_name
   *
   * @param  string $field_name   Name of the feild
   * @return mixed                The value stored for the given field
   */
  public function getFieldValue(string $field_name)
  {
    return $this->getFieldByName($field_name)->getValue();
  }

  /**
   * Get the units associated with the field $field_name
   *
   * @param  string $field_name   Name of the feild
   * @return mixed                The units stored for the given field
   */
  public function getFieldUnits(string $field_name)
  {
    return $this->getFieldByName($field_name)->getUnits();
  }

  /**
   * Get a field by it's "field index" - index in the fields array
   *
   * This makes the bad assumption that associative arrays can be counted on to preserve order.
   * TODO: come up with a better solution - it's helpful for the fields array to be both associative and index based
   *
   * @param  int    $field_index   index in the fields array
   * @return mixed                 The value stored at field_index in the fields array.
   */
  public function getFieldByIndex(int $field_index)
  {
    $this->validateFieldIndex($field_index);
    $fields_keys = array_keys($this->fields);
    return $this->fields[ $fields_keys[ $field_index ] ];
  }

  public function validateFieldIndex(int $field_index)
  {
    if(!$this->isValidFieldIndex($field_index)){
      $exception_message =
        'This ' . $this->name . ' ' . $this->type . ' message has ' . $this->numberOfFields()
        . ' fields. Trying to access field ' . ($field_index + 1)
      ;
      throw new \Exception($exception_message);
    }
  }

  public function isValidFieldIndex(int $field_index)
  {
    return ($this->numberOfFields() > $field_index && $field_index >= 0);
  }


  public function getMessageKey()
  {
    return [
      'type' => $this->type,
      'local_number' => $this->local_number,
      'name' => $this->name
    ];
  }

  public function getLocalNumber()
  {
    return $this->local_number;
  }

  public function setLocalNumber($local_number)
  {
    $this->local_number = $local_number;
  }

  public function getGlobalNumber()
  {
    return $this->global_number;
  }

  public function setNameFromGlobalNumber($global_number)
  {
    $this->name = GlobalProfileAccess::getFieldTypeValue('global_number', $global_number);
  }

}
