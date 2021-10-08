<?php

namespace App\Service;

use App\Model\FIT\Message;
use App\Model\FIT\DefinitionMessage;
use App\Model\FIT\DataMessage;
use App\Model\FIT\FieldDefinition;
use App\Model\FIT\Field;
use function Symfony\Component\String\u;
use Symfony\Component\Stopwatch\Stopwatch;

class FITParser {

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

    $headers = self::getFitCSV($lines[0]);
    return array_combine($headers, self::getFitCSV($lines[1]));

  }

  // handle substring and trimming before getting array from CSV line
  public static function getFitCSV($line)
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

    $headers = self::getFitCSV(SELF::remove_utf8_bom($lines[0]));
    $num_headers = count($headers);

    for($i = 1; $i <= $num_records; $i++){
      $record_fields = self::getFitCSV($lines[$i]);
      $num_record_fields = count($record_fields);

      if($num_record_fields < $num_headers){
        for($j = $num_record_fields; $j < $num_headers; $j++){
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
      throw new \Exception('The FITParser failed to write the csv session cache file: '.$csv_paths['session_data']);
    }
    if(!file_exists ($csv_paths['records_data'])){
      throw new \Exception('The FITParser failed to write the csv records cache file: '.$csv_paths['records_data']);
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
  public function messagesFromCSVFile($csv_path)
  {

    $current_definition = [];
    $lines = file($csv_path);
    $number_of_lines_to_read = count($lines) - 1;

    $headers = self::normalizeHeaders($lines[0]);

    $num_headers = count($headers);

    for($i = 1; $i <= $number_of_lines_to_read; $i++){
      $fields = self::getFitCSV($lines[$i]);
      $num_fields = count($fields);

      if($num_fields < $num_headers){
        for($j = $num_fields; $j < $num_headers; $j++){
          $fields[$j] = '';
        }
      }

      $row = array_combine($headers, $fields);

      $message_array = array_slice($row, 0, self::COLUMNS_BEFORE_FIELDS);

      if($row['type'] === Message::MESSAGE_TYPE_DEFINITION){
        $message_array['fields'] = self::getFieldDefinitionsFromCSVArray(
          $row['message'],
          array_slice($row, self::COLUMNS_BEFORE_FIELDS)
        );
        $message = new DefinitionMessage();
        $message->setPropertiesFromArray($message_array);
        $message->setUnitsForAllFieldDefinitionsFromGlobalProfile();
        $current_definition[$message->getLocalNumber()] = $message;
      }

      if($row['type'] === Message::MESSAGE_TYPE_DATA){
        $message_array['fields'] = self::getFieldsFromCSVArray(
          array_slice($row, self::COLUMNS_BEFORE_FIELDS)
        );
        if(!$current_definition[$row['local_number']]){
          throw new \Exception("Attempting to parse data row when no definition has been parsed for local_number " . $row['local_number']);
        }
        $message = new DataMessage($current_definition[$row['local_number']]);
        $message->setPropertiesFromArray($message_array);
      }
      $messages[] = $message;
    }
    return $messages;
  }

  /**
   * Takes the `field{n}`, `value{n}`, and `untis{n}` elements from the CSV
   * array and creates `FIT\FieldDefintions`
   *
   * This function assumes the array $a will have triplets of name, value, units
   *
   * @return [FIT\FieldDefintions] an array of FieldDefintions
   */
  public static function getFieldDefinitionsFromCSVArray($message_name, $a)
  {
    $field_definitions = [];
    $b = array_chunk($a, self::COLUMNS_PER_FIELD);
    foreach ($b as $c) {
      if($c[0]){
        list($field_name, $field_value) = $c;
        $field_definitions[$field_name] = new FieldDefinition([
          'name'  => $field_name,
          'value' => $field_value
        ]);
        $field_definitions[$field_name]->setSubfieldsFromGlobalProfile($message_name);
      }
    }
    return $field_definitions;
  }

  /**
   * Takes the `field{n}`, `value{n}`, and `untis{n}` elements from the CSV
   * array and creates key value pairs
   *
   * This function assumes the array $a will have triplets of name, value, units
   *
   * @return [field => value] an array of field values with field name as key and value as value - note that units are not used or captured.
   */
  public static function getFieldsFromCSVArray($a)
  {
    $fields = [];
    $b = array_chunk($a, self::COLUMNS_PER_FIELD);
    foreach ($b as $c) {
      if($c[0]){
        list($name, $value, $units) = $c;
        $fields[] = new Field([
          'name'  => $name,
          'value' => $value,
          'units' => $units
        ]);
      }
    }
    return $fields;
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
      self::getFitCSV(self::remove_utf8_bom($line))
    );
  }

} // end class FITParser
