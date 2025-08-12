<?php

use PHPUnit\Framework\TestCase;
use App\Utility\MultipleValueAggregator;


final class MultipleValueAggregatorTest extends TestCase
{

  /**
   * @dataProvider mvaMinMaxSumCountAverageFromArrayProvider
   */
  public function testMvaMinMaxSumCountAverageFromArray($arrays, $keys, $expected)
  {
    $mva = new MultipleValueAggregator();
    foreach ($arrays as $array) {
      $mva->addValuesFromArray($array, $keys);
    }

    foreach ($keys as $key) {
      $this->assertEquals($expected[$key]['min'],     $mva->getMin($key));
      $this->assertEquals($expected[$key]['max'],     $mva->getMax($key));
      $this->assertEquals($expected[$key]['sum'],     $mva->getSum($key));
      $this->assertEquals($expected[$key]['count'],   $mva->getCount($key));
      $this->assertEquals($expected[$key]['average'], $mva->getAverage($key));
    }

  }

  public function mvaMinMaxSumCountAverageFromArrayProvider()
  {
    $arrays = [
      [ 'distance' => 84,  'speed' => 18.5, 'altitude' => 2453 ],
      [ 'distance' => 87,  'speed' => 23.2, 'altitude' => 1851 ],
      [ 'distance' => 129, 'speed' => 20.2, 'altitude' => 1979 ],
      [ 'distance' => 155, 'speed' => 22.3, 'altitude' => 2391 ],
      [ 'distance' => 206, 'speed' => 20.7, 'altitude' => 2059 ],
      [ 'distance' => 265, 'speed' => 22.8, 'altitude' => 1504 ],
      [ 'distance' => 331, 'speed' => 25.1, 'altitude' => 1483 ],
      [ 'distance' => 360, 'speed' => 22,   'altitude' => 1740 ],
      [ 'distance' => 451, 'speed' => 22.1, 'altitude' => 1507 ],
      [ 'distance' => 504, 'speed' => 20.3, 'altitude' => 2001 ]
    ];
    $keys = ['distance', 'speed', 'altitude'];
    $expected = [
      'distance' => [
        'min' => 84,
        'max' => 504,
        'sum' => 2572,
        'count' => 10,
        'average' => 257.2,
      ],
      'speed' => [
        'min' => 18.5,
        'max' => 25.1,
        'sum' => 217.2,
        'count' => 10,
        'average' => 21.72,
      ],
      'altitude' => [
        'min' => 1483,
        'max' => 2453,
        'sum' => 18968,
        'count' => 10,
        'average' => 1896.8,
      ],
    ];
    return [
      [$arrays, $keys, $expected]
    ];
  }
}
