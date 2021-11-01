<?php

use PHPUnit\Framework\TestCase;
use App\Model\FIT\BaseType;
use App\Model\FIT\GlobalProfileAccess;

final class BaseTypeTest extends TestCase
{

  /**
   * @dataProvider baseTypesProvider
   */
  public function testConstructorAndGetters($name, $identifier, $size, $invalid_value)
  {
    $base_type = new BaseType([
      'name'            => $name,
      'identifier'      => $identifier,
      'size'            => $size,
      'invalid_value'   => $invalid_value
    ]);
    $this->assertEquals($name, $base_type->getName());
    $this->assertEquals($identifier, $base_type->getIdentifier());
    $this->assertEquals($size, $base_type->getSize());
    $this->assertEquals($invalid_value, $base_type->getInvalidValue());
  }

  public function baseTypesProvider()
  {
    return GlobalProfileAccess::getBaseTypesArray();
  }

  public function testInvalidBaseTypeThrowsException()
  {
    $this->expectException(\Exception::class);
    $base_type = new BaseType([
      'name'            => 'int256',
      'identifier'      => '0xFFFF',
      'size'            => 256,
      'invalid_value'   => 'does not matter'
    ]);
  }


}
