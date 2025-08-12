<?php

namespace App\Utility;

use function Symfony\Component\String\u;

class MultipleValueAggregator
{
  protected $aggregators = [];

  /**
   * updates the aggregate values based on the passed value
   *
   * @param   object  $obj  the object containing properties to be aggregated
   * @param   array   $keys list of object properties to aggregate
   */
  public function addValuesFromObject($obj, $keys)
  {
    foreach($keys as $key){
      if(!array_key_exists($key, $this->aggregators)){
        $this->aggregators[$key] = new ValueAggregator();
      }
      $ag = $this->getAggregator($key);

      $property_getter = 'get' . u($key)->camel()->title();
      if(method_exists($this, $property_getter)){
        $value = $obj->{$property_getter}();
      } else {
        throw new \Exception("MultipleValueAggregator::addValues() The object must have public getter functions for all properties", 1);
      }
      $ag->addValue($value);
    }
  }

  /**
   * updates the aggregate values based on the passed value
   *
   * @param   array  $array   the array containing values to be aggregated
   * @param   array  $keys    list of array keys to aggregate
   */
  public function addValuesFromArray($array, $keys)
  {
    foreach($keys as $key){
      if(!array_key_exists($key, $this->aggregators)){
        $this->aggregators[$key] = new ValueAggregator();
      }
      $ag = $this->aggregators[$key];

      if(!array_key_exists($key, $array)){
        throw new \Exception("The passed array does not have a value for the key `$key`", 1);
      }

      $ag->addValue($array[$key]);
    }
  }

  public function getAggregator($key)
  {
    if(!array_key_exists($key, $this->aggregators)){
      throw new \Exception("No aggregator exists for the key `$key`", 1);
    }
    return $this->aggregators[$key];
  }

  public function getMin($key)
  {
    return $this->getAggregator($key)->getMin();
  }

  public function getMax($key)
  {
    return $this->getAggregator($key)->getMax();
  }

  public function getSum($key)
  {
    return $this->getAggregator($key)->getSum();
  }

  public function getCount($key)
  {
    return $this->getAggregator($key)->getCount();
  }

  public function getAverage($key)
  {
    return $this->getAggregator($key)->getAverage();
  }
}
