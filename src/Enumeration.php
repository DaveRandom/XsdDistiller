<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller;

abstract class Enumeration
{
    final public static function parse(string $name, bool $caseInsensitive = false)
    {
        $constants = (new \ReflectionClass(static::class))->getConstants();

        if (isset($constants[$name])) {
            return $constants[$name];
        }

        if ($caseInsensitive) {
            foreach ($constants as $constant => $value) {
                if (\strcasecmp($constant, $name) === 0) {
                    return $value;
                }
            }
        }

        throw new \InvalidArgumentException("Invalid enumeration member name: {$name}");
    }

    final public static function getNameForValue($value, bool $strict = true)
    {
        if (false !== $name = \array_search($value, (new \ReflectionClass(static::class))->getConstants(), $strict)) {
            return $name;
        }

        throw new \InvalidArgumentException("Value not present in enumeration: {$value}");
    }

    final private function __construct() { }
}
