<?php

use PHPUnit\Framework\TestCase;
use App\Model\FIT\GlobalProfile;

final class GlobalProfileTest extends TestCase
{
  public function testGetFieldDefinition(): void
  {
    $definition = GlobalProfile::getFieldDefinition('event', 'data');
    $this->assertIsObject($definition);
    $this->assertEquals('data', $definition->getName());
  }
}
