<?php

namespace App\Model\FIT;

class FITProfile
{

  public static function fitEnumToString($type, $value)
  {
    $enum_data = self::fitEnumData();
    return isset($enum_data[$type][$value]) ? $enum_data[$type][$value] : 'unknown';
  }

  public static function fitEnumData()
  {
    return [
      'sport' => [  // Have capitalised and replaced underscores with spaces.
        0 => 'Generic',
        1 => 'Running',
        2 => 'Cycling',
        3 => 'Transition',
        4 => 'Fitness equipment',
        5 => 'Swimming',
        6 => 'Basketball',
        7 => 'Soccer',
        8 => 'Tennis',
        9 => 'American football',
        10 => 'Training',
        11 => 'Walking',
        12 => 'Cross country skiing',
        13 => 'Alpine skiing',
        14 => 'Snowboarding',
        15 => 'Rowing',
        16 => 'Mountaineering',
        17 => 'Hiking',
        18 => 'Multisport',
        19 => 'Paddling',
        254 => 'All'
      ]
    ];
  }

}
