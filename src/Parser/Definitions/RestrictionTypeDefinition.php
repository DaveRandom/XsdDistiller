<?php declare(strict_types=1);

namespace DaveRandom\XsdDistiller\Parser\Definitions;

use DaveRandom\XsdDistiller\DefinitionLocation;
use DaveRandom\XsdDistiller\EntityName;
use DaveRandom\XsdDistiller\FullyQualifiedName;

final class RestrictionTypeDefinition extends SimpleTypeDefinition
{
    private $restrictionElement;

    public function __construct(DefinitionLocation $definitionLocation, EntityName $name, FullyQualifiedName $baseTypeName, \DOMElement $restrictionElement)
    {
        parent::__construct($definitionLocation, $name, $baseTypeName);

        $this->restrictionElement = $restrictionElement;
    }

    public function getRestrictionElement(): \DOMElement
    {
        return $this->restrictionElement;
    }

    public function __toString(): string
    {
        return "restriction type {$this->getName()} defined at {$this->getDefinitionLocation()}";
    }
}
