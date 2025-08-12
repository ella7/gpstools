<?php

namespace App\Utility;
use \NumberFormatter;

class Utility
{

  public static function formatFloat($float)
  {
    $fmt = new NumberFormatter('en_US', NumberFormatter::DECIMAL);
    $fmt->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 1);
    $fmt->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 100);
    $fmt->setSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, '');
    return $fmt->format($float);
  }
}
