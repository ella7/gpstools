<?php

namespace App\Service;

use Symfony\Component\Process\Process;

class FITCSVTool
{
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
