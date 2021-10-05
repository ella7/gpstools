<?php

namespace App\Model\FIT;

use function Symfony\Component\String\u;

class SubfieldDefinition
{
  protected $name;
  protected $units;
  protected $ref_fields;

  public function __construct($properties)
  {
    $this->setPropertiesFromArray($properties);
    return $this;
  }

  /**
   * Evaluates the rules contained in ref_fields against the passed messages
   *
   * @param DataMessage $message    The data message against which the rules will be evaluated
   * @return bool                   Returns true as soon as a rule is met, otherwise returns false
   */
  public function matchesMessage(DataMessage $message)
  {
    /*
    echo "we are going to see if the message matches the subfield ref_fields: \n";
    echo "MESSAGE: \n";
    dump($message);
    echo "SUBFIELD: \n";
    dump($this);
    */

    if(count($this->ref_fields) < 1 ){
      throw new \Exception('Subfields must contain at least one Reference Field');
    }
    foreach ($this->ref_fields as $ref_field) {
      if($message->getFieldValue($ref_field->getName()) == $ref_field->getValue()){
        return true;
      }
    }
    return false;
  }

  public function setRefFields($a)
  {
    $ref_fields = [];
    foreach($a as $ref_field){
      if(is_array($ref_field)){
        $ref_field = new ReferenceFieldDefinition($ref_field);
      }
      $ref_fields[] = $ref_field;
    }
    $this->ref_fields = $ref_fields;
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
