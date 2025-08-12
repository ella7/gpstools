<?php

use PHPUnit\Framework\TestCase;
use App\Utility\ValueAggregator;


final class ValueAggregatorTest extends TestCase
{

  /**
   * @dataProvider minMaxSumCountAverageProvider
   */
  public function testMinMaxSumCountAverage($values, $expected)
  {
    $aggregator = new ValueAggregator();
    foreach ($values as $value) {
      $aggregator->addValue($value);
    }
    $this->assertEquals($expected['min'],     $aggregator->getMin());
    $this->assertEquals($expected['max'],     $aggregator->getMax());
    $this->assertEquals($expected['sum'],     $aggregator->getSum());
    $this->assertEquals($expected['count'],   $aggregator->getCount());
    $this->assertEquals($expected['average'], $aggregator->getAverage());
  }

  public function minMaxSumCountAverageProvider()
  {
    return [
      [
        'values'   => [2.22,3.06,4.43,4.43,10.36,13.18,15.88,19.42,21.59,21.59,24.17,31.46,35.29,38.51,41.51,44.19,46.68,49.72,53.88,53.88],
        'expected' => [
          'min' => 2.22,
          'max' => 53.88,
          'sum' => 535.45,
          'count' => 20,
          'average' => 26.7725,
        ]
      ],
      [
        'values'   => [16,3,2,2,3,3,21,21,4,8,20,6,4,18,14,19,17,13,9,3],
        'expected' => [
          'min' => 2,
          'max' => 21,
          'sum' => 206,
          'count' => 20,
          'average' => 10.3,
        ]
      ]
    ];
  }
}
