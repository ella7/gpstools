<?php

namespace App\Service;

use App\Model\FIT\Message;
use App\Model\FIT\File;
use Symfony\Component\Filesystem\Filesystem;


class FITCSVWriter {

  const COLUMNS_PER_FIELD = 3;            // number of columns in the FIT CSV file per field
  const COLUMNS_BEFORE_FIELDS = 3;        // number of columns in the FIT CSV before the field columns
  const ENCLOSURE_TRIGGER = '# ! # ! #';  /**
   * Write a FIT CSV file for the given File model to the specified filesystem path.
   *
   * Converts the File's messages into CSV and writes the resulting content to the provided path.
   *
   * @param File $file FIT file model containing messages to export.
   * @param string $path Destination filesystem path for the generated CSV.
   * @return void
   */

  public function writeFile(File $file, $path)
  {
    $this->CSVFileFromMessages($path, $file->getMessages());
  }

  /**
   * Build a FIT CSV from a sequence of messages and write it to disk.
   *
   * Creates a CSV header (using a fixed max field count), appends one CSV row per
   * message, and writes the resulting CSV content to the given filesystem path.
   *
   * @param string $path Filesystem path where the CSV will be written.
   * @param iterable|array $messages Iterable of App\Model\FIT\Message instances to include in the CSV.
   * @return void
   */
  public function CSVFileFromMessages($path, $messages)
  {
    // $max_number_of_fields = $this->getMaxNumberOfFields($messages);
    $max_number_of_fields = 52;
    $output = $this->getHeaderString($max_number_of_fields) . "\n";
    foreach($messages as $message){
      $output .= $this->getMessageString($message) . "\n";
    }
    $file_system = new Filesystem;
    $file_system->dumpFile($path, $output);
  }

  /**
   * Determine the maximum number of fields present in a collection of messages.
   *
   * Iterates the provided messages and returns the largest value returned by
   * each message's numberOfFields() method.
   *
   * @param iterable|Message[] $messages Iterable of Message objects.
   * @return int The maximum number of fields found (0 if the collection is empty).
   */
  public function getMaxNumberOfFields($messages)
  {
    $max = 0;
    foreach($messages as $message){
      $max = ($message->numberOfFields() > $max) ? $message->numberOfFields() : $max;
    }
    return $max;
  }

  /**
   * Build a single CSV row representing the given FIT Message.
   *
   * For DEFINITION messages the row contains triplets of: field name, number of values, and an empty units column.
   * For DATA messages the row contains triplets of: field name, value (as string), and units. If the message has fewer
   * data fields than its definition, the row is padded with empty columns so field columns align with the definition.
   * The resulting row is returned as a CSV-formatted string (including an extra trailing empty column to match input files).
   *
   * @param Message $message The FIT Message to render as a CSV row.
   * @return string A CSV-formatted line for the message (no terminating newline).
   */
  public function getMessageString(Message $message)
  {
    $line = $message->getMessageKey();

    if($message->getType() === MESSAGE::MESSAGE_TYPE_DEFINITION){
      foreach($message->getFields() as $field){
        $line = array_merge($line, [
          $field->getName(),
          $field->getNumberOfValues(),
          '',                         // units should be left empty
        ]);
      }
    }
    if($message->getType() === MESSAGE::MESSAGE_TYPE_DATA){
      foreach($message->getValidFields() as $field){
        $line = array_merge($line, [
          $field->getName(),
          $field->getValueAsString().self::ENCLOSURE_TRIGGER,
          $field->getUnits(),
        ]);
      }
      if($message->getDefinition()->numberOfFields() > $message->numberOfFields()){
        $length = $message->getDefinition()->numberOfFields() * 3 + 3; // 3 columns per field plus the 3 beginning fields
        $line = array_pad($line, $length, '');
      }
    }
    $length = ($message->numberOfFields() + $message->numberOfEmptyFields()) * self::COLUMNS_PER_FIELD + self::COLUMNS_BEFORE_FIELDS;
    return self::str_putcsv(array_pad($line, $length + 1, '')); // the + 1 is to match input file, we're adding an empty column to the end of each row
  }

  /**
   * Convert an array into a single CSV-formatted line.
   *
   * Writes the provided array to an in-memory CSV stream using the given
   * delimiter and enclosure, returns the resulting CSV line as a string,
   * and strips any occurrences of the internal ENCLOSURE_TRIGGER marker.
   *
   * @param array $input The values to encode as a single CSV row.
   * @param string $delimiter Column delimiter (default: ',').
   * @param string $enclosure Field enclosure character (default: '"').
   * @return string The encoded CSV line (without a trailing newline and with ENCLOSURE_TRIGGER removed).
   */
  public static function str_putcsv($input, $delimiter = ',', $enclosure = '"')
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
