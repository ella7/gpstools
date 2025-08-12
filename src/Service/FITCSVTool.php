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

  /**
   * Initialize the FITCSV tool wrapper.
   *
   * Validates that the FIT/CSV jar exists and ensures the temporary unpack directory exists
   * (creates it recursively with mode 0755 if missing). Stores the jar path and tmp path.
   *
   * @param string $fitcsv_jar_path Filesystem path to the FITCSV jar file; must exist.
   * @param string $tmp_unpack_path Directory used for temporary output; will be created if missing.
   * @throws \Exception If the specified FITCSV jar file does not exist.
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
   * Convert a FIT file to CSV using the configured FITCSV jar.
   *
   * Sets the conversion mode to FIT→CSV, records the input path and optional
   * data/definition filters, then runs the conversion.
   *
   * @param string $path Path to the input FIT file to convert.
   * @param string[] $data_filters Optional list of data filter names to pass as `--data` to the tool.
   * @param string[] $defn_filters Optional list of definition filter names to pass as `--defn` to the tool.
   */
  public function convertFIT2CSV($path, $data_filters = [], $defn_filters = [])
  {
    $this->setMode(self::FIT2CSV);
    $this->setPath($path);
    $this->data_filters = $data_filters;
    $this->defn_filters = $defn_filters;
    $this->convert();
  }

  /**
   * Set the input file path and compute a cache key for it.
   *
   * Validates that the given file path exists, stores it on the instance,
   * and computes a cache key via cacheKeyFromFilePath().
   *
   * @param string $path Absolute or relative path to the input FIT/CSV file.
   * @throws \Exception If the file at $path does not exist.
   */
  public function setPath(string $path)
  {
    if(!file_exists($path)){
      throw new \Exception('The file "'.$path.'" was not found');
    }
    $this->path       = $path;
    $this->cache_key  = self::cacheKeyFromFilePath($path);
  }

  /**
   * Add a data filter that will be passed to the conversion tool.
   *
   * Appends a filter string to the list of data filters used when building
   * the conversion command (serialized as a comma-separated list if multiple).
   *
   * @param string $filter Filter identifier or expression to include in the data export.
   */
  public function addDataFilter(string $filter)
  {
    $this->data_filters[] = $filter;
  }

  /**
   * Add a definition filter to be applied during conversion.
   *
   * The provided filter string is appended to the internal list of definition filters
   * and will be included when building the converter command (sent via `--defn`).
   *
   * @param string $filter A definition filter expression (will be combined with others as comma-separated).
   */
  public function addDefnFilter(string $filter)
  {
    $this->defn_filters[] = $filter;
  }

  /**
   * Adds a filter to both definition and data filter lists.
   *
   * This is a convenience method that appends the provided filter to the
   * internal definition filters and data filters so it applies to both
   * sides of conversion.
   *
   * @param string $filter Filter expression or key to apply to both definition and data output
   */
  public function addFilter(string $filter)
  {
    $this->addDefnFilter($filter);
    $this->addDataFilter($filter);
  }

  /**
   * Set the conversion mode used by the tool.
   *
   * Accepted values are the class constants: `self::FIT2CSV` or `self::CSV2FIT`.
   *
   * @param int $mode Conversion mode constant.
   */
  public function setMode($mode)
  {
    $this->mode = $mode;
  }

  /**
   * Return the CLI flag that corresponds to the current conversion mode.
   *
   * '-b' is returned for FIT2CSV mode, '-c' for CSV2FIT mode. Returns null when
   * the mode is not set or is unrecognized.
   *
   * @return string|null
   */
  protected function getModeFlag()
  {
    if($this->mode === self::FIT2CSV) return '-b';
    if($this->mode === self::CSV2FIT) return '-c';
  }

  /**
   * Execute the configured conversion process (FIT ↔ CSV).
   *
   * Runs the assembled Symfony Process returned by getConversionProcess(). If the subprocess
   * produced any error output, an exception is thrown (the exception message includes the
   * invoked command line). Otherwise returns whether the subprocess exited successfully.
   *
   * @return bool True when the subprocess exited successfully, false otherwise.
   * @throws \Exception When the subprocess produced error output; message contains the command line.
   */
  public function convert()
  {
    $process = $this->getConversionProcess();
    $process->run();
    if($process->getErrorOutput()){
      throw new \Exception("The call `".$process->getCommandLine() . "` resulted in an error", 1);
    }
    return $process->isSuccessful();
  }

  /**
   * Build a Symfony Process configured to run the FIT/CSV conversion tool for the current settings.
   *
   * The returned Process is constructed with the base java -jar command, the current mode flag
   * (FIT2CSV or CSV2FIT), the configured input path, the main output path and optional
   * `--data` / `--defn` arguments when corresponding filters are present.
   *
   * @return \Symfony\Component\Process\Process A Process ready to be run to perform the conversion.
   */
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
   * Compute output file paths for the converted "main" and "data" files.
   *
   * The returned paths are constructed from the service's temporary unpack directory
   * and the instance cache key. The filename extension is `.csv` when the tool is
   * in FIT2CSV mode, otherwise `.fit`. The data file path (suffix `_data`) is
   * always included even if no data filter is configured.
   *
   * @return array{main:string,data:string} Array with keys:
   *   - `main`: full path for the primary converted file
   *   - `data`: full path for the associated data file
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

  /**
   * Create a stable cache key for a file based on its modification time and path.
   *
   * The key is the MD5 of the concatenation of the file's modification time
   * formatted as `YmdHis` and the file path. This ensures the key changes when
   * the file's contents (mtime) or location changes.
   *
   * @param string $path Path to an existing file.
   * @return string MD5 hash to use as a cache key.
   * @throws \Exception If the file does not exist.
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
   * Return the base command array used to invoke the FITCSV jar with Java.
   *
   * The returned array is the command prefix suitable for Symfony Process execution,
   * e.g. `['java', '-jar', '/path/to/fitcsv.jar']`.
   *
   * @return string[] Command segments for executing the FITCSV jar.
   */
  protected function baseCommand()
  {
    return ['java', '-jar', $this->fitcsv_jar_path];
  }

  /**
   * Runs the FIT/CSV jar with no arguments to verify the tool can be executed.
   *
   * Executes the base command (java -jar <fitcsv.jar>) and returns whether the process
   * completed successfully. If the process produced any error output, an Exception is thrown
   * containing the invoked command line.
   *
   * @return bool True if the process exited successfully; false otherwise.
   * @throws \Exception If the process produced error output.
   */
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
