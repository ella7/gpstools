<?php

namespace App\Utility;

use function Symfony\Component\String\u;

class MultipleValueAggregator
{
  protected $aggregators = [];

  /**
   * Add values from an object to per-key aggregators.
   *
   * For each key in $keys this method ensures a ValueAggregator exists for that key,
   * builds a getter name from the key (using Symfony's string helper: `get` + TitleCase(key)),
   * calls that getter on the provided object to obtain the value, and adds the value
   * to the corresponding aggregator.
   *
   * @param object $obj  The source object that must expose public getter methods for each key.
   * @param string[] $keys  List of property names to aggregate (keys are transformed into `get` + TitleCase(key)).
   * @throws \Exception If the expected getter method for any key does not exist on the object.
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
   * Add values from an associative array into per-key ValueAggregator instances.
   *
   * For each key in $keys this method ensures a ValueAggregator exists (created on demand)
   * and adds the corresponding value from $array to that aggregator.
   *
   * @param array $array Associative array containing values keyed by property name.
   * @param array $keys  List of keys to aggregate — each key must exist in $array.
   * @throws \Exception If a required key from $keys is missing in $array.
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

  /**
   * Return the ValueAggregator associated with the given key.
   *
   * @param string|int $key The identifier for the requested aggregator.
   * @return \App\Utility\ValueAggregator The aggregator for the key.
   * @throws \Exception If no aggregator exists for the given key.
   */
  public function getAggregator($key)
  {
    if(!array_key_exists($key, $this->aggregators)){
      throw new \Exception("No aggregator exists for the key `$key`", 1);
    }
    return $this->aggregators[$key];
  }

  /**
   * Return the minimum value recorded for a given key.
   *
   * @param string $key The aggregator key.
   * @return mixed The minimum value for the key.
   * @throws \Exception If no aggregator exists for the given key.
   */
  public function getMin($key)
  {
    return $this->getAggregator($key)->getMin();
  }

  /**
   * Return the maximum value recorded for the given key.
   *
   * @param string $key The aggregator key whose maximum is requested.
   * @return mixed The maximum value added for the specified key (type depends on stored values).
   * @throws \Exception If no aggregator exists for the given key.
   */
  public function getMax($key)
  {
    return $this->getAggregator($key)->getMax();
  }

  /**
   * Return the sum of values accumulated for the given key.
   *
   * Retrieves the per-key ValueAggregator and returns its sum.
   *
   * @param string $key Identifier of the aggregated value set.
   * @return int|float The summed value (integer or float).
   * @throws \Exception If no aggregator exists for the provided key.
   */
  public function getSum($key)
  {
    return $this->getAggregator($key)->getSum();
  }

  /**
   * Return the number of values aggregated for the given key.
   *
   * @param string $key The aggregator key.
   * @return int The count of values recorded for the key.
   */
  public function getCount($key)
  {
    return $this->getAggregator($key)->getCount();
  }

  /**
   * Return the average value accumulated for a given key.
   *
   * @param string $key Key identifying the per-key aggregator.
   * @return mixed The average value as computed by the underlying ValueAggregator.
   */
  public function getAverage($key)
  {
    return $this->getAggregator($key)->getAverage();
  }
}
