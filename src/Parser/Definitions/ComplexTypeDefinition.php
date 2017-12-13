<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Parser\Definitions;

use DaveRandom\XsdDistiller\DefinitionLocation;
use DaveRandom\XsdDistiller\EntityName;
use DaveRandom\XsdDistiller\FullyQualifiedName;

final class ComplexTypeDefinition extends TypeDefinition
{
    private $memberDefinitions;

    public function __construct(DefinitionLocation $definitionLocation, EntityName $name, ?FullyQualifiedName $baseTypeName, array $memberDefinitions)
    {
        parent::__construct($definitionLocation, $name, $baseTypeName);

        $this->memberDefinitions = $memberDefinitions;
    }

    /**
     * @return ElementDefinition[]
     */
    public function getMemberDefinitions(): array
    {
        return $this->memberDefinitions;
    }

    public function __toString(): string
    {
        return "complex type {$this->getName()} defined at {$this->getDefinitionLocation()}";
    }
}
