<?php
namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\FITCSVTool;

final class FITCSVToolTest extends KernelTestCase
{
  protected $tool;
  protected $fitcsv_jar_path;
  protected $tmp_unpack_path;

  public function setUp() : void
  {
    self::bootKernel();
    $container    = static::getContainer();
    $this->tool   = $container->get(FITCSVTool::class);
    $this->fitcsv_jar_path = $container->get('parameter_bag')->get('app.fitcsv_jar_path');
    $this->tmp_unpack_path = $container->get('parameter_bag')->get('app.tmp_unpack_path');
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
}
