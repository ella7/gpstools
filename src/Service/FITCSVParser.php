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

  protected $message_limit; /**
   * Create a FIT CSV parser service.
   *
   * Validates that the FitCSVTool JAR exists and ensures the temporary unpack directory exists (creates it if missing),
   * then stores the provided paths for later use.
   *
   * @param string $fitcsv_jar_path Path to the FitCSVTool JAR executable. Must exist or an Exception is thrown.
   * @param string $tmp_unpack_path Path to a directory where temporary CSV cache files will be created; the directory
   *                               will be created if it does not already exist.
   *
   * @throws \Exception If the FitCSVTool JAR is not found at $fitcsv_jar_path.
   */

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

  /**
   * Set the FIT file to parse and update internal cache state.
   *
   * Validates that the provided file path exists, stores it as the current
   * FIT path, computes a cache key based on the file path and modification
   * time, and updates the `converted_to_csv` flag based on whether cached CSV
   * outputs already exist for that file.
   *
   * @param string $path Path to the .fit file.
   * @throws \Exception If the file does not exist.
   */
  public function setFitPath(string $path)
  {
    if(!file_exists($path)){
      throw new \Exception('The file "'.$path.'" was not found');
    }
    $this->fit_path = $path;
    $this->csv_key  = self::cacheKeyFromFilePath($path);

    $this->converted_to_csv = $this->cachedCSVFilesExist();
  }

  /**
   * Create a deterministic cache key for a file path based on its path and last modification time.
   *
   * Produces an MD5 hash of the concatenation of the file's modification timestamp (formatted as YmdHis)
   * and the original file path. This ensures the key changes when the file is modified.
   *
   * @param string $path Path to the file. Must exist.
   * @return string MD5 hash to use as a cache key.
   * @throws \Exception If the file at $path does not exist.
   */
  public static function cacheKeyFromFilePath($path)
  {
    if(file_exists($path)){
      return md5(date("YmdHis",filemtime($path)).$path);
    } else {
      throw new \Exception('Cannot create cache key. File "' . $path . '" does not exist');
    }
  }

  /**
   * Check whether all expected cached CSV files exist for the current FIT file.
   *
   * Iterates the file paths returned by getCSVPaths() and returns true only if every
   * path exists on disk; returns false as soon as a missing file is found.
   *
   * @return bool True when all expected cache files are present, false otherwise.
   */
  public function cachedCSVFilesExist()
  {
    foreach ($this->getCSVPaths() as $path) {
      if(!file_exists($path)){
        return false;
      }
    }
    return true;
  }

  /**
   * Return session-level CSV data as an associative array of header => value.
   *
   * Ensures CSV cache exists (generating it if necessary), reads the cached
   * session_data CSV, and combines the first row as headers with the second
   * row as values.
   *
   * @return array<string,string> Associative array mapping session CSV header names to their values.
   */
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

  /**
   * Parse a single FIT CSV line into an array of fields.
   *
   * Trims whitespace, removes a single trailing comma if present (by dropping the last character),
   * then parses the line with PHP's CSV parser (str_getcsv) and returns the resulting array of values.
   *
   * @param string $line The raw CSV line (may include trailing comma and surrounding whitespace).
   * @return string[] Array of parsed field values.
   */
  public static function arrayFromFitCSV($line)
  {
    return str_getcsv(substr(trim($line),0,-1));
  }

  // the new version of the FIT tool adds a BOM
  /**
   * Remove a UTF-8 Byte Order Mark (BOM) from the start of a string, if present.
   *
   * @param string $text Input text that may start with a UTF-8 BOM.
   * @return string The input text with a leading UTF-8 BOM removed (unchanged if none present).
   */
  public static function remove_utf8_bom($text)
  {
      $bom = pack('H*','EFBBBF');
      $text = preg_replace("/^$bom/", '', $text);
      return $text;
  }

  /**
   * Parse and return activity records from the cached FIT CSV "records_data" file.
   *
   * Ensures CSV cache exists (generating it if necessary), reads the cached records CSV,
   * normalizes the header row, and returns an array of associative records where each
   * record maps header names to field values. Short data rows are padded with empty
   * strings so they match the header column count.
   *
   * Note: this function assumes a single record type in the CSV; it may not behave
   * correctly if the file contains multiple differing record formats.
   *
   * @return array<int, array<string, string>> Array of records indexed numerically; each record is an associative array keyed by header name.
   */
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

  /**
   * Generate session and records CSV files for the current FIT file into the temporary cache.
   *
   * Runs the external FitCSVTool JAR to produce session and record CSV outputs (and their
   * corresponding `_data` cache files) in the parser's temporary directory, then validates
   * that the expected cache files were created. If successful, marks the parser as having
   * a converted CSV cache.
   *
   * Side effects:
   * - Executes an external `java -jar` command.
   * - Writes CSV files to the paths returned by getCSVPaths().
   * - Sets $this->converted_to_csv to true on success.
   *
   * Exceptions:
   * - Throws an Exception when an expected cache file (session_data or records_data) is not found
   *   after the external command runs.
   */
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

  /**
   * Return file paths for the CSV files associated with the current FIT file cache key.
   *
   * Builds a base path from the parser's temporary unpack directory and the computed
   * csv_key, and returns an associative array with the expected CSV filenames:
   * - 'session'      => raw session CSV
   * - 'records'      => raw records CSV
   * - 'session_data' => cached/parsed session data CSV
   * - 'records_data' => cached/parsed records data CSV
   *
   * @return array<string,string> Associative array mapping the CSV role to its full filesystem path.
   */
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
   * Parse a FitCSVTool-generated CSV and return an ordered list of FIT Message objects.
   *
   * Reads the CSV file (skipping the header) and converts each line into a Message
   * (DefinitionMessage or DataMessage). Definition messages are recorded by their
   * local number and later attached to corresponding Data messages. An optional
   * internal message limit may truncate the number of processed lines.
   *
   * @param string $csv_path Path to a CSV file produced by the FitCSVTool for a .fit file.
   * @return \App\Model\FIT\Message[] Array of Message objects (DefinitionMessage and DataMessage) in file order.
   * @throws \Exception If a Data message references a local number for which no Definition has been parsed.
   */
  public function messagesFromCSVFile($csv_path)
  {
    $messages = [];
    $current_definitions = [];
    $lines = file($csv_path);
    array_shift($lines);

    if($this->message_limit){
      $lines = array_slice($lines, 0, $this->message_limit);
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

  /**
   * Parse a single CSV line and return the corresponding FIT Message object.
   *
   * Converts a CSV message line into a Message instance (DefinitionMessage or
   * DataMessage) by extracting the message type, local number, name, fields, and
   * count of empty field groups from the line.
   *
   * @param string $line A single CSV-formatted message line from a FIT CSV export.
   * @return Message|DefinitionMessage|DataMessage|null The constructed message object, or null if the line's type is not a recognized message type.
   */
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
   * Convert CSV field triplets into FIT Field or FieldDefinition objects.
   *
   * Parses an array produced from a FIT CSV line where values are arranged as:
   * [type, local_number, message_name, field1_name, field1_value, field1_units, field2_name, ...].
   * For each non-empty field name triplet, returns a FieldDefinition when the line
   * is a DEFINITION record (the definition's size is set from the parsed value),
   * or a Field for DATA records.
   *
   * @param array $a CSV line split into columns (must contain the initial triplet then groups of three columns per field)
   * @return array<int, \App\Model\FIT\Field|\App\Model\FIT\FieldDefinition> Array of created Field or FieldDefinition objects
   */
  public static function getFieldsFromCSVArray(array $a)
  {
    $fields = [];
    $b = array_chunk($a, self::COLUMNS_PER_FIELD);
    list($type, $local_number, $message_name) = array_shift($b);
    foreach ($b as $c) {
      if($c[0]){
        list($name, $value, $units) = $c;

        if($type === Message::MESSAGE_TYPE_DEFINITION){
          $field_properties = [
            'name'  => $name,
            'units' => $units
          ];
          $field = FieldDefinition::initFromGlobalProfileByNames(
            $message_name,
            $field_properties
          );
          $field->setSizeFromNumberOfValues($value);
          $fields[] = $field;
        }
        if($type === Message::MESSAGE_TYPE_DATA){
          $field_properties = [
            'name'  => $name,
            'value' => $value,
            'units' => $units
          ];
          $fields[] = new Field($field_properties);
        }
      }
    }
    return $fields;
  }

  /**
   * Count groups of empty FIT CSV fields in a parsed CSV row.
   *
   * Interprets the input array as consecutive triplets of columns (one field =
   * three columns, per COLUMNS_PER_FIELD) and returns how many triplets are
   * entirely empty. Values that are null, empty string, or otherwise falsy are
   * considered empty.
   *
   * @param array $line_array Array of CSV column values for a single row.
   * @return int Number of three-column field groups that are entirely empty.
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
   * Parse a CSV header line, remove any UTF‑8 BOM, and return header names converted to snake_case.
   *
   * The function splits the CSV line into fields (handling trailing commas), strips a leading UTF‑8
   * BOM if present, and normalizes each header to snake_case using Symfony's string utility.
   *
   * @param string $line CSV header line (typically the first line of a CSV file)
   * @return string[] Array of normalized header names in snake_case
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

  /**
   * Set a maximum number of message lines to parse from CSV input.
   *
   * When set, messagesFromCSVFile() will stop after this many message lines have been processed.
   *
   * @param int|null $limit Maximum number of message lines to parse, or null to remove the limit.
   * @return void
   */
  public function setMessageLimit($limit)
  {
    $this->message_limit = $limit;
  }


} // end class FITCSVParser
