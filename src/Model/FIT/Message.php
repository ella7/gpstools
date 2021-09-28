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
   * Get the value associated with the field $field_name
   *
   * Method signature defined here, but must be overwritten in child classes
   *
   * @param  string $field_name   Name of the feild
   * @return mixed                The value stored for the given field
   */
  public function getFieldValue(string $field_name) { }

  /**
   * Get the units associated with the field $field_name
   *
   * Method signature defined here, but must be overwritten in child classes
   *
   * @param  string $field_name   Name of the feild
   * @return mixed                The units stored for the given field
   */
  public function getFieldUnits(string $field_name) { }

  /**
   * Returns the list of properties that can be set by setPropertiesFromArray
   *
   * Can be overwritten by children in order to provide more specific control
   */
  protected function getAutoSettableProperties()
  {
    return array_keys(get_object_vars($this));
  }

  public function getLocalNumber()
  {
    return $this->local_number;
  }

  // TODO: Move to FITCSVFileWriter
  public function getCSVString()
  {
    $line[] = $this->type;
    $line[] = $this->local_number;
    $line[] = $this->message;

    if($this->type === self::MESSAGE_TYPE_DEFINITION){
      foreach($this->fields as $field){
        $line = array_merge($line, array_values($field->exportAsArray()));
      }
    }
    if($this->type === self::MESSAGE_TYPE_DATA){
      foreach($this->fields as $field_name => $value){
        $line = array_merge($line, [$field_name, $value, $this->getFieldUnits($field_name)]);
      }
    }
    return str_putcsv($line);
  }

}

if (!function_exists('str_putcsv')) {
    function str_putcsv($input, $delimiter = ',', $enclosure = '"') {
        $fp = fopen('php://temp', 'r+b');
        fputcsv($fp, $input, $delimiter, $enclosure);
        rewind($fp);
        $data = rtrim(stream_get_contents($fp), "\n");
        fclose($fp);
        return $data;
    }
}
