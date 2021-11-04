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

  // if the function is passed an array of arrays, create a new Field or FieldDefinition
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

  public function addField(Field $field)
  {
    $this->fields[] = $field;
  }

  public function numberOfFields()
  {
    return count($this->fields);
  }

  public function numberOfEmptyFields()
  {
    return $this->num_empty_fields;
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
