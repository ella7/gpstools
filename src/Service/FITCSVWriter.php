<?php

namespace App\Service;

use App\Model\FIT\Message;

class FITCSVWriter {

  const ENCLOSURE_TRIGGER = '# ! # ! #'; // HACK: need to find a better way to force string enclosure

  public function getCSVString(Message $message)
  {
    $line = $message->getMessageKey();

    if($message->getType() === MESSAGE::MESSAGE_TYPE_DEFINITION){
      foreach($message->getFields() as $field){
        $line = array_merge($line, [
          $field->getName(),
          $field->getValue(),
          '',                         // units should be left empty
        ]);
      }
    }
    if($message->getType() === MESSAGE::MESSAGE_TYPE_DATA){
      $field_index = 0;
      foreach($message->getFields() as $field_name => $value){
        $line = array_merge($line,
          [
            $field_name,
            $value.self::ENCLOSURE_TRIGGER,
            $message->getFieldUnits($field_index)
          ]
        );
        $field_index++;
      }
    }
    array_push($line, ''); // to match, we're adding an empty column to the end of each row
    return self::str_putcsv($line);
  }

  protected static function str_putcsv($input, $delimiter = ',', $enclosure = '"')
  {
    $fp = fopen('php://temp', 'r+b');
    fputcsv($fp, $input, $delimiter, $enclosure);
    rewind($fp);
    $data = rtrim(stream_get_contents($fp), "\n");
    fclose($fp);
    return str_replace(self::ENCLOSURE_TRIGGER, '', $data);
  }

  public function getHeaderString($num_fields)
  {
    $line = [
      'Type',
      'Local Number',
      'Message'
    ];
    for ($i=1; $i <= $num_fields; $i++){
      $line = array_merge($line, [
        'Field '.$i,
        'Value '.$i,
        'Units '.$i
      ]);
    }
    array_push($line, ''); // to match, we're adding an empty column to the end of each row
    return implode(',', $line);
  }
}
