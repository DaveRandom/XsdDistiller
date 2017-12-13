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

    /**
     * @return ComplexType
     */
    public function getBaseType(): ComplexType
    {
        if ($this->base === null) {
            throw new \LogicException("No base type defined for {$this}");
        }

        return $this->base;
    }

    /**
     * @return Element[]
     */
    public function getMembers(): array
    {
        return $this->memberStore->getArrayCopy();
    }
}
