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
   * initializes the aggregate values based on the passed value
   *
   * @param float $value
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
   * updates the aggregate values based on the passed value
   *
   * @param float $value
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

  public function getMin()
  {
    return $this->min;
  }

  public function getMax()
  {
    return $this->max;
  }

  public function getSum()
  {
    return $this->sum;
  }

  public function getCount()
  {
    return $this->count;
  }

  public function getAverage()
  {
    return $this->getSum()/$this->getCount();
  }

}
