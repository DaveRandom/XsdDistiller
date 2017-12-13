<?php

namespace DaveRandom\XsdDistiller\Entities;

use DaveRandom\XsdDistiller\DefinitionLocation;
use DaveRandom\XsdDistiller\EntityName;

final class ComplexType extends Type
{
    private $base;
    private $memberStore;

    public function __construct(?DefinitionLocation $definitionLocation, EntityName $name, ?ComplexType $base, \ArrayObject $memberStore)
    {
        parent::__construct($definitionLocation, $name);

        $this->base = $base;
        $this->memberStore = $memberStore;
    }

    public function hasBaseType(): bool
    {
        return $this->base !== null;
    }

    public function getBaseType(): ComplexType
    {
        if ($this->base === null) {
            throw new \LogicException("No base type defined for {$this}");
        }

        return $this->base;
    }

    public function getMembers(): ComplexTypeMemberCollection
    {
        if (!$this->memberStore instanceof ComplexTypeMemberCollection) {
            $this->memberStore = new ComplexTypeMemberCollection($this->memberStore);
        }

        return $this->memberStore;
    }
}
