<?php

namespace App\Utility;

use function Symfony\Component\String\u;

trait AutoSettablePropertiesTrait
{

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