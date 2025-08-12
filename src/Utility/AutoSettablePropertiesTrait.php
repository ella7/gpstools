<?php

namespace App\Utility;

use function Symfony\Component\String\u;

trait AutoSettablePropertiesTrait
{

  /**
   * Populate selectable object properties from an associative array.
   *
   * For each name returned by getAutoSettableProperties(), if the array contains
   * that key the method will attempt to call a setter named `set` + TitleCase
   * of the property (using the same naming transformation as the trait), and
   * fall back to assigning the value directly to the property if no setter
   * exists. Returns $this to allow fluent chaining.
   *
   * @param array $a Associative array of property => value pairs. Keys should match the names returned by getAutoSettableProperties().
   * @return $this
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
    return $this;
  }

  /**
   * Return the list of property names that setPropertiesFromArray will attempt to set.
   *
   * By default this returns the current object's properties (array_keys(get_object_vars($this))).
   * Override in subclasses to restrict or customize which properties are considered settable.
   *
   * @return string[] Array of property names.
   */
  protected function getAutoSettableProperties()
  {
    return array_keys(get_object_vars($this));
  }

}
