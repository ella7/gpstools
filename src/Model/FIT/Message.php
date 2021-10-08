<?php

namespace App\Model\FIT;

use function Symfony\Component\String\u;

class Message
{

  const MESSAGE_TYPE_DATA = 'Data';
  const MESSAGE_TYPE_DEFINITION = 'Definition';

  protected $type;
  protected $local_number;
  protected $message;
  protected $fields;
  protected $num_empty_fields;

  public function __construct($properties)
  {
    $this->setPropertiesFromArray($properties);
  }
  
  public function getType()
  {
    return $this->type;
  }

  public function getName()
  {
    return $this->message; // TODO: need to rename this->message to this->name
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
      $message =
        'This ' . $this->name . ' ' . $this->type . ' message has ' . $this->numberOfFields()
        . ' fields. Trying to access field ' . ($field_index + 1)
      ;
      throw new \Exception($message);
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
      'message' => $this->message
    ];
  }

  public function getLocalNumber()
  {
    return $this->local_number;
  }

  /**
   * Attempt to set object properties from an associative array
   *
   * @param array $a  An associatve array with keys matching the object vars for `$this`
   */
  public function setPropertiesFromArray($a)
  {
    $settable_properties = $this->getAutoSettableProperties();

    foreach ($settable_properties as $property_name) {
      if(array_key_exists($property_name, $a)){
        // First look for $this->setProperty(), and then use "$this->property = ... "
        $property_setter = 'set' . u($property_name)->camel()->title();
        if(method_exists($this, $property_setter)){
          $this->{$property_setter}($a[$property_name]);
        } else {
          $this->{$property_name} = $a[$property_name];
        }
      }
    }
  }

  /**
   * Returns the list of properties that can be set by setPropertiesFromArray
   *
   * Can be overwritten by children in order to provide more specific control
   */
  protected function getAutoSettableProperties()
  {
    return array_keys(get_object_vars($this));
  }
}
