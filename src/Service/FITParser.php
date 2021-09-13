<?php

namespace App\Service;

class FITParser {

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

    echo "we're in the constructor\n";
    echo $this->fitcsv_jar_path."\n";
    echo $this->tmp_unpack_path."\n";
  }

  public function setFitPath(string $path)
  {
    if(!file_exists($path)){
      throw new \Exception('The file "'.$path.'" was not found');
    }
    $this->fit_path = $path;
    $this->csv_key       = self::cacheKeyFromFilePath($path);

    $this->_writeCSVFilesToTmp();
  }

  public static function cacheKeyFromFilePath($path)
  {
    if(file_exists($path)){
      return md5(date("YmdHis",filemtime($path)).$path);
    } else {
      throw new \Exception('Cannot create cache key. File "' . $path . '" does not exist');
    }
  }

  public function activitySessionData()
  {

    if(!$this->converted_to_csv){
      $this->_writeCSVFilesToTmp();
    }

    $session_data_path = $this->tmp_unpack_path.$this->csv_key.'_session_data.csv';
    $lines = file($session_data_path);

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

    $records_data_path = $this->tmp_unpack_path.$this->csv_key.'_records_data.csv';
    $lines = file($records_data_path);
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
    $session_output_path = $this->tmp_unpack_path.$this->csv_key.'_session.csv';
    $records_output_path = $this->tmp_unpack_path.$this->csv_key.'_records.csv';

    $cmd_common = 'java -jar '.$this->fitcsv_jar_path.' -b '.$this->fit_path.' ';

    // TODO: replace exec with commands from symfony/process component
    exec($cmd_common.$session_output_path.' --data session');
    exec($cmd_common.$records_output_path.' --data record');

    if(!file_exists ($session_output_path)){
      throw new \Exception('The FITParser failed to write the csv session cache file: '.$session_output_path);
    }
    if(!file_exists ($records_output_path)){
      throw new \Exception('The FITParser failed to write the csv records cache file: '.$records_output_path);
    }
    $this->converted_to_csv = true;
  }

} // end class FITParser
