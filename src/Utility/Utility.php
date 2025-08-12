<?php

namespace App\Utility;
use \NumberFormatter;

class Utility
{

  /**
   * Format a numeric value into a locale-aware decimal string (en_US) with no grouping.
   *
   * The output always contains at least one fractional digit and allows up to 100 fractional digits;
   * thousands/grouping separators are removed.
   *
   * @param float|int|string $float Numeric value to format (may be a number or numeric string).
   * @return string|false The formatted decimal string on success, or false on failure.
   */
  public static function formatFloat($float)
  {
    $fmt = new NumberFormatter('en_US', NumberFormatter::DECIMAL);
    $fmt->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 1);
    $fmt->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 100);
    $fmt->setSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, '');
    return $fmt->format($float);
  }
}
