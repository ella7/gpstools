<?php

use PHPUnit\Framework\TestCase;
use App\Model\FIT\DefinitionMessage;
use App\Model\FIT\FieldDefinition;
use App\Model\FIT\GlobalProfileAccess;

final class FieldDefinitionTest extends TestCase
{

  /**
   * @dataProvider getBaseTypeNameProvider
   */
  public function testGetBaseTypeName($field)
  {
    $this->assertContains($field->getBaseTypeName(), self::validBaseTypeNames());
  }

  public static function getBaseTypeNameProvider()
  {
    foreach (GlobalProfileAccess::getAllFieldDefinitions() as $field) {
      $results[] = [ 'field' =>  $field];
    }
    return $results;
  }

  protected static function validBaseTypeNames()
  {
    return [
      'enum',
      'sint8',
      'uint8',
      'sint16',
      'uint16',
      'sint32',
      'uint32',
      'string',
      'float32',
      'float64',
      'uint8z',
      'uint16z',
      'uint32z',
      'byte',
      'sint64',
      'uint64',
      'uint64z',
    ];
  }
}
