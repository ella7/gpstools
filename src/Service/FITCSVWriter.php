<?php

namespace App\Service;

use App\Model\FIT\Message;

class FITCSVWriter {

  const ENCLOSURE_TRIGGER = '# ! # ! #'; // HACK: need to find a better way to force string enclosure

  public function CSVFileFromMessages($path, $messages)
  {
    // $max_number_of_fields = $this->getMaxNumberOfFields($messages);
    $max_number_of_fields = 52;
    $output = $this->getHeaderString($max_number_of_fields) . "\n";
    foreach($messages as $message){
      $output .= $this->getCSVString($message) . "\n";
    }
    file_put_contents($path, $output);
  }

  public function getMaxNumberOfFields($messages)
  {
    $max = 0;
    foreach($messages as $message){
      $max = ($message->numberOfFields() > $max) ? $message->numberOfFields() : $max;
    }
    return $max;
  }

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
      foreach($message->getFields() as $field){
        $line = array_merge($line, [
          $field->getName(),
          $field->getValue().self::ENCLOSURE_TRIGGER,
          $field->getUnits(),
        ]);
      }
      if($message->getDefinition()->numberOfFields() > $message->numberOfFields()){
        // echo "here's the problem \n\n";
        $length = $message->getDefinition()->numberOfFields() * 3 + 3; // 3 columns per field plus the 3 beginning fields
        $line = array_pad($line, $length, '');
        // echo self::str_putcsv($line);
        // exit();
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
