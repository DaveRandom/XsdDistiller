<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Parser\Definitions;

use DaveRandom\XsdDistiller\DefinitionLocation;
use DaveRandom\XsdDistiller\EntityName;
use DaveRandom\XsdDistiller\FullyQualifiedName;

abstract class TypeDefinition extends EntityDefinition
{
    private $baseTypeName;

    protected function __construct(DefinitionLocation $definitionLocation, EntityName $name, ?FullyQualifiedName $baseTypeName)
    {
        parent::__construct($definitionLocation, $name);

        $this->baseTypeName = $baseTypeName;
    }

    public function hasBaseType(): bool
    {
        return $this->baseTypeName !== null;
    }

    public function getBaseTypeName(): FullyQualifiedName
    {
        if ($this->baseTypeName === null) {
            throw new \LogicException('Type definition has no base type');
        }

        return $this->baseTypeName;
    }

    abstract public function __toString(): string;
}
