<?php

use PHPUnit\Framework\TestCase;
use App\Model\FIT\DefinitionMessage;
use App\Model\FIT\FieldDefinition;

final class DefinitionMessageTest extends TestCase
{
  // test shows that passing an array of properties or an already formed FieldDefinition
  // has the same result. Not particularly useful right now.
  public function testSetFields()
  {
    $msg1 = new DefinitionMessage([]);
    $msg2 = new DefinitionMessage([]);

    $array = [
      'name'  => 'my test',
      'value' => 1234,
      'units' => 'm/s'
    ];

    $field = new FieldDefinition($array);
    $msg1->setFields([$field]);
    $msg2->setFields([$array]);

    $this->assertEquals($msg1, $msg2);
  }

  public function testSetInvalidType()
  {
    $this->expectException(\Exception::class);

    $msg = new DefinitionMessage([]);
    $msg->setType('invalid type');
  }

  public function testSetValidType()
  {
    $msg = new DefinitionMessage([]);
    $msg->setType(DefinitionMessage::MESSAGE_TYPE_DEFINITION);
    $this->assertEquals(DefinitionMessage::MESSAGE_TYPE_DEFINITION, $msg->getType());
  }
}
