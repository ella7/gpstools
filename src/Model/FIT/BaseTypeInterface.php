<?php

namespace App\Model\FIT;

/**
 * // TODO:
 * I'm not convinced this is necessary yet. I'd like to set the type of FieldDefinition.type,
 * which can be a FieldType or a BaseType. I'm unsure whether to use inheritance or an interface.
 * I keep finding myself tempted to use a trait, but that won't solve the typing problem.
 *
 * I could create an abstract parent class IsOrHasABaseType. The class could implement getters and
 * setters for all of the baseType properties, and the only thing inheriting classes would need to
 * implement would be the getBaseType and setBaseType methods. The others would just call
 * getBaseType first and then call its property getters.  
 */
interface BaseTypeInterface
{
  public function getBaseTypeName();
  public function getBaseTypeIdentifier();
  public function getBaseTypeInvalidValue();
  public function getBaseTypeSize();
  public function getBaseType();
}
