<?php

namespace App\Service;

use App\Model\FIT\Message;
use App\Model\FIT\DefinitionMessage;
use App\Model\FIT\DataMessage;
use App\Model\FIT\FieldDefinition;
use App\Model\FIT\Field;
use function Symfony\Component\String\u;
use Symfony\Component\Stopwatch\Stopwatch;

class FITCSVParser
{

  const COLUMNS_PER_FIELD = 3;      // number of columns in the FIT CSV file per field
  const COLUMNS_BEFORE_FIELDS = 3;  // number of columns in the FIT CSV before the field columns

  protected $fitcsv_jar_path;
  protected $tmp_unpack_path;

  protected $fit_path = '';
  protected $csv_key = '';
  protected $converted_to_csv = false;

  public function __construct(string $fitcsv_jar_path, string $tmp_unpack_path)
  {
    // TODO: use symfony/filesystem component rather than direct access
    if(!file_exists($fitcsv_jar_path)){
      throw new \Exception('The FitCSVTool was not found at "'.$fitcsv_jar_path.'" extension');
    }

    if(!file_exists($tmp_unpack_path)){
      mkdir($tmp_unpack_path, 0755, true);
    }

    $this->fitcsv_jar_path = $fitcsv_jar_path;
    $this->tmp_unpack_path = $tmp_unpack_path;
  }

  public function setFitPath(string $path)
  {
    if(!file_exists($path)){
      throw new \Exception('The file "'.$path.'" was not found');
    }
    $this->fit_path = $path;
    $this->csv_key  = self::cacheKeyFromFilePath($path);

    $this->converted_to_csv = $this->cachedCSVFilesExist();
  }

  public static function cacheKeyFromFilePath($path)
  {
    if(file_exists($path)){
      return md5(date("YmdHis",filemtime($path)).$path);
    } else {
      throw new \Exception('Cannot create cache key. File "' . $path . '" does not exist');
    }
  }

  public function cachedCSVFilesExist()
  {
    foreach ($this->getCSVPaths() as $path) {
      if(!file_exists($path)){
        return false;
      }
    }
    return true;
  }

  public function activitySessionData()
  {

    if(!$this->converted_to_csv){
      $this->_writeCSVFilesToTmp();
    }

    $csv_paths = $this->getCSVPaths();
    $lines = file($csv_paths['session_data']);

    $headers = self::arrayFromFitCSV($lines[0]);
    return array_combine($headers, self::arrayFromFitCSV($lines[1]));

  }

  // handle substring and trimming before getting array from CSV line - remove extra comma at the end of the line
  public static function arrayFromFitCSV($line)
  {
    return str_getcsv(substr(trim($line),0,-1));
  }

  // the new version of the FIT tool adds a BOM
  // TODO: just remove from the file when writing
  public static function remove_utf8_bom($text)
  {
      $bom = pack('H*','EFBBBF');
      $text = preg_replace("/^$bom/", '', $text);
      return $text;
  }

  public function activityRecordsData()
  {
    // This function will not work if the data file contains more than one record type
    $records = array();

    if(!$this->converted_to_csv){
      $this->_writeCSVFilesToTmp();
    }

    $csv_paths = $this->getCSVPaths();
    $lines = file($csv_paths['records_data']);
    $num_records = count($lines) - 1;

    $headers = self::arrayFromFitCSV(SELF::remove_utf8_bom($lines[0]));
    $num_header_columns = count($headers);

    for($i = 1; $i <= $num_records; $i++){
      $record_fields = self::arrayFromFitCSV($lines[$i]);
      $num_record_fields = count($record_fields);

      if($num_record_fields < $num_header_columns){
        for($j = $num_record_fields; $j < $num_header_columns; $j++){
          $record_fields[$j] = '';
        }
      }
      $records[] = array_combine($headers, $record_fields);
    }
    return $records;
  }

  private function _writeCSVFilesToTmp()
  {

    $csv_paths = $this->getCSVPaths();
    $cmd_common = 'java -jar '.$this->fitcsv_jar_path.' -b '.$this->fit_path.' ';

    // TODO: replace exec with commands from symfony/process component
    exec($cmd_common.$csv_paths['session'].' --data session');
    exec($cmd_common.$csv_paths['records'].' --data record');

    if(!file_exists ($csv_paths['session_data'])){
      throw new \Exception('The FITCSVParser failed to write the csv session cache file: '.$csv_paths['session_data']);
    }
    if(!file_exists ($csv_paths['records_data'])){
      throw new \Exception('The FITCSVParser failed to write the csv records cache file: '.$csv_paths['records_data']);
    }
    $this->converted_to_csv = true;
  }

  public function getCSVPaths()
  {
    $common_path = $this->tmp_unpack_path.$this->csv_key;
    return [
      'session'       => $common_path . '_session.csv',
      'records'       => $common_path . '_records.csv',
      'session_data'  => $common_path . '_session_data.csv',
      'records_data'  => $common_path . '_records_data.csv',
    ];
  }

/*****************************************************************************************
  New functionality for parsing CSV FIT file is below this divider
******************************************************************************************/

  /**
   * Get all FIT\Messages from a given CSV FIT file
   *
   * This is the start of new functionality to deal in FIT models rather than
   * GPSTrack* models.
   *
   * @param  string $csv_path         Path to a CSV file which has been converted using the FitCSVTool from a .fit file
   * @return [\Model\FIT\Messages]    Array of Messages extracted from the file
   */
  public static function messagesFromCSVFile($csv_path)
  {
    $messages = [];
    $current_definitions = [];
    $lines = file($csv_path);
    array_shift($lines);

    // For debugging only
    $line_limit = null;
    if($line_limit){
      $lines = array_slice($lines, 0, $line_limit);
    }

    foreach($lines as $line){
      $message = self::getMessageFromCSVLine($line);
      if($message->getType() === Message::MESSAGE_TYPE_DEFINITION){
        $current_definitions[$message->getLocalNumber()] = $message;
      }
      if($message->getType() === Message::MESSAGE_TYPE_DATA){
        if(!$current_definitions[$message->getLocalNumber()]){
          throw new \Exception("Attempting to parse data row when no definition has been parsed for local_number " . $message->getLocalNumber());
        }
        $message->setDefinition($current_definitions[$message->getLocalNumber()]);
      }
      $messages[] = $message;
    }
    return $messages;
  }

  public static function getMessageFromCSVLine($line)
  {
    $line_array = self::arrayFromFitCSV($line);

    $message_array = [
      'type'              => $line_array[0],
      'local_number'      => $line_array[1],
      'name'              => $line_array[2],
      'fields'            => self::getFieldsFromCSVArray($line_array),
      'num_empty_fields'  => self::getNumberOfEmptyFields($line_array)
    ];

    if($message_array['type'] === Message::MESSAGE_TYPE_DEFINITION){
      return new DefinitionMessage($message_array);
    }
    if($message_array['type'] === Message::MESSAGE_TYPE_DATA){
      return new DataMessage($message_array);
    }
  }

  /**
   * Takes the `field{n}`, `value{n}`, and `untis{n}` elements from the CSV
   * array and creates `FIT\Fields` or `FIT\FieldDefinitions`
   *
   * This function assumes the array $a will have triplets of name, value, units
   * preceeded by the type, local number, message name triplet
   *
   * @return [FIT\Field] an array of Fields
   */
  public static function getFieldsFromCSVArray(array $a)
  {
    $fields = [];
    $b = array_chunk($a, self::COLUMNS_PER_FIELD);
    list($type, $local_number, $message_name) = array_shift($b);
    foreach ($b as $c) {
      if($c[0]){
        list($name, $value, $units) = $c;
        $field_array = [
          'name'  => $name,
          'value' => $value,
          'units' => $units
        ];

        if($type === Message::MESSAGE_TYPE_DEFINITION){
          $fields[$name] = new FieldDefinition($field_array);
        }
        if($type === Message::MESSAGE_TYPE_DATA){
          $fields[$name] = new Field($field_array);
        }
      }
    }
    return $fields;
  }

  /**
   * Takes a row from a FIT CSV file and returns the number of empty $fields
   *
   * This functionality is hopefully temporary to deal with the "extra commas"
   * issue
   *
   * @param  string $row  a row of CSV from the FIT CSV file
   * @return int          number of empty fields in the provided row of CSV
   */
  public static function getNumberOfEmptyFields(array $line_array) : int
  {
    $num_empty_fields = 0;
    $grouped_columns = array_chunk($line_array, self::COLUMNS_PER_FIELD);
    foreach ($grouped_columns as $column_group) {
      if(!$column_group[0] && !$column_group[1] && !$column_group[2]){
        $num_empty_fields++;
      }
    }
    return $num_empty_fields;
  }

  /**
   * Take a line of CSV (presumably the first line of a file), and return a normalized array of values
   * @param  string $line   string of CSV containing headers
   * @return array          array containing normalized header values
   */
  public static function normalizeHeaders($line)
  {
    return array_map(
      function($header) {
        return u($header)->snake();
      },
      self::arrayFromFitCSV(self::remove_utf8_bom($line))
    );
  }

} // end class FITCSVParser
