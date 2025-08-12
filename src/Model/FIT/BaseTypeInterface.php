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
  /**
 * Get the human-readable name of the base type.
 *
 * Returns the base type's display name as used by callers or UI. May be null if no base type is set.
 *
 * @return string|null The base type name, or null when not available.
 */
public function getBaseTypeName();
  /**
 * Return the identifier used to reference the underlying base type.
 *
 * The identifier is an implementation-defined value (for example an internal code,
 * slug, or numeric id) that uniquely references the base type this object delegates to.
 *
 * @return mixed The base type identifier.
 */
public function getBaseTypeIdentifier();
  /**
 * Returns the canonical "invalid" value for the base type.
 *
 * This value is used to represent an invalid, missing, or sentinel value for the underlying base type.
 *
 * @return mixed The invalid sentinel for the base type (may be null or another type depending on implementation).
 */
public function getBaseTypeInvalidValue();
  /**
 * Return the size of the base type.
 *
 * The value is expressed in bytes when applicable; implementations may
 * return null if the base type does not have a defined or applicable size.
 *
 * @return int|null Size in bytes, or null if not applicable.
 */
public function getBaseTypeSize();
  /**
 * Return the underlying base type associated with this instance.
 *
 * The concrete return type is implementation-specific (for example a `BaseType` or `FieldType` object)
 * and may be `null` if no base type is set.
 *
 * @return mixed|null The base type object or `null` when unset.
 */
public function getBaseType();
}
