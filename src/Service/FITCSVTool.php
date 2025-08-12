<?php

namespace App\Service;

use Symfony\Component\Process\Process;

class FITCSVTool
{

  const FIT2CSV = 1;
  const CSV2FIT = 2;

  protected $fitcsv_jar_path;
  protected $tmp_unpack_path;

  protected $path = ''; // path of file to convert whether its the .fit or .csv
  protected $cache_key = '';

  protected $data_filters = [];
  protected $defn_filters = [];

  protected $mode;

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

  public function convertFIT2CSV($path, $data_filters = [], $defn_filters = [])
  {
    $this->setMode(self::FIT2CSV);
    $this->setPath($path);
    $this->data_filters = $data_filters;
    $this->defn_filters = $defn_filters;
    $this->convert();
  }

  public function setPath(string $path)
  {
    if(!file_exists($path)){
      throw new \Exception('The file "'.$path.'" was not found');
    }
    $this->path       = $path;
    $this->cache_key  = self::cacheKeyFromFilePath($path);
  }

  public function addDataFilter(string $filter)
  {
    $this->data_filters[] = $filter;
  }

  public function addDefnFilter(string $filter)
  {
    $this->defn_filters[] = $filter;
  }

  public function addFilter(string $filter)
  {
    $this->addDefnFilter($filter);
    $this->addDataFilter($filter);
  }

  public function setMode($mode)
  {
    $this->mode = $mode;
  }

  protected function getModeFlag()
  {
    if($this->mode === self::FIT2CSV) return '-b';
    if($this->mode === self::CSV2FIT) return '-c';
  }

  public function convert()
  {
    $process = $this->getConversionProcess();
    $process->run();
    if($process->getErrorOutput()){
      throw new \Exception("The call `".$process->getCommandLine() . "` resulted in an error", 1);
    }
    return $process->isSuccessful();
  }

  public function getConversionProcess()
  {
    $output_paths = $this->getPathsForConvertedFiles();

    $args = $this->baseCommand();
    $args[] = $this->getModeFlag();
    $args[] = $this->path;
    $args[] = $output_paths['main'];
    if($this->data_filters){
      $args[] = '--data';
      $args[] = implode(',', $this->data_filters);
    }
    if($this->defn_filters){
      $args[] = '--defn';
      $args[] = implode(',', $this->defn_filters);
    }
    return new Process($args);
  }

  /**
   * Get file paths for the main and data files that will be created
   *
   * Returns the data file path regardless of whether a data filter is set.
   */
  public function getPathsForConvertedFiles()
  {
    $common_path = $this->tmp_unpack_path.$this->cache_key;
    $extension   = $this->mode === self::FIT2CSV ? '.csv' : '.fit';
    return [
      'main' => $common_path . $extension,
      'data' => $common_path . '_data' . $extension
    ];
  }

  public static function cacheKeyFromFilePath($path)
  {
    if(file_exists($path)){
      return md5(date("YmdHis",filemtime($path)).$path);
    } else {
      throw new \Exception('Cannot create cache key. File "' . $path . '" does not exist');
    }
  }

  protected function baseCommand()
  {
    return ['java', '-jar', $this->fitcsv_jar_path];
  }

  public function callToolWithNoArguments(): bool
  {
    $process = new Process($this->baseCommand());
    $process->run();
    if($process->getErrorOutput()){
      throw new \Exception("The call `".$process->getCommandLine() . "` resulted in an error", 1);
    }
    return $process->isSuccessful();
  }
}
