<?php

namespace App\Utility;

class ValueAggregator
{
  protected $min;
  protected $max;
  protected $sum;
  protected $count;
  protected $initialized = false;

  /**
   * Initialize the aggregator with the first value.
   *
   * Sets min, max, and sum to the provided value, sets count to 1, and marks the aggregator as initialized.
   *
   * @param float $value The initial value to seed the aggregates.
   */
  public function init($value)
  {
    $this->min = $value;
    $this->max = $value;
    $this->sum = $value;
    $this->count = 1;
    $this->initialized = true;
  }

  /**
   * Incorporates a numeric value into the running aggregates (min, max, sum, count).
   *
   * If this is the first value added, initializes the aggregator with it; otherwise updates
   * min and max as needed and increments sum and count.
   *
   * @param float $value The numeric value to add to the aggregates.
   */
  public function addValue($value)
  {
    if(!$this->initialized){
      $this->init($value);
    } else {
      $this->min = $value < $this->min ? $value : $this->min;
      $this->max = $value > $this->max ? $value : $this->max;
      $this->sum += $value;
      $this->count++;
    }
  }

  /**
   * Get the current minimum value seen by the aggregator.
   *
   * Returns null if no value has been added yet.
   *
   * @return float|null The minimum value, or null when uninitialized.
   */
  public function getMin()
  {
    return $this->min;
  }

  /**
   * Get the current maximum value seen by the aggregator.
   *
   * @return float|null The maximum value, or `null` if no values have been added.
   */
  public function getMax()
  {
    return $this->max;
  }

  /**
   * Return the running sum of all values added to the aggregator.
   *
   * The sum is updated by init() and addValue(). Returns null if no value has been added yet.
   *
   * @return float|null The cumulative sum, or null when uninitialized.
   */
  public function getSum()
  {
    return $this->sum;
  }

  /**
   * Get the number of values that have been aggregated.
   *
   * @return int The count of values added.
   */
  public function getCount()
  {
    return $this->count;
  }

  /**
   * Return the arithmetic mean of all values added.
   *
   * Calculates the average as getSum() divided by getCount(). This method does not guard against a zero count;
   * calling it before any values have been added will trigger a division-by-zero condition.
   *
   * @return float The average of the aggregated values.
   */
  public function getAverage()
  {
    return $this->getSum()/$this->getCount();
  }

}
