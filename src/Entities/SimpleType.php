<?php

namespace DaveRandom\XsdDistiller\Entities;

use DaveRandom\XsdDistiller\DefinitionLocation;
use DaveRandom\XsdDistiller\EntityName;

abstract class SimpleType extends Type
{
    private $base;

    public function __construct(?DefinitionLocation $definitionLocation, EntityName $name, ?SimpleType $base)
    {
        parent::__construct($definitionLocation, $name);

        $this->base = $base;
    }

    public function hasBaseType(): bool
    {
        return $this->base !== null;
    }

    public function getBaseType(): SimpleType
    {
        if ($this->base === null) {
            throw new \LogicException("No base type defined for {$this}");
        }

        return $this->base;
    }
}
