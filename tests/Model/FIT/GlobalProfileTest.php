<?php

use PHPUnit\Framework\TestCase;
use App\Model\FIT\GlobalProfileAccess;

final class GlobalProfileTest extends TestCase
{

  public function testGlobalProfileSubfieldsHaveReferenceFields()
  {
    foreach(GlobalProfileAccess::getAllFieldDefinitions() as $field_definition){
      $field_def_num = $field_definition->getNumber();
      if($field_definition->hasSubfields()){
        foreach($field_definition->getSubfields() as $subfield){
          $this->assertGreaterThan(0, count($subfield->getRefFields()));
        }
      }
    }
  }
}
