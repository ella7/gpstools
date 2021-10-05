<?php

namespace App\Service;

use App\Model\FIT\Message;

class FITCSVWriter {

  public function getCSVString(Message $message)
  {
    $line = $message->getMessageKey();

    if($message->getType() === MESSAGE::MESSAGE_TYPE_DEFINITION){
      foreach($message->getFields() as $field){
        $line = array_merge($line, array_values($field->exportAsArray()));
      }
    }
    if($message->getType() === MESSAGE::MESSAGE_TYPE_DATA){
      $field_index = 0;
      foreach($message->getFields() as $field_name => $value){
        $line = array_merge($line, [$field_name, $value, $message->getFieldUnits($field_index)]);
        $field_index++;
      }
    }
    return self::str_putcsv($line);
  }

  protected static function str_putcsv($input, $delimiter = ',', $enclosure = '"')
  {
    $fp = fopen('php://temp', 'r+b');
    fputcsv($fp, $input, $delimiter, $enclosure);
    rewind($fp);
    $data = rtrim(stream_get_contents($fp), "\n");
    fclose($fp);
    return $data;
  }

}
