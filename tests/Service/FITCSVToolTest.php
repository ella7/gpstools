<?php
namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\FITCSVTool;

final class FITCSVToolTest extends KernelTestCase
{
  protected $tool;
  protected $fitcsv_jar_path;
  protected $tmp_unpack_path;
  protected $project_dir;

  public function setUp() : void
  {
    self::bootKernel();
    $container    = static::getContainer();
    $this->tool   = $container->get(FITCSVTool::class);
    $this->fitcsv_jar_path = $container->get('parameter_bag')->get('app.fitcsv_jar_path');
    $this->tmp_unpack_path = $container->get('parameter_bag')->get('app.tmp_unpack_path');
    $this->project_dir = $container->get('kernel')->getProjectDir();
  }

  // really just checking that no errors happen during setup
  public function testFITCSVToolConstructor(): void
  {
    $this->assertTrue(true);
  }

  public function testCallToolWithNoArguments(): void
  {
    $this->assertTrue($this->tool->callToolWithNoArguments());
  }

  public function testConvertFIT2CSV()
  {
    $fit_path = $this->project_dir . '/tests/Data/Activity.fit';
    $expected_csv_path = $this->project_dir . '/tests/Data/Activity_bug.csv';

    $this->tool->convertFIT2CSV($fit_path);
    $converted_csv_paths = $this->tool->getPathsForConvertedFiles();
    $this->assertFileEquals($expected_csv_path, $converted_csv_paths['main']);
  }
}
